<?php
require_once __DIR__ . '/../../config/app.php';

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login');
    exit;
}

// Buscar dados do usuário
$database = new Database();
$db = $database->getConnection();

$query = "SELECT email, email_verified, name FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Se já está verificado, redireciona para dashboard
if ($user['email_verified']) {
    header('Location: ' . BASE_URL . '/dashboard');
    exit;
}

$user_name = explode(' ', $user['name'])[0];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifique seu E-mail - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #fcfbf7 0%, #f9fafb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .verify-container {
            max-width: 500px;
            width: 100%;
            background: #fff;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            text-align: center;
        }
        .icon-box {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #e7b73b, #b38609);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }
        h1 {
            color: #1f2937;
            font-size: 24px;
            margin: 0 0 12px;
            font-weight: 800;
        }
        p {
            color: #6b7280;
            line-height: 1.6;
            margin: 0 0 24px;
        }
        .email-display {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            margin: 20px 0;
            font-weight: 600;
            color: #1f2937;
        }
        .btn {
            display: inline-block;
            background: #2563eb;
            color: #fff;
            padding: 14px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background 0.2s ease;
            font-size: 16px;
        }
        .btn:hover {
            background: #1d4ed8;
        }
        .btn-secondary {
            background: transparent;
            color: #6b7280;
            border: 1px solid #e5e7eb;
            margin-left: 12px;
        }
        .btn-secondary:hover {
            background: #f9fafb;
            color: #1f2937;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin: 16px 0;
            font-size: 14px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-info {
            background: #fff4d6;
            color: #5b4a11;
            border: 1px solid #f1e4b3;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="icon-box">📧</div>

        <h1>Verifique seu E-mail</h1>
        <p>Olá, <strong><?= htmlspecialchars($user_name) ?></strong>! Enviamos um e-mail de verificação para:</p>

        <div class="email-display">
            <?= htmlspecialchars($user['email']) ?>
        </div>

        <div class="alert alert-info">
            <strong>📬 Verifique sua caixa de entrada</strong><br>
            Clique no link que enviamos para ativar sua conta.
        </div>

        <div id="message-container"></div>

        <div style="margin-top: 28px;">
            <button class="btn" onclick="resendEmail()" id="resend-btn">
                Reenviar E-mail
            </button>
            <a href="<?= BASE_URL ?>/logout" class="btn btn-secondary">Sair</a>
        </div>

        <p style="margin-top: 24px; font-size: 14px; color: #9ca3af;">
            Não recebeu o e-mail? Verifique sua pasta de spam ou lixo eletrônico.
        </p>
    </div>

    <script>
        function resendEmail() {
            const btn = document.getElementById('resend-btn');
            const messageContainer = document.getElementById('message-container');

            btn.disabled = true;
            btn.textContent = 'Enviando...';

            fetch('<?= BASE_URL ?>/api/resend-verification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageContainer.innerHTML = '<div class="alert alert-success">✅ E-mail reenviado com sucesso!</div>';

                    // Countdown de 60 segundos
                    let countdown = 60;
                    btn.textContent = `Aguarde ${countdown}s`;

                    const interval = setInterval(() => {
                        countdown--;
                        btn.textContent = `Aguarde ${countdown}s`;

                        if (countdown <= 0) {
                            clearInterval(interval);
                            btn.disabled = false;
                            btn.textContent = 'Reenviar E-mail';
                        }
                    }, 1000);
                } else {
                    messageContainer.innerHTML = '<div class="alert alert-danger">❌ ' + data.message + '</div>';
                    btn.disabled = false;
                    btn.textContent = 'Reenviar E-mail';
                }
            })
            .catch(error => {
                messageContainer.innerHTML = '<div class="alert alert-danger">❌ Erro ao reenviar e-mail.</div>';
                btn.disabled = false;
                btn.textContent = 'Reenviar E-mail';
            });
        }
    </script>
</body>
</html>
