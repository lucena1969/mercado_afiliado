-- Script MÍNIMO para criar usuário de teste
-- Execute este script no banco de dados mercado_afiliado

-- 1. Inserir usuário de teste
INSERT INTO users (
    name, 
    email, 
    password, 
    status
) VALUES (
    'Teste LinkMaestro',
    'teste@linkmaestro.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'active'
);

-- 2. Verificar se o usuário foi criado
SELECT LAST_INSERT_ID() as user_id_criado;

-- 3. Ver o usuário criado
SELECT * FROM users WHERE email = 'teste@linkmaestro.com';

-- 4. Tentar criar plano Pro mínimo
INSERT INTO subscription_plans (name, slug) VALUES ('Pro', 'pro');

-- 5. Verificar planos disponíveis
SELECT * FROM subscription_plans;

-- =====================================
-- DADOS PARA LOGIN:
-- Email: teste@linkmaestro.com
-- Senha: password
-- ====================================

-- Se der erro, execute primeiro o script check_tables_structure.sql
-- para ver a estrutura exata das tabelas.