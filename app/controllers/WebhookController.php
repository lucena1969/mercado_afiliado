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
    private $rateLimiter;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->integration = new Integration($this->conn);
        $this->sale = new Sale($this->conn);
        $this->product = new Product($this->conn);
        $this->productSubscription = new ProductSubscription($this->conn);
        $this->syncLog = new SyncLog($this->conn);
        $this->rateLimiter = new RateLimiter($this->conn);
    }

    // Processar webhook por plataforma
    public function processWebhook($platform, $token) {
        $start_time = microtime(true);

        try {
            // === RATE LIMITING ===
            // 1. Verificar limite global (proteção do servidor)
            if (!$this->rateLimiter->checkGlobalLimit()) {
                http_response_code(429);
                header('Retry-After: 60');
                echo json_encode([
                    'error' => 'Taxa de requisições global excedida',
                    'retry_after' => 60
                ]);
                error_log("Rate limit global excedido");
                return;
            }

            // 2. Verificar limite por IP (proteção contra DDoS)
            $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            if (!$this->rateLimiter->checkIpLimit($client_ip)) {
                http_response_code(429);
                header('Retry-After: 1');
                echo json_encode([
                    'error' => 'Muitas requisições do mesmo IP',
                    'retry_after' => 1
                ]);
                error_log("Rate limit por IP excedido: {$client_ip}");
                return;
            }

            // Logs de entrada
            error_log("Webhook recebido: {$platform} - Token: {$token} - IP: {$client_ip}");

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

            // 3. Verificar limite por integração (100/minuto)
            if (!$this->rateLimiter->checkIntegrationLimit($integration_data['id'])) {
                $retry_after = $this->rateLimiter->getRetryAfter("integration_{$integration_data['id']}", 100, 60);
                http_response_code(429);
                header("Retry-After: {$retry_after}");
                echo json_encode([
                    'error' => 'Taxa de requisições da integração excedida',
                    'retry_after' => $retry_after
                ]);
                error_log("Rate limit por integração excedido: {$integration_data['id']}");
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
            $content_type = $_SERVER['CONTENT_TYPE'] ?? '';

            // Suportar JSON e x-www-form-urlencoded
            if (strpos($content_type, 'application/json') !== false) {
                $payload = json_decode($raw_payload, true);
            } else {
                // x-www-form-urlencoded
                parse_str($raw_payload, $payload);
                // Fallback para $_POST se parse_str falhar
                if (empty($payload) && !empty($_POST)) {
                    $payload = $_POST;
                }
            }

            if (empty($payload)) {
                error_log("Payload vazio - Content-Type: {$content_type}, Raw: " . substr($raw_payload, 0, 200));
                http_response_code(400);
                echo json_encode(['error' => 'Payload inválido ou vazio']);
                return;
            }

            error_log("Payload recebido - Campos: " . implode(', ', array_keys($payload)));

            // === VALIDAÇÃO DE DADOS ===
            // Validar e sanitizar dados específicos por plataforma
            try {
                switch ($platform) {
                    case 'monetizze':
                        MonetizzeValidator::validate($payload);
                        $payload = MonetizzeValidator::sanitize($payload);
                        break;

                    case 'hotmart':
                        HotmartValidator::validate($payload);
                        $payload = HotmartValidator::sanitize($payload);
                        break;

                    case 'eduzz':
                        EduzzValidator::validate($payload);
                        $payload = EduzzValidator::sanitize($payload);
                        break;

                    case 'braip':
                        BraipValidator::validate($payload);
                        $payload = BraipValidator::sanitize($payload);
                        break;
                }

                error_log("{$platform}: Payload validado e sanitizado com sucesso");
            } catch (Exception $e) {
                error_log("{$platform}: Erro na validação do payload - {$e->getMessage()}");
                http_response_code(422);
                echo json_encode([
                    'error' => 'Dados inválidos',
                    'message' => $e->getMessage(),
                    'platform' => $platform
                ]);
                return;
            }

            // Verificar idempotência para Monetizze (chave_unica)
            if ($platform === 'monetizze' && isset($payload['chave_unica'])) {
                $webhookEvent = new WebhookEvent($this->conn);
                $existing = $webhookEvent->findByUniqueKey('monetizze', $payload['chave_unica']);

                if ($existing) {
                    error_log("Postback duplicado detectado: {$payload['chave_unica']}");
                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Postback já processado anteriormente',
                        'duplicate' => true
                    ]);
                    return;
                }
            }

            // Processar evento
            $result = $this->processWebhookEvent($integration_data, $platform, $payload);

            $processing_time = round((microtime(true) - $start_time) * 1000);

            // Salvar evento no banco (com tempo de processamento)
            $this->saveWebhookEvent($integration_data['id'], $platform, $payload, $raw_payload, $processing_time);

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
    private function saveWebhookEvent($integration_id, $platform, $payload, $raw_payload = null, $processing_time_ms = null) {
        $webhookEvent = new WebhookEvent($this->conn);

        // Extrair tipo de evento
        $event_type = $this->extractEventType($platform, $payload);

        // Extrair chave única (se houver)
        $unique_key = null;
        if ($platform === 'monetizze' && isset($payload['chave_unica'])) {
            $unique_key = $payload['chave_unica'];
        }

        // === CAPTURAR METADADOS DE SEGURANÇA ===
        // IP do cliente (considerar proxies/load balancers)
        $client_ip = $this->getClientIp();

        // User-Agent
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        // Método HTTP
        $http_method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';

        // Headers da requisição (para auditoria)
        $request_headers = $this->getRequestHeaders();

        return $webhookEvent->create([
            'integration_id' => $integration_id,
            'platform' => $platform,
            'event_type' => $event_type,
            'unique_key' => $unique_key,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'raw_payload' => $raw_payload,
            'client_ip' => $client_ip,
            'user_agent' => $user_agent,
            'request_headers' => $request_headers,
            'http_method' => $http_method,
            'processing_time_ms' => $processing_time_ms,
            'processed' => 1
        ]);
    }

    /**
     * Obter IP real do cliente (considera proxies e load balancers)
     */
    private function getClientIp() {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Proxies padrão
            'HTTP_X_REAL_IP',            // Nginx
            'HTTP_CLIENT_IP',            // Proxies
            'REMOTE_ADDR'                // IP direto
        ];

        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                // Se tiver múltiplos IPs (X-Forwarded-For), pegar o primeiro
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                // Validar se é IP válido
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return 'unknown';
    }

    /**
     * Capturar headers HTTP importantes
     */
    private function getRequestHeaders() {
        $headers = [];

        // Headers importantes para segurança/auditoria
        $important_headers = [
            'User-Agent',
            'Accept',
            'Content-Type',
            'X-Forwarded-For',
            'X-Real-IP',
            'CF-Connecting-IP',
            'CF-Ray',
            'Origin',
            'Referer'
        ];

        if (function_exists('getallheaders')) {
            $all_headers = getallheaders();
            foreach ($important_headers as $header) {
                if (isset($all_headers[$header])) {
                    $headers[$header] = $all_headers[$header];
                }
            }
        } else {
            // Fallback para ambientes sem getallheaders()
            foreach ($important_headers as $header) {
                $server_key = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
                if (isset($_SERVER[$server_key])) {
                    $headers[$header] = $_SERVER[$server_key];
                }
            }
        }

        return json_encode($headers, JSON_UNESCAPED_UNICODE);
    }

    // Extrair tipo de evento do payload
    private function extractEventType($platform, $payload) {
        switch ($platform) {
            case 'hotmart':
                return $payload['event'] ?? 'unknown';
            case 'monetizze':
                // Monetizze usa postback_evento (códigos 1-6)
                $evento = $payload['postback_evento'] ?? null;
                if ($evento) {
                    $event_map = [
                        1 => 'venda_iniciada',
                        2 => 'venda_aprovada',
                        3 => 'venda_cancelada',
                        4 => 'venda_devolvida',
                        5 => 'venda_bloqueada',
                        6 => 'venda_completa'
                    ];
                    return $event_map[$evento] ?? 'unknown';
                }
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
            // === USAR HANDLERS ESPECIALIZADOS PARA EVENTOS ESPECÍFICOS ===
            if ($platform === 'monetizze') {
                require_once __DIR__ . '/../handlers/EventHandlerFactory.php';

                $event_code = (int) ($payload['postback_evento'] ?? 0);
                $handler = EventHandlerFactory::getHandler($this->conn, $integration_data, $payload);

                if ($handler) {
                    error_log("Usando handler especializado para evento {$event_code}");
                    return $handler->handle();
                }

                error_log("Usando processamento padrão para evento {$event_code}");
            }

            // === PROCESSAMENTO PADRÃO (VENDAS NORMAIS) ===
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
                // Estrutura oficial conforme documentação Monetizze
                'chave_unica' => md5(uniqid()),
                'url_recuperacao' => '',
                'postback_evento' => '6', // Completa
                'codigo_venda' => 'TEST_' . rand(10000000, 99999999),
                'codigo_produto' => '145388',
                'codigo_plano' => '140235',
                'codigo_status' => '6',
                'order_bump' => 0,
                'ecommerce' => false,
                'venda' => [
                    'codigo' => 'TEST_' . rand(10000000, 99999999),
                    'plano' => '140235',
                    'dataInicio' => date('Y-m-d H:i:s'),
                    'dataFinalizada' => date('Y-m-d H:i:s'),
                    'meioPagamento' => 'Monetizze',
                    'formaPagamento' => 'Cartão de crédito',
                    'garantiaRestante' => 0,
                    'status' => 'Completa',
                    'valor' => '157.00',
                    'quantidade' => '1',
                    'valorRecebido' => '65.33',
                    'onebuyclick' => '0',
                    'venda_upsell' => null,
                    'tipo_frete' => '0',
                    'descr_tipo_frete' => '0',
                    'frete' => '0.00',
                    'cupom' => null,
                    'src' => 'teste',
                    'utm_source' => 'teste',
                    'utm_medium' => 'webhook',
                    'utm_content' => '',
                    'utm_campaign' => 'teste_integracao',
                    'query_string' => '',
                    'linkBoleto' => '',
                    'linha_digitavel' => '',
                    'parcelas' => '1'
                ],
                'plano' => [
                    'codigo' => '140235',
                    'referencia' => 'FN140235',
                    'nome' => 'Plano de Teste',
                    'quantidade' => '1',
                    'sku' => null
                ],
                'produto' => [
                    'codigo' => '145388',
                    'chave' => '4a920341e79639658c2459847e146da9',
                    'nome' => 'Produto de Teste Monetizze',
                    'categoria' => 'Educacional, Cursos Técnicos e Profissionalizantes'
                ],
                'comprador' => [
 