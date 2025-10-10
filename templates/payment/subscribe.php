<?php
// Garantir que a sessão está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar login corretamente - sistema usa $_SESSION['user'] 
$user_logged = isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
$user_id = $_SESSION['user']['id'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinar Plano Starter - <?= APP_NAME ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Inter, system-ui, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
        }
        .container {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .header h1 {
            color: #1f2937;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .header p {
            color: #6b7280;
            font-size: 1.1rem;
        }
        .plan-card {
            border: 3px solid #667eea;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            background: #f8faff;
        }
        .plan-badge {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: #667eea;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .plan-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
            text-align: center;
        }
        .plan-price {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .price {
            font-size: 3rem;
            font-weight: 800;
            color: #667eea;
        }
        .period {
            color: #6b7280;
            font-size: 1.1rem;
        }
        .features {
            list-style: none;
            margin-bottom: 2rem;
        }
        .features li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
        }
        .features li:before {
            content: '✅';
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        .btn-payment {
            width: 100%;
            background: #667eea;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        .btn-payment:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .btn-payment:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }
        .security {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 1rem;
            color: #6b7280;
            font-size: 0.9rem;
        }
        .security:before {
            content: '🔒';
            margin-right: 0.5rem;
        }
        .loading {
            display: none;
            text-align: center;
            color: #6b7280;
        }
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Assinar Agora</h1>
            <p>Escolha seu plano e comece a otimizar seus resultados</p>
        </div>

        <div id="error-message" class="error"></div>

        <div class="plan-card">
            <div class="plan-badge">Plano Recomendado</div>
            <div class="plan-name">Starter</div>
            <div class="plan-price">
                <span class="price">R$ 79</span>
                <div class="period">/mês</div>
            </div>
            
            <ul class="features">
                <li>UTMs inteligentes ilimitadas</li>
                <li>Relatórios detalhados</li>
                <li>Integrações básicas</li>
                <li>Suporte por email</li>
                <li>Dashboard completo</li>
                <li>Pixel BR básico</li>
            </ul>

            <?php if ($user_logged): ?>
                <a href="https://www.mercadopago.com.br/subscriptions/checkout?preapproval_plan_id=036da8ea54c846258aa0e177a87cecfc" 
                   class="btn-payment" target="_blank">
                    Assinar por R$ 79/mês
                </a>
            <?php else: ?>
                <button id="payment-btn" class="btn-payment" disabled>
                    Faça login para assinar
                </button>
            <?php endif; ?>
            
            <div class="loading" id="loading" style="display: none;">
                🔄 Redirecionando para MercadoPago...
            </div>
            
            <div class="security">
                Pagamento seguro via MercadoPago
            </div>
        </div>
    </div>

    <script>
        // Verificar se usuário está logado
        <?php if (!$user_logged): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const errorDiv = document.getElementById('error-message');
            
            // Mostrar mensagem para login
            errorDiv.innerHTML = `
                <strong>⚠️ Login necessário</strong><br>
                Você precisa estar logado para assinar. 
                <a href="/login?redirect=${encodeURIComponent(window.location.pathname)}" 
                   style="color: #dc2626; font-weight: 600; text-decoration: underline;">
                   Clique aqui para fazer login
                </a>
            `;
            errorDiv.style.display = 'block';
        });
        <?php endif; ?>
        
        // Analytics - track subscription click
        document.addEventListener('DOMContentLoaded', function() {
            const subscribeBtn = document.querySelector('.btn-payment');
            if (subscribeBtn && subscribeBtn.href) {
                subscribeBtn.addEventListener('click', function() {
                    console.log('Subscription started:', {
                        plan: 'starter',
                        amount: 79.00,
                        timestamp: new Date().toISOString()
                    });
                    
                    // Show loading message
                    const loading = document.getElementById('loading');
                    loading.style.display = 'block';
                });
            }
        });
    </script>
</body>
</html>