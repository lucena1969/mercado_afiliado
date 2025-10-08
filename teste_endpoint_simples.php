<?php
// Teste endpoint simplificado para debug
header('Content-Type: application/json');

try {
    // Simular mesmos dados do relatorio_execucao_pca
    echo json_encode([
        'success' => true,
        'message' => 'Teste endpoint funcionando',
        'data' => [
            'resultados' => [],
            'estatisticas' => [
                'total_registros' => 0,
                'valor_total_formatado' => 'R$ 0,00'
            ]
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>