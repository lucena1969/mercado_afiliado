<?php
/**
 * Arquivo principal - Router para Hostinger (Document Root fixo)
 * Mercado Afiliado
 */

require_once 'config/app.php';
require_once 'config/database.php';

// Router simples
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remover query string
$path = strtok($path, '?');

// Rotas
switch ($path) {
    case '':
    case '/':
        include 'templates/landing.php';
        break;
        
    case '/login':
        include 'templates/auth/login.php';
        break;
        
    case '/register':
        include 'templates/auth/register.php';
        break;
        
    case '/dashboard':
        include 'templates/dashboard/index.php';
        break;
        
    case '/integrations':
        include 'templates/integrations/index.php';
        break;
        
    case '/integrations/add':
        include 'templates/integrations/add.php';
        break;
        
    case '/integrations/test':
        include 'templates/integrations/test.php';
        break;
        
    case '/pixel':
        include 'templates/pixel/index.php';
        break;
        
    case '/pixel/save':
        include 'templates/pixel/save.php';
        break;
        
    case '/pixel/events':
        include 'templates/pixel/events.php';
        break;
        
    case '/pixel/event-details':
        include 'templates/pixel/event-details.php';
        break;
        
    case '/pricing':
        include 'templates/pricing.php';
        break;
        
    case '/logout':
        include 'app/controllers/AuthController.php';
        $auth = new AuthController();
        $auth->logout();
        break;
        
    // APIs
    case '/api/auth/login':
        include 'api/auth.php';
        break;
        
    case '/api/auth/register':
        include 'api/auth.php';
        break;
        
    // Verificar se é uma rota de webhook antes do default
    default:
        // Verificar se é uma rota de webhook
        if (preg_match('/^\/api\/webhooks\/([a-z]+)\/([a-zA-Z0-9]+)$/', $path, $matches)) {
            $platform = $matches[1];
            $token = $matches[2];
            include 'api/webhooks.php';
        } else {
            http_response_code(404);
            echo "<h1>404 - Página não encontrada</h1>";
        }
        break;
}
?>