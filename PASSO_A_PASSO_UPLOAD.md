# 🚀 PASSO A PASSO - Upload OAuth

## ⚡ UPLOAD RÁPIDO (Ordem Crítica)

### 🎯 PASSO 1: Dependências OAuth (MAIS IMPORTANTE!)
```
📁 COMPRIMIR: vendor/
📤 UPLOAD: vendor.zip para raiz do servidor
🗂️ EXTRAIR: vendor.zip no servidor
📍 RESULTADO: /home/u590097272/domains/mercadoafiliado.com.br/public_html/vendor/
```

### 🔧 PASSO 2: Configurações
**Substituir estes arquivos:**
- `config/app.php`
- `composer.json`

### 💻 PASSO 3: Código OAuth
**Substituir estes arquivos:**
- `app/controllers/AuthController.php`
- `app/models/User.php`
- `public/router.php`

**Criar este arquivo novo:**
- `api/auth/oauth.php`

### 🎨 PASSO 4: Template
**Substituir:**
- `templates/auth/login.php`

### 🗄️ PASSO 5: Banco de Dados
**Executar no servidor via browser:**
```
https://mercadoafiliado.com.br/migrate_oauth.php
```
**OU via phpMyAdmin:** importar `database/add_oauth_simple.sql`

---

## 📋 CHECKLIST DE UPLOAD

### ✅ Antes do Upload
- [ ] Pasta `vendor/` comprimida
- [ ] Credenciais OAuth em `config/app.php` (pode usar as de teste)
- [ ] Todos os arquivos listados prontos

### ✅ Durante o Upload
- [ ] Upload `vendor.zip` primeiro
- [ ] Extrair `vendor.zip` no servidor
- [ ] Upload arquivos modificados
- [ ] Verificar permissões dos arquivos

### ✅ Após Upload
- [ ] Teste: https://mercadoafiliado.com.br/debug_oauth.php
- [ ] Executar: https://mercadoafiliado.com.br/migrate_oauth.php
- [ ] Teste: https://mercadoafiliado.com.br/login
- [ ] Verificar botões OAuth aparecem

---

## 🛠️ COMANDOS ÚTEIS

### Via cPanel File Manager
1. **Upload vendor.zip**
2. **Clicar com direito → Extract**
3. **Upload outros arquivos**

### Via FTP
```bash
# Comprimir vendor localmente
7z a vendor.zip vendor/

# Upload via FTP client
# Extrair no servidor
```

### Via Terminal SSH (se disponível)
```bash
cd /home/u590097272/domains/mercadoafiliado.com.br/public_html/
unzip vendor.zip
chmod -R 755 vendor/
php migrate_oauth.php
```

---

## 🔍 VERIFICAÇÃO FINAL

### 1. Teste Dependências
**URL:** https://mercadoafiliado.com.br/debug_oauth.php
**Esperado:**
- ✅ Autoload existe
- ✅ Google OAuth disponível
- ✅ Facebook OAuth disponível

### 2. Teste Login
**URL:** https://mercadoafiliado.com.br/login
**Esperado:**
- Botões "Google" e "Facebook" visíveis
- Divisor "ou continue com" aparece

### 3. Teste Rotas (deve dar erro de credenciais, não 404)
**URLs:**
- https://mercadoafiliado.com.br/auth/google
- https://mercadoafiliado.com.br/auth/facebook
**Esperado:** "Credenciais Google não configuradas" (não 404)

### 4. Teste Banco
**Executar:** `DESCRIBE users;` no phpMyAdmin  
**Esperado:** Campos `uuid`, `google_id`, `facebook_id`, etc.

---

## ⚠️ PROBLEMAS COMUNS

| Problema | Causa | Solução |
|----------|-------|---------|
| Botões OAuth não aparecem | vendor/ não enviado | Upload vendor.zip |
| Erro 404 nas rotas | router.php não atualizado | Substituir router.php |
| "Class not found" | autoload não funcionando | Verificar vendor/autoload.php |
| Erro de banco | Migração não executada | Executar migrate_oauth.php |

---

## 🎯 RESUMO EXECUTIVO

**CRÍTICO:**
1. Upload pasta `vendor/` (15-20MB)
2. Substituir 6 arquivos principais
3. Executar migração do banco

**RESULTADO:**
- ✅ Login com Google/Facebook funcionando
- ✅ Sistema compatível com OAuth 2.0
- ✅ Fallback gracioso se OAuth indisponível

**TEMPO ESTIMADO:** 15-30 minutos