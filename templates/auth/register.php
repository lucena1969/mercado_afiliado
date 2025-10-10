<?php
// Redirecionar se já estiver logado
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    header('Location: ' . BASE_URL . '/dashboard');
    exit;
}

// Buscar planos disponíveis
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$subscription = new Subscription($db);
$plans = $subscription->getActivePlans();

// Plano selecionado na URL
$selected_plan = $_GET['plan'] ?? 'starter';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar conta - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body style="background: #f9fafb;">
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem;">
        <div style="width: 100%; max-width: 500px;">
            <!-- Logo -->
            <div style="text-align: center; margin-bottom: 2rem;">
                <a href="<?= BASE_URL ?>" style="display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; color: var(--color-dark);">
                    <div style="width: 40px; height: 40px; background: var(--color-primary); border-radius: 8px;"></div>
                    <span style="font-size: 1.5rem; font-weight: 700;">Mercado Afiliado</span>
                </a>
            </div>

            <!-- Card de Registro -->
            <div class="card">
                <div class="card-header">
                    <h1 style="font-size: 1.5rem; font-weight: 600; text-align: center;">Teste grátis por 14 dias</h1>
                    <p style="text-align: center; color: var(--color-gray); margin-top: 0.5rem; font-size: 0.875rem;">
                        Sem compromisso. Cancele quando quiser.
                    </p>
                </div>
                <div class="card-body">
                    <!-- Mensagens de erro -->
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-error">
                            <?= htmlspecialchars($_SESSION['error_message']) ?>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>/api/auth/register" method="POST">
                        <div class="form-group">
                            <label for="name" class="form-label">Nome completo</label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                class="form-input" 
                                placeholder="João Silva"
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">E-mail</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-input" 
                                placeholder="joao@exemplo.com"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">Telefone (opcional)</label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                class="form-input" 
                                placeholder="(11) 99999-9999"
                                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                            >
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="password" class="form-label">Senha</label>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    class="form-input" 
                                    placeholder="••••••••"
                                    required
                                    minlength="6"
                                >
                            </div>

                            <div class="form-group">
                                <label for="password_confirm" class="form-label">Confirmar senha</label>
                                <input 
                                    type="password" 
                                    id="password_confirm" 
                                    name="password_confirm" 
                                    class="form-input" 
                                    placeholder="••••••••"
                                    required
                                    minlength="6"
                                >
                            </div>
                        </div>

                        <!-- Seleção do plano -->
                        <div class="form-group">
                            <label class="form-label">Escolha seu plano</label>
                            <div style="display: grid; gap: 0.5rem;">
                                <?php foreach ($plans as $plan): ?>
                                    <label style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; <?= $selected_plan === $plan['slug'] ? 'border-color: var(--color-primary); background: rgba(245, 158, 11, 0.05);' : '' ?>">
                                        <input 
                                            type="radio" 
                                            name="plan" 
                                            value="<?= $plan['slug'] ?>"
                                            <?= $selected_plan === $plan['slug'] ? 'checked' : '' ?>
                                            style="margin: 0;"
                                        >
                                        <div style="flex: 1;">
                                            <div style="font-weight: 600; color: var(--color-dark);">
                                                <?= htmlspecialchars($plan['name']) ?> - R$ <?= number_format($plan['price_monthly'], 0, ',', '.') ?>/mês
                                            </div>
                                            <div style="font-size: 0.875rem; color: var(--color-gray); margin-top: 0.25rem;">
                                                <?= htmlspecialchars($plan['description']) ?>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" required style="margin-top: 0.25rem;">
                                <span style="font-size: 0.875rem; color: var(--color-gray);">
                                    Concordo com os <a href="#" onclick="openModal('termos-modal')" style="color: var(--color-primary); text-decoration: none;">termos de uso</a> 
                                    e <a href="#" onclick="openModal('privacidade-modal')" style="color: var(--color-primary); text-decoration: none;">política de privacidade</a>
                                </span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                            Criar conta e iniciar trial
                        </button>
                    </form>

                    <div style="text-align: center; font-size: 0.875rem; color: var(--color-gray);">
                        💳 Não cobramos nada durante o trial<br>
                        🚫 Cancele quando quiser
                    </div>
                </div>
            </div>

            <!-- Link para login -->
            <div style="text-align: center; margin-top: 1.5rem; color: var(--color-gray);">
                Já tem uma conta? 
                <a href="<?= BASE_URL ?>/login" style="color: var(--color-primary); text-decoration: none; font-weight: 600;">
                    Fazer login
                </a>
            </div>
        </div>
    </div>

    <!-- Modal de Termos de Uso -->
    <div id="termos-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Termos de Uso – Mercado Afiliado</h2>
                <span class="modal-close" onclick="closeModal('termos-modal')">&times;</span>
            </div>
            <div class="modal-body">
                <h3>1. Aceitação dos Termos</h3>
                <p>Ao acessar e utilizar a plataforma Mercado Afiliado, o usuário declara estar de acordo com estes Termos de Uso, bem como com a legislação aplicável. Caso não concorde com qualquer condição aqui prevista, não deverá utilizar o serviço.</p>
                
                <h3>2. Descrição do Serviço</h3>
                <p>O Mercado Afiliado é uma plataforma de suporte às campanhas de marketing de afiliados, que permite aos usuários monitorar a venda de produtos e serviços, sejam digitais ou não, mantidos nas diversas plataformas de vendas, mediante a configuração de pixels de vendas e outros recursos tecnológicos.</p>
                
                <h3>3. Cadastro e Responsabilidades do Usuário</h3>
                <ul>
                    <li>O usuário compromete-se a:</li>
                    <li>Fornecer informações verdadeiras, atualizadas e completas no momento do cadastro;</li>
                    <li>Manter a confidencialidade de seu login e senha, sendo responsável por todas as atividades realizadas em sua conta;</li>
                    <li>Utilizar a plataforma de forma ética, legal e em conformidade com estes Termos de Uso;</li>
                    <li>Não praticar atividades que possam violar direitos de terceiros, incluindo, mas não se limitando a, propriedade intelectual e direitos de imagem;</li>
                    <li>Não utilizar a plataforma para fins fraudulentos, ilegais ou abusivos</li>
                    <li>Não violar direitos de propriedade intelectual</li>
                </ul>
                
                <h3>4. Assinatura, Cobrança e Cancelamento</h3>
                <p>O Mercado Afiliado é oferecido mediante assinatura mensal;</p>
                <p>As cobranças são recorrentes e processadas de acordo com o plano contratado pelo usuário;</p>
                <p>O usuário autoriza a cobrança automática no cartão ou meio de pagamento escolhido, até que opte pelo cancelamento;</p>
                <p>O cancelamento pode ser solicitado a qualquer momento, produzindo efeitos apenas para os ciclos de cobrança futuros. Não haverá reembolso proporcional de valores já pagos;</p>
                <p>O não pagamento no prazo acarretará na suspensão automática do acesso à plataforma até a regularização.</p>
                
                <h3>5. Limitações de Responsabilidade</h3>
                <p>O Mercado Afiliado:</p>
                <p>Não garante resultados financeiros, pois estes dependem exclusivamente da atuação e estratégia do usuário;</p>
                <p>Não se responsabiliza por falhas técnicas, interrupções temporárias ou indisponibilidades de serviços de terceiros;</p>
                <p>Não responde por danos indiretos, perda de lucros, dados ou quaisquer prejuízos decorrentes do uso da plataforma.</p>
                
                <h3>6. Alterações nos Termos</h3>
                <p>O Mercado Afiliado poderá atualizar estes Termos a qualquer momento, mediante publicação em sua plataforma. A continuidade do uso após a atualização será interpretada como aceitação das novas condições.</p>
                
                <h3>7. Término do Serviço</h3>
                <p>Qualquer parte pode encerrar o acordo a qualquer momento. Após o término, você perde o acesso à plataforma.</p>
                
                <h3>8. Foro</h3>
                <p>Fica eleito o foro da comarca do domicílio do prestador do serviço como competente para dirimir quaisquer controvérsias oriundas destes Termos..</p>
                
                <p><strong>Data de vigência:</strong> 29 de agosto de 2025</p>
            </div>
        </div>
    </div>

    <!-- Modal de Política de Privacidade -->
    <div id="privacidade-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Política de Privacidade</h2>
                <span class="modal-close" onclick="closeModal('privacidade-modal')">&times;</span>
            </div>
            <div class="modal-body">
                <h3>1. Informações que Coletamos</h3>
                <p>Coletamos as seguintes informações:</p>
                <ul>
                    <li>Dados pessoais: nome, e-mail, telefone</li>
                    <li>Informações de conta: senhas criptografadas</li>
                    <li>Dados de uso: estatísticas de cliques e conversões</li>
                    <li>Informações técnicas: endereço IP, navegador, dispositivo</li>
                </ul>
                
                <h3>2. Como Usamos suas Informações</h3>
                <ul>
                    <li>Fornecer e melhorar nossos serviços</li>
                    <li>Processar pagamentos de comissões</li>
                    <li>Comunicar atualizações e promoções</li>
                    <li>Cumprir obrigações legais</li>
                    <li>Análise e melhoria da plataforma</li>
                </ul>
                
                <h3>3. Compartilhamento de Informações</h3>
                <p>Não vendemos suas informações pessoais. Podemos compartilhar dados apenas com:</p>
                <ul>
                    <li>Prestadores de serviços essenciais</li>
                    <li>Autoridades legais quando exigido</li>
                    <li>Parceiros comerciais com seu consentimento</li>
                </ul>
                
                <h3>4. Segurança dos Dados</h3>
                <p>Implementamos medidas de segurança técnicas e organizacionais para proteger suas informações contra acesso não autorizado, alteração, divulgação ou destruição.</p>
                
                <h3>5. Seus Direitos</h3>
                <p>Você tem direito a:</p>
                <ul>
                    <li>Acessar seus dados pessoais</li>
                    <li>Corrigir informações incorretas</li>
                    <li>Solicitar exclusão de dados</li>
                    <li>Portabilidade dos dados</li>
                    <li>Revogar consentimentos</li>
                </ul>
                
                <h3>6. Cookies e Tecnologias Similares</h3>
                <p>Utilizamos cookies para melhorar a experiência do usuário, analisar o tráfego e personalizar conteúdo.</p>
                
                <h3>7. Contato</h3>
                <p>Para questões sobre privacidade, entre em contato: contato@mercadoafiliado.com</p>
                
                <p><strong>Data de vigência:</strong> 29 de agosto de 2025</p>
            </div>
        </div>
    </div>

    <style>
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 8px;
            width: 100%;
            max-width: 600px;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--color-dark);
        }
        
        .modal-close {
            font-size: 2rem;
            font-weight: bold;
            color: #9ca3af;
            cursor: pointer;
            line-height: 1;
            transition: color 0.2s;
        }
        
        .modal-close:hover {
            color: var(--color-dark);
        }
        
        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            line-height: 1.6;
        }
        
        .modal-body h3 {
            color: var(--color-dark);
            font-weight: 600;
            margin: 1.5rem 0 0.5rem 0;
            font-size: 1.1rem;
        }
        
        .modal-body h3:first-child {
            margin-top: 0;
        }
        
        .modal-body p {
            margin-bottom: 1rem;
            color: var(--color-gray);
        }
        
        .modal-body ul {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }
        
        .modal-body li {
            margin-bottom: 0.5rem;
            color: var(--color-gray);
        }
    </style>

    <script>
        // Auto-focus no primeiro campo
        document.getElementById('name').focus();

        // Validação de senhas em tempo real
        document.getElementById('password_confirm').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirm = this.value;
            
            if (confirm && password !== confirm) {
                this.setCustomValidity('As senhas não coincidem');
            } else {
                this.setCustomValidity('');
            }
        });

        // Funções dos modais
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Fechar modal clicando fora do conteúdo
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        // Fechar modal com ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    if (modal.style.display === 'flex') {
                        modal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }
                });
            }
        });
    </script>
</body>