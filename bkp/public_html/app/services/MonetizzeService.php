<?php
/**
 * Service Monetizze - Cliente para API da Monetizze
 */

class MonetizzeService {
    private $api_key;
    private $base_url = 'https://api.monetizze.com.br/v1';
    
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    // Buscar produtos do afiliado
    public function getProducts() {
        $url = $this->base_url . '/products?' . http_build_query([
            'key' => $this->api_key
        ]);

        $response = $this->makeRequest('GET', $url);
        
        return $response;
    }

    // Buscar vendas
    public function getSales($start_date = null, $end_date = null) {
        $params = ['key' => $this->api_key];
        
        if ($start_date) {
            $params['start_date'] = $start_date;
        }
        if ($end_date) {
            $params['end_date'] = $end_date;
        }

        $url = $this->base_url . '/sales?' . http_build_query($params);

        $response = $this->makeRequest('GET', $url);
        
        return $response;
    }

    // Buscar vendas por período
    public function getSalesByPeriod($days = 30) {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        return $this->getSales($start_date, $end_date);
    }

    // Validar credenciais
    public function validateCredentials() {
        try {
            $response = $this->getProducts();
            return isset($response['data']) || isset($response['products']);
        } catch (Exception $e) {
            return false;
        }
    }

    // Processar webhook da Monetizze
    public function processWebhook($payload) {
        // Monetizze envia dados diferentes dependendo do evento
        if (!isset($payload['evento']) && !isset($payload['status'])) {
            throw new Exception('Estrutura de webhook inválida');
        }

        $event_type = $payload['evento'] ?? $payload['status'];
        
        // Mapear evento para formato padrão
        switch ($event_type) {
            case 'venda_aprovada':
            case 'sale_approved':
            case 'approved':
                return $this->mapSaleData($payload, 'approved');
                
            case 'venda_cancelada':
            case 'sale_cancelled':
            case 'cancelled':
                return $this->mapSaleData($payload, 'cancelled');
                
            case 'venda_reembolsada':
            case 'sale_refunded':
            case 'refunded':
                return $this->mapSaleData($payload, 'refunded');
                
            case 'venda_contestada':
            case 'chargeback':
                return $this->mapSaleData($payload, 'chargeback');
                
            default:
                throw new Exception('Tipo de evento não suportado: ' . $event_type);
        }
    }

    // Mapear dados da venda para formato padrão
    private function mapSaleData($data, $status) {
        return [
            'external_sale_id' => $data['venda_id'] ?? $data['sale_id'] ?? $data['id'],
            'customer_name' => $data['cliente_nome'] ?? $data['customer_name'] ?? $data['nome'],
            'customer_email' => $data['cliente_email'] ?? $data['customer_email'] ?? $data['email'],
            'customer_document' => $data['cliente_documento'] ?? $data['customer_document'] ?? $data['documento'],
            'amount' => $data['valor'] ?? $data['amount'] ?? $data['price'] ?? 0,
            'commission_amount' => $data['comissao'] ?? $data['commission'] ?? 0,
            'currency' => 'BRL',
            'status' => $status,
            'payment_method' => $data['forma_pagamento'] ?? $data['payment_method'] ?? null,
            'conversion_date' => $data['data_venda'] ?? $data['sale_date'] ?? $data['created_at'],
            'approval_date' => $status === 'approved' ? ($data['data_aprovacao'] ?? $data['approved_at'] ?? null) : null,
            'refund_date' => $status === 'refunded' ? date('Y-m-d H:i:s') : null,
            'external_product_id' => $data['produto_id'] ?? $data['product_id'] ?? null,
            'product_name' => $data['produto_nome'] ?? $data['product_name'] ?? null,
            'utm_source' => $data['utm_source'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'utm_content' => $data['utm_content'] ?? null,
            'utm_term' => $data['utm_term'] ?? null,
            'metadata_json' => json_encode($data)
        ];
    }

    // Fazer requisição HTTP
    private function makeRequest($method, $url, $data = null, $headers = []) {
        $ch = curl_init();
        
        $default_headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: MercadoAfiliado/1.0'
        ];
        
        $headers = array_merge($default_headers, $headers);
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
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

    // Buscar comissões
    public function getCommissions($start_date = null, $end_date = null) {
        $params = ['key' => $this->api_key];
        
        if ($start_date) {
            $params['start_date'] = $start_date;
        }
        if ($end_date) {
            $params['end_date'] = $end_date;
        }

        $url = $this->base_url . '/commissions?' . http_build_query($params);

        $response = $this->makeRequest('GET', $url);
        
        return $response;
    }

    // Mapear produtos da Monetizze para formato padrão
    public function mapProductData($product_data) {
        return [
            'external_id' => $product_data['id'] ?? $product_data['produto_id'],
            'name' => $product_data['name'] ?? $product_data['nome'],
            'category' => $product_data['category'] ?? $product_data['categoria'] ?? null,
            'price' => $product_data['price'] ?? $product_data['preco'] ?? 0,
            'currency' => 'BRL',
            'commission_percentage' => $product_data['commission_percentage'] ?? $product_data['percentual_comissao'] ?? 0,
            'status' => 'active',
            'metadata_json' => json_encode($product_data)
        ];
    }

    // Buscar detalhes de uma venda específica
    public function getSaleDetails($sale_id) {
        $url = $this->base_url . '/sales/' . $sale_id . '?' . http_build_query([
            'key' => $this->api_key
        ]);

        $response = $this->makeRequest('GET', $url);
        
        return $response;
    }

    // Buscar estatísticas
    public function getStats($start_date = null, $end_date = null) {
        $params = ['key' => $this->api_key];
        
        if ($start_date) {
            $params['start_date'] = $start_date;
        }
        if ($end_date) {
            $params['end_date'] = $end_date;
        }

        $url = $this->base_url . '/stats?' . http_build_query($params);

        $response = $this->makeRequest('GET', $url);
        
        return $response;
    }
}