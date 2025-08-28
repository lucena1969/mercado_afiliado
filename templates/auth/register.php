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
                                    Concordo com os <a href="#" style="color: var(--color-primary);">termos de uso</a> 
                                    e <a href="#" style="color: var(--color-primary);">política de privacidade</a>
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
    </script>
</body>
</html>