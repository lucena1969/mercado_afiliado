<?php
/**
 * Página Sobre - Mercado Afiliado
 * Otimizada para SEO e AI Search Engines
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre o Mercado Afiliado | Plataforma de Rastreamento para Afiliados Brasileiros</title>
    <meta name="description" content="Mercado Afiliado é uma plataforma completa de rastreamento e análise para afiliados digitais. Rastreie links, eventos, integre com Hotmart, Eduzz, Facebook Ads e Google Ads em um só lugar.">
    <meta name="keywords" content="mercado afiliado, rastreamento de afiliados, pixel de conversão, hotmart, eduzz, facebook capi, google ads, analytics">

    <!-- Open Graph -->
    <meta property="og:title" content="Sobre o Mercado Afiliado">
    <meta property="og:description" content="Plataforma completa de rastreamento para afiliados digitais brasileiros">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= BASE_URL ?>/sobre">

    <!-- Schema.org -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "Mercado Afiliado",
      "applicationCategory": "BusinessApplication",
      "operatingSystem": "Web",
      "description": "Plataforma completa de rastreamento e análise para afiliados digitais brasileiros. Link Maestro para rastreamento de links, Pixel BR para eventos, IntegraSync para integrações com Hotmart, Eduzz, Facebook Ads e Google Ads.",
      "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "BRL"
      },
      "featureList": [
        "Rastreamento de links (Link Maestro)",
        "Pixel de conversão brasileiro (Pixel BR)",
        "Integração Hotmart e Eduzz (IntegraSync)",
        "Facebook CAPI e Google Enhanced Conversions",
        "Painel unificado de métricas",
        "Cálculo automático de ROI"
      ],
      "author": {
        "@type": "Organization",
        "name": "Mercado Afiliado",
        "url": "<?= BASE_URL ?>"
      }
    }
    </script>

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        /* Variáveis globais */
        :root {
            --color-primary: #f59e0b;
        }

        /* Reset básico */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f9fafb;
            color: #1a202c;
            line-height: 1.6;
        }

        /* Estilos do Header */
        .app-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .app-header-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #1a202c;
            font-weight: 600;
            font-size: 18px;
            transition: opacity 0.2s;
        }

        .app-header-logo:hover {
            opacity: 0.8;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--color-primary, #f59e0b), #d97706);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 20px;
        }

        .app-header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .dropdown {
            position: relative;
        }

        .app-header-user {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .app-header-user:hover {
            background: #f7fafc;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--color-primary, #f59e0b), #d97706);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .user-name {
            font-size: 14px;
            font-weight: 500;
            color: #2d3748;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 200px;
            overflow: hidden;
            z-index: 1001;
        }

        .dropdown.active .dropdown-content {
            display: block;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            text-decoration: none;
            color: #2d3748;
            transition: background 0.2s;
            font-size: 14px;
        }

        .dropdown-item:hover {
            background: #f7fafc;
        }

        .dropdown-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 4px 0;
        }

        /* Estilos da página Sobre */
        .sobre-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
        }
        .hero-section {
            text-align: center;
            margin-bottom: 80px;
        }
        .hero-section h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 48px;
            color: #1a202c;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        .hero-section .subtitle {
            font-size: 20px;
            color: #718096;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
        }
        .content-section {
            margin-bottom: 60px;
        }
        .content-section h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 32px;
            color: #2d3748;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .content-section h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 24px;
            color: #2d3748;
            margin: 30px 0 15px 0;
        }
        .content-section p {
            font-size: 16px;
            line-height: 1.8;
            color: #4a5568;
            margin-bottom: 16px;
        }
        .content-section ul {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        .content-section ul li {
            padding: 12px 0 12px 32px;
            position: relative;
            color: #4a5568;
            line-height: 1.6;
        }
        .content-section ul li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: var(--color-primary);
            font-weight: bold;
            font-size: 20px;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin: 40px 0;
        }
        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 4px solid var(--color-primary);
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        }
        .feature-card h3 {
            font-size: 20px;
            margin: 16px 0 12px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .feature-card p {
            font-size: 14px;
            color: #718096;
            margin: 0;
        }
        .cta-section {
            background: linear-gradient(135deg, var(--color-primary) 0%, #d97706 100%);
            color: white;
            padding: 60px 40px;
            border-radius: 16px;
            text-align: center;
            margin-top: 80px;
        }
        .cta-section h2 {
            font-size: 36px;
            margin-bottom: 20px;
            color: white;
        }
        .cta-section p {
            font-size: 18px;
            margin-bottom: 30px;
            color: rgba(255,255,255,0.9);
        }
        .cta-button {
            display: inline-block;
            background: white;
            color: var(--color-primary);
            padding: 16px 40px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 18px;
            transition: all 0.3s;
        }
        .cta-button:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .comparison-table th {
            background: var(--color-primary);
            color: white;
            padding: 16px;
            text-align: left;
            font-weight: 600;
        }
        .comparison-table td {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        .comparison-table tr:last-child td {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../app/components/header.php'; ?>

    <div class="sobre-container">
        <div class="hero-section">
            <h1>O que é o Mercado Afiliado?</h1>
            <p class="subtitle">
                Uma plataforma completa de rastreamento e análise de dados desenvolvida especialmente
                para profissionais de marketing de afiliados brasileiros.
            </p>
        </div>

        <div class="content-section">
            <h2><i data-lucide="target" style="width: 32px; height: 32px;"></i> Nossa Missão</h2>
            <p>
                <strong>Mercado Afiliado</strong> foi criado para resolver um problema real: a falta de ferramentas
                de rastreamento acessíveis e em português para afiliados brasileiros.
            </p>
            <p>
                Enquanto ferramentas internacionais como Voluum e ClickMagick cobram valores proibitivos e não
                integram nativamente com plataformas brasileiras (Hotmart, Eduzz), nós oferecemos uma solução
                completa, acessível e 100% adaptada ao mercado brasileiro.
            </p>
        </div>

        <div class="content-section">
            <h2><i data-lucide="layers" style="width: 32px; height: 32px;"></i> O que o Mercado Afiliado faz?</h2>
            <p>
                O Mercado Afiliado permite que afiliados digitais rastreiem, analisem e otimizem suas
                campanhas de marketing através de quatro módulos principais:
            </p>

            <div class="features-grid">
                <div class="feature-card">
                    <i data-lucide="link" style="width: 40px; height: 40px; color: var(--color-primary);"></i>
                    <h3>Link Maestro</h3>
                    <p>
                        Sistema de criação e rastreamento de links inteligentes com suporte a parâmetros UTM,
                        redirecionamento condicional e análise detalhada de cliques por fonte de tráfego.
                    </p>
                </div>

                <div class="feature-card">
                    <i data-lucide="eye" style="width: 40px; height: 40px; color: var(--color-primary);"></i>
                    <h3>Pixel BR</h3>
                    <p>
                        Pixel de conversão 100% compatível com LGPD que rastreia eventos (pageviews, leads, compras)
                        e integra com Facebook CAPI e Google Enhanced Conversions.
                    </p>
                </div>

                <div class="feature-card">
                    <i data-lucide="zap" style="width: 40px; height: 40px; color: var(--color-primary);"></i>
                    <h3>IntegraSync</h3>
                    <p>
                        Integração automática com Hotmart, Eduzz, Facebook Ads e Google Ads para receber
                        dados de vendas e enviar eventos de conversão via CAPI/S2S.
                    </p>
                </div>

                <div class="feature-card">
                    <i data-lucide="bar-chart-3" style="width: 40px; height: 40px; color: var(--color-primary);"></i>
                    <h3>Painel Unificado</h3>
                    <p>
                        Dashboard centralizado com todas as métricas de desempenho, cálculo automático de ROI,
                        análise de funil e relatórios personalizáveis.
                    </p>
                </div>
            </div>
        </div>

        <div class="content-section">
            <h2><i data-lucide="users" style="width: 32px; height: 32px;"></i> Para quem é o Mercado Afiliado?</h2>
            <ul>
                <li><strong>Afiliados iniciantes e profissionais</strong> de plataformas como Hotmart, Eduzz e Monetizze que querem rastrear suas campanhas sem gastar fortunas</li>
                <li><strong>Produtores digitais</strong> que vendem infoprodutos e precisam entender de onde vêm suas vendas</li>
                <li><strong>Agências de marketing digital</strong> especializadas em performance que gerenciam campanhas de múltiplos clientes</li>
                <li><strong>Profissionais de tráfego pago</strong> (Facebook Ads, Google Ads, TikTok Ads) que precisam calcular ROI preciso</li>
                <li><strong>Consultores e mentores</strong> que ensinam marketing digital e querem uma ferramenta nacional para recomendar</li>
            </ul>
        </div>

        <div class="content-section">
            <h2><i data-lucide="check-circle" style="width: 32px; height: 32px;"></i> Por que usar o Mercado Afiliado?</h2>

            <h3>Vantagens competitivas:</h3>
            <ul>
                <li><strong>100% em Português:</strong> Interface, suporte e documentação totalmente em português brasileiro</li>
                <li><strong>Conformidade com LGPD:</strong> Sistema de consent management e proteção de dados pessoais</li>
                <li><strong>Integração nativa:</strong> Conecta diretamente com Hotmart e Eduzz via webhook</li>
                <li><strong>Custo acessível:</strong> Plano gratuito para iniciantes e planos pagos a partir de R$ 97/mês</li>
                <li><strong>Servidor no Brasil:</strong> Menor latência e dados armazenados em território nacional</li>
                <li><strong>Suporte local:</strong> Atendimento em português e entendimento do mercado brasileiro</li>
            </ul>

            <h3>Comparação com concorrentes:</h3>
            <table class="comparison-table">
                <tr>
                    <th>Recurso</th>
                    <th>Mercado Afiliado</th>
                    <th>Voluum</th>
                    <th>ClickMagick</th>
                </tr>
                <tr>
                    <td>Preço mensal</td>
                    <td><strong>R$ 97 - R$ 297</strong></td>
                    <td>US$ 89 (~R$ 450)</td>
                    <td>US$ 47 (~R$ 240)</td>
                </tr>
                <tr>
                    <td>Interface em Português</td>
                    <td>✅ Sim</td>
                    <td>❌ Não</td>
                    <td>❌ Não</td>
                </tr>
                <tr>
                    <td>Integração Hotmart/Eduzz</td>
                    <td>✅ Nativa</td>
                    <td>⚠️ Manual</td>
                    <td>⚠️ Manual</td>
                </tr>
                <tr>
                    <td>LGPD Compliant</td>
                    <td>✅ Sim</td>
                    <td>⚠️ GDPR (Europa)</td>
                    <td>⚠️ GDPR (Europa)</td>
                </tr>
                <tr>
                    <td>Suporte em Português</td>
                    <td>✅ Sim</td>
                    <td>❌ Inglês</td>
                    <td>❌ Inglês</td>
                </tr>
                <tr>
                    <td>Servidor no Brasil</td>
                    <td>✅ Sim</td>
                    <td>❌ EUA/Europa</td>
                    <td>❌ EUA</td>
                </tr>
            </table>
        </div>

        <div class="content-section">
            <h2><i data-lucide="settings" style="width: 32px; height: 32px;"></i> Principais Recursos Técnicos</h2>
            <ul>
                <li><strong>Rastreamento de links:</strong> Sistema próprio de encurtamento e tracking com suporte total a UTM parameters</li>
                <li><strong>Pixel de conversão:</strong> JavaScript snippet instalável em qualquer site ou landing page</li>
                <li><strong>CAPI/S2S:</strong> Envio server-to-server para Facebook Conversions API e Google Offline Conversions</li>
                <li><strong>Webhooks:</strong> Recebimento automático de postbacks do Hotmart, Eduzz e outras plataformas</li>
                <li><strong>API REST:</strong> Endpoints completos para integração personalizada e automação</li>
                <li><strong>Dashboard em tempo real:</strong> Métricas atualizadas instantaneamente via WebSocket</li>
                <li><strong>Exportação de dados:</strong> CSV, Excel e PDF para análises externas</li>
            </ul>
        </div>

        <div class="content-section">
            <h2><i data-lucide="rocket" style="width: 32px; height: 32px;"></i> Como Começar?</h2>
            <p>Começar a usar o Mercado Afiliado é simples e rápido:</p>
            <ol style="list-style: decimal; margin-left: 20px; line-height: 2;">
                <li>Crie uma conta gratuita em <a href="<?= BASE_URL ?>/register" style="color: var(--color-primary); font-weight: 600;">mercadoafiliado.com.br</a></li>
                <li>Configure sua primeira integração (Hotmart ou Eduzz)</li>
                <li>Crie seu primeiro pixel de rastreamento</li>
                <li>Instale o pixel em suas landing pages</li>
                <li>Crie links rastreados para suas campanhas</li>
                <li>Acompanhe os resultados no Painel Unificado</li>
            </ol>
            <p>Todo o processo leva menos de 15 minutos e temos tutoriais em vídeo para cada etapa.</p>
        </div>

        <div class="content-section">
            <h2><i data-lucide="shield-check" style="width: 32px; height: 32px;"></i> Segurança e Privacidade</h2>
            <p>
                Levamos a segurança dos seus dados muito a sério. O Mercado Afiliado utiliza:
            </p>
            <ul>
                <li><strong>Criptografia SSL/TLS:</strong> Todos os dados trafegam de forma criptografada</li>
                <li><strong>Conformidade LGPD:</strong> Sistema de consent management e anonimização de dados</li>
                <li><strong>Backup automático:</strong> Seus dados são salvos diariamente em múltiplos servidores</li>
                <li><strong>Autenticação segura:</strong> Senhas criptografadas com bcrypt e suporte a 2FA</li>
                <li><strong>Logs de auditoria:</strong> Rastreamento completo de acessos e modificações</li>
            </ul>
        </div>

        <div class="cta-section">
            <h2>Pronto para começar?</h2>
            <p>Crie sua conta gratuita agora e comece a rastrear suas campanhas em minutos.</p>
            <a href="<?= BASE_URL ?>/register" class="cta-button">Criar Conta Gratuita</a>
        </div>
    </div>

    <?php include __DIR__ . '/../../app/components/footer.php'; ?>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
