-- Script para criar usuário de teste com plano Scale para Link Maestro
-- Execute este script no banco de dados mercado_afiliado

-- 1. Inserir usuário de teste Scale
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
    'Usuário Teste Scale',
    'teste.scale@mercadoafiliado.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password
    'active',
    1,
    NOW(),
    NOW(),
    NOW()
);

-- 2. Obter o ID do usuário criado
SET @user_id = LAST_INSERT_ID();

-- 3. Verificar se o plano Scale existe, se não, criar
INSERT IGNORE INTO subscription_plans (
    id,
    name,
    slug,
    description,
    price,
    billing_cycle,
    features_json,
    limits_json,
    status,
    created_at,
    updated_at
) VALUES (
    3,
    'Scale',
    'scale',
    'Plano Scale com recursos ilimitados e white label',
    299.00,
    'monthly',
    '["link_maestro", "utm_templates", "advanced_analytics", "click_tracking", "pixel_br", "whatsapp_alerts", "custom_domains", "team_management", "white_label"]',
    '{"short_links": -1, "utm_templates": -1, "clicks_per_month": -1, "analytics_retention_days": 730}',
    'active',
    NOW(),
    NOW()
);

-- 4. Criar assinatura Scale para o usuário de teste
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
    3, -- ID do plano Scale
    'active', -- Status ativo
    NULL, -- Sem trial
    DATE_ADD(NOW(), INTERVAL 1 MONTH), -- Próxima cobrança em 1 mês
    NOW(),
    NOW()
);

-- 5. Exibir informações do usuário Scale criado
SELECT 
    '=== USUÁRIO SCALE CRIADO COM SUCESSO ===' as status;

SELECT 
    u.id as user_id,
    u.name as nome,
    u.email as email,
    'password' as senha_texto,
    sp.name as plano,
    sp.slug as plano_slug,
    us.status as status_assinatura
FROM users u
JOIN user_subscriptions us ON u.id = us.user_id
JOIN subscription_plans sp ON us.plan_id = sp.id
WHERE u.id = @user_id;

SELECT 
    '=== DADOS PARA LOGIN SCALE ===' as info;

SELECT 
    'teste.scale@mercadoafiliado.com' as email,
    'password' as senha,
    'Plano Scale Ativo - Recursos Ilimitados' as observacao;