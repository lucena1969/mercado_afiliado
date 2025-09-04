<?php
/**
 * Sistema de roteamento simples para o Mercado Afiliado
 */

// Incluir configurações da aplicação
require_once dirname(__DIR__) . '/config/app.php';

// Iniciar sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir classes necessárias
$root_path = dirname(__DIR__);

// Auto-load simples das classes
function autoload_classes($class_name) {
    $root_path = dirname(__DIR__);
    
    $paths = [
        $root_path . '/app/models/' . $class_name . '.php',
        $root_path . '/app/controllers/' . $class_name . '.php',
        $root_path . '/config/' . $class_name . '.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
}

spl_autoload_register('autoload_classes');

// Obter a rota da URL ou parâmetro GET
$route = '';
if (isset($_GET['route'])) {
    $route = $_GET['route'];
} else {
    $request_uri = $_SERVER['REQUEST_URI'];
    $route = parse_url($request_uri, PHP_URL_PATH);
    $route = trim($route, '/');
}

// Se a rota estiver vazia, mostrar a landing page
if (empty($route)) {
    include __DIR__ . '/index.php';
    exit;
}

// Definir as rotas disponíveis
$routes = [
    'register' => $root_path . '/templates/auth/register.php',
    'login' => $root_path . '/templates/auth/login.php',
    'logout' => $root_path . '/templates/auth/logout.php',
    'dashboard' => $root_path . '/templates/dashboard/index.php',
    'unified-panel' => $root_path . '/templates/dashboard/unified_panel.php',
    'integrations' => $root_path . '/templates/integrations/index.php',
    'integrations/add' => $root_path . '/templates/integrations/add.php',
    'integrations/test' => $root_path . '/templates/integrations/test.php',
    'pixel' => $root_path . '/templates/pixel/index.php',
    'pixel/events' => $root_path . '/templates/pixel/events.php',
    'pixel/event-details' => $root_path . '/templates/pixel/event-details.php',
    'auth/google' => $root_path . '/api/auth/oauth.php',
    'auth/google/callback' => $root_path . '/api/auth/oauth.php',
    'login-manual' => $root_path . '/templates/auth/login_manual.php',
];

// Verificar se a rota existe
if (array_key_exists($route, $routes)) {
    $template_path = $routes[$route];
    
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        show_404();
    }
} else {
    // Verificar se é uma rota de API
    if (strpos($route, 'api/') === 0) {
        handle_api_route($route);
    } else {
        show_404();
    }
}

function handle_api_route($route) {
    global $root_path;
    
    // Remover o prefixo 'api/'
    $api_route = substr($route, 4);
    
    $api_routes = [
        'auth' => $root_path . '/api/auth.php',
        'auth/register' => $root_path . '/api/auth.php',
        'auth/login' => $root_path . '/api/auth.php',
        'auth/logout' => $root_path . '/api/auth.php',
        'webhooks' => $root_path . '/api/webhooks.php',
        'pixel/collect' => $root_path . '/api/pixel/collect.php',
    ];

    // Rotas OAuth
    $oauth_routes = [
        'auth/google' => $root_path . '/api/auth/oauth.php',
        'auth/google/callback' => $root_path . '/api/auth/oauth.php',
        'auth/facebook' => $root_path . '/api/auth/oauth.php',
        'auth/facebook/callback' => $root_path . '/api/auth/oauth.php',
    ];

    // Verificar rotas OAuth primeiro
    if (array_key_exists($api_route, $oauth_routes)) {
        include $oauth_routes[$api_route];
        return;
    }
    
    if (array_key_exists($api_route, $api_routes)) {
        include $api_routes[$api_route];
    } else {
        show_404();
    }
}

function show_404() {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Página não encontrada - <?= APP_NAME ?></title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                margin: 0;
                padding: 0;
                background: #f9fafb;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                color: #374151;
            }
            .container {
                text-align: center;
                max-width: 500px;
                padding: 2rem;
            }
            h1 {
                font-size: 6rem;
                margin: 0;
                color: #9ca3af;
                font-weight: 800;
            }
            h2 {
                font-size: 1.5rem;
                margin: 1rem 0;
                color: #1f2937;
            }
            p {
                color: #6b7280;
                margin-bottom: 2rem;
            }
            .btn {
                display: inline-flex;
                align-items: center;
                padding: 0.75rem 1.5rem;
                background: #3b82f6;
                color: white;
                text-decoration: none;
                border-radius: 0.5rem;
                font-weight: 600;
                transition: background 0.2s;
            }
            .btn:hover {
                background: #2563eb;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>404</h1>
            <h2>Página não encontrada</h2>
            <p>A página que você está procurando não existe ou foi removida.</p>
            <a href="<?= BASE_URL ?>/" class="btn">Voltar ao início</a>
        </div>
    </body>
    </html>
    <?php
}
?>