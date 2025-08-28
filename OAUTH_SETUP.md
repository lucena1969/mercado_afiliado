# Configuração OAuth - Google e Facebook

## 📋 Pré-requisitos

1. **Banco de Dados**: Execute a migração para adicionar campos OAuth:
   ```sql
   -- Execute o arquivo: database/add_oauth_fields.sql
   mysql -u root -p mercado_afiliado < database/add_oauth_fields.sql
   ```

2. **Dependências**: As bibliotecas OAuth já foram instaladas via Composer:
   - `league/oauth2-google`
   - `league/oauth2-facebook`

## 🔧 Configuração Google OAuth

### 1. Criar Projeto no Google Cloud Console
1. Acesse: https://console.cloud.google.com
2. Crie um novo projeto ou selecione existente
3. Vá em "APIs e Serviços" > "Credenciais"
4. Clique em "Criar credenciais" > "ID do cliente OAuth 2.0"

### 2. Configurar OAuth Consent Screen
1. Configure a tela de consentimento OAuth
2. Adicione domínio autorizado: `mercadoafiliado.com.br`
3. Adicione escopo: `openid`, `profile`, `email`

### 3. Configurar Cliente OAuth
- **Tipo**: Aplicação da web
- **URIs de origem**: `https://mercadoafiliado.com.br`
- **URIs de redirecionamento**: `https://mercadoafiliado.com.br/auth/google/callback`

### 4. Copiar Credenciais
Após criar, copie:
- **Client ID**
- **Client Secret**

### 5. Atualizar config/app.php
```php
define('GOOGLE_CLIENT_ID', 'SEU_CLIENT_ID_AQUI');
define('GOOGLE_CLIENT_SECRET', 'SEU_CLIENT_SECRET_AQUI');
```

## 📘 Configuração Facebook OAuth

### 1. Criar App no Facebook Developers
1. Acesse: https://developers.facebook.com
2. Clique em "Meus Apps" > "Criar App"
3. Selecione "Consumidor" e configure

### 2. Configurar Produto Facebook Login
1. Adicione produto "Facebook Login"
2. Configure URIs de redirecionamento válidos:
   - `https://mercadoafiliado.com.br/auth/facebook/callback`

### 3. Configurar Domínios do App
1. Vá em "Configurações" > "Básico"
2. Adicione domínio: `mercadoafiliado.com.br`

### 4. Configurar Permissões
- `email` (obrigatório)
- `public_profile` (obrigatório)

### 5. Copiar Credenciais
- **App ID**
- **Chave Secreta do App**

### 6. Atualizar config/app.php
```php
define('FACEBOOK_CLIENT_ID', 'SEU_APP_ID_AQUI');
define('FACEBOOK_CLIENT_SECRET', 'SEU_APP_SECRET_AQUI');
```

## 🔒 Configurações de Segurança

### URLs de Produção
Certifique-se de que as URLs estão configuradas corretamente:
- Google: `https://mercadoafiliado.com.br/auth/google/callback`
- Facebook: `https://mercadoafiliado.com.br/auth/facebook/callback`

### HTTPS Obrigatório
OAuth 2.0 requer HTTPS em produção. Certifique-se de que o site está configurado com SSL válido.

### State Parameter
O sistema já implementa validação do parâmetro `state` para prevenir ataques CSRF.

## 📊 Campos da Tabela Users

A migração adiciona os seguintes campos:
- `uuid` - Identificador único
- `phone` - Telefone (opcional)
- `avatar` - URL da foto de perfil
- `google_id` - ID único do Google
- `facebook_id` - ID único do Facebook
- `email_verified_at` - Data de verificação do email
- `last_login_at` - Último login

## 🚀 Como Funciona

1. **Login Social**: Usuário clica nos botões no template de login
2. **Redirecionamento**: Sistema redireciona para Google/Facebook
3. **Autorização**: Usuário autoriza a aplicação
4. **Callback**: Plataforma redireciona para nossa callback URL
5. **Processamento**: Sistema processa dados e faz login/registro
6. **Dashboard**: Usuário é redirecionado para o dashboard

## 🔍 Testando

### Desenvolvimento Local
Para testar localmente, configure URLs de desenvolvimento:
- `http://localhost/mercado_afiliado/auth/google/callback`
- `http://localhost/mercado_afiliado/auth/facebook/callback`

### URLs de Teste
- Login Google: `/auth/google`
- Login Facebook: `/auth/facebook`

## ⚠️ Importante

1. **Nunca commite credenciais**: Mantenha Client ID e Secret seguros
2. **Use HTTPS em produção**: OAuth requer conexão segura
3. **Configure domínios corretos**: URLs devem coincidir exatamente
4. **Execute a migração**: Banco deve ter campos OAuth
5. **Teste em ambiente seguro**: Use dados de teste primeiro

## 📞 Suporte

Em caso de problemas:
1. Verifique logs do servidor web
2. Confirme se URLs estão corretas
3. Teste credenciais OAuth
4. Verifique se migração foi executada