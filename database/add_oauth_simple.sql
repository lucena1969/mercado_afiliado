-- Migração OAuth simples
USE u590097272_mercado_afilia;

-- Tentar adicionar campos (ignorar erros se já existem)
ALTER TABLE users ADD COLUMN uuid VARCHAR(36) NULL AFTER id;
ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER email;
ALTER TABLE users ADD COLUMN avatar VARCHAR(500) NULL AFTER phone;
ALTER TABLE users ADD COLUMN google_id VARCHAR(100) NULL AFTER avatar;
ALTER TABLE users ADD COLUMN facebook_id VARCHAR(100) NULL AFTER google_id;
ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL AFTER facebook_id;
ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL AFTER email_verified_at;

-- Gerar UUIDs para registros sem UUID
UPDATE users SET uuid = UUID() WHERE uuid IS NULL OR uuid = '';

-- Adicionar índices se não existem
CREATE UNIQUE INDEX idx_uuid ON users(uuid);
CREATE INDEX idx_google_id ON users(google_id);
CREATE INDEX idx_facebook_id ON users(facebook_id);