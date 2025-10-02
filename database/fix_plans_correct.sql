-- Correção dos planos com valores corretos
-- Starter: R$ 79, Pro: R$ 149, Scale: R$ 299
-- Execute este script no phpMyAdmin

-- 1. Ver situação atual
SELECT 'SITUAÇÃO ATUAL:' as status;
SELECT id, name, slug, price_monthly, is_active FROM subscription_plans ORDER BY price_monthly;

-- 2. Desativar todos os planos primeiro
UPDATE subscription_plans SET is_active = 0;

-- 3. Reativar apenas os 3 planos corretos
-- Plano Starter (R$ 79)
UPDATE subscription_plans 
SET is_active = 1, 
    name = 'Starter', 
    slug = 'starter', 
    description = 'Plano básico para começar',
    price_monthly = 79.00,
    sort_order = 1
WHERE id = (SELECT * FROM (SELECT MIN(id) FROM subscription_plans WHERE price_monthly BETWEEN 75 AND 85) AS temp)
LIMIT 1;

-- Plano Pro (R$ 149)
UPDATE subscription_plans 
SET is_active = 1, 
    name = 'Pro', 
    slug = 'pro', 
    description = 'Plano profissional com Link Maestro',
    price_monthly = 149.00,
    sort_order = 2
WHERE id = (SELECT * FROM (SELECT MIN(id) FROM subscription_plans WHERE price_monthly BETWEEN 140 AND 160) AS temp)
LIMIT 1;

-- Plano Scale (R$ 299)
UPDATE subscription_plans 
SET is_active = 1, 
    name = 'Scale', 
    slug = 'scale', 
    description = 'Plano empresarial com recursos ilimitados',
    price_monthly = 299.00,
    sort_order = 3
WHERE id = (SELECT * FROM (SELECT MIN(id) FROM subscription_plans WHERE price_monthly BETWEEN 290 AND 310) AS temp)
LIMIT 1;

-- 4. Resultado final na ordem correta
SELECT 'RESULTADO FINAL - ORDEM: STARTER, PRO, SCALE:' as resultado;
SELECT id, name, slug, price_monthly, description, is_active, sort_order 
FROM subscription_plans 
WHERE is_active = 1 
ORDER BY sort_order;