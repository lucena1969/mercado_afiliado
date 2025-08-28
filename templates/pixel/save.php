<?php
/**
 * Salvar configurações do Pixel BR
 */
// Arquivo já foi incluído via router, então config já foi carregado
// Apenas carregar as dependências necessárias
require_once __DIR__ . '/../../app/controllers/AuthController.php';

$auth = new AuthController();
$auth->requireAuth();

$user_data = $_SESSION['user'] ?? null;
if (!$user_data) {
    header('Location: ' . BASE_URL . '/login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pixel');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

try {
    $pixelConfig = new PixelConfiguration($conn);
    
    if ($action === 'save_config') {
        $config_id = $_POST['config_id'] ?? null;
        
        if ($config_id) {
            $pixelConfig->id = $config_id;
            if (!$pixelConfig->read()) {
                throw new Exception('Configuração não encontrada');
            }
            
            if ($pixelConfig->user_id != $user_data['id']) {
                throw new Exception('Acesso negado');
            }
        }
        
        $pixelConfig->user_id = $user_data['id'];
        $pixelConfig->pixel_name = $_POST['pixel_name'] ?? '';
        $pixelConfig->integration_id = !empty($_POST['integration_id']) ? $_POST['integration_id'] : null;
        $pixelConfig->auto_track_pageviews = isset($_POST['auto_track_pageviews']) ? 1 : 0;
        $pixelConfig->auto_track_clicks = isset($_POST['auto_track_clicks']) ? 1 : 0;
        $pixelConfig->consent_mode = $_POST['consent_mode'] ?? 'required';
        $pixelConfig->status = 'testing';
        
        if (empty($pixelConfig->pixel_name)) {
            throw new Exception('Nome do pixel é obrigatório');
        }
        
        if ($config_id) {
            if ($pixelConfig->update()) {
                $response = ['success' => true, 'message' => 'Configuração atualizada com sucesso'];
            } else {
                throw new Exception('Erro ao atualizar configuração');
            }
        } else {
            $conn->prepare("UPDATE pixel_configurations SET status='inactive' WHERE user_id = ?")->execute([$user_data['id']]);
            
            if ($pixelConfig->create()) {
                $response = ['success' => true, 'message' => 'Pixel criado com sucesso'];
            } else {
                throw new Exception('Erro ao criar pixel');
            }
        }
        
    } elseif ($action === 'activate') {
        $config_id = $_POST['config_id'] ?? null;
        
        if (!$config_id) {
            throw new Exception('ID da configuração é obrigatório');
        }
        
        $pixelConfig->id = $config_id;
        if (!$pixelConfig->read() || $pixelConfig->user_id != $user_data['id']) {
            throw new Exception('Configuração não encontrada ou acesso negado');
        }
        
        $conn->prepare("UPDATE pixel_configurations SET status='inactive' WHERE user_id = ?")->execute([$user_data['id']]);
        
        $pixelConfig->status = 'active';
        if ($pixelConfig->update()) {
            $response = ['success' => true, 'message' => 'Pixel ativado com sucesso'];
        } else {
            throw new Exception('Erro ao ativar pixel');
        }
        
    } elseif ($action === 'save_bridges') {
        $config_id = $_POST['config_id'] ?? null;
        
        if (!$config_id) {
            throw new Exception('ID da configuração é obrigatório');
        }
        
        $pixelConfig->id = $config_id;
        if (!$pixelConfig->read() || $pixelConfig->user_id != $user_data['id']) {
            throw new Exception('Configuração não encontrada ou acesso negado');
        }
        
        $pixelConfig->facebook_pixel_id = $_POST['facebook_pixel_id'] ?? null;
        $pixelConfig->facebook_access_token = $_POST['facebook_access_token'] ?? null;
        $pixelConfig->google_conversion_id = $_POST['google_conversion_id'] ?? null;
        $pixelConfig->google_conversion_label = $_POST['google_conversion_label'] ?? null;
        $pixelConfig->tiktok_pixel_code = $_POST['tiktok_pixel_code'] ?? null;
        $pixelConfig->tiktok_access_token = $_POST['tiktok_access_token'] ?? null;
        
        if ($pixelConfig->update()) {
            $response = ['success' => true, 'message' => 'Bridges atualizados com sucesso'];
        } else {
            throw new Exception('Erro ao atualizar bridges');
        }
        
    } else {
        throw new Exception('Ação inválida');
    }
    
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

if (isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if ($response['success']) {
    $_SESSION['success_message'] = $response['message'];
} else {
    $_SESSION['error_message'] = $response['message'];
}

header('Location: ' . BASE_URL . '/pixel');
exit;
?>