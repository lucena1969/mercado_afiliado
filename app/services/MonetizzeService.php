<?php
/**
 * Service Monetizze - Cliente para API da Monetizze v2.1
 * Documentação: https://api.monetizze.com.br/2.1/apidoc
 */

class MonetizzeService {
    private $consumer_key; // X_CONSUMER_KEY
    private $token; // TOKEN gerado dinamicamente
    private $token_expires_at;
    private $base_url = 'https://api.monetizze.com.br/2.1';

    public function __construct($consumer_key) {
        $this->consumer_key = $consumer_key;
    }

    /**
     * 1. AUTENTICAÇÃO - Gerar Token de Acesso
     * GET /2.1/token
     * Token válido por 15 minutos, renova automaticamente
     */
    public function generateToken() {
        $url = $this->base_url . '/token';

        $headers = [
            'X_CONSUMER_KEY: ' . $this->consumer_key,
            'Content-Type: application/json'
        ];

        try {
            $response = $this->makeRequest('GET', $url, null, $headers, false);

            if (isset($response['TOKEN'])) {
                $this->token = $response['TOKEN'];
                $this->token_expires_at = strtotime($response['expire']);
                return true;
            }

            throw new Exception('Token não retornado pela API');
        } catch (Exception $e) {
            error_log("Erro ao gerar token Monetizze: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verificar se o token precisa ser renovado
     */
    private function ensureValidToken() {
        // Se não tem token ou está expirando em menos de 1 minuto
        if (!$this->token || !$this->token_expires_at || (time() >= ($this->token_expires_at - 60))) {
            $this->generateToken();
        }
    }

    /**
     * 2. BUSCAR TRANSAÇÕES - Pesquisar vendas
     * GET /2.1/transactions
     *
     * @param array $filters - Filtros opcionais:
     *   - product: Código do produto
     *   - transaction: Código da venda
     *   - email: Email do comprador
     *   - date_min: Data início (yyyy-mm-dd hh:mm:ss)
     *   - date_max: Data fim (yyyy-mm-dd hh:mm:ss)
     *   - status: Array [1=Aguardando, 2=Finalizada, 3=Cancelada, 4=Devolvida, 5=Bloqueada, 6=Completa]
     *   - forma_pagamento: Array [1=Cartão, 3=Boleto, 4=PayPal, 8=Pix]
     *   - page: Número da página (padrão: retorna todos)
     */
    public function getTransactions($filters = []) {
        $this->ensureValidToken();

        $url = $this->base_url . '/transactions';

        if (!empty($filters)) {
            $url .= '?' . http_build_query($filters);
        }

        $headers = [
            'TOKEN: ' . $this->token,
            'Content-Type: application/x-www-form-urlencoded'
        ];

        try {
            $response = $this->makeRequest('GET', $url, null, $headers);
            return $response;
        } catch (Exception $e) {
            error_log("Erro ao buscar transações Monetizze: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Buscar vendas por período
     */
    public function getSalesByPeriod($days = 30) {
        $end_date = date('Y-m-d 23:59:59');
        $start_date = date('Y-m-d 00:00:00', strtotime("-{$days} days"));

        return $this->getTransactions([
            'date_min' => $start_date,
            'date_max' => $end_date
        ]);
    }

    /**
     * 3. PROCESSAR WEBHOOK/POSTBACK
     * POST recebido via Ferramentas > Postback (Server to Server)
     *
     * Eventos (postback_evento):
     * 1 = Aguardando pagamento
     * 2 = Finalizada / Aprovada
     * 3 = Cancelada
     * 4 = Devolvida (Reembolso)
     * 5 = Bloqueada
     * 6 = Completa
     * 7 = Abandono de Checkout
     * 70 = Ingresso
     * 101-106 = Assinaturas e Recuperação Parcelada
     *
     * Importante: Não é garantido disparo único do evento (usar chave_unica)
     */
    public function processWebhook($payload) {
        // Validar estrutura do webhook
        if (!isset($payload['postback_evento'])) {
            // Se for teste da Monetizze, criar payload simulado
            if (isset($payload['teste']) || empty($payload)) {
                error_log("Monetizze: Recebido teste sem postback_evento, retornando dados simulados");
                $payload = $this->createTestPayload();
            } else {
                throw new Exception('Estrutura de webhook Monetizze inválida - campo postback_evento ausente. Campos recebidos: ' . implode(', ', array_keys($payload)));
            }
        }

        // postback_evento pode vir como string ou número
        $event_code = (int) $payload['postback_evento'];

        // Mapear código do evento para status
        $status_map = [
            1 => 'pending',      // Aguardando pagamento
            2 => 'approved',     // Finalizada / Aprovada
            3 => 'cancelled',    // Cancelada
            4 => 'refunded',     // Devolvida (Reembolso)
            5 => 'blocked',      // Bloqueada
            6 => 'approved',     // Completa (também aprovada)
            7 => 'pending',      // Abandono de checkout (tratar como pending)
            101 => 'approved',   // Assinatura - Ativa
            102 => 'pending',    // Assinatura - Inadimplente
            103 => 'cancelled',  // Assinatura - Cancelada
            104 => 'pending',    // Assinatura - Aguardando pagamento
            105 => 'approved',   // Recuperação Parcelada - Ativa
            106 => 'cancelled'   // Recuperação Parcelada - Cancelada
        ];

        $status = $status_map[$event_code] ?? 'pending';

        // Verificar se é assinatura
        if ($event_code >= 101 && $event_code <= 106) {
            return $this->mapSubscriptionData($payload, $status);
        }

        return $this->mapSaleData($payload, $status);
    }

    /**
     * Mapear dados da venda do webhook para formato padrão
     * Estrutura oficial conforme documentação Monetizze
     */
    private function mapSaleData($data, $status) {
        // Campos no nível raiz (estrutura oficial)
        $codigo_venda = $data['codigo_venda'] ?? null;
        $chave_unica = $data['chave_unica'] ?? null;

        // Objetos aninhados
        $venda = $data['venda'] ?? [];
        $comprador = $data['comprador'] ?? [];
        $produto = $data['produto'] ?? [];
        $comissoes = $data['comissoes'] ?? [];
        $plano = $data['plano'] ?? [];

        // Calcular comissão total (somar comissões de afiliados, excluindo produtor)
        $commission_total = 0;
        if (!empty($comissoes)) {
            foreach ($comissoes as $comissao_item) {
                // Estrutura: array de objetos com chave 'comissao' OU array direto
                $comissao = $comissao_item['comissao'] ?? $comissao_item;

                if (isset($comissao['valor'])) {
                    $tipo = strtolower($comissao['tipo_comissao'] ?? '');
                    // Excluir apenas "Produtor" e "Sistema"
                    if ($tipo !== 'produtor' && $tipo !== 'sistema') {
                        $commission_total += (float) $comissao['valor'];
                    }
                }
            }
        }

        // Usar valorRecebido se não houver comissões
        if ($commission_total == 0 && isset($venda['valorRecebido'])) {
            $commission_total = (float) $venda['valorRecebido'];
        }

        return [
            'external_sale_id' => $codigo_venda ?? $venda['codigo'] ?? null,
            'customer_name' => $comprador['nome'] ?? null,
            'customer_email' => $comprador['email'] ?? null,
            'customer_document' => $comprador['cnpj_cpf'] ?? null,
            'amount' => (float) ($venda['valor'] ?? 0),
            'commission_amount' => $commission_total,
            'currency' => 'BRL',
            'status' => $status,
            'payment_method' => $venda['formaPagamento'] ?? $venda['meioPagamento'] ?? null,
            'conversion_date' => $venda['dataInicio'] ?? date('Y-m-d H:i:s'),
            'approval_date' => $status === 'approved' ? ($venda['dataFinalizada'] ?? date('Y-m-d H:i:s')) : null,
            'refund_date' => $status === 'refunded' ? ($venda['dataFinalizada'] ?? date('Y-m-d H:i:s')) : null,
            'external_product_id' => $produto['codigo'] ?? $data['codigo_produto'] ?? null,
            'product_name' => $produto['nome'] ?? null,
            'utm_source' => $venda['utm_source'] ?? null,
            'utm_medium' => $venda['utm_medium'] ?? null,
            'utm_campaign' => $venda['utm_campaign'] ?? null,
            'utm_content' => $venda['utm_content'] ?? null,
            'utm_term' => null,
            'metadata_json' => json_encode([
                'chave_unica' => $chave_unica,
                'codigo_status' => $data['codigo_status'] ?? null,
                'tipoEvento' => $data['tipoEvento'] ?? null,
                'tipoPostback' => $data['tipoPostback'] ?? null,
                'plano' => $plano,
                'full_payload' => $data
            ])
        ];
    }

    /**
     * Criar payload de teste quando a Monetizze envia teste vazio
     */
    private function createTestPayload() {
        return [
            'chave_unica' => md5('test_' . time()),
            'postback_evento' => '6', // Completa
            'codigo_venda' => 'TEST_' . rand(10000000, 99999999),
            'codigo_produto' => '999999',
            'codigo_status' => '6',
            'venda' => [
                'codigo' => 'TEST_' . rand(10000000, 99999999),
                'dataInicio' => date('Y-m-d H:i:s'),
                'dataFinalizada' => date('Y-m-d H:i:s'),
                'formaPagamento' => 'Teste',
                'status' => 'Completa',
                'valor' => '100.00',
                'valorRecebido' => '50.00',
                'quantidade' => '1'
            ],
            'produto' => [
                'codigo' => '999999',
                'nome' => 'Produto de Teste - Webhook Configurado'
            ],
            'comprador' => [
                'nome' => 'Teste Webhook',
                'email' => 'teste@webhook.com',
                'cnpj_cpf' => '00000000000'
            ],
            'tipoEvento' => [
                'codigo' => 6,
                'descricao' => 'Completa (Teste)'
            ]
        ];
    }

    /**
     * Validar credenciais da API
     */
    public function validateCredentials() {
        try {
            $this->generateToken();
            return true;
        } catch (Exception $e) {
            error_log("Falha na validação Monetizze: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fazer requisição HTTP
     */
    private function makeRequest($method, $url, $data = null, $headers = [], $use_token = true) {
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
            $error_response = json_decode($response, true);
            $error_msg = $error_response['Error'] ?? $error_response['error'] ?? $response;
            throw new Exception('Erro HTTP ' . $http_code . ': ' . $error_msg);
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Erro ao decodificar JSON: ' . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Mapear dados de assinatura do webhook para formato padrão
     * Eventos 101-106: Assinaturas e Recuperação Parcelada
     */
    private function mapSubscriptionData($data, $status) {
        $assinatura = $data['assinatura'] ?? [];
        $comprador = $data['comprador'] ?? [];
        $produto = $data['produto'] ?? [];
        $plano = $data['plano'] ?? [];
        $venda = $data['venda'] ?? [];

        return [
            'type' => 'subscription',
            'external_subscription_id' => $assinatura['codigo'] ?? null,
            'external_subscriber_code' => $data['codigo_venda'] ?? null,
            'external_plan_id' => $plano['codigo'] ?? null,
            'subscriber_name' => $comprador['nome'] ?? null,
            'subscriber_email' => $comprador['email'] ?? null,
            'subscriber_phone_ddd' => null,
            'subscriber_phone_number' => $comprador['telefone'] ?? null,
            'subscriber_cell_ddd' => null,
            'subscriber_cell_number' => null,
            'plan_name' => $plano['nome'] ?? null,
            'status' => $status,
            'actual_recurrence_value' => (float) ($venda['valor'] ?? 0),
            'currency' => 'BRL',
            'cancellation_date' => $status === 'cancelled' ? date('Y-m-d H:i:s') : null,
            'date_next_charge' => null,
            'external_product_id' => $produto['codigo'] ?? null,
            'product_name' => $produto['nome'] ?? null,
            'metadata_json' => json_encode($data)
        ];
    }

    /**
     * Mapear dados de transação da API para formato padrão
     */
    public function mapTransactionToSale($transaction) {
        $venda = $transaction['venda'] ?? $transaction;
        $comprador = $transaction['comprador'] ?? [];
        $produto = $transaction['produto'] ?? [];
        $comissoes = $transaction['comissoes'] ?? [];

        // Status da venda
        $status_map = [
            'Aguardando pagamento' => 'pending',
            'Finalizada' => 'approved',
            'Cancelada' => 'cancelled',
            'Devolvida' => 'refunded',
            'Bloqueada' => 'blocked',
            'Completa' => 'approved'
        ];

        $status_text = $venda['status'] ?? 'pending';
        $status = $status_map[$status_text] ?? 'pending';

        // Calcular comissão
        $commission_total = 0;
        if (!empty($comissoes)) {
            foreach ($comissoes as $comissao) {
                if (isset($comissao['valor']) &&
                    (!isset($comissao['tipo_comissao']) ||
                     stripos($comissao['tipo_comissao'], 'produtor') === false)) {
                    $commission_total += (float) $comissao['valor'];
                }
            }
        }

        return [
            'external_sale_id' => $venda['codigo'] ?? null,
            'customer_name' => $comprador['nome'] ?? null,
            'customer_email' => $comprador['email'] ?? null,
            'customer_document' => $comprador['cnpj_cpf'] ?? null,
            'amount' => (float) ($venda['valor'] ?? 0),
            'commission_amount' => $commission_total > 0 ? $commission_total : (float) ($venda['valorRecebido'] ?? 0),
            'currency' => 'BRL',
            'status' => $status,
            'payment_method' => $venda['formaPagamento'] ?? null,
            'conversion_date' => $venda['dataInicio'] ?? date('Y-m-d H:i:s'),
            'approval_date' => in_array($status, ['approved']) ? ($venda['dataFinalizada'] ?? null) : null,
            'refund_date' => $status === 'refunded' ? ($venda['dataFinalizada'] ?? date('Y-m-d H:i:s')) : null,
            'external_product_id' => $produto['codigo'] ?? null,
            'product_name' => $produto['nome'] ?? null,
            'utm_source' => $venda['utm_source'] ?? null,
            'utm_medium' => $venda['utm_medium'] ?? null,
            'utm_campaign' => $venda['utm_campaign'] ?? null,
            'utm_content' => $venda['utm_content'] ?? null,
            'utm_term' => null,
            'metadata_json' => json_encode($transaction)
        ];
    }
}