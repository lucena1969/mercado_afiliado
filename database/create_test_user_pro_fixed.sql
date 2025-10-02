-- Script CORRIGIDO para criar usuário de teste com plano Pro para Link Maestro
-- Execute este script no banco de dados mercado_afiliado

-- 1. Inserir usuário de teste (estrutura básica)
INSERT INTO users (
    name, 
    email, 
    password, 
    status, 
    created_at, 
    updated_at
) VALUES (
    'Usuário Teste Pro',
    'teste.pro@mercadoafiliado.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password
    'active',
    NOW(),
    NOW()
);

-- 2. Obter o ID do usuário criado
SET @user_id = LAST_INSERT_ID();

-- 3. Verificar se o plano Pro existe, se não, criar
INSERT IGNORE INTO subscription_plans (
    name,
    slug,
    description,
    price,
    billing_cycle,
    status,
    created_at,
    updated_at
) VALUES (
    'Pro',
    'pro',
    'Plano Pro com Link Maestro e recursos avançados',
    149.00,
    'monthly',
    'active',
    NOW(),
    NOW()
);

-- 4. Obter ID do plano Pro
SET @plan_id = (SELECT id FROM subscription_plans WHERE slug = 'pro' LIMIT 1);

-- 5. Criar assinatura Pro para o usuário de teste
INSERT INTO user_subscriptions (
    user_id,
    plan_id,
    status,
    next_billing_date,
    created_at,
    updated_at
) VALUES (
    @user_id,
    @plan_id,
    'active',
    DATE_ADD(NOW(), INTERVAL 1 MONTH),
    NOW(),
    NOW()
);

-- 6. Criar alguns dados de exemplo para teste (somente se as tabelas existirem)

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

-- 7. Exibir informações do usuário criado
SELECT 
    '=== USUÁRIO DE TESTE PRO CRIADO COM SUCESSO ===' as status;

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