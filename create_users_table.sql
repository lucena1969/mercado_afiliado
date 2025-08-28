-- ================================
-- TABELA USERS (necessária para o Pixel BR)
-- ================================

USE mercado_afiliado;

-- Criar tabela users se não existir
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    plan ENUM('starter', 'pro', 'scale') DEFAULT 'starter',
    status ENUM('active', 'inactive', 'trial') DEFAULT 'trial',
    trial_ends_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_plan (plan)
);

-- Inserir usuário de teste
INSERT IGNORE INTO users (id, name, email, password, plan, status) VALUES 
(999, 'Usuário Teste Pixel', 'teste@pixel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pro', 'active');

-- Verificar se as tabelas de integração existem
CREATE TABLE IF NOT EXISTS integrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    platform ENUM('hotmart', 'monetizze', 'eduzz', 'braip') NOT NULL,
    name VARCHAR(255) NOT NULL,
    api_key VARCHAR(255) NULL,
    api_secret VARCHAR(255) NULL,
    webhook_token VARCHAR(255) NULL,
    webhook_url VARCHAR(255) NULL,
    status ENUM('active', 'inactive', 'error', 'pending') DEFAULT 'pending',
    last_sync_at TIMESTAMP NULL,
    last_error TEXT NULL,
    config_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_platform (user_id, platform),
    INDEX idx_user_platform (user_id, platform),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    integration_id INT NOT NULL,
    external_id VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NULL,
    price DECIMAL(10,2) NULL,
    currency VARCHAR(3) DEFAULT 'BRL',
    commission_percentage DECIMAL(5,2) NULL,
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    metadata_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (integration_id) REFERENCES integrations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_integration_external (integration_id, external_id),
    INDEX idx_integration (integration_id),
    INDEX idx_status (status)
);

-- Inserir dados de teste
INSERT IGNORE INTO integrations (id, user_id, platform, name, status, config_json) VALUES 
(1, 999, 'hotmart', 'Integração Teste', 'active', '{"facebook_pixel_id": "123456789", "test": true}');

INSERT IGNORE INTO products (id, integration_id, external_id, name, price) VALUES 
(1, 1, 'PROD_TEST_001', 'Produto de Teste do Pixel', 197.00);