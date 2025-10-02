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
                <li><a href="<?= BASE_URL ?>/integrations" class="active">🔗 IntegraSync</a></li>
                <li><a href="#" onclick="showComingSoon('Painel Unificado')">📈 Painel Unificado</a></li>
                <li><a href="#" onclick="showComingSoon('Link Maestro')">🎯 Link Maestro</a></li>
                <li><a href="#" onclick="showComingSoon('Pixel BR')">📊 Pixel BR</a></li>
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
                                                    <?= $int['status'] === 'active' ? 'Ativa' : ucfirst($int['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button class="btn" style="background: #f3f4f6; color: var(--color-gray); padding: 0.5rem;" onclick="showComingSoon('Configurar')">
                                            ⚙️
                                        </button>
                                        <button class="btn" style="background: #f3f4f6; color: var(--color-gray); padding: 0.5rem;" onclick="showComingSoon('Sincronizar')">
                                            🔄
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

    <script>
        function showComingSoon(feature) {
            alert('🚧 ' + feature + ' será implementado na próxima etapa!\n\nEstamos construindo isso agora. Em breve você poderá configurar suas integrações.');
        }
    </script>
</body>
</html>