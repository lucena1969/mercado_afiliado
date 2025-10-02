-- Script para verificar a estrutura da tabela subscription_plans
-- Execute este script para entender as constraints

-- 1. Ver estrutura da tabela
SHOW COLUMNS FROM subscription_plans;

-- 2. Ver constraints e índices
SHOW INDEX FROM subscription_plans;

-- 3. Ver se já existem planos
SELECT * FROM subscription_plans;

-- 4. Ver informações detalhadas da tabela
SHOW CREATE TABLE subscription_plans;