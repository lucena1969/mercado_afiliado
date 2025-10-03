<?php
// Incluir configurações
$root_path = dirname(dirname(__DIR__));
require_once $root_path . '/config/app.php';

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
    <title>📈 Painel Unificado - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        /* Layout principal otimizado */
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
            background: #f8fafc;
        }
        
        .sidebar {
            width: 240px;
            background: white;
            border-right: 1px solid #e2e8f0;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin: 0;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
            border-right: 3px solid transparent;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: #f1f5f9;
            color: #3b82f6;
            border-right-color: #3b82f6;
        }
        
        .main-content {
            flex: 1;
            margin-left: 240px;
            padding: 20px;
            max-width: calc(100vw - 240px);
        }
        
        /* Header compacto */
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: white;
            padding: 16px 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .panel-header h1 {
            margin: 0;
            font-size: 1.5rem;
            color: #1e293b;
        }
        
        .panel-header p {
            margin: 4px 0 0 0;
            color: #64748b;
            font-size: 14px;
        }
        
        .period-selector {
            display: flex;
            gap: 4px;
        }
        
        .period-btn {
            padding: 6px 12px;
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 6px;
            text-decoration: none;
            color: #64748b;
            font-size: 13px;
            transition: all 0.2s;
        }
        
        .period-btn.active, .period-btn:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        /* KPIs em grid mais compacto */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .kpi-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid #3b82f6;
            min-height: 120px;
        }
        
        .kpi-card.revenue { border-left-color: #10b981; }
        .kpi-card.commission { border-left-color: #f59e0b; }
        .kpi-card.conversions { border-left-color: #8b5cf6; }
        .kpi-card.rate { border-left-color: #ef4444; }
        
        .kpi-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
            line-height: 1;
        }
        
        .kpi-label {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 8px;
        }
        
        .kpi-growth {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
        }
        
        .growth-positive { color: #059669; }
        .growth-negative { color: #dc2626; }
        .growth-neutral { color: #6b7280; }
        
        /* Layout dos gráficos lado a lado */
        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .chart-card {
            background: white;
            padding: 16px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            height: 320px;
        }
        
        .chart-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 12px;
        }
        
        .chart-container {
            height: 260px;
            position: relative;
        }
        
        /* Tabelas lado a lado mais compactas */
        .data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .data-card {
            background: white;
            padding: 16px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            max-height: 400px;
            overflow-y: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .data-table th {
            text-align: left;
            padding: 8px 6px;
            border-bottom: 2px solid #e2e8f0;
            font-weight: 600;
            color: #475569;
            font-size: 12px;
            background: #f8fafc;
            position: sticky;
            top: 0;
        }
        
        .data-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .platform-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .badge-hotmart { background: #fee2e2; color: #dc2626; }
        .badge-monetizze { background: #dbeafe; color: #2563eb; }
        .badge-eduzz { background: #dcfce7; color: #16a34a; }
        .badge-braip { background: #fef3c7; color: #d97706; }
        
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
        @media (max-width: 1200px) {
            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .charts-row {
                grid-template-columns: 1fr;
            }
            
            .data-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="<?= BASE_URL ?>/dashboard"><i data-lucide="bar-chart-3" style="width: 16px; height: 16px; margin-right: 6px;"></i>Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>/unified-panel" class="active"><i data-lucide="trending-up" style="width: 16px; height: 16px; margin-right: 6px;"></i>Painel Unificado</a></li>
                <li><a href="<?= BASE_URL ?>/integrations"><i data-lucide="link" style="width: 16px; height: 16px; margin-right: 6px;"></i>IntegraSync</a></li>
                <li><a href="<?= BASE_URL ?>/link-maestro"><i data-lucide="target" style="width: 16px; height: 16px; margin-right: 6px;"></i>Link Maestro</a></li>
                <li><a href="<?= BASE_URL ?>/pixel"><i data-lucide="eye" style="width: 16px; height: 16px; margin-right: 6px;"></i>Pixel BR</a></li>
                <li><a href="#" onclick="showComingSoon('Alerta Queda')"><i data-lucide="alert-triangle" style="width: 16px; height: 16px; margin-right: 6px;"></i>Alerta Queda</a></li>
                <li><a href="#" onclick="showComingSoon('CAPI Bridge')"><i data-lucide="bridge" style="width: 16px; height: 16px; margin-right: 6px;"></i>CAPI Bridge</a></li>
                <li><a href="<?= BASE_URL ?>/logout">Sair</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header compacto -->
            <div class="panel-header">
                <div>
                    <h1>📈 Painel Unificado</h1>
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
                        📊 <?= number_format($kpis['total_conversions'] ?? 0) ?> total
                    </div>
                </div>
            </div>

            <!-- Gráficos lado a lado -->
            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-title">📈 Evolução da Receita</div>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-title">🏢 Receita por Rede</div>
                    <div class="chart-container">
                        <canvas id="networkChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tabelas compactas -->
            <div class="data-grid">
                <div class="data-card">
                    <div class="chart-title">🎯 Performance por Rede</div>
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
                        <div class="empty-icon">📊</div>
                        <p>Nenhuma integração ativa</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="data-card">
                    <div class="chart-title">🏆 Top Produtos</div>
                    <?php if (!empty($top_products)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Conv.</th>
                                <th>Receita</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_products as $product): ?>
                            <tr>
                                <td>
                                    <div style="font-size: 12px;"><?= htmlspecialchars(substr($product['product_name'], 0, 25)) ?>...</div>
                                    <span class="platform-badge badge-<?= $product['platform'] ?>">
                                        <?= ucfirst($product['platform']) ?>
                                    </span>
                                </td>
                                <td><?= $product['approved_conversions'] ?></td>
                                <td>R$ <?= number_format($product['revenue'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">🏆</div>
                        <p>Nenhum produto vendido</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Dados dos gráficos
        const revenueData = <?= $revenue_chart_data ?>;
        const networkData = <?= $network_chart_data ?>;

        // Gráfico de evolução da receita
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: revenueData.map(d => new Date(d.date).toLocaleDateString('pt-BR')),
                datasets: [{
                    label: 'Receita Diária',
                    data: revenueData.map(d => d.daily_revenue),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de receita por rede
        const networkCtx = document.getElementById('networkChart').getContext('2d');
        new Chart(networkCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(networkData),
                datasets: [{
                    data: Object.values(networkData),
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Função para "Coming Soon"
        function showComingSoon(feature) {
            alert(`${feature} - Em breve! 🚀`);
        }

        // Inicializar ícones Lucide
        lucide.createIcons();
    </script>
</body>
</html>