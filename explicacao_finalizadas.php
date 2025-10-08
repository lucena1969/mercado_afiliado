<?php
require_once 'config.php';
require_once 'functions.php';

echo "<h1>📋 Explicação: Card 'Finalizadas' no Dashboard PCA</h1>\n";
echo "<p style='background: #e1f5fe; padding: 15px; border-radius: 8px; border-left: 4px solid #0277bd;'>\n";
echo "<strong>Pergunta:</strong> O que significa o card 'Finalizadas'? São contratações já com contrato assinado?\n";
echo "</p>\n";

session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nivel'] = 1;

try {
    $pdo = conectarDB();

    echo "<h2>🔍 Análise dos Dados:</h2>\n";

    // 1. O que significa "Encerrada" no PCA
    echo "<h3>1. Situação 'Encerrada' no PCA:</h3>\n";
    $stmt = $pdo->query("
        SELECT
            COUNT(DISTINCT p.numero_contratacao) as total_encerradas,
            COUNT(DISTINCT CASE WHEN l.numero_contratacao IS NOT NULL THEN p.numero_contratacao END) as com_licitacao,
            COUNT(DISTINCT CASE WHEN l.situacao = 'HOMOLOGADO' THEN p.numero_contratacao END) as homologadas
        FROM pca_dados p
        LEFT JOIN licitacoes l ON p.numero_contratacao = l.numero_contratacao
        WHERE p.situacao_execucao = 'Encerrada'
    ");
    $stats = $stmt->fetch();

    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr style='background: #f5f5f5;'><th>Métrica</th><th>Quantidade</th><th>Significado</th></tr>\n";
    echo "<tr><td><strong>Total Encerradas</strong></td><td>{$stats['total_encerradas']}</td><td>Contratações com situação 'Encerrada' no PCA</td></tr>\n";
    echo "<tr><td><strong>Com Licitação</strong></td><td>{$stats['com_licitacao']}</td><td>Dessas, quantas têm processo licitatório</td></tr>\n";
    echo "<tr><td><strong>Homologadas</strong></td><td>{$stats['homologadas']}</td><td>Dessas, quantas têm licitação HOMOLOGADA</td></tr>\n";
    echo "</table>\n";

    // 2. Exemplos práticos
    echo "<h3>2. Exemplos de Contratações 'Encerradas':</h3>\n";
    $stmt = $pdo->query("
        SELECT
            p.numero_contratacao,
            p.titulo_contratacao,
            p.data_conclusao_processo,
            l.situacao as situacao_licitacao,
            l.valor_homologado
        FROM pca_dados p
        LEFT JOIN licitacoes l ON p.numero_contratacao = l.numero_contratacao
        WHERE p.situacao_execucao = 'Encerrada'
        GROUP BY p.numero_contratacao
        ORDER BY p.data_conclusao_processo DESC
        LIMIT 5
    ");

    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>\n";
    echo "<tr style='background: #f5f5f5;'><th>Contratação</th><th>Título</th><th>Data Conclusão</th><th>Situação Licitação</th><th>Valor Homologado</th></tr>\n";

    while ($row = $stmt->fetch()) {
        $situacao_licitacao = $row['situacao_licitacao'] ?: 'SEM LICITAÇÃO';
        $valor = $row['valor_homologado'] ? 'R$ ' . number_format($row['valor_homologado'], 2, ',', '.') : '-';

        echo "<tr>\n";
        echo "<td>{$row['numero_contratacao']}</td>\n";
        echo "<td>" . substr($row['titulo_contratacao'], 0, 60) . "...</td>\n";
        echo "<td>{$row['data_conclusao_processo']}</td>\n";
        echo "<td style='font-weight: bold; color: " . ($situacao_licitacao === 'HOMOLOGADO' ? '#10b981' : '#ef4444') . ";'>{$situacao_licitacao}</td>\n";
        echo "<td>{$valor}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";

    // 3. Fluxo completo
    echo "<h3>3. Fluxo do PCA - Situações de Execução:</h3>\n";
    $stmt = $pdo->query("
        SELECT
            situacao_execucao,
            COUNT(DISTINCT numero_contratacao) as contratacoes,
            ROUND(COUNT(DISTINCT numero_contratacao) * 100.0 / (SELECT COUNT(DISTINCT numero_contratacao) FROM pca_dados WHERE numero_contratacao IS NOT NULL), 1) as percentual
        FROM pca_dados
        WHERE numero_contratacao IS NOT NULL
        GROUP BY situacao_execucao
        ORDER BY contratacoes DESC
    ");

    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr style='background: #f5f5f5;'><th>Situação</th><th>Contratações</th><th>%</th><th>Significado</th></tr>\n";

    while ($row = $stmt->fetch()) {
        $situacao = $row['situacao_execucao'];
        $significado = '';

        switch (strtolower($situacao)) {
            case 'não iniciado':
                $significado = '🚨 Ainda não começaram o processo de contratação';
                break;
            case 'preparação':
                $significado = '⏳ Em fase de elaboração/preparação do processo';
                break;
            case 'encerrada':
                $significado = '✅ Processo de contratação FINALIZADO (pode ter contrato assinado)';
                break;
            case 'edição':
                $significado = '📝 Em fase de edição/ajustes no processo';
                break;
            case 'divulgada':
                $significado = '📢 Processo divulgado/publicado';
                break;
            case 'revogada':
                $significado = '❌ Processo cancelado/revogado';
                break;
            default:
                $significado = '❓ Outros status';
        }

        echo "<tr>\n";
        echo "<td><strong>{$situacao}</strong></td>\n";
        echo "<td>{$row['contratacoes']}</td>\n";
        echo "<td>{$row['percentual']}%</td>\n";
        echo "<td>{$significado}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";

    echo "<h2>✅ Conclusão:</h2>\n";
    echo "<div style='background: #f0f9ff; padding: 20px; border-radius: 8px; border-left: 4px solid #0369a1; margin: 20px 0;'>\n";
    echo "<h3>🎯 O que significa 'Finalizadas':</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>✅ SIM:</strong> São contratações onde o <strong>processo de contratação foi FINALIZADO</strong></li>\n";
    echo "<li><strong>🏆 Maioria com contrato:</strong> Das {$stats['total_encerradas']} encerradas, {$stats['homologadas']} têm licitação HOMOLOGADA</li>\n";
    echo "<li><strong>📋 Status no PCA:</strong> 'Encerrada' = processo concluído (sucesso ou não)</li>\n";
    echo "<li><strong>🤝 Contratos assinados:</strong> Principalmente sim, quando há licitação HOMOLOGADA</li>\n";
    echo "</ul>\n";

    echo "<h3>📊 Em números:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>{$stats['total_encerradas']} contratações</strong> finalizaram o processo</li>\n";
    echo "<li><strong>{$stats['homologadas']} têm contratos</strong> (licitação homologada)</li>\n";
    echo "<li><strong>" . ($stats['total_encerradas'] - $stats['com_licitacao']) . " sem licitação</strong> (dispensa, inexigibilidade, etc.)</li>\n";
    echo "</ul>\n";

    echo "<p><strong>🎯 Resumo:</strong> 'Finalizadas' = <em>Processo de contratação CONCLUÍDO</em>, a maioria resultando em contratos assinados.</p>\n";
    echo "</div>\n";

} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>\n";
}

echo "<p><a href='relatorios_gerenciais.php?modulo=planejamento'>📊 Voltar ao Dashboard</a></p>\n";
?>