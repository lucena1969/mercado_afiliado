-- Script PASSO A PASSO para criar usuário de teste
-- Execute uma linha por vez no phpMyAdmin

-- PASSO 1: Criar usuário
INSERT INTO users (name, email, password) 
VALUES ('Teste LinkMaestro', 'teste@linkmaestro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- PASSO 2: Ver se criou
SELECT id, name, email FROM users WHERE email = 'teste@linkmaestro.com';

-- PASSO 3: Ver que planos existem
SELECT * FROM subscription_plans;

-- PASSO 4: Ver estrutura da tabela subscription_plans
SHOW COLUMNS FROM subscription_plans;

-- PASSO 5: Se não houver planos, criar um plano Pro básico
INSERT INTO subscription_plans (name, slug) VALUES ('Pro', 'pro');

-- PASSO 6: Ver estrutura da tabela user_subscriptions  
SHOW COLUMNS FROM user_subscriptions;

-- PASSO 7: Criar assinatura Pro (ajuste conforme estrutura das tabelas)
INSERT INTO user_subscriptions (user_id, plan_id) 
SELECT 
    u.id,
    sp.id
FROM users u, subscription_plans sp
WHERE u.email = 'teste@linkmaestro.com' 
  AND sp.slug = 'pro'
LIMIT 1;

-- PASSO 8: Verificar se tudo funcionou
SELECT 
    u.id as user_id,
    u.name,
    u.email,
    sp.name as plano
FROM users u
LEFT JOIN user_subscriptions us ON u.id = us.user_id
LEFT JOIN subscription_plans sp ON us.plan_id = sp.id
WHERE u.email = 'teste@linkmaestro.com';

-- =====================================
-- DADOS PARA LOGIN:
-- Email: teste@linkmaestro.com  
-- Senha: password
-- ====================================