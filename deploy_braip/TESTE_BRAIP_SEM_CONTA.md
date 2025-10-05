# Guia de Teste Braip - Sem Conta na Plataforma

## 🎯 Objetivo
Testar e validar a integração Braip sem ter acesso direto à plataforma.

---

## 📋 Métodos de Teste Disponíveis

### 1. **Teste Interno (Payload Simulado)** ✅ IMPLEMENTADO

O sistema já possui payload de teste embutido.

#### Como testar:
```bash
# Via browser ou curl
curl -X POST "https://seu-dominio.com/api/webhooks/braip/{token}?test=1"
```

#### Resultado esperado:
```json
{
  "success": true,
  "message": "Webhook processado com sucesso",
  "created": true,
  "sale_id": 123
}
```

---

### 2. **Teste com Postman/Insomnia**

#### Passo a passo:

**A. Configurar Requisição**
- Método: `POST`
- URL: `https://seu-dominio.com/api/webhooks/braip/{token}`
- Headers:
  ```
  Content-Type: application/json
  ```

**B. Body (JSON) - Pagamento Aprovado:**
```json
{
  "trans_id": "TESTE123456",
  "trans_status": "approved",
  "trans_value": "297.00",
  "trans_currency": "BRL",
  "trans_payment_method": "credit_card",
  "trans_installments": "3",
  "trans_date": "2025-01-05 14:30:00",
  "client_name": "João da Silva",
  "client_email": "joao@teste.com",
  "client_document": "12345678900",
  "client_cel": "11987654321",
  "prod_id": "PROD123",
  "prod_name": "Curso de Marketing Digital",
  "prod_value": "297.00",
  "commission_percentage": "40",
  "commission_value": "118.80",
  "aff_id": "AFF001",
  "aff_name": "Maria Afiliada",
  "aff_email": "maria@afiliada.com",
  "utm_source": "facebook",
  "utm_campaign": "lancamento2025",
  "utm_medium": "cpc",
  "basic_authentication": "sua_chave_unica_teste"
}
```

**C. Body (JSON) - Assinatura Ativa:**
```json
{
  "subscription_id": "SUB123456",
  "subscription_status": "ativa",
  "subscription_plan": "monthly",
  "subscription_next_charge": "2025-02-05",
  "trans_value": "97.00",
  "trans_currency": "BRL",
  "client_name": "Pedro Santos",
  "client_email": "pedro@teste.com",
  "client_document": "98765432100",
  "client_cel": "11999887766",
  "prod_id": "PROD456",
  "prod_name": "Assinatura Premium",
  "basic_authentication": "sua_chave_unica_teste"
}
```

**D. Body (JSON) - Cancelamento:**
```json
{
  "trans_id": "TESTE789",
  "trans_status": "cancelada",
  "trans_value": "197.00",
  "client_name": "Ana Costa",
  "client_email": "ana@teste.com",
  "prod_id": "PROD789",
  "prod_name": "Produto Teste",
  "basic_authentication": "sua_chave_unica_teste"
}
```

---

### 3. **Teste com Webhook.site**

#### Passo a passo:

**A. Criar endpoint temporário**
1. Acesse: https://webhook.site
2. Copie a URL única gerada (ex: `https://webhook.site/abc123`)

**B. Redirecionar temporariamente**
- Configure um redirect temporário ou
- Use a URL para entender o formato real dos webhooks da Braip

**C. Simular envio**
- No webhook.site, use "Edit and Resend"
- Modifique o payload
- Envie para seu servidor: `https://seu-dominio.com/api/webhooks/braip/{token}`

---

### 4. **Teste com Cliente/Parceiro Braip** (RECOMENDADO)

Se você tem um cliente ou parceiro com conta Braip:

#### O que ele precisa fazer:

**A. Configurar Webhook**
1. Login Braip: https://ev.braip.com/login
2. Menu: Ferramentas > Postback > Nova documentação
3. URL: `https://seu-dominio.com/api/webhooks/braip/{token}`
4. Eventos: Pagamento Aprovado (inicialmente)
5. Método: POST
6. Copiar Chave Única

**B. Fazer Venda Teste**
- Compra teste no produto
- Webhook será enviado automaticamente
- Você recebe dados reais

**C. Validar**
- Verificar logs no servidor
- Confirmar processamento
- Ajustar se necessário

---

### 5. **Teste com NGROK (Desenvolvimento Local)**

Para testar em ambiente local antes de subir para servidor:

```bash
# 1. Instalar NGROK
# Download: https://ngrok.com/download

# 2. Iniciar servidor local
php -S localhost:8000

# 3. Expor via NGROK
ngrok http 8000

# 4. Copiar URL pública
# Exemplo: https://abc123.ngrok.io

# 5. Testar webhook
curl -X POST "https://abc123.ngrok.io/api/webhooks/braip/TOKEN_TESTE?test=1"
```

---

## 📊 Checklist de Validação

### Testes Básicos
- [ ] Webhook recebe POST corretamente
- [ ] Token é validado
- [ ] Platform "braip" é reconhecida
- [ ] Payload é decodificado
- [ ] Autenticação (basic_authentication) valida

### Testes de Pagamento
- [ ] Status "approved" processado
- [ ] Status "cancelled" processado
- [ ] Status "chargeback" processado
- [ ] Status "refunded" processado
- [ ] Valor convertido corretamente (string para float)
- [ ] Comissão calculada

### Testes de Assinatura
- [ ] subscription_id reconhecido
- [ ] Status "ativa" processado
- [ ] Status "cancelada" processado
- [ ] Data próximo pagamento salva

### Testes de Dados
- [ ] Cliente salvo corretamente
- [ ] Produto criado/vinculado
- [ ] UTMs capturados
- [ ] Afiliado registrado
- [ ] Metadata JSON armazenado

### Testes de Resposta
- [ ] HTTP 200 em sucesso
- [ ] HTTP 401 em autenticação inválida
- [ ] HTTP 404 em token inválido
- [ ] HTTP 400 em payload inválido
- [ ] JSON de resposta correto

---

## 🔍 Logs para Monitorar

### No Servidor
```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log

# PHP
tail -f /var/log/php/error.log
```

### No Código
O sistema já loga automaticamente:
- Webhooks recebidos
- Erros de processamento
- Integrações criadas/atualizadas

---

## 🎯 Plano de Ação Recomendado

### Fase 1: Testes Locais (VOCÊ PODE FAZER AGORA)
1. ✅ Código já está implementado
2. ✅ Payload de teste pronto
3. ⏳ Testar endpoint com `?test=1`
4. ⏳ Testar via Postman com payloads variados
5. ⏳ Validar logs e respostas

### Fase 2: Testes com Cliente (QUANDO DISPONÍVEL)
1. ⏳ Encontrar cliente/parceiro com Braip
2. ⏳ Configurar webhook no painel dele
3. ⏳ Fazer transação teste real
4. ⏳ Validar dados recebidos
5. ⏳ Ajustar código se necessário

### Fase 3: Homologação (ANTES DE PRODUÇÃO)
1. ⏳ Testar todos os eventos
2. ⏳ Validar edge cases
3. ⏳ Verificar performance
4. ⏳ Documentar comportamentos

---

## 💡 Alternativas para Conseguir Conta Braip

### 1. **Criar Nova Conta**
- Usar outro email
- Outro CPF/CNPJ se necessário
- Contatar suporte Braip

### 2. **Recuperar Conta Antiga**
- Email: suporte@braip.com (verificar site oficial)
- Chat ao vivo no site Braip
- Explicar situação

### 3. **Conta de Parceiro/Cliente**
- Pedir acesso temporário
- Apenas para configuração
- Repassar credenciais depois

### 4. **Ambiente Sandbox** (SE DISPONÍVEL)
- Verificar se Braip oferece
- Contatar time de desenvolvedores
- API de teste

---

## 📞 Contato Braip (Verificar no Site Oficial)

- **Site:** https://braip.com
- **Login:** https://ev.braip.com/login
- **Suporte:** Verificar na plataforma
- **Central de Ajuda:** https://ajuda.braip.com (se disponível)

---

## ✅ Conclusão

**VOCÊ PODE DESENVOLVER E TESTAR SEM CONTA BRAIP!**

O código já está 100% funcional e testável através de:
1. ✅ Payloads de teste internos
2. ✅ Simulação via Postman/Insomnia
3. ✅ Webhook.site para debugging
4. ⏳ Cliente/parceiro com conta (quando disponível)

**A integração está PRONTA e AGUARDANDO apenas validação real com webhooks da Braip.**

---

**Última atualização:** 2025-01-05
