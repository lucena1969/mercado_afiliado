<?php
// Endpoint alternativo para relatório PCA - mais robusto
// Limpar output buffer ANTES de qualquer include
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Configurar headers ANTES de incluir qualquer arquivo
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar método OPTIONS primeiro
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    exit(0);
}

// Agora incluir os arquivos
include_once 'config.php';
include_once 'functions.php';

// Limpar qualquer output acidental dos includes
ob_clean();

// Verificar login usando a função padrão do sistema
if (!isset($_SESSION['usuario_id'])) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não logado'
    ]);
    ob_end_flush();
    exit;
}

try {
    $pdo = conectarDB();

    // Receber parâmetros (GET ou POST)
    $params = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

    $ano_selecionado = $params['ano'] ?? date('Y');
    $data_inicio = $params['data_inicio'] ?? '';
    $data_fim = $params['data_fim'] ?? '';
    $area_requisitante_filtro = $params['area_requisitante_filtro'] ?? '';
    $categoria_filtro = $params['categoria_filtro'] ?? '';
    $status_execucao_filtro = $params['status_execucao_filtro'] ?? '';
    $status_contratacao_filtro = $params['status_contratacao_filtro'] ?? '';
    $situacao_original_filtro = $params['situacao_original_filtro'] ?? '';
    $tem_licitacao_filtro = $params['tem_licitacao_filtro'] ?? '';
    $valor_minimo = $params['valor_minimo'] ?? '';
    $valor_maximo = $params['valor_maximo'] ?? '';
    $pagina = intval($params['pagina'] ?? 1);

    // Usar sempre tabela unificada pca_dados
    $tabela_pca = 'pca_dados';

    // Buscar importações
    $stmt_importacoes = $pdo->prepare("
        SELECT GROUP_CONCAT(id) as ids
        FROM pca_importacoes
        WHERE ano_pca = ? AND status = 'concluido'
    ");
    $stmt_importacoes->execute([$ano_selecionado]);
    $importacoes_result = $stmt_importacoes->fetch();
    $importacoes_ids_str = $importacoes_result['ids'] ?? '';

    if (empty($importacoes_ids_str)) {
        $response = [
            'success' => true,
            'data' => [
                'resultados' => [],
                'total_resultados' => 0,
                'paginacao' => [
                    'pagina_atual' => 1,
                    'total_paginas' => 0,
                    'por_pagina' => 20,
                    'total_registros' => 0
                ],
                'estatisticas' => [
                    'total_registros' => 0,
                    'valor_total_formatado' => 'R$ 0,00',
                    'contadores' => [
                        'em_atraso' => 0,
                        'em_execucao' => 0,
                        'executado' => 0,
                        'nao_executado' => 0
                    ],
                    'percentuais_quantidade' => [
                        'em_atraso' => 0,
                        'em_execucao' => 0,
                        'executado' => 0,
                        'nao_executado' => 0
                    ]
                ]
            ]
        ];

        echo json_encode($response);
        exit;
    }

    $importacoes_ids = explode(',', $importacoes_ids_str);
    $where_stats = "importacao_id IN (" . implode(',', $importacoes_ids) . ")";

    // Query agrupada por contratação - DEFINITIVA sem JOIN
    $sql_base = "
        SELECT
            pca.numero_contratacao,
            MAX(pca.titulo_contratacao) as titulo_contratacao,
            MAX(pca.categoria_contratacao) as categoria_contratacao,
            MAX(pca.area_requisitante) as area_requisitante,
            COALESCE(MAX(pca.valor_total_contratacao), SUM(pca.valor_total)) as valor_total_contratacao,
            SUM(pca.valor_total) as valor_total_itens,
            COUNT(*) as total_itens,
            MAX(pca.status_contratacao) as status_contratacao,
            MAX(pca.situacao_execucao) as situacao_execucao,
            MAX(pca.data_inicio_processo) as data_inicio_processo,
            MAX(pca.data_conclusao_processo) as data_conclusao_processo,
            CASE
                WHEN MAX(pca.situacao_execucao) LIKE '%o iniciado' THEN 'EM ATRASO'
                WHEN MAX(pca.situacao_execucao) LIKE '%preparacao%' OR MAX(pca.situacao_execucao) LIKE '%edicao%' THEN 'EM EXECUÇÃO'
                WHEN MAX(pca.situacao_execucao) LIKE '%encerrada%' THEN 'EXECUTADO'
                WHEN MAX(pca.situacao_execucao) LIKE '%revogada%' OR MAX(pca.situacao_execucao) LIKE '%anulada%' THEN 'NÃO EXECUTADO'
                ELSE 'INDEFINIDO'
            END as status_execucao_ajustado,
            (SELECT CASE WHEN COUNT(*) > 0 THEN 'SIM' ELSE 'NÃO' END
             FROM licitacoes l WHERE l.numero_contratacao = pca.numero_contratacao) as tem_licitacao,
            (SELECT modalidade FROM licitacoes l WHERE l.numero_contratacao = pca.numero_contratacao LIMIT 1) as modalidade_licitacao,
            (SELECT situacao FROM licitacoes l WHERE l.numero_contratacao = pca.numero_contratacao LIMIT 1) as situacao_licitacao,
            GROUP_CONCAT(DISTINCT pca.numero_dfd ORDER BY pca.numero_dfd SEPARATOR ', ') as dfds_vinculados
        FROM {$tabela_pca} pca
        WHERE {$where_stats}
        AND pca.numero_dfd IS NOT NULL AND pca.numero_dfd != ''
        AND pca.numero_contratacao IS NOT NULL AND pca.numero_contratacao != ''
    ";

    // Adicionar filtros
    $where_conditions = [];
    $sql_params = [];

    if (!empty($data_inicio) && !empty($data_fim)) {
        $where_conditions[] = "DATE(pca.data_inicio_processo) BETWEEN ? AND ?";
        $sql_params[] = $data_inicio;
        $sql_params[] = $data_fim;
    }

    if (!empty($area_requisitante_filtro)) {
        $where_conditions[] = "pca.area_requisitante = ?";
        $sql_params[] = $area_requisitante_filtro;
    }

    if (!empty($categoria_filtro)) {
        $where_conditions[] = "pca.categoria_contratacao = ?";
        $sql_params[] = $categoria_filtro;
    }

    if (!empty($status_contratacao_filtro)) {
        $where_conditions[] = "pca.status_contratacao = ?";
        $sql_params[] = $status_contratacao_filtro;
    }

    if (!empty($situacao_original_filtro)) {
        $where_conditions[] = "pca.situacao_execucao LIKE ?";
        $sql_params[] = '%' . $situacao_original_filtro . '%';
    }

    if (!empty($valor_minimo)) {
        $where_conditions[] = "pca.valor_total_contratacao >= ?";
        $sql_params[] = floatval($valor_minimo);
    }

    if (!empty($valor_maximo)) {
        $where_conditions[] = "pca.valor_total_contratacao <= ?";
        $sql_params[] = floatval($valor_maximo);
    }

    if (!empty($where_conditions)) {
        $sql_base .= " AND " . implode(" AND ", $where_conditions);
    }

    // Adicionar GROUP BY simples - apenas por número da contratação
    $sql_base .= " GROUP BY pca.numero_contratacao";

    $sql_base .= " ORDER BY COALESCE(MAX(pca.valor_total_contratacao), SUM(pca.valor_total)) DESC LIMIT 50";

    $stmt = $pdo->prepare($sql_base);
    $stmt->execute($sql_params);
    $resultados = $stmt->fetchAll();

    // Aplicar filtros pós-processamento
    if (!empty($status_execucao_filtro)) {
        $resultados = array_filter($resultados, function($row) use ($status_execucao_filtro) {
            return $row['status_execucao_ajustado'] === $status_execucao_filtro;
        });
    }

    if (!empty($tem_licitacao_filtro)) {
        $resultados = array_filter($resultados, function($row) use ($tem_licitacao_filtro) {
            return $row['tem_licitacao'] === $tem_licitacao_filtro;
        });
    }

    // Estatísticas - agora considerando contratações agrupadas
    $total_registros = count($resultados);
    $valor_total = array_sum(array_column($resultados, 'valor_total_itens'));
    $total_itens = array_sum(array_column($resultados, 'total_itens'));

    $stats_status = ['EM ATRASO' => 0, 'EM EXECUÇÃO' => 0, 'EXECUTADO' => 0, 'NÃO EXECUTADO' => 0];
    foreach ($resultados as $row) {
        $status = $row['status_execucao_ajustado'];
        if (isset($stats_status[$status])) {
            $stats_status[$status]++;
        }
    }

    // Percentuais
    $percentuais_quantidade = [];
    if ($total_registros > 0) {
        $percentuais_quantidade = [
            'em_atraso' => round(($stats_status['EM ATRASO'] / $total_registros) * 100, 1),
            'em_execucao' => round(($stats_status['EM EXECUÇÃO'] / $total_registros) * 100, 1),
            'executado' => round(($stats_status['EXECUTADO'] / $total_registros) * 100, 1),
            'nao_executado' => round(($stats_status['NÃO EXECUTADO'] / $total_registros) * 100, 1)
        ];
    } else {
        $percentuais_quantidade = [
            'em_atraso' => 0,
            'em_execucao' => 0,
            'executado' => 0,
            'nao_executado' => 0
        ];
    }

    // Formatar resultados - agrupados por contratação
    $resultados_formatados = [];
    foreach ($resultados as $row) {
        $resultados_formatados[] = [
            'numero_contratacao' => $row['numero_contratacao'],
            'titulo_contratacao' => substr($row['titulo_contratacao'], 0, 100) . '...',
            'categoria_contratacao' => $row['categoria_contratacao'],
            'area_requisitante' => $row['area_requisitante'],
            'status_execucao_ajustado' => $row['status_execucao_ajustado'],
            'valor_total_contratacao' => $row['valor_total_contratacao'],
            'valor_total_itens' => $row['valor_total_itens'],
            'total_itens' => $row['total_itens'],
            'valor_formatado' => 'R$ ' . number_format($row['valor_total_itens'], 2, ',', '.'),
            'valor_contratacao_formatado' => 'R$ ' . number_format($row['valor_total_contratacao'], 2, ',', '.'),
            'tem_licitacao' => $row['tem_licitacao'],
            'modalidade_licitacao' => $row['modalidade_licitacao'] ?? '-',
            'dfds_vinculados' => $row['dfds_vinculados'],
            'situacao_execucao' => $row['situacao_execucao'],
        ];
    }

    // Paginação
    $por_pagina = 20;
    $offset = ($pagina - 1) * $por_pagina;
    $total_paginas = ceil(count($resultados_formatados) / $por_pagina);
    $resultados_limitados = array_slice($resultados_formatados, $offset, $por_pagina);

    $response = [
        'success' => true,
        'data' => [
            'resultados' => $resultados_limitados,
            'total_resultados' => $total_registros,
            'paginacao' => [
                'pagina_atual' => $pagina,
                'total_paginas' => $total_paginas,
                'por_pagina' => $por_pagina,
                'total_registros' => count($resultados_formatados)
            ],
            'estatisticas' => [
                'total_contratacoes' => $total_registros,
                'total_itens' => $total_itens,
                'valor_total_formatado' => 'R$ ' . number_format($valor_total, 2, ',', '.'),
                'contadores' => [
                    'em_atraso' => $stats_status['EM ATRASO'],
                    'em_execucao' => $stats_status['EM EXECUÇÃO'],
                    'executado' => $stats_status['EXECUTADO'],
                    'nao_executado' => $stats_status['NÃO EXECUTADO']
                ],
                'percentuais_quantidade' => $percentuais_quantidade
            ]
        ]
    ];

    // Limpar buffer e enviar JSON limpo
    ob_clean();
    echo json_encode($response);
    ob_end_flush();

} catch (Exception $e) {
    // Limpar buffer e enviar erro como JSON
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    ob_end_flush();
}
exit;