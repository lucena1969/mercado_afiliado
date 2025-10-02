-- PASSO A PASSO para resolver o problema
-- Execute comando por comando no phpMyAdmin

-- PASSO 1: Ver estrutura da tabela (para entender a constraint)
SHOW CREATE TABLE subscription_plans;

-- PASSO 2: Ver planos existentes
SELECT * FROM subscription_plans;

-- PASSO 3A: Se não houver planos, tentar criar com features válidas
INSERT INTO subscription_plans (name, slug, features) 
VALUES ('Pro', 'pro', 'link_maestro');

-- PASSO 3B: Se der erro, tentar sem a coluna features
INSERT INTO subscription_plans (name, slug) 
VALUES ('Pro', 'pro');

-- PASSO 3C: Se ainda der erro, tentar só com name
INSERT INTO subscription_plans (name) VALUES ('Pro');

-- PASSO 4: Verificar se algum plano foi criado
SELECT * FROM subscription_plans;

-- PASSO 5: Se houver qualquer plano (ID = X), criar assinatura
-- Substitua X pelo ID do plano disponível

-- INSERT INTO user_subscriptions (user_id, plan_id) 
-- VALUES (
--     (SELECT id FROM users WHERE email = 'teste@linkmaestro.com'),
--     X
-- );

-- PASSO 6: Verificar se funcionou
SELECT 
    u.name,
    u.email,
    sp.name as plano
FROM users u
LEFT JOIN user_subscriptions us ON u.id = us.user_id
LEFT JOIN subscription_plans sp ON us.plan_id = sp.id
WHERE u.email = 'teste@linkmaestro.com';