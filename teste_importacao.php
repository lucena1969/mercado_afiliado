<?php
// Script de teste para verificar importação PCA
require_once 'config.php';
require_once 'functions.php';

echo "<h1>🧪 Teste de Importação PCA</h1>\n";

// Simular sessão de usuário
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nivel'] = 1;

$arquivo = 'pca_2025.csv';

if (!file_exists($arquivo)) {
    echo "<p>❌ Arquivo '$arquivo' não encontrado!</p>\n";
    exit;
}

echo "<p>📄 Testando arquivo: <strong>$arquivo</strong></p>\n";

// Abrir arquivo e ler algumas linhas
$handle = fopen($arquivo, 'r');
if (!$handle) {
    echo "<p>❌ Erro ao abrir arquivo!</p>\n";
    exit;
}

// Detectar separador
$primeira_linha = fgets($handle);
rewind($handle);
$separador = ';';
if (substr_count($primeira_linha, ',') > substr_count($primeira_linha, ';')) {
    $separador = ',';
}

echo "<p>🔍 Separador detectado: <strong>'$separador'</strong></p>\n";

// Ler cabeçalho
$header = fgetcsv($handle, 0, $separador);
echo "<h3>📋 Cabeçalho do CSV:</h3>\n";
echo "<ol>\n";
foreach ($header as $i => $coluna) {
    echo "<li><strong>Coluna $i:</strong> " . htmlspecialchars($coluna) . "</li>\n";
}
echo "</ol>\n";

// Ler primeiras 3 linhas de dados
echo "<h3>📊 Primeiras linhas de dados:</h3>\n";
$linha_num = 1;
while (($linha = fgetcsv($handle, 0, $separador)) !== FALSE && $linha_num <= 3) {
    echo "<h4>Linha $linha_num:</h4>\n";
    echo "<ul>\n";

    // Mostrar área requisitante especificamente
    $area_requisitante = trim($linha[9] ?? 'N/A');
    echo "<li><strong>Área Requisitante (coluna 9):</strong> " . htmlspecialchars($area_requisitante) . "</li>\n";

    echo "<li><strong>Número Contratação (coluna 0):</strong> " . htmlspecialchars($linha[0] ?? 'N/A') . "</li>\n";
    echo "<li><strong>Título (coluna 3):</strong> " . htmlspecialchars(substr($linha[3] ?? 'N/A', 0, 100)) . "...</li>\n";
    echo "<li><strong>Categoria (coluna 4):</strong> " . htmlspecialchars($linha[4] ?? 'N/A') . "</li>\n";
    echo "</ul>\n";

    $linha_num++;
}

fclose($handle);

echo "<p>✅ Teste de leitura concluído!</p>\n";
echo "<p><a href='dashboard.php'>← Voltar ao Dashboard</a></p>\n";
?>