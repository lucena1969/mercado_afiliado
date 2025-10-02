<?php
/**
 * Debug do webhook - Verificar se está recebendo requisições
 * APAGUE após usar!
 */

header('Content-Type: application/json');

// Capturar TUDO
$debug_info = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'url' => $_SERVER['REQUEST_URI'],
    'headers' => getallheaders(),
    'get' => $_GET,
    'post' => $_POST,
    'raw_input' => file_get_contents('php://input'),
    'server' => [
        'CONTENT_TYPE' => $_SERVER['CONTENT_TYPE'] ?? 'não definido',
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
        'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? ''
    ]
];

// Salvar em arquivo
file_put_contents(
    __DIR__ . '/debug_log.json',
    json_encode($debug_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n",
    FILE_APPEND
);

// Retornar sucesso
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Debug capturado',
    'received_at' => date('Y-m-d H:i:s'),
    'data' => $debug_info
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
