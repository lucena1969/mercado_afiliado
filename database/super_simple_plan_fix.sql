-- SUPER SIMPLES: Desativar planos duplicados
-- Execute este comando no phpMyAdmin

-- Desativar todos os planos
UPDATE subscription_plans SET is_active = 0;

-- Reativar apenas 3 planos (pelos menores IDs)
UPDATE subscription_plans SET is_active = 1 WHERE id IN (
    SELECT id FROM (
        SELECT MIN(id) as id FROM subscription_plans WHERE price_monthly = 0 
        UNION
        SELECT MIN(id) as id FROM subscription_plans WHERE price_monthly BETWEEN 100 AND 200
        UNION  
        SELECT MIN(id) as id FROM subscription_plans WHERE price_monthly BETWEEN 250 AND 350
    ) as temp
);

-- Corrigir nomes e preços
UPDATE subscription_plans SET name = 'Starter', slug = 'starter', price_monthly = 0, sort_order = 1 WHERE price_monthly = 0 AND is_active = 1;
UPDATE subscription_plans SET name = 'Pro', slug = 'pro', price_monthly = 149, sort_order = 2 WHERE price_monthly BETWEEN 100 AND 200 AND is_active = 1;
UPDATE subscription_plans SET name = 'Scale', slug = 'scale', price_monthly = 299, sort_order = 3 WHERE price_monthly BETWEEN 250 AND 350 AND is_active = 1;

-- Ver resultado
SELECT id, name, slug, price_monthly, is_active, sort_order FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order;