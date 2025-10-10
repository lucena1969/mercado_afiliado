<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifique seu e-mail - Mercado Afiliado</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f9fafb;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #b38609, #e7b73b);
            padding: 40px 30px;
            text-align: center;
            color: #fff;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
        }
        .content {
            padding: 40px 30px;
        }
        .content h2 {
            color: #1f2937;
            font-size: 20px;
            margin: 0 0 16px;
        }
        .content p {
            color: #6b7280;
            line-height: 1.6;
            margin: 0 0 20px;
        }
        .btn {
            display: inline-block;
            background: #2563eb;
            color: #fff;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 20px 0;
        }
        .btn:hover {
            background: #1d4ed8;
        }
        .info-box {
            background: #fff4d6;
            border: 1px solid #f1e4b3;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
        }
        .info-box p {
            margin: 0;
            color: #5b4a11;
            font-size: 14px;
        }
        .footer {
            background: #f9fafb;
            padding: 24px 30px;
            text-align: center;
            color: #9ca3af;
            font-size: 14px;
        }
        .footer a {
            color: #2563eb;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 Mercado Afiliado</h1>
        </div>

        <div class="content">
            <h2>Olá, <?= htmlspecialchars($user_name) ?>!</h2>
            <p>Bem-vindo ao <strong>Mercado Afiliado</strong>! Estamos felizes em tê-lo conosco.</p>
            <p>Para começar a usar a plataforma e acessar todas as funcionalidades, você precisa verificar seu endereço de e-mail.</p>

            <div style="text-align: center;">
                <a href="<?= $verification_link ?>" class="btn">Verificar Meu E-mail</a>
            </div>

            <div class="info-box">
                <p><strong>⏰ Este link expira em 24 horas.</strong></p>
                <p>Se você não solicitou esta verificação, ignore este e-mail.</p>
            </div>

            <p style="font-size: 14px; color: #9ca3af; margin-top: 24px;">
                Se o botão não funcionar, copie e cole este link no seu navegador:<br>
                <a href="<?= $verification_link ?>" style="color: #2563eb; word-break: break-all;"><?= $verification_link ?></a>
            </p>
        </div>

        <div class="footer">
            <p>Este e-mail foi enviado por <strong>Mercado Afiliado</strong></p>
            <p>QE 40, lote 8, sala 402, Brasília/DF | (61) 99916-3260</p>
            <p><a href="<?= BASE_URL ?>">mercadoafiliado.com.br</a></p>
        </div>
    </div>
</body>
</html>
