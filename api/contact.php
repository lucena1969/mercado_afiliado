<?php
/**
 * API endpoint para processar formulário de contato
 * Mercado Afiliado
 */

// Headers para CORS e JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

// Incluir configurações se disponível
if (file_exists('../config/app.php')) {
    require_once '../config/app.php';
}

// Configurações SMTP
$smtp_config = [
    'host' => defined('SMTP_HOST') ? SMTP_HOST : 'smtp.hostinger.com',
    'port' => defined('SMTP_PORT') ? SMTP_PORT : 587,
    'username' => defined('SMTP_USERNAME') ? SMTP_USERNAME : 'contato@mercadoafiliado.com.br',
    'password' => defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '', // PREENCHER NO CONFIG/APP.PHP
    'from_email' => defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'contato@mercadoafiliado.com.br',
    'from_name' => defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Mercado Afiliado - Contato',
    'to_email' => defined('CONTACT_EMAIL') ? CONTACT_EMAIL : 'contato@mercadoafiliado.com.br'
];

try {
    // Pegar dados do formulário
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        // Fallback para form-data
        $input = $_POST;
    }
    
    // Validação básica
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $subject = trim($input['subject'] ?? 'Contato pelo site');
    $message = trim($input['message'] ?? '');
    
    // Validações
    if (empty($name) || empty($email) || empty($message)) {
        throw new Exception('Nome, e-mail e mensagem são obrigatórios.');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('E-mail inválido.');
    }
    
    if (strlen($message) < 10) {
        throw new Exception('Mensagem muito curta (mínimo 10 caracteres).');
    }
    
    // Sanitizar dados
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    
    // Verificar se PHPMailer está disponível
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Usar PHPMailer
        $success = sendEmailWithPHPMailer($smtp_config, $name, $email, $subject, $message);
    } else {
        // Fallback para mail() nativo
        $success = sendEmailWithNativePHP($name, $email, $subject, $message, $smtp_config['to_email']);
    }
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Mensagem enviada com sucesso! Retornaremos em breve.'
        ]);
    } else {
        throw new Exception('Erro ao enviar mensagem. Tente novamente.');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Enviar email usando PHPMailer
 */
function sendEmailWithPHPMailer($config, $name, $email, $subject, $message) {
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Configurações do servidor
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config['port'];
        $mail->CharSet = 'UTF-8';
        
        // Remetente
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addReplyTo($email, $name);
        
        // Destinatário
        $mail->addAddress($config['to_email']);
        
        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = '[CONTATO] ' . $subject;
        
        $html_message = "
        <h2>Nova mensagem de contato</h2>
        <p><strong>Nome:</strong> {$name}</p>
        <p><strong>E-mail:</strong> {$email}</p>
        <p><strong>Assunto:</strong> {$subject}</p>
        <p><strong>Mensagem:</strong></p>
        <div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>
            " . nl2br($message) . "
        </div>
        <hr>
        <p><small>Enviado via formulário de contato do site em " . date('d/m/Y H:i:s') . "</small></p>
        ";
        
        $mail->Body = $html_message;
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $html_message));
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Erro PHPMailer: " . $e->getMessage());
        return false;
    }
}

/**
 * Enviar email usando função mail() nativa (fallback)
 */
function sendEmailWithNativePHP($name, $email, $subject, $message, $to_email) {
    $headers = [
        'From: Mercado Afiliado <contato@mercadoafiliado.com.br>',
        'Reply-To: ' . $email,
        'Content-Type: text/html; charset=UTF-8',
        'MIME-Version: 1.0',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $email_subject = '[CONTATO] ' . $subject;
    
    $html_message = "
    <h2>Nova mensagem de contato</h2>
    <p><strong>Nome:</strong> {$name}</p>
    <p><strong>E-mail:</strong> {$email}</p>
    <p><strong>Assunto:</strong> {$subject}</p>
    <p><strong>Mensagem:</strong></p>
    <div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>
        " . nl2br($message) . "
    </div>
    <hr>
    <p><small>Enviado via formulário de contato do site em " . date('d/m/Y H:i:s') . "</small></p>
    ";
    
    return mail($to_email, $email_subject, $html_message, implode("\r\n", $headers));
}
?>