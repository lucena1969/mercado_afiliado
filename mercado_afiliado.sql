-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 26/08/2025 às 01:22
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `mercado_afiliado`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `bridge_logs`
--

CREATE TABLE `bridge_logs` (
  `id` bigint(20) NOT NULL,
  `pixel_event_id` bigint(20) DEFAULT NULL,
  `platform` enum('facebook','google','tiktok','other') NOT NULL,
  `payload_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload_json`)),
  `response_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_json`)),
  `status` enum('pending','sent','failed','retry') DEFAULT 'pending',
  `http_status_code` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `retry_count` int(11) DEFAULT 0,
  `max_retries` int(11) DEFAULT 3,
  `next_retry_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `integrations`
--

CREATE TABLE `integrations` (
  `id` int(11) NOT NULL,
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
  `config_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `integrations`
--

INSERT INTO `integrations` (`id`, `user_id`, `platform`, `name`, `api_key`, `api_secret`, `webhook_token`, `webhook_url`, `status`, `last_sync_at`, `last_error`, `config_json`, `created_at`, `updated_at`) VALUES
(1, 1, 'hotmart', 'Teste Hotmart', 'test_key_123', 'test_secret_456', '3b3a49976a535b59cddf5546a2732b48d949316e13340ec71336c61e9743e224', NULL, 'pending', NULL, NULL, '{\"created_via\":\"manual\"}', '2025-08-22 20:44:05', '2025-08-22 20:44:05'),
(3, 1, 'monetizze', 'Teste Hotmart', 'test_key_123', '', '0c6baf30efafe2f4b7f765749ce3654d20c2658d79f1edf050ed6bfa1adab31c', NULL, 'pending', NULL, NULL, '{\"created_via\":\"manual\"}', '2025-08-24 21:50:06', '2025-08-24 21:50:06');

-- --------------------------------------------------------

--
-- Estrutura para tabela `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subscription_id` int(11) DEFAULT NULL,
  `external_id` varchar(255) DEFAULT NULL,
  `gateway` enum('mercadopago','pix','stripe','pagseguro') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'BRL',
  `status` enum('pending','completed','failed','cancelled','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pixel_configurations`
--

CREATE TABLE `pixel_configurations` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `pixel_configurations`
--

INSERT INTO `pixel_configurations` (`id`, `user_id`, `integration_id`, `pixel_name`, `facebook_pixel_id`, `facebook_access_token`, `facebook_test_event_code`, `google_conversion_id`, `google_conversion_label`, `google_developer_token`, `google_refresh_token`, `tiktok_pixel_code`, `tiktok_access_token`, `tiktok_advertiser_id`, `auto_track_pageviews`, `auto_track_clicks`, `consent_mode`, `data_retention_days`, `custom_domain`, `custom_script_url`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'Pixel Principal', 'EXAMPLE_PIXEL_ID', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'required', 365, NULL, NULL, 'inactive', '2025-08-23 14:32:19', '2025-08-23 15:11:37'),
(2, 1, NULL, 'pixel teste', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 'required', 365, NULL, NULL, 'testing', '2025-08-23 15:11:37', '2025-08-23 15:11:37');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pixel_events`
--

CREATE TABLE `pixel_events` (
  `id` bigint(20) NOT NULL,
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
  `user_data_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`user_data_json`)),
  `custom_data_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_data_json`)),
  `consent_status` enum('granted','denied') DEFAULT 'granted',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `pixel_events`
--

INSERT INTO `pixel_events` (`id`, `event_name`, `event_time`, `event_id`, `user_id`, `integration_id`, `product_id`, `source_url`, `referrer_url`, `utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term`, `user_data_json`, `custom_data_json`, `consent_status`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 'page_view', 1755959539, 'test_page_view_1', NULL, NULL, NULL, 'https://example.com', NULL, NULL, NULL, 'campaign_test', NULL, NULL, NULL, '{\"test\": true}', 'granted', NULL, NULL, '2025-08-23 14:32:19'),
(2, 'lead', 1755959539, 'test_lead_1', NULL, NULL, NULL, 'https://example.com/lead', NULL, NULL, NULL, 'campaign_test', NULL, NULL, NULL, '{\"email\": \"test@example.com\"}', 'granted', NULL, NULL, '2025-08-23 14:32:19'),
(3, 'purchase', 1755959539, 'test_purchase_1', NULL, NULL, NULL, 'https://example.com/checkout', NULL, NULL, NULL, 'campaign_test', NULL, NULL, NULL, '{\"value\": 197.00, \"currency\": \"BRL\", \"order_id\": \"TEST-001\"}', 'granted', NULL, NULL, '2025-08-23 14:32:19'),
(4, '', 1755959965, 'test_1755959965', NULL, NULL, NULL, 'http://localhost/test', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'granted', NULL, NULL, '2025-08-23 14:39:25'),
(5, '', 1755960538, 'test_1755960538', NULL, NULL, NULL, 'http://localhost/test', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'granted', NULL, NULL, '2025-08-23 14:48:58'),
(6, '', 1755960801, 'test_1755960801', NULL, NULL, NULL, 'http://localhost/test', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'granted', NULL, NULL, '2025-08-23 14:53:21');

-- --------------------------------------------------------

--
-- Estrutura para tabela `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `integration_id` int(11) NOT NULL,
  `external_id` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'BRL',
  `commission_percentage` decimal(5,2) DEFAULT NULL,
  `status` enum('active','inactive','archived') DEFAULT 'active',
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `product_performance`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `product_performance` (
`product_id` int(11)
,`product_name` varchar(255)
,`platform` enum('hotmart','monetizze','eduzz','braip')
,`total_sales` bigint(21)
,`approved_sales` decimal(22,0)
,`total_revenue` decimal(32,2)
,`avg_ticket` decimal(14,6)
,`last_sale_date` timestamp
);

-- --------------------------------------------------------

--
-- Estrutura para tabela `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
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
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `subscription_plans`
--

CREATE TABLE `subscription_plans` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `price_monthly` decimal(10,2) NOT NULL,
  `price_yearly` decimal(10,2) DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`features`)),
  `limits_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`limits_json`)),
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `subscription_plans`
--

INSERT INTO `subscription_plans` (`id`, `name`, `slug`, `description`, `price_monthly`, `price_yearly`, `features`, `limits_json`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Starter', 'starter', 'Ideal para afiliados iniciantes', 79.00, 790.00, '[\"Painel Unificado\", \"IntegraSync (2 redes)\", \"Alertas por e-mail\", \"UTM Templates\"]', '{\"integrations\": 2, \"team_members\": 1, \"link_maestro\": false, \"pixel_br\": false, \"capi_bridge\": false, \"advanced_alerts\": false, \"trial_days\": 14}', 1, 0, '2025-08-22 19:00:56', '2025-08-22 19:00:56'),
(2, 'Pro', 'pro', 'Para afiliados em crescimento', 149.00, 1490.00, '[\"Tudo do Starter\", \"Link Maestro\", \"Pixel BR\", \"Alertas WhatsApp/Telegram\", \"Cohort Reembolso\", \"Offer Radar\"]', '{\"integrations\": 4, \"team_members\": 3, \"link_maestro\": true, \"pixel_br\": true, \"capi_bridge\": false, \"advanced_alerts\": true, \"trial_days\": 14}', 1, 0, '2025-08-22 19:00:56', '2025-08-22 19:00:56'),
(3, 'Scale', 'scale', 'Para operações grandes e equipes', 299.00, 2990.00, '[\"Tudo do Pro\", \"CAPI Bridge\", \"Equipe ilimitada\", \"Auditoria LGPD\", \"Suporte prioritário\"]', '{\"integrations\": 999, \"team_members\": 999, \"link_maestro\": true, \"pixel_br\": true, \"capi_bridge\": true, \"advanced_alerts\": true, \"trial_days\": 14}', 1, 0, '2025-08-22 19:00:56', '2025-08-22 19:00:56');

-- --------------------------------------------------------

--
-- Estrutura para tabela `sync_logs`
--

CREATE TABLE `sync_logs` (
  `id` int(11) NOT NULL,
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
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `uuid`, `name`, `email`, `password`, `phone`, `avatar`, `email_verified_at`, `status`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, '4e9b11d3-152f-414d-9157-64e704005f58', 'ONESIO LUCENA NETO', 'lucena1969@gmail.com', '$2y$10$Vx0/zIyr08yRC6ffVRNc..AazN68LQvI/TYB6PBj6t/2bKQWR1K1S', '61999163260', NULL, NULL, 'active', '2025-08-24 21:33:43', '2025-08-22 19:29:00', '2025-08-24 21:33:43');

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `user_pixel_summary`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `user_pixel_summary` (
`user_id` int(11)
,`user_name` varchar(255)
,`total_events` bigint(21)
,`page_views` decimal(22,0)
,`leads` decimal(22,0)
,`purchases` decimal(22,0)
,`consented_events` decimal(22,0)
,`last_event_date` timestamp
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `user_sales_summary`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `user_sales_summary` (
`user_id` int(11)
,`user_name` varchar(255)
,`platform` enum('hotmart','monetizze','eduzz','braip')
,`total_sales` bigint(21)
,`approved_sales` decimal(22,0)
,`total_revenue` decimal(32,2)
,`total_commission` decimal(32,2)
,`last_sale_date` timestamp
);

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_subscriptions`
--

CREATE TABLE `user_subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `status` enum('active','cancelled','expired','trial','payment_pending') DEFAULT 'trial',
  `billing_cycle` enum('monthly','yearly') DEFAULT 'monthly',
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `starts_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ends_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `auto_renew` tinyint(1) DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `user_subscriptions`
--

INSERT INTO `user_subscriptions` (`id`, `user_id`, `plan_id`, `status`, `billing_cycle`, `trial_ends_at`, `starts_at`, `ends_at`, `cancelled_at`, `auto_renew`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'trial', 'monthly', '2025-09-05 19:29:00', '2025-08-22 19:29:00', NULL, NULL, 1, NULL, '2025-08-22 19:29:00', '2025-08-22 19:29:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_team_members`
--

CREATE TABLE `user_team_members` (
  `id` int(11) NOT NULL,
  `owner_user_id` int(11) NOT NULL,
  `member_user_id` int(11) NOT NULL,
  `role` enum('admin','manager','analyst','viewer') DEFAULT 'viewer',
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `status` enum('pending','active','inactive') DEFAULT 'pending',
  `invited_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `accepted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `webhook_events`
--

CREATE TABLE `webhook_events` (
  `id` int(11) NOT NULL,
  `integration_id` int(11) DEFAULT NULL,
  `platform` enum('hotmart','monetizze','eduzz','braip') NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `external_id` varchar(100) DEFAULT NULL,
  `payload_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload_json`)),
  `processed` tinyint(1) DEFAULT 0,
  `processing_error` text DEFAULT NULL,
  `received_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `webhook_events`
--

INSERT INTO `webhook_events` (`id`, `integration_id`, `platform`, `event_type`, `external_id`, `payload_json`, `processed`, `processing_error`, `received_at`, `processed_at`) VALUES
(1, NULL, 'hotmart', 'PURCHASE_COMPLETE', NULL, '{\"test\": true, \"description\": \"Evento de exemplo\"}', 1, NULL, '2025-08-22 19:36:35', NULL),
(2, NULL, 'monetizze', 'sale_approved', NULL, '{\"test\": true, \"description\": \"Evento de exemplo\"}', 1, NULL, '2025-08-22 19:36:35', NULL),
(3, NULL, 'eduzz', 'sale_completed', NULL, '{\"test\": true, \"description\": \"Evento de exemplo\"}', 1, NULL, '2025-08-22 19:36:35', NULL),
(4, NULL, 'braip', 'purchase_approved', NULL, '{\"test\": true, \"description\": \"Evento de exemplo\"}', 1, NULL, '2025-08-22 19:36:35', NULL);

-- --------------------------------------------------------

--
-- Estrutura para view `product_performance`
--
DROP TABLE IF EXISTS `product_performance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `product_performance`  AS SELECT `p`.`id` AS `product_id`, `p`.`name` AS `product_name`, `i`.`platform` AS `platform`, count(`s`.`id`) AS `total_sales`, sum(case when `s`.`status` = 'approved' then 1 else 0 end) AS `approved_sales`, sum(case when `s`.`status` = 'approved' then `s`.`amount` else 0 end) AS `total_revenue`, avg(case when `s`.`status` = 'approved' then `s`.`amount` else NULL end) AS `avg_ticket`, max(`s`.`conversion_date`) AS `last_sale_date` FROM ((`products` `p` left join `sales` `s` on(`p`.`id` = `s`.`product_id`)) left join `integrations` `i` on(`p`.`integration_id` = `i`.`id`)) GROUP BY `p`.`id` ;

-- --------------------------------------------------------

--
-- Estrutura para view `user_pixel_summary`
--
DROP TABLE IF EXISTS `user_pixel_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_pixel_summary`  AS SELECT `u`.`id` AS `user_id`, `u`.`name` AS `user_name`, count(`pe`.`id`) AS `total_events`, sum(case when `pe`.`event_name` = 'page_view' then 1 else 0 end) AS `page_views`, sum(case when `pe`.`event_name` = 'lead' then 1 else 0 end) AS `leads`, sum(case when `pe`.`event_name` = 'purchase' then 1 else 0 end) AS `purchases`, sum(case when `pe`.`consent_status` = 'granted' then 1 else 0 end) AS `consented_events`, max(`pe`.`created_at`) AS `last_event_date` FROM (`users` `u` left join `pixel_events` `pe` on(`u`.`id` = `pe`.`user_id`)) GROUP BY `u`.`id` ;

-- --------------------------------------------------------

--
-- Estrutura para view `user_sales_summary`
--
DROP TABLE IF EXISTS `user_sales_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_sales_summary`  AS SELECT `u`.`id` AS `user_id`, `u`.`name` AS `user_name`, `i`.`platform` AS `platform`, count(`s`.`id`) AS `total_sales`, sum(case when `s`.`status` = 'approved' then 1 else 0 end) AS `approved_sales`, sum(case when `s`.`status` = 'approved' then `s`.`amount` else 0 end) AS `total_revenue`, sum(case when `s`.`status` = 'approved' then `s`.`commission_amount` else 0 end) AS `total_commission`, max(`s`.`conversion_date`) AS `last_sale_date` FROM ((`users` `u` left join `integrations` `i` on(`u`.`id` = `i`.`user_id`)) left join `sales` `s` on(`i`.`id` = `s`.`integration_id`)) WHERE `i`.`status` = 'active' GROUP BY `u`.`id`, `i`.`platform` ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `bridge_logs`
--
ALTER TABLE `bridge_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pixel_event_id` (`pixel_event_id`),
  ADD KEY `idx_platform` (`platform`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_retry` (`next_retry_at`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Índices de tabela `integrations`
--
ALTER TABLE `integrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_platform` (`user_id`,`platform`),
  ADD KEY `idx_user_platform` (`user_id`,`platform`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_last_sync` (`last_sync_at`);

--
-- Índices de tabela `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscription_id` (`subscription_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_gateway` (`gateway`),
  ADD KEY `idx_external_id` (`external_id`),
  ADD KEY `idx_payments_created_at` (`created_at`);

--
-- Índices de tabela `pixel_configurations`
--
ALTER TABLE `pixel_configurations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_integration_id` (`integration_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `pixel_events`
--
ALTER TABLE `pixel_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_id` (`event_id`),
  ADD KEY `idx_event_name` (`event_name`),
  ADD KEY `idx_event_time` (`event_time`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_integration_id` (`integration_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_consent` (`consent_status`),
  ADD KEY `idx_utm_source` (`utm_source`),
  ADD KEY `idx_utm_campaign` (`utm_campaign`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Índices de tabela `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_integration_external` (`integration_id`,`external_id`),
  ADD KEY `idx_integration` (`integration_id`),
  ADD KEY `idx_external_id` (`external_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_integration_external_sale` (`integration_id`,`external_sale_id`),
  ADD KEY `idx_integration` (`integration_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_conversion_date` (`conversion_date`),
  ADD KEY `idx_approval_date` (`approval_date`),
  ADD KEY `idx_customer_email` (`customer_email`),
  ADD KEY `idx_utm_source` (`utm_source`),
  ADD KEY `idx_utm_campaign` (`utm_campaign`);

--
-- Índices de tabela `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_active` (`is_active`);

--
-- Índices de tabela `sync_logs`
--
ALTER TABLE `sync_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_integration` (`integration_id`),
  ADD KEY `idx_sync_type` (`sync_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_uuid` (`uuid`),
  ADD KEY `idx_users_created_at` (`created_at`);

--
-- Índices de tabela `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_ends_at` (`ends_at`),
  ADD KEY `idx_subscriptions_created_at` (`created_at`);

--
-- Índices de tabela `user_team_members`
--
ALTER TABLE `user_team_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_team_member` (`owner_user_id`,`member_user_id`),
  ADD KEY `idx_owner` (`owner_user_id`),
  ADD KEY `idx_member` (`member_user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `webhook_events`
--
ALTER TABLE `webhook_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_integration` (`integration_id`),
  ADD KEY `idx_platform` (`platform`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_processed` (`processed`),
  ADD KEY `idx_received_at` (`received_at`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `bridge_logs`
--
ALTER TABLE `bridge_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `integrations`
--
ALTER TABLE `integrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pixel_configurations`
--
ALTER TABLE `pixel_configurations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `pixel_events`
--
ALTER TABLE `pixel_events`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `sync_logs`
--
ALTER TABLE `sync_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `user_team_members`
--
ALTER TABLE `user_team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `webhook_events`
--
ALTER TABLE `webhook_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `bridge_logs`
--
ALTER TABLE `bridge_logs`
  ADD CONSTRAINT `bridge_logs_ibfk_1` FOREIGN KEY (`pixel_event_id`) REFERENCES `pixel_events` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `integrations`
--
ALTER TABLE `integrations`
  ADD CONSTRAINT `integrations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`subscription_id`) REFERENCES `user_subscriptions` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `pixel_configurations`
--
ALTER TABLE `pixel_configurations`
  ADD CONSTRAINT `pixel_configurations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pixel_configurations_ibfk_2` FOREIGN KEY (`integration_id`) REFERENCES `integrations` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `pixel_events`
--
ALTER TABLE `pixel_events`
  ADD CONSTRAINT `pixel_events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pixel_events_ibfk_2` FOREIGN KEY (`integration_id`) REFERENCES `integrations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pixel_events_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`integration_id`) REFERENCES `integrations` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`integration_id`) REFERENCES `integrations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `sync_logs`
--
ALTER TABLE `sync_logs`
  ADD CONSTRAINT `sync_logs_ibfk_1` FOREIGN KEY (`integration_id`) REFERENCES `integrations` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  ADD CONSTRAINT `user_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_subscriptions_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`);

--
-- Restrições para tabelas `user_team_members`
--
ALTER TABLE `user_team_members`
  ADD CONSTRAINT `user_team_members_ibfk_1` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_team_members_ibfk_2` FOREIGN KEY (`member_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `webhook_events`
--
ALTER TABLE `webhook_events`
  ADD CONSTRAINT `webhook_events_ibfk_1` FOREIGN KEY (`integration_id`) REFERENCES `integrations` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
