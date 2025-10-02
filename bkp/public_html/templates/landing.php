<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Todos os seus números de afiliado, sem ruído</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="nav">
                <a href="<?= BASE_URL ?>" class="nav-brand">
                    <div style="width: 32px; height: 32px; background: var(--color-primary); border-radius: 6px;"></div>
                    Mercado Afiliado
                </a>
                <ul class="nav-links">
                    <li><a href="#recursos">Recursos</a></li>
                    <li><a href="#precos">Preços</a></li>
                    <li><a href="<?= BASE_URL ?>/login">Login</a></li>
                    <li><a href="<?= BASE_URL ?>/register" class="btn btn-primary">Teste grátis</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <h1>Todos os seus números de afiliado, sem ruído.</h1>
            <p>Painel unificado, UTMs padronizadas e alertas inteligentes. Comece simples, escale com confiança.</p>
            <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
                <a href="<?= BASE_URL ?>/register" class="btn btn-primary btn-lg">Começar agora</a>
                <a href="#recursos" class="btn btn-secondary btn-lg">Ver demo</a>
            </div>
        </div>
    </section>

    <!-- Recursos -->
    <section id="recursos" style="padding: 4rem 0; background: #f9fafb;">
        <div class="container">
            <div style="text-align: center; margin-bottom: 3rem;">
                <h2 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem;">Recursos principais</h2>
                <p style="font-size: 1.25rem; color: var(--color-gray); max-width: 600px; margin: 0 auto;">
                    Tudo que você precisa para monitorar e escalar suas campanhas de afiliado.
                </p>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <div class="card">
                    <div class="card-body">
                        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">Painel Unificado</h3>
                        <p style="color: var(--color-gray);">Vendas, CR e receita por período. Sem abrir mil abas.</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">Link Maestro</h3>
                        <p style="color: var(--color-gray);">UTMs consistentes, links curtos e registro de cliques.</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">Alerta Queda</h3>
                        <p style="color: var(--color-gray);">Se a conversão despencar, você fica sabendo na hora.</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">IntegraSync</h3>
                        <p style="color: var(--color-gray);">Conexões estáveis com Hotmart, Monetizze, Eduzz e Braip.</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">Pixel BR</h3>
                        <p style="color: var(--color-gray);">Coleta de eventos no seu domínio, preparado para LGPD.</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">CAPI Bridge</h3>
                        <p style="color: var(--color-gray);">Retorno de eventos server-side para otimizar seus ads.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Preços -->
    <section id="precos" style="padding: 4rem 0; background: rgba(245, 158, 11, 0.05);">
        <div class="container">
            <div style="text-align: center; margin-bottom: 3rem;">
                <h2 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem;">Planos simples</h2>
                <p style="font-size: 1.25rem; color: var(--color-gray);">
                    Teste grátis por 14 dias. Cancele quando quiser.
                </p>
            </div>
            
            <div class="pricing-grid">
                <div class="card pricing-card">
                    <div style="font-size: 0.875rem; color: var(--color-gray); text-transform: uppercase; font-weight: 600;">Starter</div>
                    <div class="price" style="margin: 1rem 0;">
                        R$79<span class="price-period">/mês</span>
                    </div>
                    <ul style="text-align: left; margin-bottom: 2rem; list-style: none;">
                        <li>✓ 2 integrações</li>
                        <li>✓ Painel Unificado</li>
                        <li>✓ Alertas por e-mail</li>
                        <li>✓ UTM Templates</li>
                    </ul>
                    <a href="<?= BASE_URL ?>/register?plan=starter" class="btn btn-primary" style="width: 100%;">Começar</a>
                </div>
                
                <div class="card pricing-card featured">
                    <div style="font-size: 0.875rem; color: var(--color-gray); text-transform: uppercase; font-weight: 600;">Pro</div>
                    <div class="price" style="margin: 1rem 0;">
                        R$149<span class="price-period">/mês</span>
                    </div>
                    <ul style="text-align: left; margin-bottom: 2rem; list-style: none;">
                        <li>✓ 4 integrações</li>
                        <li>✓ Link Maestro avançado</li>
                        <li>✓ Pixel BR</li>
                        <li>✓ Alertas WhatsApp/Telegram</li>
                        <li>✓ Cohort Reembolso</li>
                    </ul>
                    <a href="<?= BASE_URL ?>/register?plan=pro" class="btn btn-primary" style="width: 100%;">Assinar Pro</a>
                </div>
                
                <div class="card pricing-card">
                    <div style="font-size: 0.875rem; color: var(--color-gray); text-transform: uppercase; font-weight: 600;">Scale</div>
                    <div class="price" style="margin: 1rem 0;">
                        R$299<span class="price-period">/mês</span>
                    </div>
                    <ul style="text-align: left; margin-bottom: 2rem; list-style: none;">
                        <li>✓ Integrações ilimitadas</li>
                        <li>✓ CAPI Bridge</li>
                        <li>✓ Equipe & permissões</li>
                        <li>✓ Auditoria LGPD</li>
                        <li>✓ Suporte prioritário</li>
                    </ul>
                    <a href="<?= BASE_URL ?>/register?plan=scale" class="btn btn-primary" style="width: 100%;">Falar com vendas</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer style="padding: 2rem 0; border-top: 1px solid #e5e7eb;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.875rem; color: var(--color-gray);">
                <span>&copy; 2025 Mercado Afiliado</span>
                <a href="#" style="color: var(--color-gray); text-decoration: none;">Privacidade</a>
            </div>
        </div>
    </footer>
</body>
</html>