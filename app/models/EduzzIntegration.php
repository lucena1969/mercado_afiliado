<?php
/**
 * Model EduzzIntegration - Gerenciamento específico da integração com Eduzz
 * Baseado na documentação oficial: https://developers.eduzz.com
 */

class EduzzIntegration extends Integration {

    // Configurações específicas da Eduzz
    private $eduzz_api_base = "https://api.eduzz.com";
    private $eduzz_accounts_api = "https://accounts-api.eduzz.com";

    public function __construct($db) {
        parent::__construct($db);
        $this->platform = 'eduzz';
    }

    /**
     * Validar credenciais da API Eduzz (Bearer Token OAuth2)
     */
    public function validateCredentials($api_key, $api_secret = null) {
        if (empty($api_key)) {
            return false;
        }

        // Testar conexão com endpoint de dados do usuário
        $ch = curl_init($this->eduzz_api_base . "/accounts/v1/me");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $http_code === 200;
    }

    /**
     * Validar webhook usando originSecret
     * O originSecret vem no campo data.producer.originSecret do payload
     */
    public function validateWebhookOriginSecret($payload_origin_secret, $stored_origin_secret) {
        if (empty($payload_origin_secret) || empty($stored_origin_secret)) {
            return false;
        }

        return hash_equals($stored_origin_secret, $payload_origin_secret);
    }

    /**
     * Processar dados do webhook da Eduzz
     * Formato novo: {id, event, data, sentDate}
     * Evento principal: myeduzz.invoice_paid
     */
    public function processWebhookData($webhook_payload) {
        // Validar estrutura básica
        if (!isset($webhook_payload['event']) || !isset($webhook_payload['data'])) {
            throw new Exception('Estrutura de webhook inválida');
        }

        $event = $webhook_payload['event'];
        $data = $webhook_payload['data'];

        // Verificar se é evento de venda paga
        if ($event !== 'myeduzz.invoice_paid') {
            throw new Exception('Evento não suportado: ' . $event);
        }

        // Extrair dados do comprador
        $buyer = $data['buyer'] ?? [];

        // Extrair dados do afiliado (se houver)
        $affiliate = $data['affiliate'] ?? null;

        // Extrair UTMs
        $utm = $data['utm'] ?? [];

        // Extrair primeiro item (produto principal)
        $items = $data['items'] ?? [];
        $first_item = !empty($items) ? $items[0] : [];

        // Calcular comissão do afiliado (se houver)
        $commission_amount = 0;
        if ($affiliate) {
            // A Eduzz não envia a comissão diretamente, precisaria calcular
            // Por enquanto, vamos deixar como 0 ou buscar via API depois
            $commission_amount = 0;
        }

        // Mapear para formato padrão
        $processed = [
            'transaction_id' => $data['id'] ?? null,
            'platform' => 'eduzz',
            'status' => $this->mapEduzzStatus($data['status'] ?? ''),
            'product_id' => $first_item['productId'] ?? null,
            'product_name' => $first_item['name'] ?? null,
            'customer_email' => $buyer['email'] ?? null,
            'customer_name' => $buyer['name'] ?? null,
            'customer_document' => $buyer['document'] ?? null,
            'customer_phone' => $buyer['cellphone'] ?? $buyer['phone'] ?? null,
            'amount' => isset($data['paid']['value']) ? (float)$data['paid']['value'] : 0,
            'commission_amount' => $commission_amount,
            'currency' => $data['paid']['currency'] ?? 'BRL',
            'conversion_date' => $data['createdAt'] ?? date('Y-m-d H:i:s'),
            'approval_date' => $data['paidAt'] ?? null,
            'payment_type' => $this->mapPaymentMethod($data['paymentMethod'] ?? ''),
            'installments' => $data['installments'] ?? 1,
            'affiliate_id' => $affiliate['id'] ?? null,
            'affiliate_name' => $affiliate['name'] ?? null,
            'affiliate_email' => $affiliate['email'] ?? null,
            'utm_source' => $utm['source'] ?? null,
            'utm_campaign' => $utm['campaign'] ?? null,
            'utm_medium' => $utm['medium'] ?? null,
            'utm_content' => $utm['content'] ?? null,
            'origin_secret' => $data['producer']['originSecret'] ?? null,
            'producer_id' => $data['producer']['id'] ?? null,
            'producer_name' => $data['producer']['name'] ?? null,
            'transaction_key' => $data['transaction']['key'] ?? null,
            'all_items' => $items,
            'raw_data' => json_encode($webhook_payload)
        ];

        return $processed;
    }

    /**
     * Mapear status da Eduzz para status interno
     */
    private function mapEduzzStatus($eduzz_status) {
        $status_map = [
            'paid' => 'approved',
            'refunded' => 'refunded',
            'cancelled' => 'cancelled',
            'waiting_payment' => 'pending',
            'chargeback' => 'chargeback',
            'expired' => 'cancelled'
        ];

        return $status_map[$eduzz_status] ?? 'pending';
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
     * Validar evento do webhook
     */
    public function isValidWebhookEvent($event_type) {
        $valid_events = [
            'myeduzz.invoice_paid',
            'myeduzz.invoice_refunded',
            'myeduzz.invoice_cancelled',
            'myeduzz.invoice_chargeback'
        ];

        return in_array($event_type, $valid_events);
    }

    /**
     * Buscar transações via API (se necessário no futuro)
     */
    public function fetchTransactions($access_token, $start_date = null, $end_date = null) {
        // Implementar quando houver endpoint específico de vendas na API Eduzz
        // Por enquanto, a Eduzz usa webhooks como fonte principal
        return [];
    }

    /**
     * Testar acesso à API
     */
    public function testApiAccess($access_token) {
        $ch = curl_init($this->eduzz_api_base . "/accounts/v1/me");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            return [
                'success' => true,
                'user_data' => json_decode($response, true)
            ];
        }

        return [
            'success' => false,
            'error' => 'Falha ao acessar API',
            'http_code' => $http_code
        ];
    }

    /**
     * Configurar webhook na Eduzz
     */
    public function setupWebhook($access_token, $webhook_url) {
        // A Eduzz permite configurar webhooks via console.eduzz.com
        return [
            'success' => true,
            'webhook_url' => $webhook_url,
            'instructions' => 'Configure este webhook no Console Eduzz (https://console.eduzz.com) > Seu Aplicativo > Webhooks > Adicionar evento: myeduzz.invoice_paid'
        ];
    }
}
