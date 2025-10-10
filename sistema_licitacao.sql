-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 15/09/2025 às 01:29
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
-- Banco de dados: `sistema_licitacao`
--
CREATE DATABASE IF NOT EXISTS `sistema_licitacao` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `sistema_licitacao`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `backups_sistema`
--

CREATE TABLE `backups_sistema` (
  `id` int(11) NOT NULL,
  `tipo` enum('database','files','completo') NOT NULL,
  `status` enum('processando','sucesso','erro') NOT NULL DEFAULT 'processando',
  `inicio` datetime NOT NULL,
  `fim` datetime DEFAULT NULL,
  `tamanho_total` bigint(20) DEFAULT 0,
  `arquivo_database` varchar(255) DEFAULT NULL,
  `arquivo_files` varchar(255) DEFAULT NULL,
  `tempo_execucao` int(11) DEFAULT NULL,
  `erros` text DEFAULT NULL,
  `criado_por` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `graficos_salvos`
--

CREATE TABLE `graficos_salvos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `configuracao` text NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_andamentos`
--

CREATE TABLE `historico_andamentos` (
  `id` int(11) NOT NULL,
  `nup` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `processo_id` varchar(50) DEFAULT NULL,
  `data_hora` datetime NOT NULL,
  `unidade` varchar(100) NOT NULL,
  `usuario` varchar(100) DEFAULT NULL,
  `descricao` text NOT NULL,
  `importacao_timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `licitacoes`
--

CREATE TABLE `licitacoes` (
  `id` int(11) NOT NULL,
  `nup` varchar(255) DEFAULT NULL,
  `data_entrada_dipli` date DEFAULT NULL COMMENT 'Data de entrada na DIPLI',
  `resp_instrucao` varchar(255) DEFAULT NULL COMMENT 'Responsável pela instrução',
  `area_demandante` varchar(255) DEFAULT NULL COMMENT 'Área que demandou a licitação',
  `pregoeiro` varchar(255) DEFAULT NULL COMMENT 'Nome do pregoeiro',
  `pca_dados_id` int(11) DEFAULT NULL COMMENT 'Vinculação com PCA atual',
  `numero_processo` varchar(100) DEFAULT NULL COMMENT 'Número do processo (mantido para compatibilidade)',
  `tipo_licitacao` varchar(50) DEFAULT NULL COMMENT 'Tipo de licitação (mantido para compatibilidade)',
  `modalidade` varchar(50) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL COMMENT 'Tipo da licitação (TRADICIONAL, COTACAO, SRP)',
  `numero_contratacao` varchar(100) DEFAULT NULL COMMENT 'Número da contratação do PCA',
  `numero` int(11) DEFAULT NULL COMMENT 'Número sequencial da licitação',
  `ano` int(11) DEFAULT NULL COMMENT 'Ano da licitação',
  `objeto` text DEFAULT NULL,
  `valor_estimado` decimal(15,2) DEFAULT NULL,
  `qtd_itens` int(11) DEFAULT NULL COMMENT 'Quantidade de itens da licitação',
  `data_abertura` date DEFAULT NULL,
  `data_publicacao` date DEFAULT NULL COMMENT 'Data de publicação do edital',
  `data_homologacao` date DEFAULT NULL,
  `valor_homologado` decimal(15,2) DEFAULT NULL COMMENT 'Valor homologado',
  `qtd_homol` int(11) DEFAULT NULL COMMENT 'Quantidade homologada',
  `economia` decimal(15,2) DEFAULT NULL COMMENT 'Economia obtida (estimado - homologado)',
  `link` text DEFAULT NULL COMMENT 'Link para documentos/edital',
  `usuario_id` int(11) DEFAULT NULL COMMENT 'ID do usuário que criou',
  `situacao` enum('EM_ANDAMENTO','HOMOLOGADO','FRACASSADO','REVOGADO','CANCELADO','PREPARACAO') DEFAULT 'EM_ANDAMENTO',
  `observacoes` text DEFAULT NULL COMMENT 'Observações gerais',
  `usuario_criador` int(11) DEFAULT NULL COMMENT 'Usuário criador (mantido para compatibilidade)',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Processos licitatórios vinculados aos PCAs atuais';

--
-- Acionadores `licitacoes`
--
DELIMITER $$
CREATE TRIGGER `tr_licitacoes_calcular_economia` BEFORE UPDATE ON `licitacoes` FOR EACH ROW BEGIN
    -- Calcular economia se ambos os valores estiverem preenchidos
    IF NEW.valor_estimado IS NOT NULL AND NEW.valor_homologado IS NOT NULL THEN
        SET NEW.economia = NEW.valor_estimado - NEW.valor_homologado;
    END IF;
    
    -- Atualizar data de modificação
    SET NEW.atualizado_em = NOW();
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_licitacoes_log_mudancas` AFTER UPDATE ON `licitacoes` FOR EACH ROW BEGIN
    -- Log mudança de situação
    IF OLD.situacao != NEW.situacao THEN
        INSERT INTO logs_sistema (usuario_id, acao, modulo, detalhes, modulo_origem, registro_afetado_id) 
        VALUES (NEW.usuario_id, 'MUDANCA_SITUACAO', 'licitacoes', 
                CONCAT('Situação alterada de "', OLD.situacao, '" para "', NEW.situacao, '"'), 
                'TRIGGER', NEW.id);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_sistema`
--

CREATE TABLE `logs_sistema` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `acao` varchar(100) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `modulo_origem` varchar(100) DEFAULT NULL COMMENT 'Módulo que gerou o log',
  `detalhes` text DEFAULT NULL,
  `registro_afetado_id` int(11) DEFAULT NULL COMMENT 'ID do registro afetado',
  `ip_usuario` varchar(45) DEFAULT NULL COMMENT 'IP do usuário',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs de operações e auditoria do sistema';

-- --------------------------------------------------------

--
-- Estrutura para tabela `pca_dados`
--

CREATE TABLE `pca_dados` (
  `id` int(11) NOT NULL,
  `importacao_id` int(11) NOT NULL,
  `numero_contratacao` varchar(50) DEFAULT NULL,
  `status_contratacao` varchar(100) DEFAULT NULL,
  `situacao_execucao` varchar(100) DEFAULT 'Não iniciado',
  `titulo_contratacao` varchar(500) DEFAULT NULL,
  `categoria_contratacao` varchar(200) DEFAULT NULL,
  `uasg_atual` varchar(100) DEFAULT NULL,
  `valor_total_contratacao` decimal(15,2) DEFAULT NULL,
  `data_inicio_processo` date DEFAULT NULL,
  `data_conclusao_processo` date DEFAULT NULL,
  `prazo_duracao_dias` int(11) DEFAULT NULL,
  `area_requisitante` varchar(200) DEFAULT NULL,
  `numero_dfd` varchar(50) DEFAULT NULL,
  `prioridade` varchar(50) DEFAULT NULL,
  `urgente` tinyint(1) DEFAULT 0 COMMENT 'Marca contratação como urgente',
  `numero_item_dfd` varchar(50) DEFAULT NULL,
  `data_conclusao_dfd` date DEFAULT NULL,
  `classificacao_contratacao` varchar(200) DEFAULT NULL,
  `codigo_classe_grupo` varchar(50) DEFAULT NULL,
  `nome_classe_grupo` varchar(200) DEFAULT NULL,
  `codigo_pdm_material` varchar(50) DEFAULT NULL,
  `nome_pdm_material` varchar(200) DEFAULT NULL,
  `codigo_material_servico` varchar(100) DEFAULT NULL,
  `descricao_material_servico` varchar(1000) DEFAULT NULL,
  `unidade_fornecimento` varchar(50) DEFAULT NULL,
  `valor_unitario` decimal(15,2) DEFAULT NULL,
  `quantidade` int(11) DEFAULT NULL,
  `valor_total` decimal(15,2) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dados atuais do PCA (2025 e 2026) - Editáveis';

-- --------------------------------------------------------

--
-- Estrutura para tabela `pca_estados_tempo`
--

CREATE TABLE `pca_estados_tempo` (
  `id` int(11) NOT NULL,
  `numero_contratacao` varchar(50) NOT NULL,
  `situacao_execucao` varchar(100) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date DEFAULT NULL,
  `dias_no_estado` int(11) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Controle de tempo em cada situação de execução';

-- --------------------------------------------------------

--
-- Estrutura para tabela `pca_historico`
--

CREATE TABLE `pca_historico` (
  `id` int(11) NOT NULL,
  `numero_contratacao` varchar(50) NOT NULL,
  `campo_alterado` varchar(100) NOT NULL,
  `valor_anterior` text DEFAULT NULL,
  `valor_novo` text DEFAULT NULL,
  `importacao_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_alteracao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auditoria de mudanças nos dados do PCA';

-- --------------------------------------------------------

--
-- Estrutura para tabela `pca_historico_anos`
--

CREATE TABLE `pca_historico_anos` (
  `id` int(11) NOT NULL,
  `ano` year(4) NOT NULL,
  `numero_contratacao` varchar(50) DEFAULT NULL,
  `status_contratacao` varchar(100) DEFAULT NULL,
  `situacao_execucao` varchar(100) DEFAULT 'Não iniciado',
  `titulo_contratacao` text DEFAULT NULL,
  `categoria_contratacao` varchar(200) DEFAULT NULL,
  `uasg_atual` varchar(100) DEFAULT NULL,
  `valor_total_contratacao` decimal(15,2) DEFAULT NULL,
  `data_inicio_processo` date DEFAULT NULL,
  `data_conclusao_processo` date DEFAULT NULL,
  `prazo_duracao_dias` int(11) DEFAULT NULL,
  `area_requisitante` varchar(200) DEFAULT NULL,
  `numero_dfd` varchar(50) DEFAULT NULL,
  `prioridade` varchar(50) DEFAULT NULL,
  `numero_item_dfd` varchar(50) DEFAULT NULL,
  `data_conclusao_dfd` date DEFAULT NULL,
  `classificacao_contratacao` varchar(200) DEFAULT NULL,
  `codigo_classe_grupo` varchar(50) DEFAULT NULL,
  `nome_classe_grupo` varchar(200) DEFAULT NULL,
  `codigo_pdm_material` varchar(50) DEFAULT NULL,
  `nome_pdm_material` varchar(200) DEFAULT NULL,
  `codigo_material_servico` varchar(100) DEFAULT NULL,
  `descricao_material_servico` text DEFAULT NULL,
  `unidade_fornecimento` varchar(50) DEFAULT NULL,
  `valor_unitario` decimal(15,2) DEFAULT NULL,
  `quantidade` int(11) DEFAULT NULL,
  `valor_total` decimal(15,2) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dados históricos dos PCAs (2022-2024) - Somente leitura';

-- --------------------------------------------------------

--
-- Estrutura para tabela `pca_importacoes`
--

CREATE TABLE `pca_importacoes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `ano_pca` year(4) NOT NULL DEFAULT 2025 COMMENT 'Ano do PCA importado',
  `usuario_id` int(11) NOT NULL,
  `status` enum('processando','concluido','erro') DEFAULT 'processando',
  `total_registros` int(11) DEFAULT 0,
  `registros_novos` int(11) DEFAULT 0,
  `registros_atualizados` int(11) DEFAULT 0,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de importações de PCAs separadas por ano';

-- --------------------------------------------------------

--
-- Estrutura para tabela `pca_riscos`
--

CREATE TABLE `pca_riscos` (
  `id` int(11) NOT NULL,
  `numero_dfd` varchar(50) NOT NULL,
  `mes_relatorio` varchar(7) NOT NULL COMMENT 'Formato: YYYY-MM',
  `nivel_risco` enum('baixo','medio','alto','extremo') NOT NULL,
  `categoria_risco` varchar(100) NOT NULL,
  `descricao_risco` text NOT NULL,
  `impacto` text DEFAULT NULL,
  `probabilidade` varchar(50) DEFAULT NULL,
  `acao_mitigacao` text DEFAULT NULL,
  `responsavel` varchar(100) DEFAULT NULL,
  `prazo_mitigacao` date DEFAULT NULL,
  `status_acao` enum('pendente','em_andamento','concluida','cancelada') DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `processo_andamentos`
--

CREATE TABLE `processo_andamentos` (
  `id` int(11) NOT NULL,
  `nup` varchar(50) NOT NULL COMMENT 'Número Único de Protocolo',
  `processo_id` varchar(100) NOT NULL COMMENT 'ID do processo no sistema externo',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data/hora da última atualização',
  `total_andamentos` int(11) NOT NULL DEFAULT 0 COMMENT 'Total de andamentos registrados',
  `andamentos_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Dados JSON dos andamentos' CHECK (json_valid(`andamentos_json`)),
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Armazena andamentos de processos em formato JSON';

-- --------------------------------------------------------

--
-- Estrutura para tabela `qualificacoes`
--

CREATE TABLE `qualificacoes` (
  `id` int(11) NOT NULL,
  `pca_dados_id` int(11) DEFAULT NULL COMMENT 'ID do registro vinculado na tabela pca_dados',
  `nup` varchar(50) NOT NULL COMMENT 'Número Único de Protocolo',
  `area_demandante` varchar(255) NOT NULL COMMENT 'Área que demandou a qualificação',
  `responsavel` varchar(255) NOT NULL COMMENT 'Responsável pela qualificação',
  `modalidade` varchar(100) NOT NULL COMMENT 'Modalidade da licitação',
  `objeto` text NOT NULL COMMENT 'Descrição do objeto',
  `palavras_chave` varchar(500) DEFAULT NULL COMMENT 'Palavras-chave separadas por vírgula',
  `valor_estimado` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor estimado em reais',
  `status` varchar(50) NOT NULL DEFAULT 'EM AN┴LISE',
  `observacoes` text DEFAULT NULL COMMENT 'Observações adicionais',
  `usuario_id` int(11) NOT NULL COMMENT 'ID do usuário que criou',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data de criação',
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Data de atualização'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de qualificações do sistema CGLIC';

-- --------------------------------------------------------

--
-- Estrutura para tabela `tarefas_modulos`
--

CREATE TABLE `tarefas_modulos` (
  `id` int(11) NOT NULL,
  `modulo` enum('PLANEJAMENTO','LICITACAO','QUALIFICACAO','CONTRATOS') NOT NULL,
  `nome_tarefa` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tramitacoes_comentarios`
--

CREATE TABLE `tramitacoes_comentarios` (
  `id` int(11) NOT NULL,
  `tramitacao_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `comentario` text NOT NULL,
  `tipo` enum('COMENTARIO','MUDANCA_STATUS','ATRIBUICAO','ANEXO') DEFAULT 'COMENTARIO',
  `metadados` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadados`)),
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tramitacoes_config_usuario`
--

CREATE TABLE `tramitacoes_config_usuario` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `configuracoes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`configuracoes`)),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tramitacoes_historico`
--

CREATE TABLE `tramitacoes_historico` (
  `id` int(11) NOT NULL,
  `tramitacao_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `acao` varchar(100) NOT NULL,
  `campo_alterado` varchar(50) DEFAULT NULL,
  `valor_anterior` text DEFAULT NULL,
  `valor_novo` text DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tramitacoes_kanban`
--

CREATE TABLE `tramitacoes_kanban` (
  `id` int(11) NOT NULL,
  `numero_tramite` varchar(20) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo_demanda` varchar(100) NOT NULL,
  `status` enum('TODO','EM_PROGRESSO','AGUARDANDO','CONCLUIDO','CANCELADO') NOT NULL DEFAULT 'TODO',
  `prioridade` enum('BAIXA','MEDIA','ALTA','URGENTE') NOT NULL DEFAULT 'MEDIA',
  `posicao` int(11) NOT NULL DEFAULT 0,
  `usuario_criador_id` int(11) NOT NULL,
  `usuario_responsavel_id` int(11) DEFAULT NULL,
  `modulo_origem` enum('PLANEJAMENTO','LICITACAO','QUALIFICACAO','CONTRATOS') NOT NULL,
  `modulo_destino` enum('PLANEJAMENTO','LICITACAO','QUALIFICACAO','CONTRATOS') NOT NULL,
  `prazo_limite` datetime DEFAULT NULL,
  `data_inicio` datetime DEFAULT NULL,
  `data_conclusao` datetime DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `cor_card` varchar(7) DEFAULT '#3b82f6',
  `observacoes` text DEFAULT NULL,
  `anexos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`anexos`)),
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` int(11) NOT NULL,
  `atualizado_por` int(11) DEFAULT NULL,
  `situacao_prazo` enum('NO_PRAZO','VENCENDO','ATRASADO') DEFAULT 'NO_PRAZO',
  `dias_restantes` int(11) DEFAULT 0,
  `qualificacao_id` int(11) DEFAULT NULL,
  `nup_vinculado` varchar(50) DEFAULT NULL,
  `objeto_vinculado` text DEFAULT NULL,
  `area_vinculada` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Acionadores `tramitacoes_kanban`
--
DELIMITER $$
CREATE TRIGGER `tr_tramitacao_numero_tramite` BEFORE INSERT ON `tramitacoes_kanban` FOR EACH ROW BEGIN
        IF NEW.numero_tramite IS NULL OR NEW.numero_tramite = '' THEN
            SET NEW.numero_tramite = CONCAT('TK', DATE_FORMAT(NOW(), '%y%m'), LPAD((
                SELECT COALESCE(MAX(SUBSTRING(numero_tramite, 5) + 0), 0) + 1
                FROM tramitacoes_kanban 
                WHERE numero_tramite LIKE CONCAT('TK', DATE_FORMAT(NOW(), '%y%m'), '%')
            ), 4, '0'));
        END IF;
    END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_tramitacao_numero` BEFORE INSERT ON `tramitacoes_kanban` FOR EACH ROW BEGIN 
    DECLARE next_num INT DEFAULT 1;
    
    SELECT IFNULL(MAX(CAST(SUBSTRING(numero_tramite, 7) AS UNSIGNED)), 0) + 1 INTO next_num 
    FROM tramitacoes_kanban 
    WHERE numero_tramite LIKE CONCAT('TK', YEAR(CURDATE()), '%');
    
    SET NEW.numero_tramite = CONCAT('TK', YEAR(CURDATE()), LPAD(next_num, 4, '0'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tramitacoes_tarefas`
--

CREATE TABLE `tramitacoes_tarefas` (
  `id` int(11) NOT NULL,
  `tramitacao_id` int(11) NOT NULL,
  `tarefa_modulo_id` int(11) NOT NULL,
  `estagio` enum('INICIANDO','EM_ANDAMENTO','CONCLUIDA','CANCELADA') NOT NULL DEFAULT 'INICIANDO',
  `usuario_responsavel_id` int(11) DEFAULT NULL,
  `data_inicio` datetime DEFAULT NULL,
  `data_conclusao` datetime DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `anexos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`anexos`)),
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` int(11) NOT NULL,
  `atualizado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tramitacoes_templates`
--

CREATE TABLE `tramitacoes_templates` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo_demanda_padrao` varchar(100) NOT NULL,
  `modulo_origem` enum('PLANEJAMENTO','LICITACAO','QUALIFICACAO','CONTRATOS') NOT NULL,
  `modulo_destino` enum('PLANEJAMENTO','LICITACAO','QUALIFICACAO','CONTRATOS') NOT NULL,
  `prioridade_padrao` enum('BAIXA','MEDIA','ALTA','URGENTE') DEFAULT 'MEDIA',
  `prazo_padrao_dias` int(11) DEFAULT NULL,
  `cor_padrao` varchar(7) DEFAULT '#3b82f6',
  `tags_padrao` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags_padrao`)),
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `criado_por` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo_usuario` enum('admin','usuario','coordenador','diplan','dipli','visitante','diquali') DEFAULT 'usuario',
  `nivel_acesso` tinyint(1) NOT NULL DEFAULT 3 COMMENT '1=Coordenador, 2=DIPLAN, 3=DIPLI',
  `departamento` varchar(100) DEFAULT 'CGLIC' COMMENT 'Departamento do usuário',
  `ativo` tinyint(1) DEFAULT 1,
  `ultimo_login` timestamp NULL DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sistema de usuários com níveis de acesso hierárquicos';

--
-- Acionadores `usuarios`
--
DELIMITER $$
CREATE TRIGGER `tr_usuarios_ultimo_login` BEFORE UPDATE ON `usuarios` FOR EACH ROW BEGIN
    IF NEW.ultimo_login IS NOT NULL AND NEW.ultimo_login != OLD.ultimo_login THEN
        SET NEW.atualizado_em = CURRENT_TIMESTAMP;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `view_contratacoes_licitacoes`
--

CREATE TABLE `view_contratacoes_licitacoes` (
  `numero_dfd` varchar(50) DEFAULT NULL,
  `numero_contratacao` varchar(50) DEFAULT NULL,
  `titulo_contratacao` varchar(500) DEFAULT NULL,
  `valor_total_contratacao` decimal(15,2) DEFAULT NULL,
  `situacao_execucao` varchar(100) DEFAULT NULL,
  `ano_pca` year(4) DEFAULT NULL,
  `processo_licitacao` varchar(100) DEFAULT NULL,
  `situacao_licitacao` enum('EM_ANDAMENTO','HOMOLOGADO','FRACASSADO','REVOGADO','CANCELADO','PREPARACAO') DEFAULT NULL,
  `data_abertura` date DEFAULT NULL,
  `data_homologacao` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `view_dashboard_licitacoes`
--

CREATE TABLE `view_dashboard_licitacoes` (
  `id` int(11) DEFAULT NULL,
  `nup` varchar(255) DEFAULT NULL,
  `objeto` text DEFAULT NULL,
  `modalidade` varchar(50) DEFAULT NULL,
  `situacao` enum('EM_ANDAMENTO','HOMOLOGADO','FRACASSADO','REVOGADO','CANCELADO','PREPARACAO') DEFAULT NULL,
  `valor_estimado` decimal(15,2) DEFAULT NULL,
  `valor_homologado` decimal(15,2) DEFAULT NULL,
  `economia` decimal(15,2) DEFAULT NULL,
  `data_abertura` date DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `usuario_nome` varchar(255) DEFAULT NULL,
  `numero_dfd` varchar(50) DEFAULT NULL,
  `titulo_contratacao` varchar(500) DEFAULT NULL,
  `area_requisitante` varchar(200) DEFAULT NULL,
  `status_prazo` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `view_pca_resumo_anos`
--

CREATE TABLE `view_pca_resumo_anos` (
  `tipo` varchar(9) DEFAULT NULL,
  `ano` year(4) DEFAULT NULL,
  `total_dfds` bigint(21) DEFAULT NULL,
  `total_contratacoes` bigint(21) DEFAULT NULL,
  `valor_total` decimal(37,2) DEFAULT NULL,
  `concluidas` bigint(21) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_tramitacoes_estatisticas_tarefas`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_tramitacoes_estatisticas_tarefas` (
`tramitacao_id` int(11)
,`total_tarefas` bigint(21)
,`tarefas_iniciando` bigint(21)
,`tarefas_em_andamento` bigint(21)
,`tarefas_concluidas` bigint(21)
,`tarefas_canceladas` bigint(21)
,`progresso_geral` decimal(26,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_tramitacoes_kanban`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_tramitacoes_kanban` (
`id` int(11)
,`numero_tramite` varchar(20)
,`titulo` varchar(255)
,`descricao` text
,`tipo_demanda` varchar(100)
,`status` enum('TODO','EM_PROGRESSO','AGUARDANDO','CONCLUIDO','CANCELADO')
,`prioridade` enum('BAIXA','MEDIA','ALTA','URGENTE')
,`posicao` int(11)
,`modulo_origem` enum('PLANEJAMENTO','LICITACAO','QUALIFICACAO','CONTRATOS')
,`modulo_destino` enum('PLANEJAMENTO','LICITACAO','QUALIFICACAO','CONTRATOS')
,`prazo_limite` datetime
,`data_inicio` datetime
,`data_conclusao` datetime
,`tags` longtext
,`cor_card` varchar(7)
,`observacoes` text
,`criado_em` timestamp
,`atualizado_em` timestamp
,`usuario_criador_id` int(11)
,`usuario_responsavel_id` int(11)
,`usuario_criador_nome` varchar(255)
,`usuario_criador_email` varchar(255)
,`usuario_responsavel_nome` varchar(255)
,`usuario_responsavel_email` varchar(255)
,`usuario_responsavel_departamento` varchar(100)
,`situacao_prazo` varchar(8)
,`dias_restantes` int(7)
,`total_comentarios` bigint(21)
,`tempo_processamento` int(7)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_tramitacoes_tarefas_completa`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_tramitacoes_tarefas_completa` (
`id` int(11)
,`tramitacao_id` int(11)
,`estagio` enum('INICIANDO','EM_ANDAMENTO','CONCLUIDA','CANCELADA')
,`data_inicio` datetime
,`data_conclusao` datetime
,`observacoes` text
,`tarefa_criada_em` timestamp
,`tarefa_atualizada_em` timestamp
,`tarefa_modulo_id` int(11)
,`modulo` enum('PLANEJAMENTO','LICITACAO','QUALIFICACAO','CONTRATOS')
,`nome_tarefa` varchar(100)
,`tarefa_descricao` text
,`ordem` int(11)
,`responsavel_nome` varchar(255)
,`responsavel_email` varchar(255)
,`numero_tramite` varchar(20)
,`tramitacao_titulo` varchar(255)
,`tramitacao_status` enum('TODO','EM_PROGRESSO','AGUARDANDO','CONCLUIDO','CANCELADO')
,`progresso_percentual` int(3)
);

-- --------------------------------------------------------

--
-- Estrutura para view `v_tramitacoes_estatisticas_tarefas`
--
DROP TABLE IF EXISTS `v_tramitacoes_estatisticas_tarefas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_tramitacoes_estatisticas_tarefas`  AS SELECT `tramitacoes_tarefas`.`tramitacao_id` AS `tramitacao_id`, count(0) AS `total_tarefas`, count(case when `tramitacoes_tarefas`.`estagio` = 'INICIANDO' then 1 end) AS `tarefas_iniciando`, count(case when `tramitacoes_tarefas`.`estagio` = 'EM_ANDAMENTO' then 1 end) AS `tarefas_em_andamento`, count(case when `tramitacoes_tarefas`.`estagio` = 'CONCLUIDA' then 1 end) AS `tarefas_concluidas`, count(case when `tramitacoes_tarefas`.`estagio` = 'CANCELADA' then 1 end) AS `tarefas_canceladas`, round(count(case when `tramitacoes_tarefas`.`estagio` = 'CONCLUIDA' then 1 end) * 100.0 / count(0),2) AS `progresso_geral` FROM `tramitacoes_tarefas` GROUP BY `tramitacoes_tarefas`.`tramitacao_id` ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_tramitacoes_kanban`
--
DROP TABLE IF EXISTS `v_tramitacoes_kanban`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_tramitacoes_kanban`  AS SELECT `tk`.`id` AS `id`, `tk`.`numero_tramite` AS `numero_tramite`, `tk`.`titulo` AS `titulo`, `tk`.`descricao` AS `descricao`, `tk`.`tipo_demanda` AS `tipo_demanda`, `tk`.`status` AS `status`, `tk`.`prioridade` AS `prioridade`, `tk`.`posicao` AS `posicao`, `tk`.`modulo_origem` AS `modulo_origem`, `tk`.`modulo_destino` AS `modulo_destino`, `tk`.`prazo_limite` AS `prazo_limite`, `tk`.`data_inicio` AS `data_inicio`, `tk`.`data_conclusao` AS `data_conclusao`, `tk`.`tags` AS `tags`, `tk`.`cor_card` AS `cor_card`, `tk`.`observacoes` AS `observacoes`, `tk`.`criado_em` AS `criado_em`, `tk`.`atualizado_em` AS `atualizado_em`, `tk`.`usuario_criador_id` AS `usuario_criador_id`, `tk`.`usuario_responsavel_id` AS `usuario_responsavel_id`, `uc`.`nome` AS `usuario_criador_nome`, `uc`.`email` AS `usuario_criador_email`, `ur`.`nome` AS `usuario_responsavel_nome`, `ur`.`email` AS `usuario_responsavel_email`, `ur`.`departamento` AS `usuario_responsavel_departamento`, CASE WHEN `tk`.`prazo_limite` is null THEN NULL WHEN `tk`.`prazo_limite` < current_timestamp() AND `tk`.`status` not in ('CONCLUIDO','CANCELADO') THEN 'ATRASADO' WHEN `tk`.`prazo_limite` <= current_timestamp() + interval 1 day AND `tk`.`status` not in ('CONCLUIDO','CANCELADO') THEN 'VENCENDO' ELSE 'NO_PRAZO' END AS `situacao_prazo`, CASE WHEN `tk`.`prazo_limite` is null THEN NULL ELSE to_days(`tk`.`prazo_limite`) - to_days(current_timestamp()) END AS `dias_restantes`, (select count(0) from `tramitacoes_comentarios` `tc` where `tc`.`tramitacao_id` = `tk`.`id`) AS `total_comentarios`, CASE WHEN `tk`.`data_inicio` is null THEN to_days(current_timestamp()) - to_days(`tk`.`criado_em`) WHEN `tk`.`data_conclusao` is not null THEN to_days(`tk`.`data_conclusao`) - to_days(`tk`.`data_inicio`) ELSE to_days(current_timestamp()) - to_days(`tk`.`data_inicio`) END AS `tempo_processamento` FROM ((`tramitacoes_kanban` `tk` left join `usuarios` `uc` on(`tk`.`usuario_criador_id` = `uc`.`id`)) left join `usuarios` `ur` on(`tk`.`usuario_responsavel_id` = `ur`.`id`)) ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_tramitacoes_tarefas_completa`
--
DROP TABLE IF EXISTS `v_tramitacoes_tarefas_completa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_tramitacoes_tarefas_completa`  AS SELECT `tt`.`id` AS `id`, `tt`.`tramitacao_id` AS `tramitacao_id`, `tt`.`estagio` AS `estagio`, `tt`.`data_inicio` AS `data_inicio`, `tt`.`data_conclusao` AS `data_conclusao`, `tt`.`observacoes` AS `observacoes`, `tt`.`criado_em` AS `tarefa_criada_em`, `tt`.`atualizado_em` AS `tarefa_atualizada_em`, `tm`.`id` AS `tarefa_modulo_id`, `tm`.`modulo` AS `modulo`, `tm`.`nome_tarefa` AS `nome_tarefa`, `tm`.`descricao` AS `tarefa_descricao`, `tm`.`ordem` AS `ordem`, `ur`.`nome` AS `responsavel_nome`, `ur`.`email` AS `responsavel_email`, `tk`.`numero_tramite` AS `numero_tramite`, `tk`.`titulo` AS `tramitacao_titulo`, `tk`.`status` AS `tramitacao_status`, CASE WHEN `tt`.`estagio` = 'CONCLUIDA' THEN 100 WHEN `tt`.`estagio` = 'EM_ANDAMENTO' THEN 50 WHEN `tt`.`estagio` = 'INICIANDO' THEN 25 WHEN `tt`.`estagio` = 'CANCELADA' THEN 0 ELSE 0 END AS `progresso_percentual` FROM (((`tramitacoes_tarefas` `tt` join `tarefas_modulos` `tm` on(`tt`.`tarefa_modulo_id` = `tm`.`id`)) left join `usuarios` `ur` on(`tt`.`usuario_responsavel_id` = `ur`.`id`)) left join `tramitacoes_kanban` `tk` on(`tt`.`tramitacao_id` = `tk`.`id`)) ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `backups_sistema`
--
ALTER TABLE `backups_sistema`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `graficos_salvos`
--
ALTER TABLE `graficos_salvos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `historico_andamentos`
--
ALTER TABLE `historico_andamentos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `licitacoes`
--
ALTER TABLE `licitacoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `pca_dados`
--
ALTER TABLE `pca_dados`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `pca_historico`
--
ALTER TABLE `pca_historico`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `pca_historico_anos`
--
ALTER TABLE `pca_historico_anos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `pca_riscos`
--
ALTER TABLE `pca_riscos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `processo_andamentos`
--
ALTER TABLE `processo_andamentos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `qualificacoes`
--
ALTER TABLE `qualificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pca_dados_id` (`pca_dados_id`);

--
-- Índices de tabela `tarefas_modulos`
--
ALTER TABLE `tarefas_modulos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_modulo` (`modulo`),
  ADD KEY `idx_ordem` (`ordem`),
  ADD KEY `idx_ativo` (`ativo`);

--
-- Índices de tabela `tramitacoes_comentarios`
--
ALTER TABLE `tramitacoes_comentarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tramitacao` (`tramitacao_id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_data` (`criado_em`);

--
-- Índices de tabela `tramitacoes_config_usuario`
--
ALTER TABLE `tramitacoes_config_usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_usuario` (`usuario_id`);

--
-- Índices de tabela `tramitacoes_historico`
--
ALTER TABLE `tramitacoes_historico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tramitacao` (`tramitacao_id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_acao` (`acao`),
  ADD KEY `idx_data` (`criado_em`);

--
-- Índices de tabela `tramitacoes_kanban`
--
ALTER TABLE `tramitacoes_kanban`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_tramite` (`numero_tramite`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_usuario_responsavel` (`usuario_responsavel_id`),
  ADD KEY `idx_modulos` (`modulo_origem`,`modulo_destino`),
  ADD KEY `idx_prioridade` (`prioridade`),
  ADD KEY `idx_posicao` (`posicao`),
  ADD KEY `idx_prazo` (`prazo_limite`),
  ADD KEY `idx_data_criacao` (`criado_em`),
  ADD KEY `idx_qualificacao_id` (`qualificacao_id`),
  ADD KEY `idx_nup_vinculado` (`nup_vinculado`);

--
-- Índices de tabela `tramitacoes_tarefas`
--
ALTER TABLE `tramitacoes_tarefas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tramitacao` (`tramitacao_id`),
  ADD KEY `idx_tarefa_modulo` (`tarefa_modulo_id`),
  ADD KEY `idx_estagio` (`estagio`),
  ADD KEY `idx_responsavel` (`usuario_responsavel_id`);

--
-- Índices de tabela `tramitacoes_templates`
--
ALTER TABLE `tramitacoes_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_modulos` (`modulo_origem`,`modulo_destino`),
  ADD KEY `idx_ativo` (`ativo`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `backups_sistema`
--
ALTER TABLE `backups_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `graficos_salvos`
--
ALTER TABLE `graficos_salvos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `historico_andamentos`
--
ALTER TABLE `historico_andamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `licitacoes`
--
ALTER TABLE `licitacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `logs_sistema`
--
ALTER TABLE `logs_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pca_dados`
--
ALTER TABLE `pca_dados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pca_historico`
--
ALTER TABLE `pca_historico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pca_historico_anos`
--
ALTER TABLE `pca_historico_anos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pca_riscos`
--
ALTER TABLE `pca_riscos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `processo_andamentos`
--
ALTER TABLE `processo_andamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `qualificacoes`
--
ALTER TABLE `qualificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tarefas_modulos`
--
ALTER TABLE `tarefas_modulos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tramitacoes_comentarios`
--
ALTER TABLE `tramitacoes_comentarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tramitacoes_config_usuario`
--
ALTER TABLE `tramitacoes_config_usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tramitacoes_historico`
--
ALTER TABLE `tramitacoes_historico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tramitacoes_kanban`
--
ALTER TABLE `tramitacoes_kanban`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tramitacoes_tarefas`
--
ALTER TABLE `tramitacoes_tarefas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tramitacoes_templates`
--
ALTER TABLE `tramitacoes_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `qualificacoes`
--
ALTER TABLE `qualificacoes`
  ADD CONSTRAINT `fk_qualificacoes_pca_dados` FOREIGN KEY (`pca_dados_id`) REFERENCES `pca_dados` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
