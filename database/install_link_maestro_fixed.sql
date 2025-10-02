-- =======================================
-- INSTALAÇÃO LINK MAESTRO - SEM FOREIGN KEYS
-- Execute este arquivo no banco u590097272_mercado_afilia
-- =======================================

USE `u590097272_mercado_afilia`;

-- Verificar se as tabelas já existem
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables 
    WHERE table_schema = 'u590097272_mercado_afilia' AND table_name = 'utm_templates');

-- Só executar se as tabelas não existirem
SET @sql = IF(@table_exists = 0, '
-- Tabela de templates de UTM (sem foreign key)
CREATE TABLE `utm_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT ''Nome do template'',
  `platform` enum(''facebook'',''google'',''tiktok'',''youtube'',''linkedin'',''custom'') NOT NULL DEFAULT ''custom'',
  `description` text DEFAULT NULL COMMENT ''Descrição do template'',
  `utm_source` varchar(255) DEFAULT NULL COMMENT ''Fonte do tráfego'',
  `utm_medium` varchar(255) DEFAULT NULL COMMENT ''Meio/canal'',
  `utm_campaign` varchar(255) DEFAULT NULL COMMENT ''Nome da campanha'',
  `utm_content` varchar(255) DEFAULT NULL COMMENT ''Conteúdo/anúncio'',
  `utm_term` varchar(255) DEFAULT NULL COMMENT ''Termo/palavra-chave'',
  `is_default` tinyint(1) DEFAULT 0 COMMENT ''Se é template padrão'',
  `status` enum(''active'',''inactive'') DEFAULT ''active'',
  `usage_count` int(11) DEFAULT 0 COMMENT ''Quantas vezes foi usado'',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_platform` (`user_id`, `platform`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT=''Templates de UTM para diferentes plataformas'';
', 'SELECT "utm_templates já existe" as status');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Tabela de links encurtados (sem foreign key)
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables 
    WHERE table_schema = 'u590097272_mercado_afilia' AND table_name = 'short_links');

SET @sql = IF(@table_exists = 0, '
CREATE TABLE `short_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `utm_template_id` int(11) DEFAULT NULL COMMENT ''Template usado'',
  `short_code` varchar(20) NOT NULL UNIQUE COMMENT ''Código único do link'',
  `original_url` text NOT NULL COMMENT ''URL original/destino'',
  `final_url` text NOT NULL COMMENT ''URL final com UTMs'',
  `title` varchar(255) DEFAULT NULL COMMENT ''Título/nome do link'',
  `description` text DEFAULT NULL COMMENT ''Descrição do link'',
  `campaign_name` varchar(255) DEFAULT NULL COMMENT ''Nome da campanha'',
  `ad_name` varchar(255) DEFAULT NULL COMMENT ''Nome do anúncio'',
  `creative_name` varchar(255) DEFAULT NULL COMMENT ''Nome do criativo'',
  `utm_source` varchar(255) DEFAULT NULL,
  `utm_medium` varchar(255) DEFAULT NULL,
  `utm_campaign` varchar(255) DEFAULT NULL,
  `utm_content` varchar(255) DEFAULT NULL,
  `utm_term` varchar(255) DEFAULT NULL,
  `status` enum(''active'',''inactive'',''expired'') DEFAULT ''active'',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT ''Data de expiração'',
  `click_count` int(11) DEFAULT 0 COMMENT ''Contador de cliques'',
  `last_clicked_at` timestamp NULL DEFAULT NULL COMMENT ''Último clique'',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_short_code` (`short_code`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_utm_template` (`utm_template_id`),
  KEY `idx_status` (`status`),
  KEY `idx_campaign` (`campaign_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT=''Links encurtados com tracking'';
', 'SELECT "short_links já existe" as status');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Tabela de cliques (sem foreign keys)
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables 
    WHERE table_schema = 'u590097272_mercado_afilia' AND table_name = 'link_clicks');

SET @sql = IF(@table_exists = 0, '
CREATE TABLE `link_clicks` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `short_link_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL COMMENT ''IP do visitante'',
  `user_agent` text DEFAULT NULL COMMENT ''User agent do navegador'',
  `referer` text DEFAULT NULL COMMENT ''URL de origem'',
  `country` varchar(2) DEFAULT NULL COMMENT ''Código do país'',
  `region` varchar(100) DEFAULT NULL COMMENT ''Estado/região'',
  `city` varchar(100) DEFAULT NULL COMMENT ''Cidade'',
  `device_type` enum(''desktop'',''mobile'',''tablet'',''other'') DEFAULT ''other'',
  `browser` varchar(50) DEFAULT NULL COMMENT ''Nome do navegador'',
  `os` varchar(50) DEFAULT NULL COMMENT ''Sistema operacional'',
  `utm_source` varchar(255) DEFAULT NULL,
  `utm_medium` varchar(255) DEFAULT NULL,
  `utm_campaign` varchar(255) DEFAULT NULL,
  `utm_content` varchar(255) DEFAULT NULL,
  `utm_term` varchar(255) DEFAULT NULL,
  `click_timestamp` timestamp NOT NULL DEFAULT current_timestamp() COMMENT ''Momento exato do clique'',
  `session_id` varchar(100) DEFAULT NULL COMMENT ''ID da sessão'',
  `is_unique` tinyint(1) DEFAULT 1 COMMENT ''Se é clique único'',
  `conversion_tracked` tinyint(1) DEFAULT 0 COMMENT ''Se gerou conversão'',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_short_link` (`short_link_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_timestamp` (`click_timestamp`),
  KEY `idx_utm_campaign` (`utm_campaign`),
  KEY `idx_country` (`country`),
  KEY `idx_device_type` (`device_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT=''Registro detalhado de cliques'';
', 'SELECT "link_clicks já existe" as status');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Tabela de presets
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables 
    WHERE table_schema = 'u590097272_mercado_afilia' AND table_name = 'utm_presets');

SET @sql = IF(@table_exists = 0, '
CREATE TABLE `utm_presets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` enum(''facebook'',''google'',''tiktok'',''youtube'',''linkedin'',''twitter'',''pinterest'',''snapchat'') NOT NULL,
  `preset_name` varchar(255) NOT NULL COMMENT ''Nome do preset'',
  `utm_source` varchar(255) NOT NULL,
  `utm_medium` varchar(255) NOT NULL,
  `utm_campaign_template` varchar(255) DEFAULT NULL COMMENT ''Template com placeholders'',
  `utm_content_template` varchar(255) DEFAULT NULL,
  `utm_term_template` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) DEFAULT 1 COMMENT ''Se é preset do sistema'',
  `sort_order` int(11) DEFAULT 0 COMMENT ''Ordem de exibição'',
  `status` enum(''active'',''inactive'') DEFAULT ''active'',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_platform` (`platform`),
  KEY `idx_status_order` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT=''Presets padrão de UTM'';
', 'SELECT "utm_presets já existe" as status');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Inserir presets padrão apenas se não existirem
SET @preset_count = (SELECT COUNT(*) FROM `utm_presets` WHERE `is_system` = 1);

-- Facebook presets
INSERT IGNORE INTO `utm_presets` (`platform`, `preset_name`, `utm_source`, `utm_medium`, `utm_campaign_template`, `utm_content_template`, `utm_term_template`, `description`, `sort_order`) VALUES
('facebook', 'Conversão', 'facebook', 'cpc', '{campaign_name}', '{ad_name}', '{target_audience}', 'Template para campanhas de conversão no Facebook', 1),
('facebook', 'Tráfego', 'facebook', 'cpc', '{campaign_name}_traffic', '{ad_name}', 'traffic', 'Template para campanhas de tráfego no Facebook', 2),
('facebook', 'Remarketing', 'facebook', 'cpc', '{campaign_name}_remarket', '{ad_name}', 'remarketing', 'Template para campanhas de remarketing no Facebook', 3);

-- Google presets
INSERT IGNORE INTO `utm_presets` (`platform`, `preset_name`, `utm_source`, `utm_medium`, `utm_campaign_template`, `utm_content_template`, `utm_term_template`, `description`, `sort_order`) VALUES
('google', 'Search', 'google', 'cpc', '{campaign_name}', '{ad_group}', '{keyword}', 'Template para campanhas de busca no Google', 1),
('google', 'Display', 'google', 'display', '{campaign_name}_display', '{ad_name}', 'display', 'Template para rede de display do Google', 2),
('google', 'Shopping', 'google', 'cpc', '{campaign_name}_shopping', 'shopping', 'product', 'Template para Google Shopping', 3);

-- TikTok presets
INSERT IGNORE INTO `utm_presets` (`platform`, `preset_name`, `utm_source`, `utm_medium`, `utm_campaign_template`, `utm_content_template`, `utm_term_template`, `description`, `sort_order`) VALUES
('tiktok', 'Conversão', 'tiktok', 'cpc', '{campaign_name}', '{ad_name}', 'conversion', 'Template para campanhas de conversão no TikTok', 1),
('tiktok', 'Awareness', 'tiktok', 'cpc', '{campaign_name}_awareness', '{ad_name}', 'awareness', 'Template para campanhas de awareness no TikTok', 2);

-- YouTube presets
INSERT IGNORE INTO `utm_presets` (`platform`, `preset_name`, `utm_source`, `utm_medium`, `utm_campaign_template`, `utm_content_template`, `utm_term_template`, `description`, `sort_order`) VALUES
('youtube', 'Video Ads', 'youtube', 'cpc', '{campaign_name}', '{video_name}', 'video', 'Template para anúncios em vídeo no YouTube', 1),
('youtube', 'Discovery', 'youtube', 'cpc', '{campaign_name}_discovery', '{ad_name}', 'discovery', 'Template para YouTube Discovery', 2);

-- LinkedIn presets
INSERT IGNORE INTO `utm_presets` (`platform`, `preset_name`, `utm_source`, `utm_medium`, `utm_campaign_template`, `utm_content_template`, `utm_term_template`, `description`, `sort_order`) VALUES
('linkedin', 'Sponsored Content', 'linkedin', 'cpc', '{campaign_name}', '{ad_name}', 'sponsored', 'Template para conteúdo patrocinado no LinkedIn', 1),
('linkedin', 'Message Ads', 'linkedin', 'cpc', '{campaign_name}_message', '{ad_name}', 'message', 'Template para mensagens patrocinadas no LinkedIn', 2);

-- Criar índices adicionais
CREATE INDEX IF NOT EXISTS `idx_clicks_campaign_date` ON `link_clicks` (`utm_campaign`, `click_timestamp`);
CREATE INDEX IF NOT EXISTS `idx_clicks_user_date` ON `link_clicks` (`user_id`, `click_timestamp`);
CREATE INDEX IF NOT EXISTS `idx_links_user_campaign` ON `short_links` (`user_id`, `campaign_name`);
CREATE INDEX IF NOT EXISTS `idx_short_code_status` ON `short_links` (`short_code`, `status`);

-- Mensagem de confirmação
SELECT 'Link Maestro instalado com sucesso!' as status,
       (SELECT COUNT(*) FROM `utm_templates`) as utm_templates,
       (SELECT COUNT(*) FROM `short_links`) as short_links,  
       (SELECT COUNT(*) FROM `link_clicks`) as link_clicks,
       (SELECT COUNT(*) FROM `utm_presets`) as utm_presets;