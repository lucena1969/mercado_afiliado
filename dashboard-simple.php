<?php
/**
 * Dashboard Simplificado - Sem dependências de banco
 * Mercado Afiliado
 */

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir constantes básicas
if (!defined('BASE_URL')) {
    define('BASE_URL', 'https://mercadoafiliado.com.br');
}
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Mercado Afiliado');
}

// Verificar se está logado
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ' . BASE_URL . '/login-test.php');
    exit;
}

// Obter dados do usuário
$user_name = $_SESSION['user_name'] ?? 'Usuário';
$user_email = $_SESSION['user_email'] ?? '';
$is_google_user = $_SESSION['google_user'] ?? false;
$first_name = explode(' ', $user_name)[0];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= APP_NAME ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f9fafb;
            color: #374151;
            line-height: 1.6;
        }
        
        .header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: #1f2937;
            font-weight: 700;
            font-size: 1.125rem;
        }
        
        .brand-mark {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 6px;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 1rem;
            align-items: center;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #6b7280;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .nav-links a:hover {
            background: #f3f4f6;
            color: #374151;
        }
        
        .main {
            padding: 2rem 0;
        }
        
        .welcome-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .welcome-title {
            font-size: 1.875rem;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .welcome-subtitle {
            color: #6b7280;
            font-size: 1.125rem;
            margin-bottom: 1rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .feature-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            transition: all 0.2s;
        }
        
        .feature-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        
        .feature-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .feature-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .feature-description {
            color: #6b7280;
            margin-bottom: 1rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1rem;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            background: #10b981;
            color: white;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .google-badge {
            background: #ea4335;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="nav">
                <a href="<?= BASE_URL ?>/dashboard-simple.php" class="nav-brand">
                    <div class="brand-mark"></div>
                    <?= APP_NAME ?>
                </a>
                <ul class="nav-links">
                    <li>
                        <span style="color: #6b7280;">
                            Olá, <?= htmlspecialchars($first_name) ?>
                            <?php if ($is_google_user): ?>
                                <span class="status-badge google-badge">Google</span>
                            <?php endif; ?>
                        </span>
                    </li>
                    <li><a href="<?= BASE_URL ?>/logout.php">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main">
        <div class="container">
            <!-- Welcome Section -->
            <div class="welcome-card">
                <h1 class="welcome-title">Bem-vindo ao Mercado Afiliado!</h1>
                <p class="welcome-subtitle">Sua plataforma completa para performance em marketing digital.</p>
                
                <div class="user-info">
                    <div>
                        <strong>📧 E-mail:</strong> <?= htmlspecialchars($user_email) ?><br>
                        <strong>👤 Nome:</strong> <?= htmlspecialchars($user_name) ?>
                        <?php if ($is_google_user): ?>
                            <br><strong>🔐 Login:</strong> Autenticado via Google OAuth
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Features Grid -->
            <div class="features-grid">
                <!-- Link Maestro -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" fill="white" viewBox="0 0 24 24">
                            <path d="M10 13a5 5 0 0 1 0-7l1-1a5 5 0 0 1 7 7l-1 1"></path>
                            <path d="M14 11a5 5 0 0 1 0 7l-1 1a5 5 0 0 1-7-7l1-1"></path>
                        </svg>
                    </div>
                    <h3 class="feature-title">Link Maestro</h3>
                    <p class="feature-description">Padronize UTMs, encurte links e rastreie cliques com consistência. Relatórios por campanha, anúncio e criativo.</p>
                    <a href="#" class="btn">Acessar Links</a>
                </div>

                <!-- Pixel BR -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" fill="white" viewBox="0 0 24 24">
                            <path d="M12 3l7 4v5c0 5-3.5 9-7 9s-7-4-7-9V7l7-4z"></path>
                            <path d="M9 12l2 2 4-4"></path>
                        </svg>
                    </div>
                    <h3 class="feature-title">Pixel BR</h3>
                    <p class="feature-description">Coleta no seu domínio e envia via CAPI/Enhanced Conversions/Events API. Menos bloqueio, mais conversões.</p>
                    <a href="#" class="btn">Configurar Pixel</a>
                </div>

                <!-- IntegraSync -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" fill="white" viewBox="0 0 24 24">
                            <path d="M9 7v6a3 3 0 1 0 6 0V7"></path>
                            <path d="M12 3v4"></path>
                            <path d="M7 12H3"></path>
                            <path d="M21 12h-4"></path>
                        </svg>
                    </div>
                    <h3 class="feature-title">IntegraSync</h3>
                    <p class="feature-description">Hotmart, Monetizze e outras plataformas em um só painel, com alertas e reconciliação.</p>
                    <a href="#" class="btn">Ver Integrações</a>
                </div>
            </div>
        </div>
    </main>

    <script>
        console.log('Dashboard carregado com sucesso!');
        console.log('Usuário logado:', <?= json_encode([
            'name' => $user_name,
            'email' => $user_email,
            'google_user' => $is_google_user
        ]) ?>);
    </script>
</body>
</html>