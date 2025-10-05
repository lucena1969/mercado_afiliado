<?php
/**
 * Service Braip - Cliente para integração com Braip
 * Plataforma: https://ev.braip.com
 * Baseado na documentação de integração via webhook/postback
 * A Braip usa webhook/postback como principal meio de integração
 */

class BraipService {
    private $api_key;
    private $auth_key; // Chave única de autenticação do webhook
    private $base_url = 'https://ev.braip.com';

    public function __construct($api_key, $auth_key = null) {
        $this->api_key = $api_key;
        $this->auth_key = $auth_key;
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

    /**
     * Processar webhook da Braip
     * A Braip pode enviar dados via POST ou GET
     * Formato variável dependendo da configuração
     */
    public function processWebhook($payload) {
        // Se o payload é string, tentar decodificar JSON
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if ($decoded) {
                $payload = $decoded;
            }
        }

        // Validar autenticação se disponível
        if ($this->auth_key) {
            $received_auth = $payload['basic_authentication'] ?? $payload['auth_key'] ?? null;
            if ($received_auth && !hash_equals($this->auth_key, $received_auth)) {
                throw new Exception('Autenticação do webhook inválida');
            }
        }

        // Determinar tipo de evento (pagamento ou assinatura)
        $event_type = $this->determineEventType($payload);

        if ($event_type === 'subscription') {
            return $this->mapSubscriptionData($payload);
        } else {
            return $this->mapPaymentData($payload);
        }
    }

    /**
     * Determinar tipo de evento
     */
    private function determineEventType($payload) {
        // Se tem subscription_id ou subscription_status, é assinatura
        if (isset($payload['subscription_id']) || isset($payload['subscription_status'])) {
            return 'subscription';
        }

        // Caso contrário, é pagamento
        return 'payment';
    }

    /**
     * Mapear dados de pagamento para formato padrão
     * Baseado na estrutura de webhook da Braip
     */
    private function mapPaymentData($payload) {
        // Normalizar valor
        $amount = 0;
        if (isset($payload['trans_value'])) {
            $amount = (float)str_replace(',', '.', $payload['trans_value']);
        } elseif (isset($payload['prod_value'])) {
            $amount = (float)str_replace(',', '.', $payload['prod_value']);
        }

        // Calcular comissão
        $commission_amount = 0;
        if (isset($payload['commission_value'])) {
            $commission_amount = (float)str_replace(',', '.', $payload['commission_value']);
        } elseif (isset($payload['commission_percentage']) && $amount > 0) {
            $commission_percentage = (float)str_replace(',', '.', $payload['commission_percentage']);
            $commission_amount = ($amount * $commission_percentage) / 100;
        }

        // Extrair telefone
        $phone = $payload['client_cel'] ?? $payload['client_phone'] ?? $payload['customer_phone'] ?? null;

        // Mapear status
        $status = $this->mapStatus($payload['trans_status'] ?? $payload['status'] ?? 'unknown');

        return [
            'external_sale_id' => $payload['trans_id'] ?? $payload['transaction_id'] ?? null,
            'customer_name' => $payload['client_name'] ?? $payload['customer_name'] ?? null,
            'customer_email' => $payload['client_email'] ?? $payload['customer_email'] ?? null,
            'customer_document' => $payload['client_document'] ?? $payload['customer_document'] ?? null,
            'customer_phone' => $phone,
            'amount' => $amount,
            'commission_amount' => $commission_amount,
            'currency' => $payload['trans_currency'] ?? $payload['currency'] ?? 'BRL',
            'status' => $status,
            'payment_method' => $this->mapPaymentMethod($payload['trans_payment_method'] ?? $payload['payment_method'] ?? ''),
            'conversion_date' => $payload['trans_date'] ?? $payload['created_at'] ?? date('Y-m-d H:i:s'),
            'approval_date' => $payload['approval_date'] ?? $payload['trans_date'] ?? null,
            'refund_date' => isset($payload['refund_date']) ? $payload['refund_date'] : null,
            'external_product_id' => $payload['prod_id'] ?? $payload['product_id'] ?? null,
            'product_name' => $payload['prod_name'] ?? $payload['product_name'] ?? null,
            'utm_source' => $payload['utm_source'] ?? null,
            'utm_medium' => $payload['utm_medium'] ?? null,
            'utm_campaign' => $payload['utm_campaign'] ?? null,
            'utm_content' => $payload['utm_content'] ?? null,
            'utm_term' => $payload['utm_term'] ?? null,
            'metadata_json' => json_encode([
                'platform_data' => $payload,
                'affiliate_id' => $payload['aff_id'] ?? null,
                'affiliate_name' => $payload['aff_name'] ?? null,
                'affiliate_email' => $payload['aff_email'] ?? null,
                'commission_percentage' => $payload['commission_percentage'] ?? null,
                'installments' => $payload['trans_installments'] ?? $payload['installments'] ?? 1,
                'product_code' => $payload['prod_code'] ?? null,
                'product_type' => $payload['prod_type'] ?? null
            ])
        ];
    }

    /**
     * Mapear dados de assinatura para formato padrão
     */
    private function mapSubscriptionData($payload) {
        // Extrair telefone
        $phone = $payload['client_cel'] ?? $payload['client_phone'] ?? null;

        // Separar DDD e número se possível
        $phone_ddd = null;
        $phone_number = null;
        if ($phone) {
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($phone) >= 10) {
                $phone_ddd = substr($phone, 0, 2);
                $phone_number = substr($phone, 2);
            }
        }

        // Normalizar valor
        $amount = 0;
        if (isset($payload['trans_value'])) {
            $amount = (float)str_replace(',', '.', $payload['trans_value']);
        } elseif (isset($payload['prod_value'])) {
            $amount = (float)str_replace(',', '.', $payload['prod_value']);
        }

        return [
            'type' => 'subscription',
            'external_subscription_id' => $payload['subscription_id'] ?? null,
            'external_subscriber_code' => $payload['subscriber_code'] ?? $payload['client_document'] ?? null,
            'external_plan_id' => $payload['subscription_plan'] ?? $payload['plan_id'] ?? null,
            'subscriber_name' => $payload['client_name'] ?? $payload['customer_name'] ?? null,
            'subscriber_email' => $payload['client_email'] ?? $payload['customer_email'] ?? null,
            'subscriber_phone_ddd' => $phone_ddd,
            'subscriber_phone_number' => $phone_number,
            'subscriber_cell_ddd' => $phone_ddd,
            'subscriber_cell_number' => $phone_number,
            'plan_name' => $payload['prod_name'] ?? $payload['product_name'] ?? 'Assinatura',
            'status' => $this->mapSubscriptionStatus($payload['subscription_status'] ?? 'unknown'),
            'actual_recurrence_value' => $amount,
            'currency' => $payload['trans_currency'] ?? $payload['currency'] ?? 'BRL',
            'cancellation_date' => $this->extractCancellationDate($payload),
            'date_next_charge' => $payload['subscription_next_charge'] ?? $payload['next_charge_date'] ?? null,
            'external_product_id' => $payload['prod_id'] ?? $payload['product_id'] ?? null,
            'product_name' => $payload['prod_name'] ?? $payload['product_name'] ?? null,
            'metadata_json' => json_encode([
                'platform_data' => $payload,
                'subscription_plan' => $payload['subscription_plan'] ?? null,
                'affiliate_id' => $payload['aff_id'] ?? null,
                'affiliate_name' => $payload['aff_name'] ?? null,
                'affiliate_email' => $payload['aff_email'] ?? null
            ])
        ];
    }

    /**
     * Extrair data de cancelamento baseado no status
     */
    private function extractCancellationDate($payload) {
        $status = strtolower($payload['subscription_status'] ?? '');

        if (strpos($status, 'cancel') !== false ||
            strpos($status, 'inativa') !== false ||
            strpos($status, 'vencida') !== false) {
            return $payload['cancellation_date'] ?? $payload['updated_at'] ?? date('Y-m-d H:i:s');
        }

        return null;
    }

    /**
     * Mapear status de pagamento
     */
    private function mapStatus($status) {
        $status = strtolower(trim($status));

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

        return $status_map[$status] ?? 'pending';
    }

    /**
     * Mapear status de assinatura
     */
    private function mapSubscriptionStatus($status) {
        $status = strtolower(trim($status));

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
            'cancelled' => 'cancelled',
            'cancelada' => 'cancelled',
            'inactive' => 'inactive',
            'inativa' => 'inactive',
            'expired' => 'expired',
            'vencida' => 'expired'
        ];

        return $status_map[$status] ?? 'unknown';
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

    /**
     * Obter instruções de configuração do webhook
     */
    public function getWebhookInstructions($webhook_url) {
        return [
            'platform' => 'Braip',
            'webhook_url' => $webhook_url,
            'steps' => [
                '1. Acesse https://ev.braip.com/login',
                '2. No menu lateral, clique em Ferramentas > API',
                '3. Clique em "Novo token" e salve-o',
                '4. Depois, vá em Ferramentas > Postback',
                '5. Clique em "Nova documentação"',
                '6. Cole a URL do webhook: ' . $webhook_url,
                '7. Selecione o produto que deseja integrar',
                '8. Marque os eventos:',
                '   - Pagamento: Aprovado, Cancelada, Chargeback, Devolvida',
                '   - Assinatura: Ativa, Atrasada, Canceladas (todas), Inativa, Vencida',
                '9. Selecione método HTTP: POST',
                '10. Em Documentação, copie a "Chave Única" para autenticação'
            ],
            'events_recommended' => [
                'payment' => [
                    'Pagamento Aprovado',
                    'Cancelada',
                    'Chargeback',
                    'Devolvida',
                    'Parcialmente Pago',
                    'Pagamento Atrasado'
                ],
                'subscription' => [
                    'Ativa',
                    'Atrasada',
                    'Cancelada pelo suporte',
                    'Cancelada pelo cliente',
                    'Cancelada pelo vendedor',
                    'Cancelada pela plataforma',
                    'Inativa',
                    'Vencida'
                ]
            ]
        ];
    }

    /**
     * Verificar se webhook é de teste
     */
    public function isTestWebhook($payload) {
        // Verificar campos que indicam teste
        if (isset($payload['test']) && $payload['test'] === true) {
            return true;
        }

        $email = $payload['client_email'] ?? $payload['customer_email'] ?? '';
        if (strpos($email, 'test@') === 0 || strpos($email, 'teste@') === 0) {
            return true;
        }

        return false;
    }

    /**
     * Validar chave de autenticação do webhook
     */
    public function validateWebhookAuth($payload) {
        if (!$this->auth_key) {
            return true; // Se não tem auth_key configurada, aceita
        }

        $received_auth = $payload['basic_authentication'] ?? $payload['auth_key'] ?? null;

        if (!$received_auth) {
            return false;
        }

        return hash_equals($this->auth_key, $received_auth);
    }
}