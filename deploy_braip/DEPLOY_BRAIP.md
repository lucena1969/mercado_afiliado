# Arquivos para Deploy - Integração Braip

## Arquivos Novos (criar no servidor)

### 1. INTEGRACAO_BRAIP.md
**Localização:** `/` (raiz do projeto)
**Descrição:** Documentação técnica completa da integração Braip
**Arquivo fonte:** `/workspaces/mercado_afiliado/INTEGRACAO_BRAIP.md`

### 2. app/models/BraipIntegration.php
**Localização:** `/app/models/BraipIntegration.php`
**Descrição:** Model de integração específico da Braip
**Arquivo fonte:** `/workspaces/mercado_afiliado/app/models/BraipIntegration.php`

---

## Arquivos Atualizados (substituir no servidor)

### 1. app/services/BraipService.php
**Localização:** `/app/services/BraipService.php`
**Descrição:** Service atualizado com processamento completo de webhooks
**Arquivo fonte:** `/workspaces/mercado_afiliado/app/services/BraipService.php`
**Alterações:**
- Adicionado suporte a autenticação via auth_key
- Implementado processamento de pagamentos e assinaturas
- Mapeamento completo de status
- Validação de webhooks

### 2. app/controllers/WebhookController.php
**Localização:** `/app/controllers/WebhookController.php`
**Arquivo fonte:** `/workspaces/mercado_afiliado/app/controllers/WebhookController.php`
**Alterações:**
- Adicionado suporte para auth_key da Braip (linha 369-372)
- Adicionado payload de teste Braip (linhas 451-476)

---

## Checklist de Deploy

### Antes do Deploy
- [ ] Fazer backup dos arquivos existentes no servidor
- [ ] Verificar permissões dos diretórios (755 para pastas, 644 para arquivos)

### Upload dos Arquivos

#### 1. Arquivos Novos
```bash
# Criar documentação
/INTEGRACAO_BRAIP.md

# Criar model
/app/models/BraipIntegration.php
```

#### 2. Arquivos Atualizados
```bash
# Atualizar service
/app/services/BraipService.php

# Atualizar controller
/app/controllers/WebhookController.php
```

### Após o Deploy

#### 1. Verificar Arquivos
- [ ] Verificar se todos os arquivos foram enviados corretamente
- [ ] Verificar permissões (chmod 644 nos arquivos PHP)
- [ ] Verificar propriedade (chown www-data:www-data se necessário)

#### 2. Testar Integração
- [ ] Acessar painel: https://seu-dominio.com/integrations
- [ ] Criar nova integração Braip
- [ ] Configurar API Key e Auth Key
- [ ] Testar webhook: https://seu-dominio.com/api/webhooks/braip/{token}?test=1

#### 3. Configurar na Braip
- [ ] Acessar https://ev.braip.com/login
- [ ] Ir em Ferramentas > API > Novo token
- [ ] Ir em Ferramentas > Postback > Nova documentação
- [ ] Configurar URL: https://seu-dominio.com/api/webhooks/braip/{token}
- [ ] Selecionar eventos recomendados
- [ ] Copiar Chave Única de autenticação

---

## Estrutura de Pastas no Servidor

```
seu-servidor/
├── INTEGRACAO_BRAIP.md                    [NOVO]
├── app/
│   ├── controllers/
│   │   └── WebhookController.php         [ATUALIZADO]
│   ├── models/
│   │   ├── BraipIntegration.php          [NOVO]
│   │   ├── EduzzIntegration.php          (existente)
│   │   └── Integration.php               (existente)
│   └── services/
│       ├── BraipService.php               [ATUALIZADO]
│       ├── EduzzService.php               (existente)
│       ├── HotmartService.php             (existente)
│       └── MonetizzeService.php           (existente)
```

---

## Comandos via FTP/SSH

### Via FTP (FileZilla, WinSCP, etc)
1. Conectar ao servidor
2. Navegar até a pasta raiz do projeto
3. Upload dos arquivos novos
4. Sobrescrever arquivos atualizados

### Via SSH
```bash
# Conectar ao servidor
ssh usuario@seu-servidor.com

# Navegar até o projeto
cd /caminho/do/projeto

# Upload dos arquivos (exemplo com scp)
scp usuario@origem:/workspaces/mercado_afiliado/INTEGRACAO_BRAIP.md .
scp usuario@origem:/workspaces/mercado_afiliado/app/models/BraipIntegration.php app/models/
scp usuario@origem:/workspaces/mercado_afiliado/app/services/BraipService.php app/services/
scp usuario@origem:/workspaces/mercado_afiliado/app/controllers/WebhookController.php app/controllers/

# Ajustar permissões
chmod 644 INTEGRACAO_BRAIP.md
chmod 644 app/models/BraipIntegration.php
chmod 644 app/services/BraipService.php
chmod 644 app/controllers/WebhookController.php

# Se necessário, ajustar propriedade
chown www-data:www-data INTEGRACAO_BRAIP.md
chown www-data:www-data app/models/BraipIntegration.php
chown www-data:www-data app/services/BraipService.php
chown www-data:www-data app/controllers/WebhookController.php
```

---

## Verificação de Funcionamento

### 1. Teste de Criação de Integração
```
URL: https://seu-dominio.com/integrations/add
- Selecionar plataforma: Braip
- Preencher nome da integração
- Informar API Key
- Informar API Secret (Auth Key)
- Salvar
```

### 2. Teste de Webhook
```
URL: https://seu-dominio.com/api/webhooks/braip/{seu-token}?test=1
Método: POST
```

Resposta esperada:
```json
{
  "success": true,
  "message": "Webhook processado com sucesso"
}
```

### 3. Verificar Logs
```bash
# No servidor
tail -f /var/log/apache2/error.log
# ou
tail -f /var/log/nginx/error.log
```

---

## Troubleshooting

### Erro: Class 'BraipService' not found
- Verificar se o arquivo está em `/app/services/BraipService.php`
- Verificar permissões do arquivo
- Verificar se o autoload está configurado

### Erro: Class 'BraipIntegration' not found
- Verificar se o arquivo está em `/app/models/BraipIntegration.php`
- Verificar se está sendo carregado no controller

### Webhook não recebe dados
- Verificar se a URL está correta na Braip
- Verificar se o token está correto
- Verificar logs do servidor
- Testar com `?test=1` primeiro

### Erro 500
- Verificar logs do PHP
- Verificar sintaxe dos arquivos
- Verificar dependências (Integration.php, Database.php, etc)

---

## Links Úteis

- **Documentação Braip:** https://ev.braip.com (Ferramentas > Postback > Documentação)
- **Painel Integração:** https://seu-dominio.com/integrations
- **API Webhooks:** https://seu-dominio.com/api/webhooks/braip/{token}
- **Logs de Webhook:** https://seu-dominio.com/integrations/test

---

**Data:** 2025-01-05
**Versão:** 1.0
**Status:** Pronto para deploy
