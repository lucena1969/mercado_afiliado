<?php
require_once 'config.php';
require_once 'functions.php';

verificarLogin();

// VERIFICAR SE É COORDENADOR (NÍVEL 1) - ACESSO RESTRITO
if ($_SESSION['usuario_nivel'] != 1) {
    header('Location: selecao_modulos.php?erro=acesso_negado');
    exit;
}

$pdo = conectarDB();

// Determinar o módulo ativo (padrão: todos os módulos)
$modulo_ativo = $_GET['modulo'] ?? 'geral';
$modulos_disponiveis = [
    'geral' => 'Visão Geral do Sistema',
    'planejamento' => 'Planejamento (PCA)',
    'qualificacao' => 'Qualificação de Documentação',
    'licitacao' => 'Licitações e Pregões',
    'contratos' => 'Contratos Administrativos'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios Gerenciais Executivos - Sistema CGLIC</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    
    <style>
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            background: #f8fafc;
        }
        
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            flex-shrink: 0;
            padding: 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-item i {
            margin-right: 12px;
            width: 20px;
            height: 20px;
        }
        
        .main-content {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
        }
        
        .header {
            background: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            margin: 0 0 8px 0;
            color: #2d3748;
            font-size: 2rem;
        }
        
        .header p {
            margin: 0;
            color: #64748b;
        }
        
        /* Sistema de Abas */
        .tabs-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .tabs-header {
            display: flex;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        
        .tab-button {
            flex: 1;
            padding: 16px 20px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .tab-button:hover {
            background: #e2e8f0;
            color: #2d3748;
        }
        
        .tab-button.active {
            background: white;
            color: #3b82f6;
            border-bottom-color: #3b82f6;
        }
        
        .tab-content {
            display: none;
            padding: 24px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Estilos de Formulários */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group select,
        .form-group input {
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        /* Tabela de Resultados */
        .results-container {
            margin-top: 24px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .results-header {
            background: #f8fafc;
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .results-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
        }
        
        .export-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-export {
            padding: 8px 16px;
            font-size: 12px;
            border: 1px solid #d1d5db;
            background: white;
            color: #4b5563;
        }
        
        .btn-export:hover {
            background: #f3f4f6;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .results-table th,
        .results-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .results-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .results-table tbody tr:hover {
            background: #f9fafb;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-analise {
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

        /* ESTILOS ESPECÍFICOS DO PCA */
        .status-atraso {
            background: #fee2e2;
            color: #dc2626;
        }

        .status-preparacao {
            background: #fef3c7;
            color: #d97706;
        }

        .status-execucao {
            background: #dbeafe;
            color: #2563eb;
        }

        .status-encerrada {
            background: #d1fae5;
            color: #065f46;
        }

        .tem-licitacao {
            background: #e0f2fe;
            color: #0369a1;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .nao-tem-licitacao {
            background: #fef3c7;
            color: #92400e;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .valor {
            font-weight: 600;
            color: #059669;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }

        /* MELHORIAS VISUAIS ESPECÍFICAS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 32px 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            min-height: 280px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .chart-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            border-color: #3b82f6;
        }

        .chart-title {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 12px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chart-title i {
            width: 24px;
            height: 24px;
            color: #3b82f6;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(59, 130, 246, 0.4);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        /* CSS Responsivo para Data Cards PCA */
        .pca-cards-grid {
            display: grid !important;
            grid-template-columns: repeat(6, 1fr) !important;
            gap: 15px !important;
            margin: 20px 0 !important;
        }

        /* Tablets */
        @media (max-width: 1200px) {
            .pca-cards-grid {
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 12px !important;
            }
        }

        /* Tablets pequenos */
        @media (max-width: 768px) {
            .pca-cards-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 10px !important;
            }

            .pca-cards-grid > div {
                padding: 12px !important;
            }

            .pca-cards-grid h4 {
                font-size: 12px !important;
            }

            .pca-cards-grid > div > div {
                font-size: 24px !important;
            }
        }

        /* Smartphones */
        @media (max-width: 480px) {
            .pca-cards-grid {
                grid-template-columns: 1fr !important;
                gap: 8px !important;
                margin: 15px 0 !important;
            }

            .pca-cards-grid > div {
                padding: 10px !important;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h1>Relatórios Executivos</h1>
                <p>Sistema CGLIC - Visão Gerencial</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="selecao_modulos.php" class="nav-item">
                    <i data-lucide="arrow-left"></i>
                    <span>Voltar ao Menu</span>
                </a>

                <div style="padding: 10px 20px; color: rgba(255,255,255,0.6); font-size: 12px; text-transform: uppercase; font-weight: 600;">Módulos do Sistema</div>

                <a href="?modulo=geral" class="nav-item <?php echo $modulo_ativo == 'geral' ? 'active' : ''; ?>">
                    <i data-lucide="bar-chart-4"></i>
                    <span>Visão Geral</span>
                </a>

                <a href="?modulo=planejamento" class="nav-item <?php echo $modulo_ativo == 'planejamento' ? 'active' : ''; ?>">
                    <i data-lucide="calendar-check"></i>
                    <span>Planejamento</span>
                </a>

                <a href="?modulo=qualificacao" class="nav-item <?php echo $modulo_ativo == 'qualificacao' ? 'active' : ''; ?>">
                    <i data-lucide="award"></i>
                    <span>Qualificação</span>
                </a>

                <a href="?modulo=licitacao" class="nav-item <?php echo $modulo_ativo == 'licitacao' ? 'active' : ''; ?>">
                    <i data-lucide="gavel"></i>
                    <span>Licitações</span>
                </a>

                <a href="?modulo=contratos" class="nav-item <?php echo $modulo_ativo == 'contratos' ? 'active' : ''; ?>">
                    <i data-lucide="file-text"></i>
                    <span>Contratos</span>
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <h1><?php echo $modulos_disponiveis[$modulo_ativo] ?? 'Módulo Desconhecido'; ?></h1>
                        <p><?php
                            switch($modulo_ativo) {
                                case 'geral':
                                    echo 'Visão consolidada de todos os módulos do sistema CGLIC';
                                    break;
                                case 'planejamento':
                                    echo 'Relatórios executivos do Plano de Contratações Anual (PCA)';
                                    break;
                                case 'qualificacao':
                                    echo 'Análise gerencial do processo de qualificação de documentação e artefatos licitatórios';
                                    break;
                                case 'licitacao':
                                    echo 'Relatórios gerenciais de licitações e pregões';
                                    break;
                                case 'contratos':
                                    echo 'Análise executiva de contratos administrativos';
                                    break;
                                default:
                                    echo 'Selecione um módulo na sidebar para visualizar os relatórios';
                            }
                        ?></p>
                    </div>

                    <?php if ($modulo_ativo == 'planejamento'): ?>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <label style="font-weight: 600; color: #2c3e50; white-space: nowrap;">
                            <i data-lucide="calendar-days" style="width: 18px; height: 18px; margin-right: 6px;"></i>
                            Ano do PCA:
                        </label>
                        <select id="ano_pca_header" style="padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-weight: 500; min-width: 140px;" onchange="atualizarDashboardPorAno()">
                            <option value="2026">2026 (Atual)</option>
                            <option value="2025" selected>2025</option>
                            <option value="2024">2024 (Histórico)</option>
                            <option value="2023">2023 (Histórico)</option>
                            <option value="2022">2022 (Histórico)</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Conteúdo Dinâmico por Módulo -->
            <?php if ($modulo_ativo == 'geral'): ?>

            <!-- Dashboard Executivo -->
            <div id="dashboard-executivo-container" style="display: none;">
                <div class="chart-card" style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div>
                            <h3 class="chart-title" style="margin: 0;"><i data-lucide="bar-chart-4"></i> Dashboard Executivo</h3>
                            <p style="color: #7f8c8d; margin: 5px 0 0 0;">Visão consolidada: Contratações Planejadas vs Executadas</p>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <label for="ano-dashboard-executivo" style="font-size: 14px; color: #495057;">Ano:</label>
                            <select id="ano-dashboard-executivo" onchange="atualizarDashboardExecutivo()" style="padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; background: white;">
                                <option value="2025" selected>2025 (Atual)</option>
                                <option value="2024">2024</option>
                                <option value="2023">2023</option>
                                <option value="2022">2022</option>
                                <option value="2026">2026</option>
                                <option value="todos">Todos os Anos</option>
                            </select>
                            <button onclick="gerarDashboardExecutivo()" style="padding: 8px 16px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i> Atualizar
                            </button>
                            <button onclick="debugSelect()" style="padding: 8px 16px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 5px;">
                                🔍 Debug
                            </button>
                            <button onclick="forcarAtualizacao()" style="padding: 8px 16px; background: #6f42c1; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 5px;">
                                🔄 Forçar
                            </button>
                            <button onclick="testarCards()" style="padding: 8px 16px; background: #fd7e14; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 5px;">
                                🧪 Cards
                            </button>
                        </div>
                    </div>

                    <!-- KPIs Resumo -->
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0;">
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #1e3c72;">
                            <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #1e3c72;"><i data-lucide="calendar-check" style="width: 16px; height: 16px;"></i> Planejadas</h4>
                            <div style="font-size: 28px; font-weight: bold; color: #1e3c72; margin: 5px 0;" id="total-planejadas-dash">-</div>
                            <p style="color: #7f8c8d; margin: 3px 0; font-size: 11px;" id="label-planejadas">contratações PCA</p>
                        </div>

                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #059669;">
                            <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #059669;"><i data-lucide="trending-up" style="width: 16px; height: 16px;"></i> Executadas</h4>
                            <div style="font-size: 28px; font-weight: bold; color: #059669; margin: 5px 0;" id="total-executadas-dash">-</div>
                            <p style="color: #7f8c8d; margin: 3px 0; font-size: 11px;" id="label-executadas">licitações criadas</p>
                        </div>

                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #0369a1;">
                            <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #0369a1;"><i data-lucide="percent" style="width: 16px; height: 16px;"></i> Taxa Execução</h4>
                            <div style="font-size: 28px; font-weight: bold; color: #0369a1; margin: 5px 0;"><span id="taxa-execucao-dash">-</span>%</div>
                            <p style="color: #7f8c8d; margin: 3px 0; font-size: 11px;">executado/planejado</p>
                        </div>

                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #dc2626;">
                            <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #dc2626;"><i data-lucide="check-circle" style="width: 16px; height: 16px;"></i> Homologadas</h4>
                            <div style="font-size: 28px; font-weight: bold; color: #dc2626; margin: 5px 0;" id="total-homologadas-dash">-</div>
                            <p style="color: #7f8c8d; margin: 3px 0; font-size: 11px;">concluídas</p>
                        </div>
                    </div>

                    <!-- Gráfico Planejadas vs Executadas -->
                    <div style="background: white; padding: 20px; border-radius: 8px; margin-top: 20px;">
                        <h4 style="margin: 0 0 20px 0; color: #1e3c72;"><i data-lucide="bar-chart-3" style="width: 20px; height: 20px;"></i> Evolução por Ano: Planejadas vs Executadas</h4>
                        <canvas id="grafico-planejadas-vs-executadas" width="400" height="200"></canvas>
                    </div>

                    <!-- Taxa de Execução por Ano -->
                    <div style="background: white; padding: 20px; border-radius: 8px; margin-top: 20px;">
                        <h4 style="margin: 0 0 20px 0; color: #059669;"><i data-lucide="trending-up" style="width: 20px; height: 20px;"></i> Taxa de Execução por Ano</h4>
                        <canvas id="grafico-taxa-execucao" width="400" height="150"></canvas>
                    </div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="chart-card">
                    <h3 class="chart-title"><i data-lucide="bar-chart-4"></i> Dashboard Executivo</h3>
                    <p style="color: #7f8c8d; margin-bottom: 20px;">Visão consolidada de todos os módulos</p>
                    <div style="text-align: center;">
                        <i data-lucide="trending-up" style="width: 64px; height: 64px; color: #3b82f6; margin-bottom: 20px;"></i>
                        <button class="btn-primary" onclick="gerarDashboardExecutivo()">Gerar Dashboard</button>
                    </div>
                </div>

                <div class="chart-card">
                    <h3 class="chart-title"><i data-lucide="calendar-check"></i> Planejamento</h3>
                    <p style="color: #7f8c8d; margin-bottom: 20px;">Relatórios consolidados do PCA</p>
                    <div style="text-align: center;">
                        <i data-lucide="pie-chart" style="width: 64px; height: 64px; color: #1e3c72; margin-bottom: 20px;"></i>
                        <button class="btn-primary" onclick="window.location.href='?modulo=planejamento'">Acessar</button>
                    </div>
                </div>

                <div class="chart-card">
                    <h3 class="chart-title"><i data-lucide="award"></i> Qualificação</h3>
                    <p style="color: #7f8c8d; margin-bottom: 20px;">Qualificação de documentação e artefatos licitatórios</p>
                    <div style="text-align: center;">
                        <i data-lucide="users" style="width: 64px; height: 64px; color: #f59e0b; margin-bottom: 20px;"></i>
                        <button class="btn-primary" onclick="window.location.href='?modulo=qualificacao'">Acessar</button>
                    </div>
                </div>

                <div class="chart-card">
                    <h3 class="chart-title"><i data-lucide="gavel"></i> Licitações</h3>
                    <p style="color: #7f8c8d; margin-bottom: 20px;">Performance de processos licitatórios</p>
                    <div style="text-align: center;">
                        <i data-lucide="trending-up" style="width: 64px; height: 64px; color: #10b981; margin-bottom: 20px;"></i>
                        <button class="btn-primary" onclick="window.location.href='?modulo=licitacao'">Acessar</button>
                    </div>
                </div>

                <div class="chart-card">
                    <h3 class="chart-title"><i data-lucide="file-text"></i> Contratos</h3>
                    <p style="color: #7f8c8d; margin-bottom: 20px;">Gestão de contratos administrativos</p>
                    <div style="text-align: center;">
                        <i data-lucide="file-check" style="width: 64px; height: 64px; color: #dc2626; margin-bottom: 20px;"></i>
                        <button class="btn-primary" onclick="window.location.href='?modulo=contratos'">Acessar</button>
                    </div>
                </div>
            </div>

            <?php elseif ($modulo_ativo == 'qualificacao'): ?>
            <!-- Data Cards Executivos - Qualificações (Layout Compacto) -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0;">
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #1e3c72;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #1e3c72;"><i data-lucide="file-text" style="width: 16px; height: 16px;"></i> Total</h4>
                    <div style="font-size: 28px; font-weight: bold; color: #1e3c72; margin: 5px 0;" id="total-processos-qual">-</div>
                    <p style="color: #7f8c8d; margin: 3px 0; font-size: 11px;">processos</p>
                    <div style="font-size: 14px; font-weight: 600; color: #3b82f6; margin: 5px 0;" id="valor-total-qual">-</div>
                </div>

                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #d97706;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #d97706;"><i data-lucide="search" style="width: 16px; height: 16px;"></i> Em Análise</h4>
                    <div style="font-size: 28px; font-weight: bold; color: #d97706; margin: 5px 0;" id="total-em-analise">-</div>
                    <p style="color: #7f8c8d; margin: 3px 0; font-size: 11px;">em análise</p>
                    <div style="font-size: 14px; font-weight: 600; color: #f59e0b; margin: 5px 0;" id="valor-em-analise">-</div>
                </div>

                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #059669;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #059669;"><i data-lucide="check-circle" style="width: 16px; height: 16px;"></i> Concluídos</h4>
                    <div style="font-size: 28px; font-weight: bold; color: #059669; margin: 5px 0;" id="total-concluidos">-</div>
                    <p style="color: #7f8c8d; margin: 3px 0; font-size: 11px;">finalizados</p>
                    <div style="font-size: 14px; font-weight: 600; color: #10b981; margin: 5px 0;" id="valor-concluidos">-</div>
                </div>

                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #6b7280;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #6b7280;"><i data-lucide="archive" style="width: 16px; height: 16px;"></i> Arquivados</h4>
                    <div style="font-size: 28px; font-weight: bold; color: #6b7280; margin: 5px 0;" id="total-arquivados">-</div>
                    <p style="color: #7f8c8d; margin: 3px 0; font-size: 11px;">arquivados</p>
                    <div style="font-size: 14px; font-weight: 600; color: #9ca3af; margin: 5px 0;" id="valor-arquivados">-</div>
                </div>
            </div>

            <!-- Sistema de Abas para Qualificações -->
            <div class="tabs-container">
                <div class="tabs-header">
                    <button class="tab-button active" onclick="showTab('filtros')">
                        <i data-lucide="filter"></i>
                        Filtros Gerais
                    </button>
                    <button class="tab-button" onclick="showTab('relatorio')">
                        <i data-lucide="file-text"></i>
                        Visualizar Relatório
                    </button>
                    <button class="tab-button" onclick="showTab('exportacao')">
                        <i data-lucide="download"></i>
                        Formato e Exportação
                    </button>
                </div>
                
                <!-- ABA 1: Filtros Gerais -->
                <div id="filtros" class="tab-content active">
                    <h3>📅 Período</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <i data-lucide="calendar"></i>
                                Data Início
                            </label>
                            <input type="date" id="data_inicio" name="data_inicio">
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="calendar"></i>
                                Data Fim
                            </label>
                            <input type="date" id="data_fim" name="data_fim">
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="clock"></i>
                                Presets
                            </label>
                            <select id="preset_periodo" onchange="aplicarPreset()">
                                <option value="">Personalizado</option>
                                <option value="mes_atual">Mês Atual</option>
                                <option value="trimestre">Trimestre Atual</option>
                                <option value="ano">Ano Atual</option>
                                <option value="ultimo_mes">Último Mês</option>
                            </select>
                        </div>
                    </div>
                    
                    <h3>🏢 Filtros Específicos</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <i data-lucide="building"></i>
                                Área Demandante
                            </label>
                            <select id="area_filtro" name="area_filtro">
                                <option value="">Todas as Áreas</option>
                                <!-- Preenchido via JavaScript -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="check-circle"></i>
                                Status
                            </label>
                            <select id="status_filtro" name="status_filtro">
                                <option value="">Todos os Status</option>
                                <option value="EM ANÁLISE">EM ANÁLISE</option>
                                <option value="CONCLUÍDO">CONCLUÍDO</option>
                                <option value="ARQUIVADO">ARQUIVADO</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="gavel"></i>
                                Modalidade
                            </label>
                            <select id="modalidade_filtro" name="modalidade_filtro">
                                <option value="">Todas as Modalidades</option>
                                <option value="PREGÃO">PREGÃO</option>
                                <option value="DISPENSA">DISPENSA</option>
                                <option value="INEXIBILIDADE">INEXIBILIDADE</option>
                            </select>
                        </div>
                    </div>
                    
                    <h3>💰 Faixa de Valores</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <i data-lucide="dollar-sign"></i>
                                Valor Mínimo
                            </label>
                            <input type="number" id="valor_minimo" name="valor_minimo" step="0.01" placeholder="0,00">
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="dollar-sign"></i>
                                Valor Máximo
                            </label>
                            <input type="number" id="valor_maximo" name="valor_maximo" step="0.01" placeholder="Sem limite">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button class="btn btn-primary" onclick="aplicarFiltros()">
                            <i data-lucide="search"></i>
                            Aplicar Filtros
                        </button>
                        <button class="btn btn-secondary" onclick="limparFiltros()">
                            <i data-lucide="refresh-cw"></i>
                            Limpar Filtros
                        </button>
                    </div>
                </div>
                
                <!-- ABA 2: Relatório -->
                <div id="relatorio" class="tab-content">
                    <div id="loading" class="loading" style="display: none;">
                        <i data-lucide="loader" style="animation: spin 1s linear infinite;"></i>
                        <p>Carregando relatório...</p>
                    </div>
                    
                    <div id="results" style="display: none;">
                        <div class="results-container">
                            <div class="results-header">
                                <div class="results-title">Resultados do Relatório</div>
                                <div class="export-buttons">
                                    <button class="btn btn-export" onclick="exportarRelatorio('html')">
                                        <i data-lucide="eye"></i>
                                        HTML
                                    </button>
                                    <button class="btn btn-export" onclick="exportarRelatorio('excel')">
                                        <i data-lucide="file-spreadsheet"></i>
                                        Excel
                                    </button>
                                </div>
                            </div>
                            
                            <table class="results-table" id="tabela-resultados">
                                <thead>
                                    <tr>
                                        <th>NUP</th>
                                        <th>Área Demandante</th>
                                        <th>Modalidade</th>
                                        <th>Status</th>
                                        <th>Objeto (Resumo)</th>
                                        <th>Valor Estimado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Preenchido via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- ABA 3: Exportação -->
                <div id="exportacao" class="tab-content">
                    <h3>📄 Formato de Saída</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Formato Principal</label>
                            <select id="formato_principal">
                                <option value="html">HTML (Visualização)</option>
                                <option value="excel">Excel (.xlsx)</option>
                                <option value="csv">CSV (Excel compatível)</option>
                            </select>
                        </div>
                    </div>
                    
                    <h3>📊 Opções de Conteúdo</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="incluir_graficos" checked>
                                Incluir Gráficos Estatísticos
                            </label>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="incluir_detalhes" checked>
                                Dados Detalhados
                            </label>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="incluir_filtros">
                                Incluir Filtros Aplicados
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button class="btn btn-primary" onclick="gerarRelatorioFinal()">
                            <i data-lucide="download"></i>
                            Exportar Relatório
                        </button>
                    </div>
                </div>
            </div>

            <?php elseif ($modulo_ativo == 'planejamento'): ?>
            <!-- Dashboard Executivo PCA -->
            <!-- Data Cards Executivos - Planejamento PCA (Layout Compacto) -->
            <div id="dashboard-pca" style="display: none;">
                <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 15px; margin: 20px 0;" class="pca-cards-grid">
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #1e3c72;">
                        <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #1e3c72;"><i data-lucide="calendar-check" style="width: 16px; height: 16px;"></i> Total</h4>
                        <div style="font-size: 28px; font-weight: bold; color: #1e3c72; margin: 5px 0;" id="total-contratacoes">-</div>
                        <p style="color: #7f8c8d; margin: 3px 0; font-size: 11px;">contratações</p>
                        <div style="font-size: 14px; font-weight: 600; color: #3b82f6; margin: 5px 0;" id="valor-total-pca">-</div>
                    </div>

                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #059669;">
                        <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #059669;"><i data-lucide="trending-up" style="width: 16px; height: 16px;"></i> Licitação</h4>
                        <div style="font-size: 28px; font-weight: bold; color: #059669; margin: 5px 0;"><span id="percentual-licitados">-</span></div>
                        <p style="color: #7f8c8d; margin: 3px 0; font-size: 11px;">com processo</p>
                        <div style="font-size: 14px; font-weight: 600; color: #10b981; margin: 5px 0;">-</div>
                    </div>

                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #0369a1;">
                        <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #0369a1;"><i data-lucide="check-square" style="width: 16px; height: 16px;"></i> Aprovadas</h4>
                        <div style="font-size: 28px; font-weight: bold; color: #0369a1; margin: 5px 0;" id="total-aprovadas">-</div>
                        <p style="color: #7f8c8d; margin: 3px 0; font-size: 11px;">aprovadas</p>
                        <div style="font-size: 14px; font-weight: 600; color: #0284c7; margin: 5px 0;" id="valor-aprovadas">-</div>
                    </div>

                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #dc2626;">
                        <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #dc2626;"><i data-lucide="alert-triangle" style="width: 16px; height: 16px;"></i> Em Atraso</h4>
                        <div style="font-size: 28px; font-weight: bold; color: #dc2626; margin: 5px 0;" id="total-atraso">-</div>
                        <p style="color: #7f8c8d; margin: 3px 0; font-size: 11px;">não iniciadas</p>
                        <div style="font-size: 14px; font-weight: 600; color: #ef4444; margin: 5px 0;" id="valor-atraso">-</div>
                    </div>

                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #d97706;">
                        <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #d97706;"><i data-lucide="clock" style="width: 16px; height: 16px;"></i> Preparação</h4>
                        <div style="font-size: 28px; font-weight: bold; color: #d97706; margin: 5px 0;" id="total-preparacao">-</div>
                        <p style="color: #7f8c8d; margin: 3px 0; font-size: 11px;">em preparação</p>
                        <div style="font-size: 14px; font-weight: 600; color: #f59e0b; margin: 5px 0;" id="valor-preparacao">-</div>
                    </div>

                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #059669;">
                        <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #059669;"><i data-lucide="check-circle" style="width: 16px; height: 16px;"></i> Encerradas</h4>
                        <div style="font-size: 28px; font-weight: bold; color: #059669; margin: 5px 0;" id="total-encerradas">-</div>
                        <p style="color: #7f8c8d; margin: 3px 0; font-size: 11px;">finalizadas</p>
                        <div style="font-size: 14px; font-weight: 600; color: #10b981; margin: 5px 0;" id="valor-encerradas">-</div>
                    </div>
                </div>
            </div>

            <!-- Sistema de Abas para Planejamento - MESMO MODELO QUALIFICAÇÕES -->
            <div class="tabs-container">
                <div class="tabs-header">
                    <button class="tab-button active" onclick="showTab('filtros')">
                        <i data-lucide="filter"></i>
                        Filtros Gerais
                    </button>
                    <button class="tab-button" onclick="showTab('relatorio')">
                        <i data-lucide="file-text"></i>
                        Visualizar Relatório
                    </button>
                    <button class="tab-button" onclick="showTab('exportacao')">
                        <i data-lucide="download"></i>
                        Formato e Exportação
                    </button>
                </div>

                <!-- ABA 1: Filtros Gerais -->
                <div id="filtros" class="tab-content active">
                    <h3>📅 Período</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <i data-lucide="calendar"></i>
                                Data Início
                            </label>
                            <input type="date" id="data_inicio" name="data_inicio">
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="calendar"></i>
                                Data Fim
                            </label>
                            <input type="date" id="data_fim" name="data_fim">
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="clock"></i>
                                Presets
                            </label>
                            <select id="preset_periodo" onchange="aplicarPreset()">
                                <option value="">Personalizado</option>
                                <option value="mes_atual">Mês Atual</option>
                                <option value="trimestre">Trimestre Atual</option>
                                <option value="ano">Ano Atual</option>
                                <option value="ultimo_mes">Último Mês</option>
                            </select>
                        </div>
                    </div>

                    <h3>📋 Filtros Específicos - Execução PCA</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <i data-lucide="building"></i>
                                Área Requisitante
                            </label>
                            <select id="area_requisitante_filtro" name="area_requisitante_filtro">
                                <option value="">🔄 Carregando áreas...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="list"></i>
                                Categoria da Contratação
                            </label>
                            <select id="categoria_filtro" name="categoria_filtro">
                                <option value="">Todas as Categorias</option>
                                <option value="BENS">BENS</option>
                                <option value="SERVICOS">SERVIÇOS</option>
                                <option value="CONTRATACOES_TIC">CONTRATAÇÕES TIC</option>
                                <option value="OBRAS_SERV_ESP_ENGENHARIA">OBRAS/SERV. ESP. ENGENHARIA</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="activity"></i>
                                Status de Execução
                            </label>
                            <select id="status_execucao_filtro" name="status_execucao_filtro">
                                <option value="">Todos os Status</option>
                                <option value="EM ATRASO">🔴 EM ATRASO</option>
                                <option value="EM EXECUÇÃO">🟡 EM EXECUÇÃO</option>
                                <option value="EXECUTADO">🟢 EXECUTADO</option>
                                <option value="NÃO EXECUTADO">⚫ NÃO EXECUTADO</option>
                            </select>
                        </div>
                    </div>

                    <h3>📊 Filtros de Status</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <i data-lucide="check-circle"></i>
                                Status da Contratação
                            </label>
                            <select id="status_contratacao_filtro" name="status_contratacao_filtro">
                                <option value="">Todos os Status</option>
                                <option value="Aprovada">✅ Aprovada</option>
                                <option value="Aguardando Aprovação">⏳ Aguardando Aprovação</option>
                                <option value="Rascunho">📝 Rascunho</option>
                                <option value="Devolvida">🔄 Devolvida</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="alert-triangle"></i>
                                Situação de Execução Original
                            </label>
                            <select id="situacao_original_filtro" name="situacao_original_filtro">
                                <option value="">Todas as Situações</option>
                                <option value="Não iniciado">🔴 Não iniciado</option>
                                <option value="Preparação">🟡 Preparação</option>
                                <option value="Edição">🟠 Edição</option>
                                <option value="Encerrada">🟢 Encerrada</option>
                                <option value="Revogada">⚫ Revogada</option>
                                <option value="Anulada">❌ Anulada</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="link"></i>
                                Possui Licitação
                            </label>
                            <select id="tem_licitacao_filtro" name="tem_licitacao_filtro">
                                <option value="">Todos</option>
                                <option value="SIM">✅ Com Licitação</option>
                                <option value="NAO">❌ Sem Licitação</option>
                            </select>
                        </div>
                    </div>

                    <h3>💰 Faixa de Valores</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <i data-lucide="dollar-sign"></i>
                                Valor Mínimo
                            </label>
                            <input type="number" id="valor_minimo" name="valor_minimo" step="0.01" placeholder="0,00">
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="dollar-sign"></i>
                                Valor Máximo
                            </label>
                            <input type="number" id="valor_maximo" name="valor_maximo" step="0.01" placeholder="Sem limite">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-primary" onclick="aplicarFiltros()">
                            <i data-lucide="search"></i>
                            Aplicar Filtros
                        </button>
                        <button class="btn btn-secondary" onclick="limparFiltros()">
                            <i data-lucide="refresh-cw"></i>
                            Limpar Filtros
                        </button>
                    </div>
                </div>

                <!-- ABA 2: Relatório -->
                <div id="relatorio" class="tab-content">
                    <div id="loading" class="loading" style="display: none;">
                        <i data-lucide="loader" style="animation: spin 1s linear infinite;"></i>
                        <p>Carregando relatório...</p>
                    </div>

                    <div id="results" style="display: none;">
                        <div class="results-container">
                            <div class="results-header">
                                <div class="results-title">Resultados do Relatório</div>
                                <div class="export-buttons">
                                    <button class="btn btn-export" onclick="exportarRelatorio('html')">
                                        <i data-lucide="eye"></i>
                                        HTML
                                    </button>
                                    <button class="btn btn-export" onclick="exportarRelatorio('excel')">
                                        <i data-lucide="file-spreadsheet"></i>
                                        Excel
                                    </button>
                                </div>
                            </div>

                            <table class="results-table" id="tabela-resultados">
                                <thead>
                                    <tr>
                                        <th>Nº Contratação</th>
                                        <th>Título (Resumo)</th>
                                        <th>Categoria</th>
                                        <th>Área Requisitante</th>
                                        <th>Status Execução</th>
                                        <th>Valor Total</th>
                                        <th>Dias Atraso</th>
                                        <th>Tem Licitação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Preenchido via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ABA 3: Exportação -->
                <div id="exportacao" class="tab-content">
                    <h3>📄 Formato de Saída</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Formato Principal</label>
                            <select id="formato_principal">
                                <option value="html">HTML (Visualização)</option>
                                <option value="excel">Excel (.xlsx)</option>
                                <option value="csv">CSV (Excel compatível)</option>
                            </select>
                        </div>
                    </div>

                    <h3>📊 Opções de Conteúdo</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="incluir_graficos" checked>
                                Incluir Gráficos Estatísticos
                            </label>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="incluir_detalhes" checked>
                                Dados Detalhados
                            </label>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="incluir_filtros">
                                Incluir Filtros Aplicados
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-primary" onclick="gerarRelatorioFinal()">
                            <i data-lucide="download"></i>
                            Exportar Relatório
                        </button>
                    </div>
                </div>
            </div>

            <?php elseif ($modulo_ativo == 'licitacao'): ?>
            <!-- Data Cards Executivos - Licitações (Layout Compacto) -->
            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin: 20px 0;">
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #1e3c72;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #1e3c72;"><i data-lucide="gavel" style="width: 16px; height: 16px;"></i> Total</h4>
                    <div style="font-size: 28px; font-weight: bold; color: #1e3c72; margin: 5px 0;" id="total-licitacoes">-</div>
                    <p style="color: #7f8c8d; margin: 3px 0; font-size: 11px;">licitações</p>
                    <div style="font-size: 14px; font-weight: 600; color: #3b82f6; margin: 5px 0;" id="valor-total-licitacoes">-</div>
                </div>

                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #059669;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #059669;"><i data-lucide="check-circle" style="width: 16px; height: 16px;"></i> Homologadas</h4>
                    <div style="font-size: 28px; font-weight: bold; color: #059669; margin: 5px 0;" id="total-homologadas">-</div>
                    <p style="color: #7f8c8d; margin: 3px 0; font-size: 11px;">concluídas</p>
                    <div style="font-size: 14px; font-weight: 600; color: #10b981; margin: 5px 0;" id="valor-homologadas">-</div>
                </div>

                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #d97706;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #d97706;"><i data-lucide="clock" style="width: 16px; height: 16px;"></i> Em Andamento</h4>
                    <div style="font-size: 28px; font-weight: bold; color: #d97706; margin: 5px 0;" id="total-andamento">-</div>
                    <p style="color: #7f8c8d; margin: 3px 0; font-size: 11px;">em processo</p>
                    <div style="font-size: 14px; font-weight: 600; color: #f59e0b; margin: 5px 0;" id="valor-andamento">-</div>
                </div>

                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #16a34a; position: relative;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #16a34a;"><i data-lucide="trending-down" style="width: 16px; height: 16px;"></i> Economia</h4>
                    <div style="font-size: 28px; font-weight: bold; color: #16a34a; margin: 5px 0;" id="economia-total">-</div>
                    <p style="color: #7f8c8d; margin: 3px 0; font-size: 11px;">economizada</p>
                    <div style="font-size: 14px; font-weight: 600; color: #15803d; margin: 5px 0;" id="economia-percentual">-</div>
                </div>

                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #dc2626;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #dc2626;"><i data-lucide="x-circle" style="width: 16px; height: 16px;"></i> Canceladas</h4>
                    <div style="font-size: 28px; font-weight: bold; color: #dc2626; margin: 5px 0;" id="total-canceladas">-</div>
                    <p style="color: #7f8c8d; margin: 3px 0; font-size: 11px;">revogadas</p>
                    <div style="font-size: 14px; font-weight: 600; color: #ef4444; margin: 5px 0;" id="valor-canceladas">-</div>
                </div>
            </div>

            <!-- Sistema de Abas para Licitações - MESMO MODELO QUALIFICAÇÕES -->
            <div class="tabs-container">
                <div class="tabs-header">
                    <button class="tab-button active" onclick="showTab('filtros')">
                        <i data-lucide="filter"></i>
                        Filtros Gerais
                    </button>
                    <button class="tab-button" onclick="showTab('relatorio')">
                        <i data-lucide="file-text"></i>
                        Visualizar Relatório
                    </button>
                    <button class="tab-button" onclick="showTab('exportacao')">
                        <i data-lucide="download"></i>
                        Formato e Exportação
                    </button>
                </div>

                <!-- ABA 1: Filtros Gerais -->
                <div id="filtros" class="tab-content active">
                    <h3>📅 Período</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <i data-lucide="calendar"></i>
                                Data Início
                            </label>
                            <input type="date" id="data_inicio" name="data_inicio">
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="calendar"></i>
                                Data Fim
                            </label>
                            <input type="date" id="data_fim" name="data_fim">
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="clock"></i>
                                Presets
                            </label>
                            <select id="preset_periodo" onchange="aplicarPreset()">
                                <option value="">Personalizado</option>
                                <option value="mes_atual">Mês Atual</option>
                                <option value="trimestre">Trimestre Atual</option>
                                <option value="ano">Ano Atual</option>
                                <option value="ultimo_mes">Último Mês</option>
                            </select>
                        </div>
                    </div>

                    <h3>⚖️ Filtros Específicos</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <i data-lucide="gavel"></i>
                                Modalidade
                            </label>
                            <select id="modalidade_filtro" name="modalidade_filtro">
                                <option value="">Todas as Modalidades</option>
                                <option value="PREGAO">PREGÃO</option>
                                <option value="CONCORR�NCIA">CONCORRÊNCIA</option>
                                <option value="DISPENSA">DISPENSA</option>
                                <option value="INEXIGIBILIDADE">INEXIGIBILIDADE</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="check-circle"></i>
                                Status da Licitação
                            </label>
                            <select id="status_filtro" name="status_filtro">
                                <option value="">Todos os Status</option>
                                <option value="PREPARACAO">PREPARAÇÃO</option>
                                <option value="EM_ANDAMENTO">EM ANDAMENTO</option>
                                <option value="HOMOLOGADO">HOMOLOGADO</option>
                                <option value="FRACASSADO">FRACASSADO</option>
                                <option value="REVOGADO">REVOGADO</option>
                                <option value="CANCELADO">CANCELADO</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="user-check"></i>
                                Pregoeiro
                            </label>
                            <select id="pregoeiro_filtro" name="pregoeiro_filtro">
                                <option value="">Todos os Pregoeiros</option>
                            </select>
                        </div>
                    </div>

                    <h3>💰 Faixa de Valores</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <i data-lucide="dollar-sign"></i>
                                Valor Mínimo
                            </label>
                            <input type="number" id="valor_minimo" name="valor_minimo" step="0.01" placeholder="0,00">
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="dollar-sign"></i>
                                Valor Máximo
                            </label>
                            <input type="number" id="valor_maximo" name="valor_maximo" step="0.01" placeholder="Sem limite">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-primary" onclick="aplicarFiltros()">
                            <i data-lucide="search"></i>
                            Aplicar Filtros
                        </button>
                        <button class="btn btn-secondary" onclick="limparFiltros()">
                            <i data-lucide="refresh-cw"></i>
                            Limpar Filtros
                        </button>
                    </div>
                </div>

                <!-- ABA 2: Relatório -->
                <div id="relatorio" class="tab-content">
                    <div id="loading" class="loading" style="display: none;">
                        <i data-lucide="loader" style="animation: spin 1s linear infinite;"></i>
                        <p>Carregando relatório...</p>
                    </div>

                    <div id="results" style="display: none;">
                        <div class="results-container">
                            <div class="results-header">
                                <div class="results-title">Resultados do Relatório</div>
                                <div class="export-buttons">
                                    <button class="btn btn-export" onclick="exportarRelatorio('html')">
                                        <i data-lucide="eye"></i>
                                        HTML
                                    </button>
                                    <button class="btn btn-export" onclick="exportarRelatorio('excel')">
                                        <i data-lucide="file-spreadsheet"></i>
                                        Excel
                                    </button>
                                </div>
                            </div>

                            <table class="results-table" id="tabela-resultados">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Processo</th>
                                        <th>Objeto</th>
                                        <th>Modalidade</th>
                                        <th>Status</th>
                                        <th>Pregoeiro</th>
                                        <th>Data Abertura</th>
                                        <th>Valor Estimado</th>
                                        <th>Economia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Preenchido via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ABA 3: Exportação -->
                <div id="exportacao" class="tab-content">
                    <h3>📄 Formato de Saída</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Formato Principal</label>
                            <select id="formato_principal">
                                <option value="html">HTML (Visualização)</option>
                                <option value="excel">Excel (.xlsx)</option>
                                <option value="csv">CSV (Excel compatível)</option>
                            </select>
                        </div>
                    </div>

                    <h3>📊 Opções de Conteúdo</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="incluir_graficos" checked>
                                Incluir Gráficos Estatísticos
                            </label>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="incluir_detalhes" checked>
                                Dados Detalhados
                            </label>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="incluir_filtros">
                                Incluir Filtros Aplicados
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-primary" onclick="gerarRelatorioFinal()">
                            <i data-lucide="download"></i>
                            Exportar Relatório
                        </button>
                    </div>
                </div>
            </div>

            <?php elseif ($modulo_ativo == 'contratos'): ?>
            <!-- Sistema de Abas para Contratos - MESMO MODELO QUALIFICAÇÕES -->
            <div class="tabs-container">
                <div class="tabs-header">
                    <button class="tab-button active" onclick="showTab('filtros')">
                        <i data-lucide="filter"></i>
                        Filtros Gerais
                    </button>
                    <button class="tab-button" onclick="showTab('relatorio')">
                        <i data-lucide="file-text"></i>
                        Visualizar Relatório
                    </button>
                    <button class="tab-button" onclick="showTab('exportacao')">
                        <i data-lucide="download"></i>
                        Formato e Exportação
                    </button>
                </div>

                <!-- ABA 1: Filtros Gerais -->
                <div id="filtros" class="tab-content active">
                    <h3>📅 Período</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <i data-lucide="calendar"></i>
                                Data Início
                            </label>
                            <input type="date" id="data_inicio" name="data_inicio">
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="calendar"></i>
                                Data Fim
                            </label>
                            <input type="date" id="data_fim" name="data_fim">
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="clock"></i>
                                Presets
                            </label>
                            <select id="preset_periodo" onchange="aplicarPreset()">
                                <option value="">Personalizado</option>
                                <option value="mes_atual">Mês Atual</option>
                                <option value="trimestre">Trimestre Atual</option>
                                <option value="ano">Ano Atual</option>
                                <option value="ultimo_mes">Último Mês</option>
                            </select>
                        </div>
                    </div>

                    <h3>📋 Filtros Específicos</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <i data-lucide="building-2"></i>
                                Fornecedor
                            </label>
                            <select id="fornecedor_filtro" name="fornecedor_filtro">
                                <option value="">Todos os Fornecedores</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="check-circle"></i>
                                Status do Contrato
                            </label>
                            <select id="status_filtro" name="status_filtro">
                                <option value="">Todos os Status</option>
                                <option value="VIGENTE">VIGENTE</option>
                                <option value="SUSPENSO">SUSPENSO</option>
                                <option value="ENCERRADO">ENCERRADO</option>
                                <option value="RESCINDIDO">RESCINDIDO</option>
                                <option value="VENCIDO">VENCIDO</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="file-text"></i>
                                Tipo de Contrato
                            </label>
                            <select id="tipo_filtro" name="tipo_filtro">
                                <option value="">Todos os Tipos</option>
                                <option value="FORNECIMENTO">FORNECIMENTO</option>
                                <option value="SERVIÇOS">SERVIÇOS</option>
                                <option value="OBRAS">OBRAS</option>
                                <option value="LOCAÇÃO">LOCAÇÃO</option>
                            </select>
                        </div>
                    </div>

                    <h3>💰 Faixa de Valores</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <i data-lucide="dollar-sign"></i>
                                Valor Mínimo
                            </label>
                            <input type="number" id="valor_minimo" name="valor_minimo" step="0.01" placeholder="0,00">
                        </div>
                        <div class="form-group">
                            <label>
                                <i data-lucide="dollar-sign"></i>
                                Valor Máximo
                            </label>
                            <input type="number" id="valor_maximo" name="valor_maximo" step="0.01" placeholder="Sem limite">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-primary" onclick="aplicarFiltros()">
                            <i data-lucide="search"></i>
                            Aplicar Filtros
                        </button>
                        <button class="btn btn-secondary" onclick="limparFiltros()">
                            <i data-lucide="refresh-cw"></i>
                            Limpar Filtros
                        </button>
                    </div>
                </div>

                <!-- ABA 2: Relatório -->
                <div id="relatorio" class="tab-content">
                    <div id="loading" class="loading" style="display: none;">
                        <i data-lucide="loader" style="animation: spin 1s linear infinite;"></i>
                        <p>Carregando relatório...</p>
                    </div>

                    <div id="results" style="display: none;">
                        <div class="results-container">
                            <div class="results-header">
                                <div class="results-title">Resultados do Relatório</div>
                                <div class="export-buttons">
                                    <button class="btn btn-export" onclick="exportarRelatorio('html')">
                                        <i data-lucide="eye"></i>
                                        HTML
                                    </button>
                                    <button class="btn btn-export" onclick="exportarRelatorio('excel')">
                                        <i data-lucide="file-spreadsheet"></i>
                                        Excel
                                    </button>
                                </div>
                            </div>

                            <table class="results-table" id="tabela-resultados">
                                <thead>
                                    <tr>
                                        <th>Número</th>
                                        <th>Fornecedor</th>
                                        <th>Status</th>
                                        <th>Objeto (Resumo)</th>
                                        <th>Valor do Contrato</th>
                                        <th>Vigência</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Preenchido via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ABA 3: Exportação -->
                <div id="exportacao" class="tab-content">
                    <h3>📄 Formato de Saída</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Formato Principal</label>
                            <select id="formato_principal">
                                <option value="html">HTML (Visualização)</option>
                                <option value="excel">Excel (.xlsx)</option>
                                <option value="csv">CSV (Excel compatível)</option>
                            </select>
                        </div>
                    </div>

                    <h3>📊 Opções de Conteúdo</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="incluir_graficos" checked>
                                Incluir Gráficos Estatísticos
                            </label>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="incluir_detalhes" checked>
                                Dados Detalhados
                            </label>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="incluir_filtros">
                                Incluir Filtros Aplicados
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-primary" onclick="gerarRelatorioFinal()">
                            <i data-lucide="download"></i>
                            Exportar Relatório
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
    
    <script>
        // Inicializar ícones Lucide
        lucide.createIcons();
        
        // Controle de Abas
        function showTab(tabId) {
            try {
                console.log('Showing tab:', tabId);
                
                // Esconder todas as abas
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Remover classe active de todos os botões
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Mostrar aba selecionada
                const tabElement = document.getElementById(tabId);
                if (tabElement) {
                    tabElement.classList.add('active');
                } else {
                    console.error('Tab element not found:', tabId);
                }
                
                // Ativar botão correspondente de forma mais robusta
                const tabMapping = {
                    'filtros': 0,
                    'relatorio': 1,
                    'exportacao': 2
                };
                
                const buttons = document.querySelectorAll('.tab-button');
                const targetIndex = tabMapping[tabId];
                
                if (targetIndex !== undefined && buttons[targetIndex]) {
                    buttons[targetIndex].classList.add('active');
                }
                
                // Re-inicializar ícones
                lucide.createIcons();
                
            } catch (error) {
                console.error('Erro na função showTab:', error);
            }
        }
        
        // Aplicar presets de período
        function aplicarPreset() {
            const preset = document.getElementById('preset_periodo').value;
            const hoje = new Date();

            if (preset === 'mes_atual') {
                const inicio = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
                const fim = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0);

                document.getElementById('data_inicio').value = inicio.toISOString().split('T')[0];
                document.getElementById('data_fim').value = fim.toISOString().split('T')[0];
            } else if (preset === 'ultimo_mes') {
                const inicio = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1);
                const fim = new Date(hoje.getFullYear(), hoje.getMonth(), 0);

                document.getElementById('data_inicio').value = inicio.toISOString().split('T')[0];
                document.getElementById('data_fim').value = fim.toISOString().split('T')[0];
            } else if (preset === 'trimestre') {
                const trimestreAtual = Math.floor(hoje.getMonth() / 3);
                const inicio = new Date(hoje.getFullYear(), trimestreAtual * 3, 1);
                const fim = new Date(hoje.getFullYear(), (trimestreAtual + 1) * 3, 0);

                document.getElementById('data_inicio').value = inicio.toISOString().split('T')[0];
                document.getElementById('data_fim').value = fim.toISOString().split('T')[0];
            } else if (preset === 'ano') {
                const inicio = new Date(hoje.getFullYear(), 0, 1);
                const fim = new Date(hoje.getFullYear(), 11, 31);

                document.getElementById('data_inicio').value = inicio.toISOString().split('T')[0];
                document.getElementById('data_fim').value = fim.toISOString().split('T')[0];
            }
        }
        
        // Carregar áreas demandantes
        function carregarAreas() {
            console.log('🔄 Carregando áreas de qualificações...');
            fetch('process_relatorios.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'acao=get_areas_qualificacoes'
            })
            .then(response => response.json())
            .then(data => {
                console.log('📥 Áreas recebidas:', data);
                if (data.success && data.areas) {
                    const select = document.getElementById('area_filtro');
                    if (select) {
                        // Limpar opções existentes (exceto a primeira "Todas as Áreas")
                        while (select.options.length > 1) {
                            select.remove(1);
                        }

                        // Adicionar áreas
                        data.areas.forEach(area => {
                            const option = document.createElement('option');
                            option.value = area;
                            option.textContent = area;
                            select.appendChild(option);
                        });
                        console.log('✅ Áreas carregadas:', data.areas.length);
                    }
                }
            })
            .catch(error => {
                console.error('❌ Erro ao carregar áreas:', error);
            });
        }
        
        // Aplicar filtros e gerar relatório - ESPECÍFICO PARA PCA
        function aplicarFiltros() {
            try {
                console.log('Iniciando aplicação de filtros para PCA...');

                document.getElementById('loading').style.display = 'block';
                document.getElementById('results').style.display = 'none';

                // Determinar qual módulo está ativo
                const urlParams = new URLSearchParams(window.location.search);
                const modulo = urlParams.get('modulo') || 'geral';

                // Coletar dados do formulário baseado no módulo
                const formData = new FormData();

                if (modulo === 'planejamento') {
                    // RELATÓRIO ESPECÍFICO DE EXECUÇÃO PCA
                    formData.append('acao', 'relatorio_execucao_pca');
                    formData.append('ano', document.getElementById('ano_pca_header').value); // CORRIGIDO - envia ano selecionado do header
                    formData.append('data_inicio', document.getElementById('data_inicio').value);
                    formData.append('data_fim', document.getElementById('data_fim').value);
                    formData.append('area_requisitante_filtro', document.getElementById('area_requisitante_filtro').value);
                    formData.append('categoria_filtro', document.getElementById('categoria_filtro').value);
                    formData.append('status_execucao_filtro', document.getElementById('status_execucao_filtro').value);
                    formData.append('status_contratacao_filtro', document.getElementById('status_contratacao_filtro').value);
                    formData.append('situacao_original_filtro', document.getElementById('situacao_original_filtro').value);
                    formData.append('tem_licitacao_filtro', document.getElementById('tem_licitacao_filtro').value);
                    formData.append('valor_minimo', document.getElementById('valor_minimo').value);
                    formData.append('valor_maximo', document.getElementById('valor_maximo').value);
                } else if (modulo === 'qualificacao') {
                    // RELATÓRIO DE QUALIFICAÇÕES (ORIGINAL)
                    formData.append('acao', 'relatorio_area_demandante');
                    formData.append('data_inicio', document.getElementById('data_inicio').value);
                    formData.append('data_fim', document.getElementById('data_fim').value);
                    formData.append('area_filtro', document.getElementById('area_filtro')?.value || '');
                    formData.append('status_filtro', document.getElementById('status_filtro')?.value || '');
                    formData.append('modalidade_filtro', document.getElementById('modalidade_filtro')?.value || '');
                    formData.append('valor_minimo', document.getElementById('valor_minimo').value);
                    formData.append('valor_maximo', document.getElementById('valor_maximo').value);
                } else if (modulo === 'licitacao') {
                    // RELATÓRIO DE LICITAÇÕES E PREGÕES
                    formData.append('acao', 'relatorio_licitacoes_filtrado');
                    formData.append('data_inicio', document.getElementById('data_inicio').value);
                    formData.append('data_fim', document.getElementById('data_fim').value);
                    formData.append('modalidade_filtro', document.getElementById('modalidade_filtro')?.value || '');
                    formData.append('status_filtro', document.getElementById('status_filtro')?.value || '');
                    formData.append('pregoeiro_filtro', document.getElementById('pregoeiro_filtro')?.value || '');
                    formData.append('valor_minimo', document.getElementById('valor_minimo').value);
                    formData.append('valor_maximo', document.getElementById('valor_maximo').value);
                } else {
                    alert('Módulo não suportado ainda: ' + modulo);
                    document.getElementById('loading').style.display = 'none';
                    return;
                }

                console.log('Enviando dados para:', formData.get('acao'));

                // Usar process_relatorios.php para qualificações
                let endpointUrl = modulo === 'qualificacao' ? 'process_relatorios.php' : 'process.php';
                let isPlanning = modulo === 'planejamento';

                if (isPlanning) {
                    // Para planejamento, tentar endpoint alternativo primeiro
                    endpointUrl = 'relatorio_pca_alternativo.php';
                    console.log('Usando endpoint alternativo para PCA:', endpointUrl);
                }

                console.log('🎯 Endpoint para', modulo, ':', endpointUrl);

                fetch(endpointUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('📡 Resposta recebida, status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .catch(error => {
                    console.error('❌ Erro no endpoint principal:', error);

                    // FALLBACK: Se falhar e for planejamento, tentar process.php
                    if (isPlanning && endpointUrl !== 'process.php') {
                        console.log('Tentando fallback para process.php...');
                        return fetch('process.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            console.log('Resposta fallback, status:', response.status);
                            if (!response.ok) {
                                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                            }
                            return response.json();
                        });
                    } else {
                        throw error;
                    }
                })
                .then(data => {
                    console.log('Dados recebidos:', data);
                    document.getElementById('loading').style.display = 'none';

                    if (data.success) {
                        if (modulo === 'planejamento') {
                            preencherTabelaPCA(data.data);
                        } else if (modulo === 'licitacao') {
                            preencherTabelaLicitacoes(data.data, data.resumo);
                        } else {
                            preencherTabela(data.data);
                        }
                        document.getElementById('results').style.display = 'block';
                        showTab('relatorio');
                    } else {
                        console.error('Erro do servidor:', data.message);
                        alert('Erro ao gerar relatório: ' + (data.message || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    document.getElementById('loading').style.display = 'none';
                    console.error('Erro detalhado:', error);
                    alert('Erro ao gerar relatório: ' + error.message + '\nVerifique o console para mais detalhes.');
                });
            } catch (error) {
                document.getElementById('loading').style.display = 'none';
                console.error('Erro na função aplicarFiltros:', error);
                alert('Erro interno na aplicação: ' + error.message);
            }
        }

        // Preencher tabela específica para PCA (Execução do Planejamento)
        function preencherTabelaPCA(data) {
            try {
                console.log('Preenchendo tabela PCA com dados:', data);

                const tbody = document.querySelector('#tabela-resultados tbody');
                if (!tbody) {
                    throw new Error('Elemento tbody da tabela não encontrado');
                }

                tbody.innerHTML = ''; // Limpar tabela

                if (!data.resultados || data.resultados.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px; color: #6b7280;">Nenhum resultado encontrado com os filtros aplicados</td></tr>';
                    return;
                }

                data.resultados.forEach((item, index) => {
                    try {
                        const row = document.createElement('tr');

                        // Definir classes CSS para status
                        let statusClass = 'status-analise';
                        let statusEmoji = '';

                        switch(item.status_execucao_ajustado) {
                            case 'EM ATRASO':
                                statusClass = 'status-atraso';
                                statusEmoji = '🔴';
                                break;
                            case 'EM EXECUÇÃO':
                                statusClass = 'status-execucao';
                                statusEmoji = '🟡';
                                break;
                            case 'EXECUTADO':
                                statusClass = 'status-executado';
                                statusEmoji = '🟢';
                                break;
                            case 'NÃO EXECUTADO':
                                statusClass = 'status-nao-executado';
                                statusEmoji = '⚫';
                                break;
                        }

                        // Badge para "Tem Licitação"
                        let licitacaoBadge = '';
                        if (item.tem_licitacao === 'SIM') {
                            licitacaoBadge = '<span style="background: #10b981; color: white; padding: 2px 6px; border-radius: 4px; font-size: 11px;">✅ SIM</span>';
                        } else {
                            licitacaoBadge = '<span style="background: #ef4444; color: white; padding: 2px 6px; border-radius: 4px; font-size: 11px;">❌ NÃO</span>';
                        }

                        // Exibir dias de atraso apenas se > 0
                        let diasAtrasoDisplay = '';
                        if (item.dias_atraso > 0) {
                            diasAtrasoDisplay = `${item.dias_atraso} dias`;
                        } else {
                            diasAtrasoDisplay = '-';
                        }

                        row.innerHTML = `
                            <td>${item.numero_contratacao || '-'}</td>
                            <td title="${item.titulo_contratacao || ''}">${(item.titulo_contratacao || '').substring(0, 60)}...</td>
                            <td>${item.categoria_contratacao || '-'}</td>
                            <td title="${item.area_requisitante || ''}">${(item.area_requisitante || '').substring(0, 30)}...</td>
                            <td><span class="status-badge ${statusClass}">${statusEmoji} ${item.status_execucao_ajustado || '-'}</span></td>
                            <td class="valor">${item.valor_formatado || 'R$ 0,00'}</td>
                            <td style="text-align: center; color: ${item.dias_atraso > 0 ? '#dc2626' : '#6b7280'}">${diasAtrasoDisplay}</td>
                            <td>${licitacaoBadge}</td>
                        `;

                        tbody.appendChild(row);
                    } catch (rowError) {
                        console.error('Erro ao processar linha', index, ':', rowError, 'Item:', item);
                    }
                });

                // Atualizar título com estatísticas
                const titleElement = document.querySelector('.results-title');
                if (titleElement && data.estatisticas) {
                    const stats = data.estatisticas;
                    titleElement.innerHTML = `
                        <div>
                            <strong>Execução do PCA: ${stats.total_registros || 0} contratações</strong><br>
                            <small style="color: #6b7280;">
                                Total: ${stats.valor_total_formatado || 'R$ 0,00'} |
                                Em Atraso: ${stats.contadores.em_atraso}(${stats.percentuais_quantidade.em_atraso}%) |
                                Executado: ${stats.contadores.executado}(${stats.percentuais_quantidade.executado}%)
                            </small>
                        </div>
                    `;
                }

                console.log('Tabela PCA preenchida com sucesso!');

            } catch (error) {
                console.error('Erro na função preencherTabelaPCA:', error);
                const tbody = document.querySelector('#tabela-resultados tbody');
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px; color: #dc2626;">Erro ao exibir resultados. Verifique o console.</td></tr>';
                }
            }
        }

        // Preencher tabela com resultados
        function preencherTabela(data) {
            try {
                console.log('Preenchendo tabela com dados:', data);

                const tbody = document.querySelector('#tabela-resultados tbody');
                if (!tbody) {
                    throw new Error('Elemento tbody da tabela não encontrado');
                }

                tbody.innerHTML = ''; // Limpar tabela

                // Aceitar tanto data.resultados quanto array direto
                const resultados = Array.isArray(data) ? data : (data.resultados || []);

                if (resultados.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: #6b7280;">Nenhum resultado encontrado com os filtros aplicados</td></tr>';
                    return;
                }

                resultados.forEach((item, index) => {
                    try {
                        const row = document.createElement('tr');
                        
                        // Definir classe CSS para status
                        let statusClass = 'status-analise';
                        if (item.status && (item.status.includes('CONCLU') || item.status === 'CONCLUÍDO')) {
                            statusClass = 'status-concluido';
                        } else if (item.status && item.status.includes('ARQUIVADO')) {
                            statusClass = 'status-arquivado';
                        } else if (item.status && (item.status.includes('ANÁLISE') || item.status.includes('AN') && item.status.includes('LISE'))) {
                            statusClass = 'status-analise';
                        }
                        
                        // Formatar valor se não estiver formatado
                        let valorFormatado = item.valor_formatado || 'R$ 0,00';
                        if (!item.valor_formatado && item.valor_estimado) {
                            try {
                                valorFormatado = `R$ ${parseFloat(item.valor_estimado).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                            } catch (e) {
                                valorFormatado = 'R$ 0,00';
                            }
                        }
                        
                        row.innerHTML = `
                            <td>${item.nup || ''}</td>
                            <td>${item.area_demandante || ''}</td>
                            <td>${item.modalidade || ''}</td>
                            <td><span class="status-badge ${statusClass}">${item.status || ''}</span></td>
                            <td title="${item.objeto_resumo || ''}">${(item.objeto_resumo || '').substring(0, 80)}...</td>
                            <td class="valor">${valorFormatado}</td>
                        `;
                        
                        tbody.appendChild(row);
                    } catch (rowError) {
                        console.error('Erro ao processar linha', index, ':', rowError, 'Item:', item);
                    }
                });
                
                // Atualizar título com estatísticas
                const titleElement = document.querySelector('.results-title');
                if (titleElement && data.estatisticas) {
                    titleElement.innerHTML = `
                        Resultados: ${data.estatisticas.total_registros || 0} registros | Total: ${data.estatisticas.valor_total_formatado || 'R$ 0,00'}
                    `;
                }
                
                console.log('Tabela preenchida com sucesso!');
                
            } catch (error) {
                console.error('Erro na função preencherTabela:', error);
                const tbody = document.querySelector('#tabela-resultados tbody');
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: #dc2626;">Erro ao exibir resultados. Verifique o console.</td></tr>';
                }
            }
        }

        // Preencher tabela de licitações
        function preencherTabelaLicitacoes(data, resumo) {
            try {
                console.log('Preenchendo tabela de licitações com dados:', data, resumo);

                // Mostrar resumo primeiro
                if (resumo) {
                    mostrarResumoLicitacoes(resumo);
                }

                const tbody = document.querySelector('#tabela-resultados tbody');
                if (!tbody) {
                    throw new Error('Elemento tbody da tabela não encontrado');
                }

                tbody.innerHTML = ''; // Limpar tabela

                if (!data || data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 20px; color: #6b7280;">Nenhuma licitação encontrada com os filtros aplicados</td></tr>';
                    return;
                }

                data.forEach((licitacao, index) => {
                    try {
                        const row = document.createElement('tr');

                        // Definir classes CSS para situação
                        let situacaoClass = '';
                        const situacaoOriginal = licitacao.situacao || 'NÃO INFORMADO';

                        // Formatar texto do status para exibição
                        let situacaoTexto = situacaoOriginal;
                        switch (situacaoOriginal.toUpperCase()) {
                            case 'EM_ANDAMENTO':
                                situacaoTexto = 'EM ANDAMENTO';
                                break;
                            case 'PREPARACAO':
                                situacaoTexto = 'PREPARAÇÃO';
                                break;
                            case 'INEXIGIBILIDADE':
                                situacaoTexto = 'INEXIGIBILIDADE';
                                break;
                        }

                        switch (situacaoOriginal.toUpperCase()) {
                            case 'HOMOLOGADO':
                                situacaoClass = 'status-homologado';
                                break;
                            case 'EM_ANDAMENTO':
                                situacaoClass = 'status-andamento';
                                break;
                            case 'REVOGADO':
                            case 'CANCELADO':
                            case 'FRACASSADO':
                                situacaoClass = 'status-cancelado';
                                break;
                            case 'PREPARACAO':
                                situacaoClass = 'status-planejamento';
                                break;
                            default:
                                situacaoClass = 'status-planejamento';
                        }

                        // Calcular economia APENAS para licitações HOMOLOGADAS
                        const valorEstimado = parseFloat(licitacao.valor_estimado || 0);
                        const valorHomologado = parseFloat(licitacao.valor_homologado || 0);

                        let economia = 0;
                        let economiaPercentual = 0;
                        let economiaTexto = 'N/A';

                        if (situacaoOriginal.toUpperCase() === 'HOMOLOGADO' && valorEstimado > 0 && valorHomologado > 0) {
                            economia = valorEstimado - valorHomologado;
                            economiaPercentual = ((economia / valorEstimado) * 100);
                            economiaTexto = `R$ ${economia.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
                            economiaTexto += `<small style="display: block; font-size: 11px;">(${economiaPercentual.toFixed(1)}%)</small>`;
                        } else {
                            economiaTexto = '<span style="color: #6b7280; font-style: italic;">Não aplicável</span>';
                        }

                        row.innerHTML = `
                            <td>${index + 1}</td>
                            <td style="font-weight: 600;">${licitacao.numero_processo || 'N/A'}</td>
                            <td>${licitacao.objeto || 'N/A'}</td>
                            <td style="text-align: center;">${licitacao.modalidade || 'N/A'}</td>
                            <td style="text-align: center;">
                                <span class="status-badge ${situacaoClass}">${situacaoTexto}</span>
                            </td>
                            <td style="text-align: center;">${licitacao.pregoeiro || 'N/A'}</td>
                            <td style="text-align: center;">${licitacao.data_abertura_formatada || 'N/A'}</td>
                            <td style="text-align: right; font-weight: 600;">
                                R$ ${valorEstimado.toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                            </td>
                            <td style="text-align: right; color: ${economia > 0 ? '#16a34a' : '#6b7280'}; font-weight: 600;">
                                ${economiaTexto}
                            </td>
                        `;

                        tbody.appendChild(row);

                    } catch (itemError) {
                        console.error(`Erro ao processar licitação ${index}:`, itemError, licitacao);
                    }
                });

                console.log('Tabela de licitações preenchida com sucesso!');

            } catch (error) {
                console.error('Erro na função preencherTabelaLicitacoes:', error);
                const tbody = document.querySelector('#tabela-resultados tbody');
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 20px; color: #dc2626;">Erro ao exibir resultados. Verifique o console.</td></tr>';
                }
            }
        }

        // Mostrar resumo das licitações
        function mostrarResumoLicitacoes(resumo) {
            const resultadosContainer = document.getElementById('results');
            if (!resultadosContainer) return;

            // Procurar ou criar área de resumo
            let resumoDiv = resultadosContainer.querySelector('.resumo-licitacoes');
            if (!resumoDiv) {
                resumoDiv = document.createElement('div');
                resumoDiv.className = 'resumo-licitacoes';
                resultadosContainer.insertBefore(resumoDiv, resultadosContainer.firstChild);
            }

            const economiaTotal = resumo.economia_total || 0;
            const economiaPercentual = resumo.economia_percentual || 0;
            const valorTotalEstimado = resumo.valor_total_estimado || 0;
            const totalHomologadas = resumo.total_homologadas || 0;
            const valorEstimadoHomologadas = resumo.valor_estimado_homologadas || 0;
            const valorTotalHomologado = resumo.valor_total_homologado || 0;

            resumoDiv.innerHTML = `
                <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #3b82f6;">
                    <h4 style="margin: 0 0 15px 0; color: #1e293b; display: flex; align-items: center;">
                        <i data-lucide="bar-chart-3" style="width: 20px; height: 20px; margin-right: 8px;"></i>
                        Resumo dos Resultados
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
                        <div style="text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: #3b82f6;">${resumo.total_encontradas || 0}</div>
                            <div style="color: #64748b; font-size: 14px;">Total de Licitações</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 20px; font-weight: bold; color: #16a34a;">${totalHomologadas}</div>
                            <div style="color: #64748b; font-size: 14px;">Licitações Homologadas</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 16px; font-weight: bold; color: #1e293b;">
                                R$ ${valorTotalEstimado.toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                            </div>
                            <div style="color: #64748b; font-size: 14px;">Valor Total Estimado</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 16px; font-weight: bold; color: #16a34a;">
                                R$ ${valorTotalHomologado.toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                            </div>
                            <div style="color: #64748b; font-size: 14px;">Valor Homologado</div>
                        </div>
                        <div style="text-align: center; border-left: 2px solid #16a34a; padding-left: 10px;">
                            <div style="font-size: 18px; font-weight: bold; color: ${economiaTotal > 0 ? '#16a34a' : '#6b7280'};">
                                R$ ${economiaTotal.toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                            </div>
                            <div style="color: #64748b; font-size: 13px;">
                                Economia Realizada<br>
                                <small style="color: #16a34a; font-weight: 600;">(${economiaPercentual.toFixed(1)}% das homologadas)</small>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 15px; padding: 10px; background: #fff7ed; border-radius: 6px; border-left: 3px solid #f59e0b;">
                        <small style="color: #92400e; font-weight: 500;">
                            <i data-lucide="info" style="width: 14px; height: 14px; margin-right: 4px;"></i>
                            A economia é calculada apenas para licitações com status HOMOLOGADO, representando a diferença entre o valor estimado inicial e o valor efetivamente homologado.
                        </small>
                    </div>
                </div>
            `;

            // Recriar ícones
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        // Limpar todos os filtros
        function limparFiltros() {
            document.getElementById('data_inicio').value = '';
            document.getElementById('data_fim').value = '';
            document.getElementById('preset_periodo').value = '';

            // Campos genéricos
            const area_filtro = document.getElementById('area_filtro');
            if (area_filtro) area_filtro.value = '';

            const status_filtro = document.getElementById('status_filtro');
            if (status_filtro) status_filtro.value = '';

            const modalidade_filtro = document.getElementById('modalidade_filtro');
            if (modalidade_filtro) modalidade_filtro.value = '';

            // Campos específicos do módulo licitação
            const pregoeiro_filtro = document.getElementById('pregoeiro_filtro');
            if (pregoeiro_filtro) pregoeiro_filtro.value = '';

            // Campos de valores
            document.getElementById('valor_minimo').value = '';
            document.getElementById('valor_maximo').value = '';

            // Campos específicos do módulo planejamento
            const area_requisitante_filtro = document.getElementById('area_requisitante_filtro');
            if (area_requisitante_filtro) area_requisitante_filtro.value = '';

            const situacao_original_filtro = document.getElementById('situacao_original_filtro');
            if (situacao_original_filtro) situacao_original_filtro.value = '';

            const tem_licitacao_filtro = document.getElementById('tem_licitacao_filtro');
            if (tem_licitacao_filtro) tem_licitacao_filtro.value = '';

            console.log('Filtros limpos com sucesso');
        }
        
        // Exportar relatório
        function exportarRelatorio(formato) {
            alert('Exportando para ' + formato.toUpperCase() + '...');
        }
        
        // Gerar relatório final
        function gerarRelatorioFinal() {
            const formato = document.getElementById('formato_principal').value;
            alert('Gerando relatório final em formato ' + formato.toUpperCase() + '...');
        }
        
        // Gerar Dashboard Executivo
        function gerarDashboardExecutivo() {
            console.log('🚀 Carregando Dashboard Executivo...');

            // Mostrar loading
            const container = document.getElementById('dashboard-executivo-container');
            const botao = event.target;

            if (botao) {
                botao.disabled = true;
                botao.innerHTML = '<i data-lucide="loader-2" class="animate-spin" style="width: 16px; height: 16px;"></i> Carregando...';
            }

            // Debug completo do elemento select
            const selectElement = document.getElementById('ano-dashboard-executivo');
            console.log('🔍 Select element encontrado:', selectElement);
            console.log('🔍 Select.value:', selectElement ? selectElement.value : 'ELEMENTO NÃO ENCONTRADO');
            console.log('🔍 Select.selectedIndex:', selectElement ? selectElement.selectedIndex : 'N/A');
            console.log('🔍 Select.options:', selectElement ? Array.from(selectElement.options).map(o => o.value + ' - ' + o.text) : 'N/A');

            // Obter ano selecionado
            const anoSelecionado = selectElement?.value || '2025';
            console.log('🔍 Ano final usado:', anoSelecionado);

            // Buscar dados do servidor
            const bodyData = `acao=dashboard_executivo_geral&ano=${anoSelecionado}`;
            console.log('📤 Body completo enviado:', bodyData);

            fetch('process_relatorios.php?v=' + Date.now(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: bodyData
            })
            .then(response => {
                console.log('📡 Response status:', response.status);
                console.log('📡 Response URL:', response.url);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text();
            })
            .then(text => {
                console.log('📄 Resposta como texto (primeiros 500 chars):', text.substring(0, 500));
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('❌ Erro ao parsear JSON:', e);
                    console.error('📄 Texto completo:', text);
                    throw new Error('Resposta não é JSON válido: ' + text.substring(0, 100));
                }
            })
            .then(data => {
                console.log('📥 Dados recebidos:', data);

                if (data.success) {
                    // Exibir o dashboard
                    container.style.display = 'block';

                    try {
                        // Atualizar KPIs
                        atualizarKPIsDashboard(data);

                        // Criar gráficos
                        criarGraficoPlanejadavsExecutadas(data);
                        criarGraficoTaxaExecucao(data);

                        // Atualizar título com ano filtrado
                        atualizarTituloDashboard(data.ano_filtro, data.filtrar_por_ano);

                        // Scroll suave para o dashboard
                        container.scrollIntoView({ behavior: 'smooth', block: 'start' });

                        console.log('✅ Dashboard Executivo carregado com sucesso!');
                    } catch (renderError) {
                        console.error('❌ Erro ao renderizar dashboard:', renderError);
                        alert('Erro ao renderizar dashboard: ' + renderError.message);
                    }
                } else {
                    throw new Error(data.message || 'Erro desconhecido');
                }
            })
            .catch(error => {
                console.error('❌ Erro ao carregar Dashboard Executivo:', error);
                alert('Erro ao carregar Dashboard Executivo: ' + error.message);
            })
            .finally(() => {
                // Restaurar botão
                if (botao) {
                    botao.disabled = false;
                    botao.innerHTML = '<i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i> Atualizar';
                }
                lucide.createIcons();
            });
        }

        // Atualizar Dashboard Executivo quando ano mudar
        function atualizarDashboardExecutivo() {
            // Usar setTimeout para garantir que a mudança do select foi processada
            setTimeout(() => {
                const container = document.getElementById('dashboard-executivo-container');
                const select = document.getElementById('ano-dashboard-executivo');

                console.log('🔄 === atualizarDashboardExecutivo() ===');
                console.log('🔄 Select element:', select);
                console.log('🔄 Select value:', select ? select.value : 'undefined');
                console.log('🔄 Container display:', container ? container.style.display : 'undefined');

                if (container && container.style.display !== 'none') {
                    console.log('🔄 Chamando gerarDashboardExecutivo...');
                    gerarDashboardExecutivo();
                } else {
                    console.log('⚠️ Container não visível, não atualizando');
                }
            }, 100); // 100ms para garantir que o evento onchange foi processado
        }

        // Atualizar KPIs do Dashboard
        function atualizarKPIsDashboard(data) {
            console.log('📊 Atualizando KPIs com dados:', data);

            // Calcular totais
            let totalPlanejadas = 0;
            let totalExecutadas = 0;
            let totalHomologadas = 0;

            // Verificar se dados existem e somar planejadas
            if (data.dados_planejadas && Array.isArray(data.dados_planejadas)) {
                data.dados_planejadas.forEach(item => {
                    totalPlanejadas += parseInt(item.total_planejadas || 0);
                });
            }

            // Verificar se dados existem e somar executadas
            if (data.dados_executadas && Array.isArray(data.dados_executadas)) {
                data.dados_executadas.forEach(item => {
                    totalExecutadas += parseInt(item.total_executadas || 0);
                    totalHomologadas += parseInt(item.homologadas || 0);
                });
            }

            // Calcular taxa de execução geral
            const taxaExecucao = totalPlanejadas > 0 ? ((totalExecutadas / totalPlanejadas) * 100).toFixed(1) : '0.0';

            // Atualizar elementos com verificação de existência
            const elementos = {
                'total-planejadas-dash': totalPlanejadas.toLocaleString('pt-BR'),
                'total-executadas-dash': totalExecutadas.toLocaleString('pt-BR'),
                'taxa-execucao-dash': taxaExecucao,
                'total-homologadas-dash': totalHomologadas.toLocaleString('pt-BR')
            };

            for (const [id, valor] of Object.entries(elementos)) {
                const elemento = document.getElementById(id);
                console.log(`🎯 Atualizando ${id}:`, elemento ? 'ENCONTRADO' : 'NÃO ENCONTRADO', '| Valor:', valor);
                if (elemento) {
                    const valorAnterior = elemento.textContent;
                    elemento.textContent = valor;
                    console.log(`✅ ${id}: "${valorAnterior}" → "${valor}"`);

                    // Destacar visualmente que mudou
                    elemento.style.backgroundColor = '#ffffcc';
                    setTimeout(() => {
                        elemento.style.backgroundColor = '';
                    }, 2000);
                } else {
                    console.warn(`⚠️ Elemento ${id} não encontrado`);
                }
            }

            // Atualizar labels contextuais
            const anoFiltro = data.ano_filtro || '2025';
            const labelPlanejadas = document.getElementById('label-planejadas');
            const labelExecutadas = document.getElementById('label-executadas');

            console.log('🏷️ Atualizando labels...');
            console.log('🏷️ labelPlanejadas:', labelPlanejadas ? 'ENCONTRADO' : 'NÃO ENCONTRADO');
            console.log('🏷️ labelExecutadas:', labelExecutadas ? 'ENCONTRADO' : 'NÃO ENCONTRADO');

            if (labelPlanejadas) {
                const novoTexto = data.filtrar_por_ano ? `PCA ${anoFiltro}` : 'PCA (todos anos)';
                console.log('🏷️ Label planejadas:', labelPlanejadas.textContent, '→', novoTexto);
                labelPlanejadas.textContent = novoTexto;
                labelPlanejadas.style.backgroundColor = '#e7f3ff';
                setTimeout(() => labelPlanejadas.style.backgroundColor = '', 2000);
            }
            if (labelExecutadas) {
                const novoTexto = data.filtrar_por_ano ? `Licitações ${anoFiltro}` : 'Licitações (todos anos)';
                console.log('🏷️ Label executadas:', labelExecutadas.textContent, '→', novoTexto);
                labelExecutadas.textContent = novoTexto;
                labelExecutadas.style.backgroundColor = '#e7f3ff';
                setTimeout(() => labelExecutadas.style.backgroundColor = '', 2000);
            }

            console.log('✅ KPIs atualizados:', { totalPlanejadas, totalExecutadas, taxaExecucao, totalHomologadas, ano: anoFiltro });
        }

        // Criar gráfico Planejadas vs Executadas
        function criarGraficoPlanejadavsExecutadas(data) {
            const ctx = document.getElementById('grafico-planejadas-vs-executadas');

            if (!ctx) {
                console.warn('⚠️ Canvas do gráfico não encontrado');
                return;
            }

            // Destruir gráfico existente se houver
            if (window.graficoPlanejadavsExecutadas) {
                window.graficoPlanejadavsExecutadas.destroy();
            }

            // Preparar dados com verificação de arrays
            const anos = [];
            const planejadas = [];
            const executadas = [];

            // Combinar dados por ano
            const anosCombinados = {};

            // Verificar se dados de planejadas existem
            if (data.dados_planejadas && Array.isArray(data.dados_planejadas)) {
                data.dados_planejadas.forEach(item => {
                    anosCombinados[item.ano] = {
                        planejadas: parseInt(item.total_planejadas || 0),
                        executadas: 0
                    };
                });
            }

            // Verificar se dados de executadas existem
            if (data.dados_executadas && Array.isArray(data.dados_executadas)) {
                data.dados_executadas.forEach(item => {
                    if (!anosCombinados[item.ano]) {
                        anosCombinados[item.ano] = { planejadas: 0, executadas: 0 };
                    }
                    anosCombinados[item.ano].executadas = parseInt(item.total_executadas || 0);
                });
            }

            // Se não há dados combinados, mostrar gráfico vazio com o ano filtrado
            if (Object.keys(anosCombinados).length === 0) {
                const anoFiltro = data.ano_filtro || new Date().getFullYear();
                anosCombinados[anoFiltro] = { planejadas: 0, executadas: 0 };
            }

            // Ordenar por ano e preparar arrays
            Object.keys(anosCombinados).sort().forEach(ano => {
                anos.push(ano);
                planejadas.push(anosCombinados[ano].planejadas);
                executadas.push(anosCombinados[ano].executadas);
            });

            window.graficoPlanejadavsExecutadas = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: anos,
                    datasets: [
                        {
                            label: 'Planejadas (PCA)',
                            data: planejadas,
                            backgroundColor: 'rgba(30, 60, 114, 0.8)',
                            borderColor: 'rgba(30, 60, 114, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Executadas (Licitações)',
                            data: executadas,
                            backgroundColor: 'rgba(5, 150, 105, 0.8)',
                            borderColor: 'rgba(5, 150, 105, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y.toLocaleString('pt-BR') + ' contratações';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString('pt-BR');
                                }
                            }
                        }
                    }
                }
            });

            console.log('📈 Gráfico Planejadas vs Executadas criado');
        }

        // Criar gráfico Taxa de Execução
        function criarGraficoTaxaExecucao(data) {
            const ctx = document.getElementById('grafico-taxa-execucao');

            if (!ctx) {
                console.warn('⚠️ Canvas da taxa de execução não encontrado');
                return;
            }

            if (window.graficoTaxaExecucao) {
                window.graficoTaxaExecucao.destroy();
            }

            const anos = [];
            const taxas = [];

            // Verificar se dados de taxa de execução existem
            if (data.taxa_execucao && Array.isArray(data.taxa_execucao) && data.taxa_execucao.length > 0) {
                data.taxa_execucao.forEach(item => {
                    anos.push(item.ano);
                    taxas.push(parseFloat(item.taxa_execucao_pct || 0));
                });
            } else {
                // Se não há dados, mostrar ano filtrado com 0%
                const anoFiltro = data.ano_filtro || new Date().getFullYear();
                anos.push(anoFiltro);
                taxas.push(0);
            }

            window.graficoTaxaExecucao = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: anos,
                    datasets: [{
                        label: 'Taxa de Execução (%)',
                        data: taxas,
                        backgroundColor: 'rgba(5, 150, 105, 0.2)',
                        borderColor: 'rgba(5, 150, 105, 1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(5, 150, 105, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Taxa de Execução: ' + context.parsed.y.toFixed(1) + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });

            console.log('📊 Gráfico Taxa de Execução criado');
        }

        // Função de debug do select
        function debugSelect() {
            console.clear();
            console.log('🔍 === DEBUG SELECT ANO ===');

            const selectElement = document.getElementById('ano-dashboard-executivo');
            console.log('Select element:', selectElement);

            if (selectElement) {
                console.log('Select.value:', selectElement.value);
                console.log('Select.selectedIndex:', selectElement.selectedIndex);
                console.log('Select.selectedOptions:', selectElement.selectedOptions);
                console.log('Todas as options:');
                for (let i = 0; i < selectElement.options.length; i++) {
                    const option = selectElement.options[i];
                    console.log(`  ${i}: ${option.value} - ${option.text} - selected: ${option.selected}`);
                }
            } else {
                console.error('❌ Select não encontrado!');
            }

            // Testar outros elementos com IDs similares
            const allSelects = document.querySelectorAll('select');
            console.log('📋 Todos os selects na página:', allSelects.length);
            allSelects.forEach((select, index) => {
                console.log(`Select ${index}: id="${select.id}", name="${select.name}", value="${select.value}"`);
            });

            alert('Debug executado - veja o console do navegador!');
        }

        // Forçar atualização com ano específico
        function forcarAtualizacao() {
            const selectElement = document.getElementById('ano-dashboard-executivo');
            const anoSelecionado = selectElement ? selectElement.value : '2025';

            console.log('🔄 === FORÇANDO ATUALIZAÇÃO ===');
            console.log('🔄 Ano do select:', anoSelecionado);

            // Fazer chamada direta da API com o ano selecionado
            const bodyData = `acao=dashboard_executivo_geral&ano=${anoSelecionado}&timestamp=${Date.now()}`;
            console.log('📤 Enviando (forçado):', bodyData);

            fetch('process_relatorios.php?v=' + Date.now(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: bodyData
            })
            .then(response => {
                console.log('📡 Response URL (forçada):', response.url);
                return response.text();
            })
            .then(text => {
                console.log('📄 Resposta texto (forçada):', text.substring(0, 200));
                return JSON.parse(text);
            })
            .then(data => {
                console.log('📥 Resposta da API (forçada):', data);

                if (data.success) {
                    console.log('✅ Ano da resposta:', data.ano_filtro);
                    console.log('✅ Filtrar por ano:', data.filtrar_por_ano);
                    console.log('✅ Dados planejadas:', data.dados_planejadas?.length || 0);
                    console.log('✅ Dados executadas:', data.dados_executadas?.length || 0);

                    // Atualizar interface
                    atualizarKPIsDashboard(data);
                    criarGraficoPlanejadavsExecutadas(data);
                    criarGraficoTaxaExecucao(data);
                    atualizarTituloDashboard(data.ano_filtro, data.filtrar_por_ano);

                    alert(`✅ Forçado! Ano: ${data.ano_filtro} | Planejadas: ${data.dados_planejadas?.length || 0} | Executadas: ${data.dados_executadas?.length || 0}`);
                } else {
                    alert('❌ Erro: ' + (data.message || 'Desconhecido'));
                }
            })
            .catch(error => {
                console.error('❌ Erro na atualização forçada:', error);
                alert('❌ Erro: ' + error.message);
            });
        }

        // Testar atualização manual dos cards
        function testarCards() {
            console.clear();
            console.log('🧪 === TESTE MANUAL DOS CARDS ===');

            const elementos = [
                'total-planejadas-dash',
                'total-executadas-dash',
                'taxa-execucao-dash',
                'total-homologadas-dash',
                'label-planejadas',
                'label-executadas'
            ];

            elementos.forEach(id => {
                const elemento = document.getElementById(id);
                console.log(`🎯 ${id}:`, elemento ? 'ENCONTRADO' : 'NÃO ENCONTRADO');

                if (elemento) {
                    console.log(`   Valor atual: "${elemento.textContent}"`);
                    console.log(`   Tipo: ${elemento.tagName}`);
                    console.log(`   Pai: ${elemento.parentElement?.tagName}`);

                    // Teste de atualização visual
                    const valorTeste = `TESTE_${Date.now()}`;
                    elemento.textContent = valorTeste;
                    elemento.style.backgroundColor = '#ff6b6b';
                    elemento.style.color = 'white';
                    elemento.style.fontWeight = 'bold';

                    console.log(`   ✅ Atualizado para: "${valorTeste}"`);

                    // Restaurar após 3 segundos
                    setTimeout(() => {
                        elemento.style.backgroundColor = '';
                        elemento.style.color = '';
                        elemento.style.fontWeight = '';
                        console.log(`   🔄 ${id} restaurado`);
                    }, 3000);
                } else {
                    console.error(`   ❌ Elemento ${id} não encontrado no DOM!`);
                }
            });

            alert('🧪 Teste executado! Os cards devem piscar em vermelho por 3 segundos se estiverem funcionando.');
        }

        // Atualizar título do dashboard com filtro de ano
        function atualizarTituloDashboard(ano, filtrarPorAno) {
            const titulo = document.querySelector('#dashboard-executivo-container h3.chart-title');
            const subtitulo = document.querySelector('#dashboard-executivo-container p');

            if (titulo) {
                if (filtrarPorAno) {
                    titulo.innerHTML = '<i data-lucide="bar-chart-4"></i> Dashboard Executivo - Ano ' + ano;
                    if (subtitulo) {
                        subtitulo.textContent = `Dados específicos do ano ${ano}: Contratações Planejadas vs Executadas`;
                    }
                } else {
                    titulo.innerHTML = '<i data-lucide="bar-chart-4"></i> Dashboard Executivo - Todos os Anos';
                    if (subtitulo) {
                        subtitulo.textContent = 'Visão histórica consolidada: Contratações Planejadas vs Executadas (2022-2026)';
                    }
                }
                lucide.createIcons();
            }
        }

        // Atualizar Dashboard por Ano
        function atualizarDashboardPorAno() {
            console.log('Ano do PCA alterado, atualizando dashboard...');
            carregarAreasRequisitantes(); // Carregar áreas do ano selecionado
            carregarDashboardPCA();
        }

        // Carregar áreas requisitantes dinamicamente
        function carregarAreasRequisitantes() {
            const anoSelecionado = document.getElementById('ano_pca_header')?.value || new Date().getFullYear();
            const selectArea = document.getElementById('area_requisitante_filtro');

            if (!selectArea) return;

            console.log('Carregando áreas requisitantes para o ano:', anoSelecionado);

            // Mostrar loading
            selectArea.innerHTML = '<option value="">🔄 Carregando áreas...</option>';
            selectArea.disabled = true;

            fetch(`api/get_areas_requisitantes.php?ano=${anoSelecionado}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Limpar e adicionar opção padrão
                        selectArea.innerHTML = '<option value="">Todas as Áreas</option>';

                        // Adicionar áreas carregadas
                        data.areas.forEach(area => {
                            const option = document.createElement('option');
                            option.value = area.codigo;
                            option.textContent = `${area.nome} (${area.quantidade_contratacoes} contratações)`;
                            selectArea.appendChild(option);
                        });

                        console.log(`✅ ${data.total_areas} áreas carregadas para ${data.ano}`);
                    } else {
                        selectArea.innerHTML = '<option value="">❌ Erro ao carregar áreas</option>';
                        console.error('Erro ao carregar áreas:', data.message);
                    }
                })
                .catch(error => {
                    selectArea.innerHTML = '<option value="">❌ Erro na conexão</option>';
                    console.error('Erro na requisição:', error);
                })
                .finally(() => {
                    selectArea.disabled = false;
                });
        }

        // Carregar Dashboard Executivo PCA
        function carregarDashboardPCA() {
            console.log('Carregando dashboard executivo PCA...');

            // Mostrar dashboard
            const dashboard = document.getElementById('dashboard-pca');
            if (dashboard) {
                dashboard.style.display = 'grid';
            }

            // Obter ano selecionado do campo (padrão: 2025)
            const anoSelecionado = document.getElementById('ano_pca_header') ? document.getElementById('ano_pca_header').value : '2025';

            // Buscar dados do servidor (CORRIGIDO - envia ano selecionado)
            fetch('process_relatorios.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `acao=dashboard_executivo_pca&ano=${anoSelecionado}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.estatisticas) {
                    const stats = data.estatisticas;

                    // Atualizar cards combinados do dashboard
                    document.getElementById('total-contratacoes').textContent = stats.total_contratacoes || '0';
                    document.getElementById('percentual-licitados').textContent = (stats.percentual_licitados || '0') + '%';
                    document.getElementById('valor-total-pca').textContent = stats.valor_total_formatado || 'R$ 0,00';

                    // Card de Contratações Aprovadas
                    document.getElementById('total-aprovadas').textContent = stats.total_aprovadas || '0';
                    document.getElementById('valor-aprovadas').textContent = stats.valor_aprovadas_formatado || 'R$ 0,00';

                    // Cards combinados com valor por status
                    document.getElementById('total-atraso').textContent = stats.total_atraso || '0';
                    document.getElementById('valor-atraso').textContent = stats.valor_atraso_formatado || 'R$ 0,00';

                    document.getElementById('total-preparacao').textContent = stats.total_preparacao || '0';
                    document.getElementById('valor-preparacao').textContent = stats.valor_preparacao_formatado || 'R$ 0,00';

                    // Card de Contratações Encerradas (novo KPI)
                    document.getElementById('total-encerradas').textContent = stats.total_encerradas || '0';
                    document.getElementById('valor-encerradas').textContent = stats.valor_encerradas_formatado || 'R$ 0,00';

                    console.log('Dashboard PCA atualizado com sucesso:', stats);
                } else {
                    console.error('Erro ao carregar estatísticas:', data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao carregar dashboard PCA:', error);
            });
        }

        // Dashboard Qualificações
        function carregarDashboardQualificacoes() {
            console.log('🚀 Carregando dashboard das qualificações...');

            // Buscar dados do servidor
            fetch('process_relatorios.php?v=' + Date.now(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'acao=dashboard_executivo_qualificacoes'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('📥 Dados qualificações recebidos:', data);
                if (data.success && data.estatisticas) {
                    const stats = data.estatisticas;
                    console.log('📊 Estatísticas qualificações:', stats);

                    // Atualizar cards do dashboard de qualificações
                    document.getElementById('total-processos-qual').textContent = stats.total_qualificacoes || '0';

                    // Formatar valor total
                    const valorTotal = parseFloat(stats.valor_total || 0);
                    document.getElementById('valor-total-qual').textContent = valorTotal.toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL'
                    });

                    // Cards por status
                    document.getElementById('total-em-analise').textContent = stats.total_analise || '0';

                    // Calcular valor em análise dos dados_status
                    const statusAnalise = data.dados_status?.find(s => s.status.includes('ANÁLISE'));
                    const valorAnalise = parseFloat(statusAnalise?.valor_total || 0);
                    document.getElementById('valor-em-analise').textContent = valorAnalise.toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL'
                    });

                    document.getElementById('total-concluidos').textContent = stats.total_concluidas || '0';

                    const statusConcluido = data.dados_status?.find(s => s.status.includes('CONCLU'));
                    const valorConcluido = parseFloat(statusConcluido?.valor_total || 0);
                    document.getElementById('valor-concluidos').textContent = valorConcluido.toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL'
                    });

                    document.getElementById('total-arquivados').textContent = stats.total_arquivadas || '0';

                    const statusArquivado = data.dados_status?.find(s => s.status === 'ARQUIVADO');
                    const valorArquivado = parseFloat(statusArquivado?.valor_total || 0);
                    document.getElementById('valor-arquivados').textContent = valorArquivado.toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL'
                    });

                    console.log('Dashboard Qualificações atualizado com sucesso:', stats);
                } else {
                    console.error('Erro ao carregar estatísticas:', data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao carregar dashboard Qualificações:', error);
            });
        }

        // Dashboard Licitações
        function carregarDashboardLicitacoes() {
            console.log('Carregando dashboard das licitações...');

            // Buscar dados do servidor
            fetch('process_relatorios.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'acao=dashboard_executivo_licitacoes'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.estatisticas) {
                    const stats = data.estatisticas;

                    // Atualizar cards do dashboard de licitações
                    document.getElementById('total-licitacoes').textContent = stats.total_licitacoes || '0';
                    document.getElementById('valor-total-licitacoes').textContent = stats.valor_total_formatado || 'R$ 0,00';

                    // Cards por situação
                    document.getElementById('total-homologadas').textContent = stats.total_homologadas || '0';
                    document.getElementById('valor-homologadas').textContent = stats.valor_homologadas_formatado || 'R$ 0,00';

                    document.getElementById('total-andamento').textContent = stats.total_andamento || '0';
                    document.getElementById('valor-andamento').textContent = stats.valor_andamento_formatado || 'R$ 0,00';

                    document.getElementById('total-canceladas').textContent = stats.total_canceladas || '0';
                    document.getElementById('valor-canceladas').textContent = stats.valor_canceladas_formatado || 'R$ 0,00';

                    // Card especial de economia
                    document.getElementById('economia-total').textContent = stats.economia_total_formatado || 'R$ 0,00';
                    document.getElementById('economia-percentual').textContent = stats.economia_percentual || '0%';

                    console.log('Dashboard Licitações atualizado com sucesso:', stats);
                } else {
                    console.error('Erro ao carregar estatísticas:', data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao carregar dashboard Licitações:', error);
            });
        }

        // Carregar pregoeiros disponíveis
        function carregarPregoeiros() {
            console.log('Carregando pregoeiros...');

            fetch('process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'acao=carregar_pregoeiros'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.pregoeiros) {
                    const selectPregoeiro = document.getElementById('pregoeiro_filtro');
                    if (selectPregoeiro) {
                        // Limpar opções existentes (exceto a primeira)
                        selectPregoeiro.innerHTML = '<option value="">Todos os Pregoeiros</option>';

                        // Adicionar pregoeiros
                        data.pregoeiros.forEach(pregoeiro => {
                            const option = document.createElement('option');
                            option.value = pregoeiro;
                            option.textContent = pregoeiro;
                            selectPregoeiro.appendChild(option);
                        });

                        console.log(`${data.pregoeiros.length} pregoeiros carregados`);
                    }
                } else {
                    console.error('Erro ao carregar pregoeiros:', data.error);
                }
            })
            .catch(error => {
                console.error('Erro ao carregar pregoeiros:', error);
            });
        }

        // Relatórios de Planejamento
        function gerarRelatorioPlanejamento(tipo) {
            const titulos = {
                'status': 'Relatório por Status do PCA',
                'area': 'Relatório por Área Demandante',
                'prazo': 'Relatório de Prazos',
                'financeiro': 'Relatório Financeiro'
            };

            alert(`${titulos[tipo]}\n\nSistema em desenvolvimento - Funcionalidades:\n\n• Filtros avançados por período\n• Análise de situação de execução\n• Exportação em múltiplos formatos\n• Gráficos estatísticos interativos\n\nImplementação: Próxima versão`);
        }

        // Relatórios de Licitação
        function gerarRelatorioLicitacao(tipo) {
            const titulos = {
                'modalidade': 'Relatório por Modalidade',
                'pregoeiro': 'Relatório por Pregoeiro',
                'tempo': 'Relatório de Prazos',
                'economia': 'Relatório de Economia'
            };

            alert(`${titulos[tipo]}\n\nSistema em desenvolvimento - Funcionalidades:\n\n• Performance detalhada por categoria\n• Análise de tempos e gargalos\n• Cálculo de economia gerada\n• Comparativos históricos\n\nImplementação: Próxima versão`);
        }

        // Relatórios de Contratos
        function gerarRelatorioContrato(tipo) {
            const titulos = {
                'status': 'Relatório por Status',
                'vigencia': 'Relatório de Vigências',
                'fornecedor': 'Relatório por Fornecedor',
                'valor': 'Relatório Financeiro'
            };

            alert(`${titulos[tipo]}\n\nSistema em desenvolvimento - Funcionalidades:\n\n• Controle de vigências e renovações\n• Análise de performance de fornecedores\n• Execução orçamentária e financeira\n• Alertas de vencimentos\n\nImplementação: Próxima versão`);
        }

        // Inicializar página
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($modulo_ativo == 'qualificacao'): ?>
            carregarAreas();
            carregarDashboardQualificacoes();
            <?php elseif ($modulo_ativo == 'planejamento'): ?>
            carregarAreasRequisitantes(); // Carregar áreas na inicialização
            carregarDashboardPCA();
            <?php elseif ($modulo_ativo == 'licitacao'): ?>
            carregarDashboardLicitacoes();
            carregarPregoeiros();
            <?php endif; ?>
            lucide.createIcons();
        });
    </script>
</body>
</html>