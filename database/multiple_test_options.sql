-- Script para criar MÚLTIPLAS opções de usuários de teste
-- Execute este script no banco de dados mercado_afiliado

-- OPÇÃO 1: Hash bcrypt padrão (senha: password)
INSERT INTO users (name, email, password) 
VALUES ('Teste Pro 1', 'teste1@exemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- OPÇÃO 2: Hash bcrypt alternativo (senha: 123456)
INSERT INTO users (name, email, password) 
VALUES ('Teste Pro 2', 'teste2@exemplo.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm');

-- OPÇÃO 3: Hash MD5 simples (senha: admin)
INSERT INTO users (name, email, password) 
VALUES ('Teste Pro 3', 'teste3@exemplo.com', '21232f297a57a5a743894a0e4a801fc3');

-- OPÇÃO 4: Senha em texto plano (senha: teste)
INSERT INTO users (name, email, password) 
VALUES ('Teste Pro 4', 'teste4@exemplo.com', 'teste');

-- Dar plano Pro para todos
INSERT INTO user_subscriptions (user_id, plan_id)
SELECT u.id, sp.id
FROM users u, subscription_plans sp
WHERE u.email IN ('teste1@exemplo.com', 'teste2@exemplo.com', 'teste3@exemplo.com', 'teste4@exemplo.com')
  AND sp.slug = 'pro';

-- Ver todos os usuários criados
SELECT 
    u.name,
    u.email,
    CASE 
        WHEN u.email = 'teste1@exemplo.com' THEN 'password'
        WHEN u.email = 'teste2@exemplo.com' THEN '123456'
        WHEN u.email = 'teste3@exemplo.com' THEN 'admin'
        WHEN u.email = 'teste4@exemplo.com' THEN 'teste'
    END as senha,
    sp.name as plano
FROM users u
JOIN user_subscriptions us ON u.id = us.user_id
JOIN subscription_plans sp ON us.plan_id = sp.id
WHERE u.email IN ('teste1@exemplo.com', 'teste2@exemplo.com', 'teste3@exemplo.com', 'teste4@exemplo.com');