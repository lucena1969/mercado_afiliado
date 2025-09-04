<?php
/**
 * API Endpoint - Configuração de Integrações
 */

// Limpar qualquer output anterior
ob_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/app.php';
require_once '../config/database.php';

// Carregar models e services necessários
require_once '../app/models/Integration.php';
require_once '../app/services/SyncService.php';
require_once '../app/services/HotmartService.php';
require_once '../app/services/MonetizzeService.php';
require_once '../app/services/EduzzService.php';
require_once '../app/services/BraipService.php';

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
$integration = new Integration($db);

// SyncService só é necessário para operações que não sejam DELETE
$syncService = null;
if ($method !== 'DELETE') {
    $syncService = new SyncService($db);
}

try {
    switch ($method) {
        case 'GET':
            handleGetConfig($integration, $user_id);
            break;
            
        case 'PUT':
        case 'POST':
            handleUpdateConfig($integration, $syncService, $user_id);
            break;
            
        case 'DELETE':
            handleDeleteIntegration($integration, $user_id);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    }
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

function handleGetConfig($integration, $user_id) {
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
    
    // Remover informações sensíveis
    unset($integration_data['api_secret']);
    
    // Mascarar API key (mostrar apenas últimos 4 caracteres)
    if (!empty($integration_data['api_key'])) {
        $api_key = $integration_data['api_key'];
        $integration_data['api_key_masked'] = str_repeat('*', max(0, strlen($api_key) - 4)) . substr($api_key, -4);
    }
    
    echo json_encode([
        'success' => true,
        'integration' => $integration_data
    ]);
}

function handleUpdateConfig($integration, $syncService, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['integration_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'integration_id é obrigatório']);
        return;
    }
    
    $integration_id = $input['integration_id'];
    $name = trim($input['name'] ?? '');
    $api_key = trim($input['api_key'] ?? '');
    $api_secret = trim($input['api_secret'] ?? '');
    $validate_credentials = $input['validate_credentials'] ?? true;
    
    // Verificar se a integração pertence ao usuário
    $integration_data = $integration->findById($integration_id);
    if (!$integration_data || $integration_data['user_id'] != $user_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Integração não encontrada ou não autorizada']);
        return;
    }
    
    // Validar dados obrigatórios
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nome da integração é obrigatório']);
        return;
    }
    
    if (empty($api_key)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'API Key é obrigatória']);
        return;
    }
    
    // Para algumas plataformas, API Secret é obrigatório
    $platform = $integration_data['platform'];
    if (in_array($platform, ['hotmart']) && empty($api_secret)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'API Secret é obrigatória para ' . ucfirst($platform)]);
        return;
    }
    
    // Validar credenciais se solicitado
    if ($validate_credentials) {
        $validation_result = $syncService->testConnection($integration_id);
        if (!$validation_result['success']) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'error' => 'Credenciais inválidas: ' . ($validation_result['error'] ?? 'Erro desconhecido')
            ]);
            return;
        }
    }
    
    // Gerar novo webhook token se não existir
    $webhook_token = $integration_data['webhook_token'];
    if (empty($webhook_token)) {
        $webhook_token = bin2hex(random_bytes(32));
    }
    
    // Atualizar credenciais
    $success = $integration->updateCredentials($integration_id, $name, $api_key, $api_secret, $webhook_token);
    
    if ($success) {
        // Buscar dados atualizados
        $updated_integration = $integration->findById($integration_id);
        
        // Gerar nova webhook URL
        $webhook_url = BASE_URL . "/api/webhooks/" . $platform . "/" . $webhook_token;
        
        echo json_encode([
            'success' => true,
            'message' => 'Integração configurada com sucesso',
            'integration' => [
                'id' => $updated_integration['id'],
                'name' => $updated_integration['name'],
                'platform' => $updated_integration['platform'],
                'status' => $updated_integration['status'],
                'webhook_url' => $webhook_url,
                'last_sync_at' => $updated_integration['last_sync_at']
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar configurações']);
    }
}

function handleDeleteIntegration($integration, $user_id) {
    // Para DELETE, ler do corpo da requisição OU da query string
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);
    $integration_id = $input['integration_id'] ?? $_GET['integration_id'] ?? null;
    
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
    
    // Deletar a integração (cascade delete irá remover produtos e vendas relacionadas)
    $success = $integration->delete($integration_id, $user_id);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Integração excluída com sucesso'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao excluir integração']);
    }
}
?>