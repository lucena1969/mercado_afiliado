# 📊 Relatórios Específicos SAA - Documentação

## 📋 Visão Geral

Sistema de relatórios gerenciais desenvolvido especificamente para as **Coordenações da Secretaria de Atenção à Saúde (SAA)**, permitindo acompanhamento detalhado dos DFDs (Documentos de Formalização de Demanda) por coordenação.

---

## 🏢 Coordenações SAA Monitoradas

O sistema monitora **8 coordenações SAA** no segundo nível hierárquico:

1. **SAA.CGDI** - Coordenação Geral de Documentação e Informação
   - Inclui: ARQUIVO, DCCMS, EDITORA, CODINF

2. **SAA.CGINFRA** - Coordenação Geral de Infraestrutura

3. **SAA.CGSA** - Coordenação Geral de Saúde

4. **SAA.CGOF** - Coordenação Geral de Orçamento e Finanças
   - Inclui: COMAP

5. **SAA.COGEP** - Coordenação Geral de Planejamento
   - Inclui: CODEP, COASS

6. **SAA.CGCON** - Coordenação Geral de Contratos
   - Inclui: DIFSEP

7. **SAA.COGAD** - Coordenação Geral Administrativa

8. **SAA.CGENG** - Coordenação Geral de Engenharia

---

## 📈 Tipos de Relatórios

### 1. **DFDs Abertos por Coordenação** 📂

**Objetivo:** Acompanhar todos os DFDs em execução por coordenação SAA.

**Critérios de Inclusão:**
- DFDs com situação diferente de: Concluído, Revogado, Anulado, Cancelado
- Inclui: Não iniciados, Em preparação, Em andamento, etc.

**Informações Exibidas:**
- Número do DFD
- Título da contratação (primeiros 80 caracteres)
- Categoria da contratação
- Situação de execução
- Status do prazo (No Prazo / Vencendo / Atrasado)
- Dias em aberto
- Valor total
- Possui licitação vinculada (SIM/NÃO)

**Agrupamento:**
- Por coordenação SAA (segundo nível)
- Ordenado por: dias aberto (decrescente) + valor (decrescente)

**Estatísticas Gerais:**
- Total de DFDs abertos
- Valor total
- DFDs com licitação vinculada
- Taxa de vinculação (%)

**Formato de Saída:**
- HTML (visualização + impressão)
- CSV (Excel compatível)

**Cores e Badges:**
- 🟢 **Verde (No Prazo)**: Data de conclusão futura (>30 dias)
- 🟡 **Amarelo (Vencendo)**: Data de conclusão em até 30 dias
- 🔴 **Vermelho (Atrasado)**: Data de conclusão já passou

---

### 2. **DFDs Não Iniciados por Coordenação** ⚠️

**Objetivo:** Identificar contratações planejadas que ainda não começaram a execução.

**Critérios de Inclusão:**
- Situação de execução: NULL, vazio ou "Não iniciado"

**Informações Exibidas:**
- Número do DFD
- Título da contratação (primeiros 60 caracteres)
- Categoria da contratação
- Data de início planejada
- Data de conclusão planejada
- Dias sem início (desde data planejada)
- Prazo restante até conclusão
- Valor total
- Criticidade (CRÍTICO / ALERTA / NORMAL)

**Agrupamento:**
- Por coordenação SAA
- Ordenado por: dias sem início (decrescente) + valor (decrescente)

**Estatísticas Gerais:**
- Total de DFDs não iniciados
- Valor total
- Situações críticas (>60 dias sem início)
- Valor médio

**Alertas Automáticos:**
- ⚠️ **Alerta de DFDs Críticos**: Mostra quantidade de DFDs com mais de 60 dias sem início

**Criticidade:**
- 🔴 **CRÍTICO**: >60 dias sem início OU <30 dias até prazo
- 🟡 **ALERTA**: >30 dias sem início OU <60 dias até prazo
- ⚫ **NORMAL**: Demais casos

**Formato de Saída:**
- HTML (visualização + impressão)
- CSV (Excel compatível)

**Destaque Visual:**
- Linhas urgentes (críticas) com fundo vermelho claro (#fee)

---

### 3. **DFDs Em Andamento por Coordenação** 🔄

**Objetivo:** Monitorar contratações em execução ativa.

**Critérios de Inclusão:**
- Situação de execução contendo: "andamento", "preparação", "edição", "execução"

**Informações Exibidas:**
- Número do DFD
- Título da contratação (primeiros 70 caracteres)
- Situação de execução
- Dias em andamento (desde início)
- Dias para conclusão (até prazo)
- Alerta de prazo (No Prazo / Atenção / Atrasado)
- Valor total
- Possui licitação vinculada (SIM/NÃO)

**Agrupamento:**
- Por coordenação SAA
- Ordenado por: dias em andamento (decrescente) + valor (decrescente)

**Estatísticas Gerais:**
- Total de DFDs em andamento
- Valor total
- DFDs com licitação
- Taxa de vinculação (%)

**Alertas de Prazo:**
- 🔴 **Atrasado**: Data de conclusão já passou
- 🟡 **Atenção**: Conclusão em até 30 dias
- 🟢 **No Prazo**: Mais de 30 dias até conclusão

**Formato de Saída:**
- HTML (visualização + impressão)
- CSV (Excel compatível)

**Destaque Visual:**
- Linhas atrasadas: fundo vermelho claro (#fee)
- Linhas com atenção: fundo amarelo claro (#fff9e6)

---

## 🔧 Como Usar

### **Acesso aos Relatórios**

1. Acesse: **Relatórios Gerenciais** → **Planejamento (PCA)**
2. Selecione o **Ano do PCA** (2022-2026)
3. Na seção **"Relatórios Específicos SAA"**, escolha o tipo de relatório:
   - DFDs Abertos por Coordenação
   - DFDs Não Iniciados por Coordenação
   - DFDs Em Andamento por Coordenação

### **Filtros Disponíveis**

#### **Filtro por Coordenação Específica:**
- Use o dropdown **"Coordenações SAA"** para filtrar por uma coordenação específica
- Ao selecionar, o filtro de "Área Requisitante" será automaticamente sincronizado

#### **Aplicar Filtro e Gerar:**
- Selecione a coordenação desejada (ou deixe "Todas")
- Clique no botão **"Gerar Relatório"**
- O relatório será aberto em nova aba

### **Exportação**

Cada relatório pode ser exportado em 2 formatos:

1. **HTML**:
   - Visualização completa com cores e formatação
   - Botão "Imprimir Relatório" para salvar em PDF via navegador
   - Gráficos e estatísticas visuais

2. **CSV**:
   - Compatível com Excel
   - UTF-8 com BOM para caracteres especiais
   - Separador: ponto-e-vírgula (;)
   - Formato: `nome_relatorio_ano_data-hora.csv`

---

## 📊 Estrutura dos Dados

### **Fontes de Dados:**

- **Tabela Principal:** `pca_dados`
- **Tabela de Licitações:** `licitacoes` (LEFT JOIN)
- **Tabela de Importações:** `pca_importacoes` (filtro por ano)

### **Agrupamento por DFD:**

Todos os relatórios agrupam dados por `numero_dfd` para:
- Evitar duplicação de itens
- Somar valores totais corretamente
- Mostrar informações consolidadas

### **Filtro de Coordenações:**

```sql
WHERE (
    area_requisitante LIKE 'SAA.CGDI.ARQUIVO%' OR
    area_requisitante LIKE 'SAA.CGINFRA%' OR
    area_requisitante LIKE 'SAA.CGSA%' OR
    ... (todas as 13 coordenações)
)
```

---

## 🎨 Design e UX

### **Cores por Tipo de Relatório:**

1. **DFDs Abertos**: 🔵 Azul (`#3498db`)
2. **DFDs Não Iniciados**: 🔴 Vermelho (`#e74c3c`)
3. **DFDs Em Andamento**: 🟡 Laranja (`#f39c12`)

### **Layout Responsivo:**

- Desktop: Tabelas completas com todas as colunas
- Impressão: Otimizado para A4 paisagem
- Ícones Lucide: Interface moderna e consistente

### **Cards de Estatísticas:**

Cada relatório exibe 4 cards com:
- Total de DFDs
- Valor total
- Métricas específicas (licitação, criticidade, etc.)
- Taxas percentuais

---

## 🔍 Exemplos de Uso

### **Cenário 1: Acompanhamento Geral da SAA**

**Objetivo:** Ver todos os DFDs abertos de todas as coordenações SAA

**Passos:**
1. Acesse Relatórios Gerenciais → Planejamento
2. Selecione ano: 2025
3. Deixe "Todas as Coordenações" no filtro
4. Clique em "Gerar Relatório" (DFDs Abertos)

**Resultado:** Relatório agrupado por 13 coordenações com estatísticas gerais

---

### **Cenário 2: Identificar DFDs Críticos de uma Coordenação**

**Objetivo:** Ver DFDs não iniciados da SAA.CGINFRA que estão críticos

**Passos:**
1. Selecione ano: 2025
2. Filtro "Coordenações SAA": SAA.CGINFRA
3. Clique em "Gerar Relatório" (DFDs Não Iniciados)

**Resultado:**
- Lista filtrada apenas da SAA.CGINFRA
- Alerta vermelho se houver DFDs com >60 dias sem início
- Badge de criticidade em cada DFD

---

### **Cenário 3: Monitorar Execução em Tempo Real**

**Objetivo:** Acompanhar DFDs em andamento que estão próximos do prazo

**Passos:**
1. Selecione ano: 2025
2. Deixe "Todas as Coordenações"
3. Clique em "Gerar Relatório" (DFDs Em Andamento)

**Resultado:**
- Visualização de todos os DFDs em execução
- Identificação visual de atrasados (fundo vermelho)
- Alertas de DFDs vencendo em até 30 dias (amarelo)

---

## 📁 Arquivos do Sistema

### **Backend (PHP):**

```
/relatorios/relatorios_saa.php
```
- Único arquivo com todos os 3 relatórios
- Funções separadas por tipo
- Exportação HTML e CSV
- ~1000 linhas de código

### **Frontend (Interface):**

```
/relatorios_gerenciais.php
```
- Seção "Relatórios Específicos SAA" (linhas 1053-1085)
- Dropdown "Coordenações SAA" (linhas 1001-1024)
- Funções JavaScript: `gerarRelatorioSAA()` e `filtrarPorCoordenacaoSAA()`

---

## 🚀 Melhorias Futuras

### **Planejadas:**
- [ ] Gráficos de pizza por coordenação
- [ ] Exportação em PDF nativo (TCPDF)
- [ ] Comparativo multi-ano
- [ ] Envio automático por e-mail
- [ ] Dashboard consolidado SAA
- [ ] Filtros por faixa de valor
- [ ] Alertas automáticos de prazos

### **Em Análise:**
- [ ] Integração com sistema de notificações
- [ ] Histórico de mudanças por DFD
- [ ] Análise de tendências por coordenação
- [ ] Relatório executivo mensal automatizado

---

## 📞 Suporte

**Desenvolvedor:** Sistema CGLIC - Ministério da Saúde
**Última Atualização:** Janeiro 2025
**Versão:** v1.0 - Relatórios SAA Implementados

---

## 📝 Changelog

### **v1.0 (Janeiro 2025)**
- ✅ Implementação dos 3 relatórios SAA
- ✅ Filtro específico de Coordenações SAA
- ✅ Exportação HTML e CSV
- ✅ Design responsivo com ícones Lucide
- ✅ Alertas de criticidade automáticos
- ✅ Integração com Relatórios Gerenciais

---

**🎯 Objetivo Final:** Proporcionar às coordenações SAA visibilidade total sobre seus processos de contratação, permitindo identificação rápida de gargalos, atrasos e necessidades de intervenção.
