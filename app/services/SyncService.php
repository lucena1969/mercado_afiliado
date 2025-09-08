<?php
/**
 * Service Sync - Sincronização manual de dados das redes
 */

class SyncService {
    private $conn;
    private $integration;
    private $product;
    private $sale;
    private $syncLog;

    public function __construct($db) {
        $this->conn = $db;
        $this->integration = new Integration($db);
        $this->product = new Product($db);
        $this->sale = new Sale($db);
        $this->syncLog = new SyncLog($db);
    }

    // Sincronizar produtos de uma integração
    public function syncProducts($integration_id) {
        $start_time = microtime(true);
        $stats = ['processed' => 0, 'created' => 0, 'updated' => 0, 'errors' => 0];
        
        try {
            // Buscar integração
            $integration_data = $this->integration->findById($integration_id);
            if (!$integration_data) {
                throw new Exception('Integração não encontrada');
            }

            // Criar service da plataforma
            $service = $this->createPlatformService($integration_data);
            
            // Buscar produtos da API
            $response = $service->getProducts();
            $products = $this->extractProductsFromResponse($response, $integration_data['platform']);

            foreach ($products as $product_data) {
                $stats['processed']++;
                
                try {
                    // Mapear dados do produto
                    $mapped_data = $service->mapProductData($product_data);
                    
                    // Buscar produto existente
                    $existing_product = $this->product->findByExternalId($integration_id, $mapped_data['external_id']);
                    $is_new = !$existing_product;
                    
                    // Criar ou atualizar produto
                    $product = new Product($this->conn);
                    $product->integration_id = $integration_id;
                    $product->external_id = $mapped_data['external_id'];
                    $product->name = $mapped_data['name'];
                    $product->category = $mapped_data['category'];
                    $product->price = $mapped_data['price'];
                    $product->currency = $mapped_data['currency'];
                    $product->commission_percentage = $mapped_data['commission_percentage'];
                    $product->status = $mapped_data['status'];
                    $product->metadata_json = $mapped_data['metadata_json'];

                    if ($product->createOrUpdate()) {
                        if ($is_new) {
                            $stats['created']++;
                        } else {
                            $stats['updated']++;
                        }
                    } else {
                        $stats['errors']++;
                    }

                } catch (Exception $e) {
                    $stats['errors']++;
                    error_log("Erro ao processar produto {$product_data['id']}: " . $e->getMessage());
                }
            }

            $processing_time = round((microtime(true) - $start_time) * 1000);
            
            // Log de sucesso
            SyncLog::logSuccess(
                $this->conn,
                $integration_id,
                'manual',
                'fetch_products',
                array_merge($stats, ['processing_time_ms' => $processing_time])
            );

            // Atualizar último sync
            $this->integration->updateLastSync($integration_id);

            return [
                'success' => true,
                'stats' => $stats,
                'processing_time_ms' => $processing_time
            ];

        } catch (Exception $e) {
            $processing_time = round((microtime(true) - $start_time) * 1000);
            
            // Log de erro
            SyncLog::logError(
                $this->conn,
                $integration_id,
                'manual',
                'fetch_products',
                $e->getMessage(),
                array_merge($stats, ['processing_time_ms' => $processing_time])
            );

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => $stats
            ];
        }
    }

    // Sincronizar vendas de uma integração
    public function syncSales($integration_id, $days = 30) {
        $start_time = microtime(true);
        $stats = ['processed' => 0, 'created' => 0, 'updated' => 0, 'errors' => 0];
        
        try {
            // Buscar integração
            $integration_data = $this->integration->findById($integration_id);
            if (!$integration_data) {
                throw new Exception('Integração não encontrada');
            }

            // Criar service da plataforma
            $service = $this->createPlatformService($integration_data);
            
            // Buscar vendas da API
            $response = $service->getSalesByPeriod($days);
            $sales = $this->extractSalesFromResponse($response, $integration_data['platform']);

            foreach ($sales as $sale_data) {
                $stats['processed']++;
                
                try {
                    // Mapear dados da venda (usando o método de webhook que já mapeia)
                    $mapped_data = $service->processWebhook(['data' => $sale_data, 'event' => 'MANUAL_SYNC']);
                    
                    // Buscar produto se necessário
                    $product_id = null;
                    if (isset($mapped_data['external_product_id'])) {
                        $product = $this->product->findByExternalId($integration_id, $mapped_data['external_product_id']);
                        $product_id = $product ? $product['id'] : null;
                    }
                    
                    // Buscar venda existente
                    $existing_sale = $this->sale->findByExternalId($integration_id, $mapped_data['external_sale_id']);
                    $is_new = !$existing_sale;
                    
                    // Criar ou atualizar venda
                    $sale = new Sale($this->conn);
                    $sale->integration_id = $integration_id;
                    $sale->product_id = $product_id;
                    $sale->external_sale_id = $mapped_data['external_sale_id'];
                    $sale->customer_name = $mapped_data['customer_name'];
                    $sale->customer_email = $mapped_data['customer_email'];
                    $sale->customer_document = $mapped_data['customer_document'];
                    $sale->amount = $mapped_data['amount'];
                    $sale->commission_amount = $mapped_data['commission_amount'];
                    $sale->currency = $mapped_data['currency'];
                    $sale->status = $mapped_data['status'];
                    $sale->payment_method = $mapped_data['payment_method'];
                    $sale->utm_source = $mapped_data['utm_source'];
                    $sale->utm_medium = $mapped_data['utm_medium'];
                    $sale->utm_campaign = $mapped_data['utm_campaign'];
                    $sale->utm_content = $mapped_data['utm_content'];
                    $sale->utm_term = $mapped_data['utm_term'];
                    $sale->conversion_date = $mapped_data['conversion_date'];
                    $sale->approval_date = $mapped_data['approval_date'];
                    $sale->refund_date = $mapped_data['refund_date'];
                    $sale->metadata_json = $mapped_data['metadata_json'];

                    if ($sale->createOrUpdate()) {
                        if ($is_new) {
                            $stats['created']++;
                        } else {
                            $stats['updated']++;
                        }
                    } else {
                        $stats['errors']++;
                    }

                } catch (Exception $e) {
                    $stats['errors']++;
                    error_log("Erro ao processar venda: " . $e->getMessage());
                }
            }

            $processing_time = round((microtime(true) - $start_time) * 1000);
            
            // Log de sucesso
            SyncLog::logSuccess(
                $this->conn,
                $integration_id,
                'manual',
                'fetch_sales',
                array_merge($stats, ['processing_time_ms' => $processing_time])
            );

            // Atualizar último sync
            $this->integration->updateLastSync($integration_id);

            return [
                'success' => true,
                'stats' => $stats,
                'processing_time_ms' => $processing_time
            ];

        } catch (Exception $e) {
            $processing_time = round((microtime(true) - $start_time) * 1000);
            
            // Log de erro
            SyncLog::logError(
                $this->conn,
                $integration_id,
                'manual',
                'fetch_sales',
                $e->getMessage(),
                array_merge($stats, ['processing_time_ms' => $processing_time])
            );

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => $stats
            ];
        }
    }

    // Sincronização completa (produtos + vendas)
    public function fullSync($integration_id, $days = 30) {
        $results = [];
        
        // Sincronizar produtos primeiro
        $results['products'] = $this->syncProducts($integration_id);
        
        // Sincronizar vendas
        $results['sales'] = $this->syncSales($integration_id, $days);
        
        return $results;
    }

    // Extrair produtos da resposta da API
    private function extractProductsFromResponse($response, $platform) {
        switch ($platform) {
            case 'hotmart':
                return $response['items'] ?? $response['data'] ?? [];
            case 'monetizze':
                return $response['data'] ?? $response['products'] ?? [];
            case 'eduzz':
                return $response['data'] ?? $response['contents'] ?? [];
            case 'braip':
                return $response['data'] ?? $response['products'] ?? [];
            default:
                return [];
        }
    }

    // Extrair vendas da resposta da API
    private function extractSalesFromResponse($response, $platform) {
        switch ($platform) {
            case 'hotmart':
                return $response['items'] ?? $response['data'] ?? [];
            case 'monetizze':
                return $response['data'] ?? $response['sales'] ?? [];
            case 'eduzz':
                return $response['data'] ?? $response['sales'] ?? [];
            case 'braip':
                return $response['data'] ?? $response['sales'] ?? [];
            default:
                return [];
        }
    }

    // Criar service da plataforma
    private function createPlatformService($integration_data) {
        switch ($integration_data['platform']) {
            case 'hotmart':
                // Para Hotmart, precisamos dos 3 parâmetros: client_id, client_secret e basic_token
                // Se api_secret contém "Basic ", é o basic_token; senão usar webhook_token
                $basic_token = null;
                if (strpos($integration_data['api_secret'], 'Basic ') === 0) {
                    $basic_token = $integration_data['api_secret'];
                } elseif (!empty($integration_data['webhook_token'])) {
                    $basic_token = $integration_data['webhook_token'];
                }
                
                return new HotmartService(
                    $integration_data['api_key'],      // client_id
                    $integration_data['api_secret'],   // client_secret
                    $basic_token                       // basic_token
                );
            case 'monetizze':
                return new MonetizzeService($integration_data['api_key']);
            case 'eduzz':
                return new EduzzService($integration_data['api_key']);
            case 'braip':
                return new BraipService($integration_data['api_key']);
            default:
                throw new Exception('Plataforma não suportada');
        }
    }

    // Testar conexão com a integração
    public function testConnection($integration_id) {
        try {
            $integration_data = $this->integration->findById($integration_id);
            if (!$integration_data) {
                throw new Exception('Integração não encontrada');
            }

            // Log detalhado para debug
            error_log("SyncService: Testando conexão para integração ID: {$integration_id}");
            error_log("SyncService: Plataforma: {$integration_data['platform']}");
            error_log("SyncService: API Key: " . (empty($integration_data['api_key']) ? '(vazio)' : substr($integration_data['api_key'], 0, 10) . '...'));
            error_log("SyncService: API Secret: " . (empty($integration_data['api_secret']) ? '(vazio)' : substr($integration_data['api_secret'], 0, 20) . '...'));

            $service = $this->createPlatformService($integration_data);
            
            // Capturar qualquer erro específico durante validação
            $validation_start = microtime(true);
            $is_valid = $service->validateCredentials();
            $validation_time = round((microtime(true) - $validation_start) * 1000, 2);
            
            error_log("SyncService: Validação levou {$validation_time}ms");
            error_log("SyncService: Resultado da validação: " . ($is_valid ? 'VÁLIDA' : 'INVÁLIDA'));
            
            if ($is_valid) {
                $this->integration->updateStatus($integration_id, 'active');
                return ['success' => true, 'message' => 'Conexão válida'];
            } else {
                $error_msg = 'Credenciais inválidas';
                
                // Para Hotmart, adicionar informação específica
                if ($integration_data['platform'] === 'hotmart') {
                    if (empty($integration_data['api_key']) && !empty($integration_data['api_secret'])) {
                        $error_msg .= ' (usando Basic token)';
                    } else if (!empty($integration_data['api_key']) && !empty($integration_data['api_secret'])) {
                        $error_msg .= ' (usando OAuth)';
                    } else {
                        $error_msg .= ' (credenciais incompletas)';
                    }
                }
                
                $this->integration->updateStatus($integration_id, 'error', $error_msg);
                return ['success' => false, 'error' => $error_msg];
            }

        } catch (Exception $e) {
            $error_msg = $e->getMessage();
            error_log("SyncService: Exceção durante teste de conexão: " . $error_msg);
            error_log("SyncService: Stack trace: " . $e->getTraceAsString());
            
            $this->integration->updateStatus($integration_id, 'error', $error_msg);
            return ['success' => false, 'error' => $error_msg];
        }
    }
}