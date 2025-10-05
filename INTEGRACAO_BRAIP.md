# Integração com Braip - Documentação Técnica

## Sobre a Braip

A Braip é uma plataforma completa de CRO (Conversion Rate Optimization) para produtores e afiliados comercializarem produtos físicos e digitais. Além da produção e venda, conta com solução integrada de pagamentos.

## Configuração da Integração

### 1. Gerando Token API

1. Acesse sua conta Braip em https://ev.braip.com/login
2. No menu lateral, clique em **Ferramentas > API**
3. Clique em **Novo token**
4. Forneça uma descrição breve
5. Salve para gerar o token

### 2. Configurando Webhook (Postback)

1. No painel Braip, vá em **Ferramentas → Postback**
2. Clique em **Nova documentação**
3. Configure os seguintes parâmetros:

#### URL de Retorno (Webhook)
```
https://seu-dominio.com/webhook/braip
```

#### Método HTTP
- **POST** (obrigatório)

#### Chave de Autenticação
- Copie a **Chave Única** disponível em Ferramentas > Postback > Documentação
- Esta chave será usada para autenticar os webhooks recebidos

### 3. Seleção de Eventos

#### Eventos de Pagamento
Recomenda-se selecionar os seguintes eventos:

- ✅ **Pagamento Aprovado** - Quando o pagamento é confirmado
- ✅ **Aguardando Pagamento** - Boleto gerado ou aguardando confirmação
- ✅ **Cancelada** - Venda cancelada
- ✅ **Chargeback** - Contestação de pagamento
- ✅ **Devolvida** - Reembolso processado
- ✅ **Parcialmente Pago** - Pagamento parcial de parcelamento
- ✅ **Pagamento Atrasado** - Atraso no pagamento
- ⚪ **Em Análise** - Pagamento em análise antifraude
- ⚪ **Estorno Pendente** - Estorno solicitado
- ⚪ **Em Processamento** - Pagamento sendo processado
- ⚪ **Alteração de Vencimento de Boleto** - Boleto com vencimento alterado
- ⚪ **Código de rastreio adicionado** - Para produtos físicos

#### Eventos de Assinatura
Para produtos recorrentes, selecione:

- ✅ **Ativa** - Assinatura ativa
- ✅ **Atrasada** - Pagamento recorrente atrasado
- ✅ **Cancelada pelo suporte** - Cancelamento via suporte
- ✅ **Cancelada pelo cliente** - Cliente cancelou
- ✅ **Cancelada pelo vendedor** - Vendedor cancelou
- ✅ **Cancelada pela plataforma** - Plataforma cancelou
- ✅ **Inativa** - Assinatura inativa
- ✅ **Vencida** - Assinatura vencida

### 4. Seleção de Produtos

- Selecione os produtos específicos que devem enviar notificações
- Ou configure para **todos os produtos**

## Estrutura do Webhook

### Autenticação

A Braip envia um parâmetro de autenticação em cada webhook:

```php
$_POST['basic_authentication'] // ou $_GET['basic_authentication']
```

**Importante:** Sempre valide este token contra sua chave configurada antes de processar os dados.

### Campos Principais do Payload

Com base nos padrões de integração da Braip, os seguintes campos são enviados:

#### Informações do Cliente
```json
{
  "client_name": "Nome do Cliente",
  "client_email": "cliente@email.com",
  "client_cel": "+5511999999999",
  "client_document": "12345678900"
}
```

#### Informações da Transação
```json
{
  "trans_id": "TRANS123456",
  "trans_status": "approved",
  "trans_value": "197.00",
  "trans_currency": "BRL",
  "trans_payment_method": "credit_card",
  "trans_installments": 1,
  "trans_date": "2025-01-15 10:30:00"
}
```

#### Informações do Produto
```json
{
  "prod_id": "123456",
  "prod_name": "Nome do Produto",
  "prod_code": "PROD-CODE-123",
  "prod_value": "197.00",
  "prod_type": "digital"
}
```

#### Informações da Comissão (Afiliado)
```json
{
  "aff_id": "789",
  "aff_name": "Nome do Afiliado",
  "aff_email": "afiliado@email.com",
  "commission_value": "78.80",
  "commission_percentage": "40"
}
```

#### Informações de Assinatura (se aplicável)
```json
{
  "subscription_id": "SUB123456",
  "subscription_status": "active",
  "subscription_plan": "monthly",
  "subscription_next_charge": "2025-02-15"
}
```

### Status de Transação

| Status | Descrição |
|--------|-----------|
| `approved` | Pagamento aprovado |
| `waiting_payment` | Aguardando pagamento |
| `cancelled` | Cancelado |
| `chargeback` | Chargeback |
| `refunded` | Reembolsado |
| `partially_paid` | Parcialmente pago |
| `late_payment` | Pagamento atrasado |
| `under_analysis` | Em análise |
| `pending_refund` | Estorno pendente |
| `processing` | Em processamento |

### Métodos de Pagamento

- `credit_card` - Cartão de crédito
- `debit_card` - Cartão de débito
- `boleto` - Boleto bancário
- `pix` - PIX
- `two_cards` - Dois cartões

## Fluxo de Processamento

### 1. Recebimento do Webhook

```php
// Exemplo de processamento
$payload = json_decode(file_get_contents('php://input'), true);

// Se não vier como JSON, tentar POST/GET
if (empty($payload)) {
    $payload = $_POST ?: $_GET;
}
```

### 2. Validação da Autenticação

```php
$auth_key = $payload['basic_authentication'] ?? '';
$expected_key = 'SUA_CHAVE_UNICA_BRAIP';

if ($auth_key !== $expected_key) {
    http_response_code(401);
    exit('Unauthorized');
}
```

### 3. Processamento do Evento

```php
$event_type = $payload['trans_status'] ?? 'unknown';

switch ($event_type) {
    case 'approved':
        // Processar pagamento aprovado
        // - Ativar acesso do cliente
        // - Registrar comissão do afiliado
        // - Enviar email de confirmação
        break;

    case 'cancelled':
        // Processar cancelamento
        // - Remover acesso do cliente
        // - Cancelar comissão do afiliado
        break;

    case 'chargeback':
        // Processar chargeback
        // - Bloquear acesso
        // - Reverter comissão
        break;

    // ... outros eventos
}
```

### 4. Resposta ao Webhook

```php
http_response_code(200);
echo json_encode(['status' => 'success']);
```

## Mapeamento de Eventos para Conversões

### Facebook Pixel / Meta CAPI

```javascript
// Pagamento Aprovado
fbq('track', 'Purchase', {
  value: trans_value,
  currency: 'BRL',
  content_ids: [prod_id],
  content_type: 'product'
});
```

### Google Analytics 4

```javascript
// Pagamento Aprovado
gtag('event', 'purchase', {
  transaction_id: trans_id,
  value: trans_value,
  currency: 'BRL',
  items: [{
    item_id: prod_id,
    item_name: prod_name,
    price: prod_value
  }]
});
```

### TikTok Pixel

```javascript
// Pagamento Aprovado
ttq.track('CompletePayment', {
  content_id: prod_id,
  content_name: prod_name,
  value: trans_value,
  currency: 'BRL'
});
```

## Tratamento de Erros

### Códigos de Resposta HTTP

- **200 OK** - Webhook processado com sucesso
- **401 Unauthorized** - Falha na autenticação
- **400 Bad Request** - Dados inválidos
- **500 Internal Server Error** - Erro no servidor

### Log de Eventos

Sempre registre:
- Data/hora do recebimento
- Payload completo
- Status do processamento
- Erros (se houver)
- Conversões disparadas

## Teste da Integração

### 1. Ambiente de Testes

A Braip permite testar webhooks através da interface em:
**Ferramentas > Postback > Testar Webhook**

### 2. Ferramentas Recomendadas

- **NGROK** - Para expor localhost para testes
- **RequestBin** - Para capturar webhooks de teste
- **Postman** - Para simular requisições

### 3. Checklist de Validação

- [ ] Token API configurado corretamente
- [ ] URL do webhook acessível
- [ ] Chave de autenticação validada
- [ ] Eventos selecionados adequadamente
- [ ] Produtos vinculados ao postback
- [ ] Logs registrando webhooks recebidos
- [ ] Conversões sendo disparadas corretamente
- [ ] Emails de notificação funcionando
- [ ] Dashboard atualizando em tempo real

## Segurança

### Boas Práticas

1. **Sempre valide a chave de autenticação**
2. **Use HTTPS** para o endpoint do webhook
3. **Limite taxa de requisições** (rate limiting)
4. **Valide estrutura dos dados** antes de processar
5. **Registre tentativas de acesso não autorizado**
6. **Não exponha credenciais** em logs ou respostas
7. **Implemente retry logic** para falhas temporárias
8. **Monitore webhooks duplicados**

## Troubleshooting

### Webhook não está sendo recebido

1. Verifique se a URL está acessível publicamente
2. Confirme que HTTPS está configurado
3. Verifique firewall e regras de segurança
4. Teste com ferramentas como NGROK ou RequestBin
5. Confira logs do servidor web

### Autenticação falhando

1. Confirme que a chave está correta
2. Verifique se não há espaços extras
3. Teste se a chave está sendo enviada no payload

### Eventos não disparando conversões

1. Verifique se os pixels estão configurados
2. Confirme IDs de pixel corretos
3. Teste com extensões de debug (Meta Pixel Helper)
4. Verifique console do navegador para erros

## Suporte

- **Central de Ajuda:** https://ajuda.braip.com/
- **Login Braip:** https://ev.braip.com/login
- **Email Suporte:** (verificar na plataforma)

## Referências

- Documentação oficial disponível em Ferramentas > Postback > Documentação
- Ferramentas de integração disponíveis no painel Braip
- Comunidade de desenvolvedores e integradores

---

**Última atualização:** Janeiro 2025
**Versão da API:** v1 (verificar versão atual na plataforma)
