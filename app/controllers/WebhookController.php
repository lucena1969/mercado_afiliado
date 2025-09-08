<?php
/**
 * Controller Webhook - Processamento de webhooks das redes
 */

class WebhookController {
    private $conn;
    private $integration;
    private $sale;
    private $product;
    private $productSubscription;
    private $syncLog;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->integration = new Integration($this->conn);
        $this->sale = new Sale($this->conn);
        $this->product = new Product($this->conn);
        $this->productSubscription = new ProductSubscription($this->conn);
        $this->syncLog = new SyncLog($this->conn);
    }

    // Processar webhook por plataforma
    public function processWebhook($platform, $token) {
        $start_time = microtime(true);
        
        try {
            // Logs de entrada
            error_log("Webhook recebido: {$platform} - Token: {$token}");
            
            // Validar plataforma
            $valid_platforms = ['hotmart', 'monetizze', 'eduzz', 'braip'];
            if (!in_array($platform, $valid_platforms)) {
                http_response_code(400);
                echo json_encode(['error' => 'Plataforma não suportada']);
                return;
            }

            // Buscar integração pelo token
            $integration_data = $this->integration->findByWebhookToken($token);
            if (!$integration_data) {
                error_log("Token de webhook não encontrado: {$token}");
                http_response_code(404);
                echo json_encode(['error' => 'Token não encontrado']);
                return;
            }

            // Verificar se a plataforma confere
            if ($integration_data['platform'] !== $platform) {
                error_log("Plataforma não confere: esperado {$integration_data['platform']}, recebido {$platform}");
                http_response_code(400);
                echo json_encode(['error' => 'Plataforma incorreta']);
                return;
            }

            // Obter dados do webhook
            $raw_payload = file_get_contents('php://input');
            $payload = json_decode($raw_payload, true);

            if (!$payload) {
                error_log("Payload inválido: {$raw_payload}");
                http_response_code(400);
                echo json_encode(['error' => 'Payload inválido']);
                return;
            }

            // Salvar evento no banco
            $this->saveWebhookEvent($integration_data['id'], $platform, $payload);

            // Processar evento
            $result = $this->processWebhookEvent($integration_data, $platform, $payload);

            $processing_time = round((microtime(true) - $start_time) * 1000);

            if ($result['success']) {
                // Log de sucesso
                SyncLog::logSuccess(
                    $this->conn,
                    $integration_data['id'],
                    'webhook',
                    'webhook_received',
                    [
                        'processed' => 1,
                        'created' => $result['created'] ? 1 : 0,
                        'updated' => $result['updated'] ? 1 : 0,
                        'processing_time_ms' => $processing_time,
                        'metadata' => [
                            'event_type' => $result['event_type'],
                            'sale_id' => $result['sale_id'] ?? null
                        ]
                    ]
                );

                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Webhook processado com sucesso']);
            } else {
                // Log de erro
                SyncLog::logError(
                    $this->conn,
                    $integration_data['id'],
                    'webhook',
                    'webhook_received',
                    $result['error'],
                    [
                        'processed' => 1,
                        'errors' => 1,
                        'processing_time_ms' => $processing_time
                    ]
                );

                http_response_code(422);
                echo json_encode(['error' => $result['error']]);
            }

        } catch (Exception $e) {
            $processing_time = round((microtime(true) - $start_time) * 1000);
            
            error_log("Erro no processamento do webhook: " . $e->getMessage());
            
            if (isset($integration_data)) {
                SyncLog::logError(
                    $this->conn,
                    $integration_data['id'],
                    'webhook',
                    'webhook_received',
                    $e->getMessage(),
                    ['processing_time_ms' => $processing_time]
                );
            }

            http_response_code(500);
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
    }

    // Salvar evento de webhook
    private function saveWebhookEvent($integration_id, $platform, $payload) {
        $query = "INSERT INTO webhook_events 
                  SET integration_id=:integration_id, platform=:platform, 
                      event_type=:event_type, payload_json=:payload_json";
        
        $stmt = $this->conn->prepare($query);
        
        // Extrair tipo de evento
        $event_type = $this->extractEventType($platform, $payload);
        
        $stmt->bindParam(":integration_id", $integration_id);
        $stmt->bindParam(":platform", $platform);
        $stmt->bindParam(":event_type", $event_type);
        $stmt->bindParam(":payload_json", json_encode($payload));
        
        $stmt->execute();
        
        return $this->conn->lastInsertId();
    }

    // Extrair tipo de evento do payload
    private function extractEventType($platform, $payload) {
        switch ($platform) {
            case 'hotmart':
                return $payload['event'] ?? 'unknown';
            case 'monetizze':
                return $payload['evento'] ?? $payload['status'] ?? 'unknown';
            case 'eduzz':
                return $payload['event_type'] ?? $payload['status'] ?? 'unknown';
            case 'braip':
                return $payload['event'] ?? $payload['status'] ?? 'unknown';
            default:
                return 'unknown';
        }
    }

    // Processar evento do webhook
    private function processWebhookEvent($integration_data, $platform, $payload) {
        try {
            // Criar service da plataforma
            $service = $this->createPlatformService($platform, $integration_data);
            
            // Processar webhook específico da plataforma
            $webhook_data = $service->processWebhook($payload);
            
            // Verificar se é assinatura ou venda
            if (isset($webhook_data['type']) && $webhook_data['type'] === 'subscription') {
                return $this->processSubscriptionEvent($integration_data, $webhook_data, $payload, $platform);
            } else {
                return $this->processSaleEvent($integration_data, $webhook_data, $payload, $service, $platform);
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Processar evento de venda (método original)
    private function processSaleEvent($integration_data, $sale_data, $payload, $service, $platform) {
        // Buscar ou criar produto se necessário
        $product_id = null;
        if (isset($sale_data['external_product_id'])) {
            $product_id = $this->ensureProduct($integration_data['id'], $sale_data, $service);
        }
        
        // Criar ou atualizar venda
        $sale = new Sale($this->conn);
        $sale->integration_id = $integration_data['id'];
        $sale->product_id = $product_id;
        $sale->external_sale_id = $sale_data['external_sale_id'];
        $sale->customer_name = $sale_data['customer_name'];
        $sale->customer_email = $sale_data['customer_email'];
        $sale->customer_document = $sale_data['customer_document'];
        $sale->amount = $sale_data['amount'];
        $sale->commission_amount = $sale_data['commission_amount'];
        $sale->currency = $sale_data['currency'];
        $sale->status = $sale_data['status'];
        $sale->payment_method = $sale_data['payment_method'];
        $sale->utm_source = $sale_data['utm_source'];
        $sale->utm_medium = $sale_data['utm_medium'];
        $sale->utm_campaign = $sale_data['utm_campaign'];
        $sale->utm_content = $sale_data['utm_content'];
        $sale->utm_term = $sale_data['utm_term'];
        $sale->conversion_date = $sale_data['conversion_date'];
        $sale->approval_date = $sale_data['approval_date'];
        $sale->refund_date = $sale_data['refund_date'];
        $sale->metadata_json = $sale_data['metadata_json'];

        // Verificar se é criação ou atualização
        $existing_sale = $sale->findByExternalId($integration_data['id'], $sale_data['external_sale_id']);
        $is_new = !$existing_sale;
        
        $success = $sale->createOrUpdate();
        
        if ($success) {
            // Atualizar último sync da integração
            $this->integration->updateLastSync($integration_data['id']);
            
            return [
                'success' => true,
                'created' => $is_new,
                'updated' => !$is_new,
                'sale_id' => $sale->id,
                'event_type' => $this->extractEventType($platform, $payload)
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Falha ao salvar venda no banco de dados'
            ];
        }
    }

    // Processar evento de assinatura (novo método)
    private function processSubscriptionEvent($integration_data, $subscription_data, $payload, $platform) {
        // Buscar ou criar produto se necessário
        $product_id = null;
        if (isset($subscription_data['external_product_id'])) {
            $product_data = [
                'external_product_id' => $subscription_data['external_product_id'],
                'product_name' => $subscription_data['product_name']
            ];
            $product_id = $this->ensureProduct($integration_data['id'], $product_data, null);
        }
        
        // Criar ou atualizar assinatura
        $subscription = new ProductSubscription($this->conn);
        $subscription->integration_id = $integration_data['id'];
        $subscription->product_id = $product_id;
        $subscription->external_subscription_id = $subscription_data['external_subscription_id'];
        $subscription->external_subscriber_code = $subscription_data['external_subscriber_code'];
        $subscription->external_plan_id = $subscription_data['external_plan_id'];
        $subscription->subscriber_name = $subscription_data['subscriber_name'];
        $subscription->subscriber_email = $subscription_data['subscriber_email'];
        $subscription->subscriber_phone_ddd = $subscription_data['subscriber_phone_ddd'];
        $subscription->subscriber_phone_number = $subscription_data['subscriber_phone_number'];
        $subscription->subscriber_cell_ddd = $subscription_data['subscriber_cell_ddd'];
        $subscription->subscriber_cell_number = $subscription_data['subscriber_cell_number'];
        $subscription->plan_name = $subscription_data['plan_name'];
        $subscription->status = $subscription_data['status'];
        $subscription->actual_recurrence_value = $subscription_data['actual_recurrence_value'];
        $subscription->currency = $subscription_data['currency'];
        $subscription->cancellation_date = $subscription_data['cancellation_date'];
        $subscription->date_next_charge = $subscription_data['date_next_charge'];
        $subscription->metadata_json = $subscription_data['metadata_json'];

        // Verificar se é criação ou atualização
        $existing_subscription = $subscription->findByExternalId(
            $integration_data['id'], 
            $subscription_data['external_subscription_id']
        );
        $is_new = !$existing_subscription;
        $previous_status = $existing_subscription ? $existing_subscription['status'] : null;
        
        $success = $subscription->createOrUpdate();
        
        if ($success) {
            // Log do evento de assinatura
            if ($subscription->id) {
                $event_type = $subscription_data['status'] === 'cancelled' ? 'cancelled' : 'created';
                $subscription->logEvent($event_type, $previous_status, $subscription_data['status'], $subscription_data);
            }
            
            // Atualizar último sync da integração
            $this->integration->updateLastSync($integration_data['id']);
            
            return [
                'success' => true,
                'created' => $is_new,
                'updated' => !$is_new,
                'subscription_id' => $subscription->id,
                'event_type' => $this->extractEventType($platform, $payload)
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Falha ao salvar assinatura no banco de dados'
            ];
        }
    }

    // Garantir que o produto existe
    private function ensureProduct($integration_id, $sale_data, $service) {
        if (!isset($sale_data['external_product_id'])) {
            return null;
        }

        // Buscar produto existente
        $existing_product = $this->product->findByExternalId($integration_id, $sale_data['external_product_id']);
        
        if ($existing_product) {
            return $existing_product['id'];
        }

        // Criar produto básico a partir dos dados da venda
        $product = new Product($this->conn);
        $product->integration_id = $integration_id;
        $product->external_id = $sale_data['external_product_id'];
        $product->name = $sale_data['product_name'] ?? 'Produto #' . $sale_data['external_product_id'];
        $product->price = $sale_data['amount'];
        $product->currency = $sale_data['currency'];
        $product->status = 'active';
        $product->metadata_json = json_encode([
            'created_from_webhook' => true,
            'sale_data' => $sale_data
        ]);

        if ($product->createOrUpdate()) {
            return $product->id;
        }

        return null;
    }

    // Criar service da plataforma
    private function createPlatformService($platform, $integration_data) {
        $config = json_decode($integration_data['config_json'], true) ?? [];
        
        switch ($platform) {
            case 'hotmart':
                return new HotmartService(
                    $integration_data['api_key'],
                    $integration_data['api_secret']
                );
            case 'monetizze':
                return new MonetizzeService($integration_data['api_key']);
            case 'eduzz':
                return new EduzzService($integration_data['api_key']);
            case 'braip':
                return new BraipService($integration_data['api_key']);
            default:
                throw new Exception('Plataforma não suportada: ' . $platform);
        }
    }

    // Método para teste manual de webhook
    public function testWebhook($platform, $token) {
        // Dados de teste para cada plataforma
        $test_payloads = [
            'hotmart' => [
                'event' => 'PURCHASE_COMPLETE',
                'data' => [
                    'transaction' => 'TEST_' . uniqid(),
                    'buyer' => [
                        'name' => 'João Teste',
                        'email' => 'joao@teste.com',
                        'document' => '12345678901'
                    ],
                    'purchase' => [
                        'price' => ['value' => 97.00, 'currency_code' => 'BRL'],
                        'order_date' => date('Y-m-d H:i:s'),
                        'approved_date' => date('Y-m-d H:i:s'),
                        'payment' => ['type' => 'CREDIT_CARD']
                    ],
                    'product' => [
                        'id' => 'TEST_PRODUCT_1',
                        'name' => 'Produto de Teste'
                    ],
                    'commissions' => [
                        ['value' => 48.50]
                    ]
                ]
            ],
            'monetizze' => [
                'evento' => 'venda_aprovada',
                'venda_id' => 'TEST_' . uniqid(),
                'cliente_nome' => 'Maria Teste',
                'cliente_email' => 'maria@teste.com',
                'valor' => 149.90,
                'comissao' => 74.95,
                'produto_id' => 'TEST_PRODUCT_2',
                'produto_nome' => 'Curso de Teste',
                'data_venda' => date('Y-m-d H:i:s')
            ]
        ];

        $payload = $test_payloads[$platform] ?? [];
        
        if (empty($payload)) {
            http_response_code(400);
            echo json_encode(['error' => 'Plataforma de teste não suportada']);
            return;
        }

        // Simular processamento
        $this->processWebhook($platform, $token);
    }
}