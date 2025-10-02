-- COMANDOS SIMPLES - Execute um por vez no phpMyAdmin

-- 1. Criar plano Pro
INSERT INTO subscription_plans (name, slug) VALUES ('Pro', 'pro');

-- 2. Dar plano Pro para o usuário teste
INSERT INTO user_subscriptions (user_id, plan_id) 
VALUES (
    (SELECT id FROM users WHERE email = 'teste@linkmaestro.com'),
    (SELECT id FROM subscription_plans WHERE slug = 'pro')
);

-- 3. Verificar se funcionou
SELECT 
    u.name,
    u.email,
    sp.name as plano
FROM users u
JOIN user_subscriptions us ON u.id = us.user_id
JOIN subscription_plans sp ON us.plan_id = sp.id
WHERE u.email = 'teste@linkmaestro.com';