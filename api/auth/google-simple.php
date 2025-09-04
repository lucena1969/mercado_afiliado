<?php
/**
 * OAuth Google SIMPLES - Sem dependências externas
 * Mercado Afiliado
 */

require_once '../../config/app.php';

// Verificar se configurações estão definidas
if (empty(GOOGLE_CLIENT_ID) || empty(GOOGLE_CLIENT_SECRET)) {
    die('Erro: Credenciais Google não configuradas');
}

$action = $_GET['action'] ?? 'login';

switch ($action) {
    case 'login':
        // Redirecionar para Google
        googleRedirect();
        break;
        
    case 'callback':
        // Processar retorno do Google
        handleGoogleCallback();
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Ação inválida']);
}

/**
 * Redirecionar para autorização Google
 */
function googleRedirect() {
    // Gerar state para segurança
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    
    // Parâmetros da URL do Google
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => BASE_URL . '/api/auth/google-simple.php?action=callback',
        'response_type' => 'code',
        'scope' => 'openid profile email',
        'state' => $state,
        'access_type' => 'offline',
        'prompt' => 'consent'
    ];
    
    $google_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    
    header('Location: ' . $google_url);
    exit;
}

/**
 * Processar callback do Google
 */
function handleGoogleCallback() {
    // Verificar state para segurança
    $state = $_GET['state'] ?? '';
    if (empty($state) || $state !== ($_SESSION['oauth_state'] ?? '')) {
        $_SESSION['error_message'] = 'Erro de segurança OAuth';
        header('Location: ' . BASE_URL . '/templates/auth/login.php');
        exit;
    }
    
    // Limpar state
    unset($_SESSION['oauth_state']);
    
    // Verificar se recebeu o code
    $code = $_GET['code'] ?? '';
    if (empty($code)) {
        $_SESSION['error_message'] = 'Autorização negada pelo usuário';
        header('Location: ' . BASE_URL . '/templates/auth/login.php');
        exit;
    }
    
    // Trocar code por access token
    $token_data = getAccessToken($code);
    if (!$token_data) {
        $_SESSION['error_message'] = 'Erro ao obter token de acesso';
        header('Location: ' . BASE_URL . '/templates/auth/login.php');
        exit;
    }
    
    // Obter dados do usuário
    $user_data = getUserInfo($token_data['access_token']);
    if (!$user_data) {
        $_SESSION['error_message'] = 'Erro ao obter dados do usuário';
        header('Location: ' . BASE_URL . '/templates/auth/login.php');
        exit;
    }
    
    // Processar login/registro
    processUserLogin($user_data);
}

/**
 * Trocar authorization code por access token
 */
function getAccessToken($code) {
    $token_url = 'https://oauth2.googleapis.com/token';
    
    $post_data = [
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => BASE_URL . '/api/auth/google-simple.php?action=callback',
        'grant_type' => 'authorization_code',
        'code' => $code
    ];
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $token_url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($post_data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($http_code !== 200) {
        error_log("Erro ao obter token: HTTP $http_code - $response");
        return false;
    }
    
    return json_decode($response, true);
}

/**
 * Obter informações do usuário do Google
 */
function getUserInfo($access_token) {
    $user_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $user_url,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $access_token,
            'Accept: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($http_code !== 200) {
        error_log("Erro ao obter dados do usuário: HTTP $http_code - $response");
        return false;
    }
    
    return json_decode($response, true);
}

/**
 * Processar login/registro do usuário
 */
function processUserLogin($user_data) {
    // Incluir classe User se disponível
    if (class_exists('User')) {
        $userModel = new User();
        
        // Verificar se usuário já existe
        $existing_user = $userModel->findByEmail($user_data['email']);
        
        if ($existing_user) {
            // Usuário existe, fazer login
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $existing_user['id'];
            $_SESSION['user_email'] = $existing_user['email'];
            $_SESSION['user_name'] = $existing_user['name'];
        } else {
            // Criar novo usuário
            $user_id = $userModel->createFromGoogle([
                'name' => $user_data['name'],
                'email' => $user_data['email'],
                'google_id' => $user_data['id'],
                'avatar' => $user_data['picture'] ?? null
            ]);
            
            if ($user_id) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_email'] = $user_data['email'];
                $_SESSION['user_name'] = $user_data['name'];
            } else {
                $_SESSION['error_message'] = 'Erro ao criar conta';
                header('Location: ' . BASE_URL . '/templates/auth/login.php');
                exit;
            }
        }
    } else {
        // Fallback simples sem banco de dados
        $_SESSION['logged_in'] = true;
        $_SESSION['user_email'] = $user_data['email'];
        $_SESSION['user_name'] = $user_data['name'];
        $_SESSION['google_user'] = true;
    }
    
    // Sucesso - redirecionar para dashboard
    $_SESSION['success_message'] = 'Login realizado com sucesso!';
    header('Location: ' . BASE_URL . '/dashboard');
    exit;
}
?>