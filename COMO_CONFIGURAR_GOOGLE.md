# 🔧 Como Configurar Google OAuth

## ⚠️ Por que não funciona ainda?

**Resposta:** Você está usando **credenciais de teste** (`teste-google-id`), que são falsas. O Google OAuth precisa de credenciais reais.

---

## 🚀 Configuração Passo a Passo

### 1. **Google Cloud Console**
1. Acesse: https://console.cloud.google.com
2. **Criar novo projeto** ou selecionar existente
3. Nome sugerido: "Mercado Afiliado OAuth"

### 2. **Ativar APIs**
1. Vá em **"APIs e Serviços"** > **"Biblioteca"**
2. Procure por **"Google+ API"** 
3. Clique **"Ativar"**

### 3. **Configurar Tela de Consentimento**
1. **"APIs e Serviços"** > **"Tela de consentimento OAuth"**
2. Escolha **"Externo"**
3. Preencha:
   - **Nome do app:** Mercado Afiliado
   - **Email de suporte:** seu@email.com
   - **Domínios autorizados:** mercadoafiliado.com.br
   - **Email do desenvolvedor:** seu@email.com
4. **Salvar e continuar**

### 4. **Criar Credenciais OAuth**
1. **"APIs e Serviços"** > **"Credenciais"**
2. **"Criar credenciais"** > **"ID do cliente OAuth 2.0"**
3. Configurar:
   - **Tipo:** Aplicação da web
   - **Nome:** Mercado Afiliado Web
   - **URIs de origem autorizadas:**
     ```
     https://mercadoafiliado.com.br
     ```
   - **URIs de redirecionamento autorizados:**
     ```
     https://mercadoafiliado.com.br/auth/google/callback
     ```
4. **Criar**

### 5. **Copiar Credenciais**
Após criar, você verá:
- **ID do cliente:** `123456789-abcdefghijklmnop.apps.googleusercontent.com`
- **Chave secreta do cliente:** `ABCD-1234567890abcdefgh`

### 6. **Atualizar config/app.php**
```php
// Configurações OAuth - Google
define('GOOGLE_CLIENT_ID', '123456789-abcdefghijklmnop.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'ABCD-1234567890abcdefgh');
define('GOOGLE_REDIRECT_URI', BASE_URL . '/auth/google/callback');
```

---

## 🧪 Como Testar

### 1. **Teste de Configuração**
`https://mercadoafiliado.com.br/test_google_direct.php`

### 2. **Login Real**
1. `https://mercadoafiliado.com.br/login-manual`
2. Clicar **"Continuar com Google"**
3. **Resultado esperado:**
   - Redireciona para Google
   - Pede para autorizar aplicação
   - Volta para seu site logado

### 3. **Possíveis Erros e Soluções**

| Erro | Causa | Solução |
|------|-------|---------|
| `redirect_uri_mismatch` | URL callback incorreta | Verificar se está exatamente: `https://mercadoafiliado.com.br/auth/google/callback` |
| `invalid_client` | Credenciais erradas | Verificar Client ID e Secret |
| `access_denied` | Usuário cancelou | Normal, deixe o usuário tentar novamente |
| Página branca | Erro PHP | Verificar logs do servidor |

---

## ✅ Status Atual vs Desejado

### ❌ Estado Atual
- Credenciais: `teste-google-id` (falsas)
- Resultado: Erro ou mensagem de configuração
- OAuth: Não funciona

### ✅ Estado Desejado  
- Credenciais: Reais do Google Cloud
- Resultado: Login funcional com Google
- OAuth: 100% operacional

---

## 🎯 Checklist de Configuração

### Google Cloud Console
- [ ] Projeto criado
- [ ] Google+ API ativada
- [ ] Tela de consentimento configurada
- [ ] Credenciais OAuth criadas
- [ ] URLs de callback configuradas

### Seu Sistema
- [ ] Client ID real em config/app.php
- [ ] Client Secret real em config/app.php
- [ ] URLs de produção corretas
- [ ] Migração do banco executada

### Testes
- [ ] test_google_direct.php mostra "configurado"
- [ ] Login manual redireciona para Google
- [ ] Usuário consegue autorizar e voltar logado

---

## 🚨 Importante

### ⚠️ Para Desenvolvimento Local
Se testando em `localhost`, configure também:
- **URIs de origem:** `http://localhost`
- **URIs de callback:** `http://localhost/mercado_afiliado/auth/google/callback`

### ⚠️ Para Produção
URLs devem ser **exatamente**:
- Origem: `https://mercadoafiliado.com.br`  
- Callback: `https://mercadoafiliado.com.br/auth/google/callback`

### ⚠️ Segurança
- **NUNCA** commitar credenciais reais no código
- **Client Secret** deve ser mantido seguro
- **HTTPS** obrigatório em produção

---

## 🎉 Resultado Final

Após configuração completa:

1. **Usuário clica** "Continuar com Google"
2. **Sistema redireciona** para Google OAuth
3. **Google pede** autorização do usuário
4. **Usuário autoriza** aplicação
5. **Google retorna** dados do usuário
6. **Sistema cria/loga** usuário automaticamente
7. **Usuário acessa** dashboard

**OAuth Google 100% funcional!** 🚀