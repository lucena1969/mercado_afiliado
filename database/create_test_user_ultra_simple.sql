-- Script ULTRA SIMPLES para criar usuário de teste
-- Execute este script no banco de dados mercado_afiliado

-- 1. Inserir usuário de teste (apenas colunas obrigatórias)
INSERT INTO users (name, email, password) 
VALUES (
    'Teste LinkMaestro',
    'teste@linkmaestro.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
);

-- 2. Ver o ID do usuário criado
SELECT LAST_INSERT_ID() as user_id_criado;

-- 3. Ver o usuário criado
SELECT id, name, email FROM users WHERE email = 'teste@linkmaestro.com';

-- 4. Ver planos existentes
SELECT * FROM subscription_plans LIMIT 5;

-- 5. Se houver planos, pegar o primeiro disponível para criar assinatura
-- (Substitua X pelo ID do plano que apareceu na consulta acima)

-- INSERT INTO user_subscriptions (user_id, plan_id) 
-- VALUES (
--     (SELECT id FROM users WHERE email = 'teste@linkmaestro.com'),
--     X  -- Substitua X pelo ID do plano desejado
-- );

-- =====================================
-- DADOS PARA LOGIN:
-- Email: teste@linkmaestro.com
-- Senha: password
-- ====================================