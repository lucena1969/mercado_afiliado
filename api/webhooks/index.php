<?php
/**
 * API Webhooks - Endpoints para receber webhooks das redes
 * URL: /api/webhooks/{platform}/{token}
 */

// Headers para webhooks
header('Content-Type: application/json');

// Capturar parâmetros da URL
$platform = $_GET['platform'] ?? null;
$token = $_GET['token'] ?? null;

// Logs de entrada
error_log("Webhook recebido - Platform: {$platform}, Token: {$token}, Method: " . $_SERVER['REQUEST_METHOD']);

require_once '../../config/app.php';
require_once '../../app/controllers/WebhookController.php';

try {
    // Validar parâmetros
    if (!$platform || !$token) {
        http_response_code(400);
        echo json_encode(['error' => 'Platform e token são obrigatórios']);
        exit;
    }
    
    $webhookController = new WebhookController();
    
    // Verificar método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
        exit;
    }
    
    // Verificar se é um teste
    $is_test = isset($_GET['test']) && $_GET['test'] === '1';
    
    if ($is_test) {
        // Modo de teste
        $webhookController->testWebhook($platform, $token);
    } else {
        // Processamento normal
        $webhookController->processWebhook($platform, $token);
    }
    
} catch (Exception $e) {
    error_log("Erro na API de webhooks: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}