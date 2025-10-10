<?php
require_once __DIR__ . '/../config/app.php';

// Pegar token da URL
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)) {
    $_SESSION['error_message'] = 'Token de verificação inválido.';
    header('Location: ' . BASE_URL . '/login');
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Buscar usuário pelo token
    $query = "SELECT id, email, email_verified, token_expires_at, name
              FROM users
              WHERE verification_token = :token";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Token não encontrado
    if (!$user) {
        $_SESSION['error_message'] = 'Token de verificação inválido ou já utilizado.';
        header('Location: ' . BASE_URL . '/login');
        exit;
    }

    // Já verificado
    if ($user['email_verified']) {
        $_SESSION['success_message'] = 'Seu e-mail já foi verificado anteriormente!';
        header('Location: ' . BASE_URL . '/login');
        exit;
    }

    // Token expirado
    if (strtotime($user['token_expires_at']) < time()) {
        $_SESSION['error_message'] = 'Token expirado. Solicite um novo e-mail de verificação.';

        // Fazer login automático para pedir reenvio
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        header('Location: ' . BASE_URL . '/verify-email');
        exit;
    }

    // Verificar e-mail
    $update_query = "UPDATE users
                     SET email_verified = TRUE,
                         verification_token = NULL,
                         token_expires_at = NULL,
                         updated_at = NOW()
                     WHERE id = :user_id";

    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':user_id', $user['id']);
    $update_stmt->execute();

    // Fazer login automático
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['success_message'] = '✅ E-mail verificado com sucesso! Bem-vindo ao Mercado Afiliado.';

    // Redirecionar para dashboard
    header('Location: ' . BASE_URL . '/dashboard');
    exit;

} catch (PDOException $e) {
    error_log('Erro ao verificar e-mail: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Erro ao verificar e-mail. Tente novamente.';
    header('Location: ' . BASE_URL . '/login');
    exit;
}
