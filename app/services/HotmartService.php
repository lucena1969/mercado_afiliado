<?php
/**
 * Service Hotmart - Cliente para API da Hotmart
 */

class HotmartService {
    private $api_key;
    private $api_secret;
    private $basic_token;
    private $base_url = 'https://developers.hotmart.com';
    private $access_token;
    
    public function __construct($api_key, $api_secret) {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        
        // Se api_secret começa com "Basic ", é o token Basic da Hotmart
        if (strpos($api_secret, 'Basic ') === 0) {
            $this->basic_token = $api_secret;
            // Log para debug
            error_log("HotmartService: Basic token detectado: " . substr($api_secret, 0, 20) . "...");
        } else {
            error_log("HotmartService: Usando OAuth - API Key: $api_key, API Secret: " . substr($api_secret, 0, 10) . "...");
        }
    }

    // Autenticar na API da Hotmart
    private function authenticate() {
        if ($this->access_token) {
            return $this->access_token;
        }

        $url = $this->base_url . '/security/oauth/token';
        
        // Hotmart usa Basic Auth no header, não body JSON
        $response = $this->makeRequest('POST', $url, 'grant_type=client_credentials', [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode($this->api_key . ':' . $this->api_secret)
        ]);

        if ($response && isset($response['access_token'])) {
            $this->access_token = $response['access_token'];
            return $this->access_token;
        }

        throw new Exception('Falha na autenticação com Hotmart: ' . (is_array($response) ? json_encode($response) : $response));
    }

    // Buscar produtos do afiliado
    public function getProducts($page = 1, $page_size = 20) {
        $url = $this->base_url . '/payments/api/v1/subscriptions?' . http_build_query([
            'page' => $page,
            'page_size' => $page_size
        ]);

        $headers = ['Content-Type: application/json'];
        
        if ($this->basic_token) {
            $headers[] = 'Authorization: ' . $this->basic_token;
        } else {
            $token = $this->authenticate();
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $response = $this->makeRequest('GET', $url, null, $headers);
        return $response;
    }

    // Buscar vendas (transações)
    public function getSales($start_date, $end_date, $page = 1, $page_size = 20) {
        $url = $this->base_url . '/payments/api/v1/sales/history?' . http_build_query([
            'start_date' => $start_date, // YYYY-MM-DD
            'end_date' => $end_date,
            'page' => $page,
            'page_size' => $page_size,
            'transaction_status' => 'APPROVED'
        ]);

        $headers = ['Content-Type: application/json'];
        
        if ($this->basic_token) {
            $headers[] = 'Authorization: ' . $this->basic_token;
        } else {
            $token = $this->authenticate();
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $response = $this->makeRequest('GET', $url, null, $headers);
        return $response;
    }

    // Buscar vendas por período específico
    public function getSalesByPeriod($days = 30) {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        return $this->getSales($start_date, $end_date);
    }

    // Validar credenciais
    public function validateCredentials() {
        try {
            // Se temos Basic token, testar direto com uma requisição simples
            if ($this->basic_token) {
                error_log("HotmartService: Validando com Basic token...");
                return $this->testBasicAuth();
            }
            
            // Senão, usar OAuth
            error_log("HotmartService: Validando com OAuth...");
            $token = $this->authenticate();
            $is_valid = !empty($token);
            error_log("HotmartService: OAuth " . ($is_valid ? 'válido' : 'inválido'));
            return $is_valid;
        } catch (Exception $e) {
            // Log do erro para debug
            error_log("Hotmart validateCredentials error: " . $e->getMessage());
            // Re-throw a exceção para que o SyncService possa capturar a mensagem específica
            throw $e;
        }
    }
    
    // Testar autenticação Basic
    private function testBasicAuth() {
        // Testar diferentes endpoints para encontrar um que funcione
        $test_urls = [
            // Endpoint de vendas (mais provável de funcionar)
            $this->base_url . '/payments/api/v1/sales/history?page=1&page_size=1&start_date=2024-01-01&end_date=2024-01-02',
            // Endpoint de assinaturas 
            $this->base_url . '/payments/api/v1/subscriptions?page=1&page_size=1',
            // Endpoint mais simples de informações da conta
            $this->base_url . '/payments/api/v1/sales/summary?start_date=2024-01-01&end_date=2024-01-02'
        ];
        
        error_log("HotmartService: Iniciando teste Basic auth com " . count($test_urls) . " endpoints");
        error_log("HotmartService: Basic token: " . substr($this->basic_token, 0, 30) . "...");
        
        $last_error = null;
        
        foreach ($test_urls as $index => $url) {
            try {
                error_log("HotmartService: Tentativa " . ($index + 1) . "/" . count($test_urls) . " - URL: $url");
                
                // Fazer request
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: ' . $this->basic_token,
                        'Content-Type: application/json'
                    ],
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_USERAGENT => 'MercadoAfiliado/1.0'
                ]);

                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                $curl_info = curl_getinfo($ch);
                curl_close($ch);

                error_log("HotmartService: Tentativa " . ($index + 1) . " - HTTP Code: $http_code");
                error_log("HotmartService: Response: " . substr($response, 0, 300));
                
                if ($error) {
                    error_log("HotmartService: cURL Error: $error");
                    $last_error = new Exception("Erro de conexão com Hotmart: $error");
                    continue; // Tenta próximo endpoint
                }

                // 200-299 são códigos de sucesso
                if ($http_code >= 200 && $http_code < 300) {
                    error_log("HotmartService: ✅ Credenciais Basic válidas! (Endpoint " . ($index + 1) . ")");
                    return true;
                }
                
                // Para outros códigos, continua testando outros endpoints
                error_log("HotmartService: Tentativa " . ($index + 1) . " falhou com HTTP $http_code");
                
                // Tentar decodificar resposta JSON
                $json_response = json_decode($response, true);
                if ($json_response !== null && (isset($json_response['error']) || isset($json_response['message']))) {
                    $error_detail = $json_response['error'] ?? $json_response['message'] ?? 'Erro desconhecido';
                    error_log("HotmartService: Erro específico da API: $error_detail");
                }
                
                // Salvar erro específico baseado no código HTTP
                if ($http_code == 401) {
                    $last_error = new Exception("Token Basic inválido ou expirado (HTTP 401)");
                } else if ($http_code == 403) {
                    $last_error = new Exception("Token Basic sem permissão para acessar dados (HTTP 403)");
                } else {
                    $last_error = new Exception("Erro na API Hotmart (HTTP $http_code): " . substr($response, 0, 100));
                }
                
            } catch (Exception $e) {
                error_log("HotmartService: Exceção na tentativa " . ($index + 1) . ": " . $e->getMessage());
                $last_error = $e;
                continue; // Tenta próximo endpoint
            }
        }
        
        // Se chegou aqui, todos os endpoints falharam
        error_log("HotmartService: ❌ Todos os endpoints falharam");
        if ($last_error) {
            throw $last_error;
        } else {
            throw new Exception("Falha na validação do token Basic em todos os endpoints testados");
        }
    }

    // Processar webhook da Hotmart
    public function processWebhook($payload) {
        // Validar estrutura do webhook
        if (!isset($payload['event']) || !isset($payload['data'])) {
            throw new Exception('Estrutura de webhook inválida');
        }

        $event_type = $payload['event'];
        $data = $payload['data'];

        // Mapear evento para formato padrão
        switch ($event_type) {
            case 'PURCHASE_COMPLETE':
            case 'PURCHASE_APPROVED':
                return $this->mapSaleData($data, 'approved');
                
            case 'PURCHASE_REFUNDED':
                return $this->mapSaleData($data, 'refunded');
                
            case 'PURCHASE_CHARGEBACK':
                return $this->mapSaleData($data, 'chargeback');
                
            default:
                throw new Exception('Tipo de evento não suportado: ' . $event_type);
        }
    }

    // Mapear dados da venda para formato padrão
    private function mapSaleData($data, $status) {
        return [
            'external_sale_id' => $data['transaction'] ?? $data['purchase']['transaction'] ?? null,
            'customer_name' => $data['buyer']['name'] ?? null,
            'customer_email' => $data['buyer']['email'] ?? null,
            'customer_document' => $data['buyer']['document'] ?? null,
            'amount' => $data['purchase']['price']['value'] ?? 0,
            'commission_amount' => $data['commissions'][0]['value'] ?? 0,
            'currency' => $data['purchase']['price']['currency_code'] ?? 'BRL',
            'status' => $status,
            'payment_method' => $data['purchase']['payment']['type'] ?? null,
            'conversion_date' => $data['purchase']['order_date'] ?? $data['purchase']['approved_date'] ?? null,
            'approval_date' => $status === 'approved' ? ($data['purchase']['approved_date'] ?? null) : null,
            'refund_date' => $status === 'refunded' ? date('Y-m-d H:i:s') : null,
            'external_product_id' => $data['product']['id'] ?? null,
            'product_name' => $data['product']['name'] ?? null,
            'utm_source' => $data['affiliations'][0]['source'] ?? null,
            'metadata_json' => json_encode($data)
        ];
    }

    // Fazer requisição HTTP
    private function makeRequest($method, $url, $data = null, $headers = []) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false, // Para desenvolvimento
            CURLOPT_USERAGENT => 'MercadoAfiliado/1.0'
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                // Se data é string, usar como está (form-urlencoded)
                // Se data é array, converter para JSON
                if (is_string($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
            }
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('Erro cURL: ' . $error);
        }

        if ($http_code >= 400) {
            $error_msg = 'Erro HTTP ' . $http_code . ': ' . $response;
            error_log("HotmartService makeRequest error: " . $error_msg);
            throw new Exception($error_msg);
        }

        return json_decode($response, true);
    }

    // Buscar detalhes de uma venda específica
    public function getSaleDetails($transaction_id) {
        $token = $this->authenticate();
        
        $url = $this->base_url . '/payments/api/v1/sales/history/' . $transaction_id;

        $response = $this->makeRequest('GET', $url, null, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);

        return $response;
    }

    // Buscar comissões
    public function getCommissions($start_date, $end_date) {
        $token = $this->authenticate();
        
        $url = $this->base_url . '/payments/api/v1/sales/commissions?' . http_build_query([
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);

        $response = $this->makeRequest('GET', $url, null, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);

        return $response;
    }

    // Mapear produtos da Hotmart para formato padrão
    public function mapProductData($product_data) {
        return [
            'external_id' => $product_data['id'],
            'name' => $product_data['name'],
            'category' => $product_data['category'] ?? null,
            'price' => $product_data['price']['value'] ?? 0,
            'currency' => $product_data['price']['currency_code'] ?? 'BRL',
            'commission_percentage' => $product_data['commission_percentage'] ?? 0,
            'status' => 'active',
            'metadata_json' => json_encode($product_data)
        ];
    }
}