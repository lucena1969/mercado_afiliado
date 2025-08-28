<?php
/**
 * Service Braip - Cliente para API da Braip
 */

class BraipService {
    private $api_key;
    private $base_url = 'https://api.braip.com';
    
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    // Buscar produtos do afiliado
    public function getProducts() {
        $url = $this->base_url . '/products?' . http_build_query([
            'api_key' => $this->api_key
        ]);

        $response = $this->makeRequest('GET', $url);
        
        return $response;
    }

    // Buscar vendas
    public function getSales($start_date = null, $end_date = null, $page = 1) {
        $params = [
            'api_key' => $this->api_key,
            'page' => $page
        ];
        
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

    // Processar webhook da Braip
    public function processWebhook($payload) {
        if (!isset($payload['event']) && !isset($payload['status'])) {
            throw new Exception('Estrutura de webhook inválida');
        }

        $event_type = $payload['event'] ?? $payload['status'];
        
        // Mapear evento para formato padrão
        switch ($event_type) {
            case 'purchase_approved':
            case 'sale_approved':
            case 'approved':
                return $this->mapSaleData($payload, 'approved');
                
            case 'purchase_cancelled':
            case 'sale_cancelled':
            case 'cancelled':
                return $this->mapSaleData($payload, 'cancelled');
                
            case 'purchase_refunded':
            case 'sale_refunded':
            case 'refunded':
                return $this->mapSaleData($payload, 'refunded');
                
            case 'chargeback':
                return $this->mapSaleData($payload, 'chargeback');
                
            default:
                throw new Exception('Tipo de evento não suportado: ' . $event_type);
        }
    }

    // Mapear dados da venda para formato padrão
    private function mapSaleData($data, $status) {
        // Braip pode ter estruturas diferentes
        $sale_data = $data['purchase'] ?? $data['sale'] ?? $data;
        $customer_data = $sale_data['customer'] ?? $sale_data['buyer'] ?? $sale_data;
        $product_data = $sale_data['product'] ?? $sale_data;
        
        return [
            'external_sale_id' => $sale_data['id'] ?? $sale_data['transaction_id'] ?? $sale_data['purchase_id'],
            'customer_name' => $customer_data['name'] ?? $customer_data['buyer_name'],
            'customer_email' => $customer_data['email'] ?? $customer_data['buyer_email'],
            'customer_document' => $customer_data['document'] ?? $customer_data['buyer_document'],
            'amount' => $sale_data['amount'] ?? $sale_data['value'] ?? $sale_data['price'] ?? 0,
            'commission_amount' => $sale_data['commission_amount'] ?? $sale_data['commission'] ?? 0,
            'currency' => 'BRL',
            'status' => $status,
            'payment_method' => $sale_data['payment_method'] ?? $sale_data['payment_type'],
            'conversion_date' => $sale_data['created_at'] ?? $sale_data['purchase_date'],
            'approval_date' => $status === 'approved' ? ($sale_data['approved_at'] ?? null) : null,
            'refund_date' => $status === 'refunded' ? date('Y-m-d H:i:s') : null,
            'external_product_id' => $product_data['id'] ?? $product_data['product_id'],
            'product_name' => $product_data['name'] ?? $product_data['product_name'],
            'utm_source' => $sale_data['utm_source'] ?? null,
            'utm_medium' => $sale_data['utm_medium'] ?? null,
            'utm_campaign' => $sale_data['utm_campaign'] ?? null,
            'utm_content' => $sale_data['utm_content'] ?? null,
            'utm_term' => $sale_data['utm_term'] ?? null,
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
        $params = ['api_key' => $this->api_key];
        
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

    // Mapear produtos da Braip para formato padrão
    public function mapProductData($product_data) {
        return [
            'external_id' => $product_data['id'] ?? $product_data['product_id'],
            'name' => $product_data['name'] ?? $product_data['title'],
            'category' => $product_data['category'] ?? null,
            'price' => $product_data['price'] ?? $product_data['value'] ?? 0,
            'currency' => 'BRL',
            'commission_percentage' => $product_data['commission_percentage'] ?? 0,
            'status' => 'active',
            'metadata_json' => json_encode($product_data)
        ];
    }

    // Buscar detalhes de uma venda específica
    public function getSaleDetails($sale_id) {
        $url = $this->base_url . '/sales/' . $sale_id . '?' . http_build_query([
            'api_key' => $this->api_key
        ]);

        $response = $this->makeRequest('GET', $url);
        
        return $response;
    }

    // Buscar estatísticas
    public function getStats($start_date = null, $end_date = null) {
        $params = ['api_key' => $this->api_key];
        
        if ($start_date) {
            $params['start_date'] = $start_date;
        }
        if ($end_date) {
            $params['end_date'] = $end_date;
        }

        $url = $this->base_url . '/analytics?' . http_build_query($params);

        $response = $this->makeRequest('GET', $url);
        
        return $response;
    }

    // Buscar links de afiliado
    public function getAffiliateLinks($product_id = null) {
        $params = ['api_key' => $this->api_key];
        
        if ($product_id) {
            $params['product_id'] = $product_id;
        }

        $url = $this->base_url . '/affiliate-links?' . http_build_query($params);

        $response = $this->makeRequest('GET', $url);
        
        return $response;
    }

    // Gerar link de afiliado
    public function generateAffiliateLink($product_id, $utm_params = []) {
        $data = [
            'api_key' => $this->api_key,
            'product_id' => $product_id
        ];
        
        if (!empty($utm_params)) {
            $data = array_merge($data, $utm_params);
        }

        $url = $this->base_url . '/affiliate-links';

        $response = $this->makeRequest('POST', $url, $data);
        
        return $response;
    }
}