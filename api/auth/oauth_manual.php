<?php
/**
 * API OAuth Manual - Funciona sem vendor/
 */

require_once '../../config/app.php';

// Usar AuthControllerManual em vez do normal
if (file_exists('../../app/controllers/AuthControllerManual.php')) {
    require_once '../../app/controllers/AuthControllerManual.php';
    $authController = new AuthControllerManual();
} else {
    // Fallback para AuthController normal
    $authController = new AuthController();
}

// Obter a rota atual
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');

// Determinar qual método OAuth chamar
if (strpos($uri, 'auth/google') !== false) {
    $authController->googleLogin();
} else {
    $path_parts = explode('/', $uri);
    $provider = end($path_parts);
    
    switch ($provider) {
        case 'google':
            $authController->googleLogin();
            break;
        default:
            http_response_code(404);
            echo json_encode([
                'error' => 'Endpoint OAuth não encontrado: ' . $provider,
                'uri' => $uri,
                'available' => ['google'],
                'message' => 'Apenas Google OAuth está disponível no momento'
            ]);
            break;
    }
}
?>