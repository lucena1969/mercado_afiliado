<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
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

      <h1>Performance que aprende com seus dados</h1>
      <p class="sub">UTMs inteligentes, integrações automáticas e <strong>Pixel BR server-side</strong> para alimentar Meta/Google/TikTok com eventos confiáveis — menos custo por lead, mais escala.</p>

      <div class="cta-row">
        <a class="btn btn-primary" href="/register" onclick="window.location.href='/register'; return false;">Comece agora</a>
        <a class="btn btn-ghost" href="/login" onclick="window.location.href='/login'; return false;">Ver demo</a>
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
      <article class="card">
        <div class="icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10 13a5 5 0 0 1 0-7l1-1a5 5 0 0 1 7 7l-1 1"></path>
            <path d="M14 11a5 5 0 0 1 0 7l-1 1a5 5 0 0 1-7-7l1-1"></path>
          </svg>
        </div>
        <h3>Link Maestro</h3>
        <p>Padronize UTMs, encurte links e rastreie cliques com consistência. Relatórios por campanha, anúncio e criativo.</p>
      </article>

      <!-- Pixel BR -->
      <article class="card">
        <div class="icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3l7 4v5c0 5-3.5 9-7 9s-7-4-7-9V7l7-4z"></path>
            <path d="M9 12l2 2 4-4"></path>
          </svg>
        </div>
        <h3>Pixel BR (server-side)</h3>
        <p>Coleta no seu domínio e envia via CAPI/Enhanced Conversions/Events API. Menos bloqueio, mais conversões confiáveis.</p>
      </article>

      <!-- IntegraSync -->
      <article class="card">
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
      </article>
    </div>

    <div class="strip">
      <strong>Planos a partir de R$ 79/mês</strong> — teste grátis, cancele com 1 clique. Suporte humano no WhatsApp.
    </div>
  </section>

  <!-- FOOTER -->
  <footer>
    <div class="container foot">
      <small>© 2025 Mercado Afiliado</small>
      <nav class="links" aria-label="Links de rodapé">
        <a href="privacidade.html">Privacidade</a>
        <a href="termos.html">Termos</a>
        <a href="contato.html">Contato</a>
      </nav>
    </div>
  </footer>

</body>
</html>