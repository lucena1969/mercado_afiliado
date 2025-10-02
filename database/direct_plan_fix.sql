-- Correção DIRETA dos planos
-- Execute comando por comando no phpMyAdmin

-- Desativar todos
UPDATE subscription_plans SET is_active = 0;

-- Reativar Starter (R$ 79)
UPDATE subscription_plans 
SET is_active = 1, name = 'Starter', slug = 'starter', price_monthly = 79.00, sort_order = 1, description = 'Plano básico para iniciar'
WHERE id = (SELECT id FROM (SELECT MIN(id) FROM subscription_plans WHERE price_monthly BETWEEN 70 AND 90) AS t);

-- Reativar Pro (R$ 149) 
UPDATE subscription_plans 
SET is_active = 1, name = 'Pro', slug = 'pro', price_monthly = 149.00, sort_order = 2, description = 'Plano profissional com Link Maestro'
WHERE id = (SELECT id FROM (SELECT MIN(id) FROM subscription_plans WHERE price_monthly BETWEEN 140 AND 160) AS t);

-- Reativar Scale (R$ 299)
UPDATE subscription_plans 
SET is_active = 1, name = 'Scale', slug = 'scale', price_monthly = 299.00, sort_order = 3, description = 'Plano empresarial ilimitado'
WHERE id = (SELECT id FROM (SELECT MIN(id) FROM subscription_plans WHERE price_monthly BETWEEN 290 AND 310) AS t);

-- Verificar resultado
SELECT name, slug, price_monthly, is_active, sort_order FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order;