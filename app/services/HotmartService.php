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
    
    public function __construct($client_id, $client_secret, $basic_token = null) {
        $this->api_key = $client_id;
        
        // Se api_secret na verdade contém o Basic token, ajustar
        if (strpos($client_secret, 'Basic ') === 0) {
            $this->basic_token = $client_secret;
            $this->api_secret = null; // Não temos o client_secret real
            error_log("HotmartService: Detectado Basic token em api_secret");
        } else {
            $this->api_secret = $client_secret;
            $this->basic_token = $basic_token;
        }
        
        error_log("HotmartService: Inicializado - Client ID: $client_id, " . 
                 "Basic Token: " . ($this->basic_token ? substr($this->basic_token, 0, 20) . "..." : "não fornecido") . ", " .
                 "Client Secret: " . ($this->api_secret ? "fornecido" : "não fornecido"));
    }

    // Autenticar na API da Hotmart
    private function authenticate() {
        if ($this->access_token) {
            return $this->access_token;
        }

        if (!$this->basic_token) {
            throw new Exception('Token Basic é obrigatório para autenticação OAuth2');
        }

        // Se temos client_secret, usar OAuth2 completo
        if ($this->api_secret) {
            // URL com parâmetros conforme documentação oficial
            $url = $this->base_url . '/security/oauth/token?' . http_build_query([
                'grant_type' => 'client_credentials',
                'client_id' => $this->api_key,
                'client_secret' => $this->api_secret
            ]);
            
            $response = $this->makeRequest('POST', $url, null, [
                'Content-Type: application/json',
                'Authorization: ' . $this->basic_token
            ]);

            if ($response && isset($response['access_token'])) {
                $this->access_token = $response['access_token'];
                error_log("HotmartService: Access token obtido com sucesso via OAuth2");
                return $this->access_token;
            }

            throw new Exception('Falha na autenticação OAuth2 com Hotmart: ' . (is_array($response) ? json_encode($response) : $response));
        } else {
            // Se só temos Basic token, não podemos gerar access_token
            // Vamos retornar null para que os métodos usem Basic diretamente
            error_log("HotmartService: Usando apenas Basic token (sem OAuth2)");
            return null;
        }
    }

    // Buscar produtos do afiliado
    public function getProducts($page = 1, $page_size = 20) {
        $url = $this->base_url . '/payments/api/v1/subscriptions?' . http_build_query([
            'page' => $page,
            'page_size' => $page_size,
            'status' => 'active'
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
        $url = $this->base_url . '/payments/api/v1/sales?' . http_build_query([
            'start_date' => $start_date, // YYYY-MM-DD
            'end_date' => $end_date,
            'page' => $page,
            'page_size' => $page_size,
            'status' => 'APPROVED'
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
        // Primeiro tentar obter access token
        try {
            $token = $this->authenticate();
            if ($token) {
                error_log("HotmartService: ✅ Autenticação OAuth2 bem-sucedida!");
                return true;
            }
        } catch (Exception $e) {
            error_log("HotmartService: Erro na autenticação OAuth2: " . $e->getMessage());
        }

        // Se OAuth falhou, tentar endpoints diretos com Basic token
        $test_urls = [
            // Endpoint de assinaturas (mais provável de funcionar)
            $this->base_url . '/payments/api/v1/subscriptions?page=1&page_size=1&status=active',
            // Endpoint de vendas 
            $this->base_url . '/payments/api/v1/sales?page=1&page_size=1&start_date=2024-01-01&end_date=2024-12-31&status=APPROVED',
            // Endpoint de sumário de vendas
            $this->base_url . '/payments/api/v1/sales/summary?start_date=2024-01-01&end_date=2024-12-31'
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
                
            case 'PURCHASE_CANCELED':
                return $this->mapSaleData($data, 'cancelled');
                
            case 'PURCHASE_EXPIRED':
                return $this->mapSaleData($data, 'expired');
                
            case 'PURCHASE_BILLET_PRINTED':
                return $this->mapSaleData($data, 'pending');
                
            case 'PURCHASE_PROTEST':
                return $this->mapSaleData($data, 'dispute');
                
            case 'PURCHASE_DELAYED':
                return $this->mapSaleData($data, 'delayed');
                
            case 'PURCHASE_OUT_OF_SHOPPING_CART':
                return $this->mapAbandonedCartData($data, 'abandoned');
                
            case 'SUBSCRIPTION_CANCELLATION':
                return $this->mapSubscriptionData($data, 'cancelled');
                
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

    // Mapear dados de assinatura para formato padrão
    private function mapSubscriptionData($data, $status) {
        return [
            'type' => 'subscription',
            'external_subscription_id' => $data['subscription']['id'] ?? null,
            'external_subscriber_code' => $data['subscriber']['code'] ?? null,
            'external_plan_id' => $data['subscription']['plan']['id'] ?? null,
            'subscriber_name' => $data['subscriber']['name'] ?? null,
            'subscriber_email' => $data['subscriber']['email'] ?? null,
            'subscriber_phone_ddd' => $data['subscriber']['phone']['dddPhone'] ?? null,
            'subscriber_phone_number' => $data['subscriber']['phone']['phone'] ?? null,
            'subscriber_cell_ddd' => $data['subscriber']['phone']['dddCell'] ?? null,
            'subscriber_cell_number' => $data['subscriber']['phone']['cell'] ?? null,
            'plan_name' => $data['subscription']['plan']['name'] ?? null,
            'status' => $status,
            'actual_recurrence_value' => $data['actual_recurrence_value'] ?? null,
            'currency' => 'BRL', // Hotmart sempre em BRL para assinaturas
            'cancellation_date' => $data['cancellation_date'] ? date('Y-m-d H:i:s', $data['cancellation_date'] / 1000) : null,
            'date_next_charge' => $data['date_next_charge'] ? date('Y-m-d H:i:s', $data['date_next_charge'] / 1000) : null,
            'external_product_id' => $data['product']['id'] ?? null,
            'product_name' => $data['product']['name'] ?? null,
            'metadata_json' => json_encode($data)
        ];
    }

    // Mapear dados de carrinho abandonado para formato padrão
    private function mapAbandonedCartData($data, $status) {
        // Carrinho abandonado não tem transação, então usar timestamp + product_id
        $external_sale_id = 'abandoned_' . time() . '_' . ($data['product']['id'] ?? 'unknown');
        
        return [
            'external_sale_id' => $external_sale_id,
            'customer_name' => $data['buyer']['name'] ?? null,
            'customer_email' => $data['buyer']['email'] ?? null,
            'customer_document' => null, // Carrinho abandonado não tem documento
            'amount' => 0, // Carrinho abandonado não tem valor final
            'commission_amount' => 0,
            'currency' => 'BRL',
            'status' => $status,
            'payment_method' => null, // Não chegou ao pagamento
            'conversion_date' => date('Y-m-d H:i:s'),
            'approval_date' => null,
            'refund_date' => null,
            'external_product_id' => $data['product']['id'] ?? null,
            'product_name' => $data['product']['name'] ?? null,
            'utm_source' => $data['affiliate'] ? 'hotmart_affiliate' : null,
            'utm_medium' => 'abandoned_cart',
            'utm_campaign' => $data['offer']['code'] ?? null,
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
        
        $url = $this->base_url . '/payments/api/v1/sales/' . $transaction_id;

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