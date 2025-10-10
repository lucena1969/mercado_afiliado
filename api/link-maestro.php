<?php
/**
 * API Link Maestro - Endpoints para gerenciamento de links
 */

// Incluir configurações
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/controllers/LinkMaestroController.php';

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Responder a requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Instanciar controller
    $controller = new LinkMaestroController();
    
    // Obter a rota da API
    $request_uri = $_SERVER['REQUEST_URI'];
    $route = parse_url($request_uri, PHP_URL_PATH);
    $route = trim($route, '/');
    
    // Remover prefixo 'api/link-maestro'
    $api_path = str_replace('api/link-maestro', '', $route);
    $api_path = trim($api_path, '/');
    
    // Obter método HTTP
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Rotear para método apropriado
    if ($api_path === 'create' && $method === 'POST') {
        $controller->createLink();
    } elseif ($api_path === 'links' && $method === 'GET') {
        $controller->getLinks();
    } elseif ($api_path === 'templates' && $method === 'POST') {
        $controller->createTemplate();
    } elseif ($api_path === 'templates' && $method === 'GET') {
        $controller->getTemplates();
    } elseif ($api_path === 'presets' && $method === 'GET') {
        $controller->getAllPresets();
    } elseif ($api_path === 'analytics' && $method === 'GET') {
        $controller->getAnalytics();
    } elseif (preg_match('/^presets\/([a-zA-Z]+)$/', $api_path, $matches) && $method === 'GET') {
        $platform = $matches[1];
        $controller->getPresets($platform);
    } elseif (preg_match('/^stats\/(\d+)$/', $api_path, $matches) && $method === 'GET') {
        $link_id = intval($matches[1]);
        $controller->getLinkStats($link_id);
    } else {
        // Endpoint não encontrado
        http_response_code(404);
        echo json_encode([
            'error' => 'Endpoint não encontrado',
            'path' => $api_path,
            'method' => $method,
            'available_endpoints' => [
                'POST /api/link-maestro/create' => 'Criar link encurtado',
                'GET /api/link-maestro/links' => 'Listar links do usuário',
                'POST /api/link-maestro/templates' => 'Criar template UTM',
                'GET /api/link-maestro/templates' => 'Listar templates do usuário',
                'GET /api/link-maestro/presets' => 'Obter todos os presets',
                'GET /api/link-maestro/analytics' => 'Obter relatório de analytics',
                'GET /api/link-maestro/presets/{platform}' => 'Obter presets por plataforma',
                'GET /api/link-maestro/stats/{link_id}' => 'Estatísticas de um link específico'
            ]
        ]);
    }
    
} catch (Exception $e) {
    // Log do erro
    error_log('Erro na API Link Maestro: ' . $e->getMessage());
    
    // Resposta de erro
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor',
        'message' => $e->getMessage()
    ]);
}
?>