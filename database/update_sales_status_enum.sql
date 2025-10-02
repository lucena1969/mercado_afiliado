-- ================================
-- ATUALIZAR ENUM STATUS DA TABELA SALES
-- Adicionar novos status de eventos de compras da Hotmart
-- ================================

-- Status atual: 'approved','pending','cancelled','refunded','chargeback'
-- Novos status: 'expired','dispute','delayed','abandoned'

ALTER TABLE `sales` 
MODIFY COLUMN `status` ENUM(
    'approved',
    'pending', 
    'cancelled',
    'refunded',
    'chargeback',
    'expired',
    'dispute', 
    'delayed',
    'abandoned'
) NOT NULL;

-- Verificar se funcionou
-- SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_NAME = 'sales' AND COLUMN_NAME = 'status';