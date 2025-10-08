<?php
// Teste específico para verificar o erro de JSON
require_once 'config.php';
require_once 'functions.php';

// Simular REQUEST_METHOD
$_SERVER['REQUEST_METHOD'] = 'POST';

// Simular sessão válida
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nivel'] = 1;

// Simular dados POST como se fosse chamado pelo JavaScript
$_POST = [
    'acao' => 'relatorio_execucao_pca',
    'ano' => '2025',
    'data_inicio' => '',
    'data_fim' => '',
    'area_requisitante_filtro' => '',
    'categoria_filtro' => '',
    'status_execucao_filtro' => '',
    'status_contratacao_filtro' => '',
    'situacao_original_filtro' => '',
    'tem_licitacao_filtro' => '',
    'valor_minimo' => '',
    'valor_maximo' => ''
];

// Capturar output
ob_start();
include 'process.php';
$output = ob_get_clean();

echo "=== SAÍDA DO PROCESS.PHP ===\n";
echo "Tamanho: " . strlen($output) . " caracteres\n";
echo "Conteúdo:\n";
echo $output;

// Tentar decodificar JSON
echo "\n\n=== TESTE JSON ===\n";
$json = json_decode($output, true);
if ($json === null) {
    echo "ERRO: JSON inválido - " . json_last_error_msg() . "\n";
} else {
    echo "JSON válido\n";
    echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
    if (isset($json['data']['resultados'])) {
        echo "Resultados: " . count($json['data']['resultados']) . " registros\n";
    }
}
?>