# 📘 Integração Eduzz - Guia Completo

## 🎯 Visão Geral

Este documento descreve como configurar e usar a integração com a **Eduzz** no Mercado Afiliado.

A integração foi desenvolvida baseada na documentação oficial da Eduzz (https://developers.eduzz.com) e utiliza:
- **OAuth2** para autenticação
- **Webhooks** para receber eventos de vendas em tempo real
- **API REST** para consultas

---

## 🔧 Configuração Inicial

### 1. Criar Aplicativo na Eduzz

1. Acesse https://console.eduzz.com
2. Crie um novo aplicativo
3. Anote as credenciais:
   - **Client ID**
   - **Client Secret**
4. Configure a URL de redirecionamento OAuth2

### 2. Obter Access Token

Existem duas formas de obter o token:

#### Opção A: Personal Token (apenas para testes)
1. No Console Eduzz, vá em "Application Listing"
2. Clique em "Copy access token"
3. ⚠️ **NÃO USE EM PRODUÇÃO**

#### Opção B: OAuth2 (produção)
Fluxo completo de autenticação:

```php
// 1. Redirecionar usuário para autorização
$auth_url = EduzzService::getAuthorizationUrl(
    $client_id,
    $redirect_uri,
    ['webhook_read', 'webhook_write']
);
header('Location: ' . $auth_url);

// 2. Após autorização, trocar código por token
$token_data = EduzzService::getAccessToken(
    $client_id,
    $client_secret,
    $code,
    $redirect_uri
);

$access_token = $token_data['access_token'];
```

### 3. Salvar Integração no Dashboard

1. Acesse o dashboard > IntegraSync > Nova Integração
2. Selecione **Eduzz**
3. Cole o **Access Token** no campo "API Key"
4. No campo "API Secret", cole o **originSecret** do seu produtor
5. Clique em "Criar integração"
6. **Copie a URL do webhook gerada**

---

## 🔔 Configurar Webhooks

### 1. No Console Eduzz

1. Acesse https://console.eduzz.com
2. Selecione seu aplicativo
3. Vá em **Webhooks** > **Adicionar Webhook**
4. Cole a URL do webhook copiada anteriormente
5. Selecione o evento: **`myeduzz.invoice_paid`**
6. Salve a configuração

### 2. Eventos Suportados

| Evento | Descrição | Status |
|--------|-----------|--------|
| `myeduzz.invoice_paid` | Fatura paga | ✅ Implementado |
| `myeduzz.invoice_refunded` | Fatura reembolsada | 🔄 Futuro |
| `myeduzz.invoice_cancelled` | Fatura cancelada | 🔄 Futuro |
| `myeduzz.invoice_chargeback` | Chargeback | 🔄 Futuro |

---

## 📦 Estrutura do Webhook

### Formato Recebido da Eduzz

```json
{
  "id": "zszf0uk65g701io8dbsckfeld",
  "event": "myeduzz.invoice_paid",
  "data": {
    "id": "12345678",
    "status": "paid",
    "buyer": {
      "name": "João Silva",
      "email": "joao@email.com",
      "document": "12345678901"
    },
    "producer": {
      "id": "1454585458",
      "name": "Produtor",
      "email": "produtor@eduzz.com",
      "originSecret": "originsecrettest"
    },
    "utm": {
      "source": "facebook",
      "campaign": "black-friday",
      "medium": "cpc"
    },
    "price": {
      "currency": "BRL",
      "value": 197.00
    },
    "paid": {
      "currency": "BRL",
      "value": 197.00
    },
    "items": [
      {
        "productId": "P567",
        "name": "Curso Completo",
        "price": {
          "currency": "BRL",
          "value": 197.00
        }
      }
    ],
    "affiliate": {
      "id": "123",
      "name": "Afiliado Teste",
      "email": "afiliado@email.com"
    },
    "paymentMethod": "creditCard",
    "installments": 3,
    "paidAt": "2024-01-10T17:45:00.000Z"
  },
  "sentDate": "2024-01-20T15:00:00.000Z"
}
```

---

## 🔐 Segurança: Validação do Webhook

O webhook é validado usando o campo **`originSecret`**:

1. A Eduzz envia `data.producer.originSecret` no payload
2. Comparamos com o originSecret armazenado na integração
3. Se não bater, o webhook é rejeitado com HTTP 401

**Como obter o originSecret:**
- Vem no próprio webhook da Eduzz
- Salve-o no campo `api_secret` ou `config_json['origin_secret']` da integração

---

## 📊 Dados Capturados

A integração captura automaticamente:

### Informações da Venda
- ✅ ID da transação
- ✅ Valor total
- ✅ Status (paid, refunded, cancelled)
- ✅ Método de pagamento
- ✅ Número de parcelas
- ✅ Data de criação
- ✅ Data de pagamento

### Informações do Cliente
- ✅ Nome completo
- ✅ Email
- ✅ CPF/CNPJ
- ✅ Telefone

### Informações do Produto
- ✅ ID do produto
- ✅ Nome do produto
- ✅ Preço

### Informações do Afiliado
- ✅ ID do afiliado
- ✅ Nome do afiliado
- ✅ Email do afiliado

### Tracking (UTMs)
- ✅ utm_source
- ✅ utm_campaign
- ✅ utm_medium
- ✅ utm_content

---

## 🧪 Testando a Integração

### 1. Teste Simples (API)

```bash
curl -X GET "https://api.eduzz.com/accounts/v1/me" \
  -H "Authorization: Bearer SEU_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### 2. Teste do Webhook

Execute o script de teste:

```bash
php test_eduzz_integration.php
```

### 3. Simulador de Webhook

Use o payload de exemplo para testar:

```bash
curl -X POST "https://seusite.com/api/webhooks/eduzz/SEU_TOKEN" \
  -H "Content-Type: application/json" \
  -d @eduzz_paga.txt
```

---

## ⚙️ Arquivos da Integração

| Arquivo | Descrição |
|---------|-----------|
| `app/models/EduzzIntegration.php` | Modelo com lógica específica |
| `app/controllers/EduzzController.php` | Controlador de webhooks |
| `app/services/EduzzService.php` | Cliente da API |
| `api/webhooks/eduzz.php` | Endpoint do webhook |

---

## 🚨 Troubleshooting

### Erro: "Token não encontrado"
- Verifique se a URL do webhook está correta
- Confirme que o token na URL é válido

### Erro: "Assinatura inválida"
- Verifique se o `originSecret` está salvo corretamente
- O originSecret deve estar em `api_secret` ou `config_json['origin_secret']`

### Webhook não chega
- Verifique se configurou o evento correto: `myeduzz.invoice_paid`
- Teste a URL do webhook manualmente
- Verifique logs do servidor

### Vendas não aparecem
- Verifique se o webhook foi processado (tabela `webhook_events`)
- Veja os logs de erro (tabela `sync_logs`)
- Execute o teste da integração

---

## 📞 Suporte

- **Documentação Eduzz:** https://developers.eduzz.com
- **Console Eduzz:** https://console.eduzz.com
- **Discord Eduzz:** https://discord.com/eduzz

---

## ✅ Checklist de Configuração

- [ ] Criar aplicativo no Console Eduzz
- [ ] Obter Access Token (OAuth2 ou Personal Token)
- [ ] Criar integração no dashboard
- [ ] Copiar URL do webhook
- [ ] Configurar webhook no Console Eduzz (evento: `myeduzz.invoice_paid`)
- [ ] Salvar originSecret na integração
- [ ] Testar integração via API
- [ ] Fazer venda de teste
- [ ] Verificar se venda aparece no dashboard

---

**🎉 Integração concluída! Suas vendas da Eduzz serão sincronizadas automaticamente.**
