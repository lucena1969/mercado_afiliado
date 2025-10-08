<?php
/**
 * Teste direto da ação dashboard_executivo_qualificacoes
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/html; charset=utf-8');

require_once 'config.php';

$pdo = conectarDB();

echo "<h1>Teste Dashboard Qualificações</h1>";
echo "<style>body{font-family:Arial;padding:20px;} pre{background:#2c3e50;color:#ecf0f1;padding:15px;border-radius:6px;} .success{background:#d4edda;color:#155724;padding:15px;margin:10px 0;border-radius:6px;} .error{background:#f8d7da;color:#721c24;padding:15px;margin:10px 0;border-radius:6px;}</style>";

// Teste 1: Contar total de registros
echo "<h3>1. Total de registros na tabela qualificacoes</h3>";
$sql = "SELECT COUNT(*) as total FROM qualificacoes";
$stmt = $pdo->query($sql);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<div class='success'>Total: " . $result['total'] . "</div>";

// Teste 2: Ver todos os status distintos
echo "<h3>2. Status distintos na tabela</h3>";
$sql = "SELECT DISTINCT status, COUNT(*) as qtd FROM qualificacoes GROUP BY status";
$stmt = $pdo->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($results);
echo "</pre>";

// Teste 3: Testar as queries individuais
echo "<h3>3. Teste das queries de contagem</h3>";

$sql = "SELECT
    COUNT(*) as total_qualificacoes,
    SUM(valor_estimado) as valor_total,
    COUNT(CASE WHEN status LIKE 'EM A%' THEN 1 END) as total_analise,
    COUNT(CASE WHEN status LIKE 'CONCLU%' THEN 1 END) as total_concluidas,
    COUNT(CASE WHEN status = 'ARQUIVADO' THEN 1 END) as total_arquivadas
FROM qualificacoes";

$stmt = $pdo->query($sql);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<div class='success'>";
echo "<strong>Resultado da query:</strong><br>";
echo "Total: " . $result['total_qualificacoes'] . "<br>";
echo "Valor Total: R$ " . number_format($result['valor_total'], 2, ',', '.') . "<br>";
echo "Em Análise: " . $result['total_analise'] . "<br>";
echo "Concluídas: " . $result['total_concluidas'] . "<br>";
echo "Arquivadas: " . $result['total_arquivadas'] . "<br>";
echo "</div>";

// Teste 4: Simular a chamada ao process_relatorios.php
echo "<h3>4. Simular POST para process_relatorios.php</h3>";

$_POST['acao'] = 'dashboard_executivo_qualificacoes';
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
include 'process_relatorios.php';
$output = ob_get_clean();

echo "<strong>Resposta do process_relatorios.php:</strong><br>";

// Tentar parsear como JSON
$json = @json_decode($output, true);

if ($json) {
    echo "<div class='success'>";
    echo "<pre>" . json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    echo "</div>";

    if ($json['success']) {
        echo "<div class='success'>";
        echo "<strong>✅ Sucesso!</strong><br>";
        echo "Estatísticas:<br>";
        echo "- Total: " . $json['estatisticas']['total_qualificacoes'] . "<br>";
        echo "- Em Análise: " . $json['estatisticas']['total_analise'] . "<br>";
        echo "- Concluídas: " . $json['estatisticas']['total_concluidas'] . "<br>";
        echo "- Arquivadas: " . $json['estatisticas']['total_arquivadas'] . "<br>";
        echo "</div>";
    } else {
        echo "<div class='error'>❌ Erro: " . ($json['message'] ?? 'Desconhecido') . "</div>";
    }
} else {
    echo "<div class='error'>";
    echo "❌ Resposta não é JSON válido:<br>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    echo "</div>";
}

// Teste 5: Dados por status
echo "<h3>5. Dados agrupados por status</h3>";
$sql_status = "SELECT
    status,
    COUNT(*) as total,
    SUM(valor_estimado) as valor_total
FROM qualificacoes
GROUP BY status
ORDER BY total DESC";

$stmt_status = $pdo->query($sql_status);
$dados_status = $stmt_status->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($dados_status);
echo "</pre>";

// Teste 6: Dados por modalidade
echo "<h3>6. Dados agrupados por modalidade</h3>";
$sql_modalidade = "SELECT
    modalidade,
    COUNT(*) as total,
    SUM(valor_estimado) as valor_total
FROM qualificacoes
GROUP BY modalidade
ORDER BY total DESC";

$stmt_modalidade = $pdo->query($sql_modalidade);
$dados_modalidade = $stmt_modalidade->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($dados_modalidade);
echo "</pre>";

// Teste 7: Ver charset/collation da conexão
echo "<h3>7. Charset e Collation da conexão</h3>";
$sql = "SHOW VARIABLES LIKE '%character%'";
$stmt = $pdo->query($sql);
$charset_vars = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
foreach ($charset_vars as $var) {
    echo $var['Variable_name'] . ": " . $var['Value'] . "\n";
}
echo "</pre>";
?>
