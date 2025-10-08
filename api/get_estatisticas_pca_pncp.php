<?php
// Desabilitar exibição de erros para não contaminar JSON
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once '../config.php';
require_once '../functions.php';

verificarLogin();

// Limpar qualquer output anterior
if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

$pdo = conectarDB();

try {
    // Buscar estatísticas usando a view inteligente PCA-PNCP
    // Forçar COLLATE para evitar erro de collation mismatch
    $sql = "
        SELECT
            COUNT(DISTINCT numero_dfd) as quantidade_dfd,
            COUNT(*) as total_itens,
            SUM(CASE WHEN valor_total_contratacao IS NOT NULL THEN valor_total_contratacao ELSE 0 END) as valor_total_pgc,
            SUM(CASE WHEN pncp_valor IS NOT NULL THEN pncp_valor ELSE 0 END) as valor_total_pncp,
            COUNT(CASE WHEN status_pncp COLLATE utf8mb4_unicode_ci = 'DISPONIVEL_PNCP' THEN 1 END) as com_dados_pncp
        FROM view_pca_com_pncp
        WHERE numero_dfd IS NOT NULL
        AND numero_dfd COLLATE utf8mb4_unicode_ci != ''
        AND ano_pca IN (2025, 2026)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $estatisticas = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$estatisticas) {
        throw new Exception('Nenhum dado retornado da consulta');
    }

    // Dados de sincronização já calculados na query principal
    $sync_stats = [
        'registros_sincronizados' => $estatisticas['total_itens'],
        'com_dados_pncp' => $estatisticas['com_dados_pncp']
    ];

    // Última sincronização PNCP
    $sql_ultima = "
        SELECT MAX(criado_em) as ultima_sincronizacao
        FROM pncp_importacoes
        WHERE status = 'concluido'
    ";

    $stmt_ultima = $pdo->prepare($sql_ultima);
    $stmt_ultima->execute();
    $ultima_sync = $stmt_ultima->fetch(PDO::FETCH_ASSOC);

    $resultado = [
        'quantidade_dfd' => intval($estatisticas['quantidade_dfd'] ?? 0),
        'total_itens' => intval($estatisticas['total_itens'] ?? 0),
        'valor_total_pgc' => floatval($estatisticas['valor_total_pgc'] ?? 0),
        'valor_total_pncp' => floatval($estatisticas['valor_total_pncp'] ?? 0),
        'registros_sincronizados' => intval($sync_stats['registros_sincronizados']),
        'com_dados_pncp' => intval($sync_stats['com_dados_pncp']),
        'percentual_sincronizacao' => $sync_stats['registros_sincronizados'] > 0 ?
            round(($sync_stats['com_dados_pncp'] / $sync_stats['registros_sincronizados']) * 100, 1) : 0,
        'ultima_sincronizacao' => $ultima_sync['ultima_sincronizacao'] ?? null
    ];

    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    error_log("Erro em get_estatisticas_pca_pncp.php: " . $e->getMessage());
    echo json_encode(['erro' => 'Erro ao carregar estatísticas: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}