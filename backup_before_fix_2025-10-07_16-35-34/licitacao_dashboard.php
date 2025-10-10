<?php
require_once 'config.php';
require_once 'functions.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar/corrigir sessão
if (!isset($_SESSION['usuario_id']) && isset($_SESSION['user_id'])) {
    $_SESSION['usuario_id'] = $_SESSION['user_id'];
}

if (!isset($_SESSION['usuario_nivel']) && isset($_SESSION['user_nivel'])) {
    $_SESSION['usuario_nivel'] = $_SESSION['user_nivel'];
}

verificarLogin();

$pdo = conectarDB();

// Buscar anos disponíveis para o filtro
$anos_disponiveis = [];
try {
    $sql_anos = "SELECT DISTINCT YEAR(data_abertura) as ano 
                 FROM licitacoes 
                 WHERE data_abertura IS NOT NULL 
                 ORDER BY ano DESC";
    $stmt_anos = $pdo->query($sql_anos);
    $anos_disponiveis = $stmt_anos->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // Se houver erro, manter array vazio
    $anos_disponiveis = [];
}


// Verificar se é uma requisição AJAX para filtros
if (isset($_GET['ajax']) && $_GET['ajax'] === 'filtrar_licitacoes') {
    // Processar filtros (mesmo código que já existe)
    $licitacoes_por_pagina = isset($_GET['por_pagina']) ? max(10, min(500, intval($_GET['por_pagina']))) : 10;
    $pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
    $offset = ($pagina_atual - 1) * $licitacoes_por_pagina;

    $filtro_situacao = $_GET['situacao_filtro'] ?? '';
    $filtro_busca = $_GET['busca'] ?? '';
    $filtro_ano = $_GET['ano_filtro'] ?? '';

    $where_conditions = ['1=1'];
    $params = [];

    if (!empty($filtro_situacao)) {
        $where_conditions[] = "l.situacao = ?";
        $params[] = $filtro_situacao;
    }

    if (!empty($filtro_busca)) {
        $where_conditions[] = "(l.nup LIKE ? OR l.objeto LIKE ? OR l.pregoeiro LIKE ? OR l.numero_contratacao LIKE ?)";
        $busca_param = "%$filtro_busca%";
        $params[] = $busca_param;
        $params[] = $busca_param;
        $params[] = $busca_param;
        $params[] = $busca_param;
    }

    if (!empty($filtro_ano)) {
        $where_conditions[] = "YEAR(l.data_abertura) = ?";
        $params[] = intval($filtro_ano);
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Contar total - NOVA ABORDAGEM sem JOIN problemático
    $sql_count = "SELECT COUNT(*) as total 
                  FROM licitacoes l 
                  LEFT JOIN usuarios u ON l.usuario_id = u.id
                  WHERE $where_clause";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params);
    $total_licitacoes = $stmt_count->fetch()['total'];

    // Buscar licitações - NOVA ABORDAGEM com vinculação às qualificações via tabela de relacionamento
    $sql_licitacoes = "SELECT
        l.id,
        l.nup,
        l.numero_contratacao,
        l.modalidade,
        l.tipo,
        l.objeto,
        l.valor_estimado,
        l.valor_homologado,
        l.economia,
        l.situacao,
        l.pregoeiro,
        l.data_entrada_dipli,
        l.data_abertura,
        l.data_homologacao,
        l.data_publicacao,
        l.resp_instrucao,
        l.area_demandante,
        l.qtd_itens,
        l.link,
        l.observacoes,
        l.usuario_id,
        l.pca_dados_id,
        l.criado_em,
        l.atualizado_em,
        u.nome as usuario_nome,
        -- Dados da qualificação vinculada (usando view de relacionamento)
        vc.qualificacao_id,
        vc.qualificacao_nup,
        vc.qualificacao_area,
        vc.qualificacao_responsavel,
        vc.qualificacao_status,
        vc.qualificacao_valor,
        vc.pca_numero_contratacao,
        vc.pca_numero_dfd,
        vc.pca_titulo,
        vc.vinculacao_ativa,
        vc.vinculacao_criada_em,
        COALESCE(l.numero_contratacao, vc.pca_numero_contratacao) as numero_contratacao_final
        FROM licitacoes l
        LEFT JOIN usuarios u ON l.usuario_id = u.id
        LEFT JOIN view_qualificacoes_licitacoes_completa vc ON l.id = vc.licitacao_id AND vc.vinculacao_ativa = 'ATIVA'
        WHERE $where_clause
        ORDER BY l.id DESC
        LIMIT $licitacoes_por_pagina OFFSET $offset";
    
    $stmt_licitacoes = $pdo->prepare($sql_licitacoes);
    $stmt_licitacoes->execute($params);
    $licitacoes_recentes = $stmt_licitacoes->fetchAll();
    

    // Buscar contagem de andamentos separadamente (igual à seção principal)
    if (!empty($licitacoes_recentes)) {
        $nups = array_column($licitacoes_recentes, 'nup');
        $placeholders = str_repeat('?,', count($nups) - 1) . '?';
        
        try {
            $sql_andamentos = "SELECT nup, COUNT(*) as total 
                              FROM historico_andamentos 
                              WHERE nup IN ($placeholders) 
                              GROUP BY nup";
            $stmt_andamentos = $pdo->prepare($sql_andamentos);
            $stmt_andamentos->execute($nups);
            $contagens_andamentos = $stmt_andamentos->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Adicionar contagem aos resultados
            foreach ($licitacoes_recentes as $index => &$licitacao) {
                $licitacao['total_andamentos'] = $contagens_andamentos[$licitacao['nup']] ?? 0;
            }
            unset($licitacao); // Limpar referência para evitar bugs
        } catch (Exception $e) {
            // Se houver erro com a tabela de andamentos, definir como 0
            foreach ($licitacoes_recentes as $index => &$licitacao) {
                $licitacao['total_andamentos'] = 0;
            }
            unset($licitacao); // Limpar referência para evitar bugs
        }
    }

    // Calcular paginação
    $total_paginas = ceil($total_licitacoes / $licitacoes_por_pagina);

    // Garantir que não há duplicações
    $temp_array = [];
    $ids_vistos = [];
    foreach ($licitacoes_recentes as $licitacao) {
        if (!in_array($licitacao['id'], $ids_vistos)) {
            $temp_array[] = $licitacao;
            $ids_vistos[] = $licitacao['id'];
        }
    }
    $licitacoes_recentes = $temp_array;
    
    // Retornar apenas o HTML dos resultados
    ob_start();
    include_once 'partials/lista_licitacoes_ajax.php'; // Vamos criar este arquivo
    $html = ob_get_clean();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'html' => $html,
        'total' => $total_licitacoes,
        'pagina_atual' => $pagina_atual,
        'total_paginas' => $total_paginas
    ]);
    exit;
}

// Buscar estatísticas para os cards e gráficos
$stats_sql = "SELECT
    COUNT(*) as total_licitacoes,
    COUNT(CASE WHEN situacao = 'EM_ANDAMENTO' THEN 1 END) as em_andamento,
    COUNT(CASE WHEN situacao = 'HOMOLOGADO' THEN 1 END) as homologadas,
    COUNT(CASE WHEN situacao = 'FRACASSADO' THEN 1 END) as fracassadas,
    COUNT(CASE WHEN situacao = 'REVOGADO' THEN 1 END) as revogadas,
    SUM(CASE WHEN situacao = 'HOMOLOGADO' THEN valor_estimado ELSE 0 END) as valor_homologado
    FROM licitacoes";

$stats = $pdo->query($stats_sql)->fetch();

// Dados para gráficos
$dados_modalidade = $pdo->query("
    SELECT modalidade, COUNT(*) as quantidade
    FROM licitacoes
    GROUP BY modalidade
")->fetchAll();

$dados_pregoeiro = $pdo->query("
    SELECT
        CASE
            WHEN l.pregoeiro IS NULL OR l.pregoeiro = '' THEN 'Não Definido'
            ELSE l.pregoeiro
        END AS pregoeiro,
        COUNT(*) AS quantidade
    FROM licitacoes l
    GROUP BY l.pregoeiro
    ORDER BY quantidade DESC
    LIMIT 5
")->fetchAll();

$dados_mensal = $pdo->query("
    SELECT
        DATE_FORMAT(
            COALESCE(data_abertura, criado_em),
            '%Y-%m'
        ) as mes,
        COUNT(*) as quantidade,
        SUM(CASE WHEN data_abertura IS NOT NULL THEN 1 ELSE 0 END) as com_data_abertura,
        SUM(CASE WHEN data_abertura IS NULL THEN 1 ELSE 0 END) as sem_data_abertura
    FROM licitacoes
    WHERE (data_abertura IS NOT NULL AND data_abertura >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH))
    OR (data_abertura IS NULL AND criado_em >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH))
    GROUP BY DATE_FORMAT(
        COALESCE(data_abertura, criado_em),
        '%Y-%m'
    )
    ORDER BY mes
")->fetchAll();

// Configuração da paginação
$licitacoes_por_pagina = isset($_GET['por_pagina']) ? max(10, min(500, intval($_GET['por_pagina']))) : 10;
$pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_atual - 1) * $licitacoes_por_pagina;

// Detectar seção ativa baseada na URL ou seção padrão
$secao_ativa = $_GET['secao'] ?? 'lista-licitacoes';

// Filtros opcionais
$filtro_situacao = $_GET['situacao_filtro'] ?? '';
$filtro_busca = $_GET['busca'] ?? '';
$filtro_ano = $_GET['ano_filtro'] ?? '';

// Construir WHERE clause para filtros
$where_conditions = ['1=1'];
$params = [];

if (!empty($filtro_situacao)) {
    $where_conditions[] = "l.situacao = ?";
    $params[] = $filtro_situacao;
}

if (!empty($filtro_busca)) {
    $where_conditions[] = "(l.nup LIKE ? OR l.objeto LIKE ? OR l.pregoeiro LIKE ? OR l.numero_contratacao LIKE ?)";
    $busca_param = "%$filtro_busca%";
    $params[] = $busca_param;
    $params[] = $busca_param;
    $params[] = $busca_param;
    $params[] = $busca_param;
}

if (!empty($filtro_ano)) {
    $where_conditions[] = "YEAR(l.data_abertura) = ?";
    $params[] = intval($filtro_ano);
}

$where_clause = implode(' AND ', $where_conditions);

// Contar total de licitações (para paginação) - NOVA ABORDAGEM sem JOIN problemático
$sql_count = "SELECT COUNT(*) as total 
              FROM licitacoes l 
              LEFT JOIN usuarios u ON l.usuario_id = u.id
              WHERE $where_clause";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_licitacoes = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_licitacoes / $licitacoes_por_pagina);

// Buscar licitações da página atual - COM view de qualificações
$sql = "SELECT
            l.id,
            l.nup,
            l.numero_contratacao,
            l.modalidade,
            l.tipo,
            l.objeto,
            l.valor_estimado,
            l.valor_homologado,
            l.economia,
            l.situacao,
            l.pregoeiro,
            l.data_entrada_dipli,
            l.data_abertura,
            l.data_homologacao,
            l.data_publicacao,
            l.resp_instrucao,
            l.area_demandante,
            l.qtd_itens,
            l.link,
            l.observacoes,
            l.usuario_id,
            l.pca_dados_id,
            l.criado_em,
            l.atualizado_em,
            u.nome as usuario_criador_nome,
            -- Dados da qualificação vinculada (usando view de relacionamento)
            vc.qualificacao_id,
            vc.qualificacao_nup,
            vc.qualificacao_area,
            vc.qualificacao_responsavel,
            vc.qualificacao_status,
            vc.qualificacao_valor,
            vc.qualificacao_objeto,
            vc.pca_numero_contratacao,
            vc.pca_numero_dfd,
            vc.pca_titulo,
            vc.vinculacao_ativa,
            vc.vinculacao_criada_em,
            COALESCE(l.numero_contratacao, vc.pca_numero_contratacao) as numero_contratacao_final
        FROM licitacoes l
        LEFT JOIN usuarios u ON l.usuario_id = u.id
        LEFT JOIN view_qualificacoes_licitacoes_completa vc ON l.id = vc.licitacao_id AND vc.vinculacao_ativa = 'ATIVA'
        WHERE $where_clause
        ORDER BY l.id DESC
        LIMIT $licitacoes_por_pagina OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$licitacoes_recentes = $stmt->fetchAll();

// Garantir que não há duplicações
if (!empty($licitacoes_recentes)) {
    $temp_array = [];
    $ids_vistos = [];
    foreach ($licitacoes_recentes as $licitacao) {
        if (!in_array($licitacao['id'], $ids_vistos)) {
            $temp_array[] = $licitacao;
            $ids_vistos[] = $licitacao['id'];
        }
    }
    $licitacoes_recentes = $temp_array;
}

// Buscar contagem de andamentos separadamente para evitar problema de collation
if (!empty($licitacoes_recentes)) {
    $nups = array_column($licitacoes_recentes, 'nup');
    $placeholders = str_repeat('?,', count($nups) - 1) . '?';
    
    try {
        $sql_andamentos = "SELECT nup, COUNT(*) as total 
                          FROM historico_andamentos 
                          WHERE nup IN ($placeholders) 
                          GROUP BY nup";
        $stmt_andamentos = $pdo->prepare($sql_andamentos);
        $stmt_andamentos->execute($nups);
        $contagens_andamentos = $stmt_andamentos->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Adicionar contagem aos resultados
        foreach ($licitacoes_recentes as $index => &$licitacao) {
            $licitacao['total_andamentos'] = $contagens_andamentos[$licitacao['nup']] ?? 0;
        }
        unset($licitacao); // Limpar referência para evitar bugs
    } catch (Exception $e) {
        // Se houver erro com a tabela de andamentos, definir como 0
        foreach ($licitacoes_recentes as $index => &$licitacao) {
            $licitacao['total_andamentos'] = 0;
        }
        unset($licitacao); // Limpar referência para evitar bugs
    }
}

// Buscar contratações disponíveis do PCA para o dropdown - todos os anos (2022-2026)
$contratacoes_pca = $pdo->query("
    SELECT DISTINCT
        p.numero_contratacao,
        p.numero_dfd,
        p.titulo_contratacao,
        p.area_requisitante,
        p.valor_total_contratacao,
        pi.ano_pca
    FROM pca_dados p
    INNER JOIN pca_importacoes pi ON p.importacao_id = pi.id
    WHERE p.numero_contratacao IS NOT NULL
    AND p.numero_contratacao != ''
    AND TRIM(p.numero_contratacao) != ''
    AND pi.ano_pca IN (2022, 2023, 2024, 2025, 2026)
    ORDER BY pi.ano_pca DESC, p.numero_contratacao ASC
    LIMIT 2000
")->fetchAll(PDO::FETCH_ASSOC);

// Sistema carregado
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Licitações - Sistema CGLIC</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/licitacao-dashboard.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
    /* Garantir que modais funcionem */
    .modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        overflow: auto;
    }
    
    .modal.show {
        display: block !important;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 20px 15px 20px;
        border-bottom: 1px solid #e5e7eb;
        background: #f8f9fa;
        border-radius: 8px 8px 0 0;
    }
    
    .modal-body {
        padding: 20px;
        max-height: calc(90vh - 120px);
        overflow-y: auto;
    }
    
    .close {
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        line-height: 1;
        background: none;
        border: none;
        padding: 0;
        margin: 0;
    }
    
    .close:hover,
    .close:focus {
        color: #000;
        text-decoration: none;
    }
    
    .modal-content {
        position: relative;
        background-color: #fefefe;
        margin: 5% auto;
        padding: 0;
        border: none;
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        animation: modalFadeIn 0.3s;
        overflow: hidden;
    }
    
    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(-50px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Animação de spinner */
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .search-input {
        width: 100% !important;
        padding: 12px 16px !important;
        border: 2px solid #e5e7eb !important;
        border-radius: 8px !important;
        font-size: 14px !important;
        font-family: inherit !important;
        transition: all 0.2s ease !important;
        background: white !important;
        color: #374151 !important;
        outline: none !important;
    }

    .search-input:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        transform: translateY(-1px) !important;
    }

    .search-input:hover {
        border-color: #d1d5db !important;
    }

    .search-suggestions {
        position: absolute !important;
        top: 100% !important;
        left: 0 !important;
        right: 0 !important;
        background: white !important;
        border: 2px solid #e5e7eb !important;
        border-top: none !important;
        border-radius: 0 0 8px 8px !important;
        max-height: 280px !important;
        overflow-y: auto !important;
        z-index: 1000 !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        margin-top: -1px !important;
    }

    .suggestion-item {
        padding: 12px 16px !important;
        border-bottom: 1px solid #f3f4f6 !important;
        cursor: pointer !important;
        transition: background 0.15s ease !important;
        font-size: 14px !important;
    }

    .suggestion-item:hover {
        background: #f8fafc !important;
    }

    .suggestion-item:last-child {
        border-bottom: none !important;
    }

    .suggestion-numero {
        font-weight: 600 !important;
        color: #1f2937 !important;
        margin-bottom: 4px !important;
    }

    .suggestion-titulo {
        font-size: 12px !important;
        color: #6b7280 !important;
        line-height: 1.4 !important;
    }

    .no-results {
        padding: 16px !important;
        text-align: center !important;
        color: #9ca3af !important;
        font-style: italic !important;
        font-size: 14px !important;
    }
        /* Estilos para detalhes */
        .detalhes-licitacao {
            font-family: inherit;
        }
        
        .detail-section {
            margin-bottom: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .detail-section h4 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 16px;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 8px;
        }
        
        .detail-section p {
            margin: 8px 0;
            color: #6c757d;
            line-height: 1.5;
        }
        
        .detail-section strong {
            color: #495057;
            font-weight: 600;
        }
        
        /* Estilos para paginação */
        .pagination {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            color: #495057;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .page-link:hover {
            background: #e9ecef;
            border-color: #adb5bd;
            color: #495057;
            text-decoration: none;
        }
        
        .page-link.active {
            background: #007cba;
            border-color: #007cba;
            color: white;
        }
        
        .page-link.active:hover {
            background: #006ba6;
            border-color: #006ba6;
        }
        
        /* Estilos para o modal de Ver Andamentos */
        .modal.modern-modal .btn-report {
            background: rgba(34, 197, 94, 0.1);
            border: 2px solid rgb(34, 197, 94);
            color: rgb(34, 197, 94);
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .modal.modern-modal .btn-report:hover {
            background: rgba(34, 197, 94, 0.2);
            border-color: rgb(22, 163, 74);
            color: rgb(22, 163, 74);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }
        
        .modal.modern-modal .btn-report:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(34, 197, 94, 0.2);
        }
        
        .modal.modern-modal .close-button {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid rgb(239, 68, 68);
            color: rgb(239, 68, 68);
            padding: 8px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            transition: all 0.3s ease;
        }
        
        .modal.modern-modal .close-button:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgb(220, 38, 38);
            color: rgb(220, 38, 38);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .modal.modern-modal .close-button:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.2);
        }
        
        .modal.modern-modal .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        /* Header discreto para o modal de Ver Andamentos */
        .modal.modern-modal .modal-header.gradient-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 20px 25px;
            border-radius: 8px 8px 0 0;
        }
        
        .modal.modern-modal .header-info {
            flex: 1;
        }
        
        .modal.modern-modal .modal-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal.modern-modal .modal-subtitle {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: #6c757d;
            font-weight: 500;
        }

        /* ==================== TIMELINE MELHORADA ==================== */
        
        /* Container da Timeline Melhorada */
        .timeline-container-improved {
            flex: 1;
            background: #f8fafc;
            position: relative;
            max-height: 600px;
            overflow-y: auto;
            padding: 20px;
        }

        /* Timeline Compacta - Estilos Base */
        .timeline-compact {
            position: relative;
            padding-left: 30px;
        }

        .timeline-compact::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #3b82f6, #06b6d4, #10b981);
            border-radius: 2px;
        }

        /* Item da Timeline Compacta */
        .timeline-item-compact {
            position: relative;
            margin-bottom: 16px;
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 3px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .timeline-item-compact:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
            border-left-color: #3b82f6;
        }

        .timeline-item-compact::before {
            content: '';
            position: absolute;
            left: -33px;
            top: 20px;
            width: 12px;
            height: 12px;
            background: white;
            border: 3px solid #3b82f6;
            border-radius: 50%;
            z-index: 2;
        }

        /* Estados Especiais */
        .timeline-item-compact.important::before {
            border-color: #f59e0b;
            background: #fef3c7;
        }

        .timeline-item-compact.success::before {
            border-color: #10b981;
            background: #d1fae5;
        }

        .timeline-item-compact.error::before {
            border-color: #ef4444;
            background: #fee2e2;
        }

        /* Header do Item */
        .timeline-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .timeline-item-title {
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
            margin: 0;
        }

        .timeline-item-date {
            font-size: 12px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Conteúdo do Item */
        .timeline-item-content {
            color: #4b5563;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 8px;
        }

        /* Meta informações */
        .timeline-item-meta {
            display: flex;
            gap: 12px;
            font-size: 11px;
            color: #9ca3af;
        }

        .timeline-meta-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .timeline-meta-item i {
            width: 12px;
            height: 12px;
        }

        /* Estados de Loading */
        .timeline-loading {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }

        .timeline-empty {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .timeline-empty i {
            width: 48px;
            height: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        /* Botão Carregar Mais */
        .load-more-timeline {
            width: 100%;
            padding: 12px 24px;
            background: linear-gradient(135deg, #3b82f6, #06b6d4);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .load-more-timeline:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.3);
        }

        .load-more-timeline:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Filtros da Timeline */
        .timeline-filters-compact {
            background: white;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #e5e7eb;
        }

        .timeline-filters-compact.collapsed {
            padding: 8px 16px;
        }

        .filters-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }

        .filters-content {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .filter-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-field label {
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
        }

        .filter-field input,
        .filter-field select {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            transition: border-color 0.2s ease;
        }

        .filter-field input:focus,
        .filter-field select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Animações */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .timeline-item-compact {
            animation: fadeInUp 0.4s ease-out forwards;
            opacity: 0;
        }

        .timeline-filters-compact {
            transition: all 0.3s ease;
        }

        .timeline-filters-compact.collapsed .filters-content {
            display: none;
        }

        .filters-toggle i:last-child {
            transition: transform 0.3s ease;
        }

        .timeline-filters-compact.collapsed .filters-toggle i:last-child {
            transform: rotate(-90deg);
        }

        /* Scrollbar customizada */
        .timeline-container-improved::-webkit-scrollbar {
            width: 8px;
        }

        .timeline-container-improved::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .timeline-container-improved::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .timeline-container-improved::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .timeline-container-improved {
                max-height: 500px;
                padding: 16px;
            }

            .timeline-compact {
                padding-left: 20px;
            }

            .timeline-item-compact::before {
                left: -23px;
            }

            .timeline-filters-compact .filters-content {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }

        /* CSS para Cards de Licitações */
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 24px;
            padding: 20px 0;
        }

        .licitacao-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .licitacao-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
            border-color: #059669;
        }

        .licitacao-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #059669, #10b981);
        }

        .licitacao-card.status-homologado::before {
            background: linear-gradient(90deg, #059669, #10b981);
        }

        .licitacao-card.status-em-andamento::before {
            background: linear-gradient(90deg, #f59e0b, #d97706);
        }

        .licitacao-card.status-fracassado::before {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }

        .licitacao-card.status-revogado::before {
            background: linear-gradient(90deg, #6b7280, #4b5563);
        }

        .licitacao-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .licitacao-card-nup {
            font-size: 16px;
            font-weight: 700;
            color: #059669;
            margin-bottom: 4px;
        }

        .licitacao-card-numero {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }

        .licitacao-card-modalidade {
            background: #e3f2fd;
            color: #1976d2;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .licitacao-card-body {
            margin-bottom: 20px;
        }

        .licitacao-card-objeto {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .licitacao-card-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .licitacao-card-info-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .licitacao-card-info-label {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .licitacao-card-info-value {
            font-size: 14px;
            font-weight: 500;
            color: #374151;
        }

        .licitacao-card-valor {
            font-size: 18px;
            font-weight: 700;
            color: #059669;
        }

        .licitacao-card-andamentos {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e8f5e8;
            color: #2e7d32;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .licitacao-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid #f3f4f6;
        }

        .licitacao-card-status {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .licitacao-card-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .licitacao-card-action-btn {
            width: auto;
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            transition: all 0.3s ease;
            font-size: 11px;
            font-weight: 500;
            position: relative;
        }

        .licitacao-card-action-btn .btn-label {
            display: none;
            white-space: nowrap;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            top: -35px;
            background: #333;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            z-index: 1000;
            pointer-events: none;
        }

        .licitacao-card-action-btn .btn-label::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 4px solid transparent;
            border-right: 4px solid transparent;
            border-top: 4px solid #333;
        }

        .licitacao-card-action-btn:hover .btn-label {
            display: inline;
        }

        .licitacao-card-action-btn:hover {
            /* Removido transform: scale para evitar tremor */
            opacity: 0.8;
        }

        .btn-view-card {
            background: #f3f4f6;
            color: #6c757d;
        }

        .btn-view-card:hover {
            background: #6c757d;
            color: white;
        }

        .btn-edit-card {
            background: #fff3e0;
            color: #f57c00;
        }

        .btn-edit-card:hover {
            background: #f39c12;
            color: white;
        }

        .btn-delete-card {
            background: #ffebee;
            color: #d32f2f;
        }

        .btn-delete-card:hover {
            background: #e74c3c;
            color: white;
        }

        .btn-upload-card {
            background: #e3f2fd;
            color: #1976d2;
        }

        .btn-upload-card:hover {
            background: #3498db;
            color: white;
        }

        .btn-clock-card {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .btn-clock-card:hover {
            background: #27ae60;
            color: white;
        }

        .licitacao-empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #6b7280;
            grid-column: 1 / -1;
        }

        .licitacao-empty-state-icon {
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .readonly-indicator {
            color: #7f8c8d;
            font-size: 10px;
            font-style: italic;
            margin-top: 4px;
        }

        @media (max-width: 1024px) {
            .cards-container {
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .cards-container {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .licitacao-card {
                padding: 20px;
            }
            
            .licitacao-card-info-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .licitacao-card-actions {
                gap: 6px;
            }

            .licitacao-card-action-btn {
                min-width: 28px;
                height: 28px;
                padding: 0 6px;
            }

            .licitacao-card-action-btn .btn-label {
                /* Tooltips desabilitados no mobile por espaço */
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
                    <i data-lucide="menu"></i>
                </button>
                <h2><i data-lucide="gavel"></i> Licitações</h2>
            </div>

            <nav class="sidebar-nav">
    <div class="nav-section">
        <div class="nav-section-title">Visão Geral</div>
        <button class="nav-item <?php echo $secao_ativa === 'dashboard' ? 'active' : ''; ?>" onclick="showSection('dashboard')">
            <i data-lucide="bar-chart-3"></i> <span>Dashboard</span>
        </button>
    </div>

    <div class="nav-section">
        <div class="nav-section-title">Gerenciar</div>
        <button class="nav-item <?php echo $secao_ativa === 'lista-licitacoes' ? 'active' : ''; ?>" onclick="showSection('lista-licitacoes')">
            <i data-lucide="list"></i> <span>Lista de Licitações</span>
        </button>
        <?php if (isVisitante()): ?>
        <div style="margin: 10px 15px; padding: 8px; background: #fff3cd; border-radius: 6px; border-left: 3px solid #f39c12;">
            <small style="color: #856404; font-size: 11px; font-weight: 600;">
                <i data-lucide="eye" style="width: 12px; height: 12px;"></i> MODO VISITANTE<br>
                Somente visualização e exportação
            </small>
        </div>
        <?php endif; ?>
    </div>

    <?php if (temPermissao('licitacao_relatorios')): ?>
    <div class="nav-section">
        <div class="nav-section-title">Relatórios</div>
        <button class="nav-item <?php echo $secao_ativa === 'relatorios' ? 'active' : ''; ?>" onclick="showSection('relatorios')">
            <i data-lucide="file-text"></i> <span>Relatórios</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Navegação Geral -->
    <div class="nav-section">
        <div class="nav-section-title">Sistema</div>
        <a href="selecao_modulos.php" class="nav-item">
            <i data-lucide="home"></i>
            <span>Menu Principal</span>
        </a>
        <a href="dashboard.php" class="nav-item">
            <i data-lucide="calendar-check"></i>
            <span>Planejamento</span>
        </a>
        <a href="qualificacao_dashboard.php" class="nav-item">
            <i data-lucide="award"></i>
            <span>Qualificações</span>
        </a>
    </div>
</nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['usuario_nome'], 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <h4><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></h4>
                        <p><?php echo htmlspecialchars($_SESSION['usuario_email']); ?></p>
                        <small style="color: #3498db; font-weight: 600;">
                            <?php echo getNomeNivel($_SESSION['usuario_nivel'] ?? 3); ?> - <?php echo htmlspecialchars($_SESSION['usuario_departamento'] ?? ''); ?>
                        </small>
                        <?php if (isVisitante()): ?>
                        <small style="color: #f39c12; font-weight: 600; display: block; margin-top: 4px;">
                            <i data-lucide="eye" style="width: 12px; height: 12px;"></i> Modo Somente Leitura
                        </small>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="perfil_usuario.php" class="logout-btn" style="text-decoration: none; margin-bottom: 10px; background: #27ae60 !important;">
                    <i data-lucide="user"></i> <span>Meu Perfil</span>
                </a>
                <button class="logout-btn" onclick="window.location.href='logout.php'">
                    <i data-lucide="log-out"></i> <span>Sair</span>
                </button>
            </div>
        </div>

        <main class="main-content" id="mainContent">
            <?php echo getMensagem(); ?>

            <div id="dashboard" class="content-section <?php echo $secao_ativa === 'dashboard' ? 'active' : ''; ?>">
                <div class="dashboard-header">
                    <h1><i data-lucide="bar-chart-3"></i> Painel de Licitações</h1>
                    <p>Visão geral do processo licitatório e indicadores de desempenho</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-number"><?php echo number_format($stats['total_licitacoes'] ?? 0); ?></div>
                        <div class="stat-label">Total de Licitações</div>
                    </div>
                    <div class="stat-card andamento">
                        <div class="stat-number"><?php echo $stats['em_andamento'] ?? 0; ?></div>
                        <div class="stat-label">Em Andamento</div>
                    </div>
                    <div class="stat-card homologadas">
                        <div class="stat-number"><?php echo $stats['homologadas'] ?? 0; ?></div>
                        <div class="stat-label">Homologadas</div>
                    </div>
                    <div class="stat-card fracassadas">
                        <div class="stat-number"><?php echo $stats['fracassadas'] ?? 0; ?></div>
                        <div class="stat-label">Fracassadas</div>
                    </div>
                    <div class="stat-card valor">
                        <div class="stat-number"><?php echo abreviarValor($stats['valor_homologado'] ?? 0); ?></div>
                        <div class="stat-label">Valor Homologado</div>
                    </div>
                </div>

                <div class="charts-grid">
    <div class="chart-card">
        <h3 class="chart-title"><i data-lucide="pie-chart"></i> Licitações por Modalidade</h3>
        <div class="chart-container">
            <canvas id="chartModalidade"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <h3 class="chart-title"><i data-lucide="users"></i> Licitações por Pregoeiro</h3>
        <div class="chart-container">
            <canvas id="chartPregoeiro"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <h3 class="chart-title"><i data-lucide="trending-up"></i> Evolução Mensal</h3>
        <div class="chart-container">
            <canvas id="chartMensal"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <h3 class="chart-title"><i data-lucide="activity"></i> Status das Licitações</h3>
        <div class="chart-container">
            <canvas id="chartStatus"></canvas>
        </div>
    </div>
</div>
            </div>

            <div id="lista-licitacoes" class="content-section <?php echo $secao_ativa === 'lista-licitacoes' ? 'active' : ''; ?>">
    <div class="dashboard-header">
        <h1><i data-lucide="list"></i> Lista de Licitações</h1>
        <p>Visualize e gerencie todas as licitações cadastradas</p>
    </div>

    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">Todas as Licitações</h3>
            
            
            <div class="table-filters">
                <?php if (temPermissao('licitacao_criar')): ?>
                <button onclick="abrirModalCriarLicitacao()" class="btn-primary" style="margin-right: 10px;">
                    <i data-lucide="plus-circle"></i> Nova Licitação
                </button>
                <?php endif; ?>
                <?php if (temPermissao('licitacao_exportar')): ?>
                <button onclick="exportarLicitacoes()" class="btn-primary">
                    <i data-lucide="download"></i> Exportar
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filtros e Busca -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <form id="formFiltrosLicitacao" method="GET" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #495057;">Buscar</label>
                    <input type="text" name="busca" value="<?php echo htmlspecialchars($filtro_busca); ?>" 
                           placeholder="NUP, objeto, pregoeiro ou nº contratação..." 
                           style="width: 100%; padding: 8px 12px; border: 1px solid #dee2e6; border-radius: 4px;">
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #495057;">Situação</label>
                    <select name="situacao_filtro" style="width: 100%; padding: 8px 12px; border: 1px solid #dee2e6; border-radius: 4px;">
                        <option value="">Todas as Situações</option>
                        <option value="EM_ANDAMENTO" <?php echo $filtro_situacao === 'EM_ANDAMENTO' ? 'selected' : ''; ?>>Em Andamento</option>
                        <option value="HOMOLOGADO" <?php echo $filtro_situacao === 'HOMOLOGADO' ? 'selected' : ''; ?>>Homologadas</option>
                        <option value="FRACASSADO" <?php echo $filtro_situacao === 'FRACASSADO' ? 'selected' : ''; ?>>Fracassadas</option>
                        <option value="REVOGADO" <?php echo $filtro_situacao === 'REVOGADO' ? 'selected' : ''; ?>>Revogadas</option>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #495057;">Ano de Abertura</label>
                    <select name="ano_filtro" style="width: 100%; padding: 8px 12px; border: 1px solid #dee2e6; border-radius: 4px;">
                        <option value="">Todos os Anos</option>
                        <?php foreach ($anos_disponiveis as $ano): ?>
                            <option value="<?php echo $ano; ?>" <?php echo $filtro_ano == $ano ? 'selected' : ''; ?>>
                                <?php echo $ano; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-primary" style="padding: 8px 16px;">
                        <i data-lucide="search"></i> Filtrar
                    </button>
                    <a href="licitacao_dashboard.php" class="btn-secondary" style="padding: 8px 16px; text-decoration: none;">
                        <i data-lucide="x"></i> Limpar
                    </a>
                </div>
                
                <!-- Campo oculto para preservar o valor de por_pagina -->
                <input type="hidden" name="por_pagina" value="<?php echo $licitacoes_por_pagina; ?>">
            </form>
        </div>

        <div id="resultadosLicitacoes">
        <?php if (empty($licitacoes_recentes)): ?>
            <div class="cards-container">
                <div class="licitacao-empty-state">
                    <div class="licitacao-empty-state-icon">
                        <i data-lucide="inbox" style="width: 64px; height: 64px;"></i>
                    </div>
                    <h3>Nenhuma licitação encontrada</h3>
                    <p>Não há licitações cadastradas ou que correspondam aos filtros aplicados.</p>
                    <?php if (temPermissao('licitacao_criar')): ?>
                    <button onclick="abrirModalCriarLicitacao()" class="btn-primary" style="margin-top: 20px;">
                        <i data-lucide="plus-circle"></i> Criar Primeira Licitação
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Cards Container -->
            <div class="cards-container">
                <?php 
                $contador_linha = 0;
                foreach ($licitacoes_recentes as $licitacao): 
                    $contador_linha++;
                    
                    // Definir classe do status para o card
                    $status_class = strtolower(str_replace('_', '-', $licitacao['situacao']));
                ?>
                    <div class="licitacao-card status-<?php echo $status_class; ?>">
                        <!-- Card Header -->
                        <div class="licitacao-card-header">
                            <div>
                                <div class="licitacao-card-nup"><?php echo htmlspecialchars($licitacao['nup']); ?></div>
                                <div class="licitacao-card-numero">
                                    Nº <?php echo htmlspecialchars($licitacao['numero_contratacao_final'] ?? $licitacao['numero_contratacao'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="licitacao-card-modalidade">
                                <?php echo htmlspecialchars($licitacao['modalidade']); ?>
                            </div>
                        </div>
                        
                        <!-- Card Body -->
                        <div class="licitacao-card-body">
                            <div class="licitacao-card-objeto" title="<?php echo htmlspecialchars($licitacao['objeto'] ?? ''); ?>">
                                <?php echo htmlspecialchars($licitacao['objeto'] ?? ''); ?>
                            </div>
                            
                            <div class="licitacao-card-info-grid">
                                <div class="licitacao-card-info-item">
                                    <span class="licitacao-card-info-label">Valor Estimado</span>
                                    <span class="licitacao-card-info-value licitacao-card-valor"><?php echo formatarMoeda($licitacao['valor_estimado'] ?? 0); ?></span>
                                </div>
                                <div class="licitacao-card-info-item">
                                    <span class="licitacao-card-info-label">Data Abertura</span>
                                    <span class="licitacao-card-info-value"><?php echo $licitacao['data_abertura'] ? formatarData($licitacao['data_abertura']) : 'Não definida'; ?></span>
                                </div>
                                <div class="licitacao-card-info-item">
                                    <span class="licitacao-card-info-label">Pregoeiro</span>
                                    <span class="licitacao-card-info-value"><?php echo htmlspecialchars($licitacao['pregoeiro'] ?: 'Não definido'); ?></span>
                                </div>
                                <div class="licitacao-card-info-item">
                                    <span class="licitacao-card-info-label">Andamentos</span>
                                    <span class="licitacao-card-info-value">
                                        <?php if ($licitacao['total_andamentos'] > 0): ?>
                                            <span class="licitacao-card-andamentos">
                                                <i data-lucide="activity" style="width: 12px; height: 12px;"></i>
                                                <?php echo $licitacao['total_andamentos']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">Nenhum</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Informações da Qualificação Vinculada -->
                            <?php if (isset($licitacao['qualificacao_id']) && $licitacao['qualificacao_id'] > 0 && isset($licitacao['vinculacao_ativa']) && $licitacao['vinculacao_ativa'] === 'ATIVA'): ?>
                            <div class="licitacao-card-qualificacao" style="margin-top: 10px; padding: 8px; background: #f8f9fa; border-radius: 6px; border-left: 3px solid #28a745;">
                                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                    <i data-lucide="link" style="width: 14px; height: 14px; color: #28a745;"></i>
                                    <span style="font-size: 12px; font-weight: 600; color: #28a745;">Qualificação Vinculada</span>
                                    <small style="margin-left: auto; color: #9ca3af;">
                                        ID: <?php echo $licitacao['qualificacao_id']; ?>
                                    </small>
                                </div>
                                <div style="font-size: 11px; color: #6c757d;">
                                    <div><strong>NUP:</strong> <?php echo htmlspecialchars($licitacao['qualificacao_nup'] ?? 'N/A'); ?></div>
                                    <div><strong>Área:</strong> <?php echo htmlspecialchars($licitacao['qualificacao_area'] ?? 'N/A'); ?></div>
                                    <div><strong>Responsável:</strong> <?php echo htmlspecialchars($licitacao['qualificacao_responsavel'] ?? 'N/A'); ?></div>
                                    <div><strong>Status:</strong>
                                        <span class="badge-mini status-<?php echo strtolower(str_replace(' ', '_', $licitacao['qualificacao_status'] ?? '')); ?>">
                                            <?php echo htmlspecialchars($licitacao['qualificacao_status'] ?? 'N/A'); ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($licitacao['pca_numero_contratacao'])): ?>
                                    <div><strong>PCA:</strong> <?php echo htmlspecialchars($licitacao['pca_numero_contratacao']); ?></div>
                                    <?php endif; ?>
                                    <div style="margin-top: 4px; font-size: 10px; color: #9ca3af;">
                                        <strong>Vinculada em:</strong> <?php echo date('d/m/Y H:i', strtotime($licitacao['vinculacao_criada_em'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="licitacao-card-sem-qualificacao" style="margin-top: 10px; padding: 8px; background: #fff3cd; border-radius: 6px; border-left: 3px solid #ffc107;">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="alert-triangle" style="width: 14px; height: 14px; color: #ffc107;"></i>
                                    <span style="font-size: 12px; color: #856404;">Sem vinculação com qualificação</span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Card Footer -->
                        <div class="licitacao-card-footer">
                            <div class="licitacao-card-status">
                                <span class="status-badge status-<?php echo $status_class; ?>">
                                    <?php echo str_replace('_', ' ', $licitacao['situacao']); ?>
                                </span>
                            </div>
                            
                            <div class="licitacao-card-actions">
                                <!-- Botão Ver Detalhes (sempre visível) -->
                                <button onclick="verDetalhes(<?php echo $licitacao['id']; ?>)" title="Ver Detalhes" class="licitacao-card-action-btn btn-view-card">
                                    <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                                    <span class="btn-label">Detalhes</span>
                                </button>

                                <?php if (temPermissao('licitacao_editar')): ?>
                                    <button onclick="editarLicitacao(<?php echo $licitacao['id']; ?>)" title="Editar Licitação" class="licitacao-card-action-btn btn-edit-card">
                                        <i data-lucide="edit" style="width: 14px; height: 14px;"></i>
                                        <span class="btn-label">Editar</span>
                                    </button>

                                    <?php if (temPermissao('licitacao_excluir')): ?>
                                        <button onclick="excluirLicitacao(<?php echo $licitacao['id']; ?>, '<?php echo htmlspecialchars($licitacao['nup']); ?>')" title="Excluir Licitação" class="licitacao-card-action-btn btn-delete-card">
                                            <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                            <span class="btn-label">Excluir</span>
                                        </button>
                                    <?php endif; ?>

                                    <button onclick="abrirModalImportarAndamentos('<?php echo htmlspecialchars($licitacao['nup']); ?>')" title="Importar Andamentos" class="licitacao-card-action-btn btn-upload-card">
                                        <i data-lucide="upload" style="width: 14px; height: 14px;"></i>
                                        <span class="btn-label">Importar</span>
                                    </button>

                                    <button onclick="consultarAndamentos('<?php echo htmlspecialchars($licitacao['nup']); ?>')" title="Ver Andamentos" class="licitacao-card-action-btn btn-clock-card">
                                        <i data-lucide="clock" style="width: 14px; height: 14px;"></i>
                                        <span class="btn-label">Andamentos</span>
                                    </button>
                                <?php else: ?>
                                    <button onclick="consultarAndamentos('<?php echo htmlspecialchars($licitacao['nup']); ?>')" title="Ver Andamentos" class="licitacao-card-action-btn btn-clock-card">
                                        <i data-lucide="clock" style="width: 14px; height: 14px;"></i>
                                        <span class="btn-label">Andamentos</span>
                                    </button>
                                    <div class="readonly-indicator">
                                        <i data-lucide="eye" style="width: 10px; height: 10px;"></i>
                                        Somente leitura
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Informações de Paginação -->
            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div style="color: #7f8c8d; font-size: 14px;">
                        <?php 
                        $inicio = ($pagina_atual - 1) * $licitacoes_por_pagina + 1;
                        $fim = min($pagina_atual * $licitacoes_por_pagina, $total_licitacoes);
                        ?>
                        Mostrando <?php echo $inicio; ?> a <?php echo $fim; ?> de <?php echo $total_licitacoes; ?> licitações<br>
                        Valor total estimado (página atual): <?php echo formatarMoeda(array_sum(array_column($licitacoes_recentes, 'valor_estimado'))); ?>
                    </div>
                    
                    <!-- Seletor de itens por página -->
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <label style="font-size: 14px; color: #495057; font-weight: 600;">Itens por página:</label>
                        <select onchange="alterarItensPorPagina(this.value)" style="padding: 6px 8px; border: 1px solid #dee2e6; border-radius: 4px; font-size: 14px;">
                            <option value="10" <?php echo $licitacoes_por_pagina == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="25" <?php echo $licitacoes_por_pagina == 25 ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo $licitacoes_por_pagina == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $licitacoes_por_pagina == 100 ? 'selected' : ''; ?>>100</option>
                            <option value="500" <?php echo $licitacoes_por_pagina == 500 ? 'selected' : ''; ?>>500</option>
                        </select>
                    </div>
                </div>
                
                <?php if ($total_paginas > 1): ?>
                <div class="pagination">
                    <?php
                    // Construir URL base preservando filtros e seção ativa
                    $url_params = [];
                    if (!empty($filtro_busca)) $url_params['busca'] = $filtro_busca;
                    if (!empty($filtro_situacao)) $url_params['situacao_filtro'] = $filtro_situacao;
                    if (!empty($filtro_ano)) $url_params['ano_filtro'] = $filtro_ano;
                    if ($licitacoes_por_pagina != 10) $url_params['por_pagina'] = $licitacoes_por_pagina;
                    $url_params['secao'] = $secao_ativa; // Manter seção ativa na paginação
                    $url_base = 'licitacao_dashboard.php?' . http_build_query($url_params);
                    $url_base .= empty($url_params) ? '?' : '&';
                    ?>
                    
                    <!-- Primeira página -->
                    <?php if ($pagina_atual > 1): ?>
                        <a href="<?php echo $url_base; ?>pagina=1" class="page-link">
                            <i data-lucide="chevrons-left"></i>
                        </a>
                        <a href="<?php echo $url_base; ?>pagina=<?php echo $pagina_atual - 1; ?>" class="page-link">
                            <i data-lucide="chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <!-- Páginas numeradas -->
                    <?php
                    $inicio_pag = max(1, $pagina_atual - 2);
                    $fim_pag = min($total_paginas, $pagina_atual + 2);
                    
                    for ($i = $inicio_pag; $i <= $fim_pag; $i++):
                    ?>
                        <a href="<?php echo $url_base; ?>pagina=<?php echo $i; ?>" 
                           class="page-link <?php echo $i == $pagina_atual ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <!-- Última página -->
                    <?php if ($pagina_atual < $total_paginas): ?>
                        <a href="<?php echo $url_base; ?>pagina=<?php echo $pagina_atual + 1; ?>" class="page-link">
                            <i data-lucide="chevron-right"></i>
                        </a>
                        <a href="<?php echo $url_base; ?>pagina=<?php echo $total_paginas; ?>" class="page-link">
                            <i data-lucide="chevrons-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        </div> <!-- fim resultadosLicitacoes -->
    </div>
</div>

<div id="modalCriarLicitacao" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3 id="modalLicitacaoTitulo" style="margin: 0; display: flex; align-items: center; gap: 10px;">
                <i id="modalLicitacaoIcon" data-lucide="plus-circle"></i>
                <span id="modalLicitacaoTituloTexto">Criar Nova Licitação</span>
            </h3>
            <span class="close" onclick="fecharModalSimples('modalCriarLicitacao')">&times;</span>
        </div>
        <div class="modal-body">
            <!-- Sistema de Abas -->
            <div class="tabs-container">
                <div class="tabs-header">
                    <button type="button" class="tab-button active" onclick="mostrarAba('vinculacao-qualificacao')">
                        <i data-lucide="link"></i> Vinculação Qualificação
                    </button>
                    <button type="button" class="tab-button" onclick="mostrarAba('informacoes-gerais')">
                        <i data-lucide="info"></i> Informações Gerais
                    </button>
                    <button type="button" class="tab-button" onclick="mostrarAba('prazos-datas')">
                        <i data-lucide="clock"></i> Prazos e Datas
                    </button>
                    <button type="button" class="tab-button" onclick="mostrarAba('valores-financeiro')">
                        <i data-lucide="wallet"></i> Valores e Financeiro
                    </button>
                    <button type="button" class="tab-button" onclick="mostrarAba('responsaveis')">
                        <i data-lucide="users"></i> Responsáveis
                    </button>
                </div>

                <form action="process.php" method="POST" id="formLicitacao">
                    <input type="hidden" name="acao" id="licitacao_form_acao" value="criar_licitacao">
                    <input type="hidden" name="id" id="licitacao_id">
                    <?php echo getCSRFInput(); ?>

                    <!-- Aba 1: Vinculação Qualificação -->
                    <div id="aba-vinculacao-qualificacao" class="tab-content active">
                        <h4 style="margin: 0 0 15px 0; color: #2c3e50; border-bottom: 2px solid #e9ecef; padding-bottom: 8px;">
                            <i data-lucide="link"></i> Vinculação com Qualificação
                        </h4>
                        <div class="form-grid">
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label>Qualificação *</label>
                                <div class="search-container" style="position: relative;">
                                    <input
                                        type="text"
                                        name="busca_qualificacao"
                                        id="input_qualificacao"
                                        required
                                        placeholder="Digite o NUP, área ou objeto da qualificação..."
                                        autocomplete="off"
                                        class="search-input"
                                        oninput="pesquisarQualificacaoInline(this.value)"
                                        onfocus="mostrarSugestoesQualificacao()"
                                        onblur="ocultarSugestoesQualificacao()"
                                    >
                                    <div id="sugestoes_qualificacao" class="search-suggestions" style="display: none;">
                                    </div>
                                </div>

                                <input type="hidden" id="qualificacao_id_selecionada" name="qualificacao_id">
                                <input type="hidden" id="qualificacao_nup_selecionado" name="qualificacao_nup">

                                <small style="color: #6b7280; font-size: 12px; margin-top: 5px; display: block;">
                                    <i data-lucide="info" style="width: 12px; height: 12px;"></i>
                                    Digite o NUP, área demandante ou parte do objeto para pesquisar qualificações aprovadas
                                </small>
                            </div>

                            <div id="info_qualificacao_selecionada" style="grid-column: 1 / -1; display: none; background: #e8f5e9; padding: 15px; border-radius: 8px; margin-top: 10px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <h5 style="margin: 0; color: #388e3c;">
                                        <i data-lucide="check-circle"></i> Qualificação Selecionada
                                    </h5>
                                    <button type="button" onclick="limparCamposAutoPreenchidos()"
                                            style="background: #fff; border: 1px solid #4caf50; color: #4caf50; padding: 6px 12px; border-radius: 6px; font-size: 12px; cursor: pointer;"
                                            title="Limpar vinculação e campos preenchidos">
                                        <i data-lucide="x-circle" style="width: 14px; height: 14px;"></i> Desvincular
                                    </button>
                                </div>
                                <div id="detalhes_qualificacao"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Aba 2: Informações Gerais -->
                    <div id="aba-informacoes-gerais" class="tab-content">
                        <h4 style="margin: 0 0 15px 0; color: #2c3e50; border-bottom: 2px solid #e9ecef; padding-bottom: 8px;">
                            <i data-lucide="file"></i> Informações Básicas
                        </h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>NUP *</label>
                                <input type="text" name="nup" id="nup_criar" required placeholder="xxxxx.xxxxxx/xxxx-xx" maxlength="20">
                            </div>

                            <div class="form-group">
                                <label>Modalidade *</label>
                                <select name="modalidade" required>
                                    <option value="">Selecione a modalidade</option>
                                    <option value="DISPENSA">DISPENSA</option>
                                    <option value="PREGAO">PREGÃO</option>
                                    <option value="RDC">RDC</option>
                                    <option value="INEXIGIBILIDADE">INEXIGIBILIDADE</option>
                                    <option value="CONCORRÊNCIA">CONCORRÊNCIA</option>
                                    <option value="DIÁLOGO COMPETITIVO">DIÁLOGO COMPETITIVO</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Tipo *</label>
                                <select name="tipo" required>
                                    <option value="">Selecione o tipo</option>
                                    <option value="TRADICIONAL">TRADICIONAL</option>
                                    <option value="COTACAO">COTAÇÃO</option>
                                    <option value="SRP">SRP</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Número da Contratação</label>
                                <div class="search-container" style="position: relative;">
                                    <input
                                        type="text"
                                        name="numero_contratacao"
                                        id="input_contratacao"
                                        placeholder="Digite o número da contratação..."
                                        autocomplete="off"
                                        class="search-input"
                                        oninput="pesquisarContratacaoInline(this.value)"
                                        onfocus="mostrarSugestoesInline()"
                                        onblur="ocultarSugestoesInline()"
                                    >
                                    <div id="sugestoes_contratacao" class="search-suggestions" style="display: none;"></div>
                                </div>
                                <input type="hidden" id="numero_dfd_selecionado" name="numero_dfd">
                                <input type="hidden" id="titulo_contratacao_selecionado" name="titulo_contratacao">
                                <small style="color: #6b7280; font-size: 12px;">
                                    Digite o número da contratação ou parte do título para pesquisar
                                </small>
                            </div>

                            <div class="form-group">
                                <label>Ano</label>
                                <input type="number" name="ano" value="<?php echo date('Y'); ?>" min="2020" max="2030">
                            </div>

                            <div class="form-group">
                                <label>Situação *</label>
                                <select name="situacao" required>
                                    <option value="">Selecione a situação</option>
                                    <option value="EM_ANDAMENTO" selected>EM ANDAMENTO</option>
                                    <option value="REVOGADO">REVOGADO</option>
                                    <option value="FRACASSADO">FRACASSADO</option>
                                    <option value="HOMOLOGADO">HOMOLOGADO</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Link (Documentos/Edital)</label>
                                <input type="url" name="link" placeholder="https://...">
                            </div>

                            <div class="form-group form-full">
                                <label>Objeto *</label>
                                <textarea name="objeto" id="objeto_textarea" required rows="4" placeholder="Descreva detalhadamente o objeto da licitação..."></textarea>
                            </div>
                        </div>
                    </div>


                    <!-- Aba 3: Prazos e Datas -->
                    <div id="aba-prazos-datas" class="tab-content">
                        <h4 style="margin: 0 0 15px 0; color: #2c3e50; border-bottom: 2px solid #e9ecef; padding-bottom: 8px;">
                            <i data-lucide="calendar"></i> Cronograma do Processo
                        </h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Data Entrada DIPLI</label>
                                <input type="date" name="data_entrada_dipli">
                                <small style="color: #6b7280; font-size: 12px;">Data de entrada do processo na DIPLI</small>
                            </div>

                            <div class="form-group">
                                <label>Data Abertura</label>
                                <input type="date" name="data_abertura">
                                <small style="color: #6b7280; font-size: 12px;">Data prevista para abertura das propostas</small>
                            </div>

                            <div class="form-group">
                                <label>Data Homologação</label>
                                <input type="date" name="data_homologacao" id="data_homologacao_criar">
                                <small style="color: #6b7280; font-size: 12px;">Data de homologação do resultado</small>
                            </div>

                            <div class="form-group">
                                <label>Data Publicação</label>
                                <input type="date" name="data_publicacao">
                                <small style="color: #6b7280; font-size: 12px;">Data de publicação do edital</small>
                            </div>
                        </div>
                    </div>

                    <!-- Aba 4: Valores e Financeiro -->
                    <div id="aba-valores-financeiro" class="tab-content">
                        <h4 style="margin: 0 0 15px 0; color: #2c3e50; border-bottom: 2px solid #e9ecef; padding-bottom: 8px;">
                            <i data-lucide="banknote"></i> Valores Financeiros
                        </h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Valor Estimado (R$) *</label>
                                <input type="text" name="valor_estimado" id="valor_estimado_criar" placeholder="0,00" required>
                                <small style="color: #6b7280; font-size: 12px;">Valor estimado para a contratação</small>
                            </div>

                            <div class="form-group">
                                <label>Valor Homologado (R$)</label>
                                <input type="text" name="valor_homologado" id="valor_homologado_criar" placeholder="0,00">
                                <small style="color: #6b7280; font-size: 12px;">Valor final homologado</small>
                            </div>

                            <div class="form-group">
                                <label>Economia (R$)</label>
                                <input type="text" name="economia" id="economia_criar" placeholder="0,00" readonly style="background: #f8f9fa;">
                                <small style="color: #6b7280; font-size: 12px;">Calculado automaticamente (Estimado - Homologado)</small>
                            </div>

                            <div class="form-group">
                                <label>Quantidade de Itens</label>
                                <input type="number" name="qtd_itens" min="1" placeholder="1">
                                <small style="color: #6b7280; font-size: 12px;">Número de itens da licitação</small>
                            </div>
                        </div>
                    </div>

                    <!-- Aba 5: Responsáveis -->
                    <div id="aba-responsaveis" class="tab-content">
                        <h4 style="margin: 0 0 15px 0; color: #2c3e50; border-bottom: 2px solid #e9ecef; padding-bottom: 8px;">
                            <i data-lucide="users"></i> Responsáveis pelo Processo
                        </h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Pregoeiro/Responsável</label>
                                <input type="text" name="pregoeiro" placeholder="Nome do pregoeiro">
                                <small style="color: #6b7280; font-size: 12px;">Pregoeiro responsável pela condução</small>
                            </div>

                            <div class="form-group">
                                <label>Responsável Instrução</label>
                                <input type="text" name="resp_instrucao" placeholder="Nome do responsável">
                                <small style="color: #6b7280; font-size: 12px;">Responsável pela instrução do processo</small>
                            </div>

                            <div class="form-group">
                                <label>Área Demandante</label>
                                <input type="text" name="area_demandante" id="area_demandante_criar" placeholder="Área que solicitou">
                                <small style="color: #6b7280; font-size: 12px;">Área que demandou a licitação</small>
                            </div>

                            <div class="form-group">
                                <label>Observações</label>
                                <textarea name="observacoes" rows="3" placeholder="Observações gerais sobre responsabilidades..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Botões de Ação -->
                    <div style="margin-top: 20px; display: flex; gap: 15px; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 2px solid #e9ecef;">
                        <div class="tab-navigation">
                            <button type="button" id="btn-anterior" onclick="abaAnterior()" class="btn-secondary" style="display: none;">
                                <i data-lucide="chevron-left"></i> Anterior
                            </button>
                            <button type="button" id="btn-proximo" onclick="proximaAba()" class="btn-primary">
                                Próximo <i data-lucide="chevron-right"></i>
                            </button>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" onclick="fecharModalSimples('modalCriarLicitacao')" class="btn-secondary">
                                <i data-lucide="x"></i> Cancelar
                            </button>
                            <button type="reset" class="btn-secondary" onclick="resetarFormulario()">
                                <i data-lucide="refresh-cw"></i> Limpar
                            </button>
                            <button type="submit" class="btn-success" id="btn-criar">
                                <i data-lucide="check"></i> <span id="btn-criar-texto">Criar Licitação</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

            <div id="relatorios" class="content-section <?php echo $secao_ativa === 'relatorios' ? 'active' : ''; ?>">
    <div class="dashboard-header">
        <h1><i data-lucide="file-text"></i> Relatórios</h1>
        <p>Relatórios detalhados sobre o processo licitatório</p>
    </div>

    <div class="stats-grid">
        <div class="chart-card">
            <h3 class="chart-title"><i data-lucide="pie-chart"></i> Relatório por Modalidade</h3>
            <p style="color: #7f8c8d; margin-bottom: 15px;">Análise detalhada das licitações por modalidade</p>
            
            <form method="GET" action="relatorios/gerar_relatorio_licitacao.php" target="_blank">
                <input type="hidden" name="tipo" value="modalidade">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                    <div>
                        <label style="font-size: 12px; color: #666; font-weight: 600;">Data Entrada DIPLI (Inicial)</label>
                        <input type="date" name="data_entrada_inicial" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div>
                        <label style="font-size: 12px; color: #666; font-weight: 600;">Data Entrada DIPLI (Final)</label>
                        <input type="date" name="data_entrada_final" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                    <div>
                        <label style="font-size: 12px; color: #666; font-weight: 600;">Data Homologação (Inicial)</label>
                        <input type="date" name="data_homologacao_inicial" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div>
                        <label style="font-size: 12px; color: #666; font-weight: 600;">Data Homologação (Final)</label>
                        <input type="date" name="data_homologacao_final" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                    <div>
                        <label style="font-size: 12px; color: #666; font-weight: 600;">Modalidade</label>
                        <select name="modalidade" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">Todas</option>
                            <option value="PREGAO">Pregão</option>
                            <option value="DISPENSA">Dispensa</option>
                            <option value="INEXIBILIDADE">Inexibilidade</option>
                            <option value="RDC">RDC</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 12px; color: #666; font-weight: 600;">Situação</label>
                        <select name="situacao" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">Todas</option>
                            <option value="EM_ANDAMENTO">Em Andamento</option>
                            <option value="HOMOLOGADO">Homologado</option>
                            <option value="FRACASSADO">Fracassado</option>
                            <option value="REVOGADO">Revogado</option>
                            <option value="CANCELADO">Cancelado</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button type="button" onclick="limparFiltros()" class="btn-secondary" style="background: #95a5a6; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">
                        <i data-lucide="x" style="width: 16px; height: 16px; margin-right: 6px;"></i>
                        Limpar
                    </button>
                    <button type="submit" class="btn-primary" style="background: #e74c3c; color: white; padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer;">
                        <i data-lucide="bar-chart-3" style="width: 16px; height: 16px; margin-right: 8px;"></i>
                        Gerar Relatório
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


        </div>
    </div>

    <div id="modalDetalhes" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                <i data-lucide="file-text"></i> Detalhes da Licitação
            </h3>
            <span class="close" onclick="fecharModal('modalDetalhes')">&times;</span>
        </div>
        <div class="modal-body" id="detalhesContent">
            </div>
    </div>
</div>

<div id="modalExportar" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                <i data-lucide="download"></i> Exportar Dados
            </h3>
            <span class="close" onclick="fecharModal('modalExportar')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formExportar">
                <?php echo getCSRFInput(); ?>
                <div class="form-group">
                    <label>Formato de Exportação</label>
                    <select id="formato_export" name="formato" required>
                        <option value="csv">CSV (Excel)</option>
                        <option value="excel">Excel (XLS)</option>
                        <option value="json">JSON</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Filtrar por Situação</label>
                    <select id="situacao_export" name="situacao">
                        <option value="">Todas as Situações</option>
                        <option value="EM_ANDAMENTO">Em Andamento</option>
                        <option value="HOMOLOGADO">Homologadas</option>
                        <option value="FRACASSADO">Fracassadas</option>
                        <option value="REVOGADO">Revogadas</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Período de Criação</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label style="font-size: 12px; color: #6c757d;">Data Inicial</label>
                            <input type="date" id="data_inicio_export" name="data_inicio">
                        </div>
                        <div>
                            <label style="font-size: 12px; color: #6c757d;">Data Final</label>
                            <input type="date" id="data_fim_export" name="data_fim">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Campos para Exportar</label>
                    <div style="margin-bottom: 10px;">
                        <button type="button" onclick="selecionarTodosCampos(true)" class="btn-secondary" style="margin-right: 10px; padding: 5px 10px; font-size: 12px;">
                            Selecionar Todos
                        </button>
                        <button type="button" onclick="selecionarTodosCampos(false)" class="btn-secondary" style="padding: 5px 10px; font-size: 12px;">
                            Desmarcar Todos
                        </button>
                    </div>
                    <div style="margin-top: 10px; max-height: 200px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px;">
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <input type="checkbox" name="campos[]" value="nup" checked> NUP
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <input type="checkbox" name="campos[]" value="numero_contratacao_final" checked> Número da Contratação
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <input type="checkbox" name="campos[]" value="modalidade" checked> Modalidade
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <input type="checkbox" name="campos[]" value="tipo" checked> Tipo
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <input type="checkbox" name="campos[]" value="objeto" checked> Objeto
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <input type="checkbox" name="campos[]" value="valor_estimado" checked> Valor Estimado
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <input type="checkbox" name="campos[]" value="situacao" checked> Situação
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <input type="checkbox" name="campos[]" value="pregoeiro" checked> Pregoeiro
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <input type="checkbox" name="campos[]" value="data_abertura" checked> Data Abertura
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <input type="checkbox" name="campos[]" value="data_homologacao"> Data Homologação
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <input type="checkbox" name="campos[]" value="valor_homologado"> Valor Homologado
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <input type="checkbox" name="campos[]" value="economia"> Economia
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <input type="checkbox" name="campos[]" value="area_demandante"> Área Demandante
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <input type="checkbox" name="campos[]" value="resp_instrucao"> Resp. Instrução
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <input type="checkbox" name="campos[]" value="usuario_nome"> Criado por
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <input type="checkbox" name="campos[]" value="criado_em"> Data de Criação
                        </label>
                    </div>
                </div>

                <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: flex-end;">
                    <button type="button" onclick="fecharModal('modalExportar')" class="btn-secondary">
                        <i data-lucide="x"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-primary">
                        <i data-lucide="download"></i> Exportar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Importar Andamentos -->
<div id="modalImportarAndamentos" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                <i data-lucide="upload"></i> Importar Andamentos de Processo
            </h3>
            <span class="close" onclick="fecharModal('modalImportarAndamentos')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formImportarAndamentos" enctype="multipart/form-data">
                <?php echo getCSRFInput(); ?>
                
                <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #2196f3;">
                    <h4 style="margin: 0 0 10px 0; color: #1976d2;">
                        <i data-lucide="info" style="width: 16px; height: 16px;"></i> NUP Selecionado
                    </h4>
                    <p style="margin: 0; font-weight: 600; color: #1976d2;" id="nupSelecionado">-</p>
                </div>
                
                <div class="form-group">
                    <label>Arquivo JSON *</label>
                    <input type="file" 
                           name="arquivo_json" 
                           id="arquivo_json" 
                           accept=".json" 
                           required 
                           style="width: 100%; padding: 10px; border: 2px dashed #dee2e6; border-radius: 8px; background: #f8f9fa;">
                    <small style="color: #6c757d; font-size: 12px; display: block; margin-top: 5px;">
                        Selecione um arquivo .json com os dados de andamentos do processo.
                    </small>
                </div>
                
                <details style="background: #fff3cd; padding: 10px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #f39c12;">
                    <summary style="cursor: pointer; font-weight: 600; color: #856404; padding: 5px 0;">
                        <i data-lucide="alert-triangle" style="width: 14px; height: 14px;"></i> Estrutura Esperada do JSON (clique para expandir)
                    </summary>
                    <pre style="background: #f8f9fa; padding: 8px; border-radius: 4px; font-size: 11px; overflow-x: auto; margin-top: 10px;">{
  "nup": "12345.123456/2024-12",
  "processo_id": "SEI123456789",
  "timestamp": "2024-12-27 10:30:00",
  "total_andamentos": 3,
  "andamentos": [
    {"unidade": "DIPLI", "dias": 15, "descricao": "Análise técnica"},
    {"unidade": "DIPLAN", "dias": 8, "descricao": "Revisão planejamento"},
    {"unidade": "DIQUALI", "dias": 12, "descricao": "Qualificação de demandas"}
  ]
}</pre>
                </details>
                
                <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: flex-end;">
                    <button type="button" onclick="fecharModal('modalImportarAndamentos')" class="btn-secondary">
                        <i data-lucide="x"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-primary">
                        <i data-lucide="upload"></i> Importar Andamentos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Visualizar Andamentos - REDESENHADO -->
<div id="modalVisualizarAndamentos" class="modal andamentos-modal" style="display: none;">
    <div class="andamentos-modal-content">
        <!-- Header Expandido -->
        <div class="andamentos-header">
            <div class="header-left">
                <div class="processo-info">
                    <div class="processo-titulo">
                        <i data-lucide="activity"></i>
                        <h2>Timeline do Processo</h2>
                    </div>
                    <div class="processo-detalhes">
                        <span class="nup-badge" id="nup-display">NUP: Carregando...</span>
                        <span class="status-info" id="status-display">Status: Carregando...</span>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <button class="action-btn export-btn" onclick="gerarRelatorioAndamentos()" title="Exportar Timeline">
                    <i data-lucide="download"></i>
                    <span>Exportar PDF</span>
                </button>
                <button class="action-btn refresh-btn" onclick="recarregarAndamentos()" title="Atualizar Dados">
                    <i data-lucide="refresh-ccw"></i>
                    <span>Atualizar</span>
                </button>
                <button class="action-btn close-btn" onclick="fecharModal('modalVisualizarAndamentos')" title="Fechar">
                    <i data-lucide="x"></i>
                </button>
            </div>
        </div>

        <!-- Corpo Principal com Layout em Duas Colunas -->
        <div class="andamentos-body">
            <!-- Coluna Principal - Timeline -->
            <div class="timeline-section">
                <div class="timeline-header">
                    <h3><i data-lucide="clock"></i> Histórico de Andamentos</h3>
                    <div class="timeline-controls">
                        <button class="filter-btn" onclick="toggleFiltrosAndamentos()">
                            <i data-lucide="filter"></i> Filtros
                        </button>
                    </div>
                </div>
                
                <!-- Filtros Expansíveis -->
                <div class="timeline-filters" id="filtrosAndamentos" style="display: none;">
                    <div class="filter-group">
                        <label>Período:</label>
                        <select id="filtroPerioodo">
                            <option value="">Todos</option>
                            <option value="30">Últimos 30 dias</option>
                            <option value="60">Últimos 60 dias</option>
                            <option value="90">Últimos 90 dias</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Unidade:</label>
                        <select id="filtroUnidade">
                            <option value="">Todas</option>
                            <option value="DIPLI">DIPLI</option>
                            <option value="DIPLAN">DIPLAN</option>
                            <option value="DIQUALI">DIQUALI</option>
                            <option value="CGLIC">CGLIC</option>
                        </select>
                    </div>
                </div>

                <!-- Container da Timeline Melhorada -->
                <div class="timeline-container-improved" id="conteudoAndamentos">
                    <div class="loading-timeline">
                        <div class="loading-spinner">
                            <i data-lucide="loader-2"></i>
                        </div>
                        <p>Carregando timeline do processo...</p>
                    </div>
                </div>
            </div>

            <!-- Coluna Lateral - Informações e Estatísticas -->
            <div class="info-sidebar">
                <div class="info-card">
                    <h4><i data-lucide="bar-chart-3"></i> Estatísticas</h4>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-value" id="totalAndamentos">-</span>
                            <span class="stat-label">Total de Andamentos</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value" id="tempoMedio">-</span>
                            <span class="stat-label">Tempo Médio</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value" id="unidadesEnvolvidas">-</span>
                            <span class="stat-label">Unidades</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value" id="ultimaAtualizacao">-</span>
                            <span class="stat-label">Última Atualização</span>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <h4><i data-lucide="info"></i> Informações do Processo</h4>
                    <div class="process-details">
                        <div class="detail-row">
                            <span class="detail-label">Modalidade:</span>
                            <span class="detail-value" id="modalidadeInfo">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Pregoeiro:</span>
                            <span class="detail-value" id="pregoeiroInfo">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Valor Estimado:</span>
                            <span class="detail-value" id="valorInfo">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Data de Abertura:</span>
                            <span class="detail-value" id="dataAberturaInfo">-</span>
                        </div>
                    </div>
                </div>

                <div class="info-card actions-card">
                    <h4><i data-lucide="settings"></i> Ações Rápidas</h4>
                    <div class="quick-actions">
                        <button class="quick-action-btn" onclick="verDetalhesCompletos()">
                            <i data-lucide="eye"></i> Ver Detalhes Completos
                        </button>
                        <button class="quick-action-btn" onclick="editarProcesso()">
                            <i data-lucide="edit"></i> Editar Processo
                        </button>
                        <button class="quick-action-btn" onclick="adicionarAndamento()">
                            <i data-lucide="plus"></i> Adicionar Andamento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    </div>
        </div>
        </main>
    </div>


    <script>
        
        // Dados passados do PHP para JavaScript
        window.dadosModalidade = <?php echo json_encode($dados_modalidade); ?>;
        window.dadosPregoeiro = <?php echo json_encode($dados_pregoeiro); ?>;
        window.dadosMensal = <?php echo json_encode($dados_mensal); ?>;
        window.stats = <?php echo json_encode($stats); ?>;
        window.dadosContratacoes = <?php echo json_encode($contratacoes_pca); ?>;
        
        // Compatibilidade com arquivo JS externo
        window.contratacoesPCA = window.dadosContratacoes;
        
        /**
         * Alterar quantidade de itens por página
         */
        function alterarItensPorPagina(novoValor) {
            const url = new URL(window.location);
            url.searchParams.set('por_pagina', novoValor);
            url.searchParams.set('pagina', '1'); // Voltar para a primeira página
            window.location.href = url.toString();
        }

        /**
         * Funções para o novo modal de andamentos
         */
        function toggleFiltrosAndamentos() {
            const filtros = document.getElementById('filtrosAndamentos');
            if (filtros) {
                filtros.style.display = filtros.style.display === 'none' ? 'flex' : 'none';
            }
        }

        function recarregarAndamentos() {
            const nupElement = document.getElementById('nup-display');
            if (nupElement) {
                const nup = nupElement.textContent.replace('NUP: ', '');
                if (nup && nup !== 'Carregando...') {
                    consultarAndamentos(nup);
                }
            }
        }

        function verDetalhesCompletos() {
            // Função placeholder - pode ser implementada para mostrar detalhes completos
            console.log('Ver detalhes completos - funcionalidade a ser implementada');
        }

        function editarProcesso() {
            // Função placeholder - pode ser implementada para editar o processo
            console.log('Editar processo - funcionalidade a ser implementada');
        }

        function adicionarAndamento() {
            // Função placeholder - pode ser implementada para adicionar novo andamento
            console.log('Adicionar andamento - funcionalidade a ser implementada');
        }

    </script>
    
    <script>
        // Função para mostrar seções
        function showSection(secaoId) {
            // Ocultar todas as seções
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(section => {
                section.classList.remove('active');
            });

            // Mostrar seção selecionada
            const targetSection = document.getElementById(secaoId);
            if (targetSection) {
                targetSection.classList.add('active');
            }

            // Atualizar navegação ativa
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.classList.remove('active');
            });

            // Marcar item de navegação ativo
            if (event && event.target) {
                event.target.classList.add('active');
            }

            // Atualizar URL sem recarregar página
            const url = new URL(window.location);
            url.searchParams.set('secao', secaoId);
            window.history.pushState({}, '', url);
        }

        // Função para limpar filtros do relatório
        function limparFiltros() {
            // Buscar o formulário do relatório
            const form = document.querySelector('form[action="relatorios/gerar_relatorio_licitacao.php"]');
            if (!form) {
                console.error('Formulário de relatório não encontrado');
                return;
            }

            // Limpar todos os campos input type="date"
            const dateInputs = form.querySelectorAll('input[type="date"]');
            dateInputs.forEach(input => {
                input.value = '';
            });

            // Limpar todos os selects (resetar para primeira opção que é vazia)
            const selects = form.querySelectorAll('select');
            selects.forEach(select => {
                select.selectedIndex = 0; // Primeira opção (valor vazio)
            });

            // Feedback visual opcional
            console.log('Filtros limpos com sucesso');
        }

        // =====================================
        // FUNÇÕES PARA BUSCA DE QUALIFICAÇÕES
        // =====================================

        let timeoutQualificacao = null;
        let qualificacoesCacheadas = [];

        /**
         * Pesquisar qualificações com debounce
         */
        function pesquisarQualificacaoInline(termo) {
            // Limpar timeout anterior
            if (timeoutQualificacao) {
                clearTimeout(timeoutQualificacao);
            }

            // Aguardar 300ms antes de pesquisar
            timeoutQualificacao = setTimeout(() => {
                buscarQualificacoes(termo);
            }, 300);
        }

        /**
         * Buscar qualificações via API
         */
        function buscarQualificacoes(termo) {
            if (termo.length < 2) {
                ocultarSugestoesQualificacao();
                return;
            }

            const sugestoesDiv = document.getElementById('sugestoes_qualificacao');
            sugestoesDiv.innerHTML = '<div class="loading-item">🔍 Buscando qualificações...</div>';
            sugestoesDiv.style.display = 'block';

            fetch(`api/get_qualificacao_data.php?termo=${encodeURIComponent(termo)}&limite=10`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        mostrarSugestoesQualificacoes(data.data);
                        qualificacoesCacheadas = data.data;
                    } else {
                        sugestoesDiv.innerHTML = '<div class="no-results">Nenhuma qualificação encontrada</div>';
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar qualificações:', error);
                    sugestoesDiv.innerHTML = '<div class="error-item">❌ Erro ao buscar qualificações</div>';
                });
        }

        /**
         * Mostrar sugestões de qualificações
         */
        function mostrarSugestoesQualificacoes(qualificacoes) {
            const sugestoesDiv = document.getElementById('sugestoes_qualificacao');
            let html = '';

            qualificacoes.forEach(q => {
                const statusClasse = q.busca_info.status_classe;
                const statusIcon = q.status === 'APROVADO' ? '✅' :
                                  q.status === 'CONCLUÍDO' ? '🎯' :
                                  q.status === 'EM ANÁLISE' ? '⏳' : '📋';

                const podeVincular = q.busca_info.pode_vincular;
                const jaVinculada = q.ja_vinculada;

                html += `
                    <div class="suggestion-item ${podeVincular ? 'disponivel' : 'indisponivel'}"
                         onclick="${podeVincular ? `selecionarQualificacao(${q.id})` : ''}"
                         style="${!podeVincular ? 'opacity: 0.6; cursor: not-allowed;' : ''}">
                        <div class="suggestion-header">
                            <strong>${statusIcon} ${q.nup}</strong>
                            <span class="status-badge status-${statusClasse}">${q.status}</span>
                            ${jaVinculada ? '<span class="vinculado-badge">🔗 JÁ VINCULADA</span>' : ''}
                        </div>
                        <div class="suggestion-content">
                            <div><strong>Área:</strong> ${q.area_demandante}</div>
                            <div><strong>Responsável:</strong> ${q.responsavel}</div>
                            <div><strong>Modalidade:</strong> ${q.modalidade}</div>
                            <div><strong>Valor:</strong> ${q.valor_estimado_formatado}</div>
                            <div><strong>Objeto:</strong> ${q.busca_info.titulo_resumido}</div>
                            ${q.pca_vinculado ?
                                `<div class="pca-info">
                                    <strong>PCA:</strong> ${q.pca_dados.numero_contratacao} - ${q.pca_dados.numero_dfd}
                                 </div>` :
                                '<div class="sem-pca">⚠️ Sem vinculação PCA</div>'
                            }
                        </div>
                    </div>
                `;
            });

            sugestoesDiv.innerHTML = html;
        }

        /**
         * Selecionar uma qualificação
         */
        function selecionarQualificacao(qualificacaoId) {
            const qualificacao = qualificacoesCacheadas.find(q => q.id === qualificacaoId);
            if (!qualificacao) return;

            // Preencher campos hidden
            document.getElementById('qualificacao_id_selecionada').value = qualificacao.id;
            document.getElementById('qualificacao_nup_selecionado').value = qualificacao.nup;

            // Atualizar campo de busca
            document.getElementById('input_qualificacao').value = `${qualificacao.nup} - ${qualificacao.area_demandante}`;

            // AUTO-PREENCHIMENTO DOS CAMPOS COMUNS
            preencherCamposDaQualificacao(qualificacao);

            // Mostrar informações da qualificação selecionada
            const infoDiv = document.getElementById('info_qualificacao_selecionada');
            const detalhesDiv = document.getElementById('detalhes_qualificacao');

            detalhesDiv.innerHTML = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <p><strong>NUP:</strong> ${qualificacao.nup}</p>
                        <p><strong>Área Demandante:</strong> ${qualificacao.area_demandante}</p>
                        <p><strong>Responsável:</strong> ${qualificacao.responsavel}</p>
                        <p><strong>Modalidade:</strong> ${qualificacao.modalidade}</p>
                    </div>
                    <div>
                        <p><strong>Status:</strong> <span class="status-badge status-${qualificacao.busca_info.status_classe}">${qualificacao.status}</span></p>
                        <p><strong>Valor Estimado:</strong> ${qualificacao.valor_estimado_formatado}</p>
                        <p><strong>Data:</strong> ${qualificacao.criado_em_formatado}</p>
                        ${qualificacao.pca_vinculado ?
                            `<p><strong>PCA:</strong> ${qualificacao.pca_dados.numero_contratacao}</p>` :
                            '<p><strong>PCA:</strong> <span style="color: #f39c12;">Não vinculado</span></p>'
                        }
                    </div>
                </div>
                <div style="grid-column: 1 / -1; margin-top: 10px;">
                    <p><strong>Objeto:</strong> ${qualificacao.objeto}</p>
                </div>
                <div style="grid-column: 1 / -1; margin-top: 10px; padding: 8px; background: #e8f5e9; border-radius: 6px;">
                    <p style="margin: 0; font-size: 12px; color: #2e7d32;">
                        <i data-lucide="check-circle" style="width: 14px; height: 14px;"></i>
                        <strong>Campos preenchidos automaticamente:</strong> NUP, Área Demandante, Modalidade, Objeto, Valor Estimado, Responsável
                    </p>
                </div>
            `;

            infoDiv.style.display = 'block';
            ocultarSugestoesQualificacao();

            // Navegar automaticamente para a próxima aba
            setTimeout(() => {
                mostrarAba('informacoes-gerais');
            }, 500);
        }

        /**
         * Preencher campos automaticamente com dados da qualificação
         */
        function preencherCamposDaQualificacao(qualificacao) {
            const camposPreenchidos = [];

            // 1. NUP
            const nupField = document.getElementById('nup_criar');
            if (nupField && !nupField.value.trim()) {
                nupField.value = qualificacao.nup;
                marcarCampoAutoPreenchido(nupField);
                camposPreenchidos.push('NUP');
            }

            // 2. Área Demandante
            const areaField = document.querySelector('[name="area_demandante"]');
            if (areaField && !areaField.value.trim()) {
                areaField.value = qualificacao.area_demandante;
                marcarCampoAutoPreenchido(areaField);
                camposPreenchidos.push('Área Demandante');
            }

            // 3. Modalidade
            const modalidadeField = document.querySelector('[name="modalidade"]');
            if (modalidadeField && !modalidadeField.value) {
                // Mapear modalidades se necessário
                const modalidadeMapeada = mapearModalidade(qualificacao.modalidade);
                modalidadeField.value = modalidadeMapeada;
                marcarCampoAutoPreenchido(modalidadeField);
                camposPreenchidos.push('Modalidade');
            }

            // 4. Objeto
            const objetoField = document.querySelector('[name="objeto"]');
            if (objetoField && !objetoField.value.trim()) {
                objetoField.value = qualificacao.objeto;
                marcarCampoAutoPreenchido(objetoField);
                camposPreenchidos.push('Objeto');
            }

            // 5. Valor Estimado
            const valorField = document.querySelector('[name="valor_estimado"]');
            if (valorField && !valorField.value) {
                valorField.value = qualificacao.valor_estimado.toFixed(2);
                marcarCampoAutoPreenchido(valorField);
                camposPreenchidos.push('Valor Estimado');
            }

            // 6. Responsável da Instrução (mapear de responsavel para resp_instrucao)
            const respField = document.querySelector('[name="resp_instrucao"]');
            if (respField && !respField.value.trim()) {
                respField.value = qualificacao.responsavel;
                marcarCampoAutoPreenchido(respField);
                camposPreenchidos.push('Responsável');
            }

            // 7. Observações (concatenar se já houver conteúdo)
            const obsField = document.querySelector('[name="observacoes"]');
            if (obsField && qualificacao.observacoes) {
                const obsExistentes = obsField.value.trim();
                const novaObs = `[Da Qualificação] ${qualificacao.observacoes}`;

                if (obsExistentes) {
                    obsField.value = `${obsExistentes}\n\n${novaObs}`;
                } else {
                    obsField.value = novaObs;
                    marcarCampoAutoPreenchido(obsField);
                    camposPreenchidos.push('Observações');
                }
            }

            // Mostrar notificação de sucesso
            if (camposPreenchidos.length > 0) {
                mostrarNotificacaoPreenchimento(camposPreenchidos);
            }

            // Validar consistência em tempo real
            validarConsistenciaQualificacao(qualificacao);
        }

        /**
         * Mapear modalidades entre qualificação e licitação
         */
        function mapearModalidade(modalidadeQualificacao) {
            const mapeamento = {
                'PREGÃO': 'PREGAO',
                'PREGAO': 'PREGAO',
                'DISPENSA': 'DISPENSA',
                'INEXIBILIDADE': 'INEXIBILIDADE',
                'RDC': 'RDC'
            };
            return mapeamento[modalidadeQualificacao.toUpperCase()] || modalidadeQualificacao;
        }

        /**
         * Marcar campo como auto-preenchido visualmente
         */
        function marcarCampoAutoPreenchido(campo) {
            campo.style.backgroundColor = '#e8f5e9';
            campo.style.borderColor = '#4caf50';
            campo.title = 'Campo preenchido automaticamente da qualificação';

            // Adicionar ícone de confirmação
            let icon = campo.parentNode.querySelector('.auto-filled-icon');
            if (!icon) {
                icon = document.createElement('span');
                icon.className = 'auto-filled-icon';
                icon.innerHTML = '<i data-lucide="check-circle" style="width: 16px; height: 16px; color: #4caf50; margin-left: 5px;"></i>';
                icon.style.position = 'absolute';
                icon.style.right = '8px';
                icon.style.top = '50%';
                icon.style.transform = 'translateY(-50%)';

                // Tornar o container do campo relativo se não for
                if (getComputedStyle(campo.parentNode).position === 'static') {
                    campo.parentNode.style.position = 'relative';
                }

                campo.parentNode.appendChild(icon);
            }

            // Reinicializar ícones Lucide
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
        }

        /**
         * Mostrar notificação de campos preenchidos
         */
        function mostrarNotificacaoPreenchimento(camposPreenchidos) {
            const msg = `✅ ${camposPreenchidos.length} campos preenchidos automaticamente: ${camposPreenchidos.join(', ')}`;

            // Se existe função de notificação global, usar
            if (typeof showNotification === 'function') {
                showNotification(msg, 'success', 5000);
            } else {
                // Fallback: console ou alert
                console.log(msg);
            }
        }

        /**
         * Validar consistência entre dados da qualificação e formulário
         */
        function validarConsistenciaQualificacao(qualificacao) {
            const warnings = [];

            // Validar modalidade
            const modalidadeAtual = document.querySelector('[name="modalidade"]')?.value;
            const modalidadeEsperada = mapearModalidade(qualificacao.modalidade);
            if (modalidadeAtual && modalidadeAtual !== modalidadeEsperada) {
                warnings.push(`Modalidade divergente: Qualificação tem "${qualificacao.modalidade}", formulário tem "${modalidadeAtual}"`);
            }

            // Validar valor estimado (diferença > 20%)
            const valorAtual = parseFloat(document.querySelector('[name="valor_estimado"]')?.value || '0');
            const valorQualificacao = qualificacao.valor_estimado;
            if (valorAtual && valorQualificacao && Math.abs(valorAtual - valorQualificacao) / valorQualificacao > 0.2) {
                warnings.push(`Valor estimado diverge significativamente: Qualificação R$ ${qualificacao.valor_estimado_formatado}, formulário R$ ${valorAtual.toFixed(2)}`);
            }

            // Mostrar warnings se houver
            if (warnings.length > 0) {
                setTimeout(() => {
                    const msg = `⚠️ Inconsistências detectadas:\n${warnings.join('\n')}`;
                    if (typeof showNotification === 'function') {
                        showNotification(msg, 'warning', 8000);
                    } else {
                        console.warn(msg);
                    }
                }, 1000);
            }
        }

        /**
         * Mostrar sugestões de qualificação
         */
        function mostrarSugestoesQualificacao() {
            const sugestoesDiv = document.getElementById('sugestoes_qualificacao');
            if (sugestoesDiv.innerHTML.trim() !== '') {
                sugestoesDiv.style.display = 'block';
            }
        }

        /**
         * Ocultar sugestões de qualificação
         */
        function ocultarSugestoesQualificacao() {
            setTimeout(() => {
                const sugestoesDiv = document.getElementById('sugestoes_qualificacao');
                if (sugestoesDiv) {
                    sugestoesDiv.style.display = 'none';
                }
            }, 200); // Delay para permitir clique na sugestão
        }

        /**
         * Ordem das abas para navegação
         */
        const abasOrdem = [
            'vinculacao-qualificacao',
            'informacoes-gerais',
            'prazos-datas',
            'valores-financeiro',
            'responsaveis'
        ];

        /**
         * Função para mostrar abas (atualizada para qualificações)
         */
        function mostrarAba(abaId) {
            try {
                // Ocultar todas as abas
                const abas = document.querySelectorAll('.tab-content');
                abas.forEach(aba => aba.classList.remove('active'));

                // Ocultar todos os botões de aba
                const botoes = document.querySelectorAll('.tab-button');
                botoes.forEach(botao => botao.classList.remove('active'));

                // Mostrar aba selecionada
                const abaSelecionada = document.getElementById(`aba-${abaId}`);
                if (abaSelecionada) {
                    abaSelecionada.classList.add('active');
                    console.log('✅ Aba ativada:', abaId);
                } else {
                    console.error('❌ Aba não encontrada:', `aba-${abaId}`);
                    return;
                }

                // Ativar botão da aba
                const botaoAtivo = event ? event.target :
                                  document.querySelector(`[onclick="mostrarAba('${abaId}')"]`);
                if (botaoAtivo && botaoAtivo.classList) {
                    botaoAtivo.classList.add('active');
                }

            // Atualizar visibilidade dos botões de navegação
            atualizarBotoesNavegacao(abaId);
            } catch (error) {
                console.error('❌ Erro ao mostrar aba:', error);
            }
        }

        /**
         * Função para ir para a próxima aba
         */
        function proximaAba() {
            const abaAtiva = document.querySelector('.tab-content.active');
            if (!abaAtiva) return;

            const abaAtualId = abaAtiva.id.replace('aba-', '');
            const indiceAtual = abasOrdem.indexOf(abaAtualId);

            if (indiceAtual >= 0 && indiceAtual < abasOrdem.length - 1) {
                const proximaAbaId = abasOrdem[indiceAtual + 1];
                mostrarAba(proximaAbaId);
            }
        }

        /**
         * Função para ir para a aba anterior
         */
        function abaAnterior() {
            const abaAtiva = document.querySelector('.tab-content.active');
            if (!abaAtiva) return;

            const abaAtualId = abaAtiva.id.replace('aba-', '');
            const indiceAtual = abasOrdem.indexOf(abaAtualId);

            if (indiceAtual > 0) {
                const abaAnteriorId = abasOrdem[indiceAtual - 1];
                mostrarAba(abaAnteriorId);
            }
        }

        /**
         * Atualizar visibilidade dos botões de navegação
         */
        function atualizarBotoesNavegacao(abaId) {
            const indiceAtual = abasOrdem.indexOf(abaId);
            const btnAnterior = document.getElementById('btn-anterior');
            const btnProximo = document.getElementById('btn-proximo');

            // Mostrar/ocultar botão Anterior
            if (btnAnterior) {
                if (indiceAtual <= 0) {
                    btnAnterior.style.display = 'none';
                } else {
                    btnAnterior.style.display = 'inline-flex';
                }
            }

            // Mostrar/ocultar botão Próximo
            if (btnProximo) {
                if (indiceAtual >= abasOrdem.length - 1) {
                    btnProximo.style.display = 'none';
                } else {
                    btnProximo.style.display = 'inline-flex';
                }
            }
        }

        /**
         * Limpar campos que foram preenchidos automaticamente
         */
        function limparCamposAutoPreenchidos() {
            const camposAutoPreenchidos = document.querySelectorAll('input[style*="background-color: rgb(232, 245, 233)"], select[style*="background-color: rgb(232, 245, 233)"], textarea[style*="background-color: rgb(232, 245, 233)"]');

            camposAutoPreenchidos.forEach(campo => {
                // Limpar valor
                if (campo.type === 'checkbox' || campo.type === 'radio') {
                    campo.checked = false;
                } else {
                    campo.value = '';
                }

                // Remover estilização de auto-preenchimento
                campo.style.backgroundColor = '';
                campo.style.borderColor = '';
                campo.title = '';

                // Remover ícone de confirmação
                const icon = campo.parentNode.querySelector('.auto-filled-icon');
                if (icon) {
                    icon.remove();
                }
            });

            // Limpar campos hidden da qualificação
            document.getElementById('qualificacao_id_selecionada').value = '';
            document.getElementById('qualificacao_nup_selecionado').value = '';
            document.getElementById('input_qualificacao').value = '';

            // Ocultar informações da qualificação
            document.getElementById('info_qualificacao_selecionada').style.display = 'none';

            // Mostrar notificação
            if (typeof showNotification === 'function') {
                showNotification('🧹 Campos auto-preenchidos foram limpos', 'info', 3000);
            }

            // Voltar para a aba de vinculação
            mostrarAba('vinculacao-qualificacao');
        }

        // Inicializar aba ativa ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            mostrarAba('vinculacao-qualificacao');
        });

    </script>

    <script src="assets/licitacao-dashboard.js?v=<?php echo time(); ?>&r=<?php echo rand(1000, 9999); ?>"></script>
    <script src="assets/licitacao_simples.js?v=<?php echo time(); ?>"></script>
    <script src="assets/notifications.js?v=<?php echo time(); ?>"></script>

    <script>
        // Verificar carregamento das funções essenciais
        function verificarFuncoes() {
            const funcoes = ['consultarAndamentos', 'fecharModal', 'abrirModalImportarAndamentos'];
            funcoes.forEach(funcao => {
                if (typeof window[funcao] === 'function') {
                    console.log(`✅ ${funcao} carregada corretamente`);
                } else {
                    console.error(`❌ ${funcao} NÃO está definida`);
                }
            });
        }

        // Script simples e limpo para os modais
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar funções após carregamento
            setTimeout(verificarFuncoes, 1000);

            // Criar funções fallback se necessário
            if (typeof window.fecharModal !== 'function') {
                window.fecharModal = function(modalId) {
                    console.log('🔧 Usando função fecharModal de fallback para:', modalId);
                    const modal = document.getElementById(modalId);
                    if (modal) {
                        modal.style.display = 'none';
                        modal.classList.remove('show');
                    }
                };
            }

            if (typeof window.consultarAndamentos !== 'function') {
                window.consultarAndamentos = function(nup) {
                    console.log('🔧 Usando função consultarAndamentos de fallback para:', nup);
                    alert('Função consultarAndamentos não carregada. Recarregue a página.');
                };
            }
            // Inicializar ícones Lucide
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }

            // Função simples para fechar qualquer modal
            window.fecharModalSimples = function(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'none';
                    modal.classList.remove('show');

                    // Se for o modal de criação, limpar formulário
                    if (modalId === 'modalCriarLicitacao') {
                        const form = document.getElementById('formLicitacao');
                        if (form) form.reset();
                    }
                }
            };

            // Fechar modal ao clicar no X ou fora do modal
            document.addEventListener('click', function(e) {
                // Fechar modal ao clicar no X
                if (e.target.classList.contains('close')) {
                    const modal = e.target.closest('.modal');
                    if (modal) {
                        fecharModalSimples(modal.id);
                    }
                }

                // Fechar modal ao clicar fora dele
                if (e.target.classList.contains('modal')) {
                    fecharModalSimples(e.target.id);
                }
            });

            // Fechar modal com ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modalsAbertos = document.querySelectorAll('.modal[style*="block"]');
                    modalsAbertos.forEach(modal => {
                        fecharModalSimples(modal.id);
                    });
                }
            });

            // SOLUÇÃO EMERGENCIAL: Implementar funções básicas inline

            // Função para abrir modal de criar licitação
            window.abrirModalCriarLicitacao = function(modo = 'create', dadosLicitacao = null) {
                console.log('abrirModalCriarLicitacao chamada:', modo, dadosLicitacao);

                const modal = document.getElementById('modalCriarLicitacao');
                const form = document.getElementById('formLicitacao');

                if (!modal) {
                    console.error('Modal modalCriarLicitacao não encontrado');
                    alert('Modal não encontrado');
                    return;
                }

                if (!form) {
                    console.error('Formulário formLicitacao não encontrado');
                }

                // Limpar formulário
                if (form) {
                    form.reset();
                }

                // Ajustar título do modal e botão
                const titulo = document.getElementById('modalLicitacaoTituloTexto');
                const icone = document.getElementById('modalLicitacaoIcon');
                const btnTexto = document.getElementById('btn-criar-texto');

                if (modo === 'edit') {
                    if (titulo) titulo.textContent = 'Editar Licitação';
                    if (icone) icone.setAttribute('data-lucide', 'edit');
                    if (btnTexto) btnTexto.textContent = 'Salvar Alterações';

                    // Preencher dados se fornecidos
                    if (dadosLicitacao) {
                        console.log('Preenchendo dados:', dadosLicitacao);

                        const idField = document.getElementById('licitacao_id');
                        if (idField && dadosLicitacao.id) {
                            idField.value = dadosLicitacao.id;
                        }

                        const nupField = form.querySelector('[name="nup"]');
                        if (nupField && dadosLicitacao.nup) {
                            nupField.value = dadosLicitacao.nup;
                            console.log('✅ NUP preenchido:', dadosLicitacao.nup);
                        }

                        // Preencher outros campos importantes
                        const modalidadeField = form.querySelector('[name="modalidade"]');
                        if (modalidadeField && dadosLicitacao.modalidade) {
                            modalidadeField.value = dadosLicitacao.modalidade;
                            console.log('✅ Modalidade preenchida:', dadosLicitacao.modalidade);
                        }

                        const objetoField = form.querySelector('[name="objeto"]');
                        if (objetoField && dadosLicitacao.objeto) {
                            objetoField.value = dadosLicitacao.objeto;
                            console.log('✅ Objeto preenchido:', dadosLicitacao.objeto.substring(0, 50) + '...');
                        }

                        const valorEstimadoField = form.querySelector('[name="valor_estimado"]');
                        if (valorEstimadoField && dadosLicitacao.valor_estimado) {
                            valorEstimadoField.value = dadosLicitacao.valor_estimado;
                            console.log('✅ Valor estimado preenchido:', dadosLicitacao.valor_estimado);
                        }

                        const pregoeiro = form.querySelector('[name="pregoeiro"]');
                        if (pregoeiro && dadosLicitacao.pregoeiro) {
                            pregoeiro.value = dadosLicitacao.pregoeiro;
                            console.log('✅ Pregoeiro preenchido:', dadosLicitacao.pregoeiro);
                        }

                        // CAMPOS DE DATA - CORRIGIDO para usar querySelector com name
                        console.log('🗓️ Preenchendo campos de data...');

                        const dataEntradaField = form.querySelector('[name="data_entrada_dipli"]');
                        if (dataEntradaField && dadosLicitacao.data_entrada_dipli) {
                            dataEntradaField.value = dadosLicitacao.data_entrada_dipli;
                            console.log('✅ Data entrada DIPLI:', dadosLicitacao.data_entrada_dipli);
                        } else {
                            console.log('❌ Campo data_entrada_dipli não encontrado ou dado vazio');
                        }

                        const dataAberturaField = form.querySelector('[name="data_abertura"]');
                        if (dataAberturaField && dadosLicitacao.data_abertura) {
                            dataAberturaField.value = dadosLicitacao.data_abertura;
                            console.log('✅ Data abertura:', dadosLicitacao.data_abertura);
                        } else {
                            console.log('❌ Campo data_abertura não encontrado ou dado vazio');
                        }

                        const dataPublicacaoField = form.querySelector('[name="data_publicacao"]');
                        if (dataPublicacaoField && dadosLicitacao.data_publicacao) {
                            dataPublicacaoField.value = dadosLicitacao.data_publicacao;
                            console.log('✅ Data publicação:', dadosLicitacao.data_publicacao);
                        } else {
                            console.log('⚠️ Campo data_publicacao não encontrado ou dado vazio');
                        }

                        const dataHomologacaoField = form.querySelector('[name="data_homologacao"]');
                        if (dataHomologacaoField && dadosLicitacao.data_homologacao) {
                            dataHomologacaoField.value = dadosLicitacao.data_homologacao;
                            console.log('✅ Data homologação:', dadosLicitacao.data_homologacao);
                        } else {
                            console.log('⚠️ Campo data_homologacao não encontrado ou dado vazio');
                        }

                        // CAMPOS ADICIONAIS IMPORTANTES - CORRIGIDO para usar querySelector
                        const valorHomologadoField = form.querySelector('[name="valor_homologado"]');
                        if (valorHomologadoField && dadosLicitacao.valor_homologado) {
                            valorHomologadoField.value = dadosLicitacao.valor_homologado;
                        }

                        const economiaField = form.querySelector('[name="economia"]');
                        if (economiaField && dadosLicitacao.economia) {
                            economiaField.value = dadosLicitacao.economia;
                        }

                        const tipoField = form.querySelector('[name="tipo"]');
                        if (tipoField && dadosLicitacao.tipo) {
                            tipoField.value = dadosLicitacao.tipo;
                        }

                        const situacaoField = form.querySelector('[name="situacao"]');
                        if (situacaoField && dadosLicitacao.situacao) {
                            situacaoField.value = dadosLicitacao.situacao;
                        }

                        const respInstrucaoField = form.querySelector('[name="resp_instrucao"]');
                        if (respInstrucaoField && dadosLicitacao.resp_instrucao) {
                            respInstrucaoField.value = dadosLicitacao.resp_instrucao;
                        }

                        const areaDemandanteField = form.querySelector('[name="area_demandante"]');
                        if (areaDemandanteField && dadosLicitacao.area_demandante) {
                            areaDemandanteField.value = dadosLicitacao.area_demandante;
                        }

                        const numeroContratacaoField = form.querySelector('[name="numero_contratacao"]');
                        if (numeroContratacaoField && dadosLicitacao.numero_contratacao) {
                            numeroContratacaoField.value = dadosLicitacao.numero_contratacao;
                        }

                        const observacoesField = form.querySelector('[name="observacoes"]');
                        if (observacoesField && dadosLicitacao.observacoes) {
                            observacoesField.value = dadosLicitacao.observacoes;
                        }

                        // PREENCHER DADOS DA QUALIFICAÇÃO VINCULADA
                        console.log('🔗 Verificando qualificação vinculada...');
                        if (dadosLicitacao.qualificacao && dadosLicitacao.qualificacao.id) {
                            console.log('✅ Qualificação encontrada:', dadosLicitacao.qualificacao);

                            // Preencher campo hidden com ID da qualificação
                            const qualificacaoIdField = document.getElementById('qualificacao_id_selecionada');
                            if (qualificacaoIdField) {
                                qualificacaoIdField.value = dadosLicitacao.qualificacao.id;
                                console.log('✅ Campo qualificacao_id_selecionada preenchido:', dadosLicitacao.qualificacao.id);
                            }

                            // Preencher campo hidden com NUP da qualificação
                            const qualificacaoNupField = document.getElementById('qualificacao_nup_selecionado');
                            if (qualificacaoNupField) {
                                qualificacaoNupField.value = dadosLicitacao.qualificacao.nup;
                                console.log('✅ Campo qualificacao_nup_selecionado preenchido:', dadosLicitacao.qualificacao.nup);
                            }

                            // Preencher campo de busca/exibição da qualificação
                            const inputQualificacaoField = document.getElementById('input_qualificacao');
                            if (inputQualificacaoField) {
                                const textoQualificacao = `${dadosLicitacao.qualificacao.nup} - ${dadosLicitacao.qualificacao.area_demandante}`;
                                inputQualificacaoField.value = textoQualificacao;
                                console.log('✅ Campo input_qualificacao preenchido:', textoQualificacao);
                            }

                            // Mostrar informações da qualificação selecionada
                            const infoQualificacaoDiv = document.getElementById('info_qualificacao_selecionada');
                            const detalhesQualificacaoDiv = document.getElementById('detalhes_qualificacao');
                            if (infoQualificacaoDiv && detalhesQualificacaoDiv) {
                                detalhesQualificacaoDiv.innerHTML = `
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                        <p><strong>NUP:</strong> ${dadosLicitacao.qualificacao.nup}</p>
                                        <p><strong>Área Demandante:</strong> ${dadosLicitacao.qualificacao.area_demandante}</p>
                                        <p><strong>Responsável:</strong> ${dadosLicitacao.qualificacao.responsavel}</p>
                                        <p><strong>Modalidade:</strong> ${dadosLicitacao.qualificacao.modalidade}</p>
                                        <p><strong>Status:</strong> ${dadosLicitacao.qualificacao.status}</p>
                                        <p><strong>Valor Estimado:</strong> ${dadosLicitacao.qualificacao.valor_estimado_formatado}</p>
                                    </div>
                                    <p><strong>Objeto:</strong> ${dadosLicitacao.qualificacao.objeto}</p>
                                `;
                                infoQualificacaoDiv.style.display = 'block';
                                console.log('✅ Informações da qualificação exibidas');
                            }
                        } else {
                            console.log('⚠️ Nenhuma qualificação vinculada encontrada');
                        }

                        console.log('🎯 Preenchimento completo finalizado!');
                    }
                } else {
                    if (titulo) titulo.textContent = 'Nova Licitação';
                    if (icone) icone.setAttribute('data-lucide', 'plus-circle');
                    if (btnTexto) btnTexto.textContent = 'Criar Licitação';

                    // Definir valores padrão para nova licitação
                    const tipoField = form.querySelector('[name="tipo"]');
                    if (tipoField && !tipoField.value) {
                        tipoField.value = 'TRADICIONAL';
                    }

                    const situacaoField = form.querySelector('[name="situacao"]');
                    if (situacaoField && !situacaoField.value) {
                        situacaoField.value = 'EM_ANDAMENTO';
                    }

                    // Qualificação ID pode ficar vazio para nova licitação
                    const qualificacaoField = document.getElementById('qualificacao_id_selecionada');
                    if (qualificacaoField && !qualificacaoField.value) {
                        qualificacaoField.value = '0'; // Valor padrão para indicar sem qualificação
                    }
                }

                // Mostrar modal
                modal.style.display = 'block';
                modal.style.zIndex = '1000';
                modal.classList.add('show');

                // Inicializar navegação das abas (começar na primeira aba)
                mostrarAba('vinculacao-qualificacao');

                // Atualizar ícones Lucide
                if (typeof lucide !== 'undefined' && lucide.createIcons) {
                    lucide.createIcons();
                }

                console.log('Modal aberto');
            };

            // Função para editar licitação - usar modal com abas original
            window.editarLicitacao = function(id) {
                console.log('✏️ Editando licitação ID:', id);

                if (!id) {
                    console.error('❌ ID não fornecido');
                    alert('ID da licitação não fornecido');
                    return;
                }

                // Buscar dados da licitação e abrir modal com abas
                fetch('api/get_licitacao.php?id=' + id)
                    .then(response => response.json())
                    .then(data => {
                        console.log('📊 Dados recebidos da API:', data);

                        if (data.success) {
                            const licitacao = data.data || {};
                            licitacao.id = id;
                            console.log('✅ Abrindo modal de edição com dados:', licitacao);

                            // Abrir modal com abas (modalCriarLicitacao com modo edit)
                            abrirModalCriarLicitacao('edit', licitacao);
                        } else {
                            console.error('❌ Erro na API:', data.message);
                            console.log('🔧 Abrindo modal sem dados para permitir edição manual');
                            abrirModalCriarLicitacao('edit', {id: id});
                        }
                    })
                    .catch(error => {
                        console.error('❌ Erro ao carregar licitação:', error);
                        console.log('🔧 Abrindo modal sem dados devido ao erro:', error.message);
                        abrirModalCriarLicitacao('edit', {id: id});
                    });
            };

            // Função Ver Detalhes - modal específico para visualização
            window.verDetalhes = function(id) {
                console.log('👁️ Visualizando detalhes da licitação ID:', id);

                if (!id) {
                    console.error('❌ ID não fornecido');
                    return;
                }

                const modal = document.getElementById('modalDetalhes');
                const content = document.getElementById('detalhesContent');

                if (!modal || !content) {
                    console.error('❌ Modal de detalhes não encontrado');
                    return;
                }

                // Mostrar loading
                content.innerHTML = '<div style="text-align: center; padding: 40px;"><i data-lucide="loader-2" style="animation: spin 1s linear infinite;"></i> Carregando detalhes...</div>';

                modal.classList.add('show');
                modal.style.display = 'block';

                // Buscar dados da licitação
                fetch('api/get_licitacao.php?id=' + id)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const lic = data.data;
                            console.log('✅ Dados carregados:', lic);

                            content.innerHTML = `
                                <div style="display: grid; gap: 20px;">
                                    <!-- Informações Básicas -->
                                    <div class="details-section">
                                        <h4 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 18px; border-bottom: 2px solid #3498db; padding-bottom: 8px;">
                                            <i data-lucide="info"></i> Informações Básicas
                                        </h4>
                                        <div class="details-grid">
                                            <div class="detail-item">
                                                <label>NUP:</label>
                                                <span>${lic.nup || '-'}</span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Número da Contratação:</label>
                                                <span>${lic.numero_contratacao || '-'}</span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Modalidade:</label>
                                                <span class="badge badge-info">${lic.modalidade || '-'}</span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Tipo:</label>
                                                <span class="badge badge-secondary">${lic.tipo || '-'}</span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Situação:</label>
                                                <span class="status-badge status-${(lic.situacao || '').toLowerCase().replace('_', '-')}">${(lic.situacao || '').replace('_', ' ')}</span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Área Demandante:</label>
                                                <span>${lic.area_demandante || '-'}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Objeto -->
                                    <div class="details-section">
                                        <h4 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 18px; border-bottom: 2px solid #e74c3c; padding-bottom: 8px;">
                                            <i data-lucide="file-text"></i> Objeto
                                        </h4>
                                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; line-height: 1.6;">
                                            ${lic.objeto || 'Não informado'}
                                        </div>
                                    </div>

                                    <!-- Valores -->
                                    <div class="details-section">
                                        <h4 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 18px; border-bottom: 2px solid #27ae60; padding-bottom: 8px;">
                                            <i data-lucide="dollar-sign"></i> Valores
                                        </h4>
                                        <div class="details-grid">
                                            <div class="detail-item">
                                                <label>Valor Estimado:</label>
                                                <span class="value-currency">${lic.valor_estimado ? 'R$ ' + parseFloat(lic.valor_estimado).toLocaleString('pt-BR', {minimumFractionDigits: 2}) : '-'}</span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Valor Homologado:</label>
                                                <span class="value-currency">${lic.valor_homologado ? 'R$ ' + parseFloat(lic.valor_homologado).toLocaleString('pt-BR', {minimumFractionDigits: 2}) : '-'}</span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Economia:</label>
                                                <span class="value-currency ${lic.economia > 0 ? 'positive' : ''}">${lic.economia ? 'R$ ' + parseFloat(lic.economia).toLocaleString('pt-BR', {minimumFractionDigits: 2}) : '-'}</span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Quantidade de Itens:</label>
                                                <span>${lic.qtd_itens || '-'}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Datas -->
                                    <div class="details-section">
                                        <h4 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 18px; border-bottom: 2px solid #f39c12; padding-bottom: 8px;">
                                            <i data-lucide="calendar"></i> Cronograma
                                        </h4>
                                        <div class="details-grid">
                                            <div class="detail-item">
                                                <label>Data de Entrada DIPLI:</label>
                                                <span>${lic.data_entrada_dipli ? new Date(lic.data_entrada_dipli).toLocaleDateString('pt-BR') : '-'}</span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Data de Abertura:</label>
                                                <span>${lic.data_abertura ? new Date(lic.data_abertura).toLocaleDateString('pt-BR') : '-'}</span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Data de Publicação:</label>
                                                <span>${lic.data_publicacao ? new Date(lic.data_publicacao).toLocaleDateString('pt-BR') : '-'}</span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Data de Homologação:</label>
                                                <span>${lic.data_homologacao ? new Date(lic.data_homologacao).toLocaleDateString('pt-BR') : '-'}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Responsáveis -->
                                    <div class="details-section">
                                        <h4 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 18px; border-bottom: 2px solid #9b59b6; padding-bottom: 8px;">
                                            <i data-lucide="users"></i> Responsáveis
                                        </h4>
                                        <div class="details-grid">
                                            <div class="detail-item">
                                                <label>Pregoeiro:</label>
                                                <span>${lic.pregoeiro || '-'}</span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Responsável pela Instrução:</label>
                                                <span>${lic.resp_instrucao || '-'}</span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Criado por:</label>
                                                <span>${lic.usuario_nome || '-'}</span>
                                            </div>
                                            <div class="detail-item">
                                                <label>Data de Criação:</label>
                                                <span>${lic.criado_em ? new Date(lic.criado_em).toLocaleDateString('pt-BR') + ' às ' + new Date(lic.criado_em).toLocaleTimeString('pt-BR') : '-'}</span>
                                            </div>
                                        </div>
                                    </div>

                                    ${lic.observacoes ? `
                                    <!-- Observações -->
                                    <div class="details-section">
                                        <h4 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 18px; border-bottom: 2px solid #34495e; padding-bottom: 8px;">
                                            <i data-lucide="message-square"></i> Observações
                                        </h4>
                                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; line-height: 1.6;">
                                            ${lic.observacoes}
                                        </div>
                                    </div>
                                    ` : ''}

                                    ${lic.link ? `
                                    <!-- Link do SharePoint -->
                                    <div class="details-section">
                                        <h4 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 18px; border-bottom: 2px solid #1abc9c; padding-bottom: 8px;">
                                            <i data-lucide="external-link"></i> Documentação
                                        </h4>
                                        <a href="${lic.link}" target="_blank" class="btn btn-outline-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                                            <i data-lucide="external-link"></i> Abrir no SharePoint
                                        </a>
                                    </div>
                                    ` : ''}
                                </div>
                            `;

                            // Atualizar ícones do Lucide
                            if (typeof lucide !== 'undefined') {
                                lucide.createIcons();
                            }
                        } else {
                            content.innerHTML = '<div style="text-align: center; padding: 40px; color: #dc3545;">Erro: ' + (data.message || 'Dados da licitação não encontrados') + '</div>';
                        }
                    })
                    .catch(error => {
                        console.error('❌ Erro ao carregar detalhes:', error);
                        content.innerHTML = '<div style="text-align: center; padding: 40px; color: #dc3545;">Erro ao conectar com o servidor</div>';
                    });
            };

            // Função Excluir Licitação
            window.excluirLicitacao = function(id, nup) {
                console.log('🗑️ Excluindo licitação ID:', id, 'NUP:', nup);

                if (!id) {
                    console.error('❌ ID não fornecido');
                    alert('ID da licitação não fornecido');
                    return;
                }

                // Confirmar exclusão
                if (!confirm(`Tem certeza que deseja excluir a licitação ${nup}?\n\nEsta ação não pode ser desfeita.`)) {
                    return;
                }

                // Enviar requisição de exclusão
                fetch('process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `acao=excluir_licitacao&id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Licitação excluída com sucesso!');
                        window.location.reload(); // Recarregar página
                    } else {
                        alert('Erro ao excluir: ' + (data.message || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('❌ Erro ao excluir licitação:', error);
                    alert('Erro ao conectar com o servidor: ' + error.message);
                });
            };

            // Função Abrir Modal Importar Andamentos
            window.abrirModalImportarAndamentos = function(nup) {
                console.log('📥 Abrindo modal de importar andamentos para NUP:', nup);

                if (!nup) {
                    console.error('❌ NUP não fornecido');
                    alert('NUP da licitação não fornecido');
                    return;
                }

                // Verificar se modal existe
                const modalElement = document.getElementById('modalImportarAndamentos');
                const nupElement = document.getElementById('nupSelecionado');

                if (!modalElement) {
                    console.error('Modal modalImportarAndamentos não encontrado');
                    alert('Erro: Modal de importação não encontrado.');
                    return;
                }

                if (!nupElement) {
                    console.error('Elemento nupSelecionado não encontrado');
                    alert('Erro: Elemento NUP não encontrado.');
                    return;
                }

                // Definir NUP no modal
                nupElement.textContent = nup;

                // Exibir modal
                modalElement.classList.add("show");
                modalElement.style.display = "block";

                // Recriar ícones Lucide
                setTimeout(() => {
                    if (typeof lucide !== 'undefined' && lucide.createIcons) {
                        lucide.createIcons();
                    }
                }, 100);

                console.log('Modal de importação exibido com sucesso para NUP:', nup);
            };

            // Implementar envio do formulário
            const formLicitacao = document.getElementById('formLicitacao');
            if (formLicitacao) {
                formLicitacao.addEventListener('submit', function(e) {
                    e.preventDefault();

                    console.log('🚀 Iniciando envio do formulário de licitação...');

                    const formData = new FormData(formLicitacao);
                    const btnCriar = document.getElementById('btn-criar');
                    const btnTexto = document.getElementById('btn-criar-texto');
                    const textoOriginal = btnTexto ? btnTexto.textContent : 'Salvar';

                    // Determinar ação baseada na presença do ID (para API licitacao_crud)
                    const licitacaoId = formData.get('id');
                    const acao = licitacaoId && licitacaoId.trim() !== '' && licitacaoId !== '0' ? 'editar' : 'criar';

                    // Definir ação no formulário
                    formData.set('acao', acao);

                    // Garantir que o ID está correto para edição
                    if (acao === 'editar' && !licitacaoId) {
                        console.error('❌ ID necessário para edição mas não encontrado');
                        alert('Erro: ID da licitação não encontrado para edição');
                        return;
                    }

                    // Debug: Mostrar todos os dados que serão enviados
                    console.log('📦 Dados do formulário:');
                    for (let pair of formData.entries()) {
                        console.log(`  ${pair[0]}: ${pair[1]}`);
                    }

                    console.log('🎯 Ação determinada:', acao);

                    // Mostrar loading
                    if (btnCriar) {
                        btnCriar.disabled = true;
                        if (btnTexto) btnTexto.textContent = 'Salvando...';
                    }

                    // Enviar para process.php
                    fetch('process.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => {
                        console.log('📡 Status da resposta:', response.status);
                        console.log('📡 Headers:', response.headers);

                        // Primeiro pegar como texto para debug
                        return response.text();
                    })
                    .then(responseText => {
                        console.log('📄 Resposta bruta do servidor:', responseText);

                        try {
                            // Tentar parsear como JSON
                            const data = JSON.parse(responseText);
                            console.log('✅ JSON parseado:', data);

                            if (data.success) {
                                // Sucesso
                                console.log('🎉 Sucesso!');
                                alert(data.message || 'Licitação salva com sucesso!');

                                // Fechar modal
                                window.fecharModalSimples('modalCriarLicitacao');

                                // Recarregar página para mostrar mudanças
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                // Erro
                                console.log('❌ Erro do servidor:', data.message);
                                alert(data.message || 'Erro ao salvar licitação');
                            }
                        } catch (jsonError) {
                            console.error('❌ Erro ao parsear JSON:', jsonError);
                            console.log('❌ Resposta não é JSON válido');
                            console.log('📋 Primeiros 500 caracteres:', responseText.substring(0, 500));
                            console.log('📋 Últimos 500 caracteres:', responseText.substring(responseText.length - 500));
                            console.log('📊 Tamanho total da resposta:', responseText.length, 'bytes');
                            console.log('🔍 Código do primeiro caractere:', responseText.charCodeAt(0));
                            console.log('🔍 Primeiro caractere visível:', JSON.stringify(responseText.charAt(0)));

                            alert('❌ ERRO: Resposta do servidor não é JSON válido!\n\n' +
                                  'Tamanho: ' + responseText.length + ' bytes\n' +
                                  'Primeiro char: ' + JSON.stringify(responseText.charAt(0)) + ' (código: ' + responseText.charCodeAt(0) + ')\n\n' +
                                  'Verifique o CONSOLE (F12) para ver a resposta completa.');
                        }
                    })
                    .catch(error => {
                        console.error('❌ Erro de rede:', error);
                        alert('Erro de conexão. Tente novamente.');
                    })
                    .finally(() => {
                        // Restaurar botão
                        if (btnCriar) {
                            btnCriar.disabled = false;
                            if (btnTexto) btnTexto.textContent = textoOriginal;
                        }
                    });
                });
            }

            // Aguardar carregamento dos ícones
            setTimeout(() => {
                if (typeof lucide !== 'undefined' && lucide.createIcons) {
                    lucide.createIcons();
                }
            }, 100);
        });

        // Inicializar ícones Lucide e sistemas
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }

            // Inicializar sistema de andamentos com timeout para aguardar carregamento
            function tentarInicializarAndamentos(tentativa = 0) {
                if (typeof initAndamentos === 'function') {
                    console.log('🔄 Inicializando sistema de andamentos...');
                    initAndamentos();
                } else if (tentativa < 5) {
                    console.log(`⏳ Aguardando carregamento da função initAndamentos (tentativa ${tentativa + 1}/5)...`);
                    setTimeout(() => tentarInicializarAndamentos(tentativa + 1), 100);
                } else {
                    console.log('⚠️ Função initAndamentos não encontrada após 5 tentativas - sistema de andamentos não inicializado');
                }
            }

            tentarInicializarAndamentos();

            console.log('✅ Dashboard carregado com sistema modular de licitações');
        });
    </script>

    <!-- Sistema de Controle de Timeout de Sessão -->
    <script src="assets/session-timeout.js"></script>
</body>
</html>
