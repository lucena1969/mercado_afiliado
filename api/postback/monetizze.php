<?php
/**
 * Endpoint de Postback da Monetizze
 * Recebe notificações de vendas diretamente da plataforma Monetizze
 *
 * URL para configurar na Monetizze:
 * https://seu-dominio.com/api/postback/monetizze.php?token=SEU_TOKEN_WEBHOOK
 *
 * Configuração:
 * - Menu: Ferramentas > Postback
 * - Tipo: Server to Server
 * - Formato: JSON ou x-www-form-urlencoded
 */

// Habilitar log de erros
error_log("=== Postback Monetizze Recebido ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'não definido'));

// Headers de resposta
header('Content-Type: application/json; charset=utf-8');

// Permitir apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido. Use POST.'
    ]);
    exit;
}

// Carregar dependências
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../app/models/WebhookEvent.php';
require_once __DIR__ . '/../../app/models/Integration.php';
require_once __DIR__ . '/../../app/models/Sale.php';
require_once __DIR__ . '/../../app/models/Product.php';
require_once __DIR__ . '/../../app/services/MonetizzeService.php';

// Obter token de autenticação
$token = $_GET['token'] ?? null;

if (!$token) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Token de autenticação não fornecido'
    ]);
    error_log("ERRO: Token não fornecido");
    exit;
}

try {
    // Conectar ao banco
    $database = new Database();
    $conn = $database->getConnection();

    // Buscar integração pelo webhook token
    $integration = new Integration($conn);
    $integration_data = $integration->findByWebhookToken($token);

    if (!$integration_data) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Token de webhook não encontrado'
        ]);
        error_log("ERRO: Token não encontrado no banco: {$token}");
        exit;
    }

    // Verificar se é integração Monetizze
    if ($integration_data['platform'] !== 'monetizze') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Este endpoint é exclusivo para Monetizze'
        ]);
        error_log("ERRO: Platform incorreta: {$integration_data['platform']}");
        exit;
    }

    // Obter dados do postback
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    $payload = null;
    $raw_payload = file_get_contents('php://input');

    error_log("Raw payload recebido (" . strlen($raw_payload) . " bytes)");

    // Suportar ambos os formatos: JSON e x-www-form-urlencoded
    if (strpos($content_type, 'application/json') !== false) {
        // Formato JSON
        $payload = json_decode($raw_payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON inválido: ' . json_last_error_msg());
        }
        error_log("Formato detectado: JSON");
    } else {
        // Formato x-www-form-urlencoded (padrão)
        parse_str($raw_payload, $payload);

        // Se não conseguiu parsear do raw, tentar do $_POST
        if (empty($payload) && !empty($_POST)) {
            $payload = $_POST;
        }

        error_log("Formato detectado: x-www-form-urlencoded");
    }

    if (empty($payload)) {
        throw new Exception('Payload vazio ou inválido');
    }

    error_log("Payload parseado com sucesso. Campos: " . implode(', ', array_keys($payload)));

    // Validar estrutura básica do postback Monetizze
    if (!isset($payload['postback_evento'])) {
        throw new Exception('Campo obrigatório ausente: postback_evento');
    }

    // Extrair chave_unica para idempotência
    $chave_unica = $payload['chave_unica'] ?? null;

    if ($chave_unica) {
        // Verificar se este postback já foi processado
        $webhookEvent = new WebhookEvent($conn);
        $existing = $webhookEvent->findByUniqueKey('monetizze', $chave_unica);

        if ($existing) {
            // Postback duplicado - retornar sucesso sem reprocessar
            error_log("Postback duplicado detectado: {$chave_unica}");
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Postback já processado anteriormente',
                'duplicate' => true,
                'webhook_event_id' => $existing['id']
            ]);
            exit;
        }
    }

    // Salvar evento de webhook
    $event_code = (int) $payload['postback_evento'];
    $event_type = mapEventCode($event_code);

    $webhookEvent = new WebhookEvent($conn);
    $webhook_event_id = $webhookEvent->create([
        'integration_id' => $integration_data['id'],
        'platform' => 'monetizze',
        'event_type' => $event_type,
        'unique_key' => $chave_unica,
        'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        'raw_payload' => $raw_payload
    ]);

    error_log("Webhook event salvo: ID {$webhook_event_id}");

    // Processar postback com MonetizzeService
    $monetizzeService = new MonetizzeService($integration_data['api_key']);
    $sale_data = $monetizzeService->processWebhook($payload);

    // Verificar se é venda ou assinatura
    if (isset($sale_data['type']) && $sale_data['type'] === 'subscription') {
        error_log("Tipo: Assinatura (não implementado ainda)");
        // TODO: Implementar lógica de assinatura
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Assinatura recebida (processamento pendente)',
            'webhook_event_id' => $webhook_event_id
        ]);
        exit;
    }

    // Buscar ou criar produto
    $product_id = null;
    if (isset($sale_data['external_product_id'])) {
        $product = new Product($conn);
        $existing_product = $product->findByExternalId($integration_data['id'], $sale_data['external_product_id']);

        if ($existing_product) {
            $product_id = $existing_product['id'];
        } else {
            // Criar produto automaticamente
            $product->integration_id = $integration_data['id'];
            $product->external_id = $sale_data['external_product_id'];
            $product->name = $sale_data['product_name'] ?? 'Produto #' . $sale_data['external_product_id'];
            $product->price = $sale_data['amount'];
            $product->currency = $sale_data['currency'];
            $product->status = 'active';
            $product->metadata_json = json_encode([
                'created_from_postback' => true,
                'postback_date' => date('Y-m-d H:i:s')
            ]);

            if ($product->createOrUpdate()) {
                $product_id = $product->id;
                error_log("Produto criado: ID {$product_id}");
            }
        }
    }

    // Criar ou atualizar venda
    $sale = new Sale($conn);
    $sale->integration_id = $integration_data['id'];
    $sale->product_id = $product_id;
    $sale->external_sale_id = $sale_data['external_sale_id'];
    $sale->customer_name = $sale_data['customer_name'];
    $sale->customer_email = $sale_data['customer_email'];
    $sale->customer_document = $sale_data['customer_document'];
    $sale->amount = $sale_data['amount'];
    $sale->commission_amount = $sale_data['commission_amount'];
    $sale->currency = $sale_data['currency'];
    $sale->status = $sale_data['status'];
    $sale->payment_method = $sale_data['payment_method'];
    $sale->utm_source = $sale_data['utm_source'];
    $sale->utm_medium = $sale_data['utm_medium'];
    $sale->utm_campaign = $sale_data['utm_campaign'];
    $sale->utm_content = $sale_data['utm_content'];
    $sale->utm_term = $sale_data['utm_term'];
    $sale->conversion_date = $sale_data['conversion_date'];
    $sale->approval_date = $sale_data['approval_date'];
    $sale->refund_date = $sale_data['refund_date'];
    $sale->metadata_json = $sale_data['metadata_json'];

    // Verificar se é criação ou atualização
    $existing_sale = $sale->findByExternalId($integration_data['id'], $sale_data['external_sale_id']);
    $is_new = !$existing_sale;

    $success = $sale->createOrUpdate();

    if ($success) {
        // Atualizar data do último sync
        $integration->updateLastSync($integration_data['id']);

        error_log("Venda processada com sucesso: " . ($is_new ? 'CRIADA' : 'ATUALIZADA'));

        // Responder com sucesso (HTTP 200)
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Postback processado com sucesso',
            'data' => [
                'webhook_event_id' => $webhook_event_id,
                'sale_id' => $sale->id,
                'is_new' => $is_new,
                'event_type' => $event_type,
                'event_code' => $event_code
            ]
        ]);
    } else {
        throw new Exception('Falha ao salvar venda no banco de dados');
    }

} catch (Exception $e) {
    error_log("ERRO no processamento do postback: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar postback',
        'message' => $e->getMessage()
    ]);
}

/**
 * Mapear código do evento para descrição
 */
function mapEventCode($code) {
    $map = [
        1 => 'aguardando_pagamento',
        2 => 'finalizada',
        3 => 'cancelada',
        4 => 'devolvida',
        5 => 'bloqueada',
        6 => 'completa',
        7 => 'abandono_checkout',
        70 => 'ingresso',
        98 => 'cartao',
        99 => 'boleto',
        101 => 'assinatura_ativa',
        102 => 'assinatura_inadimplente',
        103 => 'assinatura_cancelada',
        104 => 'assinatura_aguardando',
        105 => 'recuperacao_ativa',
        106 => 'recuperacao_cancelada',
        120 => 'rastreio'
    ];

    return $map[$code] ?? 'evento_' . $code;
}
