-- Script CORRETO para criar plano Pro com a estrutura real da tabela
-- Execute este script no banco de dados mercado_afiliado

-- 1. Ver planos existentes
SELECT id, name, slug, price_monthly, features FROM subscription_plans;

-- 2. Criar plano Pro com todas as colunas obrigatórias
INSERT INTO subscription_plans (
    name, 
    slug, 
    description, 
    price_monthly, 
    features, 
    limits_json,
    is_active,
    sort_order
) VALUES (
    'Pro', 
    'pro',
    'Plano Pro com Link Maestro e recursos avançados',
    149.00,
    '["link_maestro", "utm_templates", "advanced_analytics", "click_tracking", "pixel_br"]',
    '{"short_links": 1000, "utm_templates": 50, "clicks_per_month": 50000, "analytics_retention_days": 365}',
    1,
    2
);

-- 3. Verificar se o plano foi criado
SELECT id, name, slug, price_monthly, features, limits_json FROM subscription_plans WHERE slug = 'pro';

-- 4. Criar assinatura Pro para o usuário de teste
INSERT INTO user_subscriptions (user_id, plan_id) 
SELECT 
    u.id,
    sp.id
FROM users u, subscription_plans sp
WHERE u.email = 'teste@linkmaestro.com' 
  AND sp.slug = 'pro'
LIMIT 1;

-- 5. Verificar se tudo funcionou
SELECT 
    u.id as user_id,
    u.name,
    u.email,
    sp.name as plano,
    sp.slug as plano_slug,
    sp.price_monthly as preco,
    us.id as subscription_id
FROM users u
JOIN user_subscriptions us ON u.id = us.user_id
JOIN subscription_plans sp ON us.plan_id = sp.id
WHERE u.email = 'teste@linkmaestro.com';

-- 6. Resultado esperado
SELECT 
    '=== USUÁRIO UPGRADE PARA PRO CONCLUÍDO ===' as status,
    'teste@linkmaestro.com' as email,
    'password' as senha,
    'Pro - R$ 149,00/mês' as plano_ativo;