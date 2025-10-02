-- Script para criar usuário de teste com plano Pro para Link Maestro
-- Execute este script no banco de dados mercado_afiliado

-- 1. Inserir usuário de teste
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
    'Usuário Teste Pro',
    'teste.pro@mercadoafiliado.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password
    'active',
    1,
    NOW(),
    NOW(),
    NOW()
);

-- 2. Obter o ID do usuário criado
SET @user_id = LAST_INSERT_ID();

-- 3. Verificar se o plano Pro existe, se não, criar
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
    2,
    'Pro',
    'pro',
    'Plano Pro com Link Maestro e recursos avançados',
    149.00,
    'monthly',
    '["link_maestro", "utm_templates", "advanced_analytics", "click_tracking", "pixel_br", "whatsapp_alerts"]',
    '{"short_links": 1000, "utm_templates": 50, "clicks_per_month": 50000, "analytics_retention_days": 365}',
    'active',
    NOW(),
    NOW()
);

-- 4. Criar assinatura Pro para o usuário de teste
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
    2, -- ID do plano Pro
    'active', -- Status ativo (não trial)
    NULL, -- Sem trial
    DATE_ADD(NOW(), INTERVAL 1 MONTH), -- Próxima cobrança em 1 mês
    NOW(),
    NOW()
);

-- 5. Criar alguns dados de exemplo para teste

-- Template UTM de exemplo
INSERT INTO utm_templates (
    user_id,
    name,
    platform,
    description,
    utm_source,
    utm_medium,
    utm_campaign,
    utm_content,
    utm_term,
    status,
    is_default,
    usage_count,
    created_at,
    updated_at
) VALUES 
(
    @user_id,
    'Facebook Ads - Padrão',
    'facebook',
    'Template padrão para campanhas do Facebook Ads',
    'facebook',
    'cpc',
    '{{campaign_name}}',
    '{{ad_name}}',
    '{{target_audience}}',
    'active',
    0,
    0,
    NOW(),
    NOW()
),
(
    @user_id,
    'Google Ads - Search',
    'google',
    'Template para campanhas de busca no Google Ads',
    'google',
    'cpc',
    '{{campaign_name}}',
    '{{ad_group}}',
    '{{keyword}}',
    'active',
    0,
    0,
    NOW(),
    NOW()
);

-- Link de exemplo
INSERT INTO short_links (
    user_id,
    utm_template_id,
    short_code,
    original_url,
    final_url,
    title,
    description,
    campaign_name,
    utm_source,
    utm_medium,
    utm_campaign,
    utm_content,
    status,
    click_count,
    created_at,
    updated_at
) VALUES (
    @user_id,
    NULL,
    'teste001',
    'https://hotmart.com/pt-br/marketplace/produtos/exemplo-produto',
    'https://hotmart.com/pt-br/marketplace/produtos/exemplo-produto?utm_source=teste&utm_medium=social&utm_campaign=lancamento',
    'Link de Teste - Produto Exemplo',
    'Link criado para testar o Link Maestro',
    'Campanha de Lançamento',
    'teste',
    'social',
    'lancamento',
    'post_instagram',
    'active',
    5,
    NOW(),
    NOW()
);

-- Alguns cliques de exemplo no link
INSERT INTO link_clicks (
    short_link_id,
    user_id,
    ip_address,
    user_agent,
    referer,
    country,
    device_type,
    browser,
    os,
    utm_source,
    utm_medium,
    utm_campaign,
    utm_content,
    click_timestamp,
    is_unique
) VALUES 
(
    LAST_INSERT_ID(),
    @user_id,
    '192.168.1.100',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'https://instagram.com',
    'BR',
    'desktop',
    'Chrome',
    'Windows',
    'teste',
    'social',
    'lancamento',
    'post_instagram',
    DATE_SUB(NOW(), INTERVAL 2 DAY),
    1
),
(
    LAST_INSERT_ID(),
    @user_id,
    '192.168.1.101',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)',
    'https://instagram.com',
    'BR',
    'mobile',
    'Safari',
    'iOS',
    'teste',
    'social',
    'lancamento',
    'post_instagram',
    DATE_SUB(NOW(), INTERVAL 1 DAY),
    1
);

-- 6. Exibir informações do usuário criado
SELECT 
    '=== USUÁRIO DE TESTE CRIADO COM SUCESSO ===' as status;

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
    '=== DADOS PARA LOGIN ===' as info;

SELECT 
    'teste.pro@mercadoafiliado.com' as email,
    'password' as senha,
    'Plano Pro Ativo' as observacao;

-- Verificar se tudo foi criado corretamente
SELECT 
    '=== RECURSOS DISPONÍVEIS ===' as recursos;

SELECT 
    COUNT(*) as templates_utm_criados
FROM utm_templates 
WHERE user_id = @user_id;

SELECT 
    COUNT(*) as links_criados
FROM short_links 
WHERE user_id = @user_id;

SELECT 
    COUNT(*) as cliques_registrados
FROM link_clicks 
WHERE user_id = @user_id;