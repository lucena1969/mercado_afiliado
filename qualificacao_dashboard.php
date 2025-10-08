<?php
require_once 'config.php';
require_once 'functions.php';

verificarLogin();

$pdo = conectarDB();

// ========================================
// SISTEMA DE PAGINAÇÃO E FILTROS
// ========================================

// Configuração de paginação
$qualificacoes_por_pagina = isset($_GET['por_pagina']) ? max(10, min(100, intval($_GET['por_pagina']))) : 10;
$pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_atual - 1) * $qualificacoes_por_pagina;

// Processar filtros
$filtro_status = $_GET['status_filtro'] ?? '';
$filtro_modalidade = $_GET['modalidade_filtro'] ?? '';
$filtro_area = $_GET['area_filtro'] ?? '';
$filtro_busca = $_GET['busca'] ?? '';

$where_conditions = ['1=1'];
$params = [];

if (!empty($filtro_status)) {
    $where_conditions[] = "status = ?";
    $params[] = $filtro_status;
}

if (!empty($filtro_modalidade)) {
    $where_conditions[] = "modalidade = ?";
    $params[] = $filtro_modalidade;
}

if (!empty($filtro_area)) {
    $where_conditions[] = "area_demandante LIKE ?";
    $params[] = "%$filtro_area%";
}

if (!empty($filtro_busca)) {
    $where_conditions[] = "(nup LIKE ? OR responsavel LIKE ? OR palavras_chave LIKE ? OR objeto LIKE ?)";
    $busca_param = "%$filtro_busca%";
    $params[] = $busca_param;
    $params[] = $busca_param;
    $params[] = $busca_param;
    $params[] = $busca_param;
}

$where_clause = implode(' AND ', $where_conditions);

// Buscar áreas requisitantes disponíveis da tabela pca_dados
$areas_requisitantes = [];
try {
    $check_pca_table = $pdo->query("SHOW TABLES LIKE 'pca_dados'");
    if ($check_pca_table->rowCount() > 0) {
        $stmt_areas = $pdo->query("
            SELECT DISTINCT area_requisitante
            FROM pca_dados
            WHERE area_requisitante IS NOT NULL
            AND area_requisitante != ''
            ORDER BY area_requisitante ASC
        ");
        $areas_requisitantes = $stmt_areas->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (Exception $e) {
    // Em caso de erro, continuar com array vazio
    $areas_requisitantes = [];
}

// Buscar estatísticas das qualificações
try {
    // Verificar se a tabela existe
    $check_table = $pdo->query("SHOW TABLES LIKE 'qualificacoes'");
    if ($check_table->rowCount() == 0) {
        // Tabela não existe - usar dados zerados
        $stats = [
            'total_qualificacoes' => 0,
            'primeira_analise' => 0,
            'segunda_analise' => 0,
            'em_andamento' => 0,
            'concluidas' => 0,
            'arquivadas' => 0,
            'valor_total' => 0.00,
            'valor_medio' => 0.00,
            'menor_valor' => 0.00,
            'maior_valor' => 0.00
        ];
        $qualificacoes_recentes = [];
        $total_qualificacoes = 0;
    } else {
        // Buscar estatísticas gerais
        $stats_sql = "SELECT 
            COUNT(*) as total_qualificacoes,
            SUM(CASE WHEN status LIKE '1%AN%LISE' THEN 1 ELSE 0 END) as primeira_analise,
            SUM(CASE WHEN status LIKE '2%AN%LISE' THEN 1 ELSE 0 END) as segunda_analise,
            SUM(CASE WHEN status LIKE 'CONCLU%DO' THEN 1 ELSE 0 END) as concluidas,
            SUM(CASE WHEN status = 'ARQUIVADO' THEN 1 ELSE 0 END) as arquivadas,
            SUM(CASE WHEN status LIKE '%AN%LISE' THEN 1 ELSE 0 END) as em_andamento,
            COALESCE(SUM(valor_estimado), 0) as valor_total,
            COALESCE(AVG(valor_estimado), 0) as valor_medio,
            COALESCE(MIN(valor_estimado), 0) as menor_valor,
            COALESCE(MAX(valor_estimado), 0) as maior_valor
            FROM qualificacoes";
        $stmt_stats = $pdo->query($stats_sql);
        $stats = $stmt_stats->fetch();
        
        // Garantir que os valores não sejam null
        $stats['total_qualificacoes'] = intval($stats['total_qualificacoes']);
        $stats['primeira_analise'] = intval($stats['primeira_analise']);
        $stats['segunda_analise'] = intval($stats['segunda_analise']);
        $stats['em_andamento'] = intval($stats['em_andamento']);
        $stats['concluidas'] = intval($stats['concluidas']);
        $stats['arquivadas'] = intval($stats['arquivadas']);
        $stats['valor_total'] = floatval($stats['valor_total'] ?? 0.00);
        $stats['valor_medio'] = floatval($stats['valor_medio'] ?? 0.00);
        $stats['menor_valor'] = floatval($stats['menor_valor'] ?? 0.00);
        $stats['maior_valor'] = floatval($stats['maior_valor'] ?? 0.00);
        
        // Estatísticas por modalidade
        $modalidades_sql = "SELECT 
            modalidade,
            COUNT(*) as quantidade,
            SUM(valor_estimado) as valor_total_modalidade,
            AVG(valor_estimado) as valor_medio_modalidade,
            SUM(CASE WHEN status = 'CONCLUÍDO' THEN 1 ELSE 0 END) as concluidos_modalidade,
            ROUND((SUM(CASE WHEN status = 'CONCLUÍDO' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)), 1) as taxa_conclusao
            FROM qualificacoes 
            GROUP BY modalidade 
            ORDER BY quantidade DESC";
        $stmt_modalidades = $pdo->query($modalidades_sql);
        $stats_modalidades = $stmt_modalidades->fetchAll();
        
        // Estatísticas por área demandante (top 5)
        $areas_sql = "SELECT 
            area_demandante,
            COUNT(*) as quantidade,
            SUM(valor_estimado) as valor_total_area,
            AVG(valor_estimado) as valor_medio_area,
            SUM(CASE WHEN status = 'CONCLUÍDO' THEN 1 ELSE 0 END) as concluidos_area,
            ROUND((SUM(CASE WHEN status = 'CONCLUÍDO' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)), 1) as taxa_conclusao_area
            FROM qualificacoes 
            GROUP BY area_demandante 
            ORDER BY quantidade DESC 
            LIMIT 5";
        $stmt_areas = $pdo->query($areas_sql);
        $stats_areas = $stmt_areas->fetchAll();
        
        // Estatísticas temporais (últimos 6 meses)
        $temporais_sql = "SELECT 
            DATE_FORMAT(criado_em, '%Y-%m') as mes_ano,
            DATE_FORMAT(criado_em, '%M/%Y') as mes_formatado,
            COUNT(*) as quantidade_mes,
            SUM(valor_estimado) as valor_mes,
            SUM(CASE WHEN status = 'CONCLUÍDO' THEN 1 ELSE 0 END) as concluidos_mes,
            AVG(DATEDIFF(
                CASE WHEN status = 'CONCLUÍDO' THEN atualizado_em ELSE CURDATE() END, 
                criado_em
            )) as tempo_medio_dias
            FROM qualificacoes 
            WHERE criado_em >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(criado_em, '%Y-%m')
            ORDER BY mes_ano DESC";
        $stmt_temporais = $pdo->query($temporais_sql);
        $stats_temporais = $stmt_temporais->fetchAll();
        
        // Estatísticas de performance (tempo médio, eficiência)
        $performance_sql = "SELECT 
            COUNT(*) as total_processadas,
            AVG(DATEDIFF(atualizado_em, criado_em)) as tempo_medio_processamento,
            MIN(DATEDIFF(atualizado_em, criado_em)) as tempo_min_processamento,
            MAX(DATEDIFF(atualizado_em, criado_em)) as tempo_max_processamento,
            COUNT(CASE WHEN DATEDIFF(atualizado_em, criado_em) <= 30 THEN 1 END) as processadas_30_dias,
            COUNT(CASE WHEN DATEDIFF(atualizado_em, criado_em) <= 60 THEN 1 END) as processadas_60_dias
            FROM qualificacoes 
            WHERE status = 'CONCLUÍDO'";
        $stmt_performance = $pdo->query($performance_sql);
        $stats_performance = $stmt_performance->fetch();
        
        // Top responsáveis (mais ativos)
        $responsaveis_sql = "SELECT 
            responsavel,
            COUNT(*) as quantidade_responsavel,
            SUM(valor_estimado) as valor_total_responsavel,
            SUM(CASE WHEN status = 'CONCLUÍDO' THEN 1 ELSE 0 END) as concluidos_responsavel,
            ROUND((SUM(CASE WHEN status = 'CONCLUÍDO' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)), 1) as taxa_conclusao_responsavel,
            AVG(CASE WHEN status = 'CONCLUÍDO' THEN DATEDIFF(atualizado_em, criado_em) END) as tempo_medio_responsavel
            FROM qualificacoes 
            GROUP BY responsavel 
            HAVING COUNT(*) >= 2
            ORDER BY quantidade_responsavel DESC 
            LIMIT 5";
        $stmt_responsaveis = $pdo->query($responsaveis_sql);
        $stats_responsaveis = $stmt_responsaveis->fetchAll();
        
        // Contar total com filtros
        $sql_count = "SELECT COUNT(*) as total FROM qualificacoes WHERE $where_clause";
        $stmt_count = $pdo->prepare($sql_count);
        $stmt_count->execute($params);
        $total_qualificacoes = $stmt_count->fetch()['total'];
        
        // Buscar qualificações com paginação e filtros (incluindo dados PCA vinculados)
        $qualificacoes_sql = "SELECT q.*, 
                                     p.numero_dfd,
                                     p.titulo_contratacao as pca_titulo,
                                     p.area_requisitante as pca_area,
                                     p.valor_total_contratacao as pca_valor,
                                     p.situacao_execucao as pca_situacao,
                                     p.data_conclusao_processo as pca_data_conclusao,
                                     p.numero_contratacao as pca_numero
                             FROM qualificacoes q
                             LEFT JOIN pca_dados p ON q.pca_dados_id = p.id
                             WHERE $where_clause 
                             ORDER BY q.criado_em DESC 
                             LIMIT $qualificacoes_por_pagina OFFSET $offset";
        $stmt_qualificacoes = $pdo->prepare($qualificacoes_sql);
        $stmt_qualificacoes->execute($params);
        $qualificacoes_recentes = $stmt_qualificacoes->fetchAll();
    }
} catch (Exception $e) {
    // Em caso de erro, usar dados zerados
    $stats = [
        'total_qualificacoes' => 0,
        'primeira_analise' => 0,
        'segunda_analise' => 0,
        'em_andamento' => 0,
        'concluidas' => 0,
        'arquivadas' => 0,
        'valor_total' => 0.00,
        'valor_medio' => 0.00,
        'menor_valor' => 0.00,
        'maior_valor' => 0.00
    ];
    $qualificacoes_recentes = [];
    $total_qualificacoes = 0;
    $stats_modalidades = [];
    $stats_areas = [];
    $stats_temporais = [];
    $stats_performance = [];
    $stats_responsaveis = [];
}

// Calcular informações de paginação
$total_paginas = ceil($total_qualificacoes / $qualificacoes_por_pagina);
$inicio_item = ($pagina_atual - 1) * $qualificacoes_por_pagina + 1;
$fim_item = min($pagina_atual * $qualificacoes_por_pagina, $total_qualificacoes);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qualificação - Sistema CGLIC</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/qualificacao-dashboard.css">
    <link rel="stylesheet" href="assets/mobile-improvements.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    
    <style>
        /* FORÇAR BARRA DE ROLAGEM NA SIDEBAR */
        .sidebar-nav {
            overflow-y: scroll !important;
            overflow-x: hidden !important;
            height: calc(100vh - 180px) !important;
            max-height: calc(100vh - 180px) !important;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 8px !important;
        }

        .sidebar-nav::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1) !important;
            border-radius: 4px !important;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.4) !important;
            border-radius: 4px !important;
        }

        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.6) !important;
        }
        
        /* CSS para Cards de Qualificações */
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 24px;
            padding: 20px 0;
        }

        .qualificacao-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .qualificacao-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
            border-color: #3b82f6;
        }

        .qualificacao-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #f59e0b, #d97706);
        }

        .qualificacao-card.status-aprovado::before {
            background: linear-gradient(90deg, #10b981, #059669);
        }

        .qualificacao-card.status-em-andamento::before {
            background: linear-gradient(90deg, #f59e0b, #d97706);
        }
        
        .qualificacao-card.status-segunda-analise::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #9b59b6, #8e44ad);
        }
        
        .qualificacao-card.status-arquivado::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #6c757d, #495057);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .card-nup {
            font-size: 16px;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 4px;
        }

        .card-modalidade {
            align-self: flex-start;
        }

        .card-body {
            margin-bottom: 20px;
        }

        .card-objeto {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 12px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .card-info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .card-info-label {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .card-info-value {
            font-size: 14px;
            font-weight: 500;
            color: #374151;
        }

        .card-valor {
            font-size: 18px;
            font-weight: 700;
            color: #059669;
        }

        /* Seção PCA Vinculado */
        .pca-vinculado-section {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 2px solid #f3f4f6;
            position: relative;
        }
        .pca-vinculado-section.sem-vinculo {
            border-top-color: #fde68a;
            background: #fefcbf;
            margin: 16px -24px -24px -24px;
            padding: 16px 24px 20px 24px;
            border-radius: 0 0 16px 16px;
        }
        .pca-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .pca-vinculado-section.sem-vinculo .pca-header {
            color: #92400e;
        }
        .pca-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
        }
        .pca-info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .pca-info-label {
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .pca-info-value {
            font-size: 13px;
            font-weight: 600;
            color: #1f2937;
        }
        .pca-info-value.pca-situacao {
            color: #059669;
            font-weight: 700;
        }
        .btn-vincular-pca {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .btn-vincular-pca:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid #f3f4f6;
        }

        .card-status {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-actions {
            display: flex;
            gap: 8px;
        }

        .card-action-btn {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .card-action-btn:hover {
            transform: scale(1.05);
        }

        .btn-view {
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }

        .btn-view:hover {
            background: #bbdefb;
        }

        .btn-edit {
            background: #fff3e0;
            color: #f57c00;
            border: 1px solid #ffcc02;
        }

        .btn-edit:hover {
            background: #ffcc02;
            color: white;
        }

        .btn-delete {
            background: #ffebee;
            color: #d32f2f;
            border: 1px solid #ef5350;
        }

        .btn-delete:hover {
            background: #ef5350;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #6b7280;
            grid-column: 1 / -1;
        }

        .empty-state-icon {
            margin-bottom: 16px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .cards-container {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .qualificacao-card {
                padding: 20px;
            }
            
            .card-info-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Estilos específicos para o card Arquivadas */
        .stat-card.arquivadas {
            border-left: 5px solid #6c757d;
        }
        
        .stat-card.arquivadas .stat-number {
            color: #6c757d;
        }
        
        /* Grid de estatísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            color: #333;
            padding: 24px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 3px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 8px;
            color: #2d3748;
        }
        
        .stat-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #4a5568;
            font-weight: 600;
        }
        
        /* Cores específicas dos cards - bordas coloridas */
        .stat-card.total {
            border-left: 5px solid #667eea;
        }
        
        .stat-card.total .stat-number {
            color: #667eea;
        }
        
        .stat-card.andamento {
            border-left: 5px solid #f5576c;
        }
        
        .stat-card.andamento .stat-number {
            color: #f5576c;
        }
        
        .stat-card.aprovados {
            border-left: 5px solid #4facfe;
        }
        
        .stat-card.aprovados .stat-number {
            color: #4facfe;
        }
        
        .stat-card.valor {
            border-left: 5px solid #43e97b;
        }
        
        .stat-card.valor .stat-number {
            color: #43e97b;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i data-lucide="menu"></i>
                </button>
                <h2><i data-lucide="award"></i> Qualificação</h2>
            </div>
            
            <nav class="sidebar-nav">
                <!-- Navegação Principal -->
                <div class="nav-section">
                    <div class="nav-section-title">Dashboard</div>
                    <a href="javascript:void(0)" class="nav-item active" onclick="showSection('dashboard')">
                        <i data-lucide="chart-line"></i>
                        <span>Painel Principal</span>
                    </a>
                </div>
                
                <!-- Qualificações -->
                <div class="nav-section">
                    <div class="nav-section-title">Qualificações</div>
                    <a href="javascript:void(0)" class="nav-item" onclick="showSection('lista-qualificacoes')">
                        <i data-lucide="list"></i>
                        <span>Qualificações</span>
                    </a>
                </div>
                
                <!-- Relatórios -->
                <div class="nav-section">
                    <div class="nav-section-title">Relatórios</div>
                    <a href="javascript:void(0)" class="nav-item" onclick="showSection('relatorios')">
                        <i data-lucide="file-text"></i>
                        <span>Relatórios</span>
                    </a>
                    <a href="relatorios_gerenciais.php" class="nav-item">
                        <i data-lucide="pie-chart"></i>
                        <span>Relatórios Gerenciais</span>
                    </a>
                    <a href="javascript:void(0)" class="nav-item" onclick="showSection('estatisticas')">
                        <i data-lucide="bar-chart-3"></i>
                        <span>Estatísticas</span>
                    </a>
                </div>
                
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
                    <a href="licitacao_dashboard.php" class="nav-item">
                        <i data-lucide="gavel"></i>
                        <span>Licitações</span>
                    </a>
                    <a href="contratos_dashboard.php" class="nav-item">
                        <i data-lucide="file-text"></i>
                        <span>Contratos</span>
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
                    </div>
                </div>
                <a href="perfil_usuario.php" class="logout-btn" style="text-decoration: none; margin-bottom: 10px; background: #27ae60 !important;">
                    <i data-lucide="user"></i> <span>Meu Perfil</span>
                </a>
                <button class="logout-btn" onclick="window.location.href='logout.php'">
                    <i data-lucide="log-out"></i>
                    <span>Sair</span>
                </button>
            </div>
        </div>
        
        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            
            <!-- Dashboard Principal -->
            <section id="dashboard" class="content-section active">
                <!-- Header -->
                <div class="dashboard-header">
                    <h1 style="color: white;"><i data-lucide="award"></i> Painel de Qualificações</h1>
                    <p style="color: white;">Gerencie qualificações de artefatos processuais, avalie e acompanhe o processo de qualificação</p>
                </div>
                
                <!-- Cards de Estatísticas -->
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-number"><?php echo number_format($stats['total_qualificacoes']); ?></div>
                        <div class="stat-label">Total Qualificações</div>
                    </div>
                    <div class="stat-card andamento">
                        <div class="stat-number"><?php echo number_format($stats['em_andamento']); ?></div>
                        <div class="stat-label">Em Análise</div>
                    </div>
                    <div class="stat-card aprovados">
                        <div class="stat-number"><?php echo number_format($stats['concluidas']); ?></div>
                        <div class="stat-label">Concluídas</div>
                    </div>
                    <div class="stat-card arquivadas">
                        <div class="stat-number"><?php echo number_format($stats['arquivadas']); ?></div>
                        <div class="stat-label">Arquivadas</div>
                    </div>
                    <div class="stat-card valor">
                        <div class="stat-number"><?php echo abreviarValor($stats['valor_total']); ?></div>
                        <div class="stat-label">Valor Total</div>
                    </div>
                </div>
                
                <!-- Gráficos -->
                <div class="charts-grid">
                    <div class="chart-card">
                        <div class="chart-title">
                            <i data-lucide="bar-chart"></i>
                            Status das Qualificações
                        </div>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="chart-card">
                        <div class="chart-title">
                            <i data-lucide="users"></i>
                            Performance por Responsável
                        </div>
                        <div class="chart-container">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Seção removida - será recriada como modal seguindo padrão de licitações -->
            
            <!-- Lista de Qualificações -->
            <section id="lista-qualificacoes" class="content-section">
                <div class="dashboard-header">
                    <h1 style="color: white;"><i data-lucide="list"></i> Lista de Qualificações</h1>
                    <p style="color: white;">Visualize e gerencie todas as qualificações cadastradas</p>
                </div>
                
                <div class="table-container">
                    <!-- Controles e Filtros -->
                    <div class="table-controls">
                        <div class="table-info">
                            <h3>Qualificações Cadastradas</h3>
                            <p>Total: <?php echo number_format($total_qualificacoes); ?> qualificações</p>
                        </div>
                        
                        <div class="table-actions">
                            <button onclick="abrirModal('modalCriarQualificacao')" class="btn-primary">
                                <i data-lucide="plus-circle"></i> Nova Qualificação
                            </button>
                        </div>
                    </div>
                    
                    <!-- Filtros de Busca -->
                    <div class="filter-container">
                        <form method="GET" class="filter-form" id="filtroQualificacoes">
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label for="busca">
                                        <i data-lucide="search"></i> Buscar:
                                    </label>
                                    <input type="text" id="busca" name="busca" 
                                           value="<?php echo htmlspecialchars($filtro_busca); ?>"
                                           placeholder="NUP, responsável, palavra-chave ou objeto..."
                                           class="filter-input">
                                </div>
                                
                                <div class="filter-group">
                                    <label for="status_filtro">
                                        <i data-lucide="check-circle"></i> Status:
                                    </label>
                                    <select id="status_filtro" name="status_filtro" class="filter-select">
                                        <option value="">Todos os status</option>
                                        <option value="EM ANÁLISE" <?php echo $filtro_status === 'EM ANÁLISE' ? 'selected' : ''; ?>>EM ANÁLISE</option>
                                        <option value="CONCLUÍDO" <?php echo $filtro_status === 'CONCLUÍDO' ? 'selected' : ''; ?>>CONCLUÍDO</option>
                                        <option value="ARQUIVADO" <?php echo $filtro_status === 'ARQUIVADO' ? 'selected' : ''; ?>>ARQUIVADO</option>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <label for="modalidade_filtro">
                                        <i data-lucide="gavel"></i> Modalidade:
                                    </label>
                                    <select id="modalidade_filtro" name="modalidade_filtro" class="filter-select">
                                        <option value="">Todas as modalidades</option>
                                        <option value="PREGÃO" <?php echo $filtro_modalidade === 'PREGÃO' ? 'selected' : ''; ?>>PREGÃO</option>
                                        <option value="PREGÃO SRP" <?php echo $filtro_modalidade === 'PREGÃO SRP' ? 'selected' : ''; ?>>PREGÃO SRP</option>
                                        <option value="CONCORRÊNCIA" <?php echo $filtro_modalidade === 'CONCORRÊNCIA' ? 'selected' : ''; ?>>CONCORRÊNCIA</option>
                                        <option value="CONCURSO" <?php echo $filtro_modalidade === 'CONCURSO' ? 'selected' : ''; ?>>CONCURSO</option>
                                        <option value="LEILÃO" <?php echo $filtro_modalidade === 'LEILÃO' ? 'selected' : ''; ?>>LEILÃO</option>
                                        <option value="INEXIGIBILIDADE" <?php echo $filtro_modalidade === 'INEXIGIBILIDADE' ? 'selected' : ''; ?>>INEXIGIBILIDADE</option>
                                        <option value="DISPENSA" <?php echo $filtro_modalidade === 'DISPENSA' ? 'selected' : ''; ?>>DISPENSA</option>
                                        
                                        <option value="DIÁLOGO COMPETITIVO" <?php echo $filtro_modalidade === 'DIÁLOGO COMPETITIVO' ? 'selected' : ''; ?>>DIÁLOGO COMPETITIVO</option>
                                        <option value="ADESÃO" <?php echo $filtro_modalidade === 'ADESÃO' ? 'selected' : ''; ?>>ADESÃO</option>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <label for="por_pagina">
                                        <i data-lucide="list"></i> Por página:
                                    </label>
                                    <select id="por_pagina" name="por_pagina" class="filter-select">
                                        <option value="10" <?php echo $qualificacoes_por_pagina == 10 ? 'selected' : ''; ?>>10</option>
                                        <option value="20" <?php echo $qualificacoes_por_pagina == 20 ? 'selected' : ''; ?>>20</option>
                                        <option value="50" <?php echo $qualificacoes_por_pagina == 50 ? 'selected' : ''; ?>>50</option>
                                        <option value="100" <?php echo $qualificacoes_por_pagina == 100 ? 'selected' : ''; ?>>100</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="filter-actions">
                                <button type="submit" class="btn-primary">
                                    <i data-lucide="search"></i> Filtrar
                                </button>
                                <a href="?" class="btn-secondary">
                                    <i data-lucide="x"></i> Limpar
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Cards Container -->
                    <div class="cards-container">
                        <?php if (!empty($qualificacoes_recentes)): ?>
                            <?php foreach ($qualificacoes_recentes as $qualificacao): ?>
                                <?php
                                // Definir classes para modalidade
                                $modalidade_class = '';
                                switch($qualificacao['modalidade']) {
                                    case 'PREGÃO': $modalidade_class = 'badge-pregao'; break;
                                    case 'PREGÃO SRP': $modalidade_class = 'badge-pregao-srp'; break;
                                    case 'CONCORRÊNCIA': $modalidade_class = 'badge-concorrencia'; break;
                                    case 'CONCURSO': $modalidade_class = 'badge-concurso'; break;
                                    case 'LEILÃO': $modalidade_class = 'badge-leilao'; break;
                                    case 'INEXIGIBILIDADE': $modalidade_class = 'badge-inexigibilidade'; break;
                                    case 'DISPENSA': $modalidade_class = 'badge-dispensa'; break;
                                    
                                    case 'DIÁLOGO COMPETITIVO': $modalidade_class = 'badge-dialogo'; break;
                                    case 'ADESÃO': $modalidade_class = 'badge-adesao'; break;
                                    default: $modalidade_class = 'badge-default';
                                }
                                
                                // Definir classes para status
                                $status_class = '';
                                $card_status_class = '';
                                // Usar strpos para lidar com codificação de acentos
                                if (strpos($qualificacao['status'], 'CONCLU') !== false) {
                                    $status_class = 'status-aprovado';
                                    $card_status_class = 'status-aprovado';
                                } elseif (strpos($qualificacao['status'], '1') !== false && strpos($qualificacao['status'], 'AN') !== false) {
                                    $status_class = 'status-em-andamento';
                                    $card_status_class = 'status-em-andamento';
                                } elseif (strpos($qualificacao['status'], '2') !== false && strpos($qualificacao['status'], 'AN') !== false) {
                                    $status_class = 'status-segunda-analise';
                                    $card_status_class = 'status-segunda-analise';
                                } elseif (strpos($qualificacao['status'], 'ARQUIVADO') !== false) {
                                    $status_class = 'status-arquivado';
                                    $card_status_class = 'status-arquivado';
                                } else {
                                    $status_class = 'status-pendente';
                                    $card_status_class = 'status-pendente';
                                }
                                ?>
                                
                                <div class="qualificacao-card <?php echo $card_status_class; ?>">
                                    <!-- Card Header -->
                                    <div class="card-header">
                                        <div>
                                            <div class="card-nup"><?php echo htmlspecialchars($qualificacao['nup']); ?></div>
                                        </div>
                                        <div class="card-modalidade">
                                            <span class="modalidade-badge <?php echo $modalidade_class; ?>">
                                                <?php echo htmlspecialchars($qualificacao['modalidade']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Card Body -->
                                    <div class="card-body">
                                        <div class="card-objeto" title="<?php echo htmlspecialchars($qualificacao['objeto']); ?>">
                                            <?php echo htmlspecialchars($qualificacao['objeto']); ?>
                                        </div>
                                        
                                        <div class="card-info-grid">
                                            <div class="card-info-item">
                                                <span class="card-info-label">Área Demandante</span>
                                                <span class="card-info-value"><?php echo htmlspecialchars($qualificacao['area_demandante']); ?></span>
                                            </div>
                                            <div class="card-info-item">
                                                <span class="card-info-label">Responsável</span>
                                                <span class="card-info-value"><?php echo htmlspecialchars($qualificacao['responsavel']); ?></span>
                                            </div>
                                            <div class="card-info-item">
                                                <span class="card-info-label">Valor Estimado</span>
                                                <span class="card-info-value card-valor"><?php echo formatarMoeda($qualificacao['valor_estimado']); ?></span>
                                            </div>
                                            <div class="card-info-item">
                                                <span class="card-info-label">Criado em</span>
                                                <span class="card-info-value"><?php echo date('d/m/Y', strtotime($qualificacao['criado_em'] ?? 'now')); ?></span>
                                            </div>
                                        </div>

                                        <?php if (!empty($qualificacao['numero_dfd'])): ?>
                                        <!-- Seção PCA Vinculado -->
                                        <div class="pca-vinculado-section">
                                            <div class="pca-header">
                                                <i data-lucide="link-2"></i>
                                                <span>PCA Vinculado</span>
                                            </div>
                                            <div class="pca-info-grid">
                                                <div class="pca-info-item">
                                                    <span class="pca-info-label">DFD</span>
                                                    <span class="pca-info-value"><?php echo htmlspecialchars($qualificacao['numero_dfd']); ?></span>
                                                </div>
                                                <?php if (!empty($qualificacao['pca_numero'])): ?>
                                                <div class="pca-info-item">
                                                    <span class="pca-info-label">Contratação</span>
                                                    <span class="pca-info-value"><?php echo htmlspecialchars($qualificacao['pca_numero']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if (!empty($qualificacao['pca_situacao'])): ?>
                                                <div class="pca-info-item">
                                                    <span class="pca-info-label">Situação PCA</span>
                                                    <span class="pca-info-value pca-situacao"><?php echo htmlspecialchars($qualificacao['pca_situacao']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <!-- Seletor de PCA (quando não há vinculação) -->
                                        <div class="pca-vinculado-section sem-vinculo">
                                            <div class="pca-header">
                                                <i data-lucide="alert-circle"></i>
                                                <span>Sem Vinculação PCA</span>
                                            </div>
                                            <div class="pca-actions">
                                                <button class="btn-vincular-pca" onclick="abrirSeletorPCA(<?php echo $qualificacao['id']; ?>)">
                                                    <i data-lucide="plus"></i>
                                                    Vincular ao PCA
                                                </button>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Card Footer -->
                                    <div class="card-footer">
                                        <div class="card-status">
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($qualificacao['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="card-actions">
                                            <button onclick="visualizarQualificacao(<?php echo $qualificacao['id']; ?>)" title="Ver Detalhes" class="card-action-btn btn-view">
                                                <i data-lucide="eye" style="width: 16px; height: 16px;"></i>
                                            </button>
                                            <button onclick="editarQualificacao(<?php echo $qualificacao['id']; ?>)" title="Editar" class="card-action-btn btn-edit">
                                                <i data-lucide="edit" style="width: 16px; height: 16px;"></i>
                                            </button>
                                            <button onclick="excluirQualificacao(<?php echo $qualificacao['id']; ?>)" title="Excluir" class="card-action-btn btn-delete">
                                                <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i data-lucide="inbox" style="width: 64px; height: 64px;"></i>
                                </div>
                                <h3>Nenhuma qualificação encontrada</h3>
                                <p>Não há qualificações cadastradas ou que correspondam aos filtros aplicados.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Paginação -->
                    <?php if ($total_qualificacoes > 0): ?>
                    <div class="pagination-container">
                        <div class="pagination-info">
                            <span>Mostrando <?php echo number_format($inicio_item); ?> a <?php echo number_format($fim_item); ?> de <?php echo number_format($total_qualificacoes); ?> qualificações</span>
                        </div>
                        
                        <?php if ($total_paginas > 1): ?>
                        <div class="pagination">
                            <?php
                            $query_params = $_GET;
                            $base_url = '?' . http_build_query(array_merge($query_params, ['pagina' => '']));
                            $base_url = rtrim($base_url, '=');
                            ?>
                            
                            <!-- Primeira página -->
                            <?php if ($pagina_atual > 1): ?>
                                <a href="<?php echo $base_url; ?>=1" class="pagination-btn">
                                    <i data-lucide="chevrons-left"></i>
                                </a>
                                <a href="<?php echo $base_url; ?>=<?php echo $pagina_atual - 1; ?>" class="pagination-btn">
                                    <i data-lucide="chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <!-- Páginas numeradas -->
                            <?php
                            $inicio_pag = max(1, $pagina_atual - 2);
                            $fim_pag = min($total_paginas, $pagina_atual + 2);
                            
                            for ($i = $inicio_pag; $i <= $fim_pag; $i++):
                            ?>
                                <a href="<?php echo $base_url; ?>=<?php echo $i; ?>" 
                                   class="pagination-btn <?php echo $i === $pagina_atual ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <!-- Última página -->
                            <?php if ($pagina_atual < $total_paginas): ?>
                                <a href="<?php echo $base_url; ?>=<?php echo $pagina_atual + 1; ?>" class="pagination-btn">
                                    <i data-lucide="chevron-right"></i>
                                </a>
                                <a href="<?php echo $base_url; ?>=<?php echo $total_paginas; ?>" class="pagination-btn">
                                    <i data-lucide="chevrons-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
            
            
            <!-- Relatórios Unificados -->
            <section id="relatorios" class="content-section">
                <div class="dashboard-header">
                    <h1 style="color: white;"><i data-lucide="filter"></i> Relatório Detalhado de Qualificações</h1>
                    <p style="color: white;">Filtros avançados e visualização unificada das qualificações</p>
                </div>

                <!-- Formulário de Filtros -->
                <div class="relatorio-unificado">
                    <form id="form_relatorio_qualificacoes" method="GET">
                        <div class="filtros-container">
                            <h3><i data-lucide="filter"></i> Filtros de Pesquisa</h3>

                            <div class="filtros-row">
                                <div class="filtro-item">
                                    <label for="status_filtro">Status:</label>
                                    <select name="status_filtro" id="status_filtro">
                                        <option value="">Todos os Status</option>
                                        <option value="EM ANÁLISE" <?php echo ($filtro_status == 'EM ANÁLISE') ? 'selected' : ''; ?>>Em Análise</option>
                                        <option value="CONCLUÍDO" <?php echo ($filtro_status == 'CONCLUÍDO') ? 'selected' : ''; ?>>Concluído</option>
                                        <option value="ARQUIVADO" <?php echo ($filtro_status == 'ARQUIVADO') ? 'selected' : ''; ?>>Arquivado</option>
                                    </select>
                                </div>

                                <div class="filtro-item">
                                    <label for="modalidade_filtro">Modalidade:</label>
                                    <select name="modalidade_filtro" id="modalidade_filtro">
                                        <option value="">Todas as Modalidades</option>
                                        <option value="PREGÃO ELETRÔNICO" <?php echo ($filtro_modalidade == 'PREGÃO ELETRÔNICO') ? 'selected' : ''; ?>>Pregão Eletrônico</option>
                                        <option value="PREGÃO PRESENCIAL" <?php echo ($filtro_modalidade == 'PREGÃO PRESENCIAL') ? 'selected' : ''; ?>>Pregão Presencial</option>
                                        <option value="TOMADA DE PREÇOS" <?php echo ($filtro_modalidade == 'TOMADA DE PREÇOS') ? 'selected' : ''; ?>>Tomada de Preços</option>
                                        <option value="CONCORRÊNCIA" <?php echo ($filtro_modalidade == 'CONCORRÊNCIA') ? 'selected' : ''; ?>>Concorrência</option>
                                        <option value="DISPENSA" <?php echo ($filtro_modalidade == 'DISPENSA') ? 'selected' : ''; ?>>Dispensa</option>
                                        <option value="INEXIGIBILIDADE" <?php echo ($filtro_modalidade == 'INEXIGIBILIDADE') ? 'selected' : ''; ?>>Inexigibilidade</option>
                                    </select>
                                </div>

                                <div class="filtro-item">
                                    <label for="area_filtro">Área Demandante:</label>
                                    <select name="area_filtro" id="area_filtro">
                                        <option value="">Todas as Áreas (<?php echo count($areas_requisitantes); ?> disponíveis)</option>
                                        <?php foreach ($areas_requisitantes as $area): ?>
                                            <option value="<?php echo htmlspecialchars($area); ?>" <?php echo ($filtro_area == $area) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($area); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="filtros-row">
                                <div class="filtro-item">
                                    <label for="data_inicial">Data Inicial:</label>
                                    <input type="date" name="data_inicial" id="data_inicial" value="<?php echo date('Y-01-01'); ?>">
                                </div>

                                <div class="filtro-item">
                                    <label for="data_final">Data Final:</label>
                                    <input type="date" name="data_final" id="data_final" value="<?php echo date('Y-m-d'); ?>">
                                </div>

                                <div class="filtro-item filtro-busca">
                                    <label for="busca_texto">Busca Geral:</label>
                                    <input type="text" name="busca" id="busca_texto" placeholder="NUP, Responsável, Objeto..." value="<?php echo htmlspecialchars($filtro_busca); ?>">
                                </div>
                            </div>

                            <div class="filtros-row filtros-acoes">
                                <button type="submit" class="btn-primary">
                                    <i data-lucide="search"></i> Filtrar Resultados
                                </button>
                                <button type="button" onclick="limparFiltros()" class="btn-secondary">
                                    <i data-lucide="x"></i> Limpar Filtros
                                </button>
                                <button type="button" onclick="exportarRelatorio()" class="btn-success">
                                    <i data-lucide="download"></i> Exportar
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Resumo Estatístico -->
                    <div class="stats-resumo">
                        <div class="stat-item stat-total">
                            <span class="stat-numero"><?php echo number_format($total_qualificacoes); ?></span>
                            <span class="stat-label">Registros Encontrados</span>
                        </div>
                        <div class="stat-item stat-valor">
                            <span class="stat-numero">R$ <?php echo number_format($stats['valor_total'] ?? 0, 2, ',', '.'); ?></span>
                            <span class="stat-label">Valor Total</span>
                        </div>
                        <div class="stat-item stat-medio">
                            <span class="stat-numero">R$ <?php echo number_format($stats['valor_medio'] ?? 0, 2, ',', '.'); ?></span>
                            <span class="stat-label">Valor Médio</span>
                        </div>
                    </div>

                    <!-- Tabela de Resultados -->
                    <div class="resultados-container">
                        <div class="tabela-header">
                            <h3><i data-lucide="table"></i> Resultados da Pesquisa</h3>
                            <div class="paginacao-info">
                                Exibindo <?php echo $inicio_item; ?> - <?php echo $fim_item; ?> de <?php echo $total_qualificacoes; ?> registros
                            </div>
                        </div>

                        <div class="tabela-wrapper">
                            <table class="tabela-qualificacoes">
                                <thead>
                                    <tr>
                                        <th>NUP</th>
                                        <th>Área Demandante</th>
                                        <th>Responsável</th>
                                        <th>Modalidade</th>
                                        <th>Status</th>
                                        <th>Objeto (Resumo)</th>
                                        <th class="text-right">Valor Estimado</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="tabela_resultados">
                                    <?php if ($total_qualificacoes > 0): ?>
                                        <?php foreach ($qualificacoes_recentes as $qualificacao): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($qualificacao['nup'] ?? 'N/A'); ?></strong></td>
                                            <td><?php echo htmlspecialchars($qualificacao['area_demandante'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($qualificacao['responsavel'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge badge-modalidade">
                                                    <?php echo htmlspecialchars($qualificacao['modalidade'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status status-<?php echo strtolower(str_replace([' ', 'ª'], ['_', 'a'], $qualificacao['status'] ?? '')); ?>">
                                                    <?php echo htmlspecialchars($qualificacao['status'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td class="objeto-resumo" title="<?php echo htmlspecialchars($qualificacao['objeto'] ?? ''); ?>">
                                                <?php
                                                $objeto = $qualificacao['objeto'] ?? '';
                                                echo htmlspecialchars(strlen($objeto) > 80 ? substr($objeto, 0, 80) . '...' : $objeto);
                                                ?>
                                            </td>
                                            <td class="text-right">
                                                <strong>R$ <?php echo number_format($qualificacao['valor_estimado'] ?? 0, 2, ',', '.'); ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <button onclick="verDetalhes(<?php echo $qualificacao['id']; ?>)" class="btn-icon" title="Ver Detalhes">
                                                    <i data-lucide="eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="sem-resultados">
                                                <i data-lucide="search-x"></i>
                                                <p>Nenhuma qualificação encontrada com os filtros aplicados.</p>
                                                <button onclick="limparFiltros()" class="btn-primary">Limpar Filtros</button>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação -->
                        <?php if ($total_qualificacoes > 0): ?>
                        <div class="paginacao-container">
                            <?php
                            // Usar a mesma lógica de paginação do arquivo original
                            $query_params = $_GET;
                            unset($query_params['pagina']);
                            $base_url = '?' . http_build_query($query_params);
                            ?>

                            <?php if ($pagina_atual > 1): ?>
                                <a href="<?php echo $base_url; ?>&pagina=<?php echo $pagina_atual - 1; ?>" class="btn-paginacao">
                                    <i data-lucide="chevron-left"></i> Anterior
                                </a>
                            <?php endif; ?>

                            <span class="paginacao-info">
                                Página <?php echo $pagina_atual; ?> de <?php echo $total_paginas; ?>
                            </span>

                            <?php if ($pagina_atual < $total_paginas): ?>
                                <a href="<?php echo $base_url; ?>&pagina=<?php echo $pagina_atual + 1; ?>" class="btn-paginacao">
                                    Próxima <i data-lucide="chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Totalizações por Categoria -->
                    <?php if ($total_qualificacoes > 0): ?>
                    <div class="totalizacoes-footer">
                        <div class="totalizacao-grupo">
                            <h4><i data-lucide="pie-chart"></i> Distribuição por Status:</h4>
                            <div class="stats-distribuicao">
                                <span class="stat-badge stat-analise">Em Análise: <?php echo $stats['em_andamento'] ?? 0; ?></span>
                                <span class="stat-badge stat-concluida">Concluída: <?php echo $stats['concluidas'] ?? 0; ?></span>
                                <span class="stat-badge stat-arquivada">Arquivada: <?php echo $stats['arquivadas'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <!-- Estatísticas Avançadas -->
            <section id="estatisticas" class="content-section">
                <div class="dashboard-header">
                    <h1 style="color: white;"><i data-lucide="bar-chart-3"></i> Estatísticas Avançadas</h1>
                    <p style="color: white;">Análise detalhada e métricas de performance do processo de qualificação</p>
                </div>
                
                <!-- Estatísticas Gerais Expandidas -->
                <div class="stats-section">
                    <h2><i data-lucide="trending-up"></i> Visão Geral</h2>
                    <div class="stats-grid-expanded">
                        <div class="stat-card-expanded primary">
                            <div class="stat-icon"><i data-lucide="file-text"></i></div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo number_format($stats['total_qualificacoes']); ?></div>
                                <div class="stat-label">Total de Qualificações</div>
                                <div class="stat-trend">
                                    <?php 
                                    $taxa_conclusao = $stats['total_qualificacoes'] > 0 ? 
                                        round(($stats['concluidas'] / $stats['total_qualificacoes']) * 100, 1) : 0;
                                    ?>
                                    <span class="trend-positive"><?php echo $taxa_conclusao; ?>% concluídas</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stat-card-expanded success">
                            <div class="stat-icon"><i data-lucide="check-circle"></i></div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo number_format($stats['concluidas']); ?></div>
                                <div class="stat-label">Concluídas</div>
                                <div class="stat-trend">
                                    <span class="trend-positive">
                                        <?php echo formatarMoeda($stats['valor_total'] * ($stats['concluidas'] / max($stats['total_qualificacoes'], 1))); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stat-card-expanded warning">
                            <div class="stat-icon"><i data-lucide="clock"></i></div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo number_format($stats['em_andamento']); ?></div>
                                <div class="stat-label">Em Análise</div>
                                <div class="stat-trend">
                                    <?php 
                                    $percentual_andamento = $stats['total_qualificacoes'] > 0 ? 
                                        round(($stats['em_andamento'] / $stats['total_qualificacoes']) * 100, 1) : 0;
                                    ?>
                                    <span class="trend-neutral"><?php echo $percentual_andamento; ?>% do total</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stat-card-expanded info">
                            <div class="stat-icon"><i data-lucide="dollar-sign"></i></div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo formatarMoeda($stats['valor_total']); ?></div>
                                <div class="stat-label">Valor Total</div>
                                <div class="stat-trend">
                                    <span class="trend-neutral">Média: <?php echo formatarMoeda($stats['valor_medio']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Análise de Valores -->
                <div class="stats-section">
                    <h2><i data-lucide="dollar-sign"></i> Análise Financeira</h2>
                    <div class="stats-grid">
                        <div class="stat-card valor-maximo">
                            <div class="stat-header">
                                <i data-lucide="trending-up"></i>
                                <span>Maior Valor</span>
                            </div>
                            <div class="stat-number big"><?php echo formatarMoeda($stats['maior_valor']); ?></div>
                        </div>
                        
                        <div class="stat-card valor-medio">
                            <div class="stat-header">
                                <i data-lucide="minus"></i>
                                <span>Valor Médio</span>
                            </div>
                            <div class="stat-number big"><?php echo formatarMoeda($stats['valor_medio']); ?></div>
                        </div>
                        
                        <div class="stat-card valor-minimo">
                            <div class="stat-header">
                                <i data-lucide="trending-down"></i>
                                <span>Menor Valor</span>
                            </div>
                            <div class="stat-number big"><?php echo formatarMoeda($stats['menor_valor']); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Estatísticas por Modalidade -->
                <?php if (!empty($stats_modalidades)): ?>
                <div class="stats-section">
                    <h2><i data-lucide="pie-chart"></i> Performance por Modalidade</h2>
                    <div class="table-responsive">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Modalidade</th>
                                    <th>Quantidade</th>
                                    <th>Valor Total</th>
                                    <th>Valor Médio</th>
                                    <th>Taxa de Conclusão</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats_modalidades as $modalidade): ?>
                                <tr>
                                    <td>
                                        <span class="modalidade-badge badge-<?php echo strtolower(str_replace(['Ã', ' '], ['a', '-'], $modalidade['modalidade'])); ?>">
                                            <?php echo htmlspecialchars($modalidade['modalidade']); ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo number_format($modalidade['quantidade']); ?></strong></td>
                                    <td><span class="valor-verde"><?php echo formatarMoeda($modalidade['valor_total_modalidade']); ?></span></td>
                                    <td><?php echo formatarMoeda($modalidade['valor_medio_modalidade']); ?></td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $modalidade['taxa_conclusao']; ?>%"></div>
                                            <span class="progress-text"><?php echo $modalidade['taxa_conclusao']; ?>%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($modalidade['taxa_conclusao'] >= 80): ?>
                                            <span class="status-badge status-aprovado">Excelente</span>
                                        <?php elseif ($modalidade['taxa_conclusao'] >= 60): ?>
                                            <span class="status-badge status-em-andamento">Boa</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pendente">Atenção</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Top Áreas Demandantes -->
                <?php if (!empty($stats_areas)): ?>
                <div class="stats-section">
                    <h2><i data-lucide="building"></i> Top 5 Áreas Demandantes</h2>
                    <div class="ranking-cards">
                        <?php foreach ($stats_areas as $index => $area): ?>
                        <div class="ranking-card <?php echo $index < 3 ? 'top-' . ($index + 1) : ''; ?>">
                            <div class="ranking-position"><?php echo $index + 1; ?>º</div>
                            <div class="ranking-content">
                                <h4><?php echo htmlspecialchars($area['area_demandante']); ?></h4>
                                <div class="ranking-stats">
                                    <span><i data-lucide="file-text"></i> <?php echo $area['quantidade']; ?> qualificações</span>
                                    <span><i data-lucide="dollar-sign"></i> <?php echo formatarMoeda($area['valor_total_area']); ?></span>
                                    <span><i data-lucide="percent"></i> <?php echo $area['taxa_conclusao_area']; ?>% concluídas</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Performance de Processamento -->
                <?php if (!empty($stats_performance) && $stats_performance['total_processadas'] > 0): ?>
                <div class="stats-section">
                    <h2><i data-lucide="zap"></i> Performance de Processamento</h2>
                    <div class="performance-grid">
                        <div class="performance-card">
                            <div class="performance-icon"><i data-lucide="clock"></i></div>
                            <div class="performance-content">
                                <div class="performance-number"><?php echo round($stats_performance['tempo_medio_processamento']); ?></div>
                                <div class="performance-label">Dias Médios</div>
                                <div class="performance-detail">Para conclusão</div>
                            </div>
                        </div>
                        
                        <div class="performance-card">
                            <div class="performance-icon"><i data-lucide="zap"></i></div>
                            <div class="performance-content">
                                <div class="performance-number"><?php echo round($stats_performance['tempo_min_processamento']); ?></div>
                                <div class="performance-label">Mais Rápida</div>
                                <div class="performance-detail">Dias para conclusão</div>
                            </div>
                        </div>
                        
                        <div class="performance-card">
                            <div class="performance-icon"><i data-lucide="turtle"></i></div>
                            <div class="performance-content">
                                <div class="performance-number"><?php echo round($stats_performance['tempo_max_processamento']); ?></div>
                                <div class="performance-label">Mais Lenta</div>
                                <div class="performance-detail">Dias para conclusão</div>
                            </div>
                        </div>
                        
                        <div class="performance-card">
                            <div class="performance-icon"><i data-lucide="target"></i></div>
                            <div class="performance-content">
                                <div class="performance-number">
                                    <?php echo round(($stats_performance['processadas_30_dias'] / $stats_performance['total_processadas']) * 100); ?>%
                                </div>
                                <div class="performance-label">Em 30 Dias</div>
                                <div class="performance-detail"><?php echo $stats_performance['processadas_30_dias']; ?> de <?php echo $stats_performance['total_processadas']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Top Responsáveis -->
                <?php if (!empty($stats_responsaveis)): ?>
                <div class="stats-section">
                    <h2><i data-lucide="users"></i> Top Responsáveis (Performance)</h2>
                    <div class="table-responsive">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th>Responsável</th>
                                    <th>Qualificações</th>
                                    <th>Valor Gerenciado</th>
                                    <th>Taxa de Conclusão</th>
                                    <th>Tempo Médio</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats_responsaveis as $responsavel): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($responsavel['responsavel']); ?></strong></td>
                                    <td><?php echo number_format($responsavel['quantidade_responsavel']); ?></td>
                                    <td><span class="valor-verde"><?php echo formatarMoeda($responsavel['valor_total_responsavel']); ?></span></td>
                                    <td>
                                        <div class="progress-bar small">
                                            <div class="progress-fill" style="width: <?php echo $responsavel['taxa_conclusao_responsavel']; ?>%"></div>
                                            <span class="progress-text"><?php echo $responsavel['taxa_conclusao_responsavel']; ?>%</span>
                                        </div>
                                    </td>
                                    <td><?php echo round($responsavel['tempo_medio_responsavel'] ?? 0); ?> dias</td>
                                    <td>
                                        <?php 
                                        $performance_score = ($responsavel['taxa_conclusao_responsavel'] >= 80 && 
                                                            ($responsavel['tempo_medio_responsavel'] ?? 999) <= 45) ? 'excelente' :
                                                           (($responsavel['taxa_conclusao_responsavel'] >= 60) ? 'boa' : 'regular');
                                        ?>
                                        <span class="performance-badge <?php echo $performance_score; ?>">
                                            <?php echo ucfirst($performance_score); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Evolução Temporal -->
                <?php if (!empty($stats_temporais)): ?>
                <div class="stats-section">
                    <h2><i data-lucide="calendar"></i> Evolução Temporal (Últimos 6 Meses)</h2>
                    <div class="timeline-stats">
                        <?php foreach ($stats_temporais as $periodo): ?>
                        <div class="timeline-item">
                            <div class="timeline-header">
                                <h4><?php echo htmlspecialchars($periodo['mes_formatado']); ?></h4>
                                <span class="timeline-quantity"><?php echo $periodo['quantidade_mes']; ?> qualificações</span>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-stat">
                                    <span class="label">Valor Total:</span>
                                    <span class="valor-verde"><?php echo formatarMoeda($periodo['valor_mes']); ?></span>
                                </div>
                                <div class="timeline-stat">
                                    <span class="label">Concluídas:</span>
                                    <span><?php echo $periodo['concluidos_mes']; ?> (<?php echo round(($periodo['concluidos_mes'] / $periodo['quantidade_mes']) * 100, 1); ?>%)</span>
                                </div>
                                <div class="timeline-stat">
                                    <span class="label">Tempo Médio:</span>
                                    <span><?php echo round($periodo['tempo_medio_dias']); ?> dias</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </section>
            
        </main>
    </div>

    <!-- Modal de Criação/Edição de Qualificação (modo duplo) -->
    <div id="modalCriarQualificacao" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h3 id="tituloModalQualificacao" style="margin: 0; display: flex; align-items: center; gap: 10px;">
                    <i data-lucide="plus-circle"></i> Criar Nova Qualificação
                </h3>
                <span class="close" onclick="fecharModal('modalCriarQualificacao')">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Sistema de Abas -->
                <div class="tabs-container">
                    <div class="tabs-header">
                        <button type="button" class="tab-button active" onclick="mostrarAbaQualificacao('informacoes-gerais')">
                            <i data-lucide="info"></i> Informações Gerais
                        </button>
                        <button type="button" class="tab-button" onclick="mostrarAbaQualificacao('detalhes-objeto')">
                            <i data-lucide="file-text"></i> Detalhes do Objeto
                        </button>
                        <button type="button" class="tab-button" onclick="mostrarAbaQualificacao('vinculacao-pca')">
                            <i data-lucide="link-2"></i> Vinculação PCA
                        </button>
                        <button type="button" class="tab-button" onclick="mostrarAbaQualificacao('valores-observacoes')">
                            <i data-lucide="dollar-sign"></i> Valores e Observações
                        </button>
                    </div>

                    <form action="process.php" method="POST" id="formCriarQualificacao">
                        <input type="hidden" name="acao" id="acaoFormQualificacao" value="criar_qualificacao">
                        <input type="hidden" name="id" id="idQualificacao" value="">

                        <!-- Aba 1: Informações Gerais -->
                        <div id="aba-informacoes-gerais" class="tab-content active">
                            <h4 style="margin: 0 0 15px 0; color: #2c3e50; border-bottom: 2px solid #e9ecef; padding-bottom: 8px;">
                                <i data-lucide="info"></i> Informações Gerais
                            </h4>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>NUP (Número Único de Protocolo) *</label>
                                    <input type="text" name="nup" id="nup_criar" required placeholder="xxxxx.xxxxxx/xxxx-xx" maxlength="20">
                                </div>

                                <div class="form-group">
                                    <label>Área Demandante *</label>
                                    <input type="text" name="area_demandante" required placeholder="Nome da área solicitante">
                                </div>

                                <div class="form-group">
                                    <label>Responsável *</label>
                                    <input type="text" name="responsavel" required placeholder="Nome do responsável">
                                </div>

                                <div class="form-group">
                                    <label>Modalidade *</label>
                                    <select name="modalidade" required>
                                        <option value="">Selecione a modalidade</option>
                                        <option value="PREGÃO">PREGÃO</option>
                                        <option value="PREGÃO SRP">PREGÃO SRP</option>
                                        <option value="CONCORRÊNCIA">CONCORRÊNCIA</option>
                                        <option value="CONCURSO">CONCURSO</option>
                                        <option value="LEILÃO">LEILÃO</option>
                                        <option value="INEXIGIBILIDADE">INEXIGIBILIDADE</option>
                                        <option value="DISPENSA">DISPENSA</option>
                                        
                                        <option value="DIÁLOGO COMPETITIVO">DIÁLOGO COMPETITIVO</option>
                                        <option value="ADESÃO">ADESÃO</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Status *</label>
                                    <select name="status" required>
                                        <option value="">Selecione o status</option>
                                        <option value="EM ANÁLISE">EM ANÁLISE</option>
                                        <option value="CONCLUÍDO">CONCLUÍDO</option>
                                        <option value="ARQUIVADO">ARQUIVADO</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Aba 2: Detalhes do Objeto -->
                        <div id="aba-detalhes-objeto" class="tab-content">
                            <h4 style="margin: 0 0 15px 0; color: #2c3e50; border-bottom: 2px solid #e9ecef; padding-bottom: 8px;">
                                <i data-lucide="file-text"></i> Detalhes do Objeto
                            </h4>
                            <div class="form-grid">
                                <div class="form-group form-full">
                                    <label>Objeto *</label>
                                    <textarea name="objeto" required placeholder="Descrição detalhada do objeto da qualificação" rows="5"></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Palavras-Chave</label>
                                    <input type="text" name="palavras_chave" placeholder="Ex: equipamentos, serviços, tecnologia">
                                </div>
                            </div>
                        </div>

                        <!-- Aba 3: Vinculação PCA -->
                        <div id="aba-vinculacao-pca" class="tab-content">
                            <h4 style="margin: 0 0 15px 0; color: #2c3e50; border-bottom: 2px solid #e9ecef; padding-bottom: 8px;">
                                <i data-lucide="link-2"></i> Vinculação com PCA (Opcional)
                            </h4>
                            
                            <div class="pca-selector-section">
                                <p style="color: #6c757d; margin-bottom: 15px; font-size: 14px;">
                                    <i data-lucide="info" style="width: 16px; height: 16px; margin-right: 5px;"></i>
                                    Vincule esta qualificação a um item do Plano de Contratações Anual (PCA) para melhor rastreabilidade.
                                </p>
                                
                                <input type="hidden" name="pca_dados_id" id="pca_dados_id_criar" value="">
                                
                                <!-- Campo de busca -->
                                <div class="form-group">
                                    <label>Buscar PCA</label>
                                    <div style="position: relative;">
                                        <input type="text" 
                                               id="busca_pca_criar" 
                                               placeholder="Digite o número da contratação, DFD ou título..."
                                               style="padding-right: 40px;">
                                        <button type="button" 
                                                onclick="buscarPcaParaCriacao()" 
                                                style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #007bff; cursor: pointer;">
                                            <i data-lucide="search" style="width: 18px; height: 18px;"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Resultado da busca -->
                                <div id="resultado_busca_pca_criar" style="display: none; max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; margin-top: 10px;">
                                    <!-- Os resultados serão inseridos aqui via JavaScript -->
                                </div>
                                
                                <!-- PCA selecionado -->
                                <div id="pca_selecionado_criar" style="display: none; background: #e8f5e8; border: 1px solid #28a745; border-radius: 8px; padding: 15px; margin-top: 15px;">
                                    <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 10px;">
                                        <h5 style="margin: 0; color: #155724; display: flex; align-items: center; gap: 8px;">
                                            <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i>
                                            PCA Selecionado
                                        </h5>
                                        <button type="button" onclick="removerPcaSelecionadoCriacao()" 
                                                style="background: none; border: none; color: #155724; cursor: pointer; font-size: 18px;">
                                            <i data-lucide="x" style="width: 16px; height: 16px;"></i>
                                        </button>
                                    </div>
                                    <div id="info_pca_selecionado_criar">
                                        <!-- Informações do PCA selecionado serão inseridas aqui -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Aba 4: Valores e Observações -->
                        <div id="aba-valores-observacoes" class="tab-content">
                            <h4 style="margin: 0 0 15px 0; color: #2c3e50; border-bottom: 2px solid #e9ecef; padding-bottom: 8px;">
                                <i data-lucide="dollar-sign"></i> Valores e Observações
                            </h4>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Valor Estimado (R$) *</label>
                                    <input type="text" name="valor_estimado" class="currency" required placeholder="R$ 0,00">
                                </div>

                                <div class="form-group form-full">
                                    <label>Observações</label>
                                    <textarea name="observacoes" placeholder="Observações adicionais sobre a qualificação" rows="6"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Botões de Ação -->
                        <div style="margin-top: 20px; display: flex; gap: 15px; justify-content: flex-end; align-items: center; padding-top: 15px; border-top: 2px solid #e9ecef;">
                            <button type="button" id="btn-anterior-qualificacao" onclick="abaAnteriorQualificacao()" class="btn-secondary" style="display: none;">
                                <i data-lucide="chevron-left"></i> Anterior
                            </button>
                            <button type="button" id="btn-proximo-qualificacao" onclick="proximaAbaQualificacao()" class="btn-primary">
                                Próximo <i data-lucide="chevron-right"></i>
                            </button>
                            <button type="button" onclick="fecharModal('modalCriarQualificacao')" class="btn-secondary">
                                <i data-lucide="x"></i> Cancelar
                            </button>
                            <button type="reset" class="btn-secondary" onclick="resetarFormularioQualificacao()">
                                <i data-lucide="refresh-cw"></i> Limpar
                            </button>
                            <button type="button" class="btn-success" id="btn-criar-qualificacao" style="display: none;" onclick="submitQualificacaoForm()">
                                <i data-lucide="check"></i> <span id="textoBtn">Criar Qualificação</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Relatórios -->
    <div id="modalRelatorioQualificacao" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                    <i data-lucide="file-text"></i> <span id="tituloRelatorioQualificacao">Configurar Relatório</span>
                </h3>
                <span class="close" onclick="fecharModal('modalRelatorioQualificacao')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="formRelatorioQualificacao">
                    <input type="hidden" id="tipo_relatorio_qualificacao" name="tipo">

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="calendar" style="width: 16px; height: 16px;"></i>
                            Período
                        </label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="font-size: 12px; color: #6c757d;">Data Inicial</label>
                                <input type="date" name="data_inicial" id="qual_data_inicial" value="<?php echo date('Y-01-01'); ?>">
                            </div>
                            <div>
                                <label style="font-size: 12px; color: #6c757d;">Data Final</label>
                                <input type="date" name="data_final" id="qual_data_final" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="gavel" style="width: 16px; height: 16px;"></i>
                            Modalidade (Opcional)
                        </label>
                        <select name="modalidade" id="qual_modalidade">
                            <option value="">Todas as modalidades</option>
                            <option value="PREGÃO">PREGÃO</option>
                            <option value="PREGÃO SRP">PREGÃO SRP</option>
                            <option value="CONCORRÊNCIA">CONCORRÊNCIA</option>
                            <option value="CONCURSO">CONCURSO</option>
                            <option value="LEILÃO">LEILÃO</option>
                            <option value="INEXIGIBILIDADE">INEXIGIBILIDADE</option>
                            <option value="DISPENSA">DISPENSA</option>
                            
                            <option value="DIÁLOGO COMPETITIVO">DIÁLOGO COMPETITIVO</option>
                            <option value="ADESÃO">ADESÃO</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="building" style="width: 16px; height: 16px;"></i>
                            Área Demandante (Opcional)
                        </label>
                        <input type="text" name="area_demandante" id="qual_area_demandante" placeholder="Digite parte do nome da área">
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i>
                            Status (Opcional)
                        </label>
                        <select name="status" id="qual_status">
                            <option value="">Todos os status</option>
                            <option value="EM ANÁLISE">EM ANÁLISE</option>
                           
                            <option value="CONCLUÍDO">CONCLUÍDO</option>
                            <option value="ARQUIVADO">ARQUIVADO</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="file-type" style="width: 16px; height: 16px;"></i>
                            Formato
                        </label>
                        <select name="formato" id="qual_formato">
                            <option value="html">HTML (Visualização)</option>
                            <option value="csv">CSV (Excel)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="incluir_graficos" id="qual_incluir_graficos" checked>
                            <i data-lucide="bar-chart-3" style="width: 16px; height: 16px;"></i>
                            Incluir gráficos (apenas HTML)
                        </label>
                    </div>

                    <div class="modal-footer" style="display: flex; gap: 15px; justify-content: flex-end; padding: 20px 0 0 0; border-top: 1px solid #e5e7eb; margin-top: 25px;">
                        <button type="button" onclick="fecharModal('modalRelatorioQualificacao')" class="btn-secondary">
                            <i data-lucide="x"></i> Cancelar
                        </button>
                        <button type="submit" class="btn-primary">
                            <i data-lucide="file-text"></i> Gerar Relatório
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Seletor PCA -->
    <div class="modal" id="modalSeletorPCA">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i data-lucide="link-2"></i> Vincular ao PCA</h3>
                <button type="button" onclick="fecharModal('modalSeletorPCA')" class="modal-close">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Selecione um registro do PCA para vincular a esta qualificação:</p>
                <form id="formVincularPCA" onsubmit="processarVinculacaoPCA(event)">
                    <input type="hidden" id="qualificacao_id_vinculo" name="qualificacao_id" value="">
                    
                    <div class="form-group">
                        <label>Buscar PCA:</label>
                        <input type="text" id="busca_pca" placeholder="Digite número da contratação, DFD ou título..." onkeyup="buscarPCAs()" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Selecionar PCA:</label>
                        <div id="lista_pcas" class="pca-lista">
                            <div class="loading-pca">Carregando registros do PCA...</div>
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" onclick="fecharModal('modalSeletorPCA')" class="btn-secondary">
                            <i data-lucide="x"></i> Cancelar
                        </button>
                        <button type="submit" class="btn-primary" id="btn_confirmar_vinculo" disabled>
                            <i data-lucide="link-2"></i> Confirmar Vinculação
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Variável global para armazenar dados dos PCAs
        let pcaData = [];
        let qualificacaoSelecionada = null;
        let pcaSelecionado = null;

        // Função para abrir modal seletor de PCA
        function abrirSeletorPCA(qualificacaoId) {
            qualificacaoSelecionada = qualificacaoId;
            document.getElementById('qualificacao_id_vinculo').value = qualificacaoId;
            document.getElementById('modalSeletorPCA').style.display = 'block';
            
            // Carregar dados do PCA se não carregados ainda
            if (pcaData.length === 0) {
                carregarDadosPCA();
            } else {
                exibirPCAs(pcaData);
            }
        }

        // Função para carregar dados do PCA
        async function carregarDadosPCA(anoFiltro = null) {
            try {
                // Construir URL com filtro de ano se especificado
                let url = 'api/get_pca_data.php';
                if (anoFiltro) {
                    url += `?ano=${anoFiltro}`;
                }

                console.log('🔄 Carregando dados PCA:', url);

                const response = await fetch(url);
                if (response.ok) {
                    pcaData = await response.json();
                    console.log(`✅ Carregados ${pcaData.length} registros PCA`);

                    // Verificar se há dados de diferentes anos
                    const anosEncontrados = [...new Set(pcaData.map(pca => pca.ano))].sort();
                    if (anosEncontrados.length > 0) {
                        console.log('📅 Anos disponíveis:', anosEncontrados);
                    }

                    exibirPCAs(pcaData);
                } else {
                    document.getElementById('lista_pcas').innerHTML =
                        '<div class="erro-pca">Erro ao carregar dados do PCA</div>';
                }
            } catch (error) {
                console.error('Erro ao carregar PCA:', error);
                document.getElementById('lista_pcas').innerHTML =
                    '<div class="erro-pca">Erro de conexão ao carregar PCA</div>';
            }
        }

        // Função para buscar PCAs
        function buscarPCAs() {
            const busca = document.getElementById('busca_pca').value.trim();
            
            // Se a busca for muito curta, não filtrar
            if (busca.length < 2) {
                exibirPCAs(pcaData);
                return;
            }

            const buscaLower = busca.toLowerCase();
            const pcasFiltrados = pcaData.filter(pca => 
                (pca.numero_contratacao && pca.numero_contratacao.toLowerCase().includes(buscaLower)) ||
                (pca.numero_dfd && pca.numero_dfd.toLowerCase().includes(buscaLower)) ||
                (pca.titulo_contratacao && pca.titulo_contratacao.toLowerCase().includes(buscaLower))
            );

            exibirPCAs(pcasFiltrados);
        }

        // Função para exibir lista de PCAs
        function exibirPCAs(pcas) {
            const container = document.getElementById('lista_pcas');
            
            if (!pcas || pcas.length === 0) {
                container.innerHTML = '<div class="sem-resultados">Nenhum registro encontrado</div>';
                return;
            }

            let html = '';
            pcas.slice(0, 30).forEach(pca => { // Limitar a 30 resultados
                html += `
                    <div class="pca-item" onclick="selecionarPCA(${pca.id}, this)">
                        <div class="pca-item-header">
                            <strong>Contratação: ${pca.numero_contratacao || 'N/A'}</strong>
                            <span class="pca-valor">R$ ${formatarMoeda(pca.valor_total_contratacao)}</span>
                        </div>
                        <div class="pca-item-dfd">DFD: ${pca.numero_dfd || 'N/A'}</div>
                        <div class="pca-item-area">${pca.area_requisitante || 'Área não informada'}</div>
                        <div class="pca-item-titulo">${pca.titulo_contratacao ? pca.titulo_contratacao.substring(0, 100) + '...' : 'Título não informado'}</div>
                        <div class="pca-item-situacao">${pca.situacao_execucao || 'Situação não informada'}</div>
                    </div>
                `;
            });

            if (pcas.length > 30) {
                html += '<div class="mais-resultados">Mostrando 30 de ' + pcas.length + ' resultados. Use a busca para refinar.</div>';
            }

            container.innerHTML = html;
        }

        // Função para selecionar um PCA
        function selecionarPCA(pcaId, elemento) {
            // Remover seleção anterior
            document.querySelectorAll('.pca-item.selecionado').forEach(item => {
                item.classList.remove('selecionado');
            });

            // Selecionar novo item
            elemento.classList.add('selecionado');
            pcaSelecionado = pcaId;
            
            // Habilitar botão de confirmação
            document.getElementById('btn_confirmar_vinculo').disabled = false;
        }

        // Função para processar vinculação
        async function processarVinculacaoPCA(event) {
            event.preventDefault();
            
            if (!qualificacaoSelecionada || !pcaSelecionado) {
                alert('Erro: Dados de vinculação inválidos');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('acao', 'vincular_pca');
                formData.append('qualificacao_id', qualificacaoSelecionada);
                formData.append('pca_dados_id', pcaSelecionado);

                const response = await fetch('process.php', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    const result = await response.text();
                    
                    // Fechar modal
                    fecharModal('modalSeletorPCA');
                    
                    // Mostrar mensagem de sucesso
                    alert('Vinculação realizada com sucesso!');
                    
                    // Recarregar página para mostrar mudanças
                    location.reload();
                } else {
                    throw new Error('Erro no servidor');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao processar vinculação. Tente novamente.');
            }
        }

        // Função utilitária para formatar moeda
        function formatarMoeda(valor) {
            if (!valor) return '0,00';
            return parseFloat(valor).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // FUNÇÕES PARA RELATÓRIO UNIFICADO
        function limparFiltros() {
            document.getElementById('status_filtro').value = '';
            document.getElementById('modalidade_filtro').value = '';
            document.getElementById('area_filtro').value = '';
            document.getElementById('data_inicial').value = new Date().getFullYear() + '-01-01';
            document.getElementById('data_final').value = new Date().toISOString().split('T')[0];
            document.getElementById('busca_texto').value = '';

            // Submeter formulário com filtros limpos
            document.getElementById('form_relatorio_qualificacoes').submit();
        }

        function exportarRelatorio() {
            // Pegar valores dos filtros atuais
            const form = document.getElementById('form_relatorio_qualificacoes');
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);

            // Adicionar parâmetro de exportação
            params.append('exportar', 'csv');

            // Abrir em nova aba
            window.open('relatorios/gerar_relatorio_qualificacao.php?tipo=unificado&' + params.toString(), '_blank');
        }

        function verDetalhes(qualificacaoId) {
            // Função para ver detalhes de uma qualificação
            // Implementar modal ou redirecionamento conforme necessário
            console.log('Ver detalhes da qualificação:', qualificacaoId);
            alert('Funcionalidade de detalhes será implementada na próxima etapa.');
        }

        // Carregar áreas dinamicamente no select
        async function carregarAreas() {
            try {
                // Esta função será implementada na Etapa 2
                console.log('Carregamento de áreas será implementado na Etapa 2');
            } catch (error) {
                console.error('Erro ao carregar áreas:', error);
            }
        }

        // Inicializar funcionalidades do relatório ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            // Carregar áreas no select (será implementado na Etapa 2)
            carregarAreas();
        });
    </script>

    <!-- Estilos adicionais para o modal PCA -->
    <style>
        .pca-lista {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }

        .pca-item {
            padding: 16px;
            border-bottom: 1px solid #f3f4f6;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .pca-item:last-child {
            border-bottom: none;
        }

        .pca-item:hover {
            background: #f8fafc;
        }

        .pca-item.selecionado {
            background: #dbeafe !important;
            border-left: 4px solid #3b82f6;
        }

        .pca-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .pca-valor {
            color: #059669;
            font-weight: 700;
            font-size: 14px;
        }

        .pca-item-dfd {
            color: #3b82f6;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .pca-item-area {
            color: #6b7280;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .pca-item-titulo {
            color: #374151;
            font-size: 13px;
            margin-bottom: 4px;
        }

        .pca-item-situacao {
            color: #059669;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .loading-pca, .erro-pca, .sem-resultados, .mais-resultados {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-style: italic;
        }

        .erro-pca {
            color: #dc2626;
        }

        .mais-resultados {
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
        }

        /* ESTILOS PARA RELATÓRIO UNIFICADO */
        .relatorio-unificado {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        .filtros-container {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            border: 1px solid #e5e7eb;
        }

        .filtros-container h3 {
            margin: 0 0 20px 0;
            color: #2d3748;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filtros-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }

        .filtros-row.filtros-acoes {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-top: 20px;
            border-top: 1px solid #e5e7eb;
            padding-top: 16px;
        }

        .filtro-item {
            display: flex;
            flex-direction: column;
        }

        .filtro-item label {
            font-size: 12px;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 4px;
        }

        .filtro-item select,
        .filtro-item input {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            transition: all 0.2s ease;
        }

        .filtro-item select:focus,
        .filtro-item input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .stats-resumo {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border: 2px solid;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .stat-item.stat-total {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border-color: #3b82f6;
            color: #1e40af;
        }

        .stat-item.stat-valor {
            background: linear-gradient(135deg, #fef3c7, #fed7aa);
            border-color: #f59e0b;
            color: #92400e;
        }

        .stat-item.stat-medio {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border-color: #10b981;
            color: #065f46;
        }

        .stat-numero {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 6px;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .stat-label {
            font-size: 13px;
            font-weight: 600;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .resultados-container {
            margin-top: 24px;
        }

        .tabela-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e5e7eb;
        }

        .tabela-header h3 {
            margin: 0;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .paginacao-info {
            color: #6b7280;
            font-size: 14px;
        }

        .tabela-wrapper {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .tabela-qualificacoes {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .tabela-qualificacoes th {
            background: #f8fafc;
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
            font-size: 14px;
        }

        .tabela-qualificacoes td {
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
            vertical-align: top;
        }

        .tabela-qualificacoes tr:hover {
            background: #f9fafb;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .badge-modalidade {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status {
            display: inline-block;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .status-em_analise {
            background: #fef3c7;
            color: #92400e;
        }

        .status-concluido {
            background: #d1fae5;
            color: #065f46;
        }

        .status-arquivado {
            background: #f3f4f6;
            color: #374151;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .btn-icon {
            background: none;
            border: 1px solid #e5e7eb;
            padding: 6px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-icon:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }

        .sem-resultados {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }

        .sem-resultados i {
            width: 48px;
            height: 48px;
            margin-bottom: 16px;
            color: #d1d5db;
        }

        .sem-resultados p {
            margin: 8px 0 16px 0;
            font-size: 16px;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-success {
            background: #10b981;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-success:hover {
            background: #047857;
        }
    </style>

    <script src="assets/qualificacao-dashboard.js"></script>
    <script src="assets/mobile-improvements.js"></script>
    <script src="assets/ux-improvements.js"></script>
    <script src="assets/notifications.js"></script>
    
</body>
</html>