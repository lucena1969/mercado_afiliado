-- Script para verificar a estrutura das tabelas
-- Execute este script primeiro para ver quais colunas existem

-- 1. Verificar estrutura da tabela users
SELECT 
    'ESTRUTURA DA TABELA USERS:' as info;

DESCRIBE users;

-- 2. Verificar estrutura da tabela subscription_plans
SELECT 
    'ESTRUTURA DA TABELA SUBSCRIPTION_PLANS:' as info;

DESCRIBE subscription_plans;

-- 3. Verificar estrutura da tabela user_subscriptions
SELECT 
    'ESTRUTURA DA TABELA USER_SUBSCRIPTIONS:' as info;

DESCRIBE user_subscriptions;

-- 4. Verificar se já existem planos
SELECT 
    'PLANOS EXISTENTES:' as info;

SELECT 
    id,
    name,
    slug,
    status
FROM subscription_plans;

-- 5. Verificar se já existem usuários de teste
SELECT 
    'USUÁRIOS DE TESTE EXISTENTES:' as info;

SELECT 
    id,
    name,
    email,
    status
FROM users 
WHERE email LIKE '%teste%' OR email LIKE '%test%';