<?php
// Teste direto da API do dashboard PCA
require_once 'config.php';
require_once 'functions.php';

session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nivel'] = 1;

echo "<h1>🧪 Teste Dashboard PCA</h1>\n";

try {
    $pdo = conectarDB();

    echo "<h3>1. Total de contratações:</h3>\n";
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT numero_contratacao) as total_contratacoes
        FROM pca_dados
        WHERE numero_contratacao IS NOT NULL AND numero_contratacao != ''
    ");
    $total_contratacoes = $stmt->fetch()['total_contratacoes'];
    echo "<p><strong>Total:</strong> $total_contratacoes contratações</p>\n";

    echo "<h3>2. Percentual com licitação:</h3>\n";
    $stmt = $pdo->query("
        SELECT
            COUNT(DISTINCT p.numero_contratacao) as total_contratacoes,
            COUNT(DISTINCT l.numero_contratacao) as contratacoes_com_licitacao
        FROM pca_dados p
        LEFT JOIN licitacoes l ON p.numero_contratacao = l.numero_contratacao
        WHERE p.numero_contratacao IS NOT NULL AND p.numero_contratacao != ''
    ");
    $licitacao_stats = $stmt->fetch();
    $percentual_licitados = $licitacao_stats['total_contratacoes'] > 0 ?
        round(($licitacao_stats['contratacoes_com_licitacao'] / $licitacao_stats['total_contratacoes']) * 100, 1) : 0;
    echo "<p><strong>Com licitação:</strong> {$licitacao_stats['contratacoes_com_licitacao']} de {$licitacao_stats['total_contratacoes']} ({$percentual_licitados}%)</p>\n";

    echo "<h3>3. Valor total planejado:</h3>\n";
    $stmt = $pdo->query("
        SELECT SUM(valor_total) as valor_total
        FROM pca_dados
        WHERE numero_contratacao IS NOT NULL AND numero_contratacao != ''
    ");
    $valor_total = $stmt->fetch()['valor_total'] ?: 0;
    $valor_total_formatado = 'R$ ' . number_format($valor_total, 2, ',', '.');
    echo "<p><strong>Valor total:</strong> $valor_total_formatado</p>\n";

    echo "<h3>4. Status das contratações:</h3>\n";
    $stmt = $pdo->query("
        SELECT
            situacao_execucao,
            COUNT(DISTINCT numero_contratacao) as quantidade
        FROM pca_dados
        WHERE numero_contratacao IS NOT NULL AND numero_contratacao != ''
        GROUP BY situacao_execucao
        ORDER BY quantidade DESC
    ");

    $stats_status = [
        'atraso' => 0,
        'preparacao' => 0,
        'encerradas' => 0
    ];

    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Situação</th><th>Contratações</th><th>Categoria</th></tr>\n";

    while ($row = $stmt->fetch()) {
        $situacao = $row['situacao_execucao'];
        $situacao_lower = strtolower($situacao);
        $qtd = $row['quantidade'];

        $categoria = 'Outros';
        if (strpos($situacao_lower, 'iniciado') !== false) {
            $stats_status['atraso'] += $qtd;
            $categoria = 'Em Atraso';
        } elseif (strpos($situacao_lower, 'prepar') !== false) {
            $stats_status['preparacao'] += $qtd;
            $categoria = 'Preparação';
        } elseif (strpos($situacao_lower, 'encerr') !== false) {
            $stats_status['encerradas'] += $qtd;
            $categoria = 'Encerradas';
        }

        echo "<tr><td>$situacao</td><td>$qtd</td><td>$categoria</td></tr>\n";
    }
    echo "</table>\n";

    echo "<h3>5. Resumo dos cards:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>Total Contratações:</strong> $total_contratacoes</li>\n";
    echo "<li><strong>% Licitados:</strong> {$percentual_licitados}%</li>\n";
    echo "<li><strong>Valor Total:</strong> $valor_total_formatado</li>\n";
    echo "<li><strong>Em Atraso:</strong> {$stats_status['atraso']}</li>\n";
    echo "<li><strong>Em Preparação:</strong> {$stats_status['preparacao']}</li>\n";
    echo "<li><strong>Encerradas:</strong> {$stats_status['encerradas']}</li>\n";
    echo "</ul>\n";

} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>\n";
}

echo "<p><a href='relatorios_gerenciais.php?modulo=planejamento'>📊 Ver Dashboard Real</a></p>\n";
?>