-- Script para corrigir os planos de assinatura na base de produção
-- Execute este script no phpMyAdmin do servidor
-- Base de dados: u590097272_mercado_afilia

-- 1. Verificar planos existentes
SELECT 'PLANOS ATUAIS:' as info;
SELECT id, name, slug, price_monthly, is_active, sort_order FROM subscription_plans ORDER BY sort_order, price_monthly;

-- 2. Desativar todos os planos existentes (para evitar conflitos)
UPDATE subscription_plans SET is_active = 0;

-- 3. Verificar se os planos corretos já existem e ativá-los
-- Starter (R$ 79)
UPDATE subscription_plans 
SET is_active = 1, 
    name = 'Starter', 
    slug = 'starter', 
    description = 'Ideal para afiliados iniciantes',
    price_monthly = 79.00,
    sort_order = 1
WHERE price_monthly BETWEEN 70 AND 90 
LIMIT 1;

-- Pro (R$ 149)  
UPDATE subscription_plans 
SET is_active = 1,
    name = 'Pro',
    slug = 'pro', 
    description = 'Para afiliados em crescimento',
    price_monthly = 149.00,
    sort_order = 2
WHERE price_monthly BETWEEN 140 AND 160 
LIMIT 1;

-- Scale (R$ 299)
UPDATE subscription_plans 
SET is_active = 1,
    name = 'Scale', 
    slug = 'scale',
    description = 'Para operações grandes e equipes', 
    price_monthly = 299.00,
    sort_order = 3
WHERE price_monthly BETWEEN 290 AND 310 
LIMIT 1;

-- 4. Se não existirem planos nos valores corretos, criar novos
INSERT INTO subscription_plans (name, slug, description, price_monthly, price_yearly, features, limits_json, is_active, sort_order, created_at, updated_at)
SELECT * FROM (
    SELECT 'Starter' as name, 'starter' as slug, 'Ideal para afiliados iniciantes' as description, 
           79.00 as price_monthly, 790.00 as price_yearly,
           '[\"Painel Unificado\", \"IntegraSync (2 redes)\", \"Alertas por e-mail\", \"UTM Templates\"]' as features,
           '{\"integrations\": 2, \"team_members\": 1, \"link_maestro\": false, \"pixel_br\": false, \"capi_bridge\": false, \"advanced_alerts\": false, \"trial_days\": 14}' as limits_json,
           1 as is_active, 1 as sort_order, NOW() as created_at, NOW() as updated_at
) as tmp
WHERE NOT EXISTS (
    SELECT 1 FROM subscription_plans WHERE slug = 'starter' AND is_active = 1
);

INSERT INTO subscription_plans (name, slug, description, price_monthly, price_yearly, features, limits_json, is_active, sort_order, created_at, updated_at)
SELECT * FROM (
    SELECT 'Pro' as name, 'pro' as slug, 'Para afiliados em crescimento' as description,
           149.00 as price_monthly, 1490.00 as price_yearly, 
           '[\"Tudo do Starter\", \"Link Maestro\", \"Pixel BR\", \"Alertas WhatsApp/Telegram\", \"Cohort Reembolso\", \"Offer Radar\"]' as features,
           '{\"integrations\": 4, \"team_members\": 3, \"link_maestro\": true, \"pixel_br\": true, \"capi_bridge\": false, \"advanced_alerts\": true, \"trial_days\": 14}' as limits_json,
           1 as is_active, 2 as sort_order, NOW() as created_at, NOW() as updated_at
) as tmp  
WHERE NOT EXISTS (
    SELECT 1 FROM subscription_plans WHERE slug = 'pro' AND is_active = 1
);

INSERT INTO subscription_plans (name, slug, description, price_monthly, price_yearly, features, limits_json, is_active, sort_order, created_at, updated_at)
SELECT * FROM (
    SELECT 'Scale' as name, 'scale' as slug, 'Para operações grandes e equipes' as description,
           299.00 as price_monthly, 2990.00 as price_yearly,
           '[\"Tudo do Pro\", \"CAPI Bridge\", \"Equipe ilimitada\", \"Auditoria LGPD\", \"Suporte prioritário\"]' as features,
           '{\"integrations\": 999, \"team_members\": 999, \"link_maestro\": true, \"pixel_br\": true, \"capi_bridge\": true, \"advanced_alerts\": true, \"trial_days\": 14}' as limits_json,
           1 as is_active, 3 as sort_order, NOW() as created_at, NOW() as updated_at
) as tmp
WHERE NOT EXISTS (
    SELECT 1 FROM subscription_plans WHERE slug = 'scale' AND is_active = 1  
);

-- 5. Garantir que temos apenas 3 planos ativos com os slugs corretos
DELETE FROM subscription_plans 
WHERE is_active = 1 
AND slug NOT IN ('starter', 'pro', 'scale');

-- 6. Verificar resultado final
SELECT 'RESULTADO FINAL:' as info;
SELECT id, name, slug, description, price_monthly, is_active, sort_order 
FROM subscription_plans 
WHERE is_active = 1 
ORDER BY sort_order;

-- 7. Contar planos ativos
SELECT 'TOTAL DE PLANOS ATIVOS:' as info, COUNT(*) as total 
FROM subscription_plans 
WHERE is_active = 1;

-- RESULTADO ESPERADO:
-- 3 planos ativos:
-- Starter - R$ 79,00 - Ordem 1
-- Pro - R$ 149,00 - Ordem 2  
-- Scale - R$ 299,00 - Ordem 3