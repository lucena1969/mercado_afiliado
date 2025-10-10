# 🚀 Guia de Deploy - cglic.net

## 📋 Informações do Ambiente

**Domínio:** https://cglic.net  
**Estrutura:** Arquivos na raiz do `public_html`  
**Banco de Dados:** `u590097272_sistema_licita`  
**Usuário MySQL:** `u590097272_onesioneto`  
**Senha MySQL:** `Numse!2020`

---

## 🔄 Processo de Deploy - PASSO A PASSO

### ✅ **PASSO 1: Configurar o Banco de Dados**

#### 1.1 - Acessar phpMyAdmin no painel da hospedagem

#### 1.2 - Verificar se usuário existe
- Menu: Contas de Usuário
- Procurar por: `u590097272_onesioneto`
- Se não existir, pedir ao suporte para criar

#### 1.3 - Importar o banco de dados
```
1. Clicar em "Importar"
2. Escolher arquivo: database/sistema_licitacao_atualizado.SQL
3. Charset: utf8mb4
4. Clicar em "Executar"
5. Aguardar (pode levar 2-3 minutos)
```

#### 1.4 - Verificar se o banco foi criado
- O banco `u590097272_sistema_licita` deve aparecer na lista
- Deve conter aproximadamente 30 tabelas

---

### ✅ **PASSO 2: Fazer Upload dos Arquivos**

#### 2.1 - Estrutura de pastas no servidor
```
public_html/
├── index.php                    # Login
├── config.php                   # Configurações (já está OK!)
├── .htaccess                    # Apache config (já está OK!)
├── functions.php
├── process.php
├── selecao_modulos.php
├── dashboard.php
├── licitacao_dashboard.php
├── gestao_riscos.php
├── gerenciar_usuarios.php
├── assets/                      # CSS, JS, imagens
├── api/                         # APIs REST
├── relatorios/                  # Scripts de relatórios
├── utils/                       # Utilitários
├── uploads/                     # Arquivos enviados
├── backups/                     # Backups do sistema
├── logs/                        # Logs
└── cache/                       # Cache
```

#### 2.2 - Fazer upload via FTP/SFTP
```bash
# Conectar ao servidor
# Host: cglic.net (ou IP fornecido)
# Usuário: (fornecido pela hospedagem)
# Senha: (fornecida pela hospedagem)
# Porta: 21 (FTP) ou 22 (SFTP)

# Fazer upload de TODOS os arquivos PHP e pastas
# EXCETO: node_modules/, database/*.backup, .git/
```

#### 2.3 - Arquivos que NÃO devem ser enviados
❌ `node_modules/`  
❌ `database/*.backup`  
❌ `.git/`  
❌ `.env` (opcional - usar .env.production como base)

---

### ✅ **PASSO 3: Configurar Permissões**

#### Via FileManager do painel:
```
public_html/              → 755
public_html/uploads/      → 777 (escrita)
public_html/backups/      → 777 (escrita)
public_html/logs/         → 777 (escrita)
public_html/cache/        → 777 (escrita)
```

#### Via SSH (se disponível):
```bash
cd /home/u590097272/public_html
chmod -R 755 .
chmod -R 777 uploads backups logs cache
```

---

### ✅ **PASSO 4: Testar o Sistema**

#### 4.1 - Acessar o sistema
```
URL: https://cglic.net
Login: admin@cglic.gov.br
Senha: admin123
```

#### 4.2 - Verificações após login
- [ ] Dashboard carrega corretamente
- [ ] Menu de módulos funciona
- [ ] Módulo Planejamento (PCA) carrega
- [ ] Módulo Licitações carrega
- [ ] Gestão de Riscos funciona
- [ ] Relatórios são gerados

#### 4.3 - Se houver erros
1. Verificar logs: `public_html/logs/sistema.log`
2. Verificar permissões das pastas
3. Verificar conexão com banco no phpMyAdmin
4. Consultar seção "Troubleshooting" abaixo

---

## 🔒 **Segurança Pós-Deploy**

### 1. Alterar senha do admin
```
Login → Gestão de Usuários → Editar admin@cglic.gov.br
Trocar senha de "admin123" para algo seguro
```

### 2. Verificar SSL/HTTPS
```
- O .htaccess já força HTTPS
- Certificado SSL deve estar ativo no painel
- Testar: http://cglic.net deve redirecionar para https://
```

### 3. Proteger arquivos sensíveis
```
O .htaccess já bloqueia:
✅ .env
✅ .sql
✅ .log
✅ .backup
✅ .md
```

---

## 🔧 **Troubleshooting**

### Erro: "Could not connect to database"
```
Solução:
1. Verificar em config.php se as credenciais estão corretas
2. Verificar no phpMyAdmin se o banco existe
3. Testar conexão manual no phpMyAdmin
```

### Erro: "Permission denied" ao fazer upload
```
Solução:
1. Verificar permissões: chmod 777 uploads backups logs cache
2. Verificar propriedade: chown nobody:nobody (via suporte)
```

### Erro 500 - Internal Server Error
```
Solução:
1. Verificar logs: logs/sistema.log ou error_log do Apache
2. Desativar DEBUG_MODE em config.php
3. Verificar .htaccess (testar renomear para .htaccess.bak)
```

### Página em branco
```
Solução:
1. Ativar display_errors temporariamente
2. Verificar logs do PHP
3. Verificar se todas as dependências estão no servidor
```

### CSS/JS não carregam
```
Solução:
1. Verificar caminhos em config.php (SITE_URL)
2. Limpar cache do navegador (Ctrl + Shift + R)
3. Verificar permissões da pasta assets/
```

---

## 📞 **Suporte**

### Logs para verificar em caso de problemas:
```
1. logs/sistema.log              (logs do sistema)
2. error_log                     (logs do Apache/PHP)
3. phpMyAdmin → SQL → SHOW ERRORS
```

### Comandos úteis SQL para debug:
```sql
-- Verificar usuário
SELECT User, Host FROM mysql.user WHERE User = 'u590097272_onesioneto';

-- Verificar banco
SHOW DATABASES LIKE 'u590097272_sistema_licita';

-- Verificar tabelas
USE u590097272_sistema_licita;
SHOW TABLES;

-- Testar usuário admin
SELECT id, nome, email, nivel_acesso FROM usuarios WHERE email = 'admin@cglic.gov.br';
```

---

## ✅ **Checklist Final**

Antes de considerar o deploy concluído:

- [ ] Banco de dados importado com sucesso
- [ ] Todos os arquivos PHP enviados para public_html
- [ ] Permissões das pastas configuradas (777 para uploads, logs, cache, backups)
- [ ] Sistema acessível via https://cglic.net
- [ ] Login funcionando (admin@cglic.gov.br / admin123)
- [ ] Todos os módulos carregando corretamente
- [ ] Senha do admin alterada
- [ ] SSL/HTTPS ativo e funcionando
- [ ] Relatórios gerando corretamente
- [ ] Importação de CSV testada (se aplicável)

---

## 🎯 **Resumo Rápido**

```bash
# 1. Importar SQL no phpMyAdmin
database/sistema_licitacao_atualizado.SQL

# 2. Upload para public_html via FTP
Todos os arquivos exceto: node_modules, .git, *.backup

# 3. Permissões (via SSH ou FileManager)
chmod 777 uploads backups logs cache

# 4. Testar
https://cglic.net
Login: admin@cglic.gov.br / admin123

# 5. Trocar senha do admin!
```

---

**📌 Última atualização:** 06/10/2025  
**🌐 Domínio:** https://cglic.net  
**📁 Root:** public_html/
