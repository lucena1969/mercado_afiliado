<?php
/**
 * API Endpoint - Sincronização de Integrações
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/app.php';
require_once '../config/database.php';

// Iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação
if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

$user_id = $_SESSION['user']['id'];
$method = $_SERVER['REQUEST_METHOD'];

// Conectar ao banco
$database = new Database();
$db = $database->getConnection();

// Instanciar services
$syncService = new SyncService($db);
$integration = new Integration($db);

try {
    switch ($method) {
        case 'POST':
            handleSyncRequest($syncService, $integration, $user_id);
            break;
            
        case 'GET':
            handleStatusRequest($syncService, $integration, $user_id);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}

function handleSyncRequest($syncService, $integration, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['integration_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'integration_id é obrigatório']);
        return;
    }
    
    $integration_id = $input['integration_id'];
    $sync_type = $input['type'] ?? 'full'; // products, sales, full
    $days = $input['days'] ?? 30;
    
    // Verificar se a integração pertence ao usuário
    $integration_data = $integration->findById($integration_id);
    if (!$integration_data || $integration_data['user_id'] != $user_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Integração não encontrada ou não autorizada']);
        return;
    }
    
    // Executar sincronização baseada no tipo
    switch ($sync_type) {
        case 'products':
            $result = $syncService->syncProducts($integration_id);
            break;
            
        case 'sales':
            $result = $syncService->syncSales($integration_id, $days);
            break;
            
        case 'full':
            $result = $syncService->fullSync($integration_id, $days);
            break;
            
        case 'test':
            $result = $syncService->testConnection($integration_id);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Tipo de sincronização inválido']);
            return;
    }
    
    echo json_encode($result);
}

function handleStatusRequest($syncService, $integration, $user_id) {
    $integration_id = $_GET['integration_id'] ?? null;
    
    if (!$integration_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'integration_id é obrigatório']);
        return;
    }
    
    // Verificar se a integração pertence ao usuário
    $integration_data = $integration->findById($integration_id);
    if (!$integration_data || $integration_data['user_id'] != $user_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Integração não encontrada ou não autorizada']);
        return;
    }
    
    // Retornar status da integração
    $stats = $integration->getStats($integration_id);
    
    echo json_encode([
        'success' => true,
        'integration' => [
            'id' => $integration_data['id'],
            'name' => $integration_data['name'],
            'platform' => $integration_data['platform'],
            'status' => $integration_data['status'],
            'last_sync_at' => $integration_data['last_sync_at'],
            'last_error' => $integration_data['last_error']
        ],
        'stats' => $stats
    ]);
}