# 🔄 ESTRATÉGIA DE MIGRAÇÃO - VINCULAÇÃO QUALIFICAÇÕES → PCA_DADOS

## 📋 **Visão Geral**

Este documento detalha a estratégia para implementar a vinculação entre os módulos **Qualificação** e **Planejamento** através do campo `pca_dados_id` na tabela `qualificacoes`.

---

## 🎯 **Objetivo**

Criar uma relação foreign key entre:
- **Tabela origem:** `qualificacoes` (35 registros existentes)
- **Tabela destino:** `pca_dados` (639 registros existentes)
- **Campo novo:** `pca_dados_id` (INT, NULL, com índice e foreign key)

---

## 🔍 **Análise Atual do Banco de Dados**

### **Tabela `qualificacoes` (35 registros)**
| Campo | Tipo | Uso para Vinculação |
|-------|------|---------------------|
| `nup` | varchar(50) | ❌ Não há campo equivalente em pca_dados |
| `area_demandante` | varchar(255) | ✅ **CRITÉRIO PRINCIPAL** → `pca_dados.area_requisitante` |
| `valor_estimado` | decimal(15,2) | ✅ **CRITÉRIO SECUNDÁRIO** → `pca_dados.valor_total_contratacao` |
| `objeto` | text | ✅ **CRITÉRIO TERCIÁRIO** → `pca_dados.titulo_contratacao` (similaridade textual) |
| `modalidade` | varchar(100) | ❌ Não aplicável para vinculação |

### **Tabela `pca_dados` (639 registros)**
| Campo | Tipo | Uso para Vinculação |
|-------|------|---------------------|
| `area_requisitante` | varchar(200) | ✅ **CRITÉRIO PRINCIPAL** |
| `valor_total_contratacao` | decimal(15,2) | ✅ **CRITÉRIO SECUNDÁRIO** |
| `titulo_contratacao` | varchar(500) | ✅ **CRITÉRIO TERCIÁRIO** |
| `numero_dfd` | varchar(50) | ℹ️ Informativo (DFD de origem) |
| `numero_contratacao` | varchar(50) | ℹ️ Informativo (número da contratação) |

---

## 📊 **Estratégias de Vinculação**

### **🎯 Estratégia 1: CRITÉRIO MÚLTIPLO (Recomendada)**

**Prioridade de Matching:**
1. **MATCH PERFEITO** - Área + Valor (±20%) + Similaridade do objeto
2. **MATCH BOM** - Área + Valor (±30%)
3. **MATCH ACEITÁVEL** - Área idêntica
4. **MATCH MANUAL** - Revisão caso a caso

**Algoritmo Proposto:**
```sql
-- Buscar vinculações por prioridade
SELECT p.id, p.area_requisitante, p.valor_total_contratacao, p.titulo_contratacao,
       CASE 
           WHEN p.area_requisitante = q.area_demandante 
                AND p.valor_total_contratacao BETWEEN (q.valor_estimado * 0.8) AND (q.valor_estimado * 1.2)
                AND p.titulo_contratacao LIKE CONCAT('%', SUBSTRING(q.objeto, 1, 30), '%') 
           THEN 'MATCH_PERFEITO'
           
           WHEN p.area_requisitante = q.area_demandante 
                AND p.valor_total_contratacao BETWEEN (q.valor_estimado * 0.7) AND (q.valor_estimado * 1.3)
           THEN 'MATCH_BOM'
           
           WHEN p.area_requisitante = q.area_demandante
           THEN 'MATCH_ACEITAVEL'
           
           ELSE 'MATCH_MANUAL'
       END as tipo_match
FROM pca_dados p, qualificacoes q
WHERE q.id = [ID_QUALIFICACAO]
ORDER BY tipo_match, ABS(p.valor_total_contratacao - q.valor_estimado)
```

### **🔄 Estratégia 2: VINCULAÇÃO INCREMENTAL**

**Fases de Implementação:**
1. **Fase 1:** Vinculações automáticas (matches perfeitos e bons)
2. **Fase 2:** Vinculações manuais (matches aceitáveis)
3. **Fase 3:** Revisão e correção

---

## 🛠 **Plano de Implementação**

### **📅 ETAPA 1: Preparação (Pré-requisitos)**

#### **1.1 Backup Completo**
```bash
# Via XAMPP MySQL Command Line
mysqldump -u root sistema_licitacao > backup_pre_migracao_$(date +%Y%m%d_%H%M%S).sql

# Ou via interface web
# Acesse: http://localhost/phpmyadmin
# Selecione: sistema_licitacao → Exportar → SQL → Executar
```

#### **1.2 Análise com Script Provisório**
```bash
# Acessar script de análise
http://localhost/sistema_licitacao/script_migracao_provisorio.php
```

**Checklist de Análise:**
- [ ] Revisar todas as 35 qualificações
- [ ] Identificar matches automáticos
- [ ] Documentar vinculações manuais necessárias
- [ ] Validar valores e áreas

### **📅 ETAPA 2: Modificação da Estrutura**

#### **2.1 Adicionar Campo `pca_dados_id`**
```sql
-- ⚠️ EXECUTAR EM AMBIENTE DE TESTE PRIMEIRO
USE sistema_licitacao;

-- Adicionar campo
ALTER TABLE qualificacoes 
ADD COLUMN pca_dados_id INT(11) NULL AFTER id;

-- Adicionar índice para performance
ALTER TABLE qualificacoes 
ADD INDEX idx_pca_dados_id (pca_dados_id);

-- Adicionar foreign key constraint (OPCIONAL - mas recomendado)
ALTER TABLE qualificacoes 
ADD CONSTRAINT fk_qualificacoes_pca_dados 
FOREIGN KEY (pca_dados_id) REFERENCES pca_dados(id) 
ON DELETE SET NULL 
ON UPDATE CASCADE;
```

#### **2.2 Verificar Estrutura**
```sql
-- Confirmar que o campo foi adicionado
DESCRIBE qualificacoes;

-- Verificar índices
SHOW INDEX FROM qualificacoes;

-- Verificar foreign keys
SELECT 
    CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_NAME = 'qualificacoes' AND TABLE_SCHEMA = 'sistema_licitacao';
```

### **📅 ETAPA 3: Migração dos Dados**

#### **3.1 Vinculações Automáticas (Matches Perfeitos)**
```sql
-- Exemplo de script para matches perfeitos
UPDATE qualificacoes q
INNER JOIN pca_dados p ON (
    p.area_requisitante = q.area_demandante 
    AND p.valor_total_contratacao BETWEEN (q.valor_estimado * 0.8) AND (q.valor_estimado * 1.2)
)
SET q.pca_dados_id = p.id
WHERE q.pca_dados_id IS NULL;
```

#### **3.2 Vinculações Manuais**
```sql
-- Baseado na análise do script provisório, executar:
UPDATE qualificacoes SET pca_dados_id = [ID_PCA] WHERE id = [ID_QUALIF];

-- Exemplo prático (SUBSTITUIR pelos IDs reais):
-- UPDATE qualificacoes SET pca_dados_id = 6698 WHERE id = 7;
-- UPDATE qualificacoes SET pca_dados_id = 6699 WHERE id = 8;
-- ... etc
```

### **📅 ETAPA 4: Validação e Testes**

#### **4.1 Verificações de Integridade**
```sql
-- Verificar quantas qualificações foram vinculadas
SELECT 
    COUNT(*) as total,
    COUNT(pca_dados_id) as vinculadas,
    COUNT(*) - COUNT(pca_dados_id) as sem_vinculo
FROM qualificacoes;

-- Verificar se todas as vinculações são válidas
SELECT COUNT(*) as vinculos_invalidos
FROM qualificacoes q
LEFT JOIN pca_dados p ON q.pca_dados_id = p.id
WHERE q.pca_dados_id IS NOT NULL AND p.id IS NULL;

-- Listar qualificações sem vinculação
SELECT id, nup, area_demandante, valor_estimado
FROM qualificacoes 
WHERE pca_dados_id IS NULL;
```

#### **4.2 Testes de Performance**
```sql
-- Teste de consulta com JOIN
SELECT q.nup, q.area_demandante, p.titulo_contratacao, p.numero_dfd
FROM qualificacoes q
INNER JOIN pca_dados p ON q.pca_dados_id = p.id
ORDER BY q.id;
```

### **📅 ETAPA 5: Atualização da Interface**

#### **5.1 Modificações em `qualificacao_dashboard.php`**
```php
// Adicionar JOIN na query principal
$query = "
    SELECT q.*, 
           p.titulo_contratacao, p.numero_dfd, p.area_requisitante,
           p.valor_total_contratacao, p.situacao_execucao
    FROM qualificacoes q
    LEFT JOIN pca_dados p ON q.pca_dados_id = p.id
    ORDER BY q.id DESC
";

// Exibir dados do PCA vinculado nos cards
echo '<div class="pca-info">';
echo '<strong>PCA Vinculado:</strong> ' . htmlspecialchars($row['numero_dfd'] ?? 'Não vinculado');
echo '<br><strong>Situação:</strong> ' . htmlspecialchars($row['situacao_execucao'] ?? '-');
echo '</div>';
```

#### **5.2 Adicionar Seletor de PCA**
```php
// Buscar PCAs disponíveis para vinculação
$pca_query = "
    SELECT id, numero_dfd, titulo_contratacao, area_requisitante, valor_total_contratacao
    FROM pca_dados 
    WHERE area_requisitante = ?
    ORDER BY numero_dfd DESC
";

// Interface de seleção
echo '<select name="pca_dados_id" class="form-control">';
echo '<option value="">Selecione o PCA...</option>';
foreach ($pcas_disponiveis as $pca) {
    echo '<option value="' . $pca['id'] . '">' . htmlspecialchars($pca['numero_dfd'] . ' - ' . $pca['titulo_contratacao']) . '</option>';
}
echo '</select>';
```

---

## 🚨 **Riscos e Mitigações**

### **⚠️ RISCOS IDENTIFICADOS**

| Risco | Probabilidade | Impacto | Mitigação |
|-------|---------------|---------|-----------|
| **Perda de dados durante migração** | Baixa | Alto | ✅ Backup completo obrigatório |
| **Vinculações incorretas** | Média | Médio | ✅ Script de análise + revisão manual |
| **Performance degradada** | Baixa | Baixo | ✅ Índices adequados + testes |
| **Incompatibilidade com dados existentes** | Baixa | Alto | ✅ Testes em ambiente separado |

### **🛡️ ESTRATÉGIAS DE ROLLBACK**

#### **Rollback Rápido (Campo apenas)**
```sql
-- Remover foreign key
ALTER TABLE qualificacoes DROP FOREIGN KEY fk_qualificacoes_pca_dados;

-- Remover índice
ALTER TABLE qualificacoes DROP INDEX idx_pca_dados_id;

-- Remover campo
ALTER TABLE qualificacoes DROP COLUMN pca_dados_id;
```

#### **Rollback Completo (Backup)**
```bash
# Restaurar backup completo
mysql -u root sistema_licitacao < backup_pre_migracao_YYYYMMDD_HHMMSS.sql
```

---

## 📈 **Benefícios Esperados**

### **🎯 Funcionalidades Novas**
- ✅ **Integração Planejamento ↔ Qualificação**
- ✅ **Rastreabilidade completa:** DFD → Qualificação → Licitação
- ✅ **Relatórios cruzados:** Status do PCA x Status da Qualificação
- ✅ **Dashboard unificado:** Visão completa do processo
- ✅ **Consistência de dados:** Valores e prazos sincronizados

### **📊 Melhorias de Processo**
- **Eficiência:** Menos retrabalho na entrada de dados
- **Controle:** Visão end-to-end do processo licitatório
- **Compliance:** Rastreabilidade exigida pela Lei 14.133/2021
- **Relatórios:** Análises mais precisas e completas

---

## ✅ **Checklist de Execução**

### **📋 Pré-Migração**
- [ ] Backup completo do banco de dados
- [ ] Execução do script de análise provisório
- [ ] Documentação de todas as vinculações manuais
- [ ] Teste em ambiente separado
- [ ] Comunicação com usuários sobre manutenção

### **📋 Durante a Migração**
- [ ] Executar scripts SQL na ordem correta
- [ ] Verificar cada etapa antes de prosseguir
- [ ] Documentar quaisquer problemas encontrados
- [ ] Validar integridade dos dados

### **📋 Pós-Migração**
- [ ] Testes de todas as funcionalidades
- [ ] Verificação de performance
- [ ] Atualização da documentação
- [ ] Treinamento dos usuários (se necessário)
- [ ] Monitoramento por 48h após deploy

---

## 📞 **Próximos Passos**

1. **Revisar este documento** e aprovar a estratégia
2. **Executar o script provisório:** `http://localhost/sistema_licitacao/script_migracao_provisorio.php`
3. **Analisar todas as 35 qualificações** e documentar vinculações
4. **Fazer backup completo** do banco de dados
5. **Executar migração em ambiente de teste** primeiro
6. **Aplicar em produção** após validação completa

---

## 📝 **Histórico de Versões**

| Versão | Data | Autor | Alterações |
|--------|------|-------|------------|
| 1.0 | 2025-01-XX | Claude AI | Criação inicial da estratégia |
| 1.1 | 2025-01-XX | Claude AI | Script de análise provisório adicionado |

---

**⚠️ IMPORTANTE:** Este documento deve ser revisado e aprovado antes da implementação. Qualquer dúvida ou ajuste necessário deve ser discutido antes de prosseguir com a migração.