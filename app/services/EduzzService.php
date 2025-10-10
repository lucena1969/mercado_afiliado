<?php
/**
 * Service Eduzz - Cliente para API da Eduzz
 * Baseado na documentação oficial: https://developers.eduzz.com
 * API Base: https://api.eduzz.com
 * Autenticação: Bearer Token (OAuth2)
 */

class EduzzService {
    private $access_token;
    private $api_base = 'https://api.eduzz.com';
    private $accounts_api_base = 'https://accounts-api.eduzz.com';

    public function __construct($access_token) {
        $this->access_token = $access_token;
    }

    /**
     * Validar credenciais (Bearer Token)
     */
    public function validateCredentials() {
        try {
            $response = $this->getUserData();
            return isset($response['id']) && isset($response['email']);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obter dados do usuário autenticado
     * GET /accounts/v1/me
     */
    public function getUserData() {
        $url = $this->api_base . '/accounts/v1/me';
        return $this->makeRequest('GET', $url);
    }

    /**
     * Processar webhook da Eduzz
     * Formato: {id, event, data, sentDate}
     * Evento principal: myeduzz.invoice_paid
     */
    public function processWebhook($payload) {
        if (!isset($payload['event']) || !isset($payload['data'])) {
            throw new Exception('Estrutura de webhook inválida');
        }

        $event = $payload['event'];
        $data = $payload['data'];

        // Processar apenas evento de venda paga
        if ($event === 'myeduzz.invoice_paid') {
            return $this->mapInvoicePaidData($data);
        }

        throw new Exception('Tipo de evento não suportado: ' . $event);
    }

    /**
     * Mapear dados de invoice_paid para formato padrão
     */
    private function mapInvoicePaidData($data) {
        $buyer = $data['buyer'] ?? [];
        $affiliate = $data['affiliate'] ?? null;
        $utm = $data['utm'] ?? [];
        $items = $data['items'] ?? [];
        $first_item = !empty($items) ? $items[0] : [];

        return [
            'external_sale_id' => $data['id'] ?? null,
            'customer_name' => $buyer['name'] ?? null,
            'customer_email' => $buyer['email'] ?? null,
            'customer_document' => $buyer['document'] ?? null,
            'amount' => isset($data['paid']['value']) ? (float)$data['paid']['value'] : 0,
            'commission_amount' => 0, // A Eduzz não envia comissão diretamente
            'currency' => $data['paid']['currency'] ?? 'BRL',
            'status' => $this->mapStatus($data['status'] ?? ''),
            'payment_method' => $this->mapPaymentMethod($data['paymentMethod'] ?? ''),
            'conversion_date' => $data['createdAt'] ?? date('Y-m-d H:i:s'),
            'approval_date' => $data['paidAt'] ?? null,
            'external_product_id' => $first_item['productId'] ?? null,
            'product_name' => $first_item['name'] ?? null,
            'utm_source' => $utm['source'] ?? null,
            'utm_medium' => $utm['medium'] ?? null,
            'utm_campaign' => $utm['campaign'] ?? null,
            'utm_content' => $utm['content'] ?? null,
            'utm_term' => null,
            'metadata_json' => json_encode($data)
        ];
    }

    /**
     * Mapear status
     */
    private function mapStatus($status) {
        $status_map = [
            'paid' => 'approved',
            'refunded' => 'refunded',
            'cancelled' => 'cancelled',
            'waiting_payment' => 'pending',
            'chargeback' => 'chargeback'
        ];

        return $status_map[$status] ?? 'pending';
    }

    /**
     * Mapear método de pagamento
     */
    private function mapPaymentMethod($payment_method) {
        $method_map = [
            'creditCard' => 'credit_card',
            'pix' => 'pix',
            'bankslip' => 'boleto',
            'combinedPayment' => 'combined',
            'installmentBankslip' => 'boleto_parcelado'
        ];

        return $method_map[$payment_method] ?? $payment_method;
    }

    /**
     * Fazer requisição HTTP
     */
    private function makeRequest($method, $url, $data = null, $headers = []) {
        $ch = curl_init();

        $default_headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->access_token,
            'User-Agent: MercadoAfiliado/1.0'
        ];

        $headers = array_merge($default_headers, $headers);

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
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

    /**
     * Obter token OAuth2 a partir de código de autorização
     */
    public static function getAccessToken($client_id, $client_secret, $code, $redirect_uri) {
        $ch = curl_init();

        $data = [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'code' => $code,
            'redirect_uri' => $redirect_uri,
            'grant_type' => 'authorization_code'
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://accounts-api.eduzz.com/oauth/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('Erro cURL: ' . $error);
        }

        if ($http_code !== 200) {
            throw new Exception('Erro ao obter token: ' . $response);
        }

        return json_decode($response, true);
    }

    /**
     * Gerar URL de autorização OAuth2
     */
    public static function getAuthorizationUrl($client_id, $redirect_uri, $scopes = ['webhook_read', 'webhook_write']) {
        $params = [
            'client_id' => $client_id,
            'responseType' => 'code',
            'redirectTo' => $redirect_uri
        ];

        if (!empty($scopes)) {
            $params['scope'] = implode(' ', $scopes);
        }

        return 'https://accounts.eduzz.com/oauth/authorize?' . http_build_query($params);
    }
}
