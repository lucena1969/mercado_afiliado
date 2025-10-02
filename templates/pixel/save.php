<?php
/**
 * Salvar configurações do Pixel BR
 * Sistema robusto com validações e feedback aprimorado
 */

// Log para debug
error_log("Pixel Save: Iniciando processamento para usuário " . ($_SESSION['user']['id'] ?? 'não logado'));

// Carregar dependências
require_once __DIR__ . '/../../app/controllers/AuthController.php';

// Verificar autenticação
$auth = new AuthController();
$auth->requireAuth();

$user_data = $_SESSION['user'] ?? null;
if (!$user_data) {
    error_log("Pixel Save: Usuário não autenticado, redirecionando");
    header('Location: ' . BASE_URL . '/login');
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Pixel Save: Método inválido " . $_SERVER['REQUEST_METHOD']);
    header('Location: ' . BASE_URL . '/pixel');
    exit;
}

// Conectar ao banco
$db = new Database();
$conn = $db->getConnection();

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

error_log("Pixel Save: Processando ação: " . $action);

// Função auxiliar para validar dados
function validatePixelName($name) {
    if (empty($name)) {
        return "Nome do pixel é obrigatório";
    }
    if (strlen($name) < 3) {
        return "Nome do pixel deve ter pelo menos 3 caracteres";
    }
    if (strlen($name) > 100) {
        return "Nome do pixel não pode ter mais de 100 caracteres";
    }
    return null;
}

function validateFacebookPixelId($id) {
    if (!empty($id) && !preg_match('/^\d{15,16}$/', $id)) {
        return "ID do Pixel Facebook deve conter 15-16 dígitos";
    }
    return null;
}

function validateGoogleConversionId($id) {
    if (!empty($id) && !preg_match('/^AW-\d+$/', $id)) {
        return "ID de conversão Google deve ter formato AW-123456789";
    }
    return null;
}

function validateTikTokPixelCode($code) {
    if (!empty($code) && !preg_match('/^C4A/', $code)) {
        return "Código do Pixel TikTok deve começar com C4A";
    }
    return null;
}

try {
    $pixelConfig = new PixelConfiguration($conn);
    
    if ($action === 'save_config') {
        error_log("Pixel Save: Processando save_config");
        
        $config_id = $_POST['config_id'] ?? null;
        $pixel_name = trim($_POST['pixel_name'] ?? '');
        $integration_id = !empty($_POST['integration_id']) ? $_POST['integration_id'] : null;
        $consent_mode = $_POST['consent_mode'] ?? 'required';
        
        // Validações de entrada
        $validation_error = validatePixelName($pixel_name);
        if ($validation_error) {
            throw new Exception($validation_error);
        }
        
        // Validar se integration_id existe (se fornecido)
        if ($integration_id) {
            $integrationCheck = $conn->prepare("SELECT id FROM integrations WHERE id = ? AND user_id = ? AND status = 'active'");
            $integrationCheck->execute([$integration_id, $user_data['id']]);
            if (!$integrationCheck->fetch()) {
                throw new Exception('Integração selecionada não encontrada ou inativa');
            }
        }
        
        // Verificar se é atualização ou criação
        if ($config_id) {
            error_log("Pixel Save: Atualizando configuração ID: " . $config_id);
            
            $pixelConfig->id = $config_id;
            if (!$pixelConfig->read()) {
                throw new Exception('Configuração não encontrada');
            }
            
            if ($pixelConfig->user_id != $user_data['id']) {
                throw new Exception('Acesso negado - configuração pertence a outro usuário');
            }
        } else {
            error_log("Pixel Save: Criando nova configuração");
            
            // Verificar se usuário já tem pixel ativo
            $existingPixel = $conn->prepare("SELECT id FROM pixel_configurations WHERE user_id = ? AND status = 'active'");
            $existingPixel->execute([$user_data['id']]);
            if ($existingPixel->fetch()) {
                error_log("Pixel Save: Desativando pixel existente");
                // Desativar pixels existentes
                $conn->prepare("UPDATE pixel_configurations SET status='inactive' WHERE user_id = ?")->execute([$user_data['id']]);
            }
        }
        
        // Configurar dados do pixel
        $pixelConfig->user_id = $user_data['id'];
        $pixelConfig->pixel_name = $pixel_name;
        $pixelConfig->integration_id = $integration_id;
        $pixelConfig->auto_track_pageviews = isset($_POST['auto_track_pageviews']) ? 1 : 0;
        $pixelConfig->auto_track_clicks = isset($_POST['auto_track_clicks']) ? 1 : 0;
        $pixelConfig->consent_mode = $consent_mode;
        $pixelConfig->status = 'testing';
        $pixelConfig->data_retention_days = 365; // Padrão LGPD
        
        // Salvar configuração
        if ($config_id) {
            if ($pixelConfig->update()) {
                error_log("Pixel Save: Configuração atualizada com sucesso");
                $response = [
                    'success' => true, 
                    'message' => '✅ Configuração do pixel atualizada com sucesso!',
                    'pixel_id' => $config_id
                ];
            } else {
                throw new Exception('Erro interno ao atualizar configuração do pixel');
            }
        } else {
            if ($pixelConfig->create()) {
                error_log("Pixel Save: Pixel criado com sucesso, ID: " . $pixelConfig->id);
                $response = [
                    'success' => true, 
                    'message' => '🎉 Pixel criado com sucesso! Agora você pode configurar as integrações.',
                    'pixel_id' => $pixelConfig->id
                ];
            } else {
                throw new Exception('Erro interno ao criar pixel - verifique os dados e tente novamente');
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
        error_log("Pixel Save: Processando save_bridges");
        
        $config_id = $_POST['config_id'] ?? null;
        
        if (!$config_id) {
            throw new Exception('ID da configuração é obrigatório para salvar integrações');
        }
        
        // Carregar configuração existente
        $pixelConfig->id = $config_id;
        if (!$pixelConfig->read() || $pixelConfig->user_id != $user_data['id']) {
            throw new Exception('Configuração não encontrada ou acesso negado');
        }
        
        // Dados das integrações
        $facebook_pixel_id = trim($_POST['facebook_pixel_id'] ?? '');
        $facebook_access_token = trim($_POST['facebook_access_token'] ?? '');
        $google_conversion_id = trim($_POST['google_conversion_id'] ?? '');
        $google_conversion_label = trim($_POST['google_conversion_label'] ?? '');
        $tiktok_pixel_code = trim($_POST['tiktok_pixel_code'] ?? '');
        $tiktok_access_token = trim($_POST['tiktok_access_token'] ?? '');
        
        // Validações específicas
        $validation_errors = [];
        
        if ($facebook_pixel_id) {
            $error = validateFacebookPixelId($facebook_pixel_id);
            if ($error) $validation_errors[] = "Facebook: " . $error;
        }
        
        if ($google_conversion_id) {
            $error = validateGoogleConversionId($google_conversion_id);
            if ($error) $validation_errors[] = "Google: " . $error;
        }
        
        if ($tiktok_pixel_code) {
            $error = validateTikTokPixelCode($tiktok_pixel_code);
            if ($error) $validation_errors[] = "TikTok: " . $error;
        }
        
        // Validar tokens de acesso (se pixel ID fornecido, token é obrigatório)
        if ($facebook_pixel_id && empty($facebook_access_token)) {
            $validation_errors[] = "Facebook: Access Token é obrigatório quando Pixel ID é fornecido";
        }
        
        if ($tiktok_pixel_code && empty($tiktok_access_token)) {
            $validation_errors[] = "TikTok: Access Token é obrigatório quando Pixel Code é fornecido";
        }
        
        if (!empty($validation_errors)) {
            throw new Exception("Erros de validação:\n• " . implode("\n• ", $validation_errors));
        }
        
        // Configurar dados
        $pixelConfig->facebook_pixel_id = !empty($facebook_pixel_id) ? $facebook_pixel_id : null;
        $pixelConfig->facebook_access_token = !empty($facebook_access_token) ? $facebook_access_token : null;
        $pixelConfig->google_conversion_id = !empty($google_conversion_id) ? $google_conversion_id : null;
        $pixelConfig->google_conversion_label = !empty($google_conversion_label) ? $google_conversion_label : null;
        $pixelConfig->tiktok_pixel_code = !empty($tiktok_pixel_code) ? $tiktok_pixel_code : null;
        $pixelConfig->tiktok_access_token = !empty($tiktok_access_token) ? $tiktok_access_token : null;
        
        // Contar integrações configuradas
        $configured_platforms = 0;
        if ($pixelConfig->facebook_pixel_id) $configured_platforms++;
        if ($pixelConfig->google_conversion_id) $configured_platforms++;
        if ($pixelConfig->tiktok_pixel_code) $configured_platforms++;
        
        if ($pixelConfig->update()) {
            error_log("Pixel Save: Bridges atualizados com sucesso, plataformas configuradas: " . $configured_platforms);
            $response = [
                'success' => true, 
                'message' => "🔗 Integrações atualizadas! {$configured_platforms} plataforma(s) configurada(s).",
                'platforms_configured' => $configured_platforms
            ];
        } else {
            throw new Exception('Erro interno ao atualizar integrações - tente novamente');
        }
        
    } else {
        throw new Exception('Ação inválida');
    }
    
} catch (Exception $e) {
    error_log("Pixel Save Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $response = [
        'success' => false, 
        'message' => $e->getMessage(),
        'error_code' => $e->getCode() ?: 'PIXEL_SAVE_ERROR'
    ];
}

// Resposta AJAX (para futuras melhorias)
if (isset($_POST['ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Salvar mensagem na sessão e redirecionar
if ($response['success']) {
    $_SESSION['success_message'] = $response['message'];
    
    // Log de sucesso
    error_log("Pixel Save: Operação concluída com sucesso - " . $action);
    
    // Redirecionar para seção específica se aplicável
    $redirect_url = BASE_URL . '/pixel';
    if (isset($response['pixel_id']) && $action === 'save_config') {
        $redirect_url .= '#config-success';
    } elseif ($action === 'save_bridges') {
        $redirect_url .= '#bridges-success';
    }
    
} else {
    $_SESSION['error_message'] = $response['message'];
    
    // Redirecionar para seção com erro
    $redirect_url = BASE_URL . '/pixel';
    if ($action === 'save_config') {
        $redirect_url .= '#config-error';
    } elseif ($action === 'save_bridges') {
        $redirect_url .= '#bridges-error';
    }
}

header('Location: ' . $redirect_url);
exit;
?>