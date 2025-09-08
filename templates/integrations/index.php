<?php
// Verificar autenticação
require_once '../config/app.php';
require_once '../app/controllers/AuthController.php';

$auth = new AuthController();
$auth->requireAuth();

// Buscar dados do usuário
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$user_data = $_SESSION['user'];
$integration = new Integration($db);
$product = new Product($db);
$sale = new Sale($db);

// Buscar integrações do usuário
$integrations = $integration->getByUser($user_data['id']);

// Buscar estatísticas gerais
$stats = $sale->getUserStats($user_data['id'], 30);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IntegraSync - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body style="background: #f9fafb;">
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="nav">
                <a href="<?= BASE_URL ?>/dashboard" class="nav-brand">
                    <div style="width: 32px; height: 32px; background: var(--color-primary); border-radius: 6px;"></div>
                    Mercado Afiliado
                </a>
                <ul class="nav-links">
                    <li>
                        <span style="color: var(--color-gray);">
                            Olá, <?= htmlspecialchars(explode(' ', $user_data['name'])[0]) ?>
                        </span>
                    </li>
                    <li><a href="<?= BASE_URL ?>/logout">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container" style="display: grid; grid-template-columns: 250px 1fr; gap: 2rem; margin-top: 2rem;">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="<?= BASE_URL ?>/dashboard">📊 Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>/unified-panel">📈 Painel Unificado</a></li>
                <li><a href="<?= BASE_URL ?>/integrations" class="active">🔗 IntegraSync</a></li>
                <li><a href="<?= BASE_URL ?>/link-maestro">🎯 Link Maestro</a></li>
                <li><a href="<?= BASE_URL ?>/pixel">🎯 Pixel BR</a></li>
                <li><a href="#" onclick="showComingSoon('Alerta Queda')">🚨 Alerta Queda</a></li>
                <li><a href="#" onclick="showComingSoon('CAPI Bridge')">🌉 CAPI Bridge</a></li>
                <li><a href="#" onclick="showComingSoon('Cohort Reembolso')">💰 Cohort Reembolso</a></li>
                <li><a href="#" onclick="showComingSoon('Offer Radar')">🎯 Offer Radar</a></li>
                <li><a href="#" onclick="showComingSoon('UTM Templates')">🏷️ UTM Templates</a></li>
                <li><a href="#" onclick="showComingSoon('Equipe')">👥 Equipe & Permissões</a></li>
                <li><a href="#" onclick="showComingSoon('Exportar')">📋 Exporta+</a></li>
                <li><a href="#" onclick="showComingSoon('Trilhas')">🎓 Trilhas Rápidas</a></li>
                <li><a href="#" onclick="showComingSoon('LGPD')">🛡️ Auditoria LGPD</a></li>
            </ul>
        </aside>

        <!-- Conteúdo principal -->
        <main>
            <!-- Header da página -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="font-size: var(--font-size-3xl); font-weight: 800; margin-bottom: 0.5rem;">🔗 IntegraSync</h1>
                    <p style="color: var(--color-gray);">Conexões e webhooks estáveis com as redes de afiliados</p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <a href="<?= BASE_URL ?>/integrations/add" class="btn btn-primary">
                        + Nova integração
                    </a>
                    <a href="<?= BASE_URL ?>/integrations/test" class="btn btn-secondary">
                        🧪 Teste & Logs
                    </a>
                </div>
            </div>

            <!-- Cards de estatísticas -->
            <?php if ($stats): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: var(--font-size-2xl); font-weight: 800; color: var(--color-primary);">
                            <?= number_format($stats['total_sales'] ?? 0) ?>
                        </div>
                        <div style="color: var(--color-gray); margin-top: 0.5rem; font-size: var(--font-size-sm);">Vendas (30 dias)</div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: var(--font-size-2xl); font-weight: 800; color: #10b981;">
                            <?= number_format($stats['approved_sales'] ?? 0) ?>
                        </div>
                        <div style="color: var(--color-gray); margin-top: 0.5rem; font-size: var(--font-size-sm);">Aprovadas</div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: var(--font-size-2xl); font-weight: 800; color: var(--color-primary);">
                            R$ <?= number_format($stats['total_revenue'] ?? 0, 2, ',', '.') ?>
                        </div>
                        <div style="color: var(--color-gray); margin-top: 0.5rem; font-size: var(--font-size-sm);">Receita total</div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: var(--font-size-2xl); font-weight: 800; color: #8b5cf6;">
                            R$ <?= number_format($stats['total_commission'] ?? 0, 2, ',', '.') ?>
                        </div>
                        <div style="color: var(--color-gray); margin-top: 0.5rem; font-size: var(--font-size-sm);">Comissões</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Lista de integrações -->
            <div class="card">
                <div class="card-header">
                    <h2>Suas integrações</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($integrations)): ?>
                        <!-- Estado vazio -->
                        <div style="text-align: center; padding: 3rem 1rem;">
                            <div style="font-size: 4rem; margin-bottom: 1rem;">🔗</div>
                            <h3 style="font-weight: 600; margin-bottom: 0.5rem;">Nenhuma integração configurada</h3>
                            <p style="color: var(--color-gray); margin-bottom: 2rem;">
                                Conecte com Hotmart, Monetizze, Eduzz ou Braip para começar a sincronizar suas vendas.
                            </p>
                            <a href="<?= BASE_URL ?>/integrations/add" class="btn btn-primary">
                                🚀 Configurar primeira integração
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Lista de integrações -->
                        <div style="display: grid; gap: 1rem;">
                            <?php foreach ($integrations as $int): ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div style="width: 48px; height: 48px; background: var(--color-primary); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--color-dark);">
                                            <?= strtoupper(substr($int['platform'], 0, 2)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600;"><?= htmlspecialchars($int['name']) ?></div>
                                            <div style="font-size: var(--font-size-sm); color: var(--color-gray);">
                                                <?= ucfirst($int['platform']) ?> • 
                                                <span style="color: <?= $int['status'] === 'active' ? '#10b981' : '#ef4444' ?>;">
                                                    <?php if ($int['status'] === 'active'): ?>
                                                        ✅ Ativa e configurada
                                                    <?php else: ?>
                                                        ⚙️ <?= ucfirst($int['status']) ?> - Clique em ⚙️ para configurar
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <?php if ($int['status'] !== 'active'): ?>
                                        <button class="btn" 
                                                style="background: #3b82f6; color: white; padding: 0.5rem;" 
                                                onclick="showConfigModal(<?= $int['id'] ?>, '<?= htmlspecialchars($int['name']) ?>', '<?= $int['platform'] ?>')"
                                                title="Configurar credenciais da API e webhooks">
                                            ⚙️
                                        </button>
                                        <?php endif; ?>
                                        <button class="btn" 
                                                style="background: #10b981; color: white; padding: 0.5rem;" 
                                                onclick="showSyncModal(<?= $int['id'] ?>, '<?= htmlspecialchars($int['name']) ?>', '<?= $int['platform'] ?>')"
                                                title="Sincronizar produtos e vendas desta integração">
                                            🔄
                                        </button>
                                        <button class="btn" 
                                                style="background: #ef4444; color: white; padding: 0.5rem;" 
                                                onclick="showDeleteModal(<?= $int['id'] ?>, '<?= htmlspecialchars($int['name']) ?>', '<?= $int['platform'] ?>')"
                                                title="Excluir integração permanentemente">
                                            🗑️
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Plataformas disponíveis -->
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h2>Plataformas suportadas</h2>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
                            <div style="width: 48px; height: 48px; background: #3b82f6; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">HM</div>
                            <div>
                                <div style="font-weight: 600;">Hotmart</div>
                                <div style="font-size: var(--font-size-sm); color: var(--color-gray);">API v2 + Webhooks</div>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
                            <div style="width: 48px; height: 48px; background: #10b981; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">MZ</div>
                            <div>
                                <div style="font-weight: 600;">Monetizze</div>
                                <div style="font-size: var(--font-size-sm); color: var(--color-gray);">API + Webhooks</div>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
                            <div style="width: 48px; height: 48px; background: #f59e0b; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">ED</div>
                            <div>
                                <div style="font-weight: 600;">Eduzz</div>
                                <div style="font-size: var(--font-size-sm); color: var(--color-gray);">API + Webhooks</div>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
                            <div style="width: 48px; height: 48px; background: #8b5cf6; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">BR</div>
                            <div>
                                <div style="font-weight: 600;">Braip</div>
                                <div style="font-size: var(--font-size-sm); color: var(--color-gray);">API + Webhooks</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal de configuração -->
    <div id="configModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 0.5rem; padding: 2rem; min-width: 500px; max-width: 600px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; font-size: var(--font-size-lg); font-weight: 600;">⚙️ Configurar Integração</h3>
                <button onclick="closeConfigModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            
            <div id="configModalContent">
                <form id="configForm">
                    <div style="margin-bottom: 1.5rem;">
                        <div style="font-weight: 600; margin-bottom: 0.5rem;" id="configIntegrationName">Nome da Integração</div>
                        <div style="color: var(--color-gray); font-size: var(--font-size-sm); margin-bottom: 1rem;" id="configIntegrationPlatform">Plataforma</div>
                        
                        <div style="background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <span style="color: #0ea5e9;">ℹ️</span>
                                <strong style="color: #0369a1;">Webhook URL</strong>
                            </div>
                            <div style="font-size: var(--font-size-sm); color: #0369a1; word-break: break-all;" id="webhookUrl">
                                Configure suas credenciais para gerar a URL do webhook
                            </div>
                            <div style="font-size: var(--font-size-xs); color: var(--color-gray); margin-top: 0.5rem;">
                                Use esta URL na plataforma para receber notificações automáticas de vendas
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Nome da integração:</label>
                        <input type="text" id="configName" required style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.375rem;" placeholder="Ex: Minha loja Hotmart">
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">API Key:</label>
                        <input type="text" id="configApiKey" required style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.375rem;" placeholder="Sua API Key">
                        <div style="font-size: var(--font-size-sm); color: var(--color-gray); margin-top: 0.25rem;">
                            Encontre sua API Key no painel da plataforma
                        </div>
                    </div>
                    
                    <div id="apiSecretField" style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">API Secret:</label>
                        <input type="password" id="configApiSecret" style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.375rem;" placeholder="Sua API Secret">
                        <div style="font-size: var(--font-size-sm); color: var(--color-gray); margin-top: 0.25rem;">
                            Obrigatório para algumas plataformas como Hotmart
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" id="validateCredentials" checked style="margin: 0;">
                            <span>Validar credenciais antes de salvar</span>
                        </label>
                        <div style="font-size: var(--font-size-sm); color: var(--color-gray); margin-top: 0.25rem;">
                            Recomendado: testa a conexão com a API antes de salvar
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button type="button" onclick="closeConfigModal()" class="btn" style="background: #f3f4f6; color: var(--color-gray);">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="saveConfigButton">💾 Salvar Configuração</button>
                    </div>
                </form>
            </div>
            
            <div id="configProgress" style="display: none;">
                <div style="text-align: center; padding: 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">⏳</div>
                    <div style="font-weight: 600; margin-bottom: 0.5rem;">Salvando configuração...</div>
                    <div style="color: var(--color-gray); font-size: var(--font-size-sm);" id="configStatus">Validando credenciais</div>
                </div>
            </div>
            
            <div id="configResult" style="display: none;">
                <div id="configResultContent"></div>
                <div style="display: flex; justify-content: center; margin-top: 1.5rem;">
                    <button onclick="closeConfigModal()" class="btn btn-primary">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de sincronização -->
    <div id="syncModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 0.5rem; padding: 2rem; min-width: 400px; max-width: 500px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; font-size: var(--font-size-lg); font-weight: 600;">🔄 Sincronizar Integração</h3>
                <button onclick="closeSyncModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            
            <div id="syncModalContent">
                <div style="margin-bottom: 1.5rem;">
                    <div style="font-weight: 600; margin-bottom: 0.5rem;" id="integrationName">Nome da Integração</div>
                    <div style="color: var(--color-gray); font-size: var(--font-size-sm);" id="integrationPlatform">Plataforma</div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Tipo de sincronização:</label>
                    <select id="syncType" style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.375rem;">
                        <option value="full">Sincronização completa (produtos + vendas)</option>
                        <option value="products">Apenas produtos</option>
                        <option value="sales">Apenas vendas</option>
                        <option value="test">Testar conexão</option>
                    </select>
                </div>
                
                <div id="daysOption" style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Período para vendas (dias):</label>
                    <input type="number" id="syncDays" value="30" min="1" max="365" style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.375rem;">
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button onclick="closeSyncModal()" class="btn" style="background: #f3f4f6; color: var(--color-gray);">Cancelar</button>
                    <button onclick="startSync()" class="btn btn-primary" id="syncButton">🚀 Iniciar Sincronização</button>
                </div>
            </div>
            
            <div id="syncProgress" style="display: none;">
                <div style="text-align: center; padding: 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">⏳</div>
                    <div style="font-weight: 600; margin-bottom: 0.5rem;">Sincronizando...</div>
                    <div style="color: var(--color-gray); font-size: var(--font-size-sm);" id="syncStatus">Preparando sincronização</div>
                    
                    <div style="background: #f3f4f6; border-radius: 0.5rem; height: 8px; margin: 1.5rem 0; overflow: hidden;">
                        <div id="progressBar" style="background: var(--color-primary); height: 100%; width: 0%; transition: width 0.3s;"></div>
                    </div>
                </div>
            </div>
            
            <div id="syncResult" style="display: none;">
                <div id="syncResultContent"></div>
                <div style="display: flex; justify-content: center; margin-top: 1.5rem;">
                    <button onclick="closeSyncModal()" class="btn btn-primary">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de exclusão -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 0.5rem; padding: 2rem; min-width: 400px; max-width: 500px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; font-size: var(--font-size-lg); font-weight: 600; color: #ef4444;">🗑️ Excluir Integração</h3>
                <button onclick="closeDeleteModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            
            <div id="deleteModalContent">
                <div style="margin-bottom: 1.5rem;">
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; color: #ef4444; font-weight: 600; margin-bottom: 0.5rem;">
                            <span>⚠️</span>
                            <span>Atenção: Esta ação é irreversível!</span>
                        </div>
                        <div style="color: #991b1b; font-size: var(--font-size-sm);">
                            Ao excluir esta integração, todos os dados associados serão perdidos permanentemente, incluindo:
                        </div>
                        <ul style="color: #991b1b; font-size: var(--font-size-sm); margin: 0.5rem 0 0 1rem;">
                            <li>Todas as vendas sincronizadas</li>
                            <li>Todos os produtos importados</li>
                            <li>Histórico de sincronizações</li>
                            <li>Configurações de webhook</li>
                        </ul>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <div style="font-weight: 600; margin-bottom: 0.5rem;">Integração a ser excluída:</div>
                        <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.375rem; padding: 0.75rem;">
                            <div style="font-weight: 600;" id="deleteIntegrationName">Nome da Integração</div>
                            <div style="color: var(--color-gray); font-size: var(--font-size-sm);" id="deleteIntegrationPlatform">Plataforma</div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Digite "EXCLUIR" para confirmar:</label>
                        <input type="text" id="deleteConfirmation" style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.375rem;" placeholder="Digite EXCLUIR em maiúsculas">
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button onclick="closeDeleteModal()" class="btn" style="background: #f3f4f6; color: var(--color-gray);">Cancelar</button>
                    <button onclick="confirmDelete()" class="btn" style="background: #ef4444; color: white;" id="deleteConfirmButton" disabled>🗑️ Excluir Integração</button>
                </div>
            </div>
            
            <div id="deleteProgress" style="display: none;">
                <div style="text-align: center; padding: 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">⏳</div>
                    <div style="font-weight: 600; margin-bottom: 0.5rem;">Excluindo integração...</div>
                    <div style="color: var(--color-gray); font-size: var(--font-size-sm);">Aguarde enquanto removemos todos os dados</div>
                </div>
            </div>
            
            <div id="deleteResult" style="display: none;">
                <div id="deleteResultContent"></div>
                <div style="display: flex; justify-content: center; margin-top: 1.5rem;">
                    <button onclick="closeDeleteModal()" class="btn btn-primary">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentIntegrationId = null;
        let currentConfigIntegrationId = null;
        let currentDeleteIntegrationId = null;

        function showComingSoon(feature) {
            // Se for Link Maestro, redirecionar em vez de mostrar alerta
            if (feature.toLowerCase().includes('link') && feature.toLowerCase().includes('maestro')) {
                const baseUrl = window.location.origin + '/mercado_afiliado';
                window.location.href = baseUrl + '/link-maestro';
                return;
            }
            alert('🚧 ' + feature + ' será implementado na próxima etapa!\n\nEstamos construindo isso agora. Em breve você poderá configurar suas integrações.');
        }

        function showConfigModal(integrationId, name, platform) {
            console.log('Abrindo modal de configuração para:', integrationId, name, platform);
            
            currentConfigIntegrationId = integrationId;
            
            // Update modal content
            document.getElementById('configIntegrationName').textContent = name;
            document.getElementById('configIntegrationPlatform').textContent = platform.charAt(0).toUpperCase() + platform.slice(1);
            document.getElementById('configModal').style.display = 'block';
            
            // Reset modal state
            document.getElementById('configModalContent').style.display = 'block';
            document.getElementById('configProgress').style.display = 'none';
            document.getElementById('configResult').style.display = 'none';
            
            // Show/hide API Secret field based on platform
            const apiSecretField = document.getElementById('apiSecretField');
            if (platform === 'hotmart') {
                apiSecretField.style.display = 'block';
                document.getElementById('configApiSecret').setAttribute('required', true);
            } else {
                apiSecretField.style.display = 'none';
                document.getElementById('configApiSecret').removeAttribute('required');
            }
            
            // Load current integration data
            loadIntegrationConfig(integrationId);
        }

        function closeConfigModal() {
            document.getElementById('configModal').style.display = 'none';
            currentConfigIntegrationId = null;
            
            // Reset form
            document.getElementById('configForm').reset();
            document.getElementById('webhookUrl').textContent = 'Configure suas credenciais para gerar a URL do webhook';
        }

        function loadIntegrationConfig(integrationId) {
            fetch('<?= BASE_URL ?>/api/integration_config.php?integration_id=' + integrationId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const integration = data.integration;
                        
                        // Fill form with current data
                        document.getElementById('configName').value = integration.name || '';
                        document.getElementById('configApiKey').value = integration.api_key_masked || '';
                        document.getElementById('configApiSecret').value = ''; // Never pre-fill passwords
                        
                        // Update webhook URL if exists
                        if (integration.webhook_token) {
                            const webhookUrl = '<?= BASE_URL ?>/api/webhooks/' + integration.platform + '/' + integration.webhook_token;
                            document.getElementById('webhookUrl').textContent = webhookUrl;
                        }
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar configuração:', error);
                });
        }

        // Handle config form submission
        document.getElementById('configForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                integration_id: currentConfigIntegrationId,
                name: document.getElementById('configName').value,
                api_key: document.getElementById('configApiKey').value,
                api_secret: document.getElementById('configApiSecret').value,
                validate_credentials: document.getElementById('validateCredentials').checked
            };
            
            // Show progress
            document.getElementById('configModalContent').style.display = 'none';
            document.getElementById('configProgress').style.display = 'block';
            document.getElementById('configStatus').textContent = 'Validando credenciais...';
            
            // Make API request
            fetch('<?= BASE_URL ?>/api/integration_config.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('configStatus').textContent = 'Salvando configuração...';
                
                setTimeout(() => {
                    showConfigResult(data);
                }, 1000);
            })
            .catch(error => {
                console.error('Erro:', error);
                showConfigResult({
                    success: false,
                    error: 'Erro de conexão: ' + error.message
                });
            });
        });

        function showConfigResult(data) {
            document.getElementById('configProgress').style.display = 'none';
            document.getElementById('configResult').style.display = 'block';
            
            let resultHtml = '';
            
            if (data.success) {
                resultHtml = '<div style="text-align: center; color: #10b981;">';
                resultHtml += '<div style="font-size: 3rem; margin-bottom: 1rem;">✅</div>';
                resultHtml += '<div style="font-weight: 600; margin-bottom: 1rem;">Configuração salva com sucesso!</div>';
                
                if (data.integration && data.integration.webhook_url) {
                    resultHtml += '<div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem; text-align: left;">';
                    resultHtml += '<div style="font-weight: 600; margin-bottom: 0.5rem;">🔗 URL do Webhook:</div>';
                    resultHtml += '<div style="word-break: break-all; font-family: monospace; background: white; padding: 0.5rem; border-radius: 0.25rem; border: 1px solid #e5e7eb;">';
                    resultHtml += data.integration.webhook_url;
                    resultHtml += '</div>';
                    resultHtml += '<div style="font-size: var(--font-size-sm); color: var(--color-gray); margin-top: 0.5rem;">Configure esta URL na plataforma para receber notificações automáticas</div>';
                    resultHtml += '</div>';
                }
                
                resultHtml += '</div>';
            } else {
                resultHtml = '<div style="text-align: center; color: #ef4444;">';
                resultHtml += '<div style="font-size: 3rem; margin-bottom: 1rem;">❌</div>';
                resultHtml += '<div style="font-weight: 600; margin-bottom: 1rem;">Erro na configuração</div>';
                resultHtml += '<div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 0.5rem; padding: 1rem; color: #991b1b;">';
                resultHtml += data.error || 'Erro desconhecido';
                resultHtml += '</div>';
                resultHtml += '</div>';
            }
            
            document.getElementById('configResultContent').innerHTML = resultHtml;
            
            // Refresh page after successful config to update data
            if (data.success) {
                setTimeout(() => {
                    location.reload();
                }, 3000);
            }
        }

        // Close modal when clicking outside
        document.getElementById('configModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeConfigModal();
            }
        });

        function showSyncModal(integrationId, name, platform) {
            console.log('Abrindo modal para integração:', integrationId, name, platform);
            
            currentIntegrationId = integrationId;
            
            // Update modal content
            const nameElement = document.getElementById('integrationName');
            const platformElement = document.getElementById('integrationPlatform');
            const modal = document.getElementById('syncModal');
            
            if (!nameElement || !platformElement || !modal) {
                console.error('Elementos do modal não encontrados');
                return;
            }
            
            nameElement.textContent = name;
            platformElement.textContent = platform.charAt(0).toUpperCase() + platform.slice(1);
            modal.style.display = 'block';
            
            // Reset modal state
            document.getElementById('syncModalContent').style.display = 'block';
            document.getElementById('syncProgress').style.display = 'none';
            document.getElementById('syncResult').style.display = 'none';
            
            // Remove existing event listeners to prevent duplicates
            const syncTypeSelect = document.getElementById('syncType');
            const newSyncTypeSelect = syncTypeSelect.cloneNode(true);
            syncTypeSelect.parentNode.replaceChild(newSyncTypeSelect, syncTypeSelect);
            
            // Add fresh event listener for days option visibility
            newSyncTypeSelect.addEventListener('change', function() {
                const daysOption = document.getElementById('daysOption');
                if (this.value === 'products' || this.value === 'test') {
                    daysOption.style.display = 'none';
                } else {
                    daysOption.style.display = 'block';
                }
            });
        }

        function closeSyncModal() {
            document.getElementById('syncModal').style.display = 'none';
            currentIntegrationId = null;
        }

        function startSync() {
            const syncType = document.getElementById('syncType').value;
            const syncDays = document.getElementById('syncDays').value;
            
            // Show progress
            document.getElementById('syncModalContent').style.display = 'none';
            document.getElementById('syncProgress').style.display = 'block';
            
            // Prepare request
            const requestData = {
                integration_id: currentIntegrationId,
                type: syncType,
                days: parseInt(syncDays)
            };
            
            // Start progress animation
            updateProgress(10, 'Conectando com a API...');
            
            // Make API request
            fetch('<?= BASE_URL ?>/api/sync.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            })
            .then(response => response.json())
            .then(data => {
                updateProgress(100, 'Sincronização concluída!');
                
                setTimeout(() => {
                    showSyncResult(data);
                }, 1000);
            })
            .catch(error => {
                console.error('Erro:', error);
                showSyncResult({
                    success: false,
                    error: 'Erro de conexão: ' + error.message
                });
            });
        }

        function updateProgress(percentage, status) {
            document.getElementById('progressBar').style.width = percentage + '%';
            document.getElementById('syncStatus').textContent = status;
        }

        function showSyncResult(data) {
            document.getElementById('syncProgress').style.display = 'none';
            document.getElementById('syncResult').style.display = 'block';
            
            let resultHtml = '';
            
            if (data.success) {
                resultHtml = '<div style="text-align: center; color: #10b981;">';
                resultHtml += '<div style="font-size: 3rem; margin-bottom: 1rem;">✅</div>';
                resultHtml += '<div style="font-weight: 600; margin-bottom: 1rem;">Sincronização realizada com sucesso!</div>';
                
                if (data.stats) {
                    resultHtml += '<div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem;">';
                    
                    if (data.stats.processed !== undefined) {
                        resultHtml += `<div>📊 <strong>${data.stats.processed}</strong> registros processados</div>`;
                    }
                    if (data.stats.created !== undefined) {
                        resultHtml += `<div>➕ <strong>${data.stats.created}</strong> novos registros</div>`;
                    }
                    if (data.stats.updated !== undefined) {
                        resultHtml += `<div>🔄 <strong>${data.stats.updated}</strong> registros atualizados</div>`;
                    }
                    if (data.stats.errors !== undefined && data.stats.errors > 0) {
                        resultHtml += `<div>❌ <strong>${data.stats.errors}</strong> erros encontrados</div>`;
                    }
                    
                    resultHtml += '</div>';
                }
                
                // Handle full sync results
                if (data.products && data.sales) {
                    resultHtml += '<div style="text-align: left; margin-top: 1rem;">';
                    resultHtml += '<div style="font-weight: 600; margin-bottom: 0.5rem;">Detalhes:</div>';
                    
                    if (data.products.success) {
                        resultHtml += `<div>✅ Produtos: ${data.products.stats.created + data.products.stats.updated} sincronizados</div>`;
                    }
                    if (data.sales.success) {
                        resultHtml += `<div>✅ Vendas: ${data.sales.stats.created + data.sales.stats.updated} sincronizadas</div>`;
                    }
                    
                    resultHtml += '</div>';
                }
                
                resultHtml += '</div>';
            } else {
                resultHtml = '<div style="text-align: center; color: #ef4444;">';
                resultHtml += '<div style="font-size: 3rem; margin-bottom: 1rem;">❌</div>';
                resultHtml += '<div style="font-weight: 600; margin-bottom: 1rem;">Erro na sincronização</div>';
                resultHtml += '<div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 0.5rem; padding: 1rem; color: #991b1b;">';
                resultHtml += data.error || 'Erro desconhecido';
                resultHtml += '</div>';
                resultHtml += '</div>';
            }
            
            document.getElementById('syncResultContent').innerHTML = resultHtml;
            
            // Refresh page after successful sync to update stats
            if (data.success) {
                setTimeout(() => {
                    location.reload();
                }, 3000);
            }
        }

        // Close modal when clicking outside
        document.getElementById('syncModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSyncModal();
            }
        });

        // Delete modal functions
        function showDeleteModal(integrationId, name, platform) {
            currentDeleteIntegrationId = integrationId;
            
            // Update modal content
            document.getElementById('deleteIntegrationName').textContent = name;
            document.getElementById('deleteIntegrationPlatform').textContent = platform.charAt(0).toUpperCase() + platform.slice(1);
            document.getElementById('deleteModal').style.display = 'block';
            
            // Reset modal state
            document.getElementById('deleteModalContent').style.display = 'block';
            document.getElementById('deleteProgress').style.display = 'none';
            document.getElementById('deleteResult').style.display = 'none';
            
            // Reset confirmation input
            document.getElementById('deleteConfirmation').value = '';
            document.getElementById('deleteConfirmButton').disabled = true;
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            currentDeleteIntegrationId = null;
            
            // Reset form
            document.getElementById('deleteConfirmation').value = '';
            document.getElementById('deleteConfirmButton').disabled = true;
        }

        function confirmDelete() {
            if (!currentDeleteIntegrationId || currentDeleteIntegrationId == 0) {
                alert('Erro: ID da integração é inválido');
                return;
            }
            
            // Show progress
            document.getElementById('deleteModalContent').style.display = 'none';
            document.getElementById('deleteProgress').style.display = 'block';
            
            const requestData = {
                integration_id: currentDeleteIntegrationId
            };
            
            // Make DELETE request
            fetch('<?= BASE_URL ?>/api/integration_config.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            })
            .then(response => response.json())
            .then(data => {
                setTimeout(() => {
                    showDeleteResult(data);
                }, 1000);
            })
            .catch(error => {
                console.error('Erro:', error);
                showDeleteResult({
                    success: false,
                    error: 'Erro de conexão: ' + error.message
                });
            });
        }

        function showDeleteResult(data) {
            document.getElementById('deleteProgress').style.display = 'none';
            document.getElementById('deleteResult').style.display = 'block';
            
            let resultHtml = '';
            
            if (data.success) {
                resultHtml = '<div style="text-align: center; color: #10b981;">';
                resultHtml += '<div style="font-size: 3rem; margin-bottom: 1rem;">✅</div>';
                resultHtml += '<div style="font-weight: 600; margin-bottom: 1rem;">Integração excluída com sucesso!</div>';
                resultHtml += '<div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.5rem; padding: 1rem; color: #065f46;">';
                resultHtml += 'A integração e todos os dados associados foram removidos permanentemente.';
                resultHtml += '</div>';
                resultHtml += '</div>';
            } else {
                resultHtml = '<div style="text-align: center; color: #ef4444;">';
                resultHtml += '<div style="font-size: 3rem; margin-bottom: 1rem;">❌</div>';
                resultHtml += '<div style="font-weight: 600; margin-bottom: 1rem;">Erro ao excluir integração</div>';
                resultHtml += '<div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 0.5rem; padding: 1rem; color: #991b1b;">';
                resultHtml += data.error || 'Erro desconhecido';
                resultHtml += '</div>';
                resultHtml += '</div>';
            }
            
            document.getElementById('deleteResultContent').innerHTML = resultHtml;
            
            // Refresh page after successful deletion
            if (data.success) {
                setTimeout(() => {
                    location.reload();
                }, 3000);
            }
        }

        // Enable/disable delete button based on confirmation input
        document.getElementById('deleteConfirmation').addEventListener('input', function() {
            const confirmButton = document.getElementById('deleteConfirmButton');
            if (this.value === 'EXCLUIR') {
                confirmButton.disabled = false;
                confirmButton.style.background = '#ef4444';
            } else {
                confirmButton.disabled = true;
                confirmButton.style.background = '#9ca3af';
            }
        });

        // Close delete modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>