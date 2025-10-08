<?php
// Teste direto do endpoint como se fosse uma requisição AJAX
$url = 'http://localhost/sistema_licitacao/process.php';

$data = [
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

$postdata = http_build_query($data);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => $postdata
    ]
]);

echo "=== TESTE DE ENDPOINT ===\n";
echo "URL: $url\n";
echo "Dados POST: " . print_r($data, true) . "\n";

$response = file_get_contents($url, false, $context);

echo "=== RESPOSTA ===\n";
echo "Tamanho: " . strlen($response) . " bytes\n";
echo "Conteúdo:\n";
echo $response . "\n";

echo "\n=== TESTE JSON ===\n";
$json = json_decode($response, true);
if ($json === null) {
    echo "ERRO JSON: " . json_last_error_msg() . "\n";
    echo "Primeiros 500 caracteres da resposta:\n";
    echo substr($response, 0, 500) . "\n";
} else {
    echo "JSON válido\n";
    echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
    if (isset($json['data'])) {
        echo "Tem dados: sim\n";
    }
}
?>