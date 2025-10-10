<?php
/**
 * FAQ - Perguntas Frequentes
 * Com Schema.org FAQPage markup para SEO e AI Search
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perguntas Frequentes (FAQ) - Mercado Afiliado</title>
    <meta name="description" content="Respostas para as perguntas mais frequentes sobre o Mercado Afiliado: como funciona, preços, integrações, rastreamento e muito mais.">

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

        /* Estilos da FAQ */
        .faq-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 60px 20px;
        }
        .faq-header {
            text-align: center;
            margin-bottom: 60px;
        }
        .faq-header h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 48px;
            color: #1a202c;
            margin-bottom: 16px;
        }
        .faq-header p {
            font-size: 18px;
            color: #718096;
        }
        .faq-categories {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 40px;
        }
        .category-btn {
            padding: 12px 24px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            color: #4a5568;
        }
        .category-btn:hover, .category-btn.active {
            border-color: var(--color-primary);
            background: var(--color-primary);
            color: white;
        }
        .faq-item {
            background: white;
            border-radius: 12px;
            margin-bottom: 16px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .faq-question {
            padding: 24px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }
        .faq-question:hover {
            background: #f7fafc;
        }
        .faq-question h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 18px;
            color: #2d3748;
            margin: 0;
            flex: 1;
        }
        .faq-icon {
            transition: transform 0.3s;
            color: var(--color-primary);
        }
        .faq-item.active .faq-icon {
            transform: rotate(180deg);
        }
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        .faq-item.active .faq-answer {
            max-height: 1000px;
        }
        .faq-answer-content {
            padding: 0 24px 24px 24px;
            color: #4a5568;
            line-height: 1.8;
        }
        .faq-answer-content p {
            margin-bottom: 12px;
        }
        .faq-answer-content ul {
            margin-left: 20px;
            margin-bottom: 12px;
        }
        .faq-answer-content code {
            background: #f7fafc;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #e53e3e;
        }
        .search-box {
            margin-bottom: 40px;
        }
        .search-box input {
            width: 100%;
            padding: 16px 24px;
            font-size: 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.3s;
        }
        .search-box input:focus {
            outline: none;
            border-color: var(--color-primary);
        }
        .cta-box {
            background: linear-gradient(135deg, var(--color-primary) 0%, #d97706 100%);
            color: white;
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            margin-top: 60px;
        }
        .cta-box h2 {
            font-size: 28px;
            margin-bottom: 12px;
            color: white;
        }
        .cta-box p {
            margin-bottom: 24px;
            color: rgba(255,255,255,0.9);
        }
        .cta-box a {
            display: inline-block;
            background: white;
            color: var(--color-primary);
            padding: 12px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .cta-box a:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body itemscope itemtype="https://schema.org/FAQPage">
    <?php include __DIR__ . '/../../app/components/header.php'; ?>

    <div class="faq-container">
        <div class="faq-header">
            <h1>Perguntas Frequentes</h1>
            <p>Encontre respostas para as dúvidas mais comuns sobre o Mercado Afiliado</p>
        </div>

        <div class="search-box">
            <input type="text" id="searchInput" placeholder="🔍 Buscar pergunta..." onkeyup="searchFAQ()">
        </div>

        <!-- GERAL -->
        <h2 style="font-family: 'Poppins', sans-serif; color: #2d3748; margin: 40px 0 24px 0;">📌 Geral</h2>

        <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <h3 itemprop="name">O que é o Mercado Afiliado?</h3>
                <i data-lucide="chevron-down" class="faq-icon"></i>
            </div>
            <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                <div class="faq-answer-content" itemprop="text">
                    <p>
                        <strong>Mercado Afiliado</strong> é uma plataforma completa de rastreamento e análise de dados
                        para afiliados digitais brasileiros. Permite rastrear links, eventos de conversão, integrar com
                        Hotmart, Eduzz, Facebook Ads e Google Ads em um só lugar.
                    </p>
                    <p>
                        É como ter um "Google Analytics turbinado" especialmente desenvolvido para quem trabalha com
                        marketing de afiliados e infoprodutos.
                    </p>
                </div>
            </div>
        </div>

        <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <h3 itemprop="name">Quanto custa o Mercado Afiliado?</h3>
                <i data-lucide="chevron-down" class="faq-icon"></i>
            </div>
            <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                <div class="faq-answer-content" itemprop="text">
                    <p><strong>Planos disponíveis:</strong></p>
                    <ul>
                        <li><strong>Gratuito:</strong> Recursos básicos para iniciantes (até 1.000 cliques/mês)</li>
                        <li><strong>Starter (R$ 97/mês):</strong> 10.000 cliques, 1 pixel, integração básica</li>
                        <li><strong>Pro (R$ 197/mês):</strong> 100.000 cliques, pixels ilimitados, CAPI completo</li>
                        <li><strong>Enterprise (R$ 397/mês):</strong> Cliques ilimitados, white-label, API completa</li>
                    </ul>
                    <p>Todos os planos pagos incluem 7 dias de teste grátis.</p>
                </div>
            </div>
        </div>

        <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <h3 itemprop="name">Qual a diferença entre Mercado Afiliado e Hotmart?</h3>
                <i data-lucide="chevron-down" class="faq-icon"></i>
            </div>
            <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                <div class="faq-answer-content" itemprop="text">
                    <p>
                        <strong>Hotmart</strong> é uma plataforma de vendas de produtos digitais (checkout, pagamento, entrega).
                    </p>
                    <p>
                        <strong>Mercado Afiliado</strong> é uma ferramenta de rastreamento que SE INTEGRA com Hotmart
                        para rastrear suas campanhas de marketing e calcular ROI.
                    </p>
                    <p>
                        Pense assim: Hotmart processa as vendas, Mercado Afiliado mostra de onde vieram essas vendas.
                    </p>
                </div>
            </div>
        </div>

        <!-- PIXEL BR -->
        <h2 style="font-family: 'Poppins', sans-serif; color: #2d3748; margin: 40px 0 24px 0;">👁️ Pixel BR</h2>

        <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <h3 itemprop="name">Como funciona o Pixel BR?</h3>
                <i data-lucide="chevron-down" class="faq-icon"></i>
            </div>
            <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                <div class="faq-answer-content" itemprop="text">
                    <p>
                        Pixel BR é um código JavaScript que você instala em suas páginas. Ele rastreia eventos como
                        visualizações de página, leads capturados e compras realizadas.
                    </p>
                    <p>
                        É compatível com LGPD e envia dados via CAPI para Facebook e Google, melhorando a performance
                        dos seus anúncios.
                    </p>
                </div>
            </div>
        </div>

        <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <h3 itemprop="name">Como instalar o Pixel BR no meu site?</h3>
                <i data-lucide="chevron-down" class="faq-icon"></i>
            </div>
            <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                <div class="faq-answer-content" itemprop="text">
                    <p><strong>Passo a passo:</strong></p>
                    <ol>
                        <li>Acesse o painel do Mercado Afiliado</li>
                        <li>Vá em "Pixel BR" → "Configuração"</li>
                        <li>Copie o código gerado</li>
                        <li>Cole no <code>&lt;head&gt;</code> do seu site, antes do <code>&lt;/head&gt;</code></li>
                        <li>Teste usando o Simulador de Eventos</li>
                    </ol>
                    <p>Temos tutoriais em vídeo para WordPress, HTML, Elementor e outras plataformas.</p>
                </div>
            </div>
        </div>

        <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <h3 itemprop="name">O Pixel BR é compatível com LGPD?</h3>
                <i data-lucide="chevron-down" class="faq-icon"></i>
            </div>
            <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                <div class="faq-answer-content" itemprop="text">
                    <p>
                        <strong>Sim!</strong> O Pixel BR possui sistema de consent management integrado e só coleta
                        dados após consentimento explícito do usuário, conforme exigido pela LGPD.
                    </p>
                    <p>
                        Você pode configurar um banner de cookies ou usar APIs de consentimento de terceiros.
                    </p>
                </div>
            </div>
        </div>

        <!-- LINK MAESTRO -->
        <h2 style="font-family: 'Poppins', sans-serif; color: #2d3748; margin: 40px 0 24px 0;">🔗 Link Maestro</h2>

        <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <h3 itemprop="name">O que é o Link Maestro?</h3>
                <i data-lucide="chevron-down" class="faq-icon"></i>
            </div>
            <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                <div class="faq-answer-content" itemprop="text">
                    <p>
                        Link Maestro é o sistema de rastreamento de links do Mercado Afiliado. Você cria links curtos
                        rastreados que registram cada clique, origem, dispositivo e conversão.
                    </p>
                    <p>
                        É como um Bitly turbinado com analytics avançados.
                    </p>
                </div>
            </div>
        </div>

        <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <h3 itemprop="name">Posso usar meu próprio domínio nos links?</h3>
                <i data-lucide="chevron-down" class="faq-icon"></i>
            </div>
            <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                <div class="faq-answer-content" itemprop="text">
                    <p>
                        <strong>Sim!</strong> No plano Pro e Enterprise você pode configurar um domínio customizado
                        (ex: <code>go.seudominio.com.br</code>) para seus links rastreados.
                    </p>
                    <p>
                        Isso melhora a credibilidade e taxa de cliques dos seus links.
                    </p>
                </div>
            </div>
        </div>

        <!-- INTEGRAÇÕES -->
        <h2 style="font-family: 'Poppins', sans-serif; color: #2d3748; margin: 40px 0 24px 0;">⚡ Integrações</h2>

        <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <h3 itemprop="name">Como integrar com Hotmart?</h3>
                <i data-lucide="chevron-down" class="faq-icon"></i>
            </div>
            <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                <div class="faq-answer-content" itemprop="text">
                    <p><strong>Configuração automática via Webhook:</strong></p>
                    <ol>
                        <li>No Mercado Afiliado, vá em "IntegraSync" → "Nova Integração"</li>
                        <li>Selecione "Hotmart"</li>
                        <li>Copie a URL do webhook fornecida</li>
                        <li>No painel Hotmart, vá em "Ferramentas" → "Webhooks"</li>
                        <li>Cole a URL e ative</li>
                    </ol>
                    <p>Pronto! Todas as vendas serão recebidas automaticamente.</p>
                </div>
            </div>
        </div>

        <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <h3 itemprop="name">Mercado Afiliado funciona com Facebook Ads?</h3>
                <i data-lucide="chevron-down" class="faq-icon"></i>
            </div>
            <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                <div class="faq-answer-content" itemprop="text">
                    <p>
                        <strong>Sim!</strong> O Mercado Afiliado envia eventos de conversão via Facebook Conversions API (CAPI),
                        permitindo otimização de campanhas e remarketing avançado.
                    </p>
                    <p>
                        Isso melhora significativamente os resultados dos seus anúncios, especialmente após as restrições
                        de tracking do iOS 14+.
                    </p>
                </div>
            </div>
        </div>

        <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <h3 itemprop="name">Quais plataformas são suportadas?</h3>
                <i data-lucide="chevron-down" class="faq-icon"></i>
            </div>
            <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                <div class="faq-answer-content" itemprop="text">
                    <p><strong>Plataformas de vendas:</strong></p>
                    <ul>
                        <li>Hotmart</li>
                        <li>Eduzz</li>
                        <li>Monetizze (em breve)</li>
                        <li>Kiwify (em breve)</li>
                    </ul>
                    <p><strong>Plataformas de anúncios:</strong></p>
                    <ul>
                        <li>Facebook Ads (via CAPI)</li>
                        <li>Google Ads (via Enhanced Conversions)</li>
                        <li>TikTok Ads (via Events API)</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- TÉCNICO -->
        <h2 style="font-family: 'Poppins', sans-serif; color: #2d3748; margin: 40px 0 24px 0;">🛠️ Técnico</h2>

        <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <h3 itemprop="name">O Mercado Afiliado tem API?</h3>
                <i data-lucide="chevron-down" class="faq-icon"></i>
            </div>
            <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                <div class="faq-answer-content" itemprop="text">
                    <p>
                        <strong>Sim!</strong> No plano Enterprise você tem acesso à API REST completa para:
                    </p>
                    <ul>
                        <li>Criar links programaticamente</li>
                        <li>Enviar eventos customizados</li>
                        <li>Consultar estatísticas</li>
                        <li>Gerenciar integrações</li>
                        <li>Exportar dados</li>
                    </ul>
                    <p>Documentação completa disponível em <code>/docs/api</code></p>
                </div>
            </div>
        </div>

        <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <h3 itemprop="name">O Pixel BR deixa meu site lento?</h3>
                <i data-lucide="chevron-down" class="faq-icon"></i>
            </div>
            <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                <div class="faq-answer-content" itemprop="text">
                    <p>
                        <strong>Não!</strong> O Pixel BR é extremamente otimizado:
                    </p>
                    <ul>
                        <li>Apenas 4KB de tamanho (menor que uma imagem)</li>
                        <li>Carregamento assíncrono (não bloqueia a página)</li>
                        <li>CDN global para baixa latência</li>
                        <li>Impacto zero no Google PageSpeed</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- SUPORTE -->
        <h2 style="font-family: 'Poppins', sans-serif; color: #2d3748; margin: 40px 0 24px 0;">💬 Suporte</h2>

        <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <h3 itemprop="name">Como entrar em contato com o suporte?</h3>
                <i data-lucide="chevron-down" class="faq-icon"></i>
            </div>
            <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                <div class="faq-answer-content" itemprop="text">
                    <p><strong>Canais de suporte:</strong></p>
                    <ul>
                        <li><strong>Chat ao vivo:</strong> Disponível dentro do painel (planos pagos)</li>
                        <li><strong>Email:</strong> suporte@mercadoafiliado.com.br</li>
                        <li><strong>WhatsApp:</strong> Disponível no plano Enterprise</li>
                        <li><strong>Base de conhecimento:</strong> Artigos e tutoriais em /docs</li>
                    </ul>
                    <p>Tempo médio de resposta: 2-4 horas em dias úteis.</p>
                </div>
            </div>
        </div>

        <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <h3 itemprop="name">Tem garantia de reembolso?</h3>
                <i data-lucide="chevron-down" class="faq-icon"></i>
            </div>
            <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                <div class="faq-answer-content" itemprop="text">
                    <p>
                        <strong>Sim!</strong> Oferecemos <strong>7 dias de garantia incondicional</strong>. Se não ficar
                        satisfeito, basta solicitar o reembolso total via email.
                    </p>
                    <p>Sem perguntas, sem burocracia.</p>
                </div>
            </div>
        </div>

        <div class="cta-box">
            <h2>Não encontrou sua resposta?</h2>
            <p>Entre em contato conosco ou crie uma conta gratuita para testar na prática.</p>
            <a href="<?= BASE_URL ?>/register">Criar Conta Grátis</a>
        </div>
    </div>

    <?php include __DIR__ . '/../../app/components/footer.php'; ?>

    <script>
        lucide.createIcons();

        function toggleFAQ(element) {
            const item = element.closest('.faq-item');
            const wasActive = item.classList.contains('active');

            // Fechar todos
            document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));

            // Abrir o clicado (se não estava ativo)
            if (!wasActive) {
                item.classList.add('active');
            }
        }

        function searchFAQ() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const items = document.querySelectorAll('.faq-item');

            items.forEach(item => {
                const question = item.querySelector('.faq-question h3').textContent.toLowerCase();
                const answer = item.querySelector('.faq-answer-content').textContent.toLowerCase();

                if (question.includes(filter) || answer.includes(filter)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
