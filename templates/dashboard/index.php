<?php
// Verificar autenticação
require_once '../config/app.php';
require_once '../app/controllers/AuthController.php';

$auth = new AuthController();
$auth->requireAuth();

// Buscar dados do usuário e assinatura
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$subscription = new Subscription($db);

$user_data = $_SESSION['user'];
$user_subscription = $subscription->getActiveSubscription($user_data['id']);
$is_trial = $user_subscription && $user_subscription['status'] === 'trial';
$trial_days_left = 0;

if ($is_trial && $user_subscription['trial_ends_at']) {
    $trial_end = strtotime($user_subscription['trial_ends_at']);
    $trial_days_left = max(0, ceil(($trial_end - time()) / (24 * 60 * 60)));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= APP_NAME ?></title>
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
                <li><a href="<?= BASE_URL ?>/dashboard" class="active">📊 Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>/unified-panel">📈 Painel Unificado</a></li>
                <li><a href="<?= BASE_URL ?>/integrations">🔗 IntegraSync</a></li>
                <li><a href="#" onclick="showComingSoon('Link Maestro')">🎯 Link Maestro</a></li>
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
            <!-- Mensagem de boas-vindas -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <!-- Status do trial -->
            <?php if ($is_trial): ?>
                <div class="alert alert-info" style="margin-bottom: 2rem;">
                    🎉 <strong>Trial ativo!</strong> 
                    Você tem <?= $trial_days_left ?> dias restantes no seu período de teste.
                    <a href="#" style="margin-left: 1rem; color: var(--color-primary); font-weight: 600;">Assinar agora</a>
                </div>
            <?php endif; ?>

            <!-- Cards de resumo -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 800; color: var(--color-primary);">0</div>
                        <div style="color: var(--color-gray); margin-top: 0.5rem;">Integrações ativas</div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 800; color: var(--color-primary);">R$ 0,00</div>
                        <div style="color: var(--color-gray); margin-top: 0.5rem;">Receita este mês</div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 800; color: var(--color-primary);">0</div>
                        <div style="color: var(--color-gray); margin-top: 0.5rem;">Vendas este mês</div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 800; color: var(--color-primary);">0%</div>
                        <div style="color: var(--color-gray); margin-top: 0.5rem;">Taxa de conversão</div>
                    </div>
                </div>
            </div>

            <!-- Informações da conta -->
            <div class="card">
                <div class="card-header">
                    <h2>Informações da conta</h2>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                        <div>
                            <strong>Nome:</strong><br>
                            <span style="color: var(--color-gray);"><?= htmlspecialchars($user_data['name']) ?></span>
                        </div>
                        <div>
                            <strong>E-mail:</strong><br>
                            <span style="color: var(--color-gray);"><?= htmlspecialchars($user_data['email']) ?></span>
                        </div>
                        <div>
                            <strong>Plano atual:</strong><br>
                            <span style="color: var(--color-gray);">
                                <?= $user_subscription ? htmlspecialchars($user_subscription['plan_name']) : 'Nenhum' ?>
                                <?= $is_trial ? ' (Trial)' : '' ?>
                            </span>
                        </div>
                        <div>
                            <strong>Status:</strong><br>
                            <span style="color: <?= $user_data['status'] === 'active' ? 'green' : 'red' ?>;">
                                <?= $user_data['status'] === 'active' ? '✅ Ativo' : '❌ Inativo' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Próximos passos -->
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h2>🚀 Primeiros passos</h2>
                </div>
                <div class="card-body">
                    <div style="display: grid; gap: 1rem;">
                        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: rgba(245, 158, 11, 0.05); border-radius: 6px;">
                            <div style="width: 24px; height: 24px; background: var(--color-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.875rem;">1</div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600;">Configure sua primeira integração</div>
                                <div style="font-size: 0.875rem; color: var(--color-gray); margin-top: 0.25rem;">
                                    Conecte com Hotmart, Monetizze, Eduzz ou Braip para começar a monitorar suas vendas.
                                </div>
                            </div>
                            <button class="btn btn-primary" onclick="showComingSoon('Integrações')">Configurar</button>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f3f4f6; border-radius: 6px;">
                            <div style="width: 24px; height: 24px; background: #9ca3af; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.875rem;">2</div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: var(--color-gray);">Configure alertas de queda</div>
                                <div style="font-size: 0.875rem; color: var(--color-gray); margin-top: 0.25rem;">
                                    Receba notificações quando suas conversões ou receita despencarem.
                                </div>
                            </div>
                            <button class="btn" style="background: #e5e7eb; color: var(--color-gray);" disabled>Em breve</button>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f3f4f6; border-radius: 6px;">
                            <div style="width: 24px; height: 24px; background: #9ca3af; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.875rem;">3</div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: var(--color-gray);">Crie templates de UTM</div>
                                <div style="font-size: 0.875rem; color: var(--color-gray); margin-top: 0.25rem;">
                                    Padronize suas UTMs para Meta, Google, TikTok e outras fontes.
                                </div>
                            </div>
                            <button class="btn" style="background: #e5e7eb; color: var(--color-gray);" disabled>Em breve</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showComingSoon(feature) {
            alert('🚧 ' + feature + ' estará disponível em breve!\n\nEstamos trabalhando duro para entregar essa funcionalidade o mais rápido possível.');
        }
    </script>
</body>
</html>