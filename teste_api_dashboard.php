<?php
// Teste da API real do dashboard
require_once 'config.php';
require_once 'functions.php';

session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nivel'] = 1;

// Simular a chamada POST para a API
$_POST['acao'] = 'dashboard_executivo_pca';

// Redirecionar para process.php e capturar o resultado
ob_start();
include 'process.php';
$resultado = ob_get_clean();

echo "<h1>🧪 Teste API Dashboard PCA</h1>\n";
echo "<h3>Resultado da API:</h3>\n";
echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>\n";
echo htmlspecialchars($resultado);
echo "</pre>\n";

// Tentar decodificar JSON
$dados = json_decode($resultado, true);
if ($dados) {
    echo "<h3>Dados Decodificados:</h3>\n";
    if ($dados['success']) {
        $stats = $dados['estatisticas'];
        echo "<ul>\n";
        echo "<li><strong>Total Contratações:</strong> " . ($stats['total_contratacoes'] ?? 'N/A') . "</li>\n";
        echo "<li><strong>% Licitados:</strong> " . ($stats['percentual_licitados'] ?? 'N/A') . "%</li>\n";
        echo "<li><strong>Valor Total:</strong> " . ($stats['valor_total_formatado'] ?? 'N/A') . "</li>\n";
        echo "<li><strong>Em Atraso:</strong> " . ($stats['total_atraso'] ?? 'N/A') . "</li>\n";
        echo "<li><strong>Em Preparação:</strong> " . ($stats['total_preparacao'] ?? 'N/A') . "</li>\n";
        echo "<li><strong>Encerradas:</strong> " . ($stats['total_encerradas'] ?? 'N/A') . "</li>\n";
        echo "</ul>\n";
    } else {
        echo "<p>❌ Erro na API: " . ($dados['message'] ?? 'Desconhecido') . "</p>\n";
    }
} else {
    echo "<p>❌ Erro ao decodificar JSON da API</p>\n";
}

echo "<p><a href='relatorios_gerenciais.php?modulo=planejamento'>📊 Ver Dashboard Real</a></p>\n";
?>