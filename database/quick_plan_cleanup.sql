-- Limpeza RÁPIDA de planos duplicados
-- Execute este script no banco de dados mercado_afiliado

-- 1. Ver situação atual
SELECT 'SITUAÇÃO ATUAL:' as status;
SELECT id, name, slug, price_monthly, is_active FROM subscription_plans ORDER BY price_monthly;

-- 2. Desativar todos os planos primeiro
UPDATE subscription_plans SET is_active = 0;

-- 3. Reativar apenas os 3 planos principais (usando IDs mais baixos)
-- Plano Starter (gratuito)
UPDATE subscription_plans 
SET is_active = 1, 
    name = 'Starter', 
    slug = 'starter', 
    description = 'Plano básico gratuito',
    price_monthly = 0.00,
    sort_order = 1
WHERE id = (SELECT * FROM (SELECT MIN(id) FROM subscription_plans WHERE price_monthly = 0) AS temp)
LIMIT 1;

-- Plano Pro 
UPDATE subscription_plans 
SET is_active = 1, 
    name = 'Pro', 
    slug = 'pro', 
    description = 'Plano profissional com Link Maestro',
    price_monthly = 149.00,
    sort_order = 2
WHERE id = (SELECT * FROM (SELECT MIN(id) FROM subscription_plans WHERE price_monthly BETWEEN 100 AND 200) AS temp)
LIMIT 1;

-- Plano Scale
UPDATE subscription_plans 
SET is_active = 1, 
    name = 'Scale', 
    slug = 'scale', 
    description = 'Plano empresarial ilimitado',
    price_monthly = 299.00,
    sort_order = 3
WHERE id = (SELECT * FROM (SELECT MIN(id) FROM subscription_plans WHERE price_monthly BETWEEN 200 AND 400) AS temp)
LIMIT 1;

-- 4. Resultado final
SELECT 'RESULTADO FINAL - APENAS PLANOS ATIVOS:' as resultado;
SELECT id, name, slug, price_monthly, description, is_active, sort_order 
FROM subscription_plans 
WHERE is_active = 1 
ORDER BY sort_order;