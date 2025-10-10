<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/models/User.php';
header('Content-Type: application/json');

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Buscar usuário
    $query = "SELECT id, email, email_verified, name FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
        exit;
    }

    // Se já verificado
    if ($user['email_verified']) {
        echo json_encode(['success' => false, 'message' => 'E-mail já verificado']);
        exit;
    }

    // Gerar novo token
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Atualizar token no banco
    $update_query = "UPDATE users
                     SET verification_token = :token,
                         token_expires_at = :expires_at
                     WHERE id = :user_id";

    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':token', $token);
    $update_stmt->bindParam(':expires_at', $expires_at);
    $update_stmt->bindParam(':user_id', $user['id']);
    $update_stmt->execute();

    // Enviar e-mail
    $verification_link = BASE_URL . '/api/verify-email.php?token=' . $token;
    $user_name = $user['name'];

    // Capturar template de e-mail
    ob_start();
    include __DIR__ . '/../templates/emails/verify-email.php';
    $email_body = ob_get_clean();

    // Configurar e enviar e-mail
    $to = $user['email'];
    $subject = 'Verifique seu e-mail - Mercado Afiliado';
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: Mercado Afiliado <noreply@mercadoafiliado.com.br>',
        'Reply-To: contato@mercadoafiliado.com.br'
    ];

    $mail_sent = mail($to, $subject, $email_body, implode("\r\n", $headers));

    if ($mail_sent) {
        echo json_encode([
            'success' => true,
            'message' => 'E-mail de verificação reenviado com sucesso!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao enviar e-mail. Tente novamente.'
        ]);
    }

} catch (PDOException $e) {
    error_log('Erro ao reenviar verificação: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro no servidor. Tente novamente.'
    ]);
}
