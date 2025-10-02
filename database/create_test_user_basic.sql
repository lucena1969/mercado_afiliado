-- Script BÁSICO para criar usuário de teste Pro
-- Execute este script no banco de dados mercado_afiliado

-- 1. Inserir usuário de teste básico
INSERT INTO users (
    name, 
    email, 
    password, 
    status, 
    created_at, 
    updated_at
) VALUES (
    'Teste Pro LinkMaestro',
    'teste.linkmaestro@gmail.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'active',
    NOW(),
    NOW()
);

-- 2. Verificar se criou o usuário
SELECT 
    id,
    name,
    email,
    status
FROM users 
WHERE email = 'teste.linkmaestro@gmail.com';

-- 3. Criar plano Pro básico (apenas colunas essenciais)
INSERT IGNORE INTO subscription_plans (
    name,
    slug,
    status,
    created_at,
    updated_at
) VALUES (
    'Pro',
    'pro',
    'active',
    NOW(),
    NOW()
);

-- 4. Verificar se o plano foi criado
SELECT 
    id,
    name,
    slug,
    status
FROM subscription_plans 
WHERE slug = 'pro';

-- 5. Criar assinatura Pro para o usuário
INSERT INTO user_subscriptions (
    user_id,
    plan_id,
    status,
    created_at,
    updated_at
) 
SELECT 
    u.id,
    sp.id,
    'active',
    NOW(),
    NOW()
FROM users u, subscription_plans sp
WHERE u.email = 'teste.linkmaestro@gmail.com' 
  AND sp.slug = 'pro';

-- 6. Verificar resultado final
SELECT 
    u.id as user_id,
    u.name,
    u.email,
    sp.name as plano,
    sp.slug as plano_slug,
    us.status as status_assinatura
FROM users u
JOIN user_subscriptions us ON u.id = us.user_id
JOIN subscription_plans sp ON us.plan_id = sp.id
WHERE u.email = 'teste.linkmaestro@gmail.com';

-- =====================================
-- DADOS PARA LOGIN:
-- Email: teste.linkmaestro@gmail.com
-- Senha: password
-- ====================================