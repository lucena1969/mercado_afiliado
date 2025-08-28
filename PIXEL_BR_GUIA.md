# 🎯 Pixel BR - Guia de Instalação e Uso

## 📋 Visão Geral
O **Pixel BR** é um sistema completo de tracking de eventos compatível com LGPD, integrado ao Mercado Afiliado. Permite coletar eventos de conversão e enviá-los automaticamente para Meta (Facebook), Google Ads e TikTok via server-side APIs.

## 🚀 Instalação

### 1. Executar Scripts SQL
Execute os seguintes arquivos SQL no phpMyAdmin, **nesta ordem**:

```sql
-- 1. Primeiro (se necessário)
CREATE DATABASE IF NOT EXISTS mercado_afiliado;

-- 2. Tabelas base
SOURCE create_users_table.sql;

-- 3. Tabelas do Pixel BR
SOURCE database/pixel_schema.sql;
```

### 2. Verificar Instalação
Acesse: `http://localhost/mercado_afiliado/test_connection.php`
- ✅ Deve mostrar todas as tabelas criadas
- ✅ Teste de inserção deve funcionar

## 📊 Como Usar

### 1. Acessar Interface
- **Login:** `http://localhost/mercado_afiliado/public/login`
- **Pixel BR:** Menu lateral → "🎯 Pixel BR"

### 2. Configurar Pixel
1. **Nome do Pixel:** Ex: "Pixel Principal"
2. **Integração:** (Opcional) Vincular a uma integração existente
3. **Configurações:**
   - ✅ Rastrear page views automaticamente
   - ✅ Modo consentimento LGPD
4. **Salvar** → **Ativar Pixel**

### 3. Instalar no Site
1. Na aba **"Código"**
2. Copiar o snippet JavaScript
3. Colar antes da tag `</head>` do seu site

```html
<script>window.PIXELBR_COLLECTOR_URL = "http://localhost/mercado_afiliado/api/pixel/collect.php";</script>
<script src="http://localhost/mercado_afiliado/public/assets/js/pixel/pixel_br.js?user_id=123&debug=false" async></script>
```

## 🎯 Eventos de Tracking

### Automáticos
- **Page View:** Disparado automaticamente na carga da página

### Manuais
```javascript
// Lead
PixelBR.trackLead({
    email: 'usuario@exemplo.com',
    phone: '+5511999999999'
});

// Compra
PixelBR.trackPurchase({
    value: 197.00,
    currency: 'BRL',
    order_id: 'PEDIDO-123',
    email: 'usuario@exemplo.com',
    product_name: 'Produto Exemplo'
});

// Evento customizado
PixelBR.track('custom', {
    custom_data: { evento: 'botao_cta_click' }
});
```

### Consentimento LGPD
```javascript
// Conceder consentimento
PixelBR.consentGrant();

// Negar consentimento
PixelBR.consentDeny();
```

## 🌉 CAPI Bridges

### Facebook/Meta
1. **Pixel ID:** Obtido no Gerenciador de Eventos do Facebook
2. **Access Token:** Token de sistema de longa duração
3. **Test Event Code:** (Opcional) Para modo de teste

### Google Ads
1. **Conversion ID:** Formato `AW-123456789`
2. **Conversion Label:** Identificador da conversão

### TikTok
1. **Pixel Code:** Código do pixel TikTok
2. **Access Token:** Token de API do TikTok Business

## 📈 Monitoramento

### Eventos em Tempo Real
- **Aba "Eventos"** → Lista todos os eventos coletados
- **Detalhes:** Clique em "Ver" para informações completas
- **UTMs:** Rastreamento automático de campanhas

### Métricas
- **Total de eventos:** Últimos 30 dias
- **Page Views, Leads, Compras**
- **Taxa de consentimento**
- **Status dos bridges**

## 🔧 Troubleshooting

### Erro 404 no Coletor
```bash
# Verificar se o arquivo existe
ls -la api/pixel/collect.php

# URL correta deve ser:
http://localhost/mercado_afiliado/api/pixel/collect.php
```

### Eventos Não Aparecem
1. **Verificar console:** F12 → Console (procurar erros JavaScript)
2. **Testar coletor:** Use `debug_paths.php` 
3. **Verificar banco:** Consultar tabela `pixel_events`

### Bridges Não Funcionam
1. **Tokens válidos:** Verificar se não expiraram
2. **Logs:** Consultar tabela `bridge_logs`
3. **Teste manual:** Usar ferramentas de API das plataformas

## 📝 Estrutura de Dados

### Tabela `pixel_events`
```sql
SELECT event_name, source_url, utm_campaign, custom_data_json, consent_status 
FROM pixel_events 
WHERE user_id = 123 
ORDER BY created_at DESC;
```

### Tabela `bridge_logs`
```sql
SELECT platform, status, error_message, created_at
FROM bridge_logs 
WHERE pixel_event_id IN (SELECT id FROM pixel_events WHERE user_id = 123)
ORDER BY created_at DESC;
```

## 🎓 Casos de Uso

### E-commerce
```javascript
// Página do produto
PixelBR.track('page_view', { product_id: 'PROD-123' });

// Carrinho
PixelBR.track('add_to_cart', { 
    custom_data: { value: 99.90, currency: 'BRL' } 
});

// Checkout
PixelBR.trackPurchase({
    value: 99.90,
    currency: 'BRL',
    order_id: 'ORDER-456',
    email: 'cliente@exemplo.com'
});
```

### Lead Generation
```javascript
// Formulário de contato
PixelBR.trackLead({
    email: 'lead@exemplo.com',
    phone: '+5511888888888',
    custom_data: { fonte: 'formulario_contato' }
});
```

### Campanhas de Afiliado
```javascript
// URL com UTMs: ?utm_source=facebook&utm_campaign=promo2024
// O pixel captura automaticamente os UTMs
PixelBR.trackPurchase({
    value: 197.00,
    order_id: 'AFF-789',
    email: 'comprador@exemplo.com'
});
```

## 🔐 Compliance LGPD

### Recursos Inclusos
- ✅ **Consentimento granular:** Usuário controla coleta de dados
- ✅ **Hash de PII:** Emails/telefones são hasheados (SHA-256)
- ✅ **Logs de auditoria:** Todos os eventos são registrados
- ✅ **Retenção configurável:** Definir tempo de armazenamento
- ✅ **Direito ao esquecimento:** Limpeza de dados por usuário

### Implementação Padrão
O pixel já inicia em modo **"granted"** (consentido), mas você pode implementar um banner de cookies:

```javascript
// Verificar consentimento existente
const consent = localStorage.getItem('pixelbr_consent') || 'granted';

// Mostrar banner se necessário
if (consent === 'denied') {
    showCookieBanner();
}
```

## 📚 APIs de Integração

### Coletor Principal
```
POST /api/pixel/collect.php
Content-Type: application/json

{
  "event_name": "purchase",
  "event_time": 1640995200,
  "event_id": "unique-event-id",
  "user_id": 123,
  "custom_data": {
    "value": 197.00,
    "currency": "BRL"
  }
}
```

### Resposta de Sucesso
```json
{
  "ok": true,
  "dispatch": {
    "event_id": 456,
    "bridges_triggered": 2
  }
}
```

---

## 🆘 Suporte

Para dúvidas técnicas:
1. **Testes:** Use `test_pixel.php` para diagnósticos
2. **Debug:** Use `debug_paths.php` para verificar caminhos
3. **Logs:** Consulte as tabelas do banco de dados
4. **Documentação:** Consulte `DOCS.md` para arquitetura completa

**Status:** ✅ Sistema em produção e totalmente funcional!