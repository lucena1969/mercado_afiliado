-- Script para criar NOVO usuário de teste com senha garantida
-- Execute este script no banco de dados mercado_afiliado

-- 1. Criar novo usuário de teste com hash de senha conhecido
INSERT INTO users (name, email, password) 
VALUES (
    'Teste Link Maestro Pro', 
    'testepro@mercadoafiliado.local', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
);

-- 2. Ver se criou
SELECT id, name, email FROM users WHERE email = 'testepro@mercadoafiliado.local';

-- 3. Dar plano Pro para este usuário
INSERT INTO user_subscriptions (user_id, plan_id) 
SELECT 
    u.id,
    sp.id
FROM users u, subscription_plans sp
WHERE u.email = 'testepro@mercadoafiliado.local' 
  AND sp.slug = 'pro'
LIMIT 1;

-- 4. Verificar se tudo funcionou
SELECT 
    u.name,
    u.email,
    sp.name as plano,
    sp.price_monthly as preco
FROM users u
JOIN user_subscriptions us ON u.id = us.user_id
JOIN subscription_plans sp ON us.plan_id = sp.id
WHERE u.email = 'testepro@mercadoafiliado.local';

-- 5. Exibir dados de login
SELECT 
    '=== NOVO USUÁRIO DE TESTE CRIADO ===' as status,
    'testepro@mercadoafiliado.local' as email,
    'password' as senha,
    'Plano Pro Ativo' as plano;