<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Verificação Google Search Console -->
  <meta name="google-site-verification" content="uTHlQMgDFtWeseJOU0EmQ4U6Glm98qxowIpSTFPRM70" />

  <title>Mercado Afiliado — Performance inteligente</title>
  <meta name="description" content="Landing do Mercado Afiliado: UTMs inteligentes, integrações e Pixel BR server-side."/>
  <style>
    :root{
      --bg: #fcfbf7;
      --text: #1f2937;
      --muted: #6b7280;
      --mustard-700: #b38609;
      --mustard-600: #d6a426;
      --mustard-500: #e7b73b;
      --mustard-100: #fff4d6;
      --blue-600: #2563eb;
      --blue-700: #1d4ed8;
      --green-600: #16a34a;
      --card-border: #e5e7eb;
      --shadow: 0 6px 16px rgba(17,24,39,.08);
      --radius: 14px;
    }
    * { box-sizing: border-box; }
    html,body { margin:0; padding:0; background:var(--bg); color:var(--text); font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
    a { color: inherit; text-decoration: none; cursor: pointer; }
    .container { max-width: 1120px; margin: 0 auto; padding: 0 20px; }

    /* Header */
    header.hero {
      position: relative;
      color: #fff;
      padding: 72px 0 88px;
      background:
        radial-gradient(1200px 600px at 70% -10%, rgba(255,255,255,0.2), transparent 60%),
        linear-gradient(135deg, var(--mustard-700), var(--mustard-500));
      overflow: hidden;
    }
    header .brand {
      display:flex; align-items:center; gap:10px; margin-bottom:28px;
    }
    .brand-mark {
      width:32px; height:32px; border-radius:8px;
      background: linear-gradient(135deg,#fff,rgba(255,255,255,.4));
      outline: 2px solid rgba(255,255,255,.35);
      outline-offset: 2px;
    }
    header h1 { font-size: clamp(2rem, 4vw, 3rem); line-height:1.1; margin:0 0 10px; font-weight: 800; letter-spacing: -.02em;}
    header p.sub { margin:0 0 24px; font-size: clamp(1.05rem, 1.8vw, 1.25rem); color: rgba(255,255,255,.92); max-width: 800px; }
    .cta-row { display:flex; gap:12px; flex-wrap:wrap; }

    .btn {
      display:inline-flex; align-items:center; justify-content:center; gap:10px;
      padding: 12px 18px; border-radius: 12px; font-weight: 700; letter-spacing:.2px;
      border: 2px solid transparent; transition: all .2s ease;
      cursor: pointer;
    }
    .btn-primary {
      background: var(--blue-600); color:#fff; border-color: rgba(255,255,255,.0);
      box-shadow: 0 8px 18px rgba(37,99,235,.25);
    }
    .btn-primary:hover { background: var(--blue-700); transform: translateY(-1px); }
    .btn-ghost {
      background: transparent; color:#fff; border-color: rgba(255,255,255,.45);
    }
    .btn-ghost:hover { background: rgba(255,255,255,.12); }

    /* Pills */
    .pill-row { display:flex; gap:10px; flex-wrap:wrap; margin-top:16px; }
    .pill {
      color:#fff; border:1.5px dashed rgba(255,255,255,.45); border-radius:999px; padding:8px 12px; font-size:.9rem;
      backdrop-filter: blur(2px);
    }

    /* Features */
    section.features { padding: 56px 0; }
    .grid { display:grid; gap:20px; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
    .card {
      background:#fff; border:1px solid var(--card-border); border-radius: var(--radius); padding:22px;
      box-shadow: var(--shadow); transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }
    .card:hover { transform: translateY(-4px); box-shadow: 0 10px 24px rgba(17,24,39,.1); border-color: #d1d5db; }
    .card h3 { margin:10px 0 8px; font-size:1.1rem; }
    .card p { margin:0; color: var(--muted); line-height:1.55; }
    .icon {
      width:42px; height:42px; display:inline-grid; place-items:center; border-radius:12px;
      background: var(--mustard-100); border:1px solid #f1e4b3;
    }
    .icon svg { width:22px; height:22px; stroke: #8a6a03; }

    /* Price strip */
    .strip {
      margin: 28px 0 0; padding: 14px 16px; border: 1px dashed #ead08a; background: #fff9e9;
      border-radius: 12px; color:#5b4a11; font-size:.95rem;
    }

    /* Footer */
    footer { margin-top: 48px; padding: 28px 0; background:#111827; color:#e5e7eb; }
    footer .foot { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .foot small { color:#9ca3af; }
    .links a{ color:#e5e7eb; opacity:.9; margin-left:14px; }
    .links a:hover { opacity:1; text-decoration: underline; }

    /* Section titles */
    .section-title { text-align:center; font-weight:800; letter-spacing:-.02em; margin: 8px 0 24px; font-size: clamp(1.4rem, 2.2vw, 1.8rem); color:#101828; }

    /* How it works section */
    .how-it-works {
      padding: 72px 0;
      background: linear-gradient(180deg, #fff 0%, #f9fafb 100%);
    }
    .steps-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 40px;
      margin-top: 48px;
      position: relative;
    }
    .step {
      text-align: center;
      position: relative;
    }
    .step-number {
      width: 64px;
      height: 64px;
      margin: 0 auto 20px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--mustard-600), var(--mustard-500));
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.75rem;
      font-weight: 800;
      box-shadow: 0 8px 20px rgba(211,164,38,.3);
    }
    .step h3 {
      font-size: 1.25rem;
      font-weight: 700;
      margin: 0 0 12px;
      color: var(--text);
    }
    .step p {
      color: var(--muted);
      line-height: 1.6;
      margin: 0;
    }
    .step-icon {
      width: 48px;
      height: 48px;
      margin: 0 auto 16px;
      padding: 12px;
      border-radius: 12px;
      background: var(--mustard-100);
      border: 1px solid #f1e4b3;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .step-icon svg {
      width: 24px;
      height: 24px;
      stroke: #8a6a03;
    }
    /* Arrow connector for desktop */
    @media (min-width: 768px) {
      .step:not(:last-child)::after {
        content: '→';
        position: absolute;
        right: -30px;
        top: 32px;
        font-size: 2rem;
        color: var(--mustard-400);
        opacity: 0.4;
      }
    }
    /* CTA section */
    .cta-section {
      text-align: center;
      padding: 56px 0;
      background: var(--bg);
    }
    .cta-section h2 {
      font-size: clamp(1.75rem, 3vw, 2.25rem);
      font-weight: 800;
      margin: 0 0 16px;
      color: var(--text);
    }
    .cta-section p {
      font-size: 1.1rem;
      color: var(--muted);
      margin: 0 0 32px;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }
    .cta-section .btn-primary {
      font-size: 1.1rem;
      padding: 16px 32px;
    }
    /* FAQ section */
    .faq-section {
      padding: 72px 0;
      background: #fff;
    }
    .faq-container {
      max-width: 800px;
      margin: 48px auto 0;
    }
    .faq-item {
      border-bottom: 1px solid var(--card-border);
    }
    .faq-question {
      width: 100%;
      text-align: left;
      padding: 24px 0;
      background: none;
      border: none;
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--text);
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: color 0.2s ease;
    }
    .faq-question:hover {
      color: var(--blue-600);
    }
    .faq-icon {
      width: 24px;
      height: 24px;
      transition: transform 0.3s ease;
      flex-shrink: 0;
      margin-left: 16px;
    }
    .faq-icon svg {
      width: 100%;
      height: 100%;
      stroke: var(--mustard-600);
      stroke-width: 2;
    }
    .faq-item.active .faq-icon {
      transform: rotate(180deg);
    }
    .faq-answer {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease, padding 0.3s ease;
      padding: 0;
    }
    .faq-item.active .faq-answer {
      max-height: 500px;
      padding-bottom: 24px;
    }
    .faq-answer p {
      color: var(--muted);
      line-height: 1.7;
      margin: 0;
    }
    .faq-answer strong {
      color: var(--text);
    }
    /* Modal styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.6);
      backdrop-filter: blur(4px);
      animation: fadeIn 0.2s ease;
    }
    .modal.active {
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .modal-content {
      background: #fff;
      border-radius: 16px;
      max-width: 800px;
      max-height: 85vh;
      width: 90%;
      position: relative;
      animation: slideUp 0.3s ease;
      display: flex;
      flex-direction: column;
    }
    .modal-header {
      padding: 24px 28px;
      border-bottom: 1px solid var(--card-border);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-shrink: 0;
    }
    .modal-header h2 {
      margin: 0;
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--text);
    }
    .modal-close {
      background: none;
      border: none;
      font-size: 1.5rem;
      color: var(--muted);
      cursor: pointer;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 6px;
      transition: all 0.2s ease;
    }
    .modal-close:hover {
      background: var(--mustard-100);
      color: var(--mustard-700);
    }
    .modal-body {
      padding: 28px;
      overflow-y: auto;
      flex: 1;
    }
    .modal-body h3 {
      color: var(--mustard-700);
      font-size: 1.1rem;
      margin: 24px 0 12px;
    }
    .modal-body h3:first-child {
      margin-top: 0;
    }
    .modal-body p {
      line-height: 1.7;
      color: var(--muted);
      margin: 0 0 16px;
    }
    .modal-body ul {
      color: var(--muted);
      line-height: 1.7;
      margin: 0 0 16px;
      padding-left: 24px;
    }
    .modal-body strong {
      color: var(--text);
      font-weight: 600;
    }
    .company-info {
      background: var(--mustard-100);
      border: 1px solid #f1e4b3;
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 24px;
    }
    .company-info p {
      margin: 0;
      color: var(--text);
      font-size: 0.95rem;
    }
    /* Contact form in modal */
    .contact-form {
      display: grid;
      gap: 16px;
    }
    .contact-form label {
      font-weight: 600;
      color: var(--text);
      margin-bottom: 6px;
      display: block;
    }
    .contact-form input,
    .contact-form textarea {
      width: 100%;
      padding: 12px;
      border: 1px solid var(--card-border);
      border-radius: 8px;
      font-size: 1rem;
      font-family: inherit;
      transition: border-color 0.2s ease;
    }
    .contact-form input:focus,
    .contact-form textarea:focus {
      outline: none;
      border-color: var(--blue-600);
    }
    .contact-form button {
      background: var(--blue-600);
      color: #fff;
      padding: 14px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 1rem;
      transition: background 0.2s ease;
    }
    .contact-form button:hover {
      background: var(--blue-700);
    }
    .contact-info {
      text-align: center;
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid var(--card-border);
      font-size: 0.9rem;
      color: var(--muted);
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    /* Feature list styles for modals */
    .feature-list {
      display: grid;
      gap: 20px;
      margin-top: 20px;
    }
    .feature-item {
      display: flex;
      gap: 16px;
      align-items: flex-start;
    }
    .feature-icon {
      width: 48px;
      height: 48px;
      min-width: 48px;
      background: var(--mustard-100);
      border: 1px solid #f1e4b3;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .feature-icon svg {
      width: 24px;
      height: 24px;
      stroke: var(--mustard-700);
    }
    .feature-item strong {
      display: block;
      color: var(--text);
      margin-bottom: 6px;
      font-size: 1.05rem;
    }
    .feature-item p {
      margin: 0;
      color: var(--muted);
      line-height: 1.6;
    }
    /* Screenshot preview section */
    .preview-section {
      padding: 72px 0;
      background: var(--bg);
    }
    .preview-slider {
      position: relative;
      max-width: 1000px;
      margin: 48px auto 0;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(17,24,39,.15);
    }
    .preview-slide {
      display: none;
      animation: fadeIn 0.5s ease;
    }
    .preview-slide.active {
      display: block;
    }
    .preview-slide img {
      width: 100%;
      height: auto;
      display: block;
      border-radius: 16px;
    }
    .preview-dots {
      display: flex;
      justify-content: center;
      gap: 12px;
      margin-top: 28px;
    }
    .preview-dot {
      width: 48px;
      height: 8px;
      border-radius: 4px;
      background: var(--card-border);
      cursor: pointer;
      transition: all 0.3s ease;
      border: none;
      padding: 0;
    }
    .preview-dot.active {
      background: var(--mustard-600);
      width: 64px;
    }
    .preview-dot:hover {
      background: var(--mustard-500);
    }
    .preview-caption {
      text-align: center;
      margin-top: 20px;
      color: var(--muted);
      font-size: 0.95rem;
    }
    .preview-caption strong {
      color: var(--text);
      display: block;
      margin-bottom: 4px;
      font-size: 1.05rem;
    }
    /* Arrow navigation */
    .preview-arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(255,255,255,0.9);
      border: none;
      width: 48px;
      height: 48px;
      border-radius: 50%;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
      z-index: 10;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .preview-arrow:hover {
      background: #fff;
      transform: translateY(-50%) scale(1.1);
    }
    .preview-arrow svg {
      width: 20px;
      height: 20px;
      stroke: var(--mustard-700);
      stroke-width: 3;
    }
    .preview-arrow.prev {
      left: 20px;
    }
    .preview-arrow.next {
      right: 20px;
    }
    @media (max-width: 768px) {
      .preview-arrow {
        width: 40px;
        height: 40px;
      }
      .preview-arrow.prev {
        left: 10px;
      }
      .preview-arrow.next {
        right: 10px;
      }
    }
  </style>
</head>
<body>

  <!-- HERO -->
  <header class="hero">
    <div class="container">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true"></div>
        <strong>Mercado Afiliado</strong>
      </div>

      <h1>O afiliado inteligente rastreia cada clique</h1>
      <p class="sub">Saiba exatamente qual link de afiliado converte mais, organize suas vendas no piloto automático e alimente Facebook, Google e TikTok com dados reais — menos custo, mais resultado..</p>

      <div class="cta-row">
        <a class="btn btn-primary" href="/register" onclick="window.location.href='/register'; return false;">Comece agora</a>
        <a class="btn btn-ghost" href="/login" onclick="window.location.href='/login'; return false;">Login</a>
      </div>

      <div class="pill-row" aria-label="Provas de valor">
        <div class="pill">Sem cartão no teste</div>
        <div class="pill">LGPD-ready</div>
        <div class="pill">CAPI/EC/Events API</div>
      </div>
    </div>
  </header>

  <!-- FEATURES -->
  <section class="features container">
    <h2 class="section-title">Ferramentas que o afiliado sente no bolso</h2>
    <div class="grid">
      <!-- Link Maestro -->
      <article class="card" onclick="openModal('linkMaestroModal')" style="cursor: pointer;">
        <div class="icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10 13a5 5 0 0 1 0-7l1-1a5 5 0 0 1 7 7l-1 1"></path>
            <path d="M14 11a5 5 0 0 1 0 7l-1 1a5 5 0 0 1-7-7l1-1"></path>
          </svg>
        </div>
        <h3>Link Maestro</h3>
        <p>Padronize UTMs, encurte links e rastreie cliques com consistência. Relatórios por campanha, anúncio e criativo.</p>
        <p style="margin-top: 12px; color: var(--blue-600); font-weight: 600; font-size: 0.9rem;">Clique para saber mais →</p>
      </article>

      <!-- Pixel BR -->
      <article class="card" onclick="openModal('pixelBRModal')" style="cursor: pointer;">
        <div class="icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3l7 4v5c0 5-3.5 9-7 9s-7-4-7-9V7l7-4z"></path>
            <path d="M9 12l2 2 4-4"></path>
          </svg>
        </div>
        <h3>Pixel BR (server-side)</h3>
        <p>Coleta no seu domínio e envia via CAPI/Enhanced Conversions/Events API. Menos bloqueio, mais conversões confiáveis.</p>
        <p style="margin-top: 12px; color: var(--blue-600); font-weight: 600; font-size: 0.9rem;">Clique para saber mais →</p>
      </article>

      <!-- IntegraSync -->
      <article class="card" onclick="openModal('integraSyncModal')" style="cursor: pointer;">
        <div class="icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 7v6a3 3 0 1 0 6 0V7"></path>
            <path d="M12 3v4"></path>
            <path d="M7 12H3"></path>
            <path d="M21 12h-4"></path>
            <path d="M18.5 18.5l-2 2"></path>
            <path d="M5.5 18.5l2 2"></path>
          </svg>
        </div>
        <h3>IntegraSync</h3>
        <p>Hotmart, Monetizze e outras plataformas em um só painel, com alertas de queda e reconciliação simples.</p>
        <p style="margin-top: 12px; color: var(--blue-600); font-weight: 600; font-size: 0.9rem;">Clique para saber mais →</p>
      </article>
    </div>

    <div class="strip">
      <strong>Planos a partir de R$ 79/mês</strong> — teste grátis, cancele com 1 clique. Suporte humano no WhatsApp.
    </div>
  </section>

  <!-- HOW IT WORKS -->
  <section class="how-it-works">
    <div class="container">
      <h2 class="section-title">Como funciona? Simples em 3 passos</h2>
      <div class="steps-grid">
        <!-- Passo 1 -->
        <div class="step">
          <div class="step-number">1</div>
          <div class="step-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
          </div>
          <h3>Crie sua conta grátis</h3>
          <p>Cadastro em menos de 2 minutos. Não precisa cartão de crédito para começar a testar.</p>
        </div>

        <!-- Passo 2 -->
        <div class="step">
          <div class="step-number">2</div>
          <div class="step-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
              <polyline points="7.5 4.21 12 6.81 16.5 4.21"></polyline>
              <polyline points="7.5 19.79 7.5 14.6 3 12"></polyline>
              <polyline points="21 12 16.5 14.6 16.5 19.79"></polyline>
              <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
              <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
          </div>
          <h3>Conecte suas plataformas</h3>
          <p>Integre Hotmart, Monetizze e outras redes em poucos cliques. Configure o Pixel BR no seu domínio.</p>
        </div>

        <!-- Passo 3 -->
        <div class="step">
          <div class="step-number">3</div>
          <div class="step-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
            </svg>
          </div>
          <h3>Veja os resultados</h3>
          <p>Acompanhe métricas em tempo real, otimize campanhas e reduza custo por lead com dados confiáveis.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- PREVIEW SECTION -->
  <section class="preview-section">
    <div class="container">
      <h2 class="section-title">Veja o Mercado Afiliado em Ação</h2>
      <p style="text-align: center; color: var(--muted); max-width: 700px; margin: 0 auto 0;">
        Interface intuitiva e poderosa para gerenciar suas campanhas, integrações e métricas em tempo real
      </p>

      <div class="preview-slider">
        <!-- Arrows -->
        <button class="preview-arrow prev" onclick="changeSlide(-1)" aria-label="Anterior">
          <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
        </button>
        <button class="preview-arrow next" onclick="changeSlide(1)" aria-label="Próximo">
          <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </button>

        <!-- Slides -->
        <div class="preview-slide active" data-caption="Dashboard Principal" data-description="Visão completa das suas métricas e performance">
          <img src="<?= BASE_URL ?>/public/assets/images/dashboard-preview.png" alt="Dashboard Principal do Mercado Afiliado">
        </div>
        <div class="preview-slide" data-caption="Painel Unificado" data-description="Análise detalhada com gráficos e comparativos">
          <img src="<?= BASE_URL ?>/public/assets/images/unified_panel-preview.png" alt="Painel Unificado do Mercado Afiliado">
        </div>
        <div class="preview-slide" data-caption="IntegraSync" data-description="Gerencie todas as suas integrações em um só lugar">
          <img src="<?= BASE_URL ?>/public/assets/images/integrasync-preview.png" alt="IntegraSync - Integrações">
        </div>
      </div>

      <!-- Dots Navigation -->
      <div class="preview-dots">
        <button class="preview-dot active" onclick="goToSlide(0)" aria-label="Slide 1"></button>
        <button class="preview-dot" onclick="goToSlide(1)" aria-label="Slide 2"></button>
        <button class="preview-dot" onclick="goToSlide(2)" aria-label="Slide 3"></button>
      </div>

      <!-- Caption -->
      <div class="preview-caption">
        <strong id="preview-caption-title">Dashboard Principal</strong>
        <span id="preview-caption-description">Visão completa das suas métricas e performance</span>
      </div>
    </div>
  </section>

  <!-- FAQ SECTION -->
  <section class="faq-section">
    <div class="container">
      <h2 class="section-title">Perguntas Frequentes</h2>
      <div class="faq-container">
        <!-- FAQ 1 -->
        <div class="faq-item">
          <button class="faq-question" onclick="toggleFaq(this)">
            <span>Como funciona o período de teste gratuito?</span>
            <div class="faq-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </div>
          </button>
          <div class="faq-answer">
            <p>Você tem <strong>14 dias gratuitos</strong> para testar todas as funcionalidades da plataforma, sem precisar informar cartão de crédito. Durante o teste, você pode conectar suas integrações, configurar o Pixel BR e explorar todos os recursos. Se gostar, basta escolher um plano. Se não, nada é cobrado.</p>
          </div>
        </div>

        <!-- FAQ 2 -->
        <div class="faq-item">
          <button class="faq-question" onclick="toggleFaq(this)">
            <span>Preciso ter conhecimento técnico para usar?</span>
            <div class="faq-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </div>
          </button>
          <div class="faq-answer">
            <p><strong>Não é necessário conhecimento técnico.</strong> Nossa plataforma foi criada para afiliados, não para programadores. As integrações são feitas com poucos cliques, e fornecemos guias passo a passo para cada configuração. Se precisar de ajuda, nosso suporte responde via WhatsApp.</p>
          </div>
        </div>

        <!-- FAQ 3 -->
        <div class="faq-item">
          <button class="faq-question" onclick="toggleFaq(this)">
            <span>Quais plataformas de afiliados são suportadas?</span>
            <div class="faq-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </div>
          </button>
          <div class="faq-answer">
            <p>Atualmente integramos com <strong>Hotmart, Monetizze, Eduzz, Braip</strong> e outras plataformas via webhook personalizado. O Pixel BR funciona com <strong>Meta Ads (Facebook/Instagram), Google Ads e TikTok Ads</strong> através de CAPI, Enhanced Conversions e Events API.</p>
          </div>
        </div>

        <!-- FAQ 4 -->
        <div class="faq-item">
          <button class="faq-question" onclick="toggleFaq(this)">
            <span>Os dados dos meus clientes estão seguros? É LGPD compliant?</span>
            <div class="faq-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </div>
          </button>
          <div class="faq-answer">
            <p><strong>Sim, somos 100% LGPD compliant.</strong> Todos os dados são criptografados, armazenados em servidores brasileiros e processados conforme a Lei Geral de Proteção de Dados. O Pixel BR coleta dados no seu próprio domínio, reduzindo riscos de bloqueio e garantindo conformidade com as políticas de privacidade.</p>
          </div>
        </div>

        <!-- FAQ 5 -->
        <div class="faq-item">
          <button class="faq-question" onclick="toggleFaq(this)">
            <span>Como funciona o cancelamento?</span>
            <div class="faq-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </div>
          </button>
          <div class="faq-answer">
            <p>O cancelamento é <strong>simples e feito com 1 clique</strong> direto no painel. Não há multa, fidelidade ou burocracia. Você pode cancelar a qualquer momento e manter acesso até o fim do período pago. Seus dados ficam disponíveis por 30 dias após o cancelamento para eventual exportação.</p>
          </div>
        </div>

        <!-- FAQ 6 -->
        <div class="faq-item">
          <button class="faq-question" onclick="toggleFaq(this)">
            <span>Qual é o investimento mensal?</span>
            <div class="faq-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </div>
          </button>
          <div class="faq-answer">
            <p>Os planos começam em <strong>R$ 79/mês</strong> e variam conforme o volume de eventos processados e número de integrações. Durante o teste gratuito, você pode avaliar qual plano atende melhor suas necessidades. Também oferecemos desconto para pagamento anual.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA SECTION -->
  <section class="cta-section">
    <div class="container">
      <h2>Pronto para escalar seus resultados?</h2>
      <p>Junte-se aos afiliados que já estão otimizando suas campanhas com dados precisos</p>
      <a class="btn btn-primary" href="/register" onclick="window.location.href='/register'; return false;">
        Comece grátis agora →
      </a>
    </div>
  </section>

  <!-- FOOTER -->
  <footer>
    <div class="container foot">
      <small>© 2025 Mercado Afiliado</small>
      <nav class="links" aria-label="Links de rodapé">
        <a href="#" onclick="openModal('privacidadeModal'); return false;">Privacidade</a>
        <a href="#" onclick="openModal('termosModal'); return false;">Termos</a>
        <a href="#" onclick="openModal('contatoModal'); return false;">Contato</a>
      </nav>
    </div>
  </footer>

  <!-- Modal Privacidade -->
  <div id="privacidadeModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Política de Privacidade</h2>
        <button class="modal-close" onclick="closeModal('privacidadeModal')">×</button>
      </div>
      <div class="modal-body">
        <div class="company-info">
          <p><strong>Mercado Afiliado Serviços Online Ltda</strong><br>
          QE 40, lote 8, sala 402, Brasília/DF<br>
          Telefone: (61) 99916-3260 — E-mail: contato@mercadoafiliado.com.br</p>
        </div>

        <h3>1. Coleta de Informações</h3>
        <p>Coletamos dados fornecidos pelo usuário no cadastro, bem como informações de uso, integrações e métricas de campanhas.</p>

        <h3>2. Uso das Informações</h3>
        <p>Os dados são utilizados para geração de relatórios, personalização da experiência, operação das integrações e cumprimento de obrigações legais.</p>

        <h3>3. Compartilhamento</h3>
        <p>Não compartilhamos dados pessoais com terceiros, salvo em casos de obrigação legal ou serviços técnicos essenciais para funcionamento da plataforma.</p>

        <h3>4. Segurança</h3>
        <p>Adotamos medidas de segurança técnicas e administrativas para proteger os dados contra acessos não autorizados ou uso indevido.</p>

        <h3>5. Direitos do Usuário</h3>
        <p>Em conformidade com a <strong>LGPD</strong>, o usuário pode solicitar acesso, correção ou exclusão de dados, além de revogar consentimentos. Para isso, basta enviar solicitação para <strong>contato@mercadoafiliado.com.br</strong>.</p>

        <h3>6. Alterações</h3>
        <p>Esta Política poderá ser atualizada periodicamente. A versão mais recente estará sempre disponível em nosso site.</p>
      </div>
    </div>
  </div>

  <!-- Modal Termos -->
  <div id="termosModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Termos de Uso</h2>
        <button class="modal-close" onclick="closeModal('termosModal')">×</button>
      </div>
      <div class="modal-body">
        <div class="company-info">
          <p><strong>Mercado Afiliado Serviços Online Ltda</strong><br>
          QE 40, lote 8, sala 402, Brasília/DF<br>
          Telefone: (61) 99916-3260 — E-mail: contato@mercadoafiliado.com.br</p>
        </div>

        <h3>1. Aceitação dos Termos</h3>
        <p>Ao utilizar a plataforma Mercado Afiliado, o usuário concorda integralmente com os presentes Termos de Uso e com todas as normas aplicáveis.</p>

        <h3>2. Descrição do Serviço</h3>
        <p>O Mercado Afiliado oferece soluções digitais voltadas para afiliados e infoprodutores, incluindo integrações com plataformas de afiliação, geração de UTMs, monitoramento de campanhas e rastreamento via <strong>Pixel BR</strong>.</p>

        <h3>3. Responsabilidades do Usuário</h3>
        <ul>
          <li>Fornecer dados corretos e atualizados;</li>
          <li>Manter a confidencialidade de login e senha;</li>
          <li>Usar o serviço em conformidade com a legislação vigente e a LGPD;</li>
          <li>Não realizar práticas que comprometam a segurança ou integridade da plataforma.</li>
        </ul>

        <h3>4. Pagamentos e Cancelamento</h3>
        <p>O serviço é disponibilizado mediante assinatura. O usuário poderá <strong>cancelar a qualquer momento</strong>, respeitando o ciclo de cobrança vigente. O não pagamento implicará suspensão de acesso.</p>

        <h3>5. Limitação de Responsabilidade</h3>
        <p>O Mercado Afiliado não se responsabiliza por dados incorretos fornecidos pelo usuário ou por falhas externas (APIs de terceiros, integrações).</p>

        <h3>6. Alterações nos Termos</h3>
        <p>Reservamo-nos o direito de modificar estes Termos. O uso continuado da plataforma implica aceitação das mudanças.</p>

        <h3>7. Legislação e Foro</h3>
        <p>Estes Termos são regidos pelas leis brasileiras. Qualquer litígio será resolvido no foro de <strong>Brasília/DF</strong>.</p>
      </div>
    </div>
  </div>

  <!-- Modal Contato -->
  <div id="contatoModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Fale Conosco</h2>
        <button class="modal-close" onclick="closeModal('contatoModal')">×</button>
      </div>
      <div class="modal-body">
        <form class="contact-form" id="form-contato-modal" onsubmit="enviarContato(event)">
          <div>
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" required>
          </div>
          <div>
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" required>
          </div>
          <div>
            <label for="mensagem">Mensagem:</label>
            <textarea id="mensagem" name="mensagem" rows="5" required></textarea>
          </div>
          <button type="submit">Enviar Mensagem</button>
        </form>

        <div class="contact-info">
          <p><strong>Mercado Afiliado Serviços Online Ltda</strong><br>
          QE 40, lote 8, sala 402, Brasília/DF<br>
          Telefone: <strong>(61) 99916-3260</strong></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Link Maestro -->
  <div id="linkMaestroModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 28px; height: 28px; display: inline-block; vertical-align: middle; margin-right: 8px; stroke: var(--mustard-700);">
            <path d="M10 13a5 5 0 0 1 0-7l1-1a5 5 0 0 1 7 7l-1 1"></path>
            <path d="M14 11a5 5 0 0 1 0 7l-1 1a5 5 0 0 1-7-7l1-1"></path>
          </svg>
          Link Maestro
        </h2>
        <button class="modal-close" onclick="closeModal('linkMaestroModal')">×</button>
      </div>
      <div class="modal-body">
        <p style="font-size: 1.1rem; color: var(--text); margin-bottom: 24px;">
          <strong>Transforme links de afiliado bagunçados em links rastreáveis e profissionais.</strong> O Link Maestro é a ferramenta que todo afiliado precisa para nunca mais perder o controle de onde vêm seus cliques e vendas.
        </p>

        <h3>
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 6px; stroke: var(--mustard-700);">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
          O que o Link Maestro faz?
        </h3>

        <div class="feature-list">
          <div class="feature-item">
            <div class="feature-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
              </svg>
            </div>
            <div>
              <strong>Cria UTMs automaticamente</strong>
              <p>Você não precisa criar manualmente aquelas tags complicadas (utm_source, utm_campaign...). O Link Maestro gera tudo automaticamente seguindo um padrão que você define.</p>
            </div>
          </div>

          <div class="feature-item">
            <div class="feature-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="9" y1="9" x2="15" y2="15"></line>
                <line x1="15" y1="9" x2="9" y2="15"></line>
              </svg>
            </div>
            <div>
              <strong>Encurta seus links</strong>
              <p>Links gigantes de afiliado ficam feios e assustam clientes. O Link Maestro encurta automaticamente, deixando tudo mais profissional e confiável.</p>
            </div>
          </div>

          <div class="feature-item">
            <div class="feature-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </div>
            <div>
              <strong>Rastreia cada clique</strong>
              <p>Toda vez que alguém clica no seu link, o sistema registra: de onde veio, que hora clicou, qual anúncio gerou aquele clique. Você vê tudo em tempo real.</p>
            </div>
          </div>

          <div class="feature-item">
            <div class="feature-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
              </svg>
            </div>
            <div>
              <strong>Mostra o que dá lucro</strong>
              <p>Relatórios simples mostram qual campanha, anúncio ou criativo está trazendo mais cliques e vendas. Você investe mais no que funciona e corta o que não dá resultado.</p>
            </div>
          </div>
        </div>

        <h3 style="margin-top: 32px;">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 6px; stroke: var(--mustard-700);">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
          </svg>
          Exemplo prático
        </h3>
        <div style="background: var(--mustard-100); border: 1px solid #f1e4b3; border-radius: 12px; padding: 20px; margin-top: 16px;">
          <p style="margin: 0 0 12px; color: var(--text);">
            <strong>Antes (sem Link Maestro):</strong><br>
            <code style="font-size: 0.85rem; background: #fff; padding: 4px 8px; border-radius: 4px; display: inline-block; margin-top: 6px;">https://go.hotmart.com/produto123?src=afiliado&ref=abc123xyzqwerty</code>
          </p>
          <p style="margin: 16px 0 0; color: var(--text);">
            <strong>Depois (com Link Maestro):</strong><br>
            <code style="font-size: 0.85rem; background: #fff; padding: 4px 8px; border-radius: 4px; display: inline-block; margin-top: 6px;">https://seu-link.com/oferta</code>
            <br>
            <span style="font-size: 0.9rem; color: var(--muted); margin-top: 8px; display: block;">✅ Rastreado, organizado e profissional!</span>
          </p>
        </div>

        <div style="text-align: center; margin-top: 28px;">
          <a class="btn btn-primary" href="/register" onclick="closeModal('linkMaestroModal'); window.location.href='/register'; return false;">
            Quero testar grátis →
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Pixel BR -->
  <div id="pixelBRModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 28px; height: 28px; display: inline-block; vertical-align: middle; margin-right: 8px; stroke: var(--mustard-700);">
            <path d="M12 3l7 4v5c0 5-3.5 9-7 9s-7-4-7-9V7l7-4z"></path>
            <path d="M9 12l2 2 4-4"></path>
          </svg>
          Pixel BR (server-side)
        </h2>
        <button class="modal-close" onclick="closeModal('pixelBRModal')">×</button>
      </div>
      <div class="modal-body">
        <p style="font-size: 1.1rem; color: var(--text); margin-bottom: 24px;">
          <strong>Pare de perder dinheiro por causa de bloqueadores e dados incompletos.</strong> O Pixel BR coleta informações de vendas no seu próprio domínio brasileiro e envia tudo direto para Facebook, Google e TikTok de forma confiável.
        </p>

        <h3>
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 6px; stroke: var(--mustard-700);">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          Por que você precisa disso?
        </h3>

        <div style="background: #fff4d6; border-left: 4px solid var(--mustard-600); padding: 16px; margin: 16px 0; border-radius: 8px;">
          <p style="margin: 0; color: var(--text);">
            <strong>O problema:</strong> Bloqueadores de anúncios, iOS 14+ e navegadores modernos bloqueiam os pixels tradicionais. O Facebook, Google e TikTok não recebem dados corretos sobre quem está comprando. Resultado? Seus anúncios ficam caros e você perde vendas.
          </p>
        </div>

        <div class="feature-list">
          <div class="feature-item">
            <div class="feature-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                <line x1="8" y1="21" x2="16" y2="21"></line>
                <line x1="12" y1="17" x2="12" y2="21"></line>
              </svg>
            </div>
            <div>
              <strong>Coleta de dados no SEU domínio</strong>
              <p>O Pixel BR funciona direto no seu site .com.br, sem depender de scripts de terceiros que são facilmente bloqueados. Seus dados são coletados antes de qualquer bloqueador agir.</p>
            </div>
          </div>

          <div class="feature-item">
            <div class="feature-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="16 18 22 12 16 6"></polyline>
                <polyline points="8 6 2 12 8 18"></polyline>
              </svg>
            </div>
            <div>
              <strong>Envia via CAPI, Enhanced Conversions e Events API</strong>
              <p>Os dados vão direto do servidor para o Facebook (CAPI), Google (Enhanced Conversions) e TikTok (Events API). Muito mais confiável que pixels tradicionais.</p>
            </div>
          </div>

          <div class="feature-item">
            <div class="feature-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 20V10"></path>
                <path d="M12 20V4"></path>
                <path d="M6 20v-6"></path>
              </svg>
            </div>
            <div>
              <strong>Menos custo por lead, mais vendas</strong>
              <p>Com dados precisos, o Facebook, Google e TikTok aprendem quem é seu público de verdade. Eles mostram seus anúncios para pessoas certas, você paga menos e vende mais.</p>
            </div>
          </div>

          <div class="feature-item">
            <div class="feature-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
              </svg>
            </div>
            <div>
              <strong>100% LGPD compliant</strong>
              <p>Todos os dados são tratados conforme a legislação brasileira. Você fica tranquilo e seus clientes protegidos.</p>
            </div>
          </div>
        </div>

        <h3 style="margin-top: 32px;">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 6px; stroke: var(--mustard-700);">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          Como funciona na prática?
        </h3>
        <ol style="color: var(--muted); line-height: 1.8; margin: 16px 0; padding-left: 24px;">
          <li>Você adiciona um código simples no seu site de captura ou checkout</li>
          <li>Quando alguém compra, o Pixel BR captura: e-mail, telefone, valor da compra, produto</li>
          <li>Esses dados vão direto do nosso servidor para Facebook, Google e TikTok</li>
          <li>As plataformas aprendem quem compra de você e otimizam seus anúncios automaticamente</li>
        </ol>

        <div style="background: var(--mustard-100); border: 1px solid #f1e4b3; border-radius: 12px; padding: 20px; margin-top: 20px;">
          <p style="margin: 0; color: var(--text); text-align: center;">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 24px; height: 24px; display: inline-block; vertical-align: middle; margin-right: 8px; stroke: var(--green-600);">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <strong>Resultado:</strong> Menos custo por conversão, públicos mais qualificados e anúncios que realmente escalam.
          </p>
        </div>

        <div style="text-align: center; margin-top: 28px;">
          <a class="btn btn-primary" href="/register" onclick="closeModal('pixelBRModal'); window.location.href='/register'; return false;">
            Quero testar grátis →
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal IntegraSync -->
  <div id="integraSyncModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 28px; height: 28px; display: inline-block; vertical-align: middle; margin-right: 8px; stroke: var(--mustard-700);">
            <path d="M9 7v6a3 3 0 1 0 6 0V7"></path>
            <path d="M12 3v4"></path>
            <path d="M7 12H3"></path>
            <path d="M21 12h-4"></path>
            <path d="M18.5 18.5l-2 2"></path>
            <path d="M5.5 18.5l2 2"></path>
          </svg>
          IntegraSync
        </h2>
        <button class="modal-close" onclick="closeModal('integraSyncModal')">×</button>
      </div>
      <div class="modal-body">
        <p style="font-size: 1.1rem; color: var(--text); margin-bottom: 24px;">
          <strong>Chega de ficar pulando entre Hotmart, Monetizze, Eduzz e outros painéis.</strong> O IntegraSync unifica todas as suas vendas, comissões e métricas em um único painel inteligente.
        </p>

        <h3>
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 6px; stroke: var(--mustard-700);">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          Qual o problema que resolvemos?
        </h3>

        <div style="background: #fff4d6; border-left: 4px solid var(--mustard-600); padding: 16px; margin: 16px 0; border-radius: 8px;">
          <p style="margin: 0; color: var(--text);">
            <strong>Você promove vários produtos em diferentes plataformas?</strong> Fica difícil saber quanto vendeu no total, qual produto está performando melhor e se os webhooks estão funcionando. O IntegraSync resolve isso.
          </p>
        </div>

        <div class="feature-list">
          <div class="feature-item">
            <div class="feature-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
              </svg>
            </div>
            <div>
              <strong>Todas as plataformas em um lugar</strong>
              <p>Hotmart, Monetizze, Eduzz, Braip e outras redes de afiliados conectadas automaticamente. Você vê tudo de uma vez só: vendas, comissões, produtos.</p>
            </div>
          </div>

          <div class="feature-item">
            <div class="feature-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
              </svg>
            </div>
            <div>
              <strong>Alertas de queda automáticos</strong>
              <p>Se alguma integração parar de funcionar ou as vendas caírem muito, você recebe um alerta no WhatsApp. Não perde mais vendas por webhook fora do ar.</p>
            </div>
          </div>

          <div class="feature-item">
            <div class="feature-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
              </svg>
            </div>
            <div>
              <strong>Relatórios unificados</strong>
              <p>Compare produtos, veja qual plataforma converte mais, acompanhe suas comissões em tempo real. Decisões baseadas em dados reais, não em "achismos".</p>
            </div>
          </div>

          <div class="feature-item">
            <div class="feature-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
              </svg>
            </div>
            <div>
              <strong>Reconciliação simples</strong>
              <p>Cruzamos os dados das plataformas com os cliques dos seus links. Você descobre se está perdendo comissões ou se alguma venda não foi rastreada.</p>
            </div>
          </div>
        </div>

        <h3 style="margin-top: 32px;">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 6px; stroke: var(--mustard-700);">
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
          </svg>
          Plataformas suportadas
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-top: 16px;">
          <div style="background: #fff; border: 1px solid var(--card-border); border-radius: 8px; padding: 12px; text-align: center; color: var(--text); font-weight: 600;">
            ✅ Hotmart
          </div>
          <div style="background: #fff; border: 1px solid var(--card-border); border-radius: 8px; padding: 12px; text-align: center; color: var(--text); font-weight: 600;">
            ✅ Monetizze
          </div>
          <div style="background: #fff; border: 1px solid var(--card-border); border-radius: 8px; padding: 12px; text-align: center; color: var(--text); font-weight: 600;">
            ✅ Eduzz
          </div>
          <div style="background: #fff; border: 1px solid var(--card-border); border-radius: 8px; padding: 12px; text-align: center; color: var(--text); font-weight: 600;">
            ✅ Braip
          </div>
          <div style="background: #fff; border: 1px solid var(--card-border); border-radius: 8px; padding: 12px; text-align: center; color: var(--text); font-weight: 600;">
            ✅ Webhooks customizados
          </div>
        </div>

        <div style="background: var(--mustard-100); border: 1px solid #f1e4b3; border-radius: 12px; padding: 20px; margin-top: 24px;">
          <p style="margin: 0; color: var(--text); text-align: center;">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 24px; height: 24px; display: inline-block; vertical-align: middle; margin-right: 8px; stroke: var(--green-600);">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <strong>Economia de tempo:</strong> Ao invés de 30 minutos por dia conferindo painéis, você gasta 2 minutos olhando tudo em um lugar.
          </p>
        </div>

        <div style="text-align: center; margin-top: 28px;">
          <a class="btn btn-primary" href="/register" onclick="closeModal('integraSyncModal'); window.location.href='/register'; return false;">
            Quero testar grátis →
          </a>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Modal functions
    function openModal(modalId) {
      document.getElementById(modalId).classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
      document.getElementById(modalId).classList.remove('active');
      document.body.style.overflow = 'auto';
    }

    // Close modal on background click
    document.querySelectorAll('.modal').forEach(modal => {
      modal.addEventListener('click', function(e) {
        if (e.target === this) {
          closeModal(this.id);
        }
      });
    });

    // Close modal on ESC key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        document.querySelectorAll('.modal.active').forEach(modal => {
          closeModal(modal.id);
        });
      }
    });

    // Contact form submission
    function enviarContato(event) {
      event.preventDefault();

      const form = event.target;
      const nome = form.nome.value;
      const email = form.email.value;
      const mensagem = form.mensagem.value;

      // Aqui você pode adicionar o código para enviar via AJAX/fetch se desejar
      // Por enquanto, apenas mostra o alert e redireciona

      alert('✅ Mensagem enviada com sucesso!\n\nObrigado pelo contato, ' + nome + '.\nRetornaremos em breve.');

      // Limpa o formulário
      form.reset();

      // Fecha o modal
      closeModal('contatoModal');

      // Opcional: redireciona para home (pode remover se não quiser redirecionar)
      // window.location.href = '/';
    }

    // Preview slider
    let currentSlide = 0;
    const slides = document.querySelectorAll('.preview-slide');
    const dots = document.querySelectorAll('.preview-dot');

    function showSlide(index) {
      // Remove active class from all
      slides.forEach(slide => slide.classList.remove('active'));
      dots.forEach(dot => dot.classList.remove('active'));

      // Add active class to current
      slides[index].classList.add('active');
      dots[index].classList.add('active');

      // Update caption
      const caption = slides[index].dataset.caption;
      const description = slides[index].dataset.description;
      document.getElementById('preview-caption-title').textContent = caption;
      document.getElementById('preview-caption-description').textContent = description;
    }

    function changeSlide(direction) {
      currentSlide += direction;
      if (currentSlide < 0) currentSlide = slides.length - 1;
      if (currentSlide >= slides.length) currentSlide = 0;
      showSlide(currentSlide);
    }

    function goToSlide(index) {
      currentSlide = index;
      showSlide(currentSlide);
    }

    // Auto-play slider (optional - uncomment to enable)
    // setInterval(() => changeSlide(1), 5000);

    // FAQ toggle function

    function toggleFaq(button) {
      const faqItem = button.parentElement;
      const allItems = document.querySelectorAll('.faq-item');

      // Se o item clicado já está ativo, fecha ele
      if (faqItem.classList.contains('active')) {
        faqItem.classList.remove('active');
      } else {
        // Fecha todos os outros
        allItems.forEach(item => item.classList.remove('active'));
        // Abre o clicado
        faqItem.classList.add('active');
      }
    }
  </script>

</body>
</html>