<?php
/**
 * Controller Eduzz - Processamento específico de webhooks da Eduzz
 * Baseado na documentação oficial: https://developers.eduzz.com
 * Evento principal: myeduzz.invoice_paid
 */

class EduzzController {
    private $conn;
    private $eduzzIntegration;
    private $sale;
    private $product;
    private $syncLog;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->eduzzIntegration = new EduzzIntegration($this->conn);
        $this->sale = new Sale($this->conn);
        $this->product = new Product($this->conn);
        $this->syncLog = new SyncLog($this->conn);
    }

    /**
     * Processar webhook da Eduzz
     * Formato: {id, event, data, sentDate}
     */
    public function processWebhook($token) {
        $start_time = microtime(true);

        try {
            // Log de entrada
            error_log("Webhook Eduzz recebido - Token: {$token}");

            // Buscar integração pelo token
            $integration_data = $this->eduzzIntegration->findByWebhookToken($token);
            if (!$integration_data) {
                error_log("Token de webhook Eduzz não encontrado: {$token}");
                http_response_code(404);
                echo json_encode(['error' => 'Token não encontrado']);
                return;
            }

            // Obter dados do webhook
            $raw_payload = file_get_contents('php://input');
            $payload = json_decode($raw_payload, true);

            if (!$payload) {
                error_log("Payload Eduzz inválido: {$raw_payload}");
                http_response_code(400);
                echo json_encode(['error' => 'Payload inválido']);
                return;
            }

            // Log do payload recebido
            error_log("Payload Eduzz: " . json_encode($payload));

            // Validar estrutura básica
            if (!isset($payload['event']) || !isset($payload['data'])) {
                error_log("Estrutura de webhook Eduzz inválida");
                http_response_code(400);
                echo json_encode(['error' => 'Estrutura de webhook inválida']);
                return;
            }

            // Validar originSecret
            $payload_origin_secret = $payload['data']['producer']['originSecret'] ?? '';
            $config = json_decode($integration_data['config_json'], true) ?? [];
            $stored_origin_secret = $config['origin_secret'] ?? $integration_data['api_secret'] ?? '';

            if (!$this->eduzzIntegration->validateWebhookOriginSecret($payload_origin_secret, $stored_origin_secret)) {
                error_log("originSecret inválido no webhook Eduzz. Esperado: {$stored_origin_secret}, Recebido: {$payload_origin_secret}");
                http_response_code(401);
                echo json_encode(['error' => 'Assinatura inválida']);
                return;
            }

            // Validar evento
            $event_type = $payload['event'];
            if (!$this->eduzzIntegration->isValidWebhookEvent($event_type)) {
                error_log("Evento Eduzz não suportado: {$event_type}");
                http_response_code(400);
                echo json_encode(['error' => 'Evento não suportado']);
                return;
            }

            // Salvar evento no banco
            $this->saveWebhookEvent($integration_data['id'], $payload);

            // Processar evento
            $result = $this->processWebhookEvent($integration_data, $payload);

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
                            'sale_id' => $result['sale_id'] ?? null,
                            'invoice_id' => $payload['data']['id'] ?? null
                        ]
                    ]
                );

                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Webhook Eduzz processado com sucesso',
                    'invoice_id' => $payload['data']['id'] ?? null
                ]);
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

            error_log("Erro no processamento do webhook Eduzz: " . $e->getMessage());

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

    /**
     * Salvar evento de webhook
     */
    private function saveWebhookEvent($integration_id, $payload) {
        $query = "INSERT INTO webhook_events
                  SET integration_id=:integration_id, platform='eduzz',
                      event_type=:event_type, payload_json=:payload_json";

        $stmt = $this->conn->prepare($query);

        // Extrair tipo de evento
        $event_type = $payload['event'] ?? 'unknown';

        $stmt->bindParam(":integration_id", $integration_id);
        $stmt->bindParam(":event_type", $event_type);
        $stmt->bindParam(":payload_json", json_encode($payload));

        $stmt->execute();

        return $this->conn->lastInsertId();
    }

    /**
     * Processar evento do webhook
     */
    private function processWebhookEvent($integration_data, $payload) {
        try {
            // Processar dados do webhook
            $sale_data = $this->eduzzIntegration->processWebhookData($payload);

            // Buscar ou criar produto se necessário
            $product_id = null;
            if (isset($sale_data['product_id'])) {
                $product_id = $this->ensureProduct($integration_data['id'], $sale_data);
            }

            // Criar ou atualizar venda
            $sale = new Sale($this->conn);
            $sale->integration_id = $integration_data['id'];
            $sale->product_id = $product_id;
            $sale->external_sale_id = $sale_data['transaction_id'];
            $sale->customer_name = $sale_data['customer_name'];
            $sale->customer_email = $sale_data['customer_email'];
            $sale->customer_document = $sale_data['customer_document'];
            $sale->amount = $sale_data['amount'];
            $sale->commission_amount = $sale_data['commission_amount'];
            $sale->currency = $sale_data['currency'];
            $sale->status = $sale_data['status'];
            $sale->payment_method = $sale_data['payment_type'];
            $sale->conversion_date = $sale_data['conversion_date'];
            $sale->approval_date = $sale_data['approval_date'];
            $sale->utm_source = $sale_data['utm_source'];
            $sale->utm_campaign = $sale_data['utm_campaign'];
            $sale->utm_medium = $sale_data['utm_medium'];
            $sale->utm_content = $sale_data['utm_content'];
            $sale->metadata_json = $sale_data['raw_data'];

            // Verificar se é criação ou atualização
            $existing_sale = $sale->findByExternalId($integration_data['id'], $sale_data['transaction_id']);
            $is_new = !$existing_sale;

            $success = $sale->createOrUpdate();

            if ($success) {
                // Atualizar último sync da integração
                $this->eduzzIntegration->updateLastSync($integration_data['id']);

                return [
                    'success' => true,
                    'created' => $is_new,
                    'updated' => !$is_new,
                    'sale_id' => $sale->id,
                    'event_type' => $payload['event']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Falha ao salvar venda no banco de dados'
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Garantir que o produto existe
     */
    private function ensureProduct($integration_id, $sale_data) {
        if (!isset($sale_data['product_id'])) {
            return null;
        }

        // Buscar produto existente
        $existing_product = $this->product->findByExternalId($integration_id, $sale_data['product_id']);

        if ($existing_product) {
            return $existing_product['id'];
        }

        // Criar produto básico a partir dos dados da venda
        $product = new Product($this->conn);
        $product->integration_id = $integration_id;
        $product->external_id = $sale_data['product_id'];
        $product->name = $sale_data['product_name'] ?? 'Produto #' . $sale_data['product_id'];
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

    /**
     * Testar integração com a Eduzz
     */
    public function testIntegration($integration_id) {
        try {
            $integration_data = $this->eduzzIntegration->findById($integration_id);
            if (!$integration_data) {
                return [
                    'success' => false,
                    'error' => 'Integração não encontrada'
                ];
            }

            // Testar acesso à API
            $test_result = $this->eduzzIntegration->testApiAccess($integration_data['api_key']);

            if ($test_result['success']) {
                return [
                    'success' => true,
                    'message' => 'Integração validada com sucesso',
                    'user_data' => $test_result['user_data']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $test_result['error'],
                    'http_code' => $test_result['http_code']
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
