-- ================================
-- PIXEL BR - ESTRUTURA DO BANCO
-- Sistema de tracking de eventos
-- ================================

USE mercado_afiliado;

-- ================================
-- TABELA: pixel_events (eventos coletados pelo pixel)
-- ================================
CREATE TABLE pixel_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    event_name ENUM('page_view', 'click', 'lead', 'purchase', 'custom') NOT NULL,
    event_time INT UNSIGNED NOT NULL,
    event_id VARCHAR(100) NOT NULL UNIQUE,
    user_id INT NULL,
    integration_id INT NULL,
    product_id INT NULL,
    source_url TEXT NULL,
    referrer_url TEXT NULL,
    
    -- UTM Parameters
    utm_source VARCHAR(255) NULL,
    utm_medium VARCHAR(255) NULL,
    utm_campaign VARCHAR(255) NULL,
    utm_content VARCHAR(255) NULL,
    utm_term VARCHAR(255) NULL,
    
    -- User Data (JSON for flexibility)
    user_data_json JSON NULL,
    custom_data_json JSON NULL,
    
    -- LGPD Compliance
    consent_status ENUM('granted', 'denied') DEFAULT 'granted',
    
    -- Technical Data
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (integration_id) REFERENCES integrations(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    
    -- Indexes for Performance
    INDEX idx_event_name (event_name),
    INDEX idx_event_time (event_time),
    INDEX idx_user_id (user_id),
    INDEX idx_integration_id (integration_id),
    INDEX idx_product_id (product_id),
    INDEX idx_consent (consent_status),
    INDEX idx_utm_source (utm_source),
    INDEX idx_utm_campaign (utm_campaign),
    INDEX idx_created_at (created_at)
);

-- ================================
-- TABELA: bridge_logs (logs de envio para plataformas externas)
-- ================================
CREATE TABLE bridge_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    pixel_event_id BIGINT NULL,
    platform ENUM('facebook', 'google', 'tiktok', 'other') NOT NULL,
    payload_json JSON NOT NULL,
    response_json JSON NULL,
    status ENUM('pending', 'sent', 'failed', 'retry') DEFAULT 'pending',
    http_status_code INT NULL,
    error_message TEXT NULL,
    retry_count INT DEFAULT 0,
    max_retries INT DEFAULT 3,
    next_retry_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (pixel_event_id) REFERENCES pixel_events(id) ON DELETE CASCADE,
    
    INDEX idx_platform (platform),
    INDEX idx_status (status),
    INDEX idx_retry (next_retry_at),
    INDEX idx_created_at (created_at)
);

-- ================================
-- TABELA: pixel_configurations (configurações do pixel por usuário)
-- ================================
CREATE TABLE pixel_configurations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    integration_id INT NULL,
    pixel_name VARCHAR(255) NOT NULL,
    
    -- Facebook/Meta Configuration
    facebook_pixel_id VARCHAR(100) NULL,
    facebook_access_token TEXT NULL,
    facebook_test_event_code VARCHAR(100) NULL,
    
    -- Google Configuration  
    google_conversion_id VARCHAR(100) NULL,
    google_conversion_label VARCHAR(100) NULL,
    google_developer_token TEXT NULL,
    google_refresh_token TEXT NULL,
    
    -- TikTok Configuration
    tiktok_pixel_code VARCHAR(100) NULL,
    tiktok_access_token TEXT NULL,
    tiktok_advertiser_id VARCHAR(100) NULL,
    
    -- General Settings
    auto_track_pageviews BOOLEAN DEFAULT TRUE,
    auto_track_clicks BOOLEAN DEFAULT FALSE,
    consent_mode ENUM('required', 'optional') DEFAULT 'required',
    data_retention_days INT DEFAULT 365,
    
    -- Custom Domain (for advanced users)
    custom_domain VARCHAR(255) NULL,
    custom_script_url TEXT NULL,
    
    -- Status
    status ENUM('active', 'inactive', 'testing') DEFAULT 'inactive',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (integration_id) REFERENCES integrations(id) ON DELETE SET NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_integration_id (integration_id),
    INDEX idx_status (status)
);

-- ================================
-- VIEWS ÚTEIS PARA RELATÓRIOS
-- ================================

-- View de eventos consolidados por usuário
CREATE VIEW user_pixel_summary AS
SELECT 
    u.id as user_id,
    u.name as user_name,
    COUNT(pe.id) as total_events,
    SUM(CASE WHEN pe.event_name = 'page_view' THEN 1 ELSE 0 END) as page_views,
    SUM(CASE WHEN pe.event_name = 'lead' THEN 1 ELSE 0 END) as leads,
    SUM(CASE WHEN pe.event_name = 'purchase' THEN 1 ELSE 0 END) as purchases,
    SUM(CASE WHEN pe.consent_status = 'granted' THEN 1 ELSE 0 END) as consented_events,
    MAX(pe.created_at) as last_event_date
FROM users u
LEFT JOIN pixel_events pe ON u.id = pe.user_id
GROUP BY u.id;

-- View de performance do pixel por configuração
CREATE VIEW pixel_performance AS
SELECT 
    pc.id as config_id,
    pc.pixel_name,
    pc.user_id,
    u.name as user_name,
    COUNT(pe.id) as total_events,
    COUNT(DISTINCT pe.utm_campaign) as unique_campaigns,
    AVG(CASE WHEN pe.event_name = 'purchase' AND JSON_EXTRACT(pe.custom_data_json, '$.value') IS NOT NULL 
        THEN CAST(JSON_EXTRACT(pe.custom_data_json, '$.value') AS DECIMAL(10,2)) 
        ELSE NULL END) as avg_purchase_value,
    (SELECT COUNT(*) FROM bridge_logs bl 
     JOIN pixel_events pe2 ON bl.pixel_event_id = pe2.id 
     WHERE pe2.user_id = pc.user_id AND bl.status = 'sent') as successful_bridge_sends,
    MAX(pe.created_at) as last_event_date
FROM pixel_configurations pc
LEFT JOIN users u ON pc.user_id = u.id
LEFT JOIN pixel_events pe ON pe.user_id = pc.user_id
WHERE pc.status = 'active'
GROUP BY pc.id;

-- View de eventos por UTM para análise de campanhas
CREATE VIEW utm_performance AS
SELECT 
    pe.utm_source,
    pe.utm_medium,
    pe.utm_campaign,
    COUNT(*) as total_events,
    COUNT(DISTINCT pe.user_id) as unique_users,
    SUM(CASE WHEN pe.event_name = 'purchase' THEN 1 ELSE 0 END) as purchases,
    SUM(CASE WHEN pe.event_name = 'purchase' AND JSON_EXTRACT(pe.custom_data_json, '$.value') IS NOT NULL
        THEN CAST(JSON_EXTRACT(pe.custom_data_json, '$.value') AS DECIMAL(10,2))
        ELSE 0 END) as total_revenue,
    MIN(pe.created_at) as first_event,
    MAX(pe.created_at) as last_event
FROM pixel_events pe
WHERE pe.utm_campaign IS NOT NULL
GROUP BY pe.utm_source, pe.utm_medium, pe.utm_campaign;

-- ================================
-- INSERÇÃO DE DADOS INICIAIS
-- ================================

-- Exemplo de configuração de pixel
INSERT INTO pixel_configurations (user_id, pixel_name, status, facebook_pixel_id) 
VALUES (1, 'Pixel Principal', 'testing', 'EXAMPLE_PIXEL_ID');

-- Eventos de exemplo para testes
INSERT INTO pixel_events (event_name, event_time, event_id, source_url, utm_campaign, custom_data_json) VALUES 
('page_view', UNIX_TIMESTAMP(), 'test_page_view_1', 'https://example.com', 'campaign_test', '{"test": true}'),
('lead', UNIX_TIMESTAMP(), 'test_lead_1', 'https://example.com/lead', 'campaign_test', '{"email": "test@example.com"}'),
('purchase', UNIX_TIMESTAMP(), 'test_purchase_1', 'https://example.com/checkout', 'campaign_test', '{"value": 197.00, "currency": "BRL", "order_id": "TEST-001"}');