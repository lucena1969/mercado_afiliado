<?php
// Teste OAuth - Versão do login com botões sempre visíveis
require_once 'config/app.php';

// Redirecionar se já estiver logado
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    header('Location: ' . BASE_URL . '/dashboard');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login TESTE OAuth - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body style="background: #f9fafb;">
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem;">
        <div style="width: 100%; max-width: 400px;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h1 style="color: #e11d48;">🔍 TESTE OAuth</h1>
                <p>Esta é uma página de teste para verificar os botões OAuth</p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h1 style="font-size: 1.5rem; font-weight: 600; text-align: center;">Faça seu login</h1>
                </div>
                <div class="card-body">
                    <!-- Mensagens de erro/sucesso -->
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-error">
                            <?= htmlspecialchars($_SESSION['error_message']) ?>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <!-- Login tradicional -->
                    <form action="<?= BASE_URL ?>/api/auth/login" method="POST">
                        <div class="form-group">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" id="email" name="email" class="form-input" placeholder="seu@email.com" required>
                        </div>
                        <div class="form-group">
                            <label for="password" class="form-label">Senha</label>
                            <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                            Entrar
                        </button>
                    </form>

                    <!-- Divisor OR - SEMPRE VISÍVEL -->
                    <div style="display: flex; align-items: center; margin: 1.5rem 0; color: var(--color-gray); font-size: 0.875rem;">
                        <hr style="flex: 1; border: none; border-top: 1px solid #e5e7eb; margin: 0;">
                        <span style="padding: 0 1rem;">ou continue com</span>
                        <hr style="flex: 1; border: none; border-top: 1px solid #e5e7eb; margin: 0;">
                    </div>

                    <!-- Botões OAuth - SEMPRE VISÍVEIS PARA TESTE -->
                    <div style="display: flex; gap: 0.75rem; margin-bottom: 1.5rem;">
                        <a href="<?= BASE_URL ?>/auth/google" 
                           style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.5rem; 
                                  padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px; 
                                  text-decoration: none; color: var(--color-dark); font-weight: 500; 
                                  transition: all 0.2s; background: white;">
                            <svg width="18" height="18" viewBox="0 0 24 24">
                                <path fill="#4285f4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34a853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#fbbc05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#ea4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            Google (TESTE)
                        </a>
                        <a href="<?= BASE_URL ?>/auth/facebook" 
                           style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.5rem; 
                                  padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px; 
                                  text-decoration: none; color: var(--color-dark); font-weight: 500; 
                                  transition: all 0.2s; background: white;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877f2">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            Facebook (TESTE)
                        </a>
                    </div>

                    <!-- Informações de Debug -->
                    <div style="background: #f3f4f6; padding: 1rem; border-radius: 8px; font-size: 0.875rem; margin-bottom: 1rem;">
                        <strong>Debug Info:</strong><br>
                        Autoload existe: <?= file_exists(APP_ROOT . '/vendor/autoload.php') ? '✅' : '❌' ?><br>
                        Google class: <?= class_exists('League\OAuth2\Client\Provider\Google') ? '✅' : '❌' ?><br>
                        Google ID: <?= !empty(GOOGLE_CLIENT_ID) ? '✅ Configurado' : '❌ Vazio' ?><br>
                        Facebook: ❌ Removido (apenas Google disponível)
                    </div>

                    <div style="text-align: center;">
                        <a href="<?= BASE_URL ?>/debug_oauth.php" style="color: #e11d48; text-decoration: none;">
                            🔍 Ver debug completo
                        </a>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 1.5rem; color: var(--color-gray);">
                <a href="<?= BASE_URL ?>/login" style="color: var(--color-primary);">← Voltar para login normal</a>
            </div>
        </div>
    </div>
</body>
</html>