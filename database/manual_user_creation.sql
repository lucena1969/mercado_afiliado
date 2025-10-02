-- CRIAÇÃO MANUAL DE USUÁRIO DE TESTE
-- Execute comando por comando no phpMyAdmin

-- 1. PRIMEIRO: Ver estrutura das tabelas
SHOW COLUMNS FROM users;
SHOW COLUMNS FROM subscription_plans;  
SHOW COLUMNS FROM user_subscriptions;

-- 2. CRIAR USUÁRIO (execute só esta linha primeiro)
INSERT INTO users (name, email, password) VALUES ('Teste LinkMaestro', 'teste@linkmaestro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- 3. VER SE CRIOU O USUÁRIO
SELECT * FROM users WHERE email = 'teste@linkmaestro.com';

-- 4. VER QUE PLANOS EXISTEM  
SELECT * FROM subscription_plans;

-- 5. SE NÃO HOUVER PLANOS, CRIAR UM
INSERT INTO subscription_plans (name, slug) VALUES ('Pro', 'pro');

-- 6. CRIAR ASSINATURA (substitua os números pelos IDs corretos)
-- INSERT INTO user_subscriptions (user_id, plan_id) VALUES (ID_DO_USUARIO, ID_DO_PLANO);

-- EXEMPLO (ajuste os números):
-- INSERT INTO user_subscriptions (user_id, plan_id) VALUES (1, 1);

-- =====================================
-- DADOS PARA LOGIN APÓS CRIAÇÃO:
-- Email: teste@linkmaestro.com  
-- Senha: password
-- ====================================

-- INSTRUÇÕES:
-- 1. Execute SHOW COLUMNS FROM... para ver as colunas
-- 2. Execute INSERT INTO users... 
-- 3. Anote o ID do usuário criado
-- 4. Execute SELECT * FROM subscription_plans
-- 5. Anote o ID do plano Pro
-- 6. Execute INSERT INTO user_subscriptions com os IDs corretos