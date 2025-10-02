<?php
// Obter plano atual do usuário
function getCurrentPlan($db, $user_id) {
    try {
        $query = "SELECT sp.name, sp.slug 
                 FROM user_subscriptions us 
                 JOIN subscription_plans sp ON us.plan_id = sp.id 
                 WHERE us.user_id = ? AND us.status IN ('active', 'trial')
                 ORDER BY us.created_at DESC 
                 LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $plan = $stmt->fetch();

        return $plan ? $plan['name'] : 'Starter';

    } catch (Exception $e) {
        return 'Desconhecido';
    }
}

$current_plan = getCurrentPlan($db, $user_data['id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Maestro - Upgrade Necessário - <?= APP_NAME ?></title>
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
            <!-- Upgrade Required -->
            <div style="text-align: center; padding: 4rem 2rem;">
                <div style="max-width: 600px; margin: 0 auto;">
                    
                    <!-- Ícone Link Maestro -->
                    <div style="font-size: 5rem; margin-bottom: 2rem; opacity: 0.6;">🎯</div>
                    
                    <!-- Título -->
                    <h1 style="color: var(--color-dark); margin-bottom: 1rem; font-size: 2.5rem;">
                        Link Maestro
                    </h1>
                    
                    <!-- Mensagem de upgrade -->
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
                        <h2 style="color: white; margin-bottom: 1rem;">🚀 Funcionalidade Premium</h2>
                        <p style="font-size: 1.125rem; line-height: 1.6; margin-bottom: 1.5rem;">
                            O Link Maestro está disponível apenas nos planos <strong>Pro</strong> e <strong>Scale</strong>.
                        </p>
                        <p style="opacity: 0.9;">
                            Seu plano atual: <strong><?= htmlspecialchars($current_plan) ?></strong>
                        </p>
                    </div>

                    <!-- Benefícios do Link Maestro -->
                    <div class="card" style="text-align: left; margin-bottom: 2rem;">
                        <div class="card-header">
                            <h3>✨ O que você ganha com o Link Maestro:</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: grid; gap: 1.5rem;">
                                <div style="display: flex; align-items: start; gap: 1rem;">
                                    <div style="background: #10b981; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0;">📏</div>
                                    <div>
                                        <h4 style="margin: 0 0 0.5rem 0; color: var(--color-dark);">Templates UTM Inteligentes</h4>
                                        <p style="margin: 0; color: var(--color-gray);">
                                            Crie templates padronizados para Facebook, Google, TikTok e outras plataformas. Nunca mais erre suas UTMs!
                                        </p>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: start; gap: 1rem;">
                                    <div style="background: #3b82f6; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0;">✂️</div>
                                    <div>
                                        <h4 style="margin: 0 0 0.5rem 0; color: var(--color-dark);">Links Encurtados Profissionais</h4>
                                        <p style="margin: 0; color: var(--color-gray);">
                                            URLs curtas e limpas com seu domínio. Perfeito para redes sociais e campanhas de tráfego pago.
                                        </p>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: start; gap: 1rem;">
                                    <div style="background: #8b5cf6; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0;">📊</div>
                                    <div>
                                        <h4 style="margin: 0 0 0.5rem 0; color: var(--color-dark);">Analytics Detalhado de Cliques</h4>
                                        <p style="margin: 0; color: var(--color-gray);">
                                            Veja quantas pessoas clicaram, de onde vieram, que dispositivos usaram e muito mais. Dados em tempo real!
                                        </p>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: start; gap: 1rem;">
                                    <div style="background: #f59e0b; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0;">🎯</div>
                                    <div>
                                        <h4 style="margin: 0 0 0.5rem 0; color: var(--color-dark);">Relatórios por Campanha</h4>
                                        <p style="margin: 0; color: var(--color-gray);">
                                            Compare performance entre campanhas, anúncios e criativos. Saiba exatamente o que funciona!
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comparação de planos -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                        <!-- Plano Atual (Starter) -->
                        <div class="card" style="opacity: 0.7;">
                            <div class="card-header" style="text-align: center; background: #f3f4f6;">
                                <h4><?= htmlspecialchars($current_plan) ?></h4>
                                <p style="margin: 0.5rem 0 0 0; color: var(--color-gray); font-size: 0.875rem;">Plano atual</p>
                            </div>
                            <div class="card-body">
                                <div style="text-align: center; margin-bottom: 1rem;">
                                    <div style="font-size: 2rem; font-weight: 800; color: var(--color-gray);">❌</div>
                                </div>
                                <ul style="list-style: none; padding: 0; margin: 0;">
                                    <li style="padding: 0.5rem 0; color: var(--color-gray);">❌ Link Maestro</li>
                                    <li style="padding: 0.5rem 0; color: var(--color-gray);">❌ Links encurtados</li>
                                    <li style="padding: 0.5rem 0; color: var(--color-gray);">❌ Templates UTM</li>
                                    <li style="padding: 0.5rem 0; color: var(--color-gray);">❌ Analytics avançado</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Plano Pro -->
                        <div class="card" style="border: 2px solid #10b981; position: relative;">
                            <div style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #10b981; color: white; padding: 0.25rem 1rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                                RECOMENDADO
                            </div>
                            <div class="card-header" style="text-align: center; background: linear-gradient(135deg, #10b981, #059669);">
                                <h4 style="color: white; margin: 0;">Plano Pro</h4>
                                <p style="margin: 0.5rem 0 0 0; color: rgba(255,255,255,0.9); font-size: 0.875rem;">Para afiliados em crescimento</p>
                            </div>
                            <div class="card-body">
                                <div style="text-align: center; margin-bottom: 1rem;">
                                    <div style="font-size: 1.75rem; font-weight: 800; color: #10b981;">R$ 149</div>
                                    <div style="font-size: 0.875rem; color: var(--color-gray);">/mês</div>
                                </div>
                                <ul style="list-style: none; padding: 0; margin: 0;">
                                    <li style="padding: 0.5rem 0; color: #10b981;">✅ Link Maestro completo</li>
                                    <li style="padding: 0.5rem 0; color: #10b981;">✅ Links encurtados ilimitados</li>
                                    <li style="padding: 0.5rem 0; color: #10b981;">✅ Templates UTM avançados</li>
                                    <li style="padding: 0.5rem 0; color: #10b981;">✅ Analytics em tempo real</li>
                                    <li style="padding: 0.5rem 0; color: #10b981;">✅ Pixel BR</li>
                                    <li style="padding: 0.5rem 0; color: #10b981;">✅ Alertas WhatsApp</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Botões de ação -->
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="<?= BASE_URL ?>/subscribe" class="btn" style="background: #10b981; color: white; padding: 1rem 2rem; font-size: 1.125rem; font-weight: 600; text-decoration: none; border-radius: 8px; display: inline-flex; align-items: center; gap: 0.5rem;">
                            🚀 Fazer Upgrade para Pro
                        </a>
                        
                        <a href="<?= BASE_URL ?>/dashboard" class="btn" style="background: #6b7280; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 8px;">
                            ← Voltar ao Dashboard
                        </a>
                    </div>

                    <!-- Garantia -->
                    <div style="margin-top: 2rem; padding: 1.5rem; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="font-size: 2rem;">🛡️</div>
                            <div>
                                <h4 style="margin: 0 0 0.5rem 0; color: #92400e;">Garantia de 14 dias</h4>
                                <p style="margin: 0; color: #92400e; font-size: 0.875rem;">
                                    Teste o Link Maestro por 14 dias. Se não gostar, devolvemos 100% do seu dinheiro.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showComingSoon(feature) {
            alert(feature + ' será implementado em breve!');
        }
    </script>
</body>
</html>