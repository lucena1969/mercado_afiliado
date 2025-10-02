-- Script para corrigir a senha do usuário de teste
-- Execute este script no banco de dados mercado_afiliado

-- 1. Ver usuários existentes com email teste
SELECT id, name, email, password FROM users WHERE email LIKE '%teste%';

-- 2. Atualizar senha para um hash mais simples (senha: 123456)
UPDATE users 
SET password = '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm' 
WHERE email = 'teste@linkmaestro.com';

-- 3. Verificar se atualizou
SELECT id, name, email, 'Senha atualizada para: 123456' as nova_senha FROM users WHERE email = 'teste@linkmaestro.com';

-- 4. Alternativa: Criar novo usuário com senha mais simples
INSERT INTO users (name, email, password) 
VALUES ('Usuario Teste Pro', 'teste.pro@exemplo.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm');

-- 5. Dar plano Pro para o novo usuário também
INSERT INTO user_subscriptions (user_id, plan_id) 
SELECT 
    u.id,
    sp.id
FROM users u, subscription_plans sp
WHERE u.email = 'teste.pro@exemplo.com' 
  AND sp.slug = 'pro'
LIMIT 1;

-- 6. Exibir opções de login
SELECT 
    '=== OPÇÕES DE LOGIN ===' as info;

SELECT 
    email,
    '123456' as senha,
    'Hash atualizado' as observacao
FROM users 
WHERE email IN ('teste@linkmaestro.com', 'teste.pro@exemplo.com');