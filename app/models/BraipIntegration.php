<?php
/**
 * Model BraipIntegration - Gerenciamento específico da integração com Braip
 * Baseado na documentação e padrões da plataforma Braip
 */

class BraipIntegration extends Integration {

    // Configurações específicas da Braip
    private $braip_base_url = "https://ev.braip.com";

    public function __construct($db) {
        parent::__construct($db);
        $this->platform = 'braip';
    }

    /**
     * Validar credenciais da API Braip (Token API)
     */
    public function validateCredentials($api_key, $api_secret = null) {
        if (empty($api_key)) {
            return false;
        }

        // A Braip usa token API simples
        // Validação real seria testando acesso à API, mas por enquanto
        // apenas verificamos se o token existe
        return strlen($api_key) > 0;
    }

    /**
     * Validar webhook usando chave de autenticação
     * A Braip envia basic_authentication no payload
     */
    public function validateWebhookAuth($payload_auth, $stored_auth) {
        if (empty($payload_auth) || empty($stored_auth)) {
            return false;
        }

        return hash_equals($stored_auth, $payload_auth);
    }

    /**
     * Processar dados do webhook da Braip
     * A Braip pode enviar dados via POST ou GET
     */
    public function processWebhookData($webhook_payload) {
        // Validar estrutura básica
        if (empty($webhook_payload)) {
            throw new Exception('Payload de webhook vazio');
        }

        // A Braip envia via POST/GET, pode ser array ou JSON
        if (is_string($webhook_payload)) {
            $webhook_payload = json_decode($webhook_payload, true);
        }

        // Extrair status da transação
        $status = $webhook_payload['trans_status'] ?? $webhook_payload['status'] ?? 'unknown';

        // Calcular comissão do afiliado (se houver)
        $commission_amount = 0;
        if (isset($webhook_payload['commission_value'])) {
            $commission_amount = (float)$webhook_payload['commission_value'];
        } elseif (isset($webhook_payload['aff_id']) && isset($webhook_payload['trans_value']) && isset($webhook_payload['commission_percentage'])) {
            $trans_value = (float)str_replace(',', '.', $webhook_payload['trans_value']);
            $commission_percentage = (float)str_replace(',', '.', $webhook_payload['commission_percentage']);
            $commission_amount = ($trans_value * $commission_percentage) / 100;
        }

        // Normalizar valor da transação
        $amount = 0;
        if (isset($webhook_payload['trans_value'])) {
            $amount = (float)str_replace(',', '.', $webhook_payload['trans_value']);
        } elseif (isset($webhook_payload['prod_value'])) {
            $amount = (float)str_replace(',', '.', $webhook_payload['prod_value']);
        }

        // Mapear para formato padrão
        $processed = [
            'transaction_id' => $webhook_payload['trans_id'] ?? $webhook_payload['transaction_id'] ?? null,
            'platform' => 'braip',
            'status' => $this->mapBraipStatus($status),
            'product_id' => $webhook_payload['prod_id'] ?? $webhook_payload['product_id'] ?? null,
            'product_name' => $webhook_payload['prod_name'] ?? $webhook_payload['product_name'] ?? null,
            'product_code' => $webhook_payload['prod_code'] ?? null,
            'customer_email' => $webhook_payload['client_email'] ?? $webhook_payload['customer_email'] ?? null,
            'customer_name' => $webhook_payload['client_name'] ?? $webhook_payload['customer_name'] ?? null,
            'customer_document' => $webhook_payload['client_document'] ?? $webhook_payload['customer_document'] ?? null,
            'customer_phone' => $webhook_payload['client_cel'] ?? $webhook_payload['client_phone'] ?? $webhook_payload['customer_phone'] ?? null,
            'amount' => $amount,
            'commission_amount' => $commission_amount,
            'currency' => $webhook_payload['trans_currency'] ?? 'BRL',
            'conversion_date' => $webhook_payload['trans_date'] ?? $webhook_payload['created_at'] ?? date('Y-m-d H:i:s'),
            'approval_date' => $webhook_payload['approval_date'] ?? $webhook_payload['trans_date'] ?? null,
            'payment_type' => $this->mapPaymentMethod($webhook_payload['trans_payment_method'] ?? $webhook_payload['payment_method'] ?? ''),
            'installments' => $webhook_payload['trans_installments'] ?? $webhook_payload['installments'] ?? 1,
            'affiliate_id' => $webhook_payload['aff_id'] ?? $webhook_payload['affiliate_id'] ?? null,
            'affiliate_name' => $webhook_payload['aff_name'] ?? $webhook_payload['affiliate_name'] ?? null,
            'affiliate_email' => $webhook_payload['aff_email'] ?? $webhook_payload['affiliate_email'] ?? null,
            'commission_percentage' => $webhook_payload['commission_percentage'] ?? null,
            'subscription_id' => $webhook_payload['subscription_id'] ?? null,
            'subscription_status' => $webhook_payload['subscription_status'] ?? null,
            'subscription_plan' => $webhook_payload['subscription_plan'] ?? null,
            'subscription_next_charge' => $webhook_payload['subscription_next_charge'] ?? null,
            'utm_source' => $webhook_payload['utm_source'] ?? null,
            'utm_campaign' => $webhook_payload['utm_campaign'] ?? null,
            'utm_medium' => $webhook_payload['utm_medium'] ?? null,
            'utm_content' => $webhook_payload['utm_content'] ?? null,
            'utm_term' => $webhook_payload['utm_term'] ?? null,
            'auth_key' => $webhook_payload['basic_authentication'] ?? $webhook_payload['auth_key'] ?? null,
            'raw_data' => json_encode($webhook_payload)
        ];

        return $processed;
    }

    /**
     * Mapear status da Braip para status interno
     */
    private function mapBraipStatus($braip_status) {
        // Normalizar status para lowercase
        $braip_status = strtolower(trim($braip_status));

        $status_map = [
            'approved' => 'approved',
            'paid' => 'approved',
            'pagamento aprovado' => 'approved',
            'waiting_payment' => 'pending',
            'aguardando pagamento' => 'pending',
            'pending' => 'pending',
            'cancelled' => 'cancelled',
            'cancelada' => 'cancelled',
            'canceled' => 'cancelled',
            'chargeback' => 'chargeback',
            'refunded' => 'refunded',
            'devolvida' => 'refunded',
            'partially_paid' => 'partially_paid',
            'parcialmente pago' => 'partially_paid',
            'late_payment' => 'late_payment',
            'pagamento atrasado' => 'late_payment',
            'under_analysis' => 'pending',
            'em análise' => 'pending',
            'pending_refund' => 'pending_refund',
            'estorno pendente' => 'pending_refund',
            'processing' => 'pending',
            'em processamento' => 'pending',
            'expired' => 'cancelled',
            'vencida' => 'cancelled'
        ];

        return $status_map[$braip_status] ?? 'pending';
    }

    /**
     * Mapear método de pagamento
     */
    private function mapPaymentMethod($payment_method) {
        $payment_method = strtolower(trim($payment_method));

        $method_map = [
            'credit_card' => 'credit_card',
            'cartao de credito' => 'credit_card',
            'cartão de crédito' => 'credit_card',
            'debit_card' => 'debit_card',
            'cartao de debito' => 'debit_card',
            'cartão de débito' => 'debit_card',
            'boleto' => 'boleto',
            'boleto bancario' => 'boleto',
            'boleto bancário' => 'boleto',
            'pix' => 'pix',
            'two_cards' => 'two_cards',
            'dois cartoes' => 'two_cards',
            'dois cartões' => 'two_cards'
        ];

        return $method_map[$payment_method] ?? $payment_method;
    }

    /**
     * Mapear status de assinatura
     */
    private function mapSubscriptionStatus($subscription_status) {
        $subscription_status = strtolower(trim($subscription_status));

        $status_map = [
            'active' => 'active',
            'ativa' => 'active',
            'late' => 'late',
            'atrasada' => 'late',
            'cancelled_support' => 'cancelled',
            'cancelada pelo suporte' => 'cancelled',
            'cancelled_customer' => 'cancelled',
            'cancelada pelo cliente' => 'cancelled',
            'cancelled_seller' => 'cancelled',
            'cancelada pelo vendedor' => 'cancelled',
            'cancelled_platform' => 'cancelled',
            'cancelada pela plataforma' => 'cancelled',
            'inactive' => 'inactive',
            'inativa' => 'inactive',
            'expired' => 'expired',
            'vencida' => 'expired'
        ];

        return $status_map[$subscription_status] ?? $subscription_status;
    }

    /**
     * Validar evento do webhook
     */
    public function isValidWebhookEvent($event_type) {
        $valid_events = [
            'approved',
            'paid',
            'waiting_payment',
            'cancelled',
            'chargeback',
            'refunded',
            'partially_paid',
            'late_payment',
            'under_analysis',
            'pending_refund',
            'processing',
            // Eventos de assinatura
            'subscription_active',
            'subscription_late',
            'subscription_cancelled',
            'subscription_inactive',
            'subscription_expired'
        ];

        return in_array($event_type, $valid_events);
    }

    /**
     * Verificar se o webhook é de teste
     */
    public function isTestWebhook($webhook_payload) {
        // Verificar campos que indicam teste
        if (isset($webhook_payload['test']) && $webhook_payload['test'] === true) {
            return true;
        }

        if (isset($webhook_payload['client_email']) &&
            (strpos($webhook_payload['client_email'], 'test@') === 0 ||
             strpos($webhook_payload['client_email'], 'teste@') === 0)) {
            return true;
        }

        return false;
    }

    /**
     * Testar acesso à API
     */
    public function testApiAccess($api_token) {
        // A Braip não tem endpoint público de teste específico
        // Por enquanto, apenas validamos se o token existe
        return [
            'success' => !empty($api_token),
            'message' => 'Token configurado. Configure o webhook em: ' . $this->braip_base_url . '/webhook'
        ];
    }

    /**
     * Configurar webhook na Braip
     */
    public function setupWebhook($api_token, $webhook_url) {
        return [
            'success' => true,
            'webhook_url' => $webhook_url,
            'instructions' => 'Configure este webhook no Painel Braip: Ferramentas > Postback > Nova documentação. Eventos recomendados: Pagamento Aprovado, Cancelada, Chargeback, Devolvida, Parcialmente Pago, Pagamento Atrasado. Para assinaturas: Ativa, Atrasada, Cancelada (todas), Inativa, Vencida.',
            'auth_key_location' => 'Copie a Chave Única em Ferramentas > Postback > Documentação'
        ];
    }

    /**
     * Gerar URL de webhook
     */
    public function getWebhookUrl($base_url = null) {
        if (!$base_url) {
            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
                       . "://" . $_SERVER['HTTP_HOST'];
        }

        return $base_url . '/webhook/braip';
    }

    /**
     * Processar eventos de assinatura
     */
    public function processSubscriptionEvent($webhook_payload) {
        $subscription_data = [
            'subscription_id' => $webhook_payload['subscription_id'] ?? null,
            'subscription_status' => $this->mapSubscriptionStatus($webhook_payload['subscription_status'] ?? ''),
            'subscription_plan' => $webhook_payload['subscription_plan'] ?? null,
            'subscription_next_charge' => $webhook_payload['subscription_next_charge'] ?? null,
            'customer_email' => $webhook_payload['client_email'] ?? null,
            'customer_name' => $webhook_payload['client_name'] ?? null,
            'product_id' => $webhook_payload['prod_id'] ?? null,
            'product_name' => $webhook_payload['prod_name'] ?? null,
            'platform' => 'braip',
            'raw_data' => json_encode($webhook_payload)
        ];

        return $subscription_data;
    }

    /**
     * Verificar se é evento de pagamento ou assinatura
     */
    public function getEventType($webhook_payload) {
        if (isset($webhook_payload['subscription_id']) || isset($webhook_payload['subscription_status'])) {
            return 'subscription';
        }

        if (isset($webhook_payload['trans_id']) || isset($webhook_payload['transaction_id'])) {
            return 'payment';
        }

        return 'unknown';
    }
}
