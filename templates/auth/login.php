<?php
/**
 * Template de Login - Versão Corrigida
 * Mercado Afiliado
 * 
 * IMPORTANTE: Este arquivo não deve incluir config/app.php pois já é incluído pelo router
 */

// Definir constantes se não existirem (proteção contra erro)
if (!defined('BASE_URL')) {
    define('BASE_URL', 'https://mercadoafiliado.com.br');
}
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Mercado Afiliado');
}

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
    <title>Login - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body style="background: #f9fafb;">
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem;">
        <div style="width: 100%; max-width: 400px;">
            <!-- Logo -->
            <div style="text-align: center; margin-bottom: 2rem;">
                <a href="<?= BASE_URL ?>" style="display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; color: var(--color-dark);">
                    <div style="width: 40px; height: 40px; background: var(--color-primary); border-radius: 8px;"></div>
                    <span style="font-size: 1.5rem; font-weight: 700;">Mercado Afiliado</span>
                </a>
            </div>

            <!-- Card de Login -->
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

                    <form action="<?= BASE_URL ?>/api/auth/login" method="POST">
                        <div class="form-group">
                            <label for="email" class="form-label">
                                <i data-lucide="mail" style="width: 16px; height: 16px; margin-right: 6px;"></i>
                                E-mail
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-input" 
                                placeholder="seu@email.com"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">
                                <i data-lucide="lock" style="width: 16px; height: 16px; margin-right: 6px;"></i>
                                Senha
                            </label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input" 
                                placeholder="••••••••"
                                required
                            >
                        </div>

                        <div style="margin-bottom: 1rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" name="remember" style="margin: 0;">
                                <span style="font-size: 0.875rem; color: var(--color-gray);">Lembrar de mim</span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <i data-lucide="log-in" style="width: 18px; height: 18px;"></i>
                            Entrar
                        </button>
                    </form>

                    <?php if (defined('GOOGLE_CLIENT_ID') && !empty(GOOGLE_CLIENT_ID) && GOOGLE_CLIENT_ID !== 'teste-google-id'): ?>
                    <!-- Divisor OR -->
                    <div style="display: flex; align-items: center; margin: 1.5rem 0; color: var(--color-gray); font-size: 0.875rem;">
                        <hr style="flex: 1; border: none; border-top: 1px solid #e5e7eb; margin: 0;">
                        <span style="padding: 0 1rem;">ou continue com</span>
                        <hr style="flex: 1; border: none; border-top: 1px solid #e5e7eb; margin: 0;">
                    </div>

                    <!-- Botão Google OAuth -->
                    <div style="margin-bottom: 1.5rem;">
                        <a href="<?= BASE_URL ?>/auth/google" 
                           style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.75rem; 
                                  padding: 0.875rem 1rem; border: 1px solid #e5e7eb; border-radius: 8px; 
                                  text-decoration: none; color: var(--color-dark); font-weight: 500; 
                                  transition: all 0.2s; background: white; font-size: 0.95rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24">
                                <path fill="#4285f4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34a853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#fbbc05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#ea4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            Continuar com Google
                        </a>
                    </div>
                    <?php endif; ?>

                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="#" style="color: var(--color-primary); text-decoration: none; font-size: 0.875rem;">
       