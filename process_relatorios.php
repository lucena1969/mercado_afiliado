<?php
/**
 * Processador de Ações do Módulo Relatórios Gerenciais - VERSÃO MÍNIMA
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once 'config.php';
require_once 'functions.php';

configurarSessaoSegura();

if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$acao = $_POST['acao'] ?? $_POST['action'] ?? '';
$pdo = conectarDB();

switch ($acao) {

    case 'dashboard_executivo_geral':
        verificarLogin();
        try {
            $ano = isset($_POST['ano']) ? intval($_POST['ano']) : null;
            $filtrar_por_ano = !empty($ano);

            $sql_planejadas = "SELECT ano, COUNT(DISTINCT numero_dfd) as total_planejadas, SUM(valor_total_contratacao) as valor_total
                               FROM pca_dados " . ($filtrar_por_ano ? "WHERE ano = ?" : "") . " GROUP BY ano ORDER BY ano";
            $stmt_planejadas = $pdo->prepare($sql_planejadas);
            if ($filtrar_por_ano) { $stmt_planejadas->execute([$ano]); } else { $stmt_planejadas->execute(); }
            $dados_planejadas = $stmt_planejadas->fetchAll(PDO::FETCH_ASSOC);

            $sql_executadas = "SELECT ano, COUNT(*) as total_executadas, SUM(valor_estimado) as valor_total,
                               SUM(CASE WHEN situacao = 'HOMOLOGADA' THEN 1 ELSE 0 END) as total_homologadas
                               FROM licitacoes " . ($filtrar_por_ano ? "WHERE ano = ?" : "") . " GROUP BY ano ORDER BY ano";
            $stmt_executadas = $pdo->prepare($sql_executadas);
            if ($filtrar_por_ano) { $stmt_executadas->execute([$ano]); } else { $stmt_executadas->execute(); }
            $dados_executadas = $stmt_executadas->fetchAll(PDO::FETCH_ASSOC);

            $dados_taxa_execucao = [];
            foreach ($dados_planejadas as $plan) {
                $ano_plan = $plan['ano'];
                $planejadas = intval($plan['total_planejadas']);
                $executadas = 0;
                foreach ($dados_executadas as $exec) {
                    if ($exec['ano'] == $ano_plan) { $executadas = intval($exec['total_executadas']); break; }
                }
                $taxa = $planejadas > 0 ? round(($executadas / $planejadas) * 100, 1) : 0;
                $dados_taxa_execucao[] = ['ano' => $ano_plan, 'taxa' => $taxa, 'planejadas' => $planejadas, 'executadas' => $executadas];
            }

            echo json_encode([
                'success' => true,
                'dados_planejadas' => $dados_planejadas,
                'dados_executadas' => $dados_executadas,
                'dados_taxa_execucao' => $dados_taxa_execucao,
                'filtrar_por_ano' => $filtrar_por_ano,
                'ano_filtro' => $ano
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Erro em dashboard_executivo_geral: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'dashboard_executivo_pca':
        verificarLogin();
        try {
            $ano = isset($_POST['ano']) ? intval($_POST['ano']) : date('Y');

            $sql = "SELECT COUNT(DISTINCT numero_dfd) as total_contratacoes, SUM(valor_total_contratacao) as valor_total,
                    COUNT(DISTINCT area_requisitante) as total_areas,
                    COUNT(DISTINCT CASE WHEN situacao_execucao = 'Concluído' THEN numero_dfd END) as total_concluidas,
                    COUNT(DISTINCT CASE WHEN situacao_execucao = 'Em Andamento' THEN numero_dfd END) as total_andamento,
                    COUNT(DISTINCT CASE WHEN situacao_execucao = 'Não Iniciado' THEN numero_dfd END) as total_nao_iniciadas
                    FROM pca_dados WHERE ano = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$ano]);
            $estatisticas = $stmt->fetch(PDO::FETCH_ASSOC);

            $sql_situacao = "SELECT situacao_execucao, COUNT(DISTINCT numero_dfd) as total, SUM(valor_total_contratacao) as valor_total
                             FROM pca_dados WHERE ano = ? GROUP BY situacao_execucao ORDER BY total DESC";
            $stmt_situacao = $pdo->prepare($sql_situacao);
            $stmt_situacao->execute([$ano]);
            $dados_situacao = $stmt_situacao->fetchAll(PDO::FETCH_ASSOC);

            $sql_areas = "SELECT area_requisitante, COUNT(DISTINCT numero_dfd) as total, SUM(valor_total_contratacao) as valor_total
                          FROM pca_dados WHERE ano = ? GROUP BY area_requisitante ORDER BY total DESC LIMIT 10";
            $stmt_areas = $pdo->prepare($sql_areas);
            $stmt_areas->execute([$ano]);
            $dados_areas = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'ano' => $ano,
                'estatisticas' => $estatisticas,
                'dados_situacao' => $dados_situacao,
                'dados_areas' => $dados_areas
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Erro em dashboard_executivo_pca: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'dashboard_executivo_qualificacoes':
        verificarLogin();
        try {
            $sql = "SELECT COUNT(*) as total_qualificacoes, SUM(valor_estimado) as valor_total,
                    COUNT(CASE WHEN status LIKE 'EM A%' THEN 1 END) as total_analise,
                    COUNT(CASE WHEN status LIKE 'CONCLU%' THEN 1 END) as total_concluidas,
                    COUNT(CASE WHEN status = 'ARQUIVADO' THEN 1 END) as total_arquivadas
                    FROM qualificacoes";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $estatisticas = $stmt->fetch(PDO::FETCH_ASSOC);

            $sql_status = "SELECT status, COUNT(*) as total, SUM(valor_estimado) as valor_total FROM qualificacoes GROUP BY status ORDER BY total DESC";
            $stmt_status = $pdo->prepare($sql_status);
            $stmt_status->execute();
            $dados_status = $stmt_status->fetchAll(PDO::FETCH_ASSOC);

            $sql_modalidade = "SELECT modalidade, COUNT(*) as total, SUM(valor_estimado) as valor_total FROM qualificacoes GROUP BY modalidade ORDER BY total DESC";
            $stmt_modalidade = $pdo->prepare($sql_modalidade);
            $stmt_modalidade->execute();
            $dados_modalidade = $stmt_modalidade->fetchAll(PDO::FETCH_ASSOC);

            $sql_responsavel = "SELECT responsavel, COUNT(*) as total, SUM(valor_estimado) as valor_total
                                FROM qualificacoes WHERE responsavel IS NOT NULL AND responsavel != ''
                                GROUP BY responsavel ORDER BY total DESC LIMIT 10";
            $stmt_responsavel = $pdo->prepare($sql_responsavel);
            $stmt_responsavel->execute();
            $dados_responsavel = $stmt_responsavel->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'estatisticas' => $estatisticas,
                'dados_status' => $dados_status,
                'dados_modalidade' => $dados_modalidade,
                'dados_responsavel' => $dados_responsavel
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Erro em dashboard_executivo_qualificacoes: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'dashboard_executivo_licitacoes':
        verificarLogin();
        try {
            $sql = "SELECT COUNT(*) as total_licitacoes, SUM(valor_estimado) as valor_total_estimado,
                    SUM(valor_homologado) as valor_total_homologado, SUM(economia) as economia_total,
                    COUNT(CASE WHEN situacao = 'HOMOLOGADA' THEN 1 END) as total_homologadas,
                    COUNT(CASE WHEN situacao = 'EM_ANDAMENTO' THEN 1 END) as total_andamento,
                    COUNT(CASE WHEN situacao = 'SUSPENSA' THEN 1 END) as total_suspensas,
                    COUNT(CASE WHEN situacao = 'FRACASSADA' THEN 1 END) as total_fracassadas
                    FROM licitacoes";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $estatisticas = $stmt->fetch(PDO::FETCH_ASSOC);

            $sql_modalidade = "SELECT modalidade, COUNT(*) as total, SUM(valor_estimado) as valor_estimado,
                               SUM(valor_homologado) as valor_homologado, SUM(economia) as economia_total
                               FROM licitacoes GROUP BY modalidade ORDER BY total DESC";
            $stmt_modalidade = $pdo->prepare($sql_modalidade);
            $stmt_modalidade->execute();
            $dados_modalidade = $stmt_modalidade->fetchAll(PDO::FETCH_ASSOC);

            $sql_situacao = "SELECT situacao, COUNT(*) as total, SUM(valor_estimado) as valor_estimado
                             FROM licitacoes GROUP BY situacao ORDER BY total DESC";
            $stmt_situacao = $pdo->prepare($sql_situacao);
            $stmt_situacao->execute();
            $dados_situacao = $stmt_situacao->fetchAll(PDO::FETCH_ASSOC);

            $sql_pregoeiro = "SELECT pregoeiro, COUNT(*) as total, SUM(valor_estimado) as valor_estimado,
                              SUM(valor_homologado) as valor_homologado, SUM(economia) as economia_total
                              FROM licitacoes WHERE pregoeiro IS NOT NULL AND pregoeiro != ''
                              GROUP BY pregoeiro ORDER BY total DESC LIMIT 10";
            $stmt_pregoeiro = $pdo->prepare($sql_pregoeiro);
            $stmt_pregoeiro->execute();
            $dados_pregoeiro = $stmt_pregoeiro->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'estatisticas' => $estatisticas,
                'dados_modalidade' => $dados_modalidade,
                'dados_situacao' => $dados_situacao,
                'dados_pregoeiro' => $dados_pregoeiro
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Erro em dashboard_executivo_licitacoes: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Ação não reconhecida: ' . $acao], JSON_UNESCAPED_UNICODE);
        break;
}

exit;
