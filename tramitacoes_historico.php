<?php
require_once 'config.php';
require_once 'functions.php';

verificarLogin();

$pdo = conectarDB();
$usuario_nivel = $_SESSION['usuario_nivel'];

// Verificar permissões - todos podem visualizar o histórico
// Aplicar filtros baseados no nível do usuário
$where_conditions = ["1=1"];
$params = [];

if ($usuario_nivel == 2) { // DIPLAN - apenas planejamento
    $where_conditions[] = "(t.modulo_origem = 'PLANEJAMENTO' OR t.modulo_destino = 'PLANEJAMENTO')";
} elseif ($usuario_nivel == 3) { // DIPLI - licitação, qualificação, contratos
    $where_conditions[] = "(t.modulo_origem IN ('LICITACAO','QUALIFICACAO','CONTRATOS') OR t.modulo_destino IN ('LICITACAO','QUALIFICACAO','CONTRATOS'))";
}

// Filtro por NUP específico (vindos da tela de qualificação)
$filtro_nup = $_GET['filtro_nup'] ?? '';
$where_clause_main = implode(' AND ', $where_conditions);
$where_clause_stats = implode(' AND ', $where_conditions);
$params_main = $params;
$params_stats = $params;

if (!empty($filtro_nup)) {
    // Para query principal (com JOIN)
    $where_conditions_main = $where_conditions;
    $where_conditions_main[] = "(q.nup = ? OR t.nup_vinculado = ?)";
    $params_main[] = $filtro_nup;
    $params_main[] = $filtro_nup;
    $where_clause_main = implode(' AND ', $where_conditions_main);
    
    // Para query de estatísticas (sem JOIN)
    $where_conditions_stats = $where_conditions;
    $where_conditions_stats[] = "t.nup_vinculado = ?";
    $params_stats[] = $filtro_nup;
    $where_clause_stats = implode(' AND ', $where_conditions_stats);
}

// Buscar tramitações completas com dados de qualificação
$query = "
    SELECT 
        t.*,
        u_criador.nome as nome_criador,
        u_responsavel.nome as nome_responsavel,
        q.nup,
        q.objeto,
        q.area_demandante,
        q.responsavel as responsavel_qualificacao,
        q.valor_estimado,
        q.status as status_qualificacao
    FROM tramitacoes_kanban t
    LEFT JOIN usuarios u_criador ON t.usuario_criador_id = u_criador.id
    LEFT JOIN usuarios u_responsavel ON t.usuario_responsavel_id = u_responsavel.id
    LEFT JOIN qualificacoes q ON t.qualificacao_id = q.id
    WHERE $where_clause_main
    ORDER BY t.criado_em DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params_main);
$tramitacoes = $stmt->fetchAll();

// Buscar estatísticas gerais
$stats_query = "
    SELECT 
        COALESCE(COUNT(*), 0) as total,
        COALESCE(SUM(CASE WHEN status = 'TODO' THEN 1 ELSE 0 END), 0) as todo,
        COALESCE(SUM(CASE WHEN status = 'EM_PROGRESSO' THEN 1 ELSE 0 END), 0) as em_progresso,
        COALESCE(SUM(CASE WHEN status = 'AGUARDANDO' THEN 1 ELSE 0 END), 0) as aguardando,
        COALESCE(SUM(CASE WHEN status = 'CONCLUIDO' THEN 1 ELSE 0 END), 0) as concluido,
        COALESCE(SUM(CASE WHEN status = 'CANCELADO' THEN 1 ELSE 0 END), 0) as cancelado
    FROM tramitacoes_kanban t
    WHERE $where_clause_stats
";

$stmt_stats = $pdo->prepare($stats_query);
$stmt_stats->execute($params_stats);
$stats = $stmt_stats->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Tramitações - Sistema CGLIC</title>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Layout padrão do sistema */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
            padding: 20px;
            margin: 0;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header padrão do sistema */
        .header {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-content h1 {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
        }

        .header-content .subtitle {
            color: #666;
            font-size: 15px;
            margin-top: 8px;
            line-height: 1.5;
        }

        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn-action {
            background-color: #3498db;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .btn-action:hover {
            background-color: #2980b9;
        }

        .btn-action.secondary {
            background-color: #95a5a6;
        }

        .btn-action.secondary:hover {
            background-color: #7f8c8d;
        }

        /* Cards de estatísticas padrão do sistema */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.total::before { background: #3498db; }
        .stat-card.todo::before { background: #95a5a6; }
        .stat-card.em-progresso::before { background: #f39c12; }
        .stat-card.aguardando::before { background: #9b59b6; }
        .stat-card.concluido::before { background: #27ae60; }
        .stat-card.cancelado::before { background: #e74c3c; }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-card.total .stat-value { color: #3498db; }
        .stat-card.todo .stat-value { color: #95a5a6; }
        .stat-card.em-progresso .stat-value { color: #f39c12; }
        .stat-card.aguardando .stat-value { color: #9b59b6; }
        .stat-card.concluido .stat-value { color: #27ae60; }
        .stat-card.cancelado .stat-value { color: #e74c3c; }

        .stat-label {
            color: #666;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Tabela padrão do sistema */
        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .table-header {
            background: #2c3e50;
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .table-header h2 {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .filter-badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        .table th {
            background: #34495e;
            font-weight: 600;
            color: white;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .table td {
            font-size: 14px;
            color: #333;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        /* Badges de status padrão do sistema */
        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .status-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-todo { background: #ecf0f1; color: #7f8c8d; }
        .status-em-progresso { background: #fff3cd; color: #e67e22; }
        .status-aguardando { background: #e8daef; color: #8e44ad; }
        .status-concluido { background: #d4edda; color: #27ae60; }
        .status-cancelado { background: #f8d7da; color: #e74c3c; }

        .priority-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .priority-baixa { background: #ecf0f1; color: #7f8c8d; }
        .priority-media { background: #d6eaf8; color: #2980b9; }
        .priority-alta { background: #fdebd0; color: #e67e22; }
        .priority-urgente { background: #fadbd8; color: #e74c3c; }

        .valor-monetario {
            font-family: 'Courier New', Courier, monospace;
            font-weight: 600;
            color: #27ae60;
            font-size: 13px;
        }

        .truncate {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Estado vazio padrão do sistema */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
            color: #95a5a6;
        }

        .empty-state h3 {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 12px;
        }

        .empty-state p {
            font-size: 15px;
            color: #666;
            line-height: 1.6;
        }

        /* Responsivo padrão do sistema */
        @media (max-width: 768px) {
            body { padding: 15px; }

            .container {
                padding: 0 10px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                padding: 20px;
            }

            .header-content h1 {
                font-size: 22px;
                justify-content: center;
            }

            .header-actions {
                width: 100%;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-value {
                font-size: 30px;
            }

            .table-header {
                padding: 15px 20px;
            }

            .table-header h2 {
                font-size: 16px;
            }

            .table th,
            .table td {
                padding: 12px 15px;
                font-size: 13px;
            }

            .truncate {
                max-width: 150px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .table th,
            .table td {
                padding: 10px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <h1>
                    <i data-lucide="history"></i>
                    Histórico de Tramitações
                    <?php if (!empty($filtro_nup)): ?>
                        <span style="color: #8b5cf6; font-weight: 500; font-size: 16px;">
                            - Processo <?= htmlspecialchars($filtro_nup) ?>
                        </span>
                    <?php endif; ?>
                </h1>
                <div class="subtitle">
                    <?php if (!empty($filtro_nup)): ?>
                        Tramitações vinculadas ao processo <strong><?= htmlspecialchars($filtro_nup) ?></strong>
                    <?php else: ?>
                        Visualização completa de todas as tramitações do sistema
                    <?php endif; ?>
                </div>
            </div>
            <div class="header-actions">
                <?php if (!empty($filtro_nup)): ?>
                    <a href="qualificacao_dashboard.php" class="btn-action secondary">
                        <i data-lucide="arrow-left"></i>
                        Voltar à Qualificação
                    </a>
                <?php endif; ?>
                <a href="tramitacao_kanban.php" class="btn-action">
                    <i data-lucide="kanban-square"></i>
                    <?= !empty($filtro_nup) ? 'Ir ao Kanban' : 'Voltar ao Kanban' ?>
                </a>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-value"><?= number_format($stats['total'] ?? 0) ?></div>
                <div class="stat-label">Total de Tramitações</div>
            </div>
            <div class="stat-card todo">
                <div class="stat-value"><?= number_format($stats['todo'] ?? 0) ?></div>
                <div class="stat-label">A Fazer</div>
            </div>
            <div class="stat-card em-progresso">
                <div class="stat-value"><?= number_format($stats['em_progresso'] ?? 0) ?></div>
                <div class="stat-label">Em Progresso</div>
            </div>
            <div class="stat-card aguardando">
                <div class="stat-value"><?= number_format($stats['aguardando'] ?? 0) ?></div>
                <div class="stat-label">Aguardando</div>
            </div>
            <div class="stat-card concluido">
                <div class="stat-value"><?= number_format($stats['concluido'] ?? 0) ?></div>
                <div class="stat-label">Concluído</div>
            </div>
            <div class="stat-card cancelado">
                <div class="stat-value"><?= number_format($stats['cancelado'] ?? 0) ?></div>
                <div class="stat-label">Cancelado</div>
            </div>
        </div>

        <!-- Tabela de Tramitações -->
        <div class="table-container">
            <div class="table-header">
                <h2>
                    <i data-lucide="table"></i>
                    Histórico Completo de Tramitações
                </h2>
                <?php if (!empty($filtro_nup)): ?>
                    <div class="filter-badge">
                        <i data-lucide="filter" style="width: 12px; height: 12px;"></i>
                        NUP: <?= htmlspecialchars($filtro_nup) ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (empty($tramitacoes)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i data-lucide="inbox"></i>
                    </div>
                    <?php if (!empty($filtro_nup)): ?>
                        <h3>Nenhuma tramitação encontrada para este processo</h3>
                        <p>O processo <strong><?= htmlspecialchars($filtro_nup) ?></strong> ainda não possui tramitações vinculadas.</p>
                    <?php else: ?>
                        <h3>Nenhuma tramitação encontrada</h3>
                        <p>Não há tramitações para exibir com os filtros aplicados.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nº Tramite</th>
                                <th>Título</th>
                                <th>NUP</th>
                                <th>Objeto</th>
                                <th>Área</th>
                                <th>Responsável</th>
                                <th>Valor Estimado</th>
                                <th>Status</th>
                                <th>Prioridade</th>
                                <th>Criado em</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tramitacoes as $tramitacao): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($tramitacao['numero_tramite']) ?></strong>
                                    </td>
                                    <td>
                                        <div class="truncate" title="<?= htmlspecialchars($tramitacao['titulo']) ?>">
                                            <?= htmlspecialchars($tramitacao['titulo']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($tramitacao['nup']): ?>
                                            <strong><?= htmlspecialchars($tramitacao['nup']) ?></strong>
                                        <?php elseif ($tramitacao['nup_vinculado']): ?>
                                            <?= htmlspecialchars($tramitacao['nup_vinculado']) ?>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($tramitacao['objeto']): ?>
                                            <div class="truncate" title="<?= htmlspecialchars($tramitacao['objeto']) ?>">
                                                <?= htmlspecialchars($tramitacao['objeto']) ?>
                                            </div>
                                        <?php elseif ($tramitacao['objeto_vinculado']): ?>
                                            <div class="truncate" title="<?= htmlspecialchars($tramitacao['objeto_vinculado']) ?>">
                                                <?= htmlspecialchars($tramitacao['objeto_vinculado']) ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($tramitacao['area_demandante']): ?>
                                            <?= htmlspecialchars($tramitacao['area_demandante']) ?>
                                        <?php elseif ($tramitacao['area_vinculada']): ?>
                                            <?= htmlspecialchars($tramitacao['area_vinculada']) ?>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($tramitacao['nome_responsavel']): ?>
                                            <?= htmlspecialchars($tramitacao['nome_responsavel']) ?>
                                        <?php elseif ($tramitacao['responsavel_qualificacao']): ?>
                                            <?= htmlspecialchars($tramitacao['responsavel_qualificacao']) ?>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">Não atribuído</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($tramitacao['valor_estimado']): ?>
                                            <span class="valor-monetario">
                                                R$ <?= number_format($tramitacao['valor_estimado'] / 100, 2, ',', '.') ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= strtolower(str_replace('_', '-', $tramitacao['status'])) ?>">
                                            <?= str_replace('_', ' ', $tramitacao['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="priority-badge priority-<?= strtolower($tramitacao['prioridade']) ?>">
                                            <?= $tramitacao['prioridade'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y H:i', strtotime($tramitacao['criado_em'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Inicializar ícones Lucide
        lucide.createIcons();
    </script>
</body>
</html>