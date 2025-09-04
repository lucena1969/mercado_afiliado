<?php
/**
 * OAuth Google ULTRA SIMPLES - Versão mínima funcional
 * Mercado Afiliado
 */

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações hardcoded
define('GOOGLE_CLIENT_ID', '41618611981-h8rrgi15kailmmdhh1pgcp7e97bmfue3.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('BASE_URL', 'https://mercadoafiliado.com.br');

$action = $_GET['action'] ?? 'login';

if ($action === 'login') {
    // STEP 1: Redirecionar para Google
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => BASE_URL . '/api/auth/google-ultra-simple.php?action=callback',
        'response_type' => 'code',
        'scope' => 'openid profile email',
        'state' => $state
    ];
    
    $google_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    header('Location: ' . $google_url);
    exit;
}

if ($action === 'callback') {
    // STEP 2: Processar callback
    
    // Verificar state
    $state = $_GET['state'] ?? '';
    if (empty($state) || $state !== ($_SESSION['oauth_state'] ?? '')) {
        die('Erro de segurança OAuth');
    }
    
    // Verificar code
    $code = $_GET['code'] ?? '';
    if (empty($code)) {
        die('Autorização negada');
    }
    
    // STEP 3: Trocar code por token
    $token_url = 'https://oauth2.googleapis.com/token';
    $post_data = [
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => BASE_URL . '/api/auth/google-ultra-simple.php?action=callback',
        'grant_type' => 'authorization_code',
        'code' => $code
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $token_data = json_decode($response, true);
    
    if (!isset($token_data['access_token'])) {
        die('Erro ao obter token: ' . $response);
    }
    
    // STEP 4: Obter dados do usuário
    $user_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $user_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token_data['access_token']]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $user_response = curl_exec($ch);
    curl_close($ch);
    
    $user_data = json_decode($user_response, true);
    
    if (!isset($user_data['email'])) {
        die('Erro ao obter dados do usuário: ' . $user_response);
    }
    
    // STEP 5: Login simples (só sessões)
    $_SESSION['logged_in'] = true;
    $_SESSION['user_email'] = $user_data['email'];
    $_SESSION['user_name'] = $user_data['name'];
    $_SESSION['user_id'] = 'google_' . $user_data['id'];
    $_SESSION['google_user'] = true;
    
    // Criar array 'user' para compatibilidade com dashboard
    $_SESSION['user'] = [
        'id' => 'google_' . $user_data['id'],
        'name' => $user_data['name'],
        'email' => $user_data['email'],
        'avatar' => $user_data['picture'] ?? null,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // STEP 6: Redirecionar para página de sucesso
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Login realizado com sucesso</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .success { background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin: 20px auto; max-width: 500px; }
        </style>
    </head>
    <body>
        <div class="success">
            <h2>✅ Login realizado com sucesso!</h2>
            <p>Bem-vindo(a), <?= htmlspecialchars($user_data['name']) ?>!</p>
            <p>E-mail: <?= htmlspecialchars($user_data['email']) ?></p>
            <p><a href="<?= BASE_URL ?>/dashboard-simple.php">Ir para Dashboard</a></p>
            <p><a href="<?= BASE_URL ?>">Voltar ao site</a></p>
        </div>
        
        <script>
            // Auto-redirecionar após 3 segundos
            setTimeout(function() {
                window.location.href = '<?= BASE_URL ?>/dashboard-simple.php';
            }, 3000);
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Ação inválida
die('Ação inválida: ' . htmlspecialchars($action));
?>