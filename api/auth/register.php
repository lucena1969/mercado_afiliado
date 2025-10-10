<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/models/Subscription.php';

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/register');
    exit;
}

// Validar dados
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';
$plan_id = $_POST['plan_id'] ?? 1; // Padrão: starter

// Validações
$errors = [];

if (empty($name)) {
    $errors[] = 'Nome é obrigatório';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'E-mail inválido';
}

if (empty($password) || strlen($password) < 6) {
    $errors[] = 'Senha deve ter no mínimo 6 caracteres';
}

if ($password !== $password_confirm) {
    $errors[] = 'As senhas não conferem';
}

if (!empty($errors)) {
    $_SESSION['error_message'] = implode('. ', $errors);
    header('Location: ' . BASE_URL . '/register');
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    // Verificar se e-mail já existe
    if ($user->emailExists($email)) {
        $_SESSION['error_message'] = 'Este e-mail já está cadastrado';
        header('Location: ' . BASE_URL . '/register');
        exit;
    }

    // Criar usuário (verificação de email DESABILITADA)
    $user->name = $name;
    $user->email = $email;
    $user->phone = $phone;
    $user->password = password_hash($password, PASSWORD_DEFAULT);
    $user->email_verified = true; // Auto-verificado

    if ($user->create()) {
        $user_id = $user->id;

        // Criar trial subscription (14 dias)
        $subscription = new Subscription($db);
        $trial_ends = date('Y-m-d H:i:s', strtotime('+14 days'));

        $subscription->user_id = $user_id;
        $subscription->plan_id = $plan_id;
        $subscription->status = 'trial';
        $subscription->trial_ends_at = $trial_ends;
        $subscription->create();

        // Fazer login automático e redirecionar para dashboard
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['logged_in'] = true;

        // Redirecionar para dashboard
        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    } else {
        $_SESSION['error_message'] = 'Erro ao criar conta. Tente novamente.';
        header('Location: ' . BASE_URL . '/register');
        exit;
    }

} catch (PDOException $e) {
    error_log('Erro no registro: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Erro ao criar conta. Tente novamente.';
    header('Location: ' . BASE_URL . '/register');
    exit;
}
