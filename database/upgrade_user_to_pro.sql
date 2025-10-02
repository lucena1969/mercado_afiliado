-- Script para UPGRADAR usuário de teste para plano Pro
-- Execute este script no banco de dados mercado_afiliado

-- 1. Ver o usuário de teste atual
SELECT 
    u.id,
    u.name,
    u.email,
    'Status atual: Starter (sem plano)' as observacao
FROM users u 
WHERE u.email = 'teste@linkmaestro.com';

-- 2. Ver se existe plano Pro, se não criar
INSERT IGNORE INTO subscription_plans (name, slug) VALUES ('Pro', 'pro');

-- 3. Ver planos disponíveis
SELECT id, name, slug FROM subscription_plans;

-- 4. Criar assinatura Pro para o usuário de teste
INSERT INTO user_subscriptions (user_id, plan_id) 
SELECT 
    u.id as user_id,
    sp.id as plan_id
FROM users u, subscription_plans sp
WHERE u.email = 'teste@linkmaestro.com' 
  AND sp.slug = 'pro'
LIMIT 1;

-- 5. Verificar se o upgrade funcionou
SELECT 
    u.id as user_id,
    u.name,
    u.email,
    COALESCE(sp.name, 'Starter') as plano_atual,
    COALESCE(sp.slug, 'starter') as plano_slug,
    COALESCE(us.id, 'Sem assinatura') as assinatura_id
FROM users u
LEFT JOIN user_subscriptions us ON u.id = us.user_id
LEFT JOIN subscription_plans sp ON us.plan_id = sp.id
WHERE u.email = 'teste@linkmaestro.com';

-- 6. Resultado esperado
SELECT 
    '=== UPGRADE CONCLUÍDO ===' as status,
    'teste@linkmaestro.com' as email,
    'password' as senha,
    'Pro' as novo_plano;