# Lista de Arquivos - Deploy Braip

## 📦 Pacote: deploy_braip.zip (20 KB)

### 📋 Arquivos Incluídos

| Arquivo | Tipo | Tamanho | Destino no Servidor | Ação |
|---------|------|---------|---------------------|------|
| **README.txt** | Documentação | 4.6 KB | `/` (raiz) | Leitura obrigatória |
| **INTEGRACAO_BRAIP.md** | Documentação | 9.1 KB | `/` (raiz) | Opcional |
| **DEPLOY_BRAIP.md** | Instruções | 6.2 KB | `/` (raiz) | Consulta |
| **app/models/BraipIntegration.php** | Model | 13 KB | `/app/models/` | **CRIAR** |
| **app/services/BraipService.php** | Service | 19.9 KB | `/app/services/` | **SUBSTITUIR** |
| **app/controllers/WebhookController.php** | Controller | 20 KB | `/app/controllers/` | **SUBSTITUIR** |

---

## 🎯 Ações Necessárias

### ✅ Arquivos para CRIAR (não existem no servidor)

1. **app/models/BraipIntegration.php**
   - Modelo específico da Braip
   - Valida credenciais e webhooks
   - Processa dados da plataforma

### 🔄 Arquivos para SUBSTITUIR (já existem, fazer backup antes)

1. **app/services/BraipService.php**
   - **Backup recomendado:** `app/services/BraipService.php.backup`
   - Atualização: Processamento completo de webhooks
   - Mudanças: +600 linhas de código

2. **app/controllers/WebhookController.php**
   - **Backup recomendado:** `app/controllers/WebhookController.php.backup`
   - Atualização: Suporte à Braip
   - Mudanças: Linhas 369-476

---

## 📝 Ordem de Instalação Recomendada

### Passo 1: Backup
```bash
cp app/services/BraipService.php app/services/BraipService.php.backup
cp app/controllers/WebhookController.php app/controllers/WebhookController.php.backup
```

### Passo 2: Upload
1. Upload de `app/models/BraipIntegration.php` (novo)
2. Upload de `app/services/BraipService.php` (substituir)
3. Upload de `app/controllers/WebhookController.php` (substituir)

### Passo 3: Permissões
```bash
chmod 644 app/models/BraipIntegration.php
chmod 644 app/services/BraipService.php
chmod 644 app/controllers/WebhookController.php
```

### Passo 4: Teste
- Acesse: `https://seu-dominio.com/integrations/add`
- Crie integração Braip
- Teste webhook

---

## 🔍 Verificação de Integridade

### Tamanhos Esperados
- `BraipIntegration.php`: ~13 KB
- `BraipService.php`: ~20 KB
- `WebhookController.php`: ~20 KB

### Linhas de Código
- `BraipIntegration.php`: ~370 linhas
- `BraipService.php`: ~550 linhas
- `WebhookController.php`: ~490 linhas (com Braip)

---

## ⚠️ Avisos Importantes

1. **Não deletar arquivos existentes** sem fazer backup
2. **Verificar permissões** após upload
3. **Testar imediatamente** após deploy
4. **Manter logs** durante os testes
5. **Configurar webhook na Braip** após validar funcionamento

---

## 📞 Checklist Final

- [ ] Backup realizado
- [ ] Arquivos enviados
- [ ] Permissões ajustadas
- [ ] Integração criada no painel
- [ ] Webhook testado com `?test=1`
- [ ] Webhook configurado na Braip
- [ ] Chave única configurada
- [ ] Eventos selecionados
- [ ] Teste com transação real (opcional)
- [ ] Logs verificados

---

**Arquivo:** `deploy_braip.zip`
**Localização:** `/workspaces/mercado_afiliado/deploy_braip.zip`
**Tamanho:** 20 KB
**Arquivos:** 6 principais + 3 documentação
**Data:** 2025-10-05
