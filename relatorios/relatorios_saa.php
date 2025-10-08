<?php
require_once '../config.php';
require_once '../functions.php';

verificarLogin();

$pdo = conectarDB();

// Parâmetros
$tipo = $_GET['tipo'] ?? '';
$ano_selecionado = intval($_GET['ano'] ?? 2025);
$coordenacao_filtro = $_GET['coordenacao'] ?? '';
$formato = $_GET['formato'] ?? 'html';
$incluir_graficos = isset($_GET['incluir_graficos']);

// Anos disponíveis
$anos_disponiveis = [2026, 2025, 2024, 2023, 2022];

// Verificar se ano é válido
if (!in_array($ano_selecionado, $anos_disponiveis)) {
    $ano_selecionado = 2025;
}

// Coordenações SAA - SEGUNDO NÍVEL (8 coordenações)
// Agrupamento correto: SAA.CGDI agrupa ARQUIVO, DCCMS, EDITORA, CODINF
//                      SAA.CGOF agrupa COMAP
//                      SAA.COGEP agrupa CODEP, COASS
//                      SAA.CGCON agrupa DIFSEP
$coordenacoes_saa = [
    'SAA.CGDI',      // Inclui: ARQUIVO, DCCMS, EDITORA, CODINF
    'SAA.CGINFRA',   // Coordenação completa
    'SAA.CGSA',      // Coordenação completa
    'SAA.CGOF',      // Inclui: COMAP
    'SAA.COGEP',     // Inclui: CODEP, COASS
    'SAA.CGCON',     // Inclui: DIFSEP
    'SAA.COGAD',     // Coordenação completa
    'SAA.CGENG'      // Coordenação completa
];

// Buscar IDs das importações do ano selecionado
$importacoes_ano_sql = "SELECT id FROM pca_importacoes WHERE ano_pca = ?";
$importacoes_stmt = $pdo->prepare($importacoes_ano_sql);
$importacoes_stmt->execute([$ano_selecionado]);
$importacoes_ids = [];
while ($row = $importacoes_stmt->fetch()) {
    $importacoes_ids[] = $row['id'];
}

// Construir filtro por ano
if (!empty($importacoes_ids)) {
    $where_ano = "p.importacao_id IN (" . implode(',', $importacoes_ids) . ")";
} else {
    $where_ano = "p.importacao_id = -1"; // Força retorno vazio se não há importações
}

// Gerar relatório baseado no tipo
switch ($tipo) {
    case 'dfds_abertos':
        gerarRelatorioDFDsAbertos($pdo, $where_ano, $coordenacao_filtro, $formato, $incluir_graficos, $ano_selecionado, $coordenacoes_saa);
        break;

    case 'dfds_nao_iniciados':
        gerarRelatorioDFDsNaoIniciados($pdo, $where_ano, $coordenacao_filtro, $formato, $incluir_graficos, $ano_selecionado, $coordenacoes_saa);
        break;

    case 'dfds_em_andamento':
        gerarRelatorioDFDsEmAndamento($pdo, $where_ano, $coordenacao_filtro, $formato, $incluir_graficos, $ano_selecionado, $coordenacoes_saa);
        break;

    default:
        die('Tipo de relatório inválido');
}

// ============================================================
// FUNÇÃO: Relatório de DFDs Abertos por Coordenação SAA
// ============================================================
function gerarRelatorioDFDsAbertos($pdo, $where_ano, $coordenacao_filtro, $formato, $incluir_graficos, $ano, $coordenacoes_saa) {
    // Construir WHERE para coordenações SAA
    $where_coordenacoes = [];
    foreach ($coordenacoes_saa as $coord) {
        $where_coordenacoes[] = "p.area_requisitante LIKE '" . $coord . "%'";
    }
    $where_saa = "(" . implode(" OR ", $where_coordenacoes) . ")";

    // Adicionar filtro de coordenação específica se selecionada
    $where_filtro = $where_ano . " AND " . $where_saa;
    if (!empty($coordenacao_filtro)) {
        $where_filtro .= " AND p.area_requisitante LIKE '" . $coordenacao_filtro . "%'";
    }

    // DFDs Abertos = Todos exceto Concluído, Revogado, Anulado
    $where_filtro .= " AND (p.situacao_execucao IS NULL OR p.situacao_execucao = ''
                        OR p.situacao_execucao NOT IN ('Concluído', 'Revogado', 'Anulado', 'Cancelado'))";

    // Query agrupada por DFD
    $sql = "
        SELECT
            p.numero_dfd,
            MAX(p.titulo_contratacao) as titulo_contratacao,
            MAX(p.categoria_contratacao) as categoria_contratacao,
            MAX(p.area_requisitante) as area_requisitante,
            SUM(p.valor_total) as valor_total,
            MAX(p.situacao_execucao) as situacao_execucao,
            MAX(p.data_inicio_processo) as data_inicio_processo,
            MAX(p.data_conclusao_processo) as data_conclusao_processo,
            DATEDIFF(CURDATE(), MAX(p.data_inicio_processo)) as dias_aberto,
            CASE
                WHEN MAX(p.data_conclusao_processo) < CURDATE() THEN 'Atrasado'
                WHEN MAX(p.data_conclusao_processo) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Vencendo'
                ELSE 'No Prazo'
            END as status_prazo,
            COUNT(DISTINCT l.id) as tem_licitacao
        FROM pca_dados p
        LEFT JOIN licitacoes l ON l.pca_dados_id = p.id
        WHERE $where_filtro
            AND p.numero_dfd IS NOT NULL
            AND p.numero_dfd != ''
        GROUP BY p.numero_dfd
        ORDER BY dias_aberto DESC, valor_total DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $dados = $stmt->fetchAll();

    // Agrupar por coordenação (segundo nível SAA)
    $dados_por_coordenacao = agruparPorCoordenacaoSAA($dados);

    if ($formato === 'html') {
        gerarHTMLDFDsAbertos($dados_por_coordenacao, $incluir_graficos, $ano, $coordenacao_filtro);
    } elseif ($formato === 'csv') {
        gerarCSVDFDsAbertos($dados_por_coordenacao, $ano);
    }
}

// ============================================================
// FUNÇÃO: Relatório de DFDs Não Iniciados por Coordenação SAA
// ============================================================
function gerarRelatorioDFDsNaoIniciados($pdo, $where_ano, $coordenacao_filtro, $formato, $incluir_graficos, $ano, $coordenacoes_saa) {
    // Construir WHERE para coordenações SAA
    $where_coordenacoes = [];
    foreach ($coordenacoes_saa as $coord) {
        $where_coordenacoes[] = "p.area_requisitante LIKE '" . $coord . "%'";
    }
    $where_saa = "(" . implode(" OR ", $where_coordenacoes) . ")";

    // Adicionar filtro de coordenação específica
    $where_filtro = $where_ano . " AND " . $where_saa;
    if (!empty($coordenacao_filtro)) {
        $where_filtro .= " AND p.area_requisitante LIKE '" . $coordenacao_filtro . "%'";
    }

    // DFDs Não Iniciados = situacao_execucao vazia, nula ou 'Não iniciado'
    $where_filtro .= " AND (p.situacao_execucao IS NULL OR p.situacao_execucao = '' OR p.situacao_execucao = 'Não iniciado')";

    $sql = "
        SELECT
            p.numero_dfd,
            MAX(p.titulo_contratacao) as titulo_contratacao,
            MAX(p.categoria_contratacao) as categoria_contratacao,
            MAX(p.area_requisitante) as area_requisitante,
            SUM(p.valor_total) as valor_total,
            MAX(p.data_inicio_processo) as data_inicio_processo,
            MAX(p.data_conclusao_processo) as data_conclusao_processo,
            DATEDIFF(CURDATE(), MAX(p.data_inicio_processo)) as dias_sem_inicio,
            DATEDIFF(MAX(p.data_conclusao_processo), CURDATE()) as dias_ate_prazo,
            COUNT(DISTINCT l.id) as tem_licitacao
        FROM pca_dados p
        LEFT JOIN licitacoes l ON l.pca_dados_id = p.id
        WHERE $where_filtro
            AND p.numero_dfd IS NOT NULL
            AND p.numero_dfd != ''
        GROUP BY p.numero_dfd
        ORDER BY dias_sem_inicio DESC, valor_total DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $dados = $stmt->fetchAll();

    // Agrupar por coordenação
    $dados_por_coordenacao = agruparPorCoordenacaoSAA($dados);

    if ($formato === 'html') {
        gerarHTMLDFDsNaoIniciados($dados_por_coordenacao, $incluir_graficos, $ano, $coordenacao_filtro);
    } elseif ($formato === 'csv') {
        gerarCSVDFDsNaoIniciados($dados_por_coordenacao, $ano);
    }
}

// ============================================================
// FUNÇÃO: Relatório de DFDs Em Andamento por Coordenação SAA
// ============================================================
function gerarRelatorioDFDsEmAndamento($pdo, $where_ano, $coordenacao_filtro, $formato, $incluir_graficos, $ano, $coordenacoes_saa) {
    // Construir WHERE para coordenações SAA
    $where_coordenacoes = [];
    foreach ($coordenacoes_saa as $coord) {
        $where_coordenacoes[] = "p.area_requisitante LIKE '" . $coord . "%'";
    }
    $where_saa = "(" . implode(" OR ", $where_coordenacoes) . ")";

    // Adicionar filtro de coordenação específica
    $where_filtro = $where_ano . " AND " . $where_saa;
    if (!empty($coordenacao_filtro)) {
        $where_filtro .= " AND p.area_requisitante LIKE '" . $coordenacao_filtro . "%'";
    }

    // DFDs Em Andamento = situacao_execucao contém 'andamento', 'preparação', 'edição', 'execução'
    $where_filtro .= " AND (p.situacao_execucao LIKE '%andamento%'
                        OR p.situacao_execucao LIKE '%preparação%'
                        OR p.situacao_execucao LIKE '%preparacao%'
                        OR p.situacao_execucao LIKE '%edição%'
                        OR p.situacao_execucao LIKE '%edicao%'
                        OR p.situacao_execucao LIKE '%execução%'
                        OR p.situacao_execucao LIKE '%execucao%')";

    $sql = "
        SELECT
            p.numero_dfd,
            MAX(p.titulo_contratacao) as titulo_contratacao,
            MAX(p.categoria_contratacao) as categoria_contratacao,
            MAX(p.area_requisitante) as area_requisitante,
            SUM(p.valor_total) as valor_total,
            MAX(p.situacao_execucao) as situacao_execucao,
            MAX(p.data_inicio_processo) as data_inicio_processo,
            MAX(p.data_conclusao_processo) as data_conclusao_processo,
            DATEDIFF(CURDATE(), MAX(p.data_inicio_processo)) as dias_em_andamento,
            DATEDIFF(MAX(p.data_conclusao_processo), CURDATE()) as dias_ate_conclusao,
            CASE
                WHEN MAX(p.data_conclusao_processo) < CURDATE() THEN 'Atrasado'
                WHEN MAX(p.data_conclusao_processo) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Atenção'
                ELSE 'No Prazo'
            END as alerta_prazo,
            COUNT(DISTINCT l.id) as tem_licitacao
        FROM pca_dados p
        LEFT JOIN licitacoes l ON l.pca_dados_id = p.id
        WHERE $where_filtro
            AND p.numero_dfd IS NOT NULL
            AND p.numero_dfd != ''
        GROUP BY p.numero_dfd
        ORDER BY dias_em_andamento DESC, valor_total DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $dados = $stmt->fetchAll();

    // Agrupar por coordenação
    $dados_por_coordenacao = agruparPorCoordenacaoSAA($dados);

    if ($formato === 'html') {
        gerarHTMLDFDsEmAndamento($dados_por_coordenacao, $incluir_graficos, $ano, $coordenacao_filtro);
    } elseif ($formato === 'csv') {
        gerarCSVDFDsEmAndamento($dados_por_coordenacao, $ano);
    }
}

// ============================================================
// FUNÇÃO AUXILIAR: Agrupar dados por Coordenação SAA (SEGUNDO NÍVEL)
// ============================================================
function agruparPorCoordenacaoSAA($dados) {
    $agrupado = [];

    foreach ($dados as $row) {
        $area = $row['area_requisitante'];

        // Extrair coordenação APENAS NO SEGUNDO NÍVEL após SAA
        $partes = explode('.', $area);
        if (count($partes) >= 2 && $partes[0] === 'SAA') {
            // Sempre pegar apenas SAA.SEGUNDO_NIVEL (ignorar terceiro nível)
            $coordenacao = $partes[0] . '.' . $partes[1];

            if (!isset($agrupado[$coordenacao])) {
                $agrupado[$coordenacao] = [
                    'coordenacao' => $coordenacao,
                    'dfds' => [],
                    'total_dfds' => 0,
                    'valor_total' => 0,
                    'com_licitacao' => 0
                ];
            }

            $agrupado[$coordenacao]['dfds'][] = $row;
            $agrupado[$coordenacao]['total_dfds']++;
            $agrupado[$coordenacao]['valor_total'] += floatval($row['valor_total']);
            if ($row['tem_licitacao'] > 0) {
                $agrupado[$coordenacao]['com_licitacao']++;
            }
        }
    }

    // Ordenar por total de DFDs (decrescente)
    uasort($agrupado, function($a, $b) {
        return $b['total_dfds'] - $a['total_dfds'];
    });

    return $agrupado;
}

// ============================================================
// FUNÇÃO HTML: DFDs Abertos
// ============================================================
function gerarHTMLDFDsAbertos($dados_por_coordenacao, $incluir_graficos, $ano, $coordenacao_filtro) {
    $total_dfds = array_sum(array_column($dados_por_coordenacao, 'total_dfds'));
    $valor_total = array_sum(array_column($dados_por_coordenacao, 'valor_total'));
    $total_com_licitacao = array_sum(array_column($dados_por_coordenacao, 'com_licitacao'));

    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Relatório DFDs Abertos - Coordenações SAA - <?php echo $ano; ?></title>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #f8f9fa; }
            .container { max-width: 1400px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
            h1 { color: #2c3e50; text-align: center; margin-bottom: 10px; font-size: 28px; font-weight: 700; }
            .ano-badge { text-align: center; margin-bottom: 30px; }
            .ano-badge span { display: inline-block; background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 8px 20px; border-radius: 20px; font-size: 16px; font-weight: 700; }
            .subtitle { text-align: center; color: #7f8c8d; margin-bottom: 40px; font-size: 16px; }
            .info { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: white; padding: 25px; border-radius: 12px; margin-bottom: 40px; }
            .info p { margin: 8px 0; font-weight: 500; }

            .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px; margin-bottom: 40px; }
            .summary-card { background: white; padding: 25px; border-radius: 12px; text-align: center; border-left: 5px solid #3498db; box-shadow: 0 2px 15px rgba(0,0,0,0.08); }
            .summary-card h3 { margin: 0 0 15px 0; color: #2c3e50; font-size: 16px; font-weight: 600; }
            .summary-card .value { font-size: 32px; font-weight: 800; color: #3498db; margin-bottom: 5px; }

            .coordenacao-section { margin: 40px 0; }
            .coordenacao-header { background: linear-gradient(135deg, #34495e, #2c3e50); color: white; padding: 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; }
            .coordenacao-header h3 { margin: 0; font-size: 20px; }
            .coordenacao-stats { display: flex; gap: 30px; font-size: 14px; }
            .coordenacao-stats span { font-weight: 600; }

            table { width: 100%; border-collapse: collapse; background: white; }
            th { background: #ecf0f1; color: #2c3e50; padding: 12px; text-align: left; font-weight: 600; font-size: 13px; }
            td { padding: 12px; border-bottom: 1px solid #ecf0f1; font-size: 13px; }
            tr:hover { background: #f8f9fa; }

            .badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; display: inline-block; }
            .badge-atrasado { background: #e74c3c; color: white; }
            .badge-vencendo { background: #f39c12; color: white; }
            .badge-prazo { background: #27ae60; color: white; }
            .badge-licitacao { background: #3498db; color: white; }

            @media print { .no-print { display: none; } }
        </style>
    </head>
    <body>
        <div class="container">
            <h1><i data-lucide="folder-open"></i> DFDs Abertos - Coordenações SAA</h1>
            <div class="ano-badge"><span>📅 ANO PCA: <?php echo $ano; ?></span></div>
            <div class="subtitle">Relatório de DFDs em execução por coordenação</div>

            <div class="info">
                <p><strong><i data-lucide="calendar"></i> Ano PCA:</strong> <?php echo $ano; ?></p>
                <p><strong><i data-lucide="building"></i> Coordenações Analisadas:</strong> <?php echo count($dados_por_coordenacao); ?> coordenações SAA</p>
                <?php if (!empty($coordenacao_filtro)): ?>
                <p><strong><i data-lucide="filter"></i> Filtro Aplicado:</strong> <?php echo htmlspecialchars($coordenacao_filtro); ?></p>
                <?php endif; ?>
                <p><strong><i data-lucide="clock"></i> Gerado em:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
            </div>

            <div class="summary">
                <div class="summary-card">
                    <h3>Total de DFDs Abertos</h3>
                    <div class="value"><?php echo number_format($total_dfds, 0, ',', '.'); ?></div>
                </div>
                <div class="summary-card">
                    <h3>Valor Total</h3>
                    <div class="value">R$ <?php echo number_format($valor_total, 2, ',', '.'); ?></div>
                </div>
                <div class="summary-card">
                    <h3>Com Licitação Vinculada</h3>
                    <div class="value"><?php echo number_format($total_com_licitacao, 0, ',', '.'); ?></div>
                </div>
                <div class="summary-card">
                    <h3>Taxa de Vinculação</h3>
                    <div class="value"><?php echo $total_dfds > 0 ? number_format(($total_com_licitacao / $total_dfds) * 100, 1) : '0'; ?>%</div>
                </div>
            </div>

            <?php foreach ($dados_por_coordenacao as $coord_data): ?>
            <div class="coordenacao-section">
                <div class="coordenacao-header">
                    <h3><i data-lucide="building-2"></i> <?php echo htmlspecialchars($coord_data['coordenacao']); ?></h3>
                    <div class="coordenacao-stats">
                        <span><?php echo $coord_data['total_dfds']; ?> DFDs</span>
                        <span>R$ <?php echo number_format($coord_data['valor_total'], 2, ',', '.'); ?></span>
                        <span><?php echo $coord_data['com_licitacao']; ?> c/ Licitação</span>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>DFD</th>
                            <th>Título da Contratação</th>
                            <th>Categoria</th>
                            <th style="text-align: center;">Situação</th>
                            <th style="text-align: center;">Status Prazo</th>
                            <th style="text-align: center;">Dias Aberto</th>
                            <th style="text-align: right;">Valor</th>
                            <th style="text-align: center;">Licitação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coord_data['dfds'] as $dfd): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($dfd['numero_dfd']); ?></strong></td>
                            <td><?php echo htmlspecialchars(substr($dfd['titulo_contratacao'], 0, 80)) . (strlen($dfd['titulo_contratacao']) > 80 ? '...' : ''); ?></td>
                            <td><?php echo htmlspecialchars($dfd['categoria_contratacao']); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($dfd['situacao_execucao'] ?: 'Não iniciado'); ?></td>
                            <td style="text-align: center;">
                                <?php
                                $badge_class = 'badge-prazo';
                                if ($dfd['status_prazo'] === 'Atrasado') $badge_class = 'badge-atrasado';
                                elseif ($dfd['status_prazo'] === 'Vencendo') $badge_class = 'badge-vencendo';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $dfd['status_prazo']; ?></span>
                            </td>
                            <td style="text-align: center;"><?php echo $dfd['dias_aberto']; ?> dias</td>
                            <td style="text-align: right;">R$ <?php echo number_format($dfd['valor_total'], 2, ',', '.'); ?></td>
                            <td style="text-align: center;">
                                <?php if ($dfd['tem_licitacao'] > 0): ?>
                                    <span class="badge badge-licitacao">SIM</span>
                                <?php else: ?>
                                    <span style="color: #95a5a6;">NÃO</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>

            <div class="no-print" style="text-align: center; margin-top: 40px;">
                <button onclick="window.print()" style="padding: 12px 30px; background: #3498db; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px;">
                    <i data-lucide="printer"></i> Imprimir Relatório
                </button>
            </div>
        </div>

        <script>
            lucide.createIcons();
        </script>
    </body>
    </html>
    <?php

    registrarLog('GERAR_RELATORIO', "Gerou relatório de DFDs abertos SAA - Ano $ano");
    exit;
}

// ============================================================
// FUNÇÃO HTML: DFDs Não Iniciados
// ============================================================
function gerarHTMLDFDsNaoIniciados($dados_por_coordenacao, $incluir_graficos, $ano, $coordenacao_filtro) {
    $total_dfds = array_sum(array_column($dados_por_coordenacao, 'total_dfds'));
    $valor_total = array_sum(array_column($dados_por_coordenacao, 'valor_total'));

    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Relatório DFDs Não Iniciados - SAA - <?php echo $ano; ?></title>
        <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #f8f9fa; }
            .container { max-width: 1400px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
            h1 { color: #2c3e50; text-align: center; margin-bottom: 10px; font-size: 28px; font-weight: 700; }
            .ano-badge { text-align: center; margin-bottom: 30px; }
            .ano-badge span { display: inline-block; background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; padding: 8px 20px; border-radius: 20px; font-size: 16px; font-weight: 700; }
            .subtitle { text-align: center; color: #7f8c8d; margin-bottom: 40px; font-size: 16px; }
            .info { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 25px; border-radius: 12px; margin-bottom: 40px; }
            .info p { margin: 8px 0; font-weight: 500; }

            .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px; margin-bottom: 40px; }
            .summary-card { background: white; padding: 25px; border-radius: 12px; text-align: center; border-left: 5px solid #e74c3c; box-shadow: 0 2px 15px rgba(0,0,0,0.08); }
            .summary-card h3 { margin: 0 0 15px 0; color: #2c3e50; font-size: 16px; font-weight: 600; }
            .summary-card .value { font-size: 32px; font-weight: 800; color: #e74c3c; margin-bottom: 5px; }

            .alert-box { background: #fff3cd; border-left: 5px solid #f39c12; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
            .alert-box h4 { margin: 0 0 10px 0; color: #856404; }
            .alert-box p { margin: 5px 0; color: #856404; }

            .coordenacao-section { margin: 40px 0; }
            .coordenacao-header { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; padding: 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; }
            .coordenacao-header h3 { margin: 0; font-size: 20px; }
            .coordenacao-stats { display: flex; gap: 30px; font-size: 14px; }
            .coordenacao-stats span { font-weight: 600; }

            table { width: 100%; border-collapse: collapse; background: white; }
            th { background: #ecf0f1; color: #2c3e50; padding: 12px; text-align: left; font-weight: 600; font-size: 13px; }
            td { padding: 12px; border-bottom: 1px solid #ecf0f1; font-size: 13px; }
            tr:hover { background: #f8f9fa; }
            tr.urgente { background: #fee; }

            .badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; display: inline-block; }
            .badge-critico { background: #e74c3c; color: white; }
            .badge-alerta { background: #f39c12; color: white; }
            .badge-normal { background: #95a5a6; color: white; }

            @media print { .no-print { display: none; } }
        </style>
    </head>
    <body>
        <div class="container">
            <h1><i data-lucide="alert-circle"></i> DFDs Não Iniciados - Coordenações SAA</h1>
            <div class="ano-badge"><span>📅 ANO PCA: <?php echo $ano; ?></span></div>
            <div class="subtitle">Contratações que ainda não iniciaram execução</div>

            <div class="info">
                <p><strong><i data-lucide="calendar"></i> Ano PCA:</strong> <?php echo $ano; ?></p>
                <p><strong><i data-lucide="building"></i> Coordenações Analisadas:</strong> <?php echo count($dados_por_coordenacao); ?> coordenações SAA</p>
                <?php if (!empty($coordenacao_filtro)): ?>
                <p><strong><i data-lucide="filter"></i> Filtro Aplicado:</strong> <?php echo htmlspecialchars($coordenacao_filtro); ?></p>
                <?php endif; ?>
                <p><strong><i data-lucide="clock"></i> Gerado em:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
            </div>

            <?php
            // Calcular DFDs críticos (mais de 60 dias sem início)
            $dfds_criticos = 0;
            foreach ($dados_por_coordenacao as $coord) {
                foreach ($coord['dfds'] as $dfd) {
                    if ($dfd['dias_sem_inicio'] > 60) $dfds_criticos++;
                }
            }
            if ($dfds_criticos > 0): ?>
            <div class="alert-box">
                <h4><i data-lucide="alert-triangle"></i> Atenção: DFDs Críticos Identificados</h4>
                <p><strong><?php echo $dfds_criticos; ?> DFDs</strong> estão há mais de 60 dias sem início de execução e requerem atenção imediata.</p>
            </div>
            <?php endif; ?>

            <div class="summary">
                <div class="summary-card">
                    <h3>Total Não Iniciados</h3>
                    <div class="value"><?php echo number_format($total_dfds, 0, ',', '.'); ?></div>
                </div>
                <div class="summary-card">
                    <h3>Valor Total</h3>
                    <div class="value">R$ <?php echo number_format($valor_total, 2, ',', '.'); ?></div>
                </div>
                <div class="summary-card">
                    <h3>Situação Crítica</h3>
                    <div class="value"><?php echo $dfds_criticos; ?></div>
                </div>
                <div class="summary-card">
                    <h3>Valor Médio</h3>
                    <div class="value">R$ <?php echo $total_dfds > 0 ? number_format($valor_total / $total_dfds, 2, ',', '.') : '0,00'; ?></div>
                </div>
            </div>

            <?php foreach ($dados_por_coordenacao as $coord_data): ?>
            <div class="coordenacao-section">
                <div class="coordenacao-header">
                    <h3><i data-lucide="building-2"></i> <?php echo htmlspecialchars($coord_data['coordenacao']); ?></h3>
                    <div class="coordenacao-stats">
                        <span><?php echo $coord_data['total_dfds']; ?> DFDs Não Iniciados</span>
                        <span>R$ <?php echo number_format($coord_data['valor_total'], 2, ',', '.'); ?></span>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>DFD</th>
                            <th>Título da Contratação</th>
                            <th>Categoria</th>
                            <th style="text-align: center;">Data Início</th>
                            <th style="text-align: center;">Data Conclusão</th>
                            <th style="text-align: center;">Dias sem Início</th>
                            <th style="text-align: center;">Prazo Restante</th>
                            <th style="text-align: right;">Valor</th>
                            <th style="text-align: center;">Criticidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coord_data['dfds'] as $dfd):
                            $urgente = $dfd['dias_sem_inicio'] > 60 || $dfd['dias_ate_prazo'] < 30;
                        ?>
                        <tr class="<?php echo $urgente ? 'urgente' : ''; ?>">
                            <td><strong><?php echo htmlspecialchars($dfd['numero_dfd']); ?></strong></td>
                            <td><?php echo htmlspecialchars(substr($dfd['titulo_contratacao'], 0, 60)) . (strlen($dfd['titulo_contratacao']) > 60 ? '...' : ''); ?></td>
                            <td><?php echo htmlspecialchars($dfd['categoria_contratacao']); ?></td>
                            <td style="text-align: center;"><?php echo $dfd['data_inicio_processo'] ? date('d/m/Y', strtotime($dfd['data_inicio_processo'])) : '-'; ?></td>
                            <td style="text-align: center;"><?php echo $dfd['data_conclusao_processo'] ? date('d/m/Y', strtotime($dfd['data_conclusao_processo'])) : '-'; ?></td>
                            <td style="text-align: center; font-weight: bold; color: <?php echo $dfd['dias_sem_inicio'] > 60 ? '#e74c3c' : '#2c3e50'; ?>;">
                                <?php echo $dfd['dias_sem_inicio']; ?> dias
                            </td>
                            <td style="text-align: center; color: <?php echo $dfd['dias_ate_prazo'] < 30 ? '#e74c3c' : '#27ae60'; ?>;">
                                <?php echo $dfd['dias_ate_prazo'] >= 0 ? $dfd['dias_ate_prazo'] . ' dias' : 'VENCIDO'; ?>
                            </td>
                            <td style="text-align: right;">R$ <?php echo number_format($dfd['valor_total'], 2, ',', '.'); ?></td>
                            <td style="text-align: center;">
                                <?php
                                if ($dfd['dias_sem_inicio'] > 60 || $dfd['dias_ate_prazo'] < 30) {
                                    echo '<span class="badge badge-critico">CRÍTICO</span>';
                                } elseif ($dfd['dias_sem_inicio'] > 30 || $dfd['dias_ate_prazo'] < 60) {
                                    echo '<span class="badge badge-alerta">ALERTA</span>';
                                } else {
                                    echo '<span class="badge badge-normal">NORMAL</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>

            <div class="no-print" style="text-align: center; margin-top: 40px;">
                <button onclick="window.print()" style="padding: 12px 30px; background: #e74c3c; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px;">
                    <i data-lucide="printer"></i> Imprimir Relatório
                </button>
            </div>
        </div>

        <script>
            lucide.createIcons();
        </script>
    </body>
    </html>
    <?php

    registrarLog('GERAR_RELATORIO', "Gerou relatório de DFDs não iniciados SAA - Ano $ano");
    exit;
}

// ============================================================
// FUNÇÃO HTML: DFDs Em Andamento
// ============================================================
function gerarHTMLDFDsEmAndamento($dados_por_coordenacao, $incluir_graficos, $ano, $coordenacao_filtro) {
    $total_dfds = array_sum(array_column($dados_por_coordenacao, 'total_dfds'));
    $valor_total = array_sum(array_column($dados_por_coordenacao, 'valor_total'));
    $total_com_licitacao = array_sum(array_column($dados_por_coordenacao, 'com_licitacao'));

    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Relatório DFDs Em Andamento - SAA - <?php echo $ano; ?></title>
        <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #f8f9fa; }
            .container { max-width: 1400px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
            h1 { color: #2c3e50; text-align: center; margin-bottom: 10px; font-size: 28px; font-weight: 700; }
            .ano-badge { text-align: center; margin-bottom: 30px; }
            .ano-badge span { display: inline-block; background: linear-gradient(135deg, #f39c12, #e67e22); color: white; padding: 8px 20px; border-radius: 20px; font-size: 16px; font-weight: 700; }
            .subtitle { text-align: center; color: #7f8c8d; margin-bottom: 40px; font-size: 16px; }
            .info { background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color: white; padding: 25px; border-radius: 12px; margin-bottom: 40px; }
            .info p { margin: 8px 0; font-weight: 500; }

            .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px; margin-bottom: 40px; }
            .summary-card { background: white; padding: 25px; border-radius: 12px; text-align: center; border-left: 5px solid #f39c12; box-shadow: 0 2px 15px rgba(0,0,0,0.08); }
            .summary-card h3 { margin: 0 0 15px 0; color: #2c3e50; font-size: 16px; font-weight: 600; }
            .summary-card .value { font-size: 32px; font-weight: 800; color: #f39c12; margin-bottom: 5px; }

            .coordenacao-section { margin: 40px 0; }
            .coordenacao-header { background: linear-gradient(135deg, #f39c12, #e67e22); color: white; padding: 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; }
            .coordenacao-header h3 { margin: 0; font-size: 20px; }
            .coordenacao-stats { display: flex; gap: 30px; font-size: 14px; }
            .coordenacao-stats span { font-weight: 600; }

            table { width: 100%; border-collapse: collapse; background: white; }
            th { background: #ecf0f1; color: #2c3e50; padding: 12px; text-align: left; font-weight: 600; font-size: 13px; }
            td { padding: 12px; border-bottom: 1px solid #ecf0f1; font-size: 13px; }
            tr:hover { background: #f8f9fa; }
            tr.atrasado { background: #fee; }
            tr.atencao { background: #fff9e6; }

            .badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; display: inline-block; }
            .badge-atrasado { background: #e74c3c; color: white; }
            .badge-atencao { background: #f39c12; color: white; }
            .badge-prazo { background: #27ae60; color: white; }
            .badge-licitacao { background: #3498db; color: white; }

            @media print { .no-print { display: none; } }
        </style>
    </head>
    <body>
        <div class="container">
            <h1><i data-lucide="activity"></i> DFDs Em Andamento - Coordenações SAA</h1>
            <div class="ano-badge"><span>📅 ANO PCA: <?php echo $ano; ?></span></div>
            <div class="subtitle">Contratações em execução ativa</div>

            <div class="info">
                <p><strong><i data-lucide="calendar"></i> Ano PCA:</strong> <?php echo $ano; ?></p>
                <p><strong><i data-lucide="building"></i> Coordenações Analisadas:</strong> <?php echo count($dados_por_coordenacao); ?> coordenações SAA</p>
                <?php if (!empty($coordenacao_filtro)): ?>
                <p><strong><i data-lucide="filter"></i> Filtro Aplicado:</strong> <?php echo htmlspecialchars($coordenacao_filtro); ?></p>
                <?php endif; ?>
                <p><strong><i data-lucide="clock"></i> Gerado em:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
            </div>

            <div class="summary">
                <div class="summary-card">
                    <h3>Total Em Andamento</h3>
                    <div class="value"><?php echo number_format($total_dfds, 0, ',', '.'); ?></div>
                </div>
                <div class="summary-card">
                    <h3>Valor Total</h3>
                    <div class="value">R$ <?php echo number_format($valor_total, 2, ',', '.'); ?></div>
                </div>
                <div class="summary-card">
                    <h3>Com Licitação</h3>
                    <div class="value"><?php echo number_format($total_com_licitacao, 0, ',', '.'); ?></div>
                </div>
                <div class="summary-card">
                    <h3>Taxa de Vinculação</h3>
                    <div class="value"><?php echo $total_dfds > 0 ? number_format(($total_com_licitacao / $total_dfds) * 100, 1) : '0'; ?>%</div>
                </div>
            </div>

            <?php foreach ($dados_por_coordenacao as $coord_data): ?>
            <div class="coordenacao-section">
                <div class="coordenacao-header">
                    <h3><i data-lucide="building-2"></i> <?php echo htmlspecialchars($coord_data['coordenacao']); ?></h3>
                    <div class="coordenacao-stats">
                        <span><?php echo $coord_data['total_dfds']; ?> DFDs</span>
                        <span>R$ <?php echo number_format($coord_data['valor_total'], 2, ',', '.'); ?></span>
                        <span><?php echo $coord_data['com_licitacao']; ?> c/ Licitação</span>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>DFD</th>
                            <th>Título da Contratação</th>
                            <th>Situação</th>
                            <th style="text-align: center;">Dias em Andamento</th>
                            <th style="text-align: center;">Dias p/ Conclusão</th>
                            <th style="text-align: center;">Alerta</th>
                            <th style="text-align: right;">Valor</th>
                            <th style="text-align: center;">Licitação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coord_data['dfds'] as $dfd):
                            $row_class = '';
                            if ($dfd['alerta_prazo'] === 'Atrasado') $row_class = 'atrasado';
                            elseif ($dfd['alerta_prazo'] === 'Atenção') $row_class = 'atencao';
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td><strong><?php echo htmlspecialchars($dfd['numero_dfd']); ?></strong></td>
                            <td><?php echo htmlspecialchars(substr($dfd['titulo_contratacao'], 0, 70)) . (strlen($dfd['titulo_contratacao']) > 70 ? '...' : ''); ?></td>
                            <td><?php echo htmlspecialchars($dfd['situacao_execucao']); ?></td>
                            <td style="text-align: center; font-weight: 600;"><?php echo $dfd['dias_em_andamento']; ?> dias</td>
                            <td style="text-align: center; color: <?php echo $dfd['dias_ate_conclusao'] < 0 ? '#e74c3c' : ($dfd['dias_ate_conclusao'] < 30 ? '#f39c12' : '#27ae60'); ?>;">
                                <?php echo $dfd['dias_ate_conclusao'] >= 0 ? $dfd['dias_ate_conclusao'] . ' dias' : 'VENCIDO'; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php
                                $badge_class = 'badge-prazo';
                                if ($dfd['alerta_prazo'] === 'Atrasado') $badge_class = 'badge-atrasado';
                                elseif ($dfd['alerta_prazo'] === 'Atenção') $badge_class = 'badge-atencao';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $dfd['alerta_prazo']; ?></span>
                            </td>
                            <td style="text-align: right;">R$ <?php echo number_format($dfd['valor_total'], 2, ',', '.'); ?></td>
                            <td style="text-align: center;">
                                <?php if ($dfd['tem_licitacao'] > 0): ?>
                                    <span class="badge badge-licitacao">SIM</span>
                                <?php else: ?>
                                    <span style="color: #95a5a6;">NÃO</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>

            <div class="no-print" style="text-align: center; margin-top: 40px;">
                <button onclick="window.print()" style="padding: 12px 30px; background: #f39c12; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px;">
                    <i data-lucide="printer"></i> Imprimir Relatório
                </button>
            </div>
        </div>

        <script>
            lucide.createIcons();
        </script>
    </body>
    </html>
    <?php

    registrarLog('GERAR_RELATORIO', "Gerou relatório de DFDs em andamento SAA - Ano $ano");
    exit;
}

// ============================================================
// FUNÇÕES CSV
// ============================================================
function gerarCSVDFDsAbertos($dados_por_coordenacao, $ano) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="dfds_abertos_saa_' . $ano . '_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    $cabecalho = ['Coordenação', 'DFD', 'Título', 'Categoria', 'Situação', 'Status Prazo', 'Dias Aberto', 'Valor', 'Tem Licitação'];
    fputcsv($output, $cabecalho, ';');

    foreach ($dados_por_coordenacao as $coord_data) {
        foreach ($coord_data['dfds'] as $dfd) {
            $linha = [
                $coord_data['coordenacao'],
                $dfd['numero_dfd'],
                $dfd['titulo_contratacao'],
                $dfd['categoria_contratacao'],
                $dfd['situacao_execucao'] ?: 'Não iniciado',
                $dfd['status_prazo'],
                $dfd['dias_aberto'],
                number_format($dfd['valor_total'], 2, ',', '.'),
                $dfd['tem_licitacao'] > 0 ? 'SIM' : 'NÃO'
            ];
            fputcsv($output, $linha, ';');
        }
    }

    fclose($output);
    registrarLog('EXPORTAR_RELATORIO', "Exportou DFDs abertos SAA em CSV - Ano $ano");
    exit;
}

function gerarCSVDFDsNaoIniciados($dados_por_coordenacao, $ano) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="dfds_nao_iniciados_saa_' . $ano . '_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    $cabecalho = ['Coordenação', 'DFD', 'Título', 'Categoria', 'Data Início', 'Data Conclusão', 'Dias sem Início', 'Prazo Restante', 'Valor', 'Criticidade'];
    fputcsv($output, $cabecalho, ';');

    foreach ($dados_por_coordenacao as $coord_data) {
        foreach ($coord_data['dfds'] as $dfd) {
            $criticidade = 'NORMAL';
            if ($dfd['dias_sem_inicio'] > 60 || $dfd['dias_ate_prazo'] < 30) $criticidade = 'CRÍTICO';
            elseif ($dfd['dias_sem_inicio'] > 30 || $dfd['dias_ate_prazo'] < 60) $criticidade = 'ALERTA';

            $linha = [
                $coord_data['coordenacao'],
                $dfd['numero_dfd'],
                $dfd['titulo_contratacao'],
                $dfd['categoria_contratacao'],
                $dfd['data_inicio_processo'] ? date('d/m/Y', strtotime($dfd['data_inicio_processo'])) : '-',
                $dfd['data_conclusao_processo'] ? date('d/m/Y', strtotime($dfd['data_conclusao_processo'])) : '-',
                $dfd['dias_sem_inicio'],
                $dfd['dias_ate_prazo'] >= 0 ? $dfd['dias_ate_prazo'] : 'VENCIDO',
                number_format($dfd['valor_total'], 2, ',', '.'),
                $criticidade
            ];
            fputcsv($output, $linha, ';');
        }
    }

    fclose($output);
    registrarLog('EXPORTAR_RELATORIO', "Exportou DFDs não iniciados SAA em CSV - Ano $ano");
    exit;
}

function gerarCSVDFDsEmAndamento($dados_por_coordenacao, $ano) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="dfds_em_andamento_saa_' . $ano . '_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    $cabecalho = ['Coordenação', 'DFD', 'Título', 'Situação', 'Dias em Andamento', 'Dias p/ Conclusão', 'Alerta', 'Valor', 'Tem Licitação'];
    fputcsv($output, $cabecalho, ';');

    foreach ($dados_por_coordenacao as $coord_data) {
        foreach ($coord_data['dfds'] as $dfd) {
            $linha = [
                $coord_data['coordenacao'],
                $dfd['numero_dfd'],
                $dfd['titulo_contratacao'],
                $dfd['situacao_execucao'],
                $dfd['dias_em_andamento'],
                $dfd['dias_ate_conclusao'] >= 0 ? $dfd['dias_ate_conclusao'] : 'VENCIDO',
                $dfd['alerta_prazo'],
                number_format($dfd['valor_total'], 2, ',', '.'),
                $dfd['tem_licitacao'] > 0 ? 'SIM' : 'NÃO'
            ];
            fputcsv($output, $linha, ';');
        }
    }

    fclose($output);
    registrarLog('EXPORTAR_RELATORIO', "Exportou DFDs em andamento SAA em CSV - Ano $ano");
    exit;
}
?>
