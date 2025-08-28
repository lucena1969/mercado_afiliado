<?php
/**
 * API OAuth - Rotas de autenticação social
 */

require_once '../../config/app.php';

$authController = new AuthController();

// Obter a rota atual
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');

// Determinar qual método OAuth chamar baseado na URL
if (strpos($uri, 'auth/google') !== false) {
    $authController->googleLogin();
} elseif (strpos($uri, 'auth/facebook') !== false) {
    $authController->facebookLogin();
} else {
    // Fallback - tentar detectar pela query string ou outros métodos
    $path_parts = explode('/', $uri);
    $provider = end($path_parts);
    
    switch ($provider) {
        case 'google':
            $authController->googleLogin();
            break;
        case 'facebook':
            $authController->facebookLogin();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint OAuth não encontrado: ' . $provider]);
            break;
    }
}