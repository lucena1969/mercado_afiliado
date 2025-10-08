<?php
// Teste POST básico
file_put_contents(__DIR__ . '/post_log.txt', date('Y-m-d H:i:s') . " - POST recebido\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    file_put_contents(__DIR__ . '/post_log.txt', "POST DATA: " . print_r($_POST, true) . "\n", FILE_APPEND);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'POST recebido com sucesso',
        'method' => $_SERVER['REQUEST_METHOD'],
        'post_data' => $_POST
    ]);
} else {
    file_put_contents(__DIR__ . '/post_log.txt', "Método: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Método incorreto: ' . $_SERVER['REQUEST_METHOD']
    ]);
}
?>