-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 02/09/2025 às 12:55
-- Versão do servidor: 10.11.10-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u590097272_mercado_afilia`
--

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
(1, 1, 'hotmart', 'Teste Hotmart', 'test_key_123', 'test_secret_456', '3b3a49976a535b59cddf5546a2732b48d949316e13340ec71336c61e9743e224', NULL, 'error', NULL, 'Credenciais inválidas', '{\"created_via\":\"manual\"}', '2025-08-22 20:44:05', '2025-08-31 19:09:32'),
(3, 1, 'monetizze', 'Teste Hotmart', 'test_key_123', '', '0c6baf30efafe2f4b7f765749ce3654d20c2658d79f1edf050ed6bfa1adab31c', NULL, 'error', NULL, 'Credenciais inválidas', '{\"created_via\":\"manual\"}', '2025-08-24 21:50:06', '2025-08-31 18:24:35'),
(0, 1, 'eduzz', 'Produto A Eduzz', 'keytest_123456', '', '6276b1c8c6952a52d75275a94ef139928fd34992d7df8ccfb3719c67995fd489', NULL, 'error', '2025-08-31 18:28:56', 'Credenciais inválidas', '{\"created_via\":\"manual\"}', '2025-08-30 00:23:23', '2025-08-31 18:28:56'),
(0, 1, 'braip', 'Hotmart Amigurumi', 'h9kA2x1JjeR1T6j1Np6quoYWvgnFRse8fbf781-4939-4c97-9465-6e49f6ab2630', '', '1dc92e367c028e1881a07d37d5a34fd58b2bd60d02ac734652890472da46251b', NULL, 'error', '2025-08-31 18:28:56', 'Credenciais inválidas', '{\"created_via\":\"manual\"}', '2025-08-30 01:22:09', '2025-08-31 18:28:56');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
