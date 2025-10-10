<?php
// Teste simples da lógica do dashboard sem headers
require_once 'config.php';
require_once 'functions.php';

echo "<h1>🧪 Teste Simples Dashboard PCA</h1>\n";

session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nivel'] = 1;

try {
    $pdo = conectarDB();

    // 1. Total de contratações planejadas
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT numero_contratacao) as total_contratacoes
        FROM pca_dados
        WHERE numero_contratacao IS NOT NULL AND numero_contratacao != ''
    ");
    $total_contratacoes = $stmt->fetch()['total_contratacoes'];

    // 2. Percentual com licitação associada
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

    // 3. Valor total planejado - CORRIGIDO
    $stmt = $pdo->query("
        SELECT SUM(valor_total) as valor_total
        FROM pca_dados
        WHERE numero_contratacao IS NOT NULL AND numero_contratacao != ''
    ");
    $valor_total = $stmt->fetch()['valor_total'] ?: 0;
    $valor_total_formatado = 'R$ ' . number_format($valor_total, 2, ',', '.');

    // 4. Contadores por status - SIMPLIFICADO
    $stmt = $pdo->query("
        SELECT
            situacao_execucao,
            COUNT(DISTINCT numero_contratacao) as quantidade,
            SUM(valor_total) as valor_total
        FROM pca_dados
        WHERE numero_contratacao IS NOT NULL AND numero_contratacao != ''
        GROUP BY situacao_execucao
    ");

    $stats_status = [
        'atraso' => ['quantidade' => 0, 'valor' => 0],
        'preparacao' => ['quantidade' => 0, 'valor' => 0],
        'encerradas' => ['quantidade' => 0, 'valor' => 0]
    ];

    while ($row = $stmt->fetch()) {
        $situacao = strtolower($row['situacao_execucao']);
        $qtd = $row['quantidade'];
        $valor = floatval($row['valor_total'] ?: 0);

        if (strpos($situacao, 'iniciado') !== false) {
            $stats_status['atraso']['quantidade'] += $qtd;
            $stats_status['atraso']['valor'] += $valor;
        } elseif (strpos($situacao, 'prepar') !== false) {
            $stats_status['preparacao']['quantidade'] += $qtd;
            $stats_status['preparacao']['valor'] += $valor;
        } elseif (strpos($situacao, 'encerr') !== false) {
            $stats_status['encerradas']['quantidade'] += $qtd;
            $stats_status['encerradas']['valor'] += $valor;
        }
    }

    // 5. Contratações Aprovadas (por status_contratacao)
    $stmt = $pdo->query("
        SELECT
            COUNT(DISTINCT numero_contratacao) as quantidade,
            SUM(valor_total) as valor_total
        FROM pca_dados
        WHERE numero_contratacao IS NOT NULL AND numero_contratacao != ''
        AND status_contratacao = 'Aprovada'
    ");
    $aprovadas_stats = $stmt->fetch();
    $total_aprovadas = $aprovadas_stats['quantidade'] ?: 0;
    $valor_aprovadas = floatval($aprovadas_stats['valor_total'] ?: 0);

    $resultado = [
        'success' => true,
        'estatisticas' => [
            'total_contratacoes' => $total_contratacoes,
            'percentual_licitados' => $percentual_licitados,
            'valor_total_formatado' => $valor_total_formatado,
            'valor_total_raw' => $valor_total,
            // Dados expandidos com quantidade + valor
            'total_atraso' => $stats_status['atraso']['quantidade'],
            'valor_atraso_formatado' => 'R$ ' . number_format($stats_status['atraso']['valor'], 2, ',', '.'),
            'valor_atraso_raw' => $stats_status['atraso']['valor'],
            'total_preparacao' => $stats_status['preparacao']['quantidade'],
            'valor_preparacao_formatado' => 'R$ ' . number_format($stats_status['preparacao']['valor'], 2, ',', '.'),
            'valor_preparacao_raw' => $stats_status['preparacao']['valor'],
            'total_encerradas' => $stats_status['encerradas']['quantidade'],
            'valor_encerradas_formatado' => 'R$ ' . number_format($stats_status['encerradas']['valor'], 2, ',', '.'),
            'valor_encerradas_raw' => $stats_status['encerradas']['valor'],
            // Contratações Aprovadas
            'total_aprovadas' => $total_aprovadas,
            'valor_aprovadas_formatado' => 'R$ ' . number_format($valor_aprovadas, 2, ',', '.'),
            'valor_aprovadas_raw' => $valor_aprovadas
        ]
    ];

    echo "<h3>✅ Resultado Final (formato JSON da API):</h3>\n";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>\n";
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "</pre>\n";

    echo "<h3>📊 Cards Combinados do Dashboard:</h3>\n";
    $stats = $resultado['estatisticas'];
    echo "<div style='display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin: 20px 0;'>\n";

    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #1e3c72;'>\n";
    echo "<h4>📋 Total Planejado</h4>\n";
    echo "<div style='font-size: 36px; color: #1e3c72; font-weight: bold;'>" . $stats['total_contratacoes'] . "</div>\n";
    echo "<p style='color: #7f8c8d; margin: 5px 0; font-size: 14px;'>contratações</p>\n";
    echo "<div style='font-size: 20px; color: #10b981; font-weight: 600;'>" . $stats['valor_total_formatado'] . "</div>\n";
    echo "<p style='color: #7f8c8d; margin: 0; font-size: 12px;'>valor total planejado</p>\n";
    echo "</div>\n";

    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #059669;'>\n";
    echo "<h4>📈 % Com Licitação</h4>\n";
    echo "<div style='font-size: 36px; color: #059669; font-weight: bold;'>" . $stats['percentual_licitados'] . "%</div>\n";
    echo "<p style='color: #7f8c8d; margin: 0; font-size: 14px;'>contratações com processo licitatório</p>\n";
    echo "</div>\n";

    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #0369a1;'>\n";
    echo "<h4>☑️ Aprovadas</h4>\n";
    echo "<div style='font-size: 36px; color: #0369a1; font-weight: bold;'>" . $stats['total_aprovadas'] . "</div>\n";
    echo "<p style='color: #7f8c8d; margin: 5px 0; font-size: 14px;'>contratações aprovadas</p>\n";
    echo "<div style='font-size: 18px; color: #0284c7; font-weight: 600;'>" . $stats['valor_aprovadas_formatado'] . "</div>\n";
    echo "<p style='color: #7f8c8d; margin: 0; font-size: 12px;'>valor aprovado</p>\n";
    echo "</div>\n";

    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #dc2626;'>\n";
    echo "<h4>🚨 Em Atraso</h4>\n";
    echo "<div style='font-size: 36px; color: #dc2626; font-weight: bold;'>" . $stats['total_atraso'] . "</div>\n";
    echo "<p style='color: #7f8c8d; margin: 5px 0; font-size: 14px;'>contratações não iniciadas</p>\n";
    echo "<div style='font-size: 18px; color: #ef4444; font-weight: 600;'>" . $stats['valor_atraso_formatado'] . "</div>\n";
    echo "<p style='color: #7f8c8d; margin: 0; font-size: 12px;'>valor em atraso</p>\n";
    echo "</div>\n";

    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #d97706;'>\n";
    echo "<h4>⏳ Em Preparação</h4>\n";
    echo "<div style='font-size: 36px; color: #d97706; font-weight: bold;'>" . $stats['total_preparacao'] . "</div>\n";
    echo "<p style='color: #7f8c8d; margin: 5px 0; font-size: 14px;'>contratações em andamento</p>\n";
    echo "<div style='font-size: 18px; color: #f59e0b; font-weight: 600;'>" . $stats['valor_preparacao_formatado'] . "</div>\n";
    echo "<p style='color: #7f8c8d; margin: 0; font-size: 12px;'>valor em preparação</p>\n";
    echo "</div>\n";

    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #065f46;'>\n";
    echo "<h4>✅ Finalizadas</h4>\n";
    echo "<div style='font-size: 36px; color: #065f46; font-weight: bold;'>" . $stats['total_encerradas'] . "</div>\n";
    echo "<p style='color: #7f8c8d; margin: 5px 0; font-size: 14px;'>contratações concluídas</p>\n";
    echo "<div style='font-size: 18px; color: #10b981; font-weight: 600;'>" . $stats['valor_encerradas_formatado'] . "</div>\n";
    echo "<p style='color: #7f8c8d; margin: 0; font-size: 12px;'>valor finalizado</p>\n";
    echo "</div>\n";

    echo "</div>\n";

} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>\n";
}

echo "<p><a href='relatorios_gerenciais.php?modulo=planejamento'>📊 Ver Dashboard Real</a></p>\n";
?>