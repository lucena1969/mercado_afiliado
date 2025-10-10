<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Processado - <?= APP_NAME ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Inter, system-ui, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .icon {
            width: 80px;
            height: 80px;
            background: #10b981;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
        }
        .icon.pending {
            background: #f59e0b;
        }
        .icon.error {
            background: #ef4444;
        }
        h1 {
            color: #1f2937;
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }
        p {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #5a6fd8;
        }
        .details {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 10px;
            margin: 1.5rem 0;
            text-align: left;
        }
        .details strong {
            color: #1f2937;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
        // Processar status do pagamento baseado nos parâmetros do MercadoPago
        $status = $_GET['status'] ?? 'pending';
        $payment_id = $_GET['payment_id'] ?? '';
        $preference_id = $_GET['preference_id'] ?? '';
        
        switch($status) {
            case 'approved':
                $icon_class = '';
                $icon_symbol = '✅';
                $title = 'Pagamento Aprovado!';
                $message = 'Sua assinatura foi ativada com sucesso. Você já pode acessar todos os recursos do Mercado Afiliado.';
                break;
                
            case 'pending':
                $icon_class = 'pending';
                $icon_symbol = '⏳';
                $title = 'Pagamento Pendente';
                $message = 'Seu pagamento está sendo processado. Você receberá uma confirmação por email assim que for aprovado.';
                break;
                
            case 'rejected':
            case 'failure':
                $icon_class = 'error';
                $icon_symbol = '❌';
                $title = 'Pagamento Não Aprovado';
                $message = 'Houve um problema com seu pagamento. Tente novamente ou entre em contato conosco.';
                break;
                
            default:
                $icon_class = 'pending';
                $icon_symbol = '🔍';
                $title = 'Processando Pagamento';
                $message = 'Estamos verificando o status do seu pagamento. Aguarde alguns instantes.';
        }
        ?>
        
        <div class="icon <?= $icon_class ?>"><?= $icon_symbol ?></div>
        
        <h1><?= $title ?></h1>
        <p><?= $message ?></p>
        
        <?php if ($payment_id || $preference_id): ?>
        <div class="details">
            <?php if ($payment_id): ?>
                <p><strong>ID do Pagamento:</strong> <?= htmlspecialchars($payment_id) ?></p>
            <?php endif; ?>
            <?php if ($preference_id): ?>
                <p><strong>ID da Preferência:</strong> <?= htmlspecialchars($preference_id) ?></p>
            <?php endif; ?>
            <p><strong>Data:</strong> <?= date('d/m/Y H:i') ?></p>
        </div>
        <?php endif; ?>
        
        <a href="/dashboard" class="btn">Ir para o Dashboard</a>
        
        <?php if ($status === 'rejected' || $status === 'failure'): ?>
            <br><br>
            <a href="/subscribe" class="btn" style="background: #10b981;">Tentar Novamente</a>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh para pagamentos pendentes
        <?php if ($status === 'pending'): ?>
        setTimeout(function() {
            location.reload();
        }, 10000); // Reload a cada 10 segundos
        <?php endif; ?>
        
        // Log do evento para analytics
        console.log('Payment status: <?= $status ?>', {
            payment_id: '<?= $payment_id ?>',
            preference_id: '<?= $preference_id ?>',
            timestamp: new Date().toISOString()
        });
    </script>
</body>
</html>