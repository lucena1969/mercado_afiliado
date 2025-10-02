-- Comandos MANUAIS para corrigir planos
-- Execute um comando por vez no phpMyAdmin

-- 1. Ver todos os planos atuais
SELECT id, name, slug, price_monthly, is_active FROM subscription_plans ORDER BY price_monthly;

-- 2. Desativar todos os planos
UPDATE subscription_plans SET is_active = 0;

-- 3. Encontrar IDs dos planos corretos
SELECT 'Planos por faixa de preço:' as info;
SELECT id, name, price_monthly, 'STARTER (R$ 79)' as tipo FROM subscription_plans WHERE price_monthly BETWEEN 70 AND 90;
SELECT id, name, price_monthly, 'PRO (R$ 149)' as tipo FROM subscription_plans WHERE price_monthly BETWEEN 140 AND 160;  
SELECT id, name, price_monthly, 'SCALE (R$ 299)' as tipo FROM subscription_plans WHERE price_monthly BETWEEN 290 AND 310;

-- 4. SUBSTITUA X, Y, Z pelos IDs encontrados acima e execute:

-- UPDATE subscription_plans SET is_active = 1, name = 'Starter', slug = 'starter', price_monthly = 79.00, sort_order = 1 WHERE id = X;
-- UPDATE subscription_plans SET is_active = 1, name = 'Pro', slug = 'pro', price_monthly = 149.00, sort_order = 2 WHERE id = Y;  
-- UPDATE subscription_plans SET is_active = 1, name = 'Scale', slug = 'scale', price_monthly = 299.00, sort_order = 3 WHERE id = Z;

-- 5. Verificar resultado final
-- SELECT name, slug, price_monthly, is_active, sort_order FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order;

-- RESULTADO ESPERADO:
-- Starter | R$ 79  | Ordem 1
-- Pro     | R$ 149 | Ordem 2  
-- Scale   | R$ 299 | Ordem 3