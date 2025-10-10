<?php
require_once 'config.php';
require_once 'functions.php';

verificarLogin();

$pdo = conectarDB();
$usuario_id = $_SESSION['usuario_id'];
$usuario_nivel = $_SESSION['usuario_nivel'];
$usuario_nome = $_SESSION['usuario_nome'];

// Verificar permissões - todos podem visualizar tramitações
$pode_criar = $usuario_nivel <= 3; // Coordenador, DIPLAN, DIPLI podem criar
$pode_editar = $usuario_nivel <= 3;

// Parâmetros de filtro
$filtro_modulo = $_GET['modulo'] ?? 'TODOS';
$filtro_responsavel = $_GET['responsavel'] ?? 'TODOS';
$filtro_prioridade = $_GET['prioridade'] ?? 'TODOS';
$filtro_prazo = $_GET['prazo'] ?? 'TODOS';
$busca = $_GET['busca'] ?? '';

// Construir query base
$where_conditions = ["1=1"];
$params = [];

// Aplicar filtros baseados no nível do usuário
if ($usuario_nivel == 2) { // DIPLAN - apenas planejamento
    $where_conditions[] = "(tk.modulo_origem = 'PLANEJAMENTO' OR tk.modulo_destino = 'PLANEJAMENTO')";
} elseif ($usuario_nivel == 3) { // DIPLI - licitação, qualificação, contratos
    $where_conditions[] = "(tk.modulo_origem IN ('LICITACAO','QUALIFICACAO','CONTRATOS') OR tk.modulo_destino IN ('LICITACAO','QUALIFICACAO','CONTRATOS'))";
}

// Filtros da interface
if ($filtro_modulo != 'TODOS') {
    $where_conditions[] = "(tk.modulo_origem = ? OR tk.modulo_destino = ?)";
    $params[] = $filtro_modulo;
    $params[] = $filtro_modulo;
}

if ($filtro_responsavel != 'TODOS') {
    if ($filtro_responsavel == 'MEU') {
        $where_conditions[] = "tk.usuario_responsavel_id = ?";
        $params[] = $usuario_id;
    } elseif ($filtro_responsavel == 'SEM_RESPONSAVEL') {
        $where_conditions[] = "tk.usuario_responsavel_id IS NULL";
    } else {
        $where_conditions[] = "tk.usuario_responsavel_id = ?";
        $params[] = $filtro_responsavel;
    }
}

if ($filtro_prioridade != 'TODOS') {
    $where_conditions[] = "tk.prioridade = ?";
    $params[] = $filtro_prioridade;
}

if ($filtro_prazo != 'TODOS') {
    switch ($filtro_prazo) {
        case 'ATRASADO':
            $where_conditions[] = "tk.situacao_prazo = 'ATRASADO'";
            break;
        case 'VENCENDO':
            $where_conditions[] = "tk.situacao_prazo = 'VENCENDO'";
            break;
        case 'SEM_PRAZO':
            $where_conditions[] = "tk.prazo_limite IS NULL";
            break;
    }
}

if (!empty($busca)) {
    $where_conditions[] = "(tk.titulo LIKE ? OR tk.descricao LIKE ? OR tk.tipo_demanda LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

$where_clause = implode(' AND ', $where_conditions);

// Buscar tramitações por status para o Kanban
$statuses = ['TODO', 'EM_PROGRESSO', 'AGUARDANDO', 'CONCLUIDO'];
$kanban_data = [];

foreach ($statuses as $status) {
    // Query na tabela tramitacoes_kanban sem COLLATE para evitar conflitos
    $query = "
        SELECT
            tk.*,
            u.nome as responsavel_nome
        FROM tramitacoes_kanban tk
        LEFT JOIN usuarios u ON tk.usuario_responsavel_id = u.id
        WHERE $where_clause
        AND tk.status = ?
        ORDER BY
            CASE tk.prioridade
                WHEN 'URGENTE' THEN 1
                WHEN 'ALTA' THEN 2
                WHEN 'MEDIA' THEN 3
                WHEN 'BAIXA' THEN 4
            END,
            tk.posicao ASC,
            tk.criado_em DESC
    ";

    $stmt = $pdo->prepare($query);
    $status_params = array_merge($params, [$status]);
    $stmt->execute($status_params);
    $kanban_data[$status] = $stmt->fetchAll();
}

// Buscar usuários para filtros e atribuição
$users_query = "SELECT id, nome, email, departamento FROM usuarios WHERE ativo = 1 ORDER BY nome";
$usuarios = $pdo->query($users_query)->fetchAll();

// Buscar templates disponíveis
$templates_query = "SELECT * FROM tramitacoes_templates WHERE ativo = 1 ORDER BY nome";
$templates = $pdo->query($templates_query)->fetchAll();

// Estatísticas gerais sem COLLATE para evitar conflitos
$stats_query = "
    SELECT
        COUNT(*) as total,
        COUNT(CASE WHEN tk.status = 'TODO' THEN 1 END) as todo,
        COUNT(CASE WHEN tk.status = 'EM_PROGRESSO' THEN 1 END) as em_progresso,
        COUNT(CASE WHEN tk.status = 'AGUARDANDO' THEN 1 END) as aguardando,
        COUNT(CASE WHEN tk.status = 'CONCLUIDO' THEN 1 END) as concluido,
        COUNT(CASE WHEN tk.situacao_prazo = 'ATRASADO' THEN 1 END) as atrasadas
    FROM tramitacoes_kanban tk
    LEFT JOIN usuarios u ON tk.usuario_responsavel_id = u.id
    WHERE $where_clause
";
$stmt_stats = $pdo->prepare($stats_query);
$stmt_stats->execute($params);
$stats = $stmt_stats->fetch();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tramitações Kanban - Sistema CGLIC</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        // Debug - verificar se Sortable.js carregou
        window.addEventListener('load', function() {
            if (typeof Sortable !== 'undefined') {
                console.log('✅ Sortable.js carregado com sucesso!', Sortable);
            } else {
                console.error('❌ Sortable.js NÃO foi carregado!');
            }
        });
    </script>
    <style>
        .kanban-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 20px;
            overflow-x: hidden;
        }

        .header-kanban {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .titulo-kanban {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
        }

        .stats-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: white;
            padding: 16px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            min-width: 120px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
        }

        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            margin-top: 4px;
        }

        .stat-card.total { border-left: 4px solid #3b82f6; }
        .stat-card.todo { border-left: 4px solid #6b7280; }
        .stat-card.em-progresso { border-left: 4px solid #f59e0b; }
        .stat-card.aguardando { border-left: 4px solid #8b5cf6; }
        .stat-card.concluido { border-left: 4px solid #10b981; }
        .stat-card.atrasadas { border-left: 4px solid #ef4444; }

        .filtros-kanban {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            align-items: end;
        }

        .filtro-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filtro-label {
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }

        .filtro-input, .filtro-select {
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .filtro-input:focus, .filtro-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .kanban-board {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
            overflow-x: auto;
            min-height: 600px;
        }

        .kanban-column {
            background: #f8fafc;
            border-radius: 16px;
            padding: 20px;
            min-height: 600px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 2px dashed transparent;
            transition: all 0.3s ease;
        }

        .kanban-column.drag-over {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .column-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e5e7eb;
        }

        .column-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .column-count {
            background: #3b82f6;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
        }

        .column-todo { border-top: 4px solid #6b7280; }
        .column-em-progresso { border-top: 4px solid #f59e0b; }
        .column-aguardando { border-top: 4px solid #8b5cf6; }
        .column-concluido { border-top: 4px solid #10b981; }

        .cards-container {
            min-height: 400px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .kanban-card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            cursor: move;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #3b82f6;
        }

        .kanban-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }

        .kanban-card.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
        }

        .kanban-card.prioridade-urgente { border-left-color: #dc2626; }
        .kanban-card.prioridade-alta { border-left-color: #ea580c; }
        .kanban-card.prioridade-media { border-left-color: #d97706; }
        .kanban-card.prioridade-baixa { border-left-color: #16a34a; }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .card-numero {
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
        }

        .card-prioridade {
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .prioridade-urgente { background: #fee2e2; color: #dc2626; }
        .prioridade-alta { background: #fed7aa; color: #ea580c; }
        .prioridade-media { background: #fef3c7; color: #d97706; }
        .prioridade-baixa { background: #dcfce7; color: #16a34a; }

        .card-titulo {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .card-tipo {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 12px;
        }

        .card-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #9ca3af;
        }

        .card-responsavel {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .card-prazo {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .card-prazo.atrasado { color: #dc2626; font-weight: 600; }
        .card-prazo.vencendo { color: #ea580c; font-weight: 600; }

        .card-tags {
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .tag {
            background: #f3f4f6;
            color: #374151;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 500;
        }

        .btn-nova-tramitacao {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-nova-tramitacao:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }

        .btn-voltar-menu {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
            white-space: nowrap;
        }

        .btn-voltar-menu:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(107, 114, 128, 0.3);
            background: linear-gradient(135deg, #4b5563, #374151);
        }

        .btn-historico-tramitacoes:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
            background: linear-gradient(135deg, #059669, #047857) !important;
        }

        .btn-filtrar {
            background: #f3f4f6;
            color: #374151;
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-filtrar:hover {
            background: #e5e7eb;
        }

        .empty-column {
            text-align: center;
            color: #9ca3af;
            padding: 40px 20px;
            font-style: italic;
        }

        .add-card-btn {
            width: 100%;
            padding: 12px;
            border: 2px dashed #d1d5db;
            background: transparent;
            border-radius: 8px;
            color: #6b7280;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .add-card-btn:hover {
            border-color: #3b82f6;
            color: #3b82f6;
        }

        @media (max-width: 1200px) {
            .kanban-board {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .kanban-board {
                grid-template-columns: 1fr;
            }

            .filtros-grid {
                grid-template-columns: 1fr;
            }

            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .header-kanban {
                flex-direction: column;
                align-items: stretch;
            }

            /* Ajustar header mobile */
            .header-kanban > div:first-child > div:first-child {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 12px !important;
            }

            .titulo-kanban {
                font-size: 24px !important;
            }

            .btn-voltar-menu {
                font-size: 13px;
                padding: 8px 12px;
            }

            .btn-historico-tramitacoes {
                font-size: 13px !important;
                padding: 8px 12px !important;
            }

            .btn-nova-tramitacao {
                font-size: 13px !important;
                padding: 8px 12px !important;
            }
        }

        /* Animações */
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .kanban-card {
            animation: slideIn 0.3s ease;
        }

        /* Modal styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 16px;
            padding: 0;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        }
    </style>
</head>
<body>
    <div class="kanban-container">
        <!-- Header -->
        <div class="header-kanban">
            <div>
                <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 8px;">
                    <a href="selecao_modulos.php" class="btn-voltar-menu">
                        <i data-lucide="home"></i>
                        Menu Principal
                    </a>
                    <h1 class="titulo-kanban">
                        <i data-lucide="kanban-square"></i>
                        Tramitações Kanban
                    </h1>
                </div>
                <p style="color: #6b7280; margin: 0;">Gerencie tramitações com metodologia ágil</p>
            </div>
            <div style="display: flex; gap: 12px; align-items: center;">
                <!-- Botão Histórico de Tramitações (sempre visível) -->
                <a href="tramitacoes_historico.php" class="btn-historico-tramitacoes" style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 10px 16px; border: none; border-radius: 10px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease; font-size: 14px; white-space: nowrap;">
                    <i data-lucide="history"></i>
                    Histórico
                </a>
                
                <?php if ($pode_criar): ?>
                <button class="btn-nova-tramitacao" onclick="abrirModalNovaTramitacao();">
                    <i data-lucide="plus"></i>
                    Nova Tramitação
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="stats-row">
            <div class="stat-card total">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-card todo">
                <div class="stat-number"><?php echo $stats['todo']; ?></div>
                <div class="stat-label">A Fazer</div>
            </div>
            <div class="stat-card em-progresso">
                <div class="stat-number"><?php echo $stats['em_progresso']; ?></div>
                <div class="stat-label">Em Progresso</div>
            </div>
            <div class="stat-card aguardando">
                <div class="stat-number"><?php echo $stats['aguardando']; ?></div>
                <div class="stat-label">Aguardando</div>
            </div>
            <div class="stat-card concluido">
                <div class="stat-number"><?php echo $stats['concluido']; ?></div>
                <div class="stat-label">Concluído</div>
            </div>
            <div class="stat-card atrasadas">
                <div class="stat-number"><?php echo $stats['atrasadas']; ?></div>
                <div class="stat-label">Atrasadas</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros-kanban">
            <form method="GET" class="filtros-grid">
                <div class="filtro-group">
                    <label class="filtro-label">Módulo</label>
                    <select name="modulo" class="filtro-select">
                        <option value="TODOS">Todos os Módulos</option>
                        <option value="PLANEJAMENTO" <?php echo $filtro_modulo == 'PLANEJAMENTO' ? 'selected' : ''; ?>>Planejamento</option>
                        <option value="LICITACAO" <?php echo $filtro_modulo == 'LICITACAO' ? 'selected' : ''; ?>>Licitação</option>
                        <option value="QUALIFICACAO" <?php echo $filtro_modulo == 'QUALIFICACAO' ? 'selected' : ''; ?>>Qualificação</option>
                        <option value="CONTRATOS" <?php echo $filtro_modulo == 'CONTRATOS' ? 'selected' : ''; ?>>Contratos</option>
                    </select>
                </div>

                <div class="filtro-group">
                    <label class="filtro-label">Responsável</label>
                    <select name="responsavel" class="filtro-select">
                        <option value="TODOS">Todos</option>
                        <option value="MEU" <?php echo $filtro_responsavel == 'MEU' ? 'selected' : ''; ?>>Minhas Tramitações</option>
                        <option value="SEM_RESPONSAVEL" <?php echo $filtro_responsavel == 'SEM_RESPONSAVEL' ? 'selected' : ''; ?>>Sem Responsável</option>
                        <?php foreach ($usuarios as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $filtro_responsavel == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filtro-group">
                    <label class="filtro-label">Prioridade</label>
                    <select name="prioridade" class="filtro-select">
                        <option value="TODOS">Todas</option>
                        <option value="URGENTE" <?php echo $filtro_prioridade == 'URGENTE' ? 'selected' : ''; ?>>Urgente</option>
                        <option value="ALTA" <?php echo $filtro_prioridade == 'ALTA' ? 'selected' : ''; ?>>Alta</option>
                        <option value="MEDIA" <?php echo $filtro_prioridade == 'MEDIA' ? 'selected' : ''; ?>>Média</option>
                        <option value="BAIXA" <?php echo $filtro_prioridade == 'BAIXA' ? 'selected' : ''; ?>>Baixa</option>
                    </select>
                </div>

                <div class="filtro-group">
                    <label class="filtro-label">Buscar</label>
                    <input type="text" name="busca" class="filtro-input" 
                           placeholder="Título, descrição ou número..." 
                           value="<?php echo htmlspecialchars($busca); ?>">
                </div>

                <div class="filtro-group">
                    <button type="submit" class="btn-filtrar">
                        <i data-lucide="search"></i>
                        Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Board Kanban -->
        <div class="kanban-board">
            <!-- Coluna TODO -->
            <div class="kanban-column column-todo" data-status="TODO">
                <div class="column-header">
                    <div class="column-title">
                        <i data-lucide="circle"></i>
                        A Fazer
                    </div>
                    <div class="column-count"><?php echo count($kanban_data['TODO']); ?></div>
                </div>
                <div class="cards-container" id="cards-TODO">
                    <?php if (empty($kanban_data['TODO'])): ?>
                        <div class="empty-column">
                            <i data-lucide="inbox" size="32"></i>
                            <p>Nenhuma tramitação</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($kanban_data['TODO'] as $card): ?>
                            <?php include 'components/kanban_card.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if ($pode_criar): ?>
                    <button class="add-card-btn" onclick="abrirModalNovaTramitacao('TODO');">
                        <i data-lucide="plus"></i> Adicionar Card
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Coluna EM_PROGRESSO -->
            <div class="kanban-column column-em-progresso" data-status="EM_PROGRESSO">
                <div class="column-header">
                    <div class="column-title">
                        <i data-lucide="play"></i>
                        Em Progresso
                    </div>
                    <div class="column-count"><?php echo count($kanban_data['EM_PROGRESSO']); ?></div>
                </div>
                <div class="cards-container" id="cards-EM_PROGRESSO">
                    <?php if (empty($kanban_data['EM_PROGRESSO'])): ?>
                        <div class="empty-column">
                            <i data-lucide="inbox" size="32"></i>
                            <p>Nenhuma tramitação</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($kanban_data['EM_PROGRESSO'] as $card): ?>
                            <?php include 'components/kanban_card.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if ($pode_criar): ?>
                    <button class="add-card-btn" onclick="abrirModalNovaTramitacao('EM_PROGRESSO');">
                        <i data-lucide="plus"></i> Adicionar Card
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Coluna AGUARDANDO -->
            <div class="kanban-column column-aguardando" data-status="AGUARDANDO">
                <div class="column-header">
                    <div class="column-title">
                        <i data-lucide="pause"></i>
                        Aguardando
                    </div>
                    <div class="column-count"><?php echo count($kanban_data['AGUARDANDO']); ?></div>
                </div>
                <div class="cards-container" id="cards-AGUARDANDO">
                    <?php if (empty($kanban_data['AGUARDANDO'])): ?>
                        <div class="empty-column">
                            <i data-lucide="inbox" size="32"></i>
                            <p>Nenhuma tramitação</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($kanban_data['AGUARDANDO'] as $card): ?>
                            <?php include 'components/kanban_card.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if ($pode_criar): ?>
                    <button class="add-card-btn" onclick="abrirModalNovaTramitacao('AGUARDANDO');">
                        <i data-lucide="plus"></i> Adicionar Card
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Coluna CONCLUIDO -->
            <div class="kanban-column column-concluido" data-status="CONCLUIDO">
                <div class="column-header">
                    <div class="column-title">
                        <i data-lucide="check"></i>
                        Concluído
                    </div>
                    <div class="column-count"><?php echo count($kanban_data['CONCLUIDO']); ?></div>
                </div>
                <div class="cards-container" id="cards-CONCLUIDO">
                    <?php if (empty($kanban_data['CONCLUIDO'])): ?>
                        <div class="empty-column">
                            <i data-lucide="inbox" size="32"></i>
                            <p>Nenhuma tramitação</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($kanban_data['CONCLUIDO'] as $card): ?>
                            <?php include 'components/kanban_card.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nova Tramitação -->
    <div class="modal-overlay" id="modalNovaTramitacao">
        <div class="modal">
            <div class="modal-header" style="padding: 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0; font-size: 24px; font-weight: 700;">Nova Tramitação</h2>
                <button class="modal-close" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; padding: 4px;">
                    <i data-lucide="x"></i>
                </button>
            </div>
            
            <form method="POST" action="process.php" style="margin: 0;" id="formNovaTramitacao">
                <input type="hidden" name="action" value="criar_tramitacao_kanban">
                <input type="hidden" name="redirect" value="tramitacao_kanban.php">
                
                <div style="padding: 24px; max-height: 70vh; overflow-y: auto;">
                    <!-- Template Selector -->
                    <div style="margin-bottom: 20px; padding: 16px; background: #f8fafc; border-radius: 8px;">
                        <label style="font-weight: 600; color: #374151; font-size: 14px; display: block; margin-bottom: 8px;">
                            <i data-lucide="layout-template"></i>
                            Usar Template (Opcional)
                        </label>
                        <select onchange="if(typeof aplicarTemplate === 'function') aplicarTemplate(this.value); else console.log('Template selecionado:', this.value);" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                            <option value="">Selecione um template...</option>
                            <?php foreach ($templates as $template): ?>
                                <option value="<?php echo $template['id']; ?>">
                                    <?php echo htmlspecialchars($template['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-weight: 600; color: #374151; font-size: 14px;">Módulo Origem</label>
                            <select name="modulo_origem" required style="padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                <option value="PLANEJAMENTO">Planejamento</option>
                                <option value="LICITACAO">Licitação</option>
                                <option value="QUALIFICACAO">Qualificação</option>
                                <option value="CONTRATOS">Contratos</option>
                            </select>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-weight: 600; color: #374151; font-size: 14px;">Módulo Destino</label>
                            <select name="modulo_destino" required style="padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                <option value="PLANEJAMENTO">Planejamento</option>
                                <option value="LICITACAO">Licitação</option>
                                <option value="QUALIFICACAO">Qualificação</option>
                                <option value="CONTRATOS">Contratos</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-weight: 600; color: #374151; font-size: 14px;">Prioridade</label>
                            <select name="prioridade" style="padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                <option value="BAIXA">Baixa</option>
                                <option value="MEDIA" selected>Média</option>
                                <option value="ALTA">Alta</option>
                                <option value="URGENTE">Urgente</option>
                            </select>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-weight: 600; color: #374151; font-size: 14px;">Status Inicial</label>
                            <select name="status" style="padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                <option value="TODO" selected>A Fazer</option>
                                <option value="EM_PROGRESSO">Em Progresso</option>
                                <option value="AGUARDANDO">Aguardando</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-weight: 600; color: #374151; font-size: 14px;">Responsável</label>
                            <select name="usuario_responsavel_id" style="padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                <option value="">Sem responsável</option>
                                <?php foreach ($usuarios as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['nome']); ?>
                                        <?php if ($user['departamento']): ?>
                                            (<?php echo htmlspecialchars($user['departamento']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-weight: 600; color: #374151; font-size: 14px;">Prazo Limite</label>
                            <input type="datetime-local" name="prazo_limite" style="padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                        <label style="font-weight: 600; color: #374151; font-size: 14px;">Tipo de Demanda</label>
                        <input type="text" name="tipo_demanda" placeholder="Ex: Análise Técnica, Elaboração de Edital..." required style="padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                        <label style="font-weight: 600; color: #374151; font-size: 14px;">Título</label>
                        <input type="text" name="titulo" placeholder="Título resumido da tramitação" required style="padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                        <label style="font-weight: 600; color: #374151; font-size: 14px;">Descrição</label>
                        <textarea name="descricao" placeholder="Descreva detalhadamente a demanda..." required style="min-height: 100px; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; resize: vertical; font-size: 14px;"></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-weight: 600; color: #374151; font-size: 14px;">Tags (separadas por vírgula)</label>
                            <input type="text" name="tags" placeholder="analise-tecnica, urgente, pca" style="padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-weight: 600; color: #374151; font-size: 14px;">Cor do Card</label>
                            <input type="color" name="cor_card" value="#3b82f6" style="height: 48px; padding: 4px; border: 1px solid #d1d5db; border-radius: 8px; cursor: pointer;">
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                        <label style="font-weight: 600; color: #374151; font-size: 14px;">Observações</label>
                        <textarea name="observacoes" placeholder="Observações adicionais (opcional)" style="min-height: 80px; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; resize: vertical; font-size: 14px;"></textarea>
                    </div>

                    <!-- Seção de Tarefas -->
                    <div style="margin-bottom: 20px; padding: 16px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
                            <i data-lucide="check-square" style="color: #16a34a;"></i>
                            <label style="font-weight: 600; color: #15803d; font-size: 16px; margin: 0;">
                                Tarefas do Processo (Opcional)
                            </label>
                            <button type="button" onclick="toggleTarefasSection()" style="background: none; border: none; color: #16a34a; cursor: pointer;" id="toggleTarefasBtn">
                                <i data-lucide="chevron-down" id="toggleTarefasIcon"></i>
                            </button>
                        </div>
                        
                        <div id="tarefasSection" style="display: none;">
                            <p style="font-size: 14px; color: #166534; margin-bottom: 16px;">
                                Selecione as tarefas que farão parte desta tramitação. As tarefas são organizadas por módulo de acordo com o fluxo do processo.
                            </p>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <!-- Tarefas do Módulo Origem -->
                                <div>
                                    <h4 style="color: #15803d; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                                        <i data-lucide="send" size="16"></i>
                                        Tarefas do Módulo Origem
                                    </h4>
                                    <div id="tarefasOrigem" style="max-height: 200px; overflow-y: auto; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px; background: white;">
                                        <!-- Tarefas serão carregadas via JavaScript -->
                                    </div>
                                </div>

                                <!-- Tarefas do Módulo Destino -->
                                <div>
                                    <h4 style="color: #15803d; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                                        <i data-lucide="inbox" size="16"></i>
                                        Tarefas do Módulo Destino
                                    </h4>
                                    <div id="tarefasDestino" style="max-height: 200px; overflow-y: auto; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px; background: white;">
                                        <!-- Tarefas serão carregadas via JavaScript -->
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top: 16px; padding: 12px; background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                    <i data-lucide="info" style="color: #d97706;" size="16"></i>
                                    <strong style="color: #d97706; font-size: 14px;">Informações sobre Tarefas:</strong>
                                </div>
                                <ul style="font-size: 12px; color: #92400e; margin: 0; padding-left: 20px; line-height: 1.4;">
                                    <li>Tarefas selecionadas serão criadas automaticamente com status "INICIANDO"</li>
                                    <li>Você poderá gerenciar os estágios das tarefas (Iniciando → Em Andamento → Concluída)</li>
                                    <li>Tarefas podem ser atribuídas a diferentes responsáveis</li>
                                    <li>É possível adicionar mais tarefas após criar a tramitação</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end; padding: 24px; border-top: 1px solid #e5e7eb;">
                    <button type="button" onclick="fecharModal('modalNovaTramitacao')" style="padding: 12px 24px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        Cancelar
                    </button>
                    <button type="submit" style="padding: 12px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        <i data-lucide="plus"></i>
                        Criar Tramitação
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Detalhes da Tramitação -->
    <div class="modal-overlay" id="modalDetalhes" style="display: none;">
        <div class="modal">
            <div class="modal-header" style="padding: 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                    <i data-lucide="eye"></i>
                    Detalhes da Tramitação
                </h2>
                <button class="modal-close" onclick="fecharModal('modalDetalhes')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; padding: 4px;">
                    <i data-lucide="x"></i>
                </button>
            </div>
            
            <div style="padding: 24px; max-height: 70vh; overflow-y: auto;" id="detalhesContent">
                <!-- Conteúdo será carregado via JavaScript -->
                <div style="text-align: center; padding: 40px; color: #6b7280;">
                    <i data-lucide="loader" style="animation: spin 1s linear infinite;"></i>
                    <p>Carregando detalhes...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Tramitação -->
    <div class="modal-overlay" id="modalEditar" style="display: none;">
        <div class="modal">
            <div class="modal-header" style="padding: 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                    <i data-lucide="edit"></i>
                    Editar Tramitação
                </h2>
                <button class="modal-close" onclick="fecharModal('modalEditar')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; padding: 4px;">
                    <i data-lucide="x"></i>
                </button>
            </div>
            
            <form method="POST" action="process.php" style="margin: 0;" id="formEditarTramitacao">
                <input type="hidden" name="action" value="editar_tramitacao_kanban">
                <input type="hidden" name="redirect" value="tramitacao_kanban.php">
                <input type="hidden" name="tramitacao_id" id="editTramitacaoId">
                
                <div style="padding: 24px; max-height: 70vh; overflow-y: auto;" id="editarContent">
                    <!-- Conteúdo será carregado via JavaScript -->
                    <div style="text-align: center; padding: 40px; color: #6b7280;">
                        <i data-lucide="loader" style="animation: spin 1s linear infinite;"></i>
                        <p>Carregando dados para edição...</p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end; padding: 24px; border-top: 1px solid #e5e7eb;">
                    <button type="button" onclick="fecharModal('modalEditar')" style="padding: 12px 24px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        Cancelar
                    </button>
                    <button type="submit" style="padding: 12px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        <i data-lucide="save"></i>
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Função de emergência inline -->
    <script>
        // Função de emergência diretamente no HTML
        function abrirModalNovaTramitacao(statusInicial = 'TODO') {
            const modal = document.getElementById('modalNovaTramitacao');
            if (!modal) {
                alert('❌ Modal não encontrado!');
                return;
            }
            
            // Definir status inicial
            const statusSelect = modal.querySelector('select[name="status"]');
            if (statusSelect && statusInicial) {
                statusSelect.value = statusInicial;
            }
            
            // FORÇAR estilos inline para garantir visibilidade
            modal.style.display = 'block';
            modal.style.visibility = 'visible';
            modal.style.opacity = '1';
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.right = '0';
            modal.style.bottom = '0';
            modal.style.background = 'rgba(0, 0, 0, 0.8)';
            modal.style.zIndex = '99999';
            modal.style.backdropFilter = 'blur(5px)';
            
            // Forçar também no conteúdo interno
            const modalContent = modal.querySelector('.modal');
            if (modalContent) {
                modalContent.style.visibility = 'visible';
                modalContent.style.opacity = '1';
                modalContent.style.display = 'block';
            }
            
            document.body.style.overflow = 'hidden';
            
            // Focar no primeiro campo
            setTimeout(() => {
                const firstInput = modal.querySelector('select, input');
                if (firstInput) {
                    firstInput.focus();
                }
            }, 100);
        }
        
        function fecharModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) {
                return;
            }
            
            // FORÇAR fechamento
            modal.style.display = 'none';
            modal.style.visibility = 'hidden';
            modal.style.opacity = '0';
            document.body.style.overflow = 'auto';
            
            // Limpar formulário
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
            }
        }
        
        console.log('🔧 Funções inline carregadas!');
        
        // Debug do formulário
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('formNovaTramitacao');
            if (form) {
                form.addEventListener('submit', function(e) {
                    console.log('📤 SUBMIT DETECTADO!');
                    
                    // Coletar dados do formulário
                    const formData = new FormData(form);
                    console.log('📋 Dados do formulário:');
                    for (let [key, value] of formData.entries()) {
                        console.log(`  ${key}: ${value}`);
                    }
                    
                    // Verificar campos obrigatórios
                    const titulo = formData.get('titulo');
                    const tipo_demanda = formData.get('tipo_demanda');
                    const modulo_origem = formData.get('modulo_origem');
                    const modulo_destino = formData.get('modulo_destino');
                    
                    console.log('🔍 Verificação de campos obrigatórios:');
                    console.log(`  Título: "${titulo}" ${titulo ? '✅' : '❌'}`);
                    console.log(`  Tipo Demanda: "${tipo_demanda}" ${tipo_demanda ? '✅' : '❌'}`);
                    console.log(`  Módulo Origem: "${modulo_origem}" ${modulo_origem ? '✅' : '❌'}`);
                    console.log(`  Módulo Destino: "${modulo_destino}" ${modulo_destino ? '✅' : '❌'}`);
                    
                    if (!titulo || !tipo_demanda || !modulo_origem || !modulo_destino) {
                        e.preventDefault();
                        alert('❌ Campos obrigatórios não preenchidos!\n\n' +
                              `Título: ${titulo ? '✅' : '❌'}\n` +
                              `Tipo Demanda: ${tipo_demanda ? '✅' : '❌'}\n` +
                              `Módulo Origem: ${modulo_origem ? '✅' : '❌'}\n` +
                              `Módulo Destino: ${modulo_destino ? '✅' : '❌'}`);
                        return false;
                    }
                    
                    console.log('✅ Formulário válido! Enviando...');
                    alert('🚀 Enviando formulário para process.php...');
                });
            } else {
                console.log('❌ Formulário não encontrado!');
            }
        });
        
        // Configurar eventos para fechar modal
        document.addEventListener('click', function(e) {
            // Fechar modal ao clicar fora
            if (e.target.classList.contains('modal-overlay')) {
                fecharModal(e.target.id);
            }
            
            // Fechar modal com botão X
            if (e.target.closest('.modal-close')) {
                const modal = e.target.closest('.modal-overlay');
                if (modal) {
                    fecharModal(modal.id);
                }
            }
        });
        
        // Fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modalsAbertos = document.querySelectorAll('.modal-overlay[style*="block"]');
                modalsAbertos.forEach(modal => fecharModal(modal.id));
            }
        });
    </script>
    
    <script src="assets/kanban.js"></script>
    <script>
        // Cache de tarefas para evitar múltiplas requisições
        let tarefasCache = {};

        // Inicializar Kanban
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM carregado!');
            
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
                console.log('✅ Lucide icons carregados');
            } else {
                console.log('❌ Lucide não carregado');
            }
            
            // Verificar se kanban.js foi carregado
            if (typeof initializeKanban === 'function') {
                console.log('✅ initializeKanban encontrada');
                initializeKanban();
                console.log('✅ Kanban inicializado');
            } else {
                console.log('❌ initializeKanban não encontrada');
            }
            
            // Verificar se abrirModalNovaTramitacao existe
            if (typeof abrirModalNovaTramitacao === 'function') {
                console.log('✅ abrirModalNovaTramitacao encontrada');
            } else {
                console.log('❌ abrirModalNovaTramitacao não encontrada - criando fallback');
                
                // Criar função fallback
                window.abrirModalNovaTramitacao = function(statusInicial = 'TODO') {
                    console.log('Executando fallback do modal...');
                    const modal = document.getElementById('modalNovaTramitacao');
                    if (modal) {
                        console.log('Modal encontrado, abrindo...');
                        // Definir status inicial se fornecido
                        const statusSelect = modal.querySelector('select[name="status"]');
                        if (statusSelect && statusInicial) {
                            statusSelect.value = statusInicial;
                        }
                        
                        modal.style.display = 'block';
                        document.body.style.overflow = 'hidden';
                        console.log('✅ Modal aberto com sucesso!');
                    } else {
                        console.log('❌ Modal não encontrado!');
                    }
                };
                
                // Criar função de fechar modal
                window.fecharModal = function(modalId) {
                    console.log('Fechando modal:', modalId);
                    const modal = document.getElementById(modalId);
                    if (modal) {
                        modal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                        
                        // Limpar formulário
                        const form = modal.querySelector('form');
                        if (form) {
                            form.reset();
                        }
                        console.log('✅ Modal fechado!');
                    }
                };
            }

            // Configurar eventos para carregar tarefas quando módulos mudarem
            const moduloOrigem = document.querySelector('select[name="modulo_origem"]');
            const moduloDestino = document.querySelector('select[name="modulo_destino"]');
            
            if (moduloOrigem) moduloOrigem.addEventListener('change', carregarTarefasModulos);
            if (moduloDestino) moduloDestino.addEventListener('change', carregarTarefasModulos);

            // Carregar tarefas iniciais
            carregarTarefasModulos();
        });

        // Event listeners para fechar modal
        document.addEventListener('click', function(e) {
            // Fechar modal ao clicar fora
            if (e.target.classList.contains('modal-overlay')) {
                const modalId = e.target.id;
                if (typeof fecharModal === 'function') {
                    fecharModal(modalId);
                }
            }
            
            // Fechar modal com botão X
            if (e.target.closest('.modal-close')) {
                const modal = e.target.closest('.modal-overlay');
                if (modal && typeof fecharModal === 'function') {
                    fecharModal(modal.id);
                }
            }
        });

        // Fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modalsAbertos = document.querySelectorAll('.modal-overlay[style*="block"]');
                modalsAbertos.forEach(modal => {
                    if (typeof fecharModal === 'function') {
                        fecharModal(modal.id);
                    }
                });
            }
        });

        // Toggle da seção de tarefas
        function toggleTarefasSection() {
            const section = document.getElementById('tarefasSection');
            const icon = document.getElementById('toggleTarefasIcon');
            
            if (section.style.display === 'none') {
                section.style.display = 'block';
                icon.setAttribute('data-lucide', 'chevron-up');
                if (typeof lucide !== 'undefined') lucide.createIcons();
                carregarTarefasModulos(); // Carregar tarefas quando abrir
            } else {
                section.style.display = 'none';
                icon.setAttribute('data-lucide', 'chevron-down');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        }

        // Carregar tarefas dos módulos selecionados
        async function carregarTarefasModulos() {
            const moduloOrigem = document.querySelector('select[name="modulo_origem"]').value;
            const moduloDestino = document.querySelector('select[name="modulo_destino"]').value;

            if (moduloOrigem) {
                await carregarTarefasModulo(moduloOrigem, 'tarefasOrigem', 'origem');
            }
            
            if (moduloDestino && moduloDestino !== moduloOrigem) {
                await carregarTarefasModulo(moduloDestino, 'tarefasDestino', 'destino');
            } else if (moduloDestino === moduloOrigem) {
                // Se módulos são iguais, mostrar mensagem
                const container = document.getElementById('tarefasDestino');
                container.innerHTML = '<p style="color: #6b7280; font-style: italic; font-size: 14px;">Mesmo módulo de origem</p>';
            }
        }

        // Carregar tarefas de um módulo específico
        async function carregarTarefasModulo(modulo, containerId, tipo) {
            const container = document.getElementById(containerId);
            
            // Verificar cache primeiro
            if (tarefasCache[modulo]) {
                renderizarTarefas(tarefasCache[modulo], container, tipo);
                return;
            }

            // Mostrar loading
            container.innerHTML = '<p style="color: #6b7280; font-size: 14px;"><i data-lucide="loader-2" style="animation: spin 1s linear infinite;"></i> Carregando tarefas...</p>';
            if (typeof lucide !== 'undefined') lucide.createIcons();

            try {
                const response = await fetch(`api/get_tarefas_modulo.php?modulo=${modulo}`);
                if (!response.ok) throw new Error('Erro ao carregar tarefas');
                
                const tarefas = await response.json();
                tarefasCache[modulo] = tarefas; // Salvar no cache
                renderizarTarefas(tarefas, container, tipo);
                
            } catch (error) {
                console.error('Erro ao carregar tarefas:', error);
                container.innerHTML = '<p style="color: #dc2626; font-size: 14px;">Erro ao carregar tarefas</p>';
            }
        }

        // Renderizar lista de tarefas
        function renderizarTarefas(tarefas, container, tipo) {
            if (!tarefas || tarefas.length === 0) {
                container.innerHTML = '<p style="color: #6b7280; font-style: italic; font-size: 14px;">Nenhuma tarefa disponível</p>';
                return;
            }

            const html = tarefas.map((tarefa, index) => `
                <label style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 12px; cursor: pointer; padding: 8px; border-radius: 6px; transition: background-color 0.2s;" 
                       onmouseover="this.style.backgroundColor='#f0fdf4'" 
                       onmouseout="this.style.backgroundColor='transparent'">
                    <input type="checkbox" 
                           name="tarefas_selecionadas[]" 
                           value="${tarefa.id}" 
                           style="margin-top: 2px;">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: #374151; font-size: 14px; margin-bottom: 2px;">
                            ${tarefa.ordem}. ${tarefa.nome_tarefa}
                        </div>
                        <div style="font-size: 12px; color: #6b7280; line-height: 1.4;">
                            ${tarefa.descricao}
                        </div>
                    </div>
                </label>
            `).join('');

            container.innerHTML = html;
        }

        // CSS para animação de loading (adicionar ao head se não existir)
        if (!document.getElementById('loading-animation-style')) {
            const style = document.createElement('style');
            style.id = 'loading-animation-style';
            style.textContent = `
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
    </script>
</body>
</html>