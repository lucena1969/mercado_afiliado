<?php
// Incluir configurações
$root_path = dirname(dirname(__DIR__));
// Config já incluído pelo router

// Verificar autenticação mais simples
if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    header('Location: /login');
    exit;
}

// Incluir modelos necessários
require_once $root_path . '/app/models/UnifiedPanel.php';
require_once $root_path . '/config/database.php';

try {
    // Conectar ao banco
    $database = new Database();
    $db = $database->getConnection();

    // Instanciar o modelo
    $unified_panel = new UnifiedPanel($db);

    // Período padrão (últimos 30 dias)
    $period_days = isset($_GET['period']) ? (int)$_GET['period'] : 30;
    $user_id = $_SESSION['user']['id'];

    // Buscar dados
    $kpis = $unified_panel->getDashboardKPIs($user_id, $period_days);
    $network_comparison = $unified_panel->getNetworkComparison($user_id, $period_days);
    $revenue_evolution = $unified_panel->getRevenueEvolution($user_id, $period_days);
    $top_products = $unified_panel->getTopProducts($user_id, $period_days, 5);
    $utm_analysis = $unified_panel->getUTMAnalysis($user_id, $period_days);
    $integration_status = $unified_panel->getIntegrationStatus($user_id);
    $sales_status = $unified_panel->getSalesStatusBreakdown($user_id, $period_days);
    $growth_metrics = $unified_panel->getGrowthMetrics($user_id, $period_days);

    // Preparar dados para gráficos (JSON)
    $revenue_chart_data = json_encode($revenue_evolution);
    $network_chart_data = json_encode(array_column($network_comparison, 'revenue', 'platform'));
    $status_chart_data = json_encode(array_column($sales_status, 'count', 'status'));

} catch (Exception $e) {
    error_log("Erro no Painel Unificado: " . $e->getMessage());
    
    // Valores padrão em caso de erro
    $kpis = ['total_revenue' => 0, 'total_commission' => 0, 'approved_conversions' => 0, 'conversion_rate' => 0];
    $network_comparison = [];
    $revenue_evolution = [];
    $top_products = [];
    $integration_status = [];
    $sales_status = [];
    $growth_metrics = [];
    $revenue_chart_data = '[]';
    $network_chart_data = '{}';
    $status_chart_data = '{}';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Unificado - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        /* RESET COMPLETO ESTILO PIXELX */
        * {
            margin: 0 !important;
            padding: 0 !important;
            box-sizing: border-box !important;
        }
        
        /* VARIÁVEIS MODERNAS PIXELX STYLE */
        :root {
            --bg-primary: #fafbfc !important;
            --bg-secondary: #ffffff !important;
            --bg-tertiary: #f8fafc !important;
            --text-primary: #1a202c !important;
            --text-secondary: #4a5568 !important;
            --text-muted: #718096 !important;
            --border-primary: #e2e8f0 !important;
            --border-secondary: #edf2f7 !important;
            --accent: #667eea !important;
            --accent-hover: #5a67d8 !important;
            --accent-light: rgba(102, 126, 234, 0.1) !important;
            --success: #48bb78 !important;
            --warning: #ed8936 !important;
            --shadow-xs: 0 0 0 1px rgba(0, 0, 0, 0.05) !important;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            --radius-sm: 6px !important;
            --radius-md: 8px !important;
            --radius-lg: 12px !important;
            --radius-xl: 16px !important;
        }
        
        /* BODY MODERNO */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            background: var(--bg-primary) !important;
            color: var(--text-primary) !important;
            font-weight: 400 !important;
            line-height: 1.6 !important;
            -webkit-font-smoothing: antialiased !important;
            -moz-osx-font-smoothing: grayscale !important;
        }
        
        /* HEADER STYLE PIXELX */
        .header {
            background: var(--bg-secondary) !important;
            border-bottom: 1px solid var(--border-primary) !important;
            box-shadow: var(--shadow-sm) !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 100 !important;
            backdrop-filter: blur(20px) !important;
        }
        
        /* CONTAINER */
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 0 24px;
        }
        
        /* NAVEGAÇÃO CLEAN */
        .nav {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            height: 72px !important;
        }
        
        .nav-brand {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            font-weight: 700 !important;
            font-size: 18px !important;
            color: var(--text-primary) !important;
            text-decoration: none !important;
        }
        
        .nav-links {
            display: flex !important;
            align-items: center !important;
            gap: 24px !important;
            list-style: none !important;
        }
        
        .nav-links span {
            color: var(--text-secondary) !important;
            font-weight: 500 !important;
            font-size: 14px !important;
        }
        
        .nav-links a {
            color: var(--text-secondary) !important;
            text-decoration: none !important;
            font-weight: 500 !important;
            font-size: 14px !important;
            padding: 8px 16px !important;
            border-radius: var(--radius-md) !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        
        .nav-links a:hover {
            background: var(--bg-tertiary) !important;
            color: var(--accent) !important;
        }
        
        /* SIDEBAR */
        .sidebar {
            flex-shrink: 0;
        }
        
        .sidebar-menu {
            list-style: none !important;
        }
        
        .sidebar-menu li {
            margin: 0 16px 4px 16px !important;
        }
        
        .sidebar-menu a {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            padding: 12px 16px !important;
            color: var(--text-secondary) !important;
            text-decoration: none !important;
            font-weight: 500 !important;
            font-size: 14px !important;
            border-radius: var(--radius-lg) !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            position: relative !important;
        }
        
        .sidebar-menu a i {
            width: 18px !important;
            height: 18px !important;
            stroke-width: 2 !important;
            flex-shrink: 0 !important;
        }
        
        .sidebar-menu a:before {
            content: '' !important;
            position: absolute !important;
            left: 0 !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            width: 3px !important;
            height: 20px !important;
            background: var(--accent) !important;
            border-radius: 0 4px 4px 0 !important;
            opacity: 0 !important;
            transition: opacity 0.2s ease !important;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: var(--accent-light) !important;
            color: var(--accent) !important;
            font-weight: 600 !important;
        }
        
        .sidebar-menu a.active:before {
            opacity: 1 !important;
        }
        
        /* MAIN CONTENT AREA */
        .main-content {
            background: transparent !important;
        }
        
        /* CARDS MODERNOS PIXELX */
        .card {
            background: var(--bg-secondary) !important;
            border: 1px solid var(--border-secondary) !important;
            border-radius: var(--radius-xl) !important;
            box-shadow: var(--shadow-sm) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            overflow: hidden !important;
        }
        
        .card:hover {
            box-shadow: var(--shadow-lg) !important;
            transform: translateY(-4px) !important;
            border-color: var(--accent) !important;
        }
        
        .card-header {
            padding: 24px 32px 20px !important;
            border-bottom: 1px solid var(--border-secondary) !important;
            background: var(--bg-tertiary) !important;
        }
        
        .card-body {
            padding: 24px 32px !important;
        }
        
        /* GRID DE STATS */
        .stats-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)) !important;
            gap: 24px !important;
            margin-bottom: 32px !important;
        }
        
        /* BOTÕES MODERNOS */
        .btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
            padding: 12px 24px !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            border-radius: var(--radius-lg) !important;
            border: none !important;
            cursor: pointer !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            text-decoration: none !important;
        }
        
        .btn-primary {
            background: var(--accent) !important;
            color: white !important;
            box-shadow: var(--shadow-sm) !important;
        }
        
        .btn-primary:hover {
            background: var(--accent-hover) !important;
            box-shadow: var(--shadow-md) !important;
            transform: translateY(-2px) !important;
        }
        
        /* TIPOGRAFIA MODERNA */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Inter', sans-serif !important;
            font-weight: 700 !important;
            line-height: 1.2 !important;
            letter-spacing: -0.025em !important;
            color: var(--text-primary) !important;
        }
        
        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr !important;
                gap: 24px !important;
            }
            
            .sidebar {
                order: 2 !important;
            }
        }
        
        
        /* Header mais espaçoso */
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            background: white;
            padding: 32px 36px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .panel-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 800;
            color: #1e293b;
            letter-spacing: -0.02em;
        }

        .panel-header p {
            margin: 8px 0 0 0;
            color: #64748b;
            font-size: 15px;
            font-weight: 500;
        }
        
        .period-selector {
            display: flex;
            gap: 8px;
            background: #f8fafc;
            padding: 6px;
            border-radius: 10px;
        }

        .period-btn {
            padding: 10px 18px;
            border: 2px solid transparent;
            background: transparent;
            border-radius: 8px;
            text-decoration: none;
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .period-btn.active, .period-btn:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
        }
        
        /* KPIs Redesenhados - Estilo Moderno */
        .kpi-grid {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 20px !important;
            margin-bottom: 32px !important;
        }

        .kpi-card {
            background: white !important;
            padding: 24px !important;
            border-radius: 12px !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08) !important;
            position: relative !important;
            overflow: hidden !important;
            transition: all 0.3s ease !important;
            min-height: 130px !important;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--card-color) 0%, var(--card-color-light) 100%);
        }

        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.12);
            border-color: var(--card-color);
        }

        .kpi-card.revenue {
            --card-color: #10b981;
            --card-color-light: #34d399;
        }
        .kpi-card.commission {
            --card-color: #f59e0b;
            --card-color-light: #fbbf24;
        }
        .kpi-card.conversions {
            --card-color: #8b5cf6;
            --card-color-light: #a78bfa;
        }
        .kpi-card.rate {
            --card-color: #3b82f6;
            --card-color-light: #60a5fa;
        }
        
        .kpi-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 500;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kpi-value {
            font-size: 2rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 12px;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .kpi-growth {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .growth-positive {
            background: #dcfce7;
            color: #16a34a;
        }

        .growth-negative {
            background: #fee2e2;
            color: #dc2626;
        }

        .growth-neutral {
            background: #f1f5f9;
            color: #64748b;
        }
        
        /* Gráficos Redesenhados - Layout Full Width */
        .charts-row {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 20px !important;
            margin-bottom: 32px !important;
        }

        .chart-card {
            background: white !important;
            padding: 32px !important;
            border-radius: 12px !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08) !important;
            transition: all 0.3s ease !important;
        }

        .chart-card:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 16px rgba(0,0,0,0.12) !important;
        }

        .chart-title {
            font-size: 15px !important;
            font-weight: 700 !important;
            color: #0f172a !important;
            margin-bottom: 20px !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }

        .chart-title i {
            width: 18px !important;
            height: 18px !important;
            color: #3b82f6 !important;
        }

        .chart-container {
            height: 400px !important;
            position: relative !important;
        }
        
        /* Tabelas Redesenhadas - Full Width */
        .data-grid {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 20px !important;
            margin-bottom: 32px !important;
        }

        .data-card {
            background: white !important;
            padding: 32px !important;
            border-radius: 12px !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08) !important;
            min-height: 400px !important;
            max-height: 500px !important;
            overflow-y: auto !important;
            transition: all 0.3s ease !important;
        }

        .data-card:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 16px rgba(0,0,0,0.12) !important;
        }
        
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 14px;
        }

        .data-table th {
            text-align: left;
            padding: 12px 16px;
            background: #f8fafc;
            font-weight: 600;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 2px solid #e5e7eb;
            white-space: nowrap;
        }

        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            font-weight: 500;
        }

        .data-table tbody tr {
            transition: all 0.2s ease;
        }

        .data-table tbody tr:hover {
            background: #f8fafc;
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .platform-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-hotmart {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #dc2626;
            border: 1px solid #fca5a5;
        }

        .badge-monetizze {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #2563eb;
            border: 1px solid #93c5fd;
        }

        .badge-eduzz {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #16a34a;
            border: 1px solid #86efac;
        }

        .badge-braip {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #d97706;
            border: 1px solid #fcd34d;
        }
        
        .empty-state {
            text-align: center;
            color: #64748b;
            padding: 30px 20px;
        }
        
        .empty-icon {
            font-size: 36px;
            margin-bottom: 12px;
            opacity: 0.5;
        }
        
        /* Responsivo */
        @media (max-width: 1400px) {
            .kpi-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }

        @media (max-width: 768px) {
            .kpi-grid, .charts-row, .data-grid {
                grid-template-columns: 1fr !important;
            }

            .panel-header {
                flex-direction: column !important;
                align-items: flex-start !important;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="nav">
                <a href="<?= BASE_URL ?>/dashboard" class="nav-brand">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--accent), var(--accent-hover)); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-md);">
                        <svg width="20" height="20" fill="white" viewBox="0 0 24 24">
                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                        </svg>
                    </div>
                    Mercado Afiliado
                </a>
                <ul class="nav-links">
                    <li>
                        <span>
                            Olá, <?= htmlspecialchars(explode(' ', $_SESSION['user']['name'])[0]) ?>
                        </span>
                    </li>
                    <li><a href="<?= BASE_URL ?>/logout">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div style="display: flex; min-height: 100vh; background: #f8fafc;">
        <!-- Sidebar -->
        <aside class="sidebar" style="width: 280px; background: white; border-right: 1px solid #e5e7eb; position: sticky; top: 0; height: 100vh; overflow-y: auto;">
            <ul class="sidebar-menu">
                    <li><a href="<?= BASE_URL ?>/dashboard">
                        <i data-lucide="bar-chart-3"></i> Dashboard</a></li>
                    <li><a href="<?= BASE_URL ?>/unified-panel" class="active">
                        <i data-lucide="layout-dashboard"></i> Painel Unificado</a></li>
                    <li><a href="<?= BASE_URL ?>/integrations">
                        <i data-lucide="plug"></i> IntegraSync</a></li>
                    <li><a href="#" onclick="showComingSoon('Link Maestro')">
                        <i data-lucide="link"></i> Link Maestro</a></li>
                    <li><a href="<?= BASE_URL ?>/pixel">
                        <i data-lucide="target"></i> Pixel BR</a></li>
                    <li><a href="#" onclick="showComingSoon('Alerta Queda')">
                        <i data-lucide="alert-triangle"></i> Alerta Queda</a></li>
                    <li><a href="#" onclick="showComingSoon('CAPI Bridge')">
                        <i data-lucide="bridge"></i> CAPI Bridge</a></li>
                    <li><a href="#" onclick="showComingSoon('Cohort Reembolso')">
                        <i data-lucide="trending-up"></i> Cohort Reembolso</a></li>
                    <li><a href="#" onclick="showComingSoon('Offer Radar')">
                        <i data-lucide="radar"></i> Offer Radar</a></li>
                    <li><a href="#" onclick="showComingSoon('UTM Templates')">
                        <i data-lucide="tag"></i> UTM Templates</a></li>
                    <li><a href="#" onclick="showComingSoon('Equipe')">
                        <i data-lucide="users"></i> Equipe & Permissões</a></li>
                    <li><a href="#" onclick="showComingSoon('Exportar')">
                        <i data-lucide="download"></i> Exporta+</a></li>
                    <li><a href="#" onclick="showComingSoon('Trilhas')">
                        <i data-lucide="graduation-cap"></i> Trilhas Rápidas</a></li>
                    <li><a href="#" onclick="showComingSoon('LGPD')">
                        <i data-lucide="shield-check"></i> Auditoria LGPD</a></li>
                </ul>
            </aside>

        <!-- Conteúdo principal -->
        <main style="flex: 1; padding: 2rem; overflow-x: hidden;">
            <!-- Header -->
            <div class="panel-header">
                <div>
                    <h1>Painel Unificado</h1>
                    <p>Visão consolidada de todas as suas redes de afiliação</p>
                </div>
                
                <div class="period-selector">
                    <a href="?period=7" class="period-btn <?= $period_days == 7 ? 'active' : '' ?>">7d</a>
                    <a href="?period=30" class="period-btn <?= $period_days == 30 ? 'active' : '' ?>">30d</a>
                    <a href="?period=90" class="period-btn <?= $period_days == 90 ? 'active' : '' ?>">90d</a>
                    <a href="?period=365" class="period-btn <?= $period_days == 365 ? 'active' : '' ?>">1a</a>
                </div>
            </div>

            <!-- KPIs em linha -->
            <div class="kpi-grid">
                <div class="kpi-card revenue">
                    <div class="kpi-value">R$ <?= number_format($kpis['total_revenue'] ?? 0, 0, ',', '.') ?></div>
                    <div class="kpi-label">Receita Total</div>
                    <div class="kpi-growth growth-<?= ($growth_metrics['total_revenue_growth'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                        <span><?= ($growth_metrics['total_revenue_growth'] ?? 0) >= 0 ? '↗' : '↘' ?></span>
                        <?= abs($growth_metrics['total_revenue_growth'] ?? 0) ?>%
                    </div>
                </div>
                
                <div class="kpi-card commission">
                    <div class="kpi-value">R$ <?= number_format($kpis['total_commission'] ?? 0, 0, ',', '.') ?></div>
                    <div class="kpi-label">Comissões</div>
                    <div class="kpi-growth growth-<?= ($growth_metrics['total_commission_growth'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                        <span><?= ($growth_metrics['total_commission_growth'] ?? 0) >= 0 ? '↗' : '↘' ?></span>
                        <?= abs($growth_metrics['total_commission_growth'] ?? 0) ?>%
                    </div>
                </div>
                
                <div class="kpi-card conversions">
                    <div class="kpi-value"><?= number_format($kpis['approved_conversions'] ?? 0) ?></div>
                    <div class="kpi-label">Conversões</div>
                    <div class="kpi-growth growth-<?= ($growth_metrics['approved_conversions_growth'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                        <span><?= ($growth_metrics['approved_conversions_growth'] ?? 0) >= 0 ? '↗' : '↘' ?></span>
                        <?= abs($growth_metrics['approved_conversions_growth'] ?? 0) ?>%
                    </div>
                </div>
                
                <div class="kpi-card rate">
                    <div class="kpi-value"><?= number_format($kpis['conversion_rate'] ?? 0, 1) ?>%</div>
                    <div class="kpi-label">Taxa de Conversão</div>
                    <div class="kpi-growth growth-neutral">
                        <i data-lucide="bar-chart-2" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle;"></i> <?= number_format($kpis['total_conversions'] ?? 0) ?> total
                    </div>
                </div>
            </div>

            <!-- Gráficos lado a lado -->
            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-title"><i data-lucide="trending-up"></i> Evolução da Receita</div>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-title"><i data-lucide="building"></i> Receita por Rede</div>
                    <div class="chart-container">
                        <canvas id="networkChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tabelas compactas -->
            <div class="data-grid">
                <div class="data-card">
                    <div class="chart-title">Performance por Rede</div>
                    <?php if (!empty($network_comparison)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Rede</th>
                                <th>Conv.</th>
                                <th>Receita</th>
                                <th>Taxa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($network_comparison as $network): ?>
                            <tr>
                                <td>
                                    <span class="platform-badge badge-<?= $network['platform'] ?>">
                                        <?= ucfirst($network['platform']) ?>
                                    </span>
                                </td>
                                <td><?= $network['approved_conversions'] ?></td>
                                <td>R$ <?= number_format($network['revenue'], 0, ',', '.') ?></td>
                                <td><?= number_format($network['conversion_rate'], 1) ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i data-lucide="bar-chart-3" style="width: 48px; height: 48px; color: var(--text-muted);"></i>
                        </div>
                        <p>Nenhuma integração ativa</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="data-card">
                    <div class="chart-title">Top Produtos</div>
                    <?php if (!empty($top_products)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Vendas</th>
                                <th>Receita</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_products as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= $product['conversions'] ?></td>
                                <td>R$ <?= number_format($product['revenue'], 2, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i data-lucide="package" style="width: 48px; height: 48px; color: var(--text-muted);"></i>
                        </div>
                        <p>Nenhum produto encontrado</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Inicializar Lucide Icons
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
        
        function showComingSoon(feature) {
            alert('🚧 ' + feature + ' estará disponível em breve!\n\nEstamos trabalhando duro para entregar essa funcionalidade o mais rápido possível.');
        }
    </script>
</body>
</html>
       