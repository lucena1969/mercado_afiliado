-- Script para criar usuário de teste em TRIAL para Link Maestro
-- Execute este script no banco de dados mercado_afiliado

-- 1. Inserir usuário de teste em trial
INSERT INTO users (
    name, 
    email, 
    password, 
    status, 
    email_verified, 
    email_verified_at, 
    created_at, 
    updated_at
) VALUES (
    'Usuário Teste Trial',
    'teste.trial@mercadoafiliado.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password
    'active',
    1,
    NOW(),
    NOW(),
    NOW()
);

-- 2. Obter o ID do usuário criado
SET @user_id = LAST_INSERT_ID();

-- 3. Criar assinatura Trial (7 dias) para o usuário de teste
INSERT INTO user_subscriptions (
    user_id,
    plan_id,
    status,
    trial_ends_at,
    next_billing_date,
    created_at,
    updated_at
) VALUES (
    @user_id,
    2, -- ID do plano Pro (trial do Pro)
    'trial', -- Status trial
    DATE_ADD(NOW(), INTERVAL 3 DAY), -- Trial expira em 3 dias (para testar avisos)
    DATE_ADD(NOW(), INTERVAL 3 DAY), -- Data da cobrança após trial
    NOW(),
    NOW()
);

-- 4. Exibir informações do usuário Trial criado
SELECT 
    '=== USUÁRIO TRIAL CRIADO COM SUCESSO ===' as status;

SELECT 
    u.id as user_id,
    u.name as nome,
    u.email as email,
    'password' as senha_texto,
    sp.name as plano,
    sp.slug as plano_slug,
    us.status as status_assinatura,
    us.trial_ends_at as trial_expira_em,
    DATEDIFF(us.trial_ends_at, NOW()) as dias_restantes
FROM users u
JOIN user_subscriptions us ON u.id = us.user_id
JOIN subscription_plans sp ON us.plan_id = sp.id
WHERE u.id = @user_id;

SELECT 
    '=== DADOS PARA LOGIN TRIAL ===' as info;

SELECT 
    'teste.trial@mercadoafiliado.com' as email,
    'password' as senha,
    'Trial Pro - Expira em 3 dias (para testar avisos)' as observacao;