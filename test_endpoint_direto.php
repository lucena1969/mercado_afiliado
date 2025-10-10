<?php
// Endpoint de teste isolado
include_once 'config.php';
include_once 'functions.php';

// Iniciar sessão antes de qualquer output
session_start();

// Configurar headers corretos
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Simular sessão de usuário válido
$_SESSION['usuario_logado'] = true;
$_SESSION['usuario_id'] = 1;
$_SESSION['nivel_acesso'] = 1;

try {
    $pdo = conectarDB();

    // Parâmetros simulados
    $ano_selecionado = '2025';
    $tabela_pca = getPcaTableName($ano_selecionado);

    // Buscar IDs das importações do ano
    $stmt_importacoes = $pdo->prepare("
        SELECT GROUP_CONCAT(id) as ids
        FROM pca_importacoes
        WHERE ano_pca = ? AND status = 'concluido'
    ");
    $stmt_importacoes->execute([$ano_selecionado]);
    $importacoes_result = $stmt_importacoes->fetch();
    $importacoes_ids_str = $importacoes_result['ids'] ?? '';

    if (empty($importacoes_ids_str)) {
        // Sem importações
        echo json_encode([
            'success' => true,
            'message' => 'Nenhuma importação encontrada para o ano ' . $ano_selecionado,
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
        ]);
        exit;
    }

    $importacoes_ids = explode(',', $importacoes_ids_str);
    $where_stats = "importacao_id IN (" . implode(',', $importacoes_ids) . ")";

    // Consulta SQL simplificada para teste
    $sql_teste = "
        SELECT
            numero_dfd,
            numero_contratacao,
            titulo_contratacao,
            categoria_contratacao,
            area_requisitante,
            valor_total,
            situacao_execucao,
            CASE
                WHEN situacao_execucao LIKE '%o iniciado' THEN 'EM ATRASO'
                WHEN situacao_execucao LIKE '%preparacao%' OR situacao_execucao LIKE '%edicao%' THEN 'EM EXECUÇÃO'
                WHEN situacao_execucao LIKE '%encerrada%' THEN 'EXECUTADO'
                WHEN situacao_execucao LIKE '%revogada%' OR situacao_execucao LIKE '%anulada%' THEN 'NÃO EXECUTADO'
                ELSE 'INDEFINIDO'
            END as status_execucao_ajustado
        FROM {$tabela_pca}
        WHERE {$where_stats}
        AND numero_dfd IS NOT NULL AND numero_dfd != ''
        ORDER BY valor_total DESC
        LIMIT 10
    ";

    $stmt = $pdo->prepare($sql_teste);
    $stmt->execute();
    $resultados = $stmt->fetchAll();

    // Calcular estatísticas
    $total_registros = count($resultados);
    $valor_total = array_sum(array_column($resultados, 'valor_total'));

    $stats_status = ['EM ATRASO' => 0, 'EM EXECUÇÃO' => 0, 'EXECUTADO' => 0, 'NÃO EXECUTADO' => 0];
    foreach ($resultados as $row) {
        $status = $row['status_execucao_ajustado'];
        if (isset($stats_status[$status])) {
            $stats_status[$status]++;
        }
    }

    // Calcular percentuais
    $percentuais_quantidade = [];
    if ($total_registros > 0) {
        foreach ($stats_status as $status => $count) {
            $key = strtolower(str_replace([' ', 'Ã'], ['_', 'a'], $status));
            $percentuais_quantidade[$key] = round(($count / $total_registros) * 100, 1);
        }
    } else {
        $percentuais_quantidade = [
            'em_atraso' => 0,
            'em_execucao' => 0,
            'executado' => 0,
            'nao_executado' => 0
        ];
    }

    // Formatar resultados
    $resultados_formatados = [];
    foreach ($resultados as $row) {
        $resultados_formatados[] = [
            'numero_dfd' => $row['numero_dfd'],
            'numero_contratacao' => $row['numero_contratacao'],
            'titulo_contratacao' => substr($row['titulo_contratacao'], 0, 100) . '...',
            'categoria_contratacao' => $row['categoria_contratacao'],
            'area_requisitante' => $row['area_requisitante'],
            'status_execucao_ajustado' => $row['status_execucao_ajustado'],
            'valor_total' => $row['valor_total'],
            'valor_formatado' => 'R$ ' . number_format($row['valor_total'], 2, ',', '.'),
        ];
    }

    $json_response = [
        'success' => true,
        'data' => [
            'resultados' => $resultados_formatados,
            'total_resultados' => $total_registros,
            'paginacao' => [
                'pagina_atual' => 1,
                'total_paginas' => 1,
                'por_pagina' => 20,
                'total_registros' => $total_registros
            ],
            'estatisticas' => [
                'total_registros' => $total_registros,
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

    echo json_encode($json_response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>