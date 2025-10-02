-- Script SEGURO para criar plano Pro respeitando constraints
-- Execute este script no banco de dados mercado_afiliado

-- 1. Ver planos existentes primeiro
SELECT id, name, slug FROM subscription_plans;

-- 2. Tentar criar plano Pro com todas as colunas possíveis
INSERT INTO subscription_plans (
    name, 
    slug,
    description,
    billing_cycle,
    features,
    created_at,
    updated_at
) VALUES (
    'Pro', 
    'pro',
    'Plano Pro com Link Maestro',
    'monthly',
    'link_maestro,utm_templates,advanced_analytics',
    NOW(),
    NOW()
);

-- 3. Se der erro na linha acima, tente esta versão mais simples:
-- INSERT INTO subscription_plans (name, slug, features) 
-- VALUES ('Pro', 'pro', 'link_maestro');

-- 4. Ver se o plano foi criado
SELECT id, name, slug, features FROM subscription_plans WHERE slug = 'pro';