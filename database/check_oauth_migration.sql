-- ================================
-- VERIFICAR E MIGRAR CAMPOS OAuth
-- ================================

USE u590097272_mercado_afilia;

-- Verificar se tabela users existe
SELECT 'Tabela users existe' as status 
FROM information_schema.tables 
WHERE table_schema = 'u590097272_mercado_afilia' AND table_name = 'users';

-- Verificar colunas existentes
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE 
FROM information_schema.columns 
WHERE table_schema = 'u590097272_mercado_afilia' AND table_name = 'users'
ORDER BY ORDINAL_POSITION;

-- Adicionar campos OAuth se não existirem
SET @sql = '';

SELECT @sql := CASE 
    WHEN COUNT(*) = 0 THEN CONCAT(@sql, 'ALTER TABLE users ADD COLUMN uuid VARCHAR(36) NULL AFTER id; ')
    ELSE @sql
END
FROM information_schema.columns 
WHERE table_schema = 'u590097272_mercado_afilia' AND table_name = 'users' AND column_name = 'uuid';

SELECT @sql := CASE 
    WHEN COUNT(*) = 0 THEN CONCAT(@sql, 'ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER email; ')
    ELSE @sql
END
FROM information_schema.columns 
WHERE table_schema = 'u590097272_mercado_afilia' AND table_name = 'users' AND column_name = 'phone';

SELECT @sql := CASE 
    WHEN COUNT(*) = 0 THEN CONCAT(@sql, 'ALTER TABLE users ADD COLUMN avatar VARCHAR(500) NULL AFTER phone; ')
    ELSE @sql
END
FROM information_schema.columns 
WHERE table_schema = 'u590097272_mercado_afilia' AND table_name = 'users' AND column_name = 'avatar';

SELECT @sql := CASE 
    WHEN COUNT(*) = 0 THEN CONCAT(@sql, 'ALTER TABLE users ADD COLUMN google_id VARCHAR(100) NULL AFTER avatar; ')
    ELSE @sql
END
FROM information_schema.columns 
WHERE table_schema = 'u590097272_mercado_afilia' AND table_name = 'users' AND column_name = 'google_id';

SELECT @sql := CASE 
    WHEN COUNT(*) = 0 THEN CONCAT(@sql, 'ALTER TABLE users ADD COLUMN facebook_id VARCHAR(100) NULL AFTER google_id; ')
    ELSE @sql
END
FROM information_schema.columns 
WHERE table_schema = 'u590097272_mercado_afilia' AND table_name = 'users' AND column_name = 'facebook_id';

SELECT @sql := CASE 
    WHEN COUNT(*) = 0 THEN CONCAT(@sql, 'ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL AFTER facebook_id; ')
    ELSE @sql
END
FROM information_schema.columns 
WHERE table_schema = 'u590097272_mercado_afilia' AND table_name = 'users' AND column_name = 'email_verified_at';

SELECT @sql := CASE 
    WHEN COUNT(*) = 0 THEN CONCAT(@sql, 'ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL AFTER email_verified_at; ')
    ELSE @sql
END
FROM information_schema.columns 
WHERE table_schema = 'u590097272_mercado_afilia' AND table_name = 'users' AND column_name = 'last_login_at';

-- Executar alterações se necessário
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;