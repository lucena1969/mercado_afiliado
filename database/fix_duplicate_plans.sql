-- Script para remover planos duplicados e manter apenas os válidos
-- Execute este script no banco de dados mercado_afiliado

-- 1. Ver todos os planos existentes antes da limpeza
SELECT 'PLANOS ANTES DA LIMPEZA:' as info;
SELECT id, name, slug, price_monthly, price_yearly, is_active, sort_order 
FROM subscription_plans 
ORDER BY sort_order, id;

-- 2. Desativar todos os planos duplicados ou desnecessários
-- Manter apenas um de cada tipo: Starter, Pro, Scale

-- Primeiro, identificar planos válidos para manter (com menor ID)
SET @starter_id = (SELECT MIN(id) FROM subscription_plans WHERE slug = 'starter' OR name LIKE '%Starter%' OR price_monthly = 0 LIMIT 1);
SET @pro_id = (SELECT MIN(id) FROM subscription_plans WHERE slug = 'pro' OR name LIKE '%Pro%' AND price_monthly BETWEEN 100 AND 200 LIMIT 1);
SET @scale_id = (SELECT MIN(id) FROM subscription_plans WHERE slug = 'scale' OR name LIKE '%Scale%' AND price_monthly BETWEEN 200 AND 400 LIMIT 1);

-- 3. Desativar todos os planos duplicados (manter apenas os IDs identificados)
UPDATE subscription_plans 
SET is_active = 0 
WHERE id NOT IN (
    COALESCE(@starter_id, 0), 
    COALESCE(@pro_id, 0), 
    COALESCE(@scale_id, 0)
);

-- 4. Atualizar os planos mantidos com informações corretas
-- Starter
UPDATE subscription_plans 
SET 
    name = 'Starter',
    slug = 'starter',
    description = 'Plano gratuito básico com recursos limitados',
    price_monthly = 0.00,
    price_yearly = 0.00,
    features = '["basic_dashboard", "integrations"]',
    limits_json = '{"short_links": 0, "utm_templates": 0, "clicks_per_month": 0, "analytics_retention_days": 30}',
    is_active = 1,
    sort_order = 1
WHERE id = @starter_id;

-- Pro
UPDATE subscription_plans 
SET 
    name = 'Pro',
    slug = 'pro',
    description = 'Plano profissional com Link Maestro e analytics avançado',
    price_monthly = 149.00,
    price_yearly = 1490.00,
    features = '["link_maestro", "utm_templates", "advanced_analytics", "click_tracking", "pixel_br", "whatsapp_alerts"]',
    limits_json = '{"short_links": 1000, "utm_templates": 50, "clicks_per_month": 50000, "analytics_retention_days": 365}',
    is_active = 1,
    sort_order = 2
WHERE id = @pro_id;

-- Scale
UPDATE subscription_plans 
SET 
    name = 'Scale',
    slug = 'scale',
    description = 'Plano empresarial com recursos ilimitados e white label',
    price_monthly = 299.00,
    price_yearly = 2990.00,
    features = '["link_maestro", "utm_templates", "advanced_analytics", "click_tracking", "pixel_br", "whatsapp_alerts", "custom_domains", "team_management", "white_label"]',
    limits_json = '{"short_links": -1, "utm_templates": -1, "clicks_per_month": -1, "analytics_retention_days": 730}',
    is_active = 1,
    sort_order = 3
WHERE id = @scale_id;

-- 5. Ver resultado final (apenas planos ativos)
SELECT 'PLANOS APÓS LIMPEZA (APENAS ATIVOS):' as info;
SELECT id, name, slug, price_monthly, price_yearly, is_active, sort_order 
FROM subscription_plans 
WHERE is_active = 1
ORDER BY sort_order;

-- 6. Ver planos desativados
SELECT 'PLANOS DESATIVADOS:' as info;
SELECT id, name, slug, price_monthly, is_active 
FROM subscription_plans 
WHERE is_active = 0;

-- 7. Limpar planos completamente duplicados (opcional - remover da base)
-- DELETE FROM subscription_plans WHERE is_active = 0;

SELECT 'LIMPEZA CONCLUÍDA - Agora você terá apenas 3 planos: Starter (grátis), Pro (R$ 149) e Scale (R$ 299)' as resultado;