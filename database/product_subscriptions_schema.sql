-- ================================
-- PRODUCT SUBSCRIPTIONS - Assinaturas de Produtos das Integrações
-- Sistema para gerenciar assinaturas vindas das redes de afiliados
-- ================================

-- ================================================
-- TABELA: product_subscriptions (assinaturas de produtos das redes)
-- ================================================
CREATE TABLE `product_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `integration_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  
  -- IDs externos da rede
  `external_subscription_id` varchar(100) NOT NULL,
  `external_subscriber_code` varchar(100) NOT NULL,
  `external_plan_id` varchar(100) DEFAULT NULL,
  
  -- Dados do assinante
  `subscriber_name` varchar(255) DEFAULT NULL,
  `subscriber_email` varchar(255) DEFAULT NULL,
  `subscriber_phone_ddd` varchar(3) DEFAULT NULL,
  `subscriber_phone_number` varchar(15) DEFAULT NULL,
  `subscriber_cell_ddd` varchar(3) DEFAULT NULL,
  `subscriber_cell_number` varchar(15) DEFAULT NULL,
  
  -- Dados da assinatura
  `plan_name` varchar(255) DEFAULT NULL,
  `status` enum('active','cancelled','expired','overdue','inactive','started') NOT NULL DEFAULT 'active',
  `actual_recurrence_value` decimal(10,2) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'BRL',
  
  -- Datas importantes
  `cancellation_date` timestamp NULL DEFAULT NULL,
  `date_next_charge` timestamp NULL DEFAULT NULL,
  `subscription_start_date` timestamp NULL DEFAULT NULL,
  
  -- Metadados
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata_json`)),
  
  -- Controle
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_integration_subscription` (`integration_id`, `external_subscription_id`),
  KEY `idx_integration` (`integration_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_subscriber_email` (`subscriber_email`),
  KEY `idx_subscriber_code` (`external_subscriber_code`),
  KEY `idx_status` (`status`),
  KEY `idx_cancellation_date` (`cancellation_date`),
  KEY `idx_next_charge` (`date_next_charge`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- TABELA: subscription_events (histórico de eventos das assinaturas)
-- ================================================
CREATE TABLE `subscription_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(11) NOT NULL,
  `event_type` enum('created','cancelled','reactivated','plan_changed','payment_failed','payment_success') NOT NULL,
  `event_date` timestamp NOT NULL,
  `previous_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `details_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  
  PRIMARY KEY (`id`),
  KEY `idx_subscription` (`subscription_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_event_date` (`event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;