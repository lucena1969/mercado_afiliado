<?php
// Verificar autenticação (config já incluído pelo router)
$auth = new AuthController();
$auth->requireAuth();

// Buscar dados do usuário e assinatura (database já incluído)
$database = new Database();
$db = $database->getConnection();
$subscription = new Subscription($db);

$user_data = $_SESSION['user'];
$user_subscription = $subscription->getActiveSubscription($user_data['id']);

// Incluir models necessários
require_once dirname(dirname(__DIR__)) . '/app/services/LinkMaestroService.php';
require_once dirname(dirname(__DIR__)) . '/app/models/UtmTemplate.php';
require_once dirname(dirname(__DIR__)) . '/app/models/ShortLink.php';

// Verificar se tem acesso Pro
function hasProAccess($db, $user_id) {
    try {
        $query = "SELECT sp.slug 
                 FROM user_subscriptions us 
                 JOIN subscription_plans sp ON us.plan_id = sp.id 
                 WHERE us.user_id = ? AND us.status IN ('active', 'trial')
                 ORDER BY us.created_at DESC 
                 LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $plan = $stmt->fetch();

        return $plan && in_array($plan['slug'], ['pro', 'scale']);

    } catch (Exception $e) {
        return false;
    }
}

$has_pro_access = hasProAccess($db, $user_data['id']);

// Se não tem acesso Pro, mostrar mensagem de upgrade
if (!$has_pro_access) {
    include __DIR__ . '/upgrade_required.php';
    exit;
}

// Buscar dados do dashboard
try {
    require_once dirname(dirname(__DIR__)) . '/app/services/PlanValidationService.php';
    require_once dirname(dirname(__DIR__)) . '/app/middleware/PlanValidationMiddleware.php';
    
    $linkMaestroService = new LinkMaestroService($db);
    $planValidation = new PlanValidationMiddleware($db);
    
    $dashboard_data = $linkMaestroService->getUserDashboard($user_data['id']);
    
    // Buscar templates do usuário
    $utm_template = new UtmTemplate($db);
    $templates = $utm_template->findByUser($user_data['id'], 10);
    
    // Buscar links recentes
    $short_link = new ShortLink($db);
    $recent_links = $short_link->findByUser($user_data['id'], 10);
    
    // Obter dados de validação e uso
    $plan_data = $planValidation->injectViewData($user_data['id']);
    $usage_stats = $plan_data['plan_validation']['usage_stats'];
    $trial_warning = $plan_data['plan_validation']['trial_warning'];
    
} catch (Exception $e) {
    $dashboard_data = ['stats' => ['active_links' => 0, 'total_clicks' => 0], 'top_links' => [], 'top_campaigns' => []];
    $templates = [];
    $recent_links = [];
    $usage_stats = null;
    $trial_warning = null;
    $error_message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Maestro - <?= APP_NAME ?></title>
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
                <li><a href="<?= BASE_URL ?>/integrations">🔗 IntegraSync</a></li>
                <li><a href="<?= BASE_URL ?>/link-maestro" class="active">🎯 Link Maestro</a></li>
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
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <strong>Erro:</strong> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <!-- Trial Warning -->
            <?php if ($trial_warning && $trial_warning['has_warning']): ?>
                <div class="alert <?= $trial_warning['warning']['urgent'] ? 'alert-error' : 'alert-warning' ?>" style="margin-bottom: 2rem;">
                    <strong>⏰ Aviso de Trial:</strong> <?= htmlspecialchars($trial_warning['warning']['message']) ?>
                    <a href="<?= BASE_URL ?>/subscribe" style="margin-left: 1rem; color: var(--color-primary); font-weight: 600;">Fazer Upgrade Agora</a>
                </div>
            <?php endif; ?>

            <!-- Usage Stats -->
            <?php if ($usage_stats): ?>
                <div class="card" style="margin-bottom: 2rem; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-left: 4px solid var(--color-primary);">
                    <div class="card-body">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h3 style="margin: 0; color: var(--color-dark);">📊 Seu uso atual - Plano <?= htmlspecialchars($usage_stats['plan']['name']) ?></h3>
                            <span style="background: var(--color-primary); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                                <?= strtoupper($usage_stats['plan']['slug']) ?>
                            </span>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                            <div>
                                <div style="font-size: 0.875rem; color: var(--color-gray); margin-bottom: 0.5rem;">Links Criados</div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="font-size: 1.25rem; font-weight: 600;">
                                        <?= $usage_stats['usage']['short_links']['current'] ?><?= $usage_stats['usage']['short_links']['limit'] != -1 ? '/' . $usage_stats['usage']['short_links']['limit'] : '' ?>
                                    </div>
                                    <?php if ($usage_stats['usage']['short_links']['limit'] == -1): ?>
                                        <span style="color: #10b981; font-size: 0.75rem; font-weight: 600;">ILIMITADO</span>
                                    <?php else: ?>
                                        <div style="flex: 1; background: #e5e7eb; border-radius: 4px; height: 6px;">
                                            <div style="background: var(--color-primary); border-radius: 4px; height: 100%; width: <?= min(100, ($usage_stats['usage']['short_links']['current'] / max(1, $usage_stats['usage']['short_links']['limit'])) * 100) ?>%;"></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div>
                                <div style="font-size: 0.875rem; color: var(--color-gray); margin-bottom: 0.5rem;">Templates UTM</div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="font-size: 1.25rem; font-weight: 600;">
                                        <?= $usage_stats['usage']['utm_templates']['current'] ?><?= $usage_stats['usage']['utm_templates']['limit'] != -1 ? '/' . $usage_stats['usage']['utm_templates']['limit'] : '' ?>
                                    </div>
                                    <?php if ($usage_stats['usage']['utm_templates']['limit'] == -1): ?>
                                        <span style="color: #10b981; font-size: 0.75rem; font-weight: 600;">ILIMITADO</span>
                                    <?php else: ?>
                                        <div style="flex: 1; background: #e5e7eb; border-radius: 4px; height: 6px;">
                                            <div style="background: #3b82f6; border-radius: 4px; height: 100%; width: <?= min(100, ($usage_stats['usage']['utm_templates']['current'] / max(1, $usage_stats['usage']['utm_templates']['limit'])) * 100) ?>%;"></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div>
                                <div style="font-size: 0.875rem; color: var(--color-gray); margin-bottom: 0.5rem;">Cliques este Mês</div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="font-size: 1.25rem; font-weight: 600;">
                                        <?= number_format($usage_stats['usage']['monthly_clicks']['current']) ?><?= $usage_stats['usage']['monthly_clicks']['limit'] != -1 ? '/' . number_format($usage_stats['usage']['monthly_clicks']['limit']) : '' ?>
                                    </div>
                                    <?php if ($usage_stats['usage']['monthly_clicks']['limit'] == -1): ?>
                                        <span style="color: #10b981; font-size: 0.75rem; font-weight: 600;">ILIMITADO</span>
                                    <?php else: ?>
                                        <div style="flex: 1; background: #e5e7eb; border-radius: 4px; height: 6px;">
                                            <?php $click_percentage = min(100, ($usage_stats['usage']['monthly_clicks']['current'] / max(1, $usage_stats['usage']['monthly_clicks']['limit'])) * 100); ?>
                                            <div style="background: <?= $click_percentage > 80 ? '#ef4444' : '#10b981' ?>; border-radius: 4px; height: 100%; width: <?= $click_percentage ?>%;"></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Header da página -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="margin: 0; color: var(--color-dark);">🎯 Link Maestro</h1>
                    <p style="margin: 0.5rem 0 0 0; color: var(--color-gray);">Gerencie seus links encurtados e UTMs com facilidade</p>
                </div>
                <button id="createLinkBtn" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.5rem;">
                    <span>➕</span> Criar Link
                </button>
            </div>

            <!-- Cards de estatísticas -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 800; color: var(--color-primary);"><?= $dashboard_data['stats']['active_links'] ?? 0 ?></div>
                        <div style="color: var(--color-gray); margin-top: 0.5rem;">Links ativos</div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 800; color: var(--color-primary);"><?= number_format($dashboard_data['stats']['total_clicks'] ?? 0) ?></div>
                        <div style="color: var(--color-gray); margin-top: 0.5rem;">Total de cliques</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 800; color: var(--color-primary);"><?= count($templates) ?></div>
                        <div style="color: var(--color-gray); margin-top: 0.5rem;">Templates UTM</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 800; color: var(--color-primary);"><?= $dashboard_data['stats']['links_today'] ?? 0 ?></div>
                        <div style="color: var(--color-gray); margin-top: 0.5rem;">Links hoje</div>
                    </div>
                </div>
            </div>

            <!-- Seções principais -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                
                <!-- Links recentes -->
                <div class="card">
                    <div class="card-header">
                        <h2>🔗 Links Recentes</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_links)): ?>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <?php foreach (array_slice($recent_links, 0, 5) as $link): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #f8fafc; border-radius: 6px; border-left: 4px solid var(--color-primary);">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; margin-bottom: 0.25rem;">
                                            <?= htmlspecialchars($link['title'] ?: 'Link sem título') ?>
                                        </div>
                                        <div style="font-size: 0.875rem; color: var(--color-gray);">
                                            <?= BASE_URL ?>/l/<?= htmlspecialchars($link['short_code']) ?>
                                        </div>
                                        <?php if ($link['campaign_name']): ?>
                                        <div style="font-size: 0.75rem; color: var(--color-gray); margin-top: 0.25rem;">
                                            Campanha: <?= htmlspecialchars($link['campaign_name']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-weight: 600; color: var(--color-primary);">
                                            <?= number_format($link['click_count']) ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--color-gray);">
                                            cliques
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div style="text-align: center; margin-top: 1.5rem;">
                                <a href="#" onclick="showAllLinks()" class="btn" style="background: #e5e7eb;">Ver Todos os Links</a>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 2rem;">
                                <div style="font-size: 3rem; opacity: 0.3;">🔗</div>
                                <p style="color: var(--color-gray); margin-top: 1rem;">
                                    Nenhum link criado ainda.<br>
                                    <strong>Crie seu primeiro link encurtado!</strong>
                                </p>
                                <button onclick="document.getElementById('createLinkBtn').click()" class="btn btn-primary" style="margin-top: 1rem;">
                                    Criar Primeiro Link
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Templates UTM -->
                <div class="card">
                    <div class="card-header">
                        <h2>📏 Templates UTM</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($templates)): ?>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <?php foreach (array_slice($templates, 0, 5) as $template): ?>
                                <div style="padding: 1rem; background: #f0f9ff; border-radius: 6px; border-left: 4px solid #3b82f6;">
                                    <div style="display: flex; justify-content: between; align-items: center;">
                                        <div style="flex: 1;">
                                            <div style="font-weight: 600; margin-bottom: 0.25rem;">
                                                <?= htmlspecialchars($template['name']) ?>
                                            </div>
                                            <div style="font-size: 0.875rem; color: var(--color-gray);">
                                                <?= ucfirst($template['platform']) ?> • 
                                                Usado <?= $template['usage_count'] ?> vez(es)
                                            </div>
                                        </div>
                                        <div style="background: #3b82f6; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                                            <?= strtoupper($template['platform']) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div style="text-align: center; margin-top: 1.5rem;">
                                <a href="#" onclick="showAllTemplates()" class="btn" style="background: #e5e7eb;">Ver Todos os Templates</a>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 2rem;">
                                <div style="font-size: 3rem; opacity: 0.3;">📏</div>
                                <p style="color: var(--color-gray); margin-top: 1rem;">
                                    Nenhum template criado ainda.<br>
                                    <strong>Templates facilitam a criação de UTMs!</strong>
                                </p>
                                <button onclick="createTemplate()" class="btn btn-primary" style="margin-top: 1rem;">
                                    Criar Template
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top campanhas -->
            <?php if (!empty($dashboard_data['top_campaigns'])): ?>
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h2>📊 Top Campanhas</h2>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <?php foreach (array_slice($dashboard_data['top_campaigns'], 0, 4) as $campaign): ?>
                        <div style="padding: 1rem; background: #fef3c7; border-radius: 6px; text-align: center;">
                            <div style="font-weight: 600; margin-bottom: 0.5rem;">
                                <?= htmlspecialchars($campaign['utm_campaign']) ?>
                            </div>
                            <div style="font-size: 1.5rem; font-weight: 800; color: #d97706;">
                                <?= number_format($campaign['total_clicks']) ?>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--color-gray);">
                                cliques • <?= $campaign['unique_visitors'] ?> visitantes únicos
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Incluir Modal de Compliance -->
    <?php include __DIR__ . '/compliance_modal.php'; ?>

    <!-- Modal Criar Link -->
    <div id="createLinkModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 700px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h2 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <span>🎯</span> Criar Link Encurtado
                </h2>
                <span class="modal-close" onclick="closeCreateLinkModal()">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Formulário de criação -->
                <form id="createLinkForm">
                    <!-- Alerta de Compliance -->
                    <div style="background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 100%); border: 1px solid #f59e0b; border-radius: 6px; padding: 1rem; margin-bottom: 1.5rem;">
                        <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                            <span style="font-size: 1.25rem;">🛡️</span>
                            <div>
                                <div style="font-weight: 600; color: #92400e; margin-bottom: 0.25rem;">
                                    Lembre-se: Use apenas URLs legítimas e transparentes
                                </div>
                                <div style="font-size: 0.875rem; color: #92400e; line-height: 1.4;">
                                    Este link deve redirecionar exatamente para o conteúdo que você está promovendo. 
                                    <strong>Não use para spam, phishing ou conteúdo enganoso.</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- URL Original -->
                    <div class="form-group">
                        <label for="original_url" class="form-label" style="display: flex; align-items: center; gap: 0.5rem;">
                            <span>🔗</span> URL de Destino <span style="color: #ef4444;">*</span>
                        </label>
                        <input 
                            type="url" 
                            id="original_url" 
                            name="original_url" 
                            class="form-input" 
                            placeholder="https://exemplo.com/produto"
                            required
                        >
                        <div id="urlValidationFeedback" style="margin-top: 0.5rem; font-size: 0.75rem; display: none;"></div>
                        <small style="color: var(--color-gray); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                            URL final onde os visitantes serão redirecionados. Deve ser HTTPS sempre que possível.
                        </small>
                    </div>

                    <!-- Título e Descrição -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="link_title" class="form-label">📝 Título do Link</label>
                            <input 
                                type="text" 
                                id="link_title" 
                                name="title" 
                                class="form-input" 
                                placeholder="Campanha Black Friday"
                            >
                        </div>
                        <div class="form-group">
                            <label for="campaign_name" class="form-label">🎯 Nome da Campanha</label>
                            <input 
                                type="text" 
                                id="campaign_name" 
                                name="campaign_name" 
                                class="form-input" 
                                placeholder="blackfriday2024"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">📄 Descrição (opcional)</label>
                        <textarea 
                            id="description" 
                            name="description" 
                            class="form-input" 
                            rows="2"
                            placeholder="Descrição interna do link para organização"
                        ></textarea>
                    </div>

                    <!-- Método UTM -->
                    <div class="form-group">
                        <label class="form-label" style="margin-bottom: 1rem;">🏷️ Como configurar UTMs?</label>
                        <div style="display: flex; gap: 1rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; flex: 1;">
                                <input type="radio" name="utm_method" value="template" checked>
                                <div>
                                    <div style="font-weight: 600;">📏 Usar Template</div>
                                    <div style="font-size: 0.75rem; color: var(--color-gray);">Aplicar template pré-configurado</div>
                                </div>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; flex: 1;">
                                <input type="radio" name="utm_method" value="manual">
                                <div>
                                    <div style="font-weight: 600;">✋ Manual</div>
                                    <div style="font-size: 0.75rem; color: var(--color-gray);">Configurar UTMs manualmente</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Seção Template -->
                    <div id="templateSection" class="utm-section">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div class="form-group">
                                <label for="platform" class="form-label">📱 Plataforma</label>
                                <select id="platform" name="platform" class="form-input">
                                    <option value="">Selecione a plataforma</option>
                                    <option value="facebook">📘 Facebook</option>
                                    <option value="google">🔍 Google</option>
                                    <option value="tiktok">🎵 TikTok</option>
                                    <option value="youtube">📺 YouTube</option>
                                    <option value="linkedin">💼 LinkedIn</option>
                                    <option value="custom">⚙️ Personalizado</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="template_id" class="form-label">📄 Template</label>
                                <select id="template_id" name="template_id" class="form-input">
                                    <option value="">Primeiro selecione a plataforma</option>
                                </select>
                            </div>
                        </div>

                        <!-- Variáveis do Template -->
                        <div id="templateVariables" style="display: none;">
                            <h4 style="margin: 1rem 0 0.5rem 0; color: var(--color-dark);">📝 Variáveis do Template</h4>
                            <div id="variableFields" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <!-- Campos serão inseridos dinamicamente -->
                            </div>
                        </div>
                    </div>

                    <!-- Seção UTM Manual -->
                    <div id="manualSection" class="utm-section" style="display: none;">
                        <h4 style="margin: 1rem 0 0.5rem 0; color: var(--color-dark);">🏷️ Parâmetros UTM</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="utm_source" class="form-label">UTM Source</label>
                                <input type="text" id="utm_source" name="utm_source" class="form-input" placeholder="facebook">
                                <small style="color: var(--color-gray); font-size: 0.75rem;">Ex: facebook, google, newsletter</small>
                            </div>
                            <div class="form-group">
                                <label for="utm_medium" class="form-label">UTM Medium</label>
                                <input type="text" id="utm_medium" name="utm_medium" class="form-input" placeholder="cpc">
                                <small style="color: var(--color-gray); font-size: 0.75rem;">Ex: cpc, display, social</small>
                            </div>
                            <div class="form-group">
                                <label for="utm_campaign" class="form-label">UTM Campaign</label>
                                <input type="text" id="utm_campaign" name="utm_campaign" class="form-input" placeholder="black_friday_2024">
                                <small style="color: var(--color-gray); font-size: 0.75rem;">Nome da campanha</small>
                            </div>
                            <div class="form-group">
                                <label for="utm_content" class="form-label">UTM Content</label>
                                <input type="text" id="utm_content" name="utm_content" class="form-input" placeholder="banner_topo">
                                <small style="color: var(--color-gray); font-size: 0.75rem;">Identifica o anúncio/criativo</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="utm_term" class="form-label">UTM Term</label>
                            <input type="text" id="utm_term" name="utm_term" class="form-input" placeholder="sapatos+masculinos">
                            <small style="color: var(--color-gray); font-size: 0.75rem;">Palavras-chave (para busca paga)</small>
                        </div>
                    </div>

                    <!-- Preview da URL Final -->
                    <div class="form-group" style="background: #f0f9ff; border: 1px solid #3b82f6; border-radius: 6px; padding: 1rem;">
                        <label style="font-weight: 600; color: #1e40af; margin-bottom: 0.5rem; display: block;">🔍 Preview da URL Final</label>
                        <div id="finalUrlPreview" style="font-family: monospace; font-size: 0.875rem; color: var(--color-gray); word-break: break-all; line-height: 1.4;">
                            <em>Configure os campos acima para ver o preview...</em>
                        </div>
                    </div>

                    <!-- Configurações Avançadas -->
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;" onclick="toggleAdvanced()">
                            <span id="advancedToggle">▶️</span> Configurações Avançadas
                        </label>
                        <div id="advancedOptions" style="display: none; margin-top: 1rem; padding: 1rem; background: #f9fafb; border-radius: 6px;">
                            <div class="form-group">
                                <label for="expires_at" class="form-label">⏰ Data de Expiração</label>
                                <input type="datetime-local" id="expires_at" name="expires_at" class="form-input">
                                <small style="color: var(--color-gray); font-size: 0.75rem;">Deixe vazio para nunca expirar</small>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label for="ad_name" class="form-label">📢 Nome do Anúncio</label>
                                    <input type="text" id="ad_name" name="ad_name" class="form-input" placeholder="Banner Principal">
                                </div>
                                <div class="form-group">
                                    <label for="creative_name" class="form-label">🎨 Nome do Criativo</label>
                                    <input type="text" id="creative_name" name="creative_name" class="form-input" placeholder="Imagem 1200x630">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botões -->
                    <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                        <button type="button" onclick="closeCreateLinkModal()" class="btn" style="background: #e5e7eb;">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.5rem;">
                            <span id="createButtonText">🎯 Criar Link</span>
                            <div id="createButtonSpinner" style="display: none;">⏳</div>
                        </button>
                    </div>
                </form>

                <!-- Resultado Success -->
                <div id="createSuccess" style="display: none;">
                    <div style="text-align: center; padding: 2rem;">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">✅</div>
                        <h3 style="color: #10b981; margin-bottom: 1rem;">Link criado com sucesso!</h3>
                        
                        <div style="background: #f0fdf4; border: 1px solid #10b981; border-radius: 6px; padding: 1rem; margin-bottom: 1rem;">
                            <div style="font-weight: 600; margin-bottom: 0.5rem;">🔗 Seu link encurtado:</div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; background: white; border-radius: 4px; padding: 0.75rem;">
                                <input type="text" id="generatedShortUrl" readonly class="form-input" style="border: none; font-family: monospace; font-size: 1rem;">
                                <button type="button" onclick="copyToClipboard('generatedShortUrl')" class="btn" style="padding: 0.5rem;">📋</button>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: center; gap: 1rem;">
                            <button onclick="createAnotherLink()" class="btn btn-primary">
                                ➕ Criar Outro Link
                            </button>
                            <button onclick="closeCreateLinkModal()" class="btn" style="background: #e5e7eb;">
                                Fechar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variáveis globais
        let utmPresets = {};
        let userTemplates = [];

        // Funções de navegação
        function showComingSoon(feature) {
            alert(feature + ' será implementado em breve!');
        }

        function showAllLinks() {
            alert('Funcionalidade de listagem completa será implementada em breve!');
        }

        function showAllTemplates() {
            alert('Funcionalidade de templates será implementada em breve!');
        }

        function createTemplate() {
            alert('Criação de templates será implementada em breve!');
        }

        // === MODAL DE CRIAÇÃO DE LINKS ===

        // Abrir modal
        document.getElementById('createLinkBtn').addEventListener('click', function() {
            openCreateLinkModal();
        });

        function openCreateLinkModal() {
            // Primeiro verificar se precisa mostrar compliance
            if (showComplianceModal()) {
                return; // Compliance modal foi exibido, aguardar aceitação
            }
            
            // Se compliance já aceito, abrir modal normalmente
            document.getElementById('createLinkModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Reset form
            document.getElementById('createLinkForm').reset();
            document.getElementById('createLinkForm').style.display = 'block';
            document.getElementById('createSuccess').style.display = 'none';
            
            // Reset UTM method to template
            document.querySelector('input[name="utm_method"][value="template"]').checked = true;
            toggleUtmMethod();
            
            // Load presets and templates
            loadPlatformPresets();
            
            // Focus no primeiro campo
            setTimeout(() => {
                document.getElementById('original_url').focus();
            }, 100);
        }

        function closeCreateLinkModal() {
            document.getElementById('createLinkModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Fechar modal clicando fora
        document.getElementById('createLinkModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCreateLinkModal();
            }
        });

        // === UTM METHOD TOGGLE ===

        // Listener para mudança de método UTM
        document.querySelectorAll('input[name="utm_method"]').forEach(radio => {
            radio.addEventListener('change', toggleUtmMethod);
        });

        function toggleUtmMethod() {
            const method = document.querySelector('input[name="utm_method"]:checked').value;
            const templateSection = document.getElementById('templateSection');
            const manualSection = document.getElementById('manualSection');
            
            if (method === 'template') {
                templateSection.style.display = 'block';
                manualSection.style.display = 'none';
                
                // Update radio button styles
                updateRadioStyles('template');
            } else {
                templateSection.style.display = 'none';
                manualSection.style.display = 'block';
                
                // Update radio button styles
                updateRadioStyles('manual');
            }
            
            updateFinalUrlPreview();
        }

        function updateRadioStyles(selected) {
            document.querySelectorAll('input[name="utm_method"]').forEach(radio => {
                const label = radio.closest('label');
                if (radio.value === selected) {
                    label.style.borderColor = 'var(--color-primary)';
                    label.style.backgroundColor = 'rgba(59, 130, 246, 0.05)';
                } else {
                    label.style.borderColor = '#e5e7eb';
                    label.style.backgroundColor = 'transparent';
                }
            });
        }

        // === PLATFORM PRESETS ===

        document.getElementById('platform').addEventListener('change', function() {
            const platform = this.value;
            loadTemplatesForPlatform(platform);
        });

        async function loadPlatformPresets() {
            try {
                // Carregar presets do backend
                const response = await fetch('<?= BASE_URL ?>/api/link-maestro/presets');
                const data = await response.json();
                
                if (data.success) {
                    utmPresets = data.presets;
                }
            } catch (error) {
                console.error('Erro ao carregar presets:', error);
            }
        }

        async function loadTemplatesForPlatform(platform) {
            const templateSelect = document.getElementById('template_id');
            
            // Reset template select
            templateSelect.innerHTML = '<option value="">Carregando templates...</option>';
            
            if (!platform) {
                templateSelect.innerHTML = '<option value="">Primeiro selecione a plataforma</option>';
                document.getElementById('templateVariables').style.display = 'none';
                return;
            }
            
            try {
                // Carregar templates do backend + presets
                const response = await fetch(`<?= BASE_URL ?>/api/link-maestro/templates?platform=${platform}`);
                const data = await response.json();
                
                if (data.success) {
                    templateSelect.innerHTML = '<option value="">Selecione um template</option>';
                    
                    // Adicionar presets do sistema
                    if (utmPresets[platform]) {
                        const optgroup = document.createElement('optgroup');
                        optgroup.label = '📄 Templates do Sistema';
                        
                        utmPresets[platform].forEach(preset => {
                            const option = document.createElement('option');
                            option.value = `preset_${preset.id}`;
                            option.textContent = `${preset.preset_name} - ${preset.description}`;
                            option.dataset.preset = JSON.stringify(preset);
                            optgroup.appendChild(option);
                        });
                        
                        templateSelect.appendChild(optgroup);
                    }
                    
                    // Adicionar templates do usuário
                    if (data.templates && data.templates.length > 0) {
                        const optgroup = document.createElement('optgroup');
                        optgroup.label = '👤 Meus Templates';
                        
                        data.templates.forEach(template => {
                            const option = document.createElement('option');
                            option.value = template.id;
                            option.textContent = `${template.name} - ${template.description || 'Sem descrição'}`;
                            option.dataset.template = JSON.stringify(template);
                            optgroup.appendChild(option);
                        });
                        
                        templateSelect.appendChild(optgroup);
                    }
                }
                
            } catch (error) {
                console.error('Erro ao carregar templates:', error);
                templateSelect.innerHTML = '<option value="">Erro ao carregar templates</option>';
            }
        }

        // Template selection change
        document.getElementById('template_id').addEventListener('change', function() {
            const selectedOption = this.selectedOptions[0];
            
            if (selectedOption && selectedOption.value) {
                showTemplateVariables(selectedOption);
            } else {
                document.getElementById('templateVariables').style.display = 'none';
            }
            
            updateFinalUrlPreview();
        });

        function showTemplateVariables(option) {
            const variablesDiv = document.getElementById('templateVariables');
            const fieldsDiv = document.getElementById('variableFields');
            
            let template = null;
            
            if (option.dataset.preset) {
                template = JSON.parse(option.dataset.preset);
            } else if (option.dataset.template) {
                template = JSON.parse(option.dataset.template);
            }
            
            if (!template) {
                variablesDiv.style.display = 'none';
                return;
            }
            
            // Extrair variáveis do template
            const variables = extractVariablesFromTemplate(template);
            
            if (variables.length === 0) {
                variablesDiv.style.display = 'none';
                return;
            }
            
            // Criar campos para as variáveis
            fieldsDiv.innerHTML = '';
            
            variables.forEach(variable => {
                const fieldDiv = document.createElement('div');
                fieldDiv.className = 'form-group';
                
                const label = document.createElement('label');
                label.className = 'form-label';
                label.textContent = getVariableLabel(variable);
                
                const input = document.createElement('input');
                input.type = 'text';
                input.name = `var_${variable}`;
                input.className = 'form-input';
                input.placeholder = getVariablePlaceholder(variable);
                input.addEventListener('input', updateFinalUrlPreview);
                
                fieldDiv.appendChild(label);
                fieldDiv.appendChild(input);
                fieldsDiv.appendChild(fieldDiv);
            });
            
            variablesDiv.style.display = 'block';
        }

        function extractVariablesFromTemplate(template) {
            const variables = new Set();
            
            // Verificar todos os campos UTM por variáveis {variable}
            const utmFields = [
                'utm_campaign_template', 'utm_content_template', 'utm_term_template',
                'utm_campaign', 'utm_content', 'utm_term'
            ];
            
            utmFields.forEach(field => {
                if (template[field]) {
                    const matches = template[field].match(/\{([^}]+)\}/g);
                    if (matches) {
                        matches.forEach(match => {
                            variables.add(match.replace(/[{}]/g, ''));
                        });
                    }
                }
            });
            
            return Array.from(variables);
        }

        function getVariableLabel(variable) {
            const labels = {
                'campaign_name': '🎯 Nome da Campanha',
                'ad_name': '📢 Nome do Anúncio',
                'creative_name': '🎨 Nome do Criativo',
                'keyword': '🔍 Palavra-chave',
                'target_audience': '👥 Público-alvo',
                'ad_group': '📂 Grupo de Anúncios',
                'video_name': '📺 Nome do Vídeo'
            };
            
            return labels[variable] || `📝 ${variable.replace('_', ' ').toUpperCase()}`;
        }

        function getVariablePlaceholder(variable) {
            const placeholders = {
                'campaign_name': 'black_friday_2024',
                'ad_name': 'Banner Principal',
                'creative_name': 'Imagem 1200x630',
                'keyword': 'sapatos masculinos',
                'target_audience': 'homens-25-45',
                'ad_group': 'Sapatos Casuais',
                'video_name': 'Video Promocional'
            };
            
            return placeholders[variable] || `Digite ${variable}...`;
        }

        // === URL VALIDATION ===
        
        document.getElementById('original_url').addEventListener('input', function() {
            debounce(validateUrl, 500)(this.value);
        });

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        async function validateUrl(url) {
            const feedback = document.getElementById('urlValidationFeedback');
            
            if (!url) {
                feedback.style.display = 'none';
                return;
            }

            // Validações básicas
            const validations = {
                protocol: checkProtocol(url),
                format: checkUrlFormat(url),
                domain: checkDomainSecurity(url),
                suspiciousPatterns: checkSuspiciousPatterns(url)
            };

            let messages = [];
            let hasErrors = false;

            // Verificar protocolo
            if (validations.protocol.status === 'warning') {
                messages.push(`<span style="color: #f59e0b;">⚠️ ${validations.protocol.message}</span>`);
            } else if (validations.protocol.status === 'error') {
                messages.push(`<span style="color: #ef4444;">❌ ${validations.protocol.message}</span>`);
                hasErrors = true;
            } else if (validations.protocol.status === 'success') {
                messages.push(`<span style="color: #10b981;">✅ ${validations.protocol.message}</span>`);
            }

            // Verificar formato
            if (validations.format.status === 'error') {
                messages.push(`<span style="color: #ef4444;">❌ ${validations.format.message}</span>`);
                hasErrors = true;
            }

            // Verificar padrões suspeitos
            if (validations.suspiciousPatterns.length > 0) {
                validations.suspiciousPatterns.forEach(pattern => {
                    messages.push(`<span style="color: #ef4444;">🚨 ${pattern}</span>`);
                    hasErrors = true;
                });
            }

            // Verificar domínio
            if (validations.domain.status === 'warning') {
                messages.push(`<span style="color: #f59e0b;">⚠️ ${validations.domain.message}</span>`);
            }

            // Mostrar feedback
            if (messages.length > 0) {
                feedback.innerHTML = messages.join('<br>');
                feedback.style.display = 'block';
                feedback.style.padding = '0.5rem';
                feedback.style.borderRadius = '4px';
                feedback.style.backgroundColor = hasErrors ? '#fef2f2' : '#f0fdf4';
                feedback.style.border = hasErrors ? '1px solid #ef4444' : '1px solid #10b981';
            } else {
                feedback.style.display = 'none';
            }

            // Desabilitar botão se houver erros
            const submitBtn = document.querySelector('#createLinkForm button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = hasErrors;
            }
        }

        function checkProtocol(url) {
            if (!url.startsWith('http://') && !url.startsWith('https://')) {
                return { status: 'error', message: 'URL deve começar com http:// ou https://' };
            }
            if (url.startsWith('http://')) {
                return { status: 'warning', message: 'Recomendamos HTTPS para maior segurança' };
            }
            return { status: 'success', message: 'Protocolo HTTPS ✓' };
        }

        function checkUrlFormat(url) {
            try {
                new URL(url);
                return { status: 'success', message: 'Formato válido' };
            } catch {
                return { status: 'error', message: 'Formato de URL inválido' };
            }
        }

        function checkDomainSecurity(url) {
            try {
                const domain = new URL(url).hostname.toLowerCase();
                
                // Lista de domínios conhecidos como seguros
                const trustedDomains = [
                    'hotmart.com', 'monetizze.com.br', 'eduzz.com', 'braip.com.br',
                    'amazon.com.br', 'mercadolivre.com.br', 'americanas.com.br',
                    'youtube.com', 'vimeo.com', 'facebook.com', 'instagram.com',
                    'google.com', 'microsoft.com', 'apple.com'
                ];

                if (trustedDomains.some(trusted => domain.includes(trusted))) {
                    return { status: 'success', message: 'Domínio confiável detectado' };
                }

                return { status: 'info', message: 'Domínio não reconhecido - verifique se é legítimo' };
            } catch {
                return { status: 'warning', message: 'Não foi possível verificar o domínio' };
            }
        }

        function checkSuspiciousPatterns(url) {
            const suspiciousPatterns = [
                { pattern: /bit\.ly|tinyurl|shorturl/i, message: 'Evite links já encurtados' },
                { pattern: /(?:free|gratis).*(?:money|dinheiro)/i, message: 'Padrão suspeito: "dinheiro grátis"' },
                { pattern: /(?:click|clique).*(?:here|aqui)/i, message: 'Evite URLs com "clique aqui"' },
                { pattern: /\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/i, message: 'URLs com IP podem ser suspeitas' },
                { pattern: /localhost|127\.0\.0\.1/i, message: 'URL local não funcionará publicamente' }
            ];

            const issues = [];
            suspiciousPatterns.forEach(({pattern, message}) => {
                if (pattern.test(url)) {
                    issues.push(message);
                }
            });

            return issues;
        }

        // === URL PREVIEW ===

        // Atualizar preview quando campos mudarem
        document.querySelectorAll('#createLinkForm input, #createLinkForm select, #createLinkForm textarea').forEach(field => {
            field.addEventListener('input', updateFinalUrlPreview);
        });

        function updateFinalUrlPreview() {
            const originalUrl = document.getElementById('original_url').value.trim();
            
            if (!originalUrl) {
                document.getElementById('finalUrlPreview').innerHTML = '<em>Digite uma URL para ver o preview...</em>';
                return;
            }
            
            try {
                let finalUrl = new URL(originalUrl);
                let utmParams = {};
                
                const method = document.querySelector('input[name="utm_method"]:checked').value;
                
                if (method === 'template') {
                    utmParams = getUtmFromTemplate();
                } else {
                    utmParams = getUtmFromManualFields();
                }
                
                // Adicionar UTMs à URL
                Object.keys(utmParams).forEach(key => {
                    if (utmParams[key] && utmParams[key].trim()) {
                        finalUrl.searchParams.set(key, utmParams[key].trim());
                    }
                });
                
                document.getElementById('finalUrlPreview').textContent = finalUrl.toString();
                
            } catch (error) {
                document.getElementById('finalUrlPreview').innerHTML = '<em style="color: #ef4444;">URL inválida</em>';
            }
        }

        function getUtmFromTemplate() {
            const templateSelect = document.getElementById('template_id');
            const selectedOption = templateSelect.selectedOptions[0];
            
            if (!selectedOption || !selectedOption.value) {
                return {};
            }
            
            let template = null;
            
            if (selectedOption.dataset.preset) {
                template = JSON.parse(selectedOption.dataset.preset);
            } else if (selectedOption.dataset.template) {
                template = JSON.parse(selectedOption.dataset.template);
            }
            
            if (!template) return {};
            
            // Coletar valores das variáveis
            const variables = {};
            document.querySelectorAll('#variableFields input').forEach(input => {
                if (input.name.startsWith('var_')) {
                    const varName = input.name.replace('var_', '');
                    variables[varName] = input.value;
                }
            });
            
            // Aplicar variáveis aos templates
            const utmParams = {};
            
            if (template.utm_source) utmParams.utm_source = template.utm_source;
            if (template.utm_medium) utmParams.utm_medium = template.utm_medium;
            
            // Aplicar templates com variáveis
            if (template.utm_campaign_template) {
                utmParams.utm_campaign = replaceVariables(template.utm_campaign_template, variables);
            } else if (template.utm_campaign) {
                utmParams.utm_campaign = replaceVariables(template.utm_campaign, variables);
            }
            
            if (template.utm_content_template) {
                utmParams.utm_content = replaceVariables(template.utm_content_template, variables);
            } else if (template.utm_content) {
                utmParams.utm_content = replaceVariables(template.utm_content, variables);
            }
            
            if (template.utm_term_template) {
                utmParams.utm_term = replaceVariables(template.utm_term_template, variables);
            } else if (template.utm_term) {
                utmParams.utm_term = replaceVariables(template.utm_term, variables);
            }
            
            return utmParams;
        }

        function getUtmFromManualFields() {
            return {
                utm_source: document.getElementById('utm_source').value,
                utm_medium: document.getElementById('utm_medium').value,
                utm_campaign: document.getElementById('utm_campaign').value,
                utm_content: document.getElementById('utm_content').value,
                utm_term: document.getElementById('utm_term').value
            };
        }

        function replaceVariables(template, variables) {
            let result = template;
            Object.keys(variables).forEach(key => {
                const placeholder = `{${key}}`;
                result = result.replace(new RegExp(placeholder.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), variables[key] || '');
            });
            return result;
        }

        // === CONFIGURAÇÕES AVANÇADAS ===

        function toggleAdvanced() {
            const options = document.getElementById('advancedOptions');
            const toggle = document.getElementById('advancedToggle');
            
            if (options.style.display === 'none' || !options.style.display) {
                options.style.display = 'block';
                toggle.textContent = '🔽';
            } else {
                options.style.display = 'none';
                toggle.textContent = '▶️';
            }
        }

        // === SUBMIT FORM ===

        document.getElementById('createLinkForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitButton = document.querySelector('#createLinkForm button[type="submit"]');
            const buttonText = document.getElementById('createButtonText');
            const buttonSpinner = document.getElementById('createButtonSpinner');
            
            // Mostrar loading
            submitButton.disabled = true;
            buttonText.style.display = 'none';
            buttonSpinner.style.display = 'block';
            
            try {
                const formData = new FormData(this);
                
                // Adicionar variáveis do template se houver
                const method = document.querySelector('input[name="utm_method"]:checked').value;
                if (method === 'template') {
                    document.querySelectorAll('#variableFields input').forEach(input => {
                        if (input.name.startsWith('var_')) {
                            formData.append(input.name, input.value);
                        }
                    });
                }
                
                // Enviar para API
                const response = await fetch('<?= BASE_URL ?>/api/link-maestro/create', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Mostrar sucesso
                    document.getElementById('createLinkForm').style.display = 'none';
                    document.getElementById('createSuccess').style.display = 'block';
                    document.getElementById('generatedShortUrl').value = result.short_url;
                    
                    // Atualizar estatísticas da página
                    setTimeout(() => {
                        location.reload(); // Reload para atualizar stats
                    }, 3000);
                    
                } else {
                    throw new Error(result.error || 'Erro desconhecido');
                }
                
            } catch (error) {
                alert('Erro ao criar link: ' + error.message);
                console.error('Erro:', error);
            } finally {
                // Restaurar botão
                submitButton.disabled = false;
                buttonText.style.display = 'block';
                buttonSpinner.style.display = 'none';
            }
        });

        // === SUCCESS ACTIONS ===

        function createAnotherLink() {
            document.getElementById('createSuccess').style.display = 'none';
            document.getElementById('createLinkForm').style.display = 'block';
            document.getElementById('createLinkForm').reset();
            
            // Reset to template method
            document.querySelector('input[name="utm_method"][value="template"]').checked = true;
            toggleUtmMethod();
            
            // Focus first field
            setTimeout(() => {
                document.getElementById('original_url').focus();
            }, 100);
        }

        function copyToClipboard(inputId) {
            const input = document.getElementById(inputId);
            input.select();
            document.execCommand('copy');
            
            // Feedback visual
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = '✅';
            button.style.background = '#10b981';
            button.style.color = 'white';
            
            setTimeout(() => {
                button.textContent = originalText;
                button.style.background = '';
                button.style.color = '';
            }, 1000);
        }

        // === KEYBOARD SHORTCUTS ===

        document.addEventListener('keydown', function(e) {
            // ESC para fechar modal
            if (e.key === 'Escape') {
                const modal = document.getElementById('createLinkModal');
                if (modal.style.display === 'flex') {
                    closeCreateLinkModal();
                }
            }
            
            // Ctrl+Enter para submit form
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                const form = document.getElementById('createLinkForm');
                if (form.style.display !== 'none') {
                    form.dispatchEvent(new Event('submit'));
                }
            }
        });

        // === INITIALIZATION ===

        // Inicializar quando página carregar
        document.addEventListener('DOMContentLoaded', function() {
            // Initial UTM method setup
            toggleUtmMethod();
        });
    </script>
</body>
</html>