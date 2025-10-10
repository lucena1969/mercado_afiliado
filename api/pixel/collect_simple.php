<?php
/**
 * Pixel BR - Coletor Simplificado para Teste
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON payload');
    }
    
    // Log básico para arquivo
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $input,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    $log_file = __DIR__ . '/../../logs/pixel_events.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
    
    echo json_encode([
        'ok' => true,
        'message' => 'Event received successfully',
        'event_id' => $input['event_id'] ?? 'unknown',
        'timestamp' => date('c'),
        'dispatch' => [
            'logged_to_file' => true,
            'bridges_triggered' => 0
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>