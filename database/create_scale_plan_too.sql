-- Script para criar AMBOS os planos Pro e Scale
-- Execute este script no banco de dados mercado_afiliado

-- 1. Criar plano Pro
INSERT INTO subscription_plans (
    name, 
    slug, 
    description, 
    price_monthly, 
    price_yearly,
    features, 
    limits_json,
    is_active,
    sort_order
) VALUES (
    'Pro', 
    'pro',
    'Plano Pro com Link Maestro e recursos avançados',
    149.00,
    1490.00,
    '["link_maestro", "utm_templates", "advanced_analytics", "click_tracking", "pixel_br", "whatsapp_alerts"]',
    '{"short_links": 1000, "utm_templates": 50, "clicks_per_month": 50000, "analytics_retention_days": 365}',
    1,
    2
);

-- 2. Criar plano Scale
INSERT INTO subscription_plans (
    name, 
    slug, 
    description, 
    price_monthly, 
    price_yearly,
    features, 
    limits_json,
    is_active,
    sort_order
) VALUES (
    'Scale', 
    'scale',
    'Plano Scale com recursos ilimitados e white label',
    299.00,
    2990.00,
    '["link_maestro", "utm_templates", "advanced_analytics", "click_tracking", "pixel_br", "whatsapp_alerts", "custom_domains", "team_management", "white_label"]',
    '{"short_links": -1, "utm_templates": -1, "clicks_per_month": -1, "analytics_retention_days": 730}',
    1,
    3
);

-- 3. Criar plano Starter (gratuito) se não existir
INSERT INTO subscription_plans (
    name, 
    slug, 
    description, 
    price_monthly, 
    price_yearly,
    features, 
    limits_json,
    is_active,
    sort_order
) VALUES (
    'Starter', 
    'starter',
    'Plano gratuito básico',
    0.00,
    0.00,
    '["basic_dashboard", "integrations"]',
    '{"short_links": 0, "utm_templates": 0, "clicks_per_month": 0, "analytics_retention_days": 0}',
    1,
    1
);

-- 4. Ver todos os planos criados
SELECT id, name, slug, price_monthly, features FROM subscription_plans ORDER BY sort_order;

-- 5. Dar plano Pro para o usuário de teste
INSERT INTO user_subscriptions (user_id, plan_id) 
SELECT 
    u.id,
    sp.id
FROM users u, subscription_plans sp
WHERE u.email = 'teste@linkmaestro.com' 
  AND sp.slug = 'pro'
LIMIT 1;

-- 6. Verificar resultado final
SELECT 
    u.name,
    u.email,
    sp.name as plano,
    sp.price_monthly as preco_mensal,
    'Acesso ao Link Maestro: SIM' as link_maestro_access
FROM users u
JOIN user_subscriptions us ON u.id = us.user_id
JOIN subscription_plans sp ON us.plan_id = sp.id
WHERE u.email = 'teste@linkmaestro.com';