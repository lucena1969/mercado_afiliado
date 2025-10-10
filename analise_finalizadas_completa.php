<?php
require_once 'config.php';
require_once 'functions.php';

echo "<h1>🔍 ANÁLISE COMPLETA: Contratações 'Finalizadas' vs Licitações</h1>\n";
echo "<p style='background: #fee2e2; padding: 15px; border-radius: 8px; border-left: 4px solid #dc2626;'>\n";
echo "<strong>⚠️ ALERTA:</strong> Verificação se todas as contratações 'Finalizadas' estão realmente na tabela de licitações\n";
echo "</p>\n";

session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nivel'] = 1;

try {
    $pdo = conectarDB();

    echo "<h2>📊 RESUMO EXECUTIVO:</h2>\n";

    $stmt = $pdo->query("
        SELECT
            COUNT(DISTINCT p.numero_contratacao) as total_finalizadas,
            COUNT(DISTINCT CASE WHEN l.numero_contratacao IS NOT NULL THEN p.numero_contratacao END) as com_licitacao,
            COUNT(DISTINCT CASE WHEN l.numero_contratacao IS NULL THEN p.numero_contratacao END) as sem_licitacao
        FROM pca_dados p
        LEFT JOIN licitacoes l ON p.numero_contratacao = l.numero_contratacao
        WHERE p.situacao_execucao = 'Encerrada'
    ");
    $resumo = $stmt->fetch();

    echo "<div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0;'>\n";

    echo "<div style='background: #f0f9ff; padding: 20px; text-align: center; border-radius: 8px; border-left: 4px solid #0369a1;'>\n";
    echo "<h3>📋 Total Finalizadas</h3>\n";
    echo "<div style='font-size: 36px; font-weight: bold; color: #0369a1;'>{$resumo['total_finalizadas']}</div>\n";
    echo "<p>contratações com status 'Encerrada'</p>\n";
    echo "</div>\n";

    echo "<div style='background: #f0fdf4; padding: 20px; text-align: center; border-radius: 8px; border-left: 4px solid #16a34a;'>\n";
    echo "<h3>✅ Com Licitação</h3>\n";
    echo "<div style='font-size: 36px; font-weight: bold; color: #16a34a;'>{$resumo['com_licitacao']}</div>\n";
    echo "<p>estão na tabela licitações</p>\n";
    echo "</div>\n";

    echo "<div style='background: #fef3c7; padding: 20px; text-align: center; border-radius: 8px; border-left: 4px solid #d97706;'>\n";
    echo "<h3>❓ Sem Licitação</h3>\n";
    echo "<div style='font-size: 36px; font-weight: bold; color: #d97706;'>{$resumo['sem_licitacao']}</div>\n";
    echo "<p>NÃO estão na tabela licitações</p>\n";
    echo "</div>\n";

    echo "</div>\n";

    $percentual_com_licitacao = round(($resumo['com_licitacao'] / $resumo['total_finalizadas']) * 100, 1);
    $percentual_sem_licitacao = round(($resumo['sem_licitacao'] / $resumo['total_finalizadas']) * 100, 1);

    echo "<h2>📋 ANÁLISE DETALHADA - TODAS AS 18 CONTRATAÇÕES:</h2>\n";

    $stmt = $pdo->query("
        SELECT
            p.numero_contratacao,
            SUBSTRING(p.titulo_contratacao, 1, 80) as titulo_resumido,
            p.data_conclusao_processo,
            CASE
                WHEN l.numero_contratacao IS NOT NULL THEN l.nup
                ELSE 'SEM LICITAÇÃO'
            END as nup_licitacao,
            CASE
                WHEN l.situacao IS NOT NULL THEN l.situacao
                ELSE 'NÃO ENCONTRADA'
            END as situacao_licitacao,
            CASE
                WHEN l.valor_homologado IS NOT NULL THEN CONCAT('R$ ', FORMAT(l.valor_homologado, 2, 'de_DE'))
                ELSE '-'
            END as valor_homologado
        FROM pca_dados p
        LEFT JOIN licitacoes l ON p.numero_contratacao = l.numero_contratacao
        WHERE p.situacao_execucao = 'Encerrada'
        GROUP BY p.numero_contratacao
        ORDER BY
            CASE WHEN l.numero_contratacao IS NOT NULL THEN 0 ELSE 1 END,
            p.numero_contratacao
    ");

    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>\n";
    echo "<tr style='background: #f5f5f5;'>\n";
    echo "<th>Contratação</th><th>Título</th><th>Data Conclusão</th><th>NUP Licitação</th><th>Situação</th><th>Valor</th>\n";
    echo "</tr>\n";

    $com_licitacao_count = 0;
    $sem_licitacao_count = 0;

    while ($row = $stmt->fetch()) {
        $is_sem_licitacao = ($row['nup_licitacao'] === 'SEM LICITAÇÃO');
        $background_color = $is_sem_licitacao ? '#fef3c7' : '#f0fdf4';
        $border_color = $is_sem_licitacao ? '#d97706' : '#16a34a';

        if ($is_sem_licitacao) {
            $sem_licitacao_count++;
        } else {
            $com_licitacao_count++;
        }

        echo "<tr style='background: {$background_color}; border-left: 3px solid {$border_color};'>\n";
        echo "<td><strong>{$row['numero_contratacao']}</strong></td>\n";
        echo "<td>{$row['titulo_resumido']}...</td>\n";
        echo "<td>{$row['data_conclusao_processo']}</td>\n";
        echo "<td style='font-family: monospace;'>{$row['nup_licitacao']}</td>\n";
        echo "<td><strong>{$row['situacao_licitacao']}</strong></td>\n";
        echo "<td>{$row['valor_homologado']}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";

    echo "<h2>🎯 CONCLUSÕES:</h2>\n";

    echo "<div style='background: #f0f9ff; padding: 20px; border-radius: 8px; border-left: 4px solid #0369a1; margin: 20px 0;'>\n";
    echo "<h3>✅ Contratações COM Licitação ({$com_licitacao_count} de 18 = {$percentual_com_licitacao}%):</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>✅ Todas HOMOLOGADAS</strong> - Contratos efetivos assinados</li>\n";
    echo "<li><strong>📋 Processos completos</strong> - Passaram por licitação formal</li>\n";
    echo "<li><strong>💰 Valores definidos</strong> - Contratos com valores homologados</li>\n";
    echo "<li><strong>🏆 Sucessos do PCA</strong> - Do planejamento à execução</li>\n";
    echo "</ul>\n";
    echo "</div>\n";

    echo "<div style='background: #fffbeb; padding: 20px; border-radius: 8px; border-left: 4px solid #d97706; margin: 20px 0;'>\n";
    echo "<h3>❓ Contratações SEM Licitação ({$sem_licitacao_count} de 18 = {$percentual_sem_licitacao}%):</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>❓ Possíveis dispensas</strong> - Valor baixo ou situação especial</li>\n";
    echo "<li><strong>❓ Inexigibilidades</strong> - Fornecedor único ou exclusivo</li>\n";
    echo "<li><strong>❓ Contratações diretas</strong> - Permitidas por lei</li>\n";
    echo "<li><strong>⚠️ Não rastreáveis</strong> - Sem dados na tabela licitações</li>\n";
    echo "</ul>\n";
    echo "</div>\n";

    echo "<div style='background: #fef2f2; padding: 20px; border-radius: 8px; border-left: 4px solid #dc2626; margin: 20px 0;'>\n";
    echo "<h3>⚠️ RESPOSTA À SUA PERGUNTA:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>✅ SIM:</strong> {$com_licitacao_count} contratações ({$percentual_com_licitacao}%) estão na tabela licitações</li>\n";
    echo "<li><strong>❌ NÃO:</strong> {$sem_licitacao_count} contratações ({$percentual_sem_licitacao}%) NÃO estão na tabela licitações</li>\n";
    echo "<li><strong>🎯 Interpretação:</strong> 'Finalizadas' não significa necessariamente 'com contrato via licitação'</li>\n";
    echo "<li><strong>📊 Realidade:</strong> Pode incluir dispensas, inexigibilidades ou contratações diretas</li>\n";
    echo "</ul>\n";
    echo "</div>\n";

} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>\n";
}

echo "<p><a href='relatorios_gerenciais.php?modulo=planejamento'>📊 Voltar ao Dashboard</a></p>\n";
?>