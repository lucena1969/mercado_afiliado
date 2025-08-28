<?php
/**
 * Service Hotmart - Cliente para API da Hotmart
 */

class HotmartService {
    private $api_key;
    private $api_secret;
    private $base_url = 'https://developers.hotmart.com';
    private $access_token;
    
    public function __construct($api_key, $api_secret) {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
    }

    // Autenticar na API da Hotmart
    private function authenticate() {
        if ($this->access_token) {
            return $this->access_token;
        }

        $url = $this->base_url . '/security/oauth/token';
        
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->api_key,
            'client_secret' => $this->api_secret
        ];

        $response = $this->makeRequest('POST', $url, $data, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->api_key . ':' . $this->api_secret)
        ]);

        if ($response && isset($response['access_token'])) {
            $this->access_token = $response['access_token'];
            return $this->access_token;
        }

        throw new Exception('Falha na autenticação com Hotmart: ' . json_encode($response));
    }

    // Buscar produtos do afiliado
    public function getProducts($page = 1, $page_size = 20) {
        $token = $this->authenticate();
        
        $url = $this->base_url . '/payments/api/v1/subscriptions?' . http_build_query([
            'page' => $page,
            'page_size' => $page_size
        ]);

        $response = $this->makeRequest('GET', $url, null, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);

        return $response;
    }

    // Buscar vendas (transações)
    public function getSales($start_date, $end_date, $page = 1, $page_size = 20) {
        $token = $this->authenticate();
        
        $url = $this->base_url . '/payments/api/v1/sales/history?' . http_build_query([
            'start_date' => $start_date, // YYYY-MM-DD
            'end_date' => $end_date,
            'page' => $page,
            'page_size' => $page_size,
            'transaction_status' => 'APPROVED'
        ]);

        $response = $this->makeRequest('GET', $url, null, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);

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
            $token = $this->authenticate();
            return !empty($token);
        } catch (Exception $e) {
            return false;
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
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
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
            throw new Exception('Erro HTTP ' . $http_code . ': ' . $response);
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