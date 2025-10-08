-- ========================================
-- SCRIPT PARA ADICIONAR CAMPO pca_dados_id
-- Tabela: qualificacoes
-- Data: 2025-01-20
-- ========================================

USE sistema_licitacao;

-- 1. Fazer backup da estrutura atual (informativo)
-- mysqldump -u root --no-data sistema_licitacao qualificacoes > backup_estrutura_qualificacoes.sql

-- 2. Adicionar o campo pca_dados_id
ALTER TABLE qualificacoes 
ADD COLUMN pca_dados_id INT(11) NULL 
COMMENT 'ID do registro vinculado na tabela pca_dados'
AFTER id;

-- 3. Adicionar índice para melhor performance
ALTER TABLE qualificacoes 
ADD INDEX idx_pca_dados_id (pca_dados_id);

-- 4. Adicionar foreign key constraint para integridade referencial
ALTER TABLE qualificacoes 
ADD CONSTRAINT fk_qualificacoes_pca_dados 
FOREIGN KEY (pca_dados_id) REFERENCES pca_dados(id) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

-- 5. Verificar se as alterações foram aplicadas corretamente
DESCRIBE qualificacoes;

-- 6. Verificar índices criados
SHOW INDEX FROM qualificacoes WHERE Column_name = 'pca_dados_id';

-- 7. Verificar foreign key criada
SELECT 
    CONSTRAINT_NAME, 
    COLUMN_NAME, 
    REFERENCED_TABLE_NAME, 
    REFERENCED_COLUMN_NAME,
    DELETE_RULE,
    UPDATE_RULE
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_NAME = 'qualificacoes' 
  AND TABLE_SCHEMA = 'sistema_licitacao'
  AND REFERENCED_TABLE_NAME = 'pca_dados';

-- 8. Verificar quantidade de registros (deve ser 35)
SELECT COUNT(*) as total_qualificacoes FROM qualificacoes;

-- 9. Listar qualificações com o novo campo (todos NULL inicialmente)
SELECT id, nup, area_demandante, pca_dados_id 
FROM qualificacoes 
ORDER BY id 
LIMIT 10;

-- ========================================
-- CONSULTAS ÚTEIS PARA VINCULAÇÃO MANUAL
-- ========================================

-- Listar todas as qualificações para vinculação manual
SELECT 
    id,
    nup,
    area_demandante,
    LEFT(objeto, 60) as objeto_resumo,
    valor_estimado,
    pca_dados_id
FROM qualificacoes 
ORDER BY area_demandante, valor_estimado DESC;

-- Buscar PCAs por área específica (exemplo para facilitar vinculação)
-- Substitua 'NOME_DA_AREA' pela área desejada
SELECT 
    id,
    numero_dfd,
    area_requisitante,
    LEFT(titulo_contratacao, 60) as titulo_resumo,
    valor_total_contratacao
FROM pca_dados 
WHERE area_requisitante LIKE '%CGFISC%'  -- Exemplo: área CGFISC
ORDER BY valor_total_contratacao DESC;

-- Template para vinculação manual (substituir IDs reais)
-- UPDATE qualificacoes SET pca_dados_id = [ID_PCA_DADOS] WHERE id = [ID_QUALIFICACAO];

-- Exemplos práticos (AJUSTAR com IDs reais):
-- UPDATE qualificacoes SET pca_dados_id = 6698 WHERE id = 7;
-- UPDATE qualificacoes SET pca_dados_id = 6699 WHERE id = 8;

-- Verificar vinculações após criação manual
SELECT 
    q.id as qualif_id,
    q.nup,
    q.area_demandante,
    q.valor_estimado,
    p.id as pca_id,
    p.numero_dfd,
    p.area_requisitante,
    p.valor_total_contratacao
FROM qualificacoes q
LEFT JOIN pca_dados p ON q.pca_dados_id = p.id
ORDER BY q.id;

-- ========================================
-- ROLLBACK (caso necessário)
-- ========================================

-- Para reverter as alterações:
-- ALTER TABLE qualificacoes DROP FOREIGN KEY fk_qualificacoes_pca_dados;
-- ALTER TABLE qualificacoes DROP INDEX idx_pca_dados_id;
-- ALTER TABLE qualificacoes DROP COLUMN pca_dados_id;