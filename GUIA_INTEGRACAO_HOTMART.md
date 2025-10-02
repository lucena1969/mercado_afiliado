# 🔗 Guia Completo - Integração Hotmart

## 📋 **Processo Completo de Integração**

### **🎯 1. Criar Nova Integração no IntegraSync**

1. **Acesse:** https://mercadoafiliado.com.br/integrations
2. **Clique:** "Nova integração" 
3. **Selecione:** "Hotmart" na lista de plataformas
4. **Preencha os dados:**

#### **📝 Campos obrigatórios:**

**Plataforma:** Hotmart *(selecionada)*  
**Nome da integração:** Ex: "Minha conta Hotmart Principal"

#### **🔐 Credenciais (2 opções):**

##### **OPÇÃO 1 - Basic Token (RECOMENDADO):**
- **API Key:** *(pode deixar vazio)*
- **API Secret:** Cole o token completo que começa com `Basic abc123...`

##### **OPÇÃO 2 - OAuth Tradicional:**
- **API Key:** Client ID
- **API Secret:** Client Secret *(sem "Basic " no início)*

### **🏢 2. Configurar Credenciais na Hotmart**

#### **Como obter as credenciais:**

1. **Acesse:** [Hotmart Club](https://club.hotmart.com)
2. **Navegue:** Ferramentas → Integrações → API
3. **Clique:** "Gerar credenciais"
4. **Baixe:** o arquivo .txt com as 3 credenciais:
   - Client ID
   - Client Secret  
   - Basic *(token completo)*

#### **✅ Dica importante:**
Use o **Basic token** - é mais simples e estável!

### **🔄 3. Testar a Integração**

Após criar a integração:

1. **Clique no botão ⚙️** da integração criada
2. **Configure as credenciais** seguindo as opções acima
3. **Marque:** "Validar credenciais antes de salvar"
4. **Clique:** "Salvar Configuração"

**✅ Se válida:** Aparecerá "Configuração salva com sucesso!"  
**❌ Se inválida:** Mostrará erro específico para correção

### **🎣 4. Configurar Webhook na Hotmart**

Após configurar credenciais com sucesso:

1. **Copie a URL do webhook** mostrada na confirmação
2. **Na Hotmart:** Ferramentas → Notificações → Webhooks  
3. **Adicione nova URL:** cole a URL copiada
4. **Marque os eventos:**
   - ✅ Compra aprovada
   - ✅ Compra cancelada  
   - ✅ Reembolso
   - ✅ Chargeback

### **🔄 5. Sincronizar Dados**

Com tudo configurado:

1. **Clique no botão 🔄** da integração
2. **Escolha o tipo:**
   - **Sincronização completa:** produtos + vendas
   - **Apenas produtos:** lista de produtos disponíveis
   - **Apenas vendas:** transações do período
3. **Defina período:** últimos X dias (padrão: 30)
4. **Inicie sincronização**

### **📊 6. Acompanhar Resultados**

Após a sincronização:

- **Dashboard:** Métricas consolidadas
- **Relatórios:** Vendas por período, produto, etc.
- **Logs:** Histórico de sincronizações em "Teste & Logs"

---

## 🐛 **Resolução de Problemas Comuns**

### **❌ "Credenciais inválidas"**
**Soluções:**
1. Verifique se copiou o Basic token completo (com "Basic " no início)
2. Certifique-se que as credenciais não expiraram
3. Teste com OAuth (Client ID + Secret) se Basic não funcionar

### **❌ "Usuário não autenticado"** 
**Solução:** Refaça login no Mercado Afiliado

### **❌ "Integração não encontrada"**
**Solução:** Verifique se a integração foi criada corretamente

### **❌ Webhook não recebe eventos**
**Soluções:**
1. Verifique se a URL está correta na Hotmart
2. Confirme se os eventos estão marcados
3. Teste com uma venda pequena primeiro

---

## 📈 **Fluxo Completo de Dados**

```
1. CRIAR INTEGRAÇÃO → Gerar webhook URL
2. CONFIGURAR CREDENCIAIS → Validar conexão 
3. CONFIGURAR WEBHOOK → Receber eventos automáticos
4. SINCRONIZAR DADOS → Buscar histórico
5. ACOMPANHAR MÉTRICAS → Dashboard atualizado
```

---

## 🎯 **Checklist Final**

- [ ] ✅ Integração criada no IntegraSync
- [ ] ✅ Credenciais configuradas e válidas  
- [ ] ✅ Webhook URL configurada na Hotmart
- [ ] ✅ Eventos marcados no webhook
- [ ] ✅ Primeira sincronização executada
- [ ] ✅ Dados aparecendo no dashboard

**🎉 Pronto! Sua integração Hotmart está funcionando!**