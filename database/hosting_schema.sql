-- Schema limpo para servidor de hospedagem
-- Database: u590097272_mercado_afilia
-- Removidos comandos que requerem privilégios SUPER

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Estrutura para tabela `bridge_logs`
CREATE TABLE `bridge_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pixel_event_id` bigint(20) DEFAULT NULL,
  `platform` enum('facebook','google','tiktok','other') NOT NULL,
  `payload_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `response_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `status` enum('pending','sent','failed','retry') DEFAULT 'pending',
  `http_status_code` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `retry_count` int(11) DEFAULT 0,
  `max_retries` int(11) DEFAULT 3,
  `next_retry_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `pixel_event_id` (`pixel_event_id`),
  KEY `idx_platform` (`platform`),
  KEY `idx_status` (`status`),
  KEY `idx_retry` (`next_retry_at`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estrutura para tabela `integrations`
CREATE TABLE `integrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `platform` enum('hotmart','monetizze','eduzz','braip') NOT NULL,
  `name` varchar(255) NOT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `api_secret` varchar(255) DEFAULT NULL,
  `webhook_token` varchar(255) DEFAULT NULL,
  `webhook_url` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','error','pending') DEFAULT 'pending',
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `config_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_platform` (`user_id`,`platform`),
  KEY `idx_user_platform` (`user_id`,`platform`),
  KEY `idx_status` (`status`),
  KEY `idx_last_sync` (`last_sync_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estrutura para tabela `payments`
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `subscription_id` int(11) DEFAULT NULL,
  `external_id` varchar(255) DEFAULT NULL,
  `gateway` enum('mercadopago','pix','stripe','pagseguro') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'BRL',
  `status` enum('pending','completed','failed','cancelled','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `subscription_id` (`subscription_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_gateway` (`gateway`),
  KEY `idx_external_id` (`external_id`),
  KEY `idx_payments_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estrutura para tabela `pixel_configurations`
CREATE TABLE `pixel_configurations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `integration_id` int(11) DEFAULT NULL,
  `pixel_name` varchar(255) NOT NULL,
  `facebook_pixel_id` varchar(100) DEFAULT NULL,
  `facebook_access_token` text DEFAULT NULL,
  `facebook_test_event_code` varchar(100) DEFAULT NULL,
  `google_conversion_id` varchar(100) DEFAULT NULL,
  `google_conversion_label` varchar(100) DEFAULT NULL,
  `google_developer_token` text DEFAULT NULL,
  `google_refresh_token` text DEFAULT NULL,
  `tiktok_pixel_code` varchar(100) DEFAULT NULL,
  `tiktok_access_token` text DEFAULT NULL,
  `tiktok_advertiser_id` varchar(100) DEFAULT NULL,
  `auto_track_pageviews` tinyint(1) DEFAULT 1,
  `auto_track_clicks` tinyint(1) DEFAULT 0,
  `consent_mode` enum('required','optional') DEFAULT 'required',
  `data_retention_days` int(11) DEFAULT 365,
  `custom_domain` varchar(255) DEFAULT NULL,
  `custom_script_url` text DEFAULT NULL,
  `status` enum('active','inactive','testing') DEFAULT 'inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_integration_id` (`integration_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estrutura para tabela `pixel_events`
CREATE TABLE `pixel_events` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `event_name` enum('page_view','click','lead','purchase','custom') NOT NULL,
  `event_time` int(10) UNSIGNED NOT NULL,
  `event_id` varchar(100) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `integration_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `source_url` text DEFAULT NULL,
  `referrer_url` text DEFAULT NULL,
  `utm_source` varchar(255) DEFAULT NULL,
  `utm_medium` varchar(255) DEFAULT NULL,
  `utm_campaign` varchar(255) DEFAULT NULL,
  `utm_content` varchar(255) DEFAULT NULL,
  `utm_term` varchar(255) DEFAULT NULL,
  `user_data_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `custom_data_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `consent_status` enum('granted','denied') DEFAULT 'granted',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_id` (`event_id`),
  KEY `idx_event_name` (`event_name`),
  KEY `idx_event_time` (`event_time`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_integration_id` (`integration_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_consent` (`consent_status`),
  KEY `idx_utm_source` (`utm_source`),
  KEY `idx_utm_campaign` (`utm_campaign`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estrutura para tabela `products`
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `integration_id` int(11) NOT NULL,
  `external_id` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'BRL',
  `commission_percentage` decimal(5,2) DEFAULT NULL,
  `status` enum('active','inactive','archived') DEFAULT 'active',
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_integration_external` (`integration_id`,`external_id`),
  KEY `idx_integration` (`integration_id`),
  KEY `idx_external_id` (`external_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estrutura para tabela `sales`
CREATE TABLE `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `integration_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `external_sale_id` varchar(100) NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_document` varchar(20) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `commission_amount` decimal(10,2) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'BRL',
  `status` enum('approved','pending','cancelled','refunded','chargeback') NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `utm_source` varchar(100) DEFAULT NULL,
  `utm_medium` varchar(100) DEFAULT NULL,
  `utm_campaign` varchar(100) DEFAULT NULL,
  `utm_content` varchar(100) DEFAULT NULL,
  `utm_term` varchar(100) DEFAULT NULL,
  `conversion_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approval_date` timestamp NULL DEFAULT NULL,
  `refund_date` timestamp NULL DEFAULT NULL,
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_integration_external_sale` (`integration_id`,`external_sale_id`),
  KEY `idx_integration` (`integration_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_status` (`status`),
  KEY `idx_conversion_date` (`conversion_date`),
  KEY `idx_approval_date` (`approval_date`),
  KEY `idx_customer_email` (`customer_email`),
  KEY `idx_utm_source` (`utm_source`),
  KEY `idx_utm_campaign` (`utm_campaign`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estrutura para tabela `subscription_plans`
CREATE TABLE `subscription_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `price_monthly` decimal(10,2) NOT NULL,
  `price_yearly` decimal(10,2) DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `limits_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_slug` (`slug`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estrutura para tabela `sync_logs`
CREATE TABLE `sync_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `integration_id` int(11) NOT NULL,
  `sync_type` enum('manual','webhook','scheduled') NOT NULL,
  `operation` enum('fetch_products','fetch_sales','webhook_received') NOT NULL,
  `status` enum('success','error','partial') NOT NULL,
  `records_processed` int(11) DEFAULT 0,
  `records_created` int(11) DEFAULT 0,
  `records_updated` int(11) DEFAULT 0,
  `records_errors` int(11) DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `processing_time_ms` int(11) DEFAULT NULL,
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_integration` (`integration_id`),
  KEY `idx_sync_type` (`sync_type`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estrutura para tabela `users`
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_uuid` (`uuid`),
  KEY `idx_users_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estrutura para tabela `user_subscriptions`
CREATE TABLE `user_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `status` enum('active','cancelled','expired','trial','payment_pending') DEFAULT 'trial',
  `billing_cycle` enum('monthly','yearly') DEFAULT 'monthly',
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `starts_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ends_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `auto_renew` tinyint(1) DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `plan_id` (`plan_id`),
  KEY `idx_user_status` (`user_id`,`status`),
  KEY `idx_status` (`status`),
  KEY `idx_ends_at` (`ends_at`),
  KEY `idx_subscriptions_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estrutura para tabela `user_team_members`
CREATE TABLE `user_team_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_user_id` int(11) NOT NULL,
  `member_user_id` int(11) NOT NULL,
  `role` enum('admin','manager','analyst','viewer') DEFAULT 'viewer',
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `status` enum('pending','active','inactive') DEFAULT 'pending',
  `invited_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `accepted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_team_member` (`owner_user_id`,`member_user_id`),
  KEY `idx_owner` (`owner_user_id`),
  KEY `idx_member` (`member_user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estrutura para tabela `webhook_events`
CREATE TABLE `webhook_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `integration_id` int(11) DEFAULT NULL,
  `platform` enum('hotmart','monetizze','eduzz','braip') NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `external_id` varchar(100) DEFAULT NULL,
  `payload_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `processed` tinyint(1) DEFAULT 0,
  `processing_error` text DEFAULT NULL,
  `received_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_integration` (`integration_id`),
  KEY `idx_platform` (`platform`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_processed` (`processed`),
  KEY `idx_received_at` (`received_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir dados essenciais
INSERT INTO `subscription_plans` (`id`, `name`, `slug`, `description`, `price_monthly`, `price_yearly`, `features`, `limits_json`, `is_active`, `sort_order`) VALUES
(1, 'Starter', 'starter', 'Ideal para afiliados iniciantes', 79.00, 790.00, '[\"Painel Unificado\", \"IntegraSync (2 redes)\", \"Alertas por e-mail\", \"UTM Templates\"]', '{\"integrations\": 2, \"team_members\": 1, \"link_maestro\": false, \"pixel_br\": false, \"capi_bridge\": false, \"advanced_alerts\": false, \"trial_days\": 14}', 1, 0),
(2, 'Pro', 'pro', 'Para afiliados em crescimento', 149.00, 1490.00, '[\"Tudo do Starter\", \"Link Maestro\", \"Pixel BR\", \"Alertas WhatsApp/Telegram\", \"Cohort Reembolso\", \"Offer Radar\"]', '{\"integrations\": 4, \"team_members\": 3, \"link_maestro\": true, \"pixel_br\": true, \"capi_bridge\": false, \"advanced_alerts\": true, \"trial_days\": 14}', 1, 0),
(3, 'Scale', 'scale', 'Para operações grandes e equipes', 299.00, 2990.00, '[\"Tudo do Pro\", \"CAPI Bridge\", \"Equipe ilimitada\", \"Auditoria LGPD\", \"Suporte prioritário\"]', '{\"integrations\": 999, \"team_members\": 999, \"link_maestro\": true, \"pixel_br\": true, \"capi_bridge\": true, \"advanced_alerts\": true, \"trial_days\": 14}', 1, 0);

-- Adicionar chaves estrangeiras após criação das tabelas
ALTER TABLE `bridge_logs`
  ADD CONSTRAINT `bridge_logs_ibfk_1` FOREIGN KEY (`pixel_event_id`) REFERENCES `pixel_events` (`id`) ON DELETE CASCADE;

ALTER TABLE `integrations`
  ADD CONSTRAINT `integrations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`subscription_id`) REFERENCES `user_subscriptions` (`id`) ON DELETE SET NULL;

ALTER TABLE `pixel_configurations`
  ADD CONSTRAINT `pixel_configurations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pixel_configurations_ibfk_2` FOREIGN KEY (`integration_id`) REFERENCES `integrations` (`id`) ON DELETE SET NULL;

ALTER TABLE `pixel_events`
  ADD CONSTRAINT `pixel_events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pixel_events_ibfk_2` FOREIGN KEY (`integration_id`) REFERENCES `integrations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pixel_events_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`integration_id`) REFERENCES `integrations` (`id`) ON DELETE CASCADE;

ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`integration_id`) REFERENCES `integrations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

ALTER TABLE `sync_logs`
  ADD CONSTRAINT `sync_logs_ibfk_1` FOREIGN KEY (`integration_id`) REFERENCES `integrations` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_subscriptions`
  ADD CONSTRAINT `user_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_subscriptions_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`);

ALTER TABLE `user_team_members`
  ADD CONSTRAINT `user_team_members_ibfk_1` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_team_members_ibfk_2` FOREIGN KEY (`member_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `webhook_events`
  ADD CONSTRAINT `webhook_events_ibfk_1` FOREIGN KEY (`integration_id`) REFERENCES `integrations` (`id`) ON DELETE SET NULL;