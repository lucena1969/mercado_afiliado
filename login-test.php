<?php
/**
 * Página de login simplificada para teste
 * Mercado Afiliado
 */

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir constantes básicas se não existirem
if (!defined('BASE_URL')) {
    define('BASE_URL', 'https://mercadoafiliado.com.br');
}
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Mercado Afiliado');
}

// Configurações Google (hardcoded para teste)
$google_client_id = '41618611981-h8rrgi15kailmmdhh1pgcp7e97bmfue3.apps.googleusercontent.com';
$google_client_secret = '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';

// Verificar se já está logado
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
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #f9fafb;
            color: #374151;
        }
        .container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo h1 {
            margin: 0;
            font-size: 1.5rem;
            color: #1f2937;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .btn {
            width: 100%;
            padding: 0.875rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            font-size: 0.95rem;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
            margin-bottom: 1rem;
        }
        .btn-primary:hover {
            background: #2563eb;
        }
        .btn-google {
            background: white;
            color: #374151;
            border: 1px solid #e5e7eb;
            margin-bottom: 1rem;
        }
        .btn-google:hover {
            background: #f9fafb;
        }
        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: #6b7280;
            font-size: 0.875rem;
        }
        .divider hr {
            flex: 1;
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 0;
        }
        .divider span {
            padding: 0 1rem;
        }
        .alert {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .text-center {
            text-align: center;
        }
        .text-small {
            font-size: 0.875rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="logo">
                <h1><?= APP_NAME ?></h1>
            </div>

            <!-- Mensagens de erro -->
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($_SESSION['error_message']) ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Formulário de Login Normal -->
            <form action="<?= BASE_URL ?>/api/auth/login" method="POST">
                <div class="form-group">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           placeholder="seu@email.com" required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Senha</label>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    Entrar
                </button>
            </form>

            <!-- Botão Google -->
            <?php if (!empty($google_client_id) && !empty($google_client_secret)): ?>
            <div class="divider">
                <hr><span>ou continue com</span><hr>
            </div>

            <a href="<?= BASE_URL ?>/api/auth/google-simple.php?action=login" class="btn btn-google">
                <svg width="20" height="20" viewBox="0 0 24 24">
                    <path fill="#4285f4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34a853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#fbbc05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#ea4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Continuar com Google
            </a>
            <?php endif; ?>

            <div class="text-center text-small">
                Não tem uma conta? 
                <a href="<?= BASE_URL ?>/register" style="color: #3b82f6;">Teste grátis</a>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus no primeiro campo
        document.getElementById('email').focus();
    </script>
</body>
</html>