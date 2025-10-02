-- ================================
-- INTEGRASYNC - ESTRUTURA DO BANCO
-- Sistema de integrações com redes de afiliados
-- ================================

USE mercado_afiliado;

-- ================================
-- TABELA: integrations (configurações das integrações)
-- ================================
CREATE TABLE integrations (
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
    config_json JSON NULL, -- Configurações específicas da plataforma
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_platform (user_id, platform),
    INDEX idx_user_platform (user_id, platform),
    INDEX idx_status (status),
    INDEX idx_last_sync (last_sync_at)
);
1
-- ================================
-- TABELA: products (produtos das redes)
-- ================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    integration_id INT NOT NULL,
    external_id VARCHAR(100) NOT NULL, -- ID do produto na rede
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NULL,
    price DECIMAL(10,2) NULL,
    currency VARCHAR(3) DEFAULT 'BRL',
    commission_percentage DECIMAL(5,2) NULL,
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    metadata_json JSON NULL, -- Dados extras do produto
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (integration_id) REFERENCES integrations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_integration_external (integration_id, external_id),
    INDEX idx_integration (integration_id),
    INDEX idx_external_id (external_id),
    INDEX idx_status (status)
);

-- ================================
-- TABELA: sales (vendas sincronizadas)
-- ================================
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    integration_id INT NOT NULL,
    product_id INT NULL,
    external_sale_id VARCHAR(100) NOT NULL, -- ID da venda na rede
    customer_name VARCHAR(255) NULL,
    customer_email VARCHAR(255) NULL,
    customer_document VARCHAR(20) NULL,
    amount DECIMAL(10,2) NOT NULL,
    commission_amount DECIMAL(10,2) NULL,
    currency VARCHAR(3) DEFAULT 'BRL',
    status ENUM('approved', 'pending', 'cancelled', 'refunded', 'chargeback') NOT NULL,
    payment_method VARCHAR(50) NULL,
    utm_source VARCHAR(100) NULL,
    utm_medium VARCHAR(100) NULL,
    utm_campaign VARCHAR(100) NULL,
    utm_content VARCHAR(100) NULL,
    utm_term VARCHAR(100) NULL,
    conversion_date TIMESTAMP NOT NULL,
    approval_date TIMESTAMP NULL,
    refund_date TIMESTAMP NULL,
    metadata_json JSON NULL, -- Dados extras da venda
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (integration_id) REFERENCES integrations(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    UNIQUE KEY unique_integration_external_sale (integration_id, external_sale_id),
    INDEX idx_integration (integration_id),
    INDEX idx_product (product_id),
    INDEX idx_status (status),
    INDEX idx_conversion_date (conversion_date),
    INDEX idx_approval_date (approval_date),
    INDEX idx_customer_email (customer_email),
    INDEX idx_utm_source (utm_source),
    INDEX idx_utm_campaign (utm_campaign)
);

-- ================================
-- TABELA: sync_logs (logs de sincronização)
-- ================================
CREATE TABLE sync_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    integration_id INT NOT NULL,
    sync_type ENUM('manual', 'webhook', 'scheduled') NOT NULL,
    operation ENUM('fetch_products', 'fetch_sales', 'webhook_received') NOT NULL,
    status ENUM('success', 'error', 'partial') NOT NULL,
    records_processed INT DEFAULT 0,
    records_created INT DEFAULT 0,
    records_updated INT DEFAULT 0,
    records_errors INT DEFAULT 0,
    error_message TEXT NULL,
    processing_time_ms INT NULL,
    metadata_json JSON NULL, -- Dados extras do sync
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (integration_id) REFERENCES integrations(id) ON DELETE CASCADE,
    INDEX idx_integration (integration_id),
    INDEX idx_sync_type (sync_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- ================================
-- TABELA: webhook_events (eventos de webhook recebidos)
-- ================================
CREATE TABLE webhook_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    integration_id INT NULL,
    platform ENUM('hotmart', 'monetizze', 'eduzz', 'braip') NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    external_id VARCHAR(100) NULL,
    payload_json JSON NOT NULL,
    processed BOOLEAN DEFAULT FALSE,
    processing_error TEXT NULL,
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    
    FOREIGN KEY (integration_id) REFERENCES integrations(id) ON DELETE SET NULL,
    INDEX idx_integration (integration_id),
    INDEX idx_platform (platform),
    INDEX idx_event_type (event_type),
    INDEX idx_processed (processed),
    INDEX idx_received_at (received_at)
);

-- ================================
-- INSERÇÃO DE DADOS INICIAIS
-- ================================

-- Exemplos de configurações por plataforma
INSERT INTO webhook_events (platform, event_type, payload_json, processed) VALUES 
('hotmart', 'PURCHASE_COMPLETE', '{"test": true, "description": "Evento de exemplo"}', TRUE),
('monetizze', 'sale_approved', '{"test": true, "description": "Evento de exemplo"}', TRUE),
('eduzz', 'sale_completed', '{"test": true, "description": "Evento de exemplo"}', TRUE),
('braip', 'purchase_approved', '{"test": true, "description": "Evento de exemplo"}', TRUE);

-- ================================
-- VIEWS ÚTEIS PARA RELATÓRIOS
-- ================================

-- View de vendas consolidadas por usuário
CREATE VIEW user_sales_summary AS
SELECT 
    u.id as user_id,
    u.name as user_name,
    i.platform,
    COUNT(s.id) as total_sales,
    SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved_sales,
    SUM(CASE WHEN s.status = 'approved' THEN s.amount ELSE 0 END) as total_revenue,
    SUM(CASE WHEN s.status = 'approved' THEN s.commission_amount ELSE 0 END) as total_commission,
    MAX(s.conversion_date) as last_sale_date
FROM users u
LEFT JOIN integrations i ON u.id = i.user_id
LEFT JOIN sales s ON i.id = s.integration_id
WHERE i.status = 'active'
GROUP BY u.id, i.platform;

-- View de performance por produto
CREATE VIEW product_performance AS
SELECT 
    p.id as product_id,
    p.name as product_name,
    i.platform,
    COUNT(s.id) as total_sales,
    SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved_sales,
    SUM(CASE WHEN s.status = 'approved' THEN s.amount ELSE 0 END) as total_revenue,
    AVG(CASE WHEN s.status = 'approved' THEN s.amount ELSE NULL END) as avg_ticket,
    MAX(s.conversion_date) as last_sale_date
FROM products p
LEFT JOIN sales s ON p.id = s.product_id
LEFT JOIN integrations i ON p.integration_id = i.id
GROUP BY p.id;