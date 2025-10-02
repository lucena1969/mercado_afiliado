-- ================================
-- MIGRAÇÃO: Adicionar campos OAuth
-- ================================

USE u590097272_mercado_afilia;

-- Adicionar campos OAuth na tabela users
ALTER TABLE users 
ADD COLUMN uuid VARCHAR(36) NULL AFTER id,
ADD COLUMN phone VARCHAR(20) NULL AFTER email,
ADD COLUMN avatar VARCHAR(500) NULL AFTER phone,
ADD COLUMN google_id VARCHAR(100) NULL AFTER avatar,
ADD COLUMN facebook_id VARCHAR(100) NULL AFTER google_id,
ADD COLUMN email_verified_at TIMESTAMP NULL AFTER facebook_id,
ADD COLUMN last_login_at TIMESTAMP NULL AFTER email_verified_at;

-- Gerar UUIDs para usuários existentes
UPDATE users SET uuid = (
    SELECT UUID()
) WHERE uuid IS NULL;

-- Tornar UUID obrigatório
ALTER TABLE users MODIFY COLUMN uuid VARCHAR(36) NOT NULL;

-- Adicionar índices únicos para OAuth
ALTER TABLE users 
ADD UNIQUE KEY unique_uuid (uuid),
ADD UNIQUE KEY unique_google_id (google_id),
ADD UNIQUE KEY unique_facebook_id (facebook_id);

-- Adicionar índices para performance
ALTER TABLE users 
ADD INDEX idx_google_id (google_id),
ADD INDEX idx_facebook_id (facebook_id),
ADD INDEX idx_last_login (last_login_at);