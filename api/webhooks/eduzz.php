<?php
/**
 * API Webhooks Eduzz - Endpoint específico para receber webhooks da Eduzz
 * URL: /api/webhooks/eduzz/{token}
 */

// Headers para webhooks
header('Content-Type: application/json');

// Capturar token da URL
$token = $_GET['token'] ?? null;

// Logs de entrada
error_log("Webhook Eduzz recebido - Token: {$token}, Method: " . $_SERVER['REQUEST_METHOD']);

require_once '../../config/app.php';
require_once '../../app/controllers/EduzzController.php';

try {
    // Validar token
    if (!$token) {
        http_response_code(400);
        echo json_encode(['error' => 'Token é obrigatório']);
        exit;
    }

    $eduzzController = new EduzzController();

    // Verificar método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
        exit;
    }

    // Processamento do webhook
    $eduzzController->processWebhook($token);

} catch (Exception $e) {
    error_log("Erro na API de webhooks Eduzz: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}
