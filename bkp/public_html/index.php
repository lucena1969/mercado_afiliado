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

    /* Modal styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.6);
      backdrop-filter: blur(4px);
    }
    .modal.show {
      display: flex;
      align-items: center;
      justify-content: center;
      animation: fadeIn 0.3s ease;
    }
    .modal-content {
      background: var(--bg);
      margin: 20px;
      padding: 0;
      border-radius: var(--radius);
      width: 90%;
      max-width: 600px;
      max-height: 80vh;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      animation: slideIn 0.3s ease;
      overflow: hidden;
    }
    .modal-header {
      padding: 24px 24px 16px;
      border-bottom: 1px solid var(--card-border);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .modal-title {
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--text);
      margin: 0;
    }
    .close {
      background: none;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: var(--muted);
      padding: 4px;
      border-radius: 6px;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
    }
    .close:hover {
      color: var(--text);
      background: rgba(107, 114, 128, 0.1);
    }
    .modal-body {
      padding: 24px;
      overflow-y: auto;
      max-height: calc(80vh - 100px);
    }
    .modal-body h3 {
      color: var(--text);
      font-weight: 600;
      margin: 24px 0 12px 0;
      font-size: 1.1rem;
    }
    .modal-body h3:first-child {
      margin-top: 0;
    }
    .modal-body p {
      color: var(--muted);
      line-height: 1.6;
      margin: 0 0 16px 0;
    }
    .modal-body ul {
      color: var(--muted);
      line-height: 1.6;
      margin: 0 0 16px 20px;
    }
    .modal-body a {
      color: var(--blue-600);
      text-decoration: underline;
    }
    .modal-body a:hover {
      color: var(--blue-700);
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    @keyframes slideIn {
      from { transform: translateY(-20px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    /* Contact form styles */
    .contact-form {
      margin-top: 20px;
    }
    .form-group {
      margin-bottom: 16px;
    }
    .form-group label {
      display: block;
      margin-bottom: 6px;
      font-weight: 600;
      color: var(--text);
    }
    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 12px;
      border: 1px solid var(--card-border);
      border-radius: 8px;
      font-family: inherit;
      font-size: 14px;
      color: var(--text);
      background: #fff;
      transition: border-color 0.2s ease;
    }
    .form-group input:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: var(--blue-600);
    }
    .form-group textarea {
      resize: vertical;
      min-height: 100px;
    }
    .btn-submit {
      background: var(--blue-600);
      color: #fff;
      border: none;
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s ease;
    }
    .btn-submit:hover {
      background: var(--blue-700);
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
        <a href="#" onclick="openModal('privacyModal'); return false;">Privacidade</a>
        <a href="#" onclick="openModal('termsModal'); return false;">Termos</a>
        <a href="#" onclick="openModal('contactModal'); return false;">Contato</a>
      </nav>
    </div>
  </footer>

  <!-- Modal Privacidade -->
  <div id="privacyModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Política de Privacidade</h2>
        <button class="close" onclick="closeModal('privacyModal')">&times;</button>
      </div>
      <div class="modal-body">
        <h3>1. Coleta de Informações</h3>
        <p>O Mercado Afiliado coleta informações pessoais quando você se registra, faz login ou utiliza nossos serviços. Isso inclui nome, e-mail, dados de pagamento e informações de uso da plataforma.</p>

        <h3>2. Uso das Informações</h3>
        <p>Utilizamos suas informações para:</p>
        <ul>
          <li>Fornecer e melhorar nossos serviços</li>
          <li>Processar transações e pagamentos</li>
          <li>Enviar comunicações importantes sobre sua conta</li>
          <li>Personalizar sua experiência na plataforma</li>
          <li>Cumprir obrigações legais e regulamentares</li>
        </ul>

        <h3>3. Compartilhamento de Dados</h3>
        <p>Não vendemos suas informações pessoais. Podemos compartilhar dados apenas com:</p>
        <ul>
          <li>Provedores de serviços necessários para operação da plataforma</li>
          <li>Autoridades legais quando exigido por lei</li>
          <li>Terceiros com seu consentimento explícito</li>
        </ul>

        <h3>4. Proteção de Dados</h3>
        <p>Implementamos medidas de segurança técnicas e organizacionais para proteger suas informações contra acesso não autorizado, alteração, divulgação ou destruição.</p>

        <h3>5. Seus Direitos (LGPD)</h3>
        <p>Você tem direito de:</p>
        <ul>
          <li>Acessar seus dados pessoais</li>
          <li>Corrigir dados incompletos ou incorretos</li>
          <li>Solicitar exclusão de dados</li>
          <li>Revogar consentimento</li>
          <li>Portabilidade dos dados</li>
        </ul>

        <h3>6. Cookies</h3>
        <p>Utilizamos cookies para melhorar a funcionalidade do site e analisar o uso. Você pode controlar cookies através das configurações do seu navegador.</p>

        <h3>7. Contato</h3>
        <p>Para questões sobre privacidade, entre em contato conosco através do formulário de contato ou pelo e-mail: privacidade@mercadoafiliado.com</p>

        <p><em>Última atualização: Janeiro de 2025</em></p>
      </div>
    </div>
  </div>

  <!-- Modal Termos de Uso -->
  <div id="termsModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Termos de Uso</h2>
        <button class="close" onclick="closeModal('termsModal')">&times;</button>
      </div>
      <div class="modal-body">
        <h3>1. Aceitação dos Termos</h3>
        <p>Ao utilizar o Mercado Afiliado, você concorda com estes Termos de Uso. Se não concordar, não utilize nossos serviços.</p>

        <h3>2. Descrição do Serviço</h3>
        <p>O Mercado Afiliado oferece ferramentas para afiliados digitais, incluindo:</p>
        <ul>
          <li>Link Maestro: Gerenciamento de UTMs e links</li>
          <li>Pixel BR: Tracking server-side para Meta, Google e TikTok</li>
          <li>IntegraSync: Integração com plataformas de afiliação</li>
        </ul>

        <h3>3. Cadastro e Conta</h3>
        <p>Para utilizar nossos serviços, você deve:</p>
        <ul>
          <li>Ser maior de 18 anos</li>
          <li>Fornecer informações verdadeiras e atualizadas</li>
          <li>Manter a segurança de sua senha</li>
          <li>Notificar-nos sobre uso não autorizado</li>
        </ul>

        <h3>4. Uso Adequado</h3>
        <p>Você concorda em não:</p>
        <ul>
          <li>Usar o serviço para atividades ilegais</li>
          <li>Violar direitos de terceiros</li>
          <li>Tentar hackear ou comprometer a segurança</li>
          <li>Enviar spam ou conteúdo malicioso</li>
          <li>Revender ou redistribuir nossos serviços</li>
        </ul>

        <h3>5. Pagamentos e Cancelamento</h3>
        <p>Oferecemos período de teste gratuito. Após este período, será cobrada mensalidade conforme plano escolhido. Cancelamento pode ser feito a qualquer momento com efeito no próximo ciclo de cobrança.</p>

        <h3>6. Propriedade Intelectual</h3>
        <p>Todo conteúdo da plataforma é protegido por direitos autorais. Você recebe licença limitada para uso pessoal e comercial conforme os termos.</p>

        <h3>7. Limitação de Responsabilidade</h3>
        <p>O Mercado Afiliado não se responsabiliza por danos indiretos, lucros cessantes ou perdas decorrentes do uso da plataforma.</p>

        <h3>8. Modificações</h3>
        <p>Reservamos o direito de modificar estes termos a qualquer momento. Alterações serão comunicadas com antecedência.</p>

        <h3>9. Lei Aplicável</h3>
        <p>Estes termos são regidos pela legislação brasileira. Foro da comarca de São Paulo/SP para resolução de disputas.</p>

        <p><em>Última atualização: Janeiro de 2025</em></p>
      </div>
    </div>
  </div>

  <!-- Modal Contato -->
  <div id="contactModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Entre em Contato</h2>
        <button class="close" onclick="closeModal('contactModal')">&times;</button>
      </div>
      <div class="modal-body">
        <p>Tem dúvidas, sugestões ou precisa de suporte? Entre em contato conosco!</p>
        
        <h3>Canais de Atendimento</h3>
        <p><strong>WhatsApp:</strong> +55 (11) 99999-9999<br>
        <strong>E-mail:</strong> contato@mercadoafiliado.com<br>
        <strong>Horário:</strong> Segunda a Sexta, 9h às 18h</p>

        <h3>Suporte Técnico</h3>
        <p><strong>E-mail:</strong> suporte@mercadoafiliado.com<br>
        <strong>WhatsApp:</strong> +55 (11) 88888-8888<br>
        <strong>Horário:</strong> Segunda a Sábado, 8h às 20h</p>

        <div class="contact-form">
          <h3>Envie uma Mensagem</h3>
          <form id="contactForm" onsubmit="submitContactForm(event)">
            <div class="form-group">
              <label for="name">Nome *</label>
              <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
              <label for="email">E-mail *</label>
              <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
              <label for="subject">Assunto</label>
              <input type="text" id="subject" name="subject">
            </div>
            <div class="form-group">
              <label for="message">Mensagem *</label>
              <textarea id="message" name="message" required></textarea>
            </div>
            <button type="submit" class="btn-submit">Enviar Mensagem</button>
          </form>
        </div>

        <h3>Endereço</h3>
        <p>Mercado Afiliado Ltda.<br>
        Rua das Startups, 123 - Sala 456<br>
        Vila Madalena - São Paulo/SP<br>
        CEP: 05432-000</p>
      </div>
    </div>
  </div>

  <script>
    // Funções para abrir e fechar modais
    function openModal(modalId) {
      const modal = document.getElementById(modalId);
      if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
      }
    }

    function closeModal(modalId) {
      const modal = document.getElementById(modalId);
      if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
      }
    }

    // Fechar modal clicando no fundo
    function closeModalOnBackdrop(event, modalId) {
      if (event.target.classList.contains('modal')) {
        closeModal(modalId);
      }
    }

    // Função para enviar formulário de contato
    function submitContactForm(event) {
      event.preventDefault();
      
      const formData = {
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        subject: document.getElementById('subject').value,
        message: document.getElementById('message').value
      };
      
      // Aqui você pode implementar o envio real do formulário
      console.log('Formulário enviado:', formData);
      
      // Simulação de sucesso
      alert('Mensagem enviada com sucesso! Retornaremos em breve.');
      document.getElementById('contactForm').reset();
      closeModal('contactModal');
    }

    // Debug: verificar cliques nos botões
    document.addEventListener('DOMContentLoaded', function() {
      console.log('Página carregada');
      
      const buttons = document.querySelectorAll('.btn');
      buttons.forEach(btn => {
        btn.addEventListener('click', function(e) {
          console.log('Botão clicado:', this.textContent, 'URL:', this.href);
        });
      });

      // Adicionar event listeners para fechar modais com ESC
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          const openModals = document.querySelectorAll('.modal.show');
          openModals.forEach(modal => {
            modal.classList.remove('show');
            document.body.style.overflow = '';
          });
        }
      });

      // Adicionar event listeners para fechar modais clicando no fundo
      const modals = document.querySelectorAll('.modal');
      modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
          if (e.target === modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
          }
        });
      });
    });
  </script>

</body>
</html>