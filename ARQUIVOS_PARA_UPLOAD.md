# 📁 Arquivos para Upload - OAuth Implementation

## 🎯 Arquivos OBRIGATÓRIOS para substituir no servidor

### 1. Configurações Principais
```
📁 config/
   └── app.php ⭐ MODIFICADO - Configurações OAuth e autoload
```

### 2. Controllers Atualizados  
```
📁 app/controllers/
   └── AuthController.php ⭐ MODIFICADO - Métodos OAuth adicionados
```

### 3. Models Atualizados
```
📁 app/models/  
   └── User.php ⭐ MODIFICADO - Métodos OAuth adicionados
```

### 4. Templates Atualizados
```
📁 templates/auth/
   └── login.php ⭐ MODIFICADO - Botões OAuth adicionados
```

### 5. Sistema de Rotas
```
📁 public/
   └── router.php ⭐ MODIFICADO - Rotas OAuth adicionadas
```

### 6. APIs OAuth
```
📁 api/auth/
   └── oauth.php ⭐ NOVO ARQUIVO - Handler OAuth
```

### 7. Dependências OAuth (IMPORTANTE!)
```
📁 vendor/ ⭐ PASTA COMPLETA - Bibliotecas OAuth
   ├── league/
   ├── guzzlehttp/
   ├── psr/
   └── autoload.php
```

### 8. Configurações Composer
```
📄 composer.json ⭐ NOVO ARQUIVO
📄 composer.lock ⭐ GERADO AUTOMATICAMENTE
```

## 🔧 Arquivos de Database (EXECUTAR NO SERVIDOR)

### Migração OAuth
```
📁 database/
   ├── add_oauth_fields.sql ⭐ EXECUTAR NO BANCO
   ├── add_oauth_simple.sql ⭐ ALTERNATIVA SIMPLES  
   └── check_oauth_migration.sql ⭐ VERIFICAÇÃO
```

### Script PHP de Migração
```
📄 migrate_oauth.php ⭐ EXECUTAR VIA PHP
```

## 🧪 Arquivos de Teste/Debug (OPCIONAIS)
```
📄 debug_oauth.php ⭐ PARA TESTE
📄 test_oauth_login.php ⭐ PARA TESTE  
📄 test_oauth_direct.php ⭐ PARA TESTE
📄 debug_routes.php ⭐ PARA TESTE
```

## 📚 Documentação (OPCIONAL)
```
📄 OAUTH_SETUP.md ⭐ GUIA COMPLETO
📄 DEPLOY_OAUTH.md ⭐ INSTRUÇÕES DEPLOY
📄 MIGRATE_OAUTH_INSTRUCTIONS.md ⭐ INSTRUÇÕES MIGRAÇÃO
```

---

## ⚡ ORDEM DE UPLOAD RECOMENDADA

### PASSO 1: Dependências (CRÍTICO)
1. **Fazer upload da pasta `vendor/` completa**
   - Comprima: `vendor.zip`
   - Upload via FTP/cPanel
   - Extraia no servidor

### PASSO 2: Configurações Core
2. **config/app.php** - Configurações OAuth
3. **composer.json** - Dependências

### PASSO 3: Código OAuth  
4. **app/controllers/AuthController.php** - Controller principal
5. **app/models/User.php** - Model com OAuth
6. **api/auth/oauth.php** - Handler OAuth
7. **public/router.php** - Rotas atualizadas

### PASSO 4: Interface
8. **templates/auth/login.php** - Login com botões OAuth

### PASSO 5: Database
9. **Executar migração** via migrate_oauth.php ou SQL

### PASSO 6: Teste
10. **Arquivos de teste** para verificar funcionamento

---

## 🚨 ATENÇÃO ESPECIAL

### Pasta vendor/ é OBRIGATÓRIA
- **Tamanho:** ~15-20MB
- **Contém:** Bibliotecas OAuth (Google, Facebook)  
- **Sem ela:** Botões OAuth não aparecerão

### Configurações em config/app.php
- **Credenciais de teste** já estão configuradas
- **Substitua** por credenciais reais depois dos testes

### Base de Dados
- **Execute** migrate_oauth.php no servidor
- **Ou** importe add_oauth_simple.sql via phpMyAdmin

---

## ✅ Como Verificar se Funcionou

1. **Acesse:** https://mercadoafiliado.com.br/debug_oauth.php
2. **Deve mostrar:** Todas as verificações em ✅
3. **Login:** https://mercadoafiliado.com.br/login
4. **Deve ter:** Botões Google e Facebook visíveis

---

## 🆘 Se Algo Não Funcionar

1. **vendor/ não existe:** Botões OAuth não aparecem
2. **Rota 404:** Problema no router.php  
3. **Credenciais inválidas:** Normal nos testes, configure as reais
4. **Erro de banco:** Execute a migração OAuth

**Prioridade máxima:** Upload da pasta `vendor/` primeiro!