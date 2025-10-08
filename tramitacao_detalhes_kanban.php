<?php
require_once 'config.php';
require_once 'functions.php';

verificarLogin();

$pdo = conectarDB();
$usuario_id = $_SESSION['usuario_id'];
$usuario_nivel = $_SESSION['usuario_nivel'];

$tramitacao_id = intval($_GET['id'] ?? 0);

if (!$tramitacao_id) {
    header('Location: tramitacao_kanban.php');
    exit;
}

// Buscar detalhes da tramitação
$query = "SELECT * FROM v_tramitacoes_kanban WHERE id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$tramitacao_id]);
$tramitacao = $stmt->fetch();

if (!$tramitacao) {
    header('Location: tramitacao_kanban.php');
    exit;
}

// Verificar permissão de acesso
$pode_visualizar = ($usuario_nivel == 1) || // Coordenador
                   ($tramitacao['usuario_criador_id'] == $usuario_id) || // Criador
                   ($tramitacao['usuario_responsavel_id'] == $usuario_id) || // Responsável
                   ($usuario_nivel == 4); // Visitante pode ver tudo

if (!$pode_visualizar) {
    header('Location: tramitacao_kanban.php');
    exit;
}

// Buscar histórico
$hist_query = "
    SELECT th.*, u.nome as usuario_nome, u.email as usuario_email
    FROM tramitacoes_historico th
    LEFT JOIN usuarios u ON th.usuario_id = u.id
    WHERE th.tramitacao_id = ?
    ORDER BY th.criado_em DESC
";
$stmt_hist = $pdo->prepare($hist_query);
$stmt_hist->execute([$tramitacao_id]);
$historico = $stmt_hist->fetchAll();

// Buscar comentários
$coment_query = "
    SELECT tc.*, u.nome as usuario_nome, u.email as usuario_email
    FROM tramitacoes_comentarios tc
    LEFT JOIN usuarios u ON tc.usuario_id = u.id
    WHERE tc.tramitacao_id = ?
    ORDER BY tc.criado_em ASC
";
$stmt_coment = $pdo->prepare($coment_query);
$stmt_coment->execute([$tramitacao_id]);
$comentarios = $stmt_coment->fetchAll();

// Verificar permissões de ação
$pode_editar = ($usuario_nivel <= 3) && 
               (($tramitacao['usuario_criador_id'] == $usuario_id) || 
                ($tramitacao['usuario_responsavel_id'] == $usuario_id) || 
                ($usuario_nivel == 1));

$pode_comentar = $usuario_nivel <= 3;
$pode_mudar_status = $pode_editar && !in_array($tramitacao['status'], ['CONCLUIDO', 'CANCELADO']);

// Buscar usuários para atribuição
$users_query = "SELECT id, nome, email, departamento FROM usuarios WHERE ativo = 1 ORDER BY nome";
$usuarios = $pdo->query($users_query)->fetchAll();

// Buscar tarefas da tramitação
$tarefas_query = "
    SELECT * FROM v_tramitacoes_tarefas_completa 
    WHERE tramitacao_id = ?
    ORDER BY ordem ASC, tarefa_criada_em ASC
";
$stmt_tarefas = $pdo->prepare($tarefas_query);
$stmt_tarefas->execute([$tramitacao_id]);
$tarefas = $stmt_tarefas->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tramitação <?php echo $tramitacao['numero_tramite']; ?> - Sistema CGLIC</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        .detalhes-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
        }

        .header-detalhes {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .titulo-secao {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
        }

        .subtitulo {
            color: #6b7280;
            font-size: 16px;
            margin: 8px 0 0 0;
        }

        .btn-voltar {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-voltar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(107, 114, 128, 0.3);
        }

        .grid-principal {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .card-principal {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-left: 4px solid;
        }

        .card-principal.urgente { border-left-color: #dc2626; }
        .card-principal.alta { border-left-color: #ea580c; }
        .card-principal.media { border-left-color: #d97706; }
        .card-principal.baixa { border-left-color: #16a34a; }

        .card-sidebar {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            height: fit-content;
        }

        .tramitacao-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .numero-tramite {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-todo { background: #f3f4f6; color: #374151; }
        .status-em_progresso { background: #fef3c7; color: #92400e; }
        .status-aguardando { background: #e0e7ff; color: #3730a3; }
        .status-concluido { background: #dcfce7; color: #166534; }
        .status-cancelado { background: #fee2e2; color: #991b1b; }

        .prioridade-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .prioridade-urgente { background: #fee2e2; color: #dc2626; }
        .prioridade-alta { background: #fed7aa; color: #ea580c; }
        .prioridade-media { background: #fef3c7; color: #d97706; }
        .prioridade-baixa { background: #dcfce7; color: #16a34a; }

        .titulo-tramitacao {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
            line-height: 1.4;
        }

        .descricao-tramitacao {
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 24px;
            white-space: pre-wrap;
            background: #f9fafb;
            padding: 20px;
            border-radius: 12px;
        }

        .fluxo-tramitacao {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            padding: 24px;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 16px;
            margin-bottom: 24px;
        }

        .modulo-box {
            background: white;
            padding: 16px 24px;
            border-radius: 12px;
            text-align: center;
            font-weight: 600;
            color: #1f2937;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            min-width: 120px;
        }

        .modulo-origem {
            border-left: 4px solid #3b82f6;
        }

        .modulo-destino {
            border-left: 4px solid #10b981;
        }

        .seta-fluxo {
            font-size: 24px;
            color: #6b7280;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .info-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
        }

        .info-value {
            font-size: 16px;
            color: #1f2937;
            font-weight: 500;
        }

        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 24px;
        }

        .tag {
            background: #e5e7eb;
            color: #374151;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .acoes-container {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .btn-acao {
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

        .btn-iniciar { background: #10b981; color: white; }
        .btn-pausar { background: #f59e0b; color: white; }
        .btn-concluir { background: #8b5cf6; color: white; }
        .btn-cancelar { background: #ef4444; color: white; }

        .btn-acao:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .prazo-info {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
        }

        .prazo-info.atrasado {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .prazo-info.vencendo {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #ea580c;
        }

        .prazo-info.no_prazo {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }

        .tabs-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .tabs-header {
            display: flex;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }

        .tab-button {
            flex: 1;
            padding: 16px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #6b7280;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .tab-button.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
            background: white;
        }

        .tab-content {
            display: none;
            padding: 24px;
        }

        .tab-content.active {
            display: block;
        }

        .comentarios-lista {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 24px;
        }

        .comentario-item {
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 16px;
            background: #fafafa;
        }

        .novo-comentario {
            border-top: 1px solid #e5e7eb;
            padding-top: 24px;
        }

        @media (max-width: 768px) {
            .grid-principal {
                grid-template-columns: 1fr;
            }

            .header-detalhes {
                flex-direction: column;
                align-items: stretch;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .fluxo-tramitacao {
                flex-direction: column;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="detalhes-container">
        <!-- Header -->
        <div class="header-detalhes">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px; flex-wrap: wrap;">
                    <a href="selecao_modulos.php" style="background: #e5e7eb; color: #374151; padding: 8px 12px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600;">
                        <i data-lucide="home" size="16"></i>
                        Menu
                    </a>
                    <i data-lucide="chevron-right" size="16" style="color: #9ca3af;"></i>
                    <a href="tramitacao_kanban.php" style="background: #ddd6fe; color: #7c3aed; padding: 8px 12px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600;">
                        <i data-lucide="kanban-square" size="16"></i>
                        Kanban
                    </a>
                </div>
                <h1 class="titulo-secao">
                    <i data-lucide="file-text"></i>
                    Tramitação <?php echo $tramitacao['numero_tramite']; ?>
                </h1>
                <p class="subtitulo">Criada em <?php echo date('d/m/Y \à\s H:i', strtotime($tramitacao['criado_em'])); ?></p>
            </div>
            <a href="tramitacao_kanban.php" class="btn-voltar">
                <i data-lucide="arrow-left"></i>
                Voltar ao Kanban
            </a>
        </div>

        <!-- Grid Principal -->
        <div class="grid-principal">
            <!-- Card Principal -->
            <div class="card-principal <?php echo strtolower($tramitacao['prioridade']); ?>">
                <div class="tramitacao-header">
                    <div class="numero-tramite">Trâmite <?php echo $tramitacao['numero_tramite']; ?></div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <span class="prioridade-badge prioridade-<?php echo strtolower($tramitacao['prioridade']); ?>">
                            <?php echo $tramitacao['prioridade']; ?>
                        </span>
                        <span class="status-badge status-<?php echo strtolower($tramitacao['status']); ?>">
                            <?php echo str_replace('_', ' ', $tramitacao['status']); ?>
                        </span>
                    </div>
                </div>

                <h2 class="titulo-tramitacao"><?php echo htmlspecialchars($tramitacao['titulo']); ?></h2>

                <div class="fluxo-tramitacao">
                    <div class="modulo-box modulo-origem">
                        <i data-lucide="send"></i><br>
                        <?php echo $tramitacao['modulo_origem']; ?>
                    </div>
                    <div class="seta-fluxo">
                        <i data-lucide="arrow-right"></i>
                    </div>
                    <div class="modulo-box modulo-destino">
                        <i data-lucide="inbox"></i><br>
                        <?php echo $tramitacao['modulo_destino']; ?>
                    </div>
                </div>

                <div class="descricao-tramitacao">
                    <?php echo htmlspecialchars($tramitacao['descricao']); ?>
                </div>

                <?php if ($tramitacao['observacoes']): ?>
                <div style="background: #f0f9ff; padding: 16px; border-radius: 8px; margin-bottom: 24px; border-left: 4px solid #0ea5e9;">
                    <strong style="color: #0c4a6e;">Observações:</strong><br>
                    <span style="color: #0369a1;"><?php echo htmlspecialchars($tramitacao['observacoes']); ?></span>
                </div>
                <?php endif; ?>

                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Tipo de Demanda</span>
                        <span class="info-value"><?php echo htmlspecialchars($tramitacao['tipo_demanda']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Criado por</span>
                        <span class="info-value"><?php echo htmlspecialchars($tramitacao['usuario_criador_nome']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Responsável</span>
                        <span class="info-value">
                            <?php echo $tramitacao['usuario_responsavel_nome'] ? htmlspecialchars($tramitacao['usuario_responsavel_nome']) : 'Não atribuído'; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Última Atualização</span>
                        <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($tramitacao['atualizado_em'])); ?></span>
                    </div>
                </div>

                <?php 
                if ($tramitacao['tags']) {
                    $tags = json_decode($tramitacao['tags'], true);
                    if ($tags && is_array($tags)):
                ?>
                <div class="tags-container">
                    <?php foreach ($tags as $tag): ?>
                        <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; } ?>

                <?php if ($pode_mudar_status): ?>
                <div class="acoes-container">
                    <?php if ($tramitacao['status'] == 'TODO'): ?>
                        <button class="btn-acao btn-iniciar" onclick="mudarStatus('EM_PROGRESSO')">
                            <i data-lucide="play"></i>
                            Iniciar
                        </button>
                    <?php endif; ?>

                    <?php if ($tramitacao['status'] == 'EM_PROGRESSO'): ?>
                        <button class="btn-acao btn-pausar" onclick="mudarStatus('AGUARDANDO')">
                            <i data-lucide="pause"></i>
                            Pausar
                        </button>
                        <button class="btn-acao btn-concluir" onclick="mudarStatus('CONCLUIDO')">
                            <i data-lucide="check"></i>
                            Concluir
                        </button>
                    <?php endif; ?>

                    <?php if ($tramitacao['status'] == 'AGUARDANDO'): ?>
                        <button class="btn-acao btn-iniciar" onclick="mudarStatus('EM_PROGRESSO')">
                            <i data-lucide="play"></i>
                            Retomar
                        </button>
                    <?php endif; ?>

                    <?php if (in_array($tramitacao['status'], ['TODO', 'EM_PROGRESSO', 'AGUARDANDO'])): ?>
                        <button class="btn-acao btn-cancelar" onclick="mudarStatus('CANCELADO')">
                            <i data-lucide="x"></i>
                            Cancelar
                        </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="card-sidebar">
                <?php if ($tramitacao['prazo_limite']): ?>
                <div class="prazo-info <?php echo $tramitacao['situacao_prazo'] == 'ATRASADO' ? 'atrasado' : ($tramitacao['situacao_prazo'] == 'VENCENDO' ? 'vencendo' : 'no_prazo'); ?>">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 8px;">
                        <i data-lucide="clock"></i>
                        <strong>Prazo</strong>
                    </div>
                    <div style="font-size: 18px; font-weight: 700; margin-bottom: 4px;">
                        <?php echo date('d/m/Y H:i', strtotime($tramitacao['prazo_limite'])); ?>
                    </div>
                    <div>
                        <?php
                        if ($tramitacao['situacao_prazo'] == 'ATRASADO') {
                            echo abs($tramitacao['dias_restantes']) . ' dia(s) em atraso';
                        } else if ($tramitacao['situacao_prazo'] == 'VENCENDO') {
                            echo 'Vence em ' . $tramitacao['dias_restantes'] . ' dia(s)';
                        } else {
                            echo $tramitacao['dias_restantes'] . ' dia(s) restantes';
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>

                <div style="margin-top: 24px;">
                    <h3 style="margin-bottom: 16px; display: flex; align-items: center; gap: 8px; font-size: 18px;">
                        <i data-lucide="activity"></i>
                        Atividade Recente
                    </h3>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <?php foreach (array_slice($historico, 0, 5) as $item): ?>
                        <div style="margin-bottom: 16px; padding: 12px; background: #f9fafb; border-radius: 8px; border-left: 3px solid #3b82f6;">
                            <div style="font-weight: 600; color: #374151; margin-bottom: 4px;">
                                <?php echo htmlspecialchars($item['acao']); ?>
                            </div>
                            <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">
                                <?php echo htmlspecialchars($item['usuario_nome']); ?> • 
                                <?php echo date('d/m H:i', strtotime($item['criado_em'])); ?>
                            </div>
                            <?php if ($item['observacao']): ?>
                            <div style="color: #4b5563; font-size: 14px;">
                                <?php echo htmlspecialchars($item['observacao']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Inferior -->
        <div class="tabs-container">
            <div class="tabs-header">
                <button class="tab-button active" onclick="showTab('historico')">
                    <i data-lucide="history"></i>
                    Histórico Completo
                </button>
                <button class="tab-button" onclick="showTab('comentarios')">
                    <i data-lucide="message-square"></i>
                    Comentários (<?php echo count($comentarios); ?>)
                </button>
                <button class="tab-button" onclick="showTab('tarefas')">
                    <i data-lucide="check-square"></i>
                    Tarefas (<?php echo count($tarefas); ?>)
                </button>
            </div>

            <!-- Tab Histórico -->
            <div class="tab-content active" id="tab-historico">
                <div style="position: relative;">
                    <?php if (empty($historico)): ?>
                        <div style="text-align: center; padding: 40px; color: #6b7280;">
                            <i data-lucide="clock" size="48"></i>
                            <p>Ainda não há histórico para esta tramitação.</p>
                        </div>
                    <?php else: ?>
                        <div style="position: relative; padding-left: 40px;">
                            <div style="position: absolute; left: 20px; top: 0; bottom: 0; width: 2px; background: #e5e7eb;"></div>
                            <?php foreach ($historico as $item): ?>
                            <div style="position: relative; margin-bottom: 24px;">
                                <div style="position: absolute; left: -30px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: #3b82f6; border: 2px solid white; box-shadow: 0 0 0 2px #e5e7eb;"></div>
                                <div>
                                    <div style="font-weight: 600; color: #1f2937; margin-bottom: 4px;">
                                        <?php echo htmlspecialchars($item['acao']); ?>
                                        <?php if ($item['valor_anterior'] && $item['valor_novo']): ?>
                                            (<?php echo $item['valor_anterior']; ?> → <?php echo $item['valor_novo']; ?>)
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 14px; color: #6b7280; margin-bottom: 4px;">
                                        Por <?php echo htmlspecialchars($item['usuario_nome']); ?> em 
                                        <?php echo date('d/m/Y \à\s H:i', strtotime($item['criado_em'])); ?>
                                    </div>
                                    <?php if ($item['observacao']): ?>
                                    <div style="color: #4b5563; background: #f9fafb; padding: 8px; border-radius: 6px; margin-top: 8px;">
                                        <?php echo htmlspecialchars($item['observacao']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tab Comentários -->
            <div class="tab-content" id="tab-comentarios">
                <?php if (count($comentarios) > 0): ?>
                <div class="comentarios-lista">
                    <?php foreach ($comentarios as $comentario): ?>
                    <div class="comentario-item">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-weight: 600; color: #1f2937;"><?php echo htmlspecialchars($comentario['usuario_nome']); ?></span>
                            <span style="font-size: 12px; color: #6b7280;"><?php echo date('d/m/Y H:i', strtotime($comentario['criado_em'])); ?></span>
                        </div>
                        <div style="color: #4b5563; line-height: 1.5; white-space: pre-wrap;">
                            <?php echo htmlspecialchars($comentario['comentario']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #6b7280;">
                    <i data-lucide="message-circle" size="48"></i>
                    <p>Ainda não há comentários nesta tramitação.</p>
                </div>
                <?php endif; ?>

                <?php if ($pode_comentar): ?>
                <div class="novo-comentario">
                    <h4 style="margin-bottom: 12px; color: #1f2937;">Adicionar Comentário</h4>
                    <form method="POST" action="process.php" style="display: flex; flex-direction: column; gap: 12px;">
                        <input type="hidden" name="action" value="comentar_tramitacao_kanban">
                        <input type="hidden" name="tramitacao_id" value="<?php echo $tramitacao_id; ?>">
                        <input type="hidden" name="redirect" value="tramitacao_detalhes_kanban.php?id=<?php echo $tramitacao_id; ?>">
                        
                        <textarea name="comentario" placeholder="Digite seu comentário..." required 
                                  style="min-height: 100px; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; resize: vertical; font-family: inherit;"></textarea>
                        
                        <button type="submit" style="align-self: flex-end; background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="send" size="16"></i>
                            Enviar Comentário
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tab Tarefas -->
            <div class="tab-content" id="tab-tarefas">
                <?php if (count($tarefas) > 0): ?>
                    <div style="display: grid; gap: 20px;">
                        <?php 
                        $modulos_tarefas = [];
                        foreach ($tarefas as $tarefa) {
                            $modulos_tarefas[$tarefa['modulo']][] = $tarefa;
                        }
                        ?>
                        
                        <?php foreach ($modulos_tarefas as $modulo => $tarefas_modulo): ?>
                            <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
                                <div style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; padding: 16px; display: flex; align-items: center; gap: 12px;">
                                    <i data-lucide="folder" size="20"></i>
                                    <h3 style="margin: 0; font-size: 18px; font-weight: 700;">
                                        <?php echo $modulo; ?>
                                    </h3>
                                    <div style="margin-left: auto; background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 14px; font-weight: 600;">
                                        <?php echo count($tarefas_modulo); ?> tarefa(s)
                                    </div>
                                </div>
                                
                                <div style="padding: 20px;">
                                    <div style="display: grid; gap: 16px;">
                                        <?php foreach ($tarefas_modulo as $tarefa): ?>
                                            <div class="tarefa-item" style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; position: relative;">
                                                <div style="display: flex; align-items: flex-start; gap: 16px;">
                                                    <!-- Status da tarefa -->
                                                    <div class="tarefa-status-icon" style="flex-shrink: 0; margin-top: 2px;">
                                                        <?php
                                                        $status_colors = [
                                                            'INICIANDO' => '#f59e0b',
                                                            'EM_ANDAMENTO' => '#3b82f6',
                                                            'CONCLUIDA' => '#10b981',
                                                            'CANCELADA' => '#ef4444'
                                                        ];
                                                        $status_icons = [
                                                            'INICIANDO' => 'circle',
                                                            'EM_ANDAMENTO' => 'play-circle',
                                                            'CONCLUIDA' => 'check-circle',
                                                            'CANCELADA' => 'x-circle'
                                                        ];
                                                        $cor = $status_colors[$tarefa['estagio']] ?? '#6b7280';
                                                        $icone = $status_icons[$tarefa['estagio']] ?? 'circle';
                                                        ?>
                                                        <i data-lucide="<?php echo $icone; ?>" style="color: <?php echo $cor; ?>;" size="20"></i>
                                                    </div>
                                                    
                                                    <!-- Conteúdo da tarefa -->
                                                    <div style="flex: 1;">
                                                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                                            <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: #1f2937;">
                                                                <?php echo $tarefa['ordem']; ?>. <?php echo htmlspecialchars($tarefa['nome_tarefa']); ?>
                                                            </h4>
                                                            <span class="tarefa-badge" style="background: <?php echo $cor; ?>; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: uppercase;">
                                                                <?php echo str_replace('_', ' ', $tarefa['estagio']); ?>
                                                            </span>
                                                        </div>
                                                        
                                                        <p style="color: #6b7280; font-size: 14px; margin-bottom: 12px; line-height: 1.5;">
                                                            <?php echo htmlspecialchars($tarefa['tarefa_descricao']); ?>
                                                        </p>
                                                        
                                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 12px;">
                                                            <?php if ($tarefa['responsavel_nome']): ?>
                                                            <div style="display: flex; align-items: center; gap: 6px; color: #4b5563; font-size: 14px;">
                                                                <i data-lucide="user" size="16"></i>
                                                                <strong>Responsável:</strong> <?php echo htmlspecialchars($tarefa['responsavel_nome']); ?>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($tarefa['data_inicio']): ?>
                                                            <div style="display: flex; align-items: center; gap: 6px; color: #4b5563; font-size: 14px;">
                                                                <i data-lucide="play" size="16"></i>
                                                                <strong>Iniciado:</strong> <?php echo date('d/m/Y H:i', strtotime($tarefa['data_inicio'])); ?>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($tarefa['data_conclusao']): ?>
                                                            <div style="display: flex; align-items: center; gap: 6px; color: #4b5563; font-size: 14px;">
                                                                <i data-lucide="check" size="16"></i>
                                                                <strong>Concluído:</strong> <?php echo date('d/m/Y H:i', strtotime($tarefa['data_conclusao'])); ?>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($tarefa['dias_duracao']): ?>
                                                            <div style="display: flex; align-items: center; gap: 6px; color: #4b5563; font-size: 14px;">
                                                                <i data-lucide="clock" size="16"></i>
                                                                <strong>Duração:</strong> <?php echo $tarefa['dias_duracao']; ?> dia(s)
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <?php if ($tarefa['observacoes']): ?>
                                                        <div style="background: #f0f9ff; border-left: 4px solid #0ea5e9; padding: 12px; border-radius: 6px; margin-bottom: 12px;">
                                                            <strong style="color: #0c4a6e; font-size: 14px;">Observações:</strong><br>
                                                            <span style="color: #0369a1; font-size: 14px;"><?php echo htmlspecialchars($tarefa['observacoes']); ?></span>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <!-- Progresso visual -->
                                                    <div style="flex-shrink: 0; text-align: center;">
                                                        <div style="width: 50px; height: 50px; border-radius: 50%; background: conic-gradient(<?php echo $cor; ?> <?php echo $tarefa['progresso_percentual']; ?>%, #e5e7eb 0); display: flex; align-items: center; justify-content: center;">
                                                            <div style="width: 35px; height: 35px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; color: <?php echo $cor; ?>;">
                                                                <?php echo $tarefa['progresso_percentual']; ?>%
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Ações da tarefa (se tiver permissão) -->
                                                <?php if ($pode_editar && in_array($tarefa['estagio'], ['INICIANDO', 'EM_ANDAMENTO'])): ?>
                                                <div style="display: flex; gap: 8px; margin-top: 16px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
                                                    <?php if ($tarefa['estagio'] == 'INICIANDO'): ?>
                                                        <button onclick="alterarEstagio(<?php echo $tarefa['id']; ?>, 'EM_ANDAMENTO')" 
                                                                style="background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                                            <i data-lucide="play" size="14"></i> Iniciar
                                                        </button>
                                                    <?php elseif ($tarefa['estagio'] == 'EM_ANDAMENTO'): ?>
                                                        <button onclick="alterarEstagio(<?php echo $tarefa['id']; ?>, 'CONCLUIDA')" 
                                                                style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                                            <i data-lucide="check" size="14"></i> Concluir
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <button onclick="alterarEstagio(<?php echo $tarefa['id']; ?>, 'CANCELADA')" 
                                                            style="background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                                        <i data-lucide="x" size="14"></i> Cancelar
                                                    </button>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px; color: #6b7280;">
                        <i data-lucide="clipboard-list" size="64" style="opacity: 0.5; margin-bottom: 16px;"></i>
                        <h3 style="margin-bottom: 8px; color: #374151;">Nenhuma Tarefa Encontrada</h3>
                        <p>Esta tramitação não possui tarefas associadas.</p>
                        <?php if ($pode_editar): ?>
                        <button onclick="adicionarTarefas()" style="margin-top: 16px; background: #3b82f6; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            <i data-lucide="plus"></i> Adicionar Tarefas
                        </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Esconder todas as tabs
            const tabs = document.querySelectorAll('.tab-content');
            const buttons = document.querySelectorAll('.tab-button');
            
            tabs.forEach(tab => tab.classList.remove('active'));
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Mostrar tab selecionada
            document.getElementById(`tab-${tabName}`).classList.add('active');
            event.target.classList.add('active');
        }

        function mudarStatus(novoStatus) {
            if (confirm(`Confirma a alteração do status para ${novoStatus.replace('_', ' ')}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'process.php';
                form.innerHTML = `
                    <input type="hidden" name="action" value="alterar_status_tramitacao_kanban">
                    <input type="hidden" name="tramitacao_id" value="<?php echo $tramitacao_id; ?>">
                    <input type="hidden" name="novo_status" value="${novoStatus}">
                    <input type="hidden" name="redirect" value="tramitacao_detalhes_kanban.php?id=<?php echo $tramitacao_id; ?>">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Função para alterar estágio da tarefa
        function alterarEstagio(tarefaId, novoEstagio) {
            const estagioTexto = novoEstagio.replace('_', ' ').toLowerCase();
            if (confirm(`Confirma alterar tarefa para "${estagioTexto}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'process.php';
                form.innerHTML = `
                    <input type="hidden" name="action" value="alterar_estagio_tarefa">
                    <input type="hidden" name="tarefa_id" value="${tarefaId}">
                    <input type="hidden" name="novo_estagio" value="${novoEstagio}">
                    <input type="hidden" name="redirect" value="tramitacao_detalhes_kanban.php?id=<?php echo $tramitacao_id; ?>">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Função para adicionar tarefas (placeholder)
        function adicionarTarefas() {
            alert('Funcionalidade de adicionar tarefas será implementada em breve!');
        }

        // Inicializar ícones Lucide
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>