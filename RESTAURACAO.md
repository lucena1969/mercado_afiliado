# 🔄 GUIA DE RESTAURAÇÃO DO SISTEMA

## ✅ Sistema Restaurado ao Estado Original

Todos os arquivos foram revertidos para a versão funcional ANTES da implementação da verificação de email.

## 📋 Arquivos Removidos (causavam problemas)

- ❌ `api/auth/register.php` (versão com verificação)
- ❌ `api/verify-email.php`
- ❌ `api/resend-verification.php`
- ❌ `templates/auth/verify-email.php`
- ❌ `templates/emails/`
- ❌ `database/migrations/`
- ❌ `INSTALL_EMAIL_VERIFICATION.md`

## 📂 Arquivos Restaurados (versão funcional)

- ✅ `public/router.php` - SEM rota `/verify-email`
- ✅ `templates/dashboard/index.php` - SEM verificação de email
- ✅ `templates/auth/register.php` - Versão original

## 🚀 Como Restaurar no Servidor

### Opção 1: Deletar arquivos problemáticos no servidor

Delete estes arquivos do servidor via FTP/cPanel:
```
/api/auth/register.php
/api/verify-email.php
/api/resend-verification.php
/templates/auth/verify-email.php
/templates/emails/ (pasta inteira)
/database/migrations/ (pasta inteira)
```

### Opção 2: Fazer upload dos arquivos originais

Faça upload apenas destes 3 arquivos:
1. `public/router.php` (versão atual restaurada)
2. `templates/dashboard/index.php` (versão atual restaurada)
3. `templates/auth/register.php` (versão atual restaurada)

### Opção 3: Limpar tudo e refazer upload completo

1. Faça backup do servidor atual
2. Delete todos os arquivos PHP do servidor
3. Faça upload completo do diretório `/workspaces/mercado_afiliado/`

## 🧹 Limpeza do Navegador

**IMPORTANTE:** Limpe os cookies do navegador para `mercadoafiliado.com.br`

Chrome/Edge:
1. F12 → Application → Cookies
2. Delete todos os cookies do domínio

Firefox:
1. F12 → Storage → Cookies
2. Delete todos os cookies do domínio

## ✅ Verificação

Após restaurar, teste:
1. ✅ Home page carrega normalmente
2. ✅ Página de registro funciona
3. ✅ Login funciona
4. ✅ Dashboard acessível após login

## 📝 Estado Atual do Código Local

O código local foi restaurado usando `git checkout` para os arquivos:
- `public/router.php`
- `templates/dashboard/index.php`
- `templates/auth/register.php`

Todos os arquivos de verificação de email foram deletados.

---

**O sistema está agora no estado funcional anterior à implementação da verificação de email.**
