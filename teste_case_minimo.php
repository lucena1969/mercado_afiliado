<?php
// Teste mínimo para verificar se o case está sendo executado
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Método não permitido";
    exit;
}

$acao = $_POST['acao'] ?? '';

switch ($acao) {
    case 'relatorio_execucao_pca':
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Case executado com sucesso!',
            'debug' => [
                'acao' => $acao,
                'post_keys' => array_keys($_POST),
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
        exit;
        break;

    default:
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Ação não encontrada: ' . $acao
        ]);
        exit;
}
?>