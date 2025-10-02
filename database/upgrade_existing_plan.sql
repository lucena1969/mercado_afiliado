-- Script para usar um plano EXISTENTE ou criar um novo
-- Execute este script no banco de dados mercado_afiliado

-- 1. Ver todos os planos existentes
SELECT * FROM subscription_plans;

-- 2. Se já existir um plano, use o ID dele na próxima query
-- Substitua X pelo ID de um plano existente

-- OPÇÃO A: Usar um plano existente (substitua 1 pelo ID real)
-- INSERT INTO user_subscriptions (user_id, plan_id) 
-- VALUES (
--     (SELECT id FROM users WHERE email = 'teste@linkmaestro.com'),
--     1  -- Substitua pelo ID de um plano existente
-- );

-- OPÇÃO B: Tentar criar plano Pro mínimo
INSERT INTO subscription_plans (name, slug, features) 
VALUES ('Pro', 'pro', 'link_maestro');

-- 3. Criar assinatura Pro para o usuário
INSERT INTO user_subscriptions (user_id, plan_id) 
SELECT 
    u.id,
    sp.id
FROM users u, subscription_plans sp
WHERE u.email = 'teste@linkmaestro.com' 
  AND sp.slug = 'pro'
LIMIT 1;

-- 4. Verificar resultado
SELECT 
    u.name,
    u.email,
    COALESCE(sp.name, 'Sem plano') as plano,
    COALESCE(sp.features, 'Sem features') as recursos
FROM users u
LEFT JOIN user_subscriptions us ON u.id = us.user_id
LEFT JOIN subscription_plans sp ON us.plan_id = sp.id
WHERE u.email = 'teste@linkmaestro.com';