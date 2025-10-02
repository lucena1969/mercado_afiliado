-- UPGRADE RÁPIDO para Pro - Execute no phpMyAdmin

-- 1. Criar plano Pro se não existir
INSERT IGNORE INTO subscription_plans (name, slug) VALUES ('Pro', 'pro');

-- 2. Criar assinatura Pro para o usuário teste@linkmaestro.com
INSERT INTO user_subscriptions (user_id, plan_id) 
SELECT 
    (SELECT id FROM users WHERE email = 'teste@linkmaestro.com'),
    (SELECT id FROM subscription_plans WHERE slug = 'pro')
WHERE NOT EXISTS (
    SELECT 1 FROM user_subscriptions us 
    JOIN users u ON us.user_id = u.id 
    WHERE u.email = 'teste@linkmaestro.com'
);

-- 3. Verificar se funcionou
SELECT 
    u.name,
    u.email,
    COALESCE(sp.name, 'Starter') as plano
FROM users u
LEFT JOIN user_subscriptions us ON u.id = us.user_id
LEFT JOIN subscription_plans sp ON us.plan_id = sp.id
WHERE u.email = 'teste@linkmaestro.com';