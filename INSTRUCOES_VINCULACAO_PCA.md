# 🔗 **INSTRUÇÕES PARA VINCULAÇÃO PCA → QUALIFICAÇÕES**

## 📋 **Visão Geral**

O sistema agora permite vincular registros de **Qualificação** com registros do **PCA (Plano de Contratações Anual)**, criando rastreabilidade completa do processo licitatório.

---

## 🎯 **Como Funciona**

### **✅ Qualificações COM Vinculação**
- Mostram informações do **PCA vinculado** no card
- Exibem: DFD, Número da Contratação, Situação PCA

### **🔗 Qualificações SEM Vinculação**  
- Mostram seção amarela "Sem Vinculação PCA"
- Botão **"Vincular ao PCA"** disponível

---

## 🖱️ **Como Vincular**

### **Opção 1: Interface Web (Recomendada)**

1. **Acesse:** `http://localhost/sistema_licitacao/qualificacao_dashboard.php`

2. **Para qualificação sem vínculo:**
   - Clique no botão **"Vincular ao PCA"**
   - Digite no campo de busca:
     - **Número da contratação** (ex: "415/2025")
     - **DFD** (ex: "319/2025")  
     - **Título** (palavras-chave)

3. **Selecionar PCA:**
   - Clique no registro desejado (ficará azul)
   - Clique **"Confirmar Vinculação"**
   - Aguarde confirmação de sucesso

### **Opção 2: SQL Direto**

```sql
-- Template de vinculação manual
UPDATE qualificacoes 
SET pca_dados_id = [ID_DO_PCA] 
WHERE id = [ID_DA_QUALIFICACAO];

-- Exemplo prático:
UPDATE qualificacoes SET pca_dados_id = 6698 WHERE id = 7;
```

---

## 📊 **Identificando o PCA Correto**

### **🔍 Como Buscar Registros PCA**

```sql
-- Buscar por número da contratação (mais preciso)
SELECT id, numero_contratacao, numero_dfd, titulo_contratacao, area_requisitante, valor_total_contratacao
FROM pca_dados 
WHERE numero_contratacao LIKE '%415/2025%';

-- Buscar por área requisitante
SELECT id, numero_contratacao, numero_dfd, titulo_contratacao, area_requisitante, valor_total_contratacao
FROM pca_dados 
WHERE area_requisitante LIKE '%COGEP%';

-- Buscar por DFD
SELECT id, numero_contratacao, numero_dfd, titulo_contratacao, area_requisitante, valor_total_contratacao
FROM pca_dados 
WHERE numero_dfd LIKE '%319/2025%';
```

### **💡 Critérios de Vinculação**

| Critério | Peso | Como Usar |
|----------|------|-----------|
| **Número da Contratação** | 🔴 ALTO | Campo único - vinculação precisa |
| **DFD** | 🟡 MÉDIO | Pode ter vários itens na mesma contratação |
| **Área Requisitante** | 🟢 BAIXO | Pode ter muitas contratações da mesma área |
| **Valor** | 🟢 BAIXO | Apenas como confirmação |

---

## 📝 **Exemplo Prático de Vinculação**

### **Qualificação Exemplo:**
- **ID:** 9
- **NUP:** 25000.132017/2024-31
- **Área:** COGEP/SAA
- **Objeto:** Serviços de apoio administrativo

### **PCA Vinculado:**
- **ID:** 6703  
- **Contratação:** 280/2025
- **DFD:** 512/2024
- **Área:** SAA.COGEP.CODEP
- **Título:** Contratar vagas em ações de desenvolvimento relacionadas ao Tema Estratégia, Projetos e Processos

### **SQL da Vinculação:**
```sql
UPDATE qualificacoes SET pca_dados_id = 6703 WHERE id = 9;
```

---

## ✅ **Validação das Vinculações**

### **Consulta para Verificar Vínculos Criados:**
```sql
SELECT 
    q.id as qualif_id,
    q.nup,
    q.area_demandante,
    q.valor_estimado,
    p.id as pca_id,
    p.numero_contratacao,
    p.numero_dfd,
    p.area_requisitante,
    p.valor_total_contratacao
FROM qualificacoes q
LEFT JOIN pca_dados p ON q.pca_dados_id = p.id
ORDER BY q.id;
```

### **Consulta para Listar Sem Vinculação:**
```sql
SELECT id, nup, area_demandante, valor_estimado
FROM qualificacoes 
WHERE pca_dados_id IS NULL
ORDER BY area_demandante;
```

### **Estatísticas de Vinculação:**
```sql
SELECT 
    COUNT(*) as total_qualificacoes,
    COUNT(pca_dados_id) as vinculadas,
    COUNT(*) - COUNT(pca_dados_id) as sem_vinculo,
    ROUND((COUNT(pca_dados_id) * 100.0 / COUNT(*)), 1) as percentual_vinculado
FROM qualificacoes;
```

---

## 🔧 **Solução de Problemas**

### **❌ Erro: "Erro de conexão ao carregar PCA"**
**Causa:** API não está carregando os dados  
**Solução:** Verificar se `http://localhost/sistema_licitacao/api/get_pca_data.php` está acessível

### **❌ Erro: "Registro PCA não encontrado"** 
**Causa:** ID do PCA não existe na base  
**Solução:** Verificar se o ID existe na tabela `pca_dados`

### **❌ Erro: "Qualificação não encontrada"**
**Causa:** ID da qualificação não existe  
**Solução:** Verificar se o ID existe na tabela `qualificacoes`

### **⚠️ Vinculação não aparece na interface**
**Causa:** Cache do navegador ou erro no JOIN  
**Solução:** 
1. Atualizar página (F5)
2. Verificar se `pca_dados_id` foi salvo: `SELECT id, pca_dados_id FROM qualificacoes WHERE id = X;`

---

## 📈 **Benefícios da Vinculação**

### **🔍 Rastreabilidade Completa**
- **DFD** → **Qualificação** → **Licitação** → **Contrato**
- Visão end-to-end do processo

### **📊 Relatórios Integrados**
- Status do PCA x Status da Qualificação
- Análise de prazos e atrasos
- Economia e eficiência

### **⚡ Eficiência Operacional**
- Menos retrabalho na entrada de dados
- Consistência entre módulos
- Validação cruzada de informações

---

## 📋 **Lista de 35 Qualificações para Vincular**

Você pode usar esta consulta para ver todas as qualificações que precisam ser vinculadas:

```sql
SELECT 
    q.id,
    q.nup,
    q.area_demandante,
    q.objeto,
    q.valor_estimado,
    CASE 
        WHEN q.pca_dados_id IS NULL THEN 'SEM VÍNCULO' 
        ELSE CONCAT('VINCULADO (PCA ID: ', q.pca_dados_id, ')') 
    END as status_vinculacao
FROM qualificacoes q
ORDER BY 
    CASE WHEN q.pca_dados_id IS NULL THEN 0 ELSE 1 END,
    q.area_demandante;
```

---

## 🎯 **Próximos Passos**

1. **✅ Estrutura criada** - Campo, índice e foreign key implementados
2. **✅ Interface funcionando** - Seletor de PCA operacional  
3. **🔄 Vincular manualmente** - 34 qualificações restantes (1 já vinculada como exemplo)
4. **📊 Relatórios** - Implementar dashboards integrados (futuro)
5. **🔗 Licitações** - Expandir vinculação para módulo de licitações (futuro)

---

## 📞 **Suporte**

Se encontrar problemas:

1. **Verificar logs:** Console do navegador (F12)
2. **Testar API:** `http://localhost/sistema_licitacao/api/get_pca_data.php`
3. **Consultar banco:** Usar queries de validação acima
4. **Rollback:** Scripts disponíveis em `adicionar_campo_pca_dados_id.sql`

---

**📌 Lembre-se:** Use sempre o **número da contratação** como critério principal de busca, pois é um campo único que garante precisão na vinculação!