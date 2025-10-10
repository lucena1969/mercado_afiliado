<?php
/**
 * API endpoints para autenticação
 */

require_once '../config/app.php';
require_once '../app/controllers/AuthController.php';

// Determinar ação baseada na URL
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Múltiplas tentativas de extrair a action
$action = '';
if (strpos($path, '/api/auth/register') !== false) {
    $action = 'register';
} elseif (strpos($path, '/api/auth/login') !== false) {
    $action = 'login';
} elseif (strpos($path, '/api/auth/logout') !== false) {
    $action = 'logout';
} else {
    // Fallback: pegar a última parte da URL
    $parts = explode('/', trim($path, '/'));
    $action = end($parts);
}

// Debug removido - sistema funcionando

$authController = new AuthController();

switch ($action) {
    case 'login':
        $authController->login();
        break;
        
    case 'register':
        $authController->register();
        break;
        
    case 'logout':
        $authController->logout();
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint não encontrado']);
        break;
}