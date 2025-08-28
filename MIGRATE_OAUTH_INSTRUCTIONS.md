# 🔧 Instruções para Migração OAuth

## Passo 1: Iniciar MySQL/MariaDB

1. **Abra o XAMPP Control Panel**
   - Navegue até: `C:\xampp\xampp-control.exe`
   - Clique em **"Start"** para MySQL

2. **Ou via linha de comando:**
   ```bash
   C:\xampp\mysql\bin\mysqld.exe --console
   ```

## Passo 2: Executar Migração

Escolha uma das opções abaixo:

### Opção A: Via Arquivo PHP (Recomendado)
```bash
cd C:\xampp\htdocs\mercado_afiliado
php migrate_oauth.php
```

### Opção B: Via MySQL Command Line
```bash
cd C:\xampp\htdocs\mercado_afiliado
C:\xampp\mysql\bin\mysql.exe -u u590097272_lucena1969 -p u590097272_mercado_afilia < database/add_oauth_simple.sql
```

### Opção C: Via phpMyAdmin
1. Acesse: http://localhost/phpmyadmin
2. Selecione banco: `u590097272_mercado_afilia`
3. Vá na aba **SQL**
4. Copie e cole o conteúdo do arquivo `database/add_oauth_simple.sql`
5. Clique em **"Executar"**

## Passo 3: Verificar Migração

Execute este SQL para verificar se os campos foram adicionados:

```sql
USE u590097272_mercado_afilia;
DESCRIBE users;
```

Você deve ver os novos campos:
- `uuid`
- `phone`
- `avatar`
- `google_id`
- `facebook_id`
- `email_verified_at`
- `last_login_at`

## Passo 4: Configurar Credenciais OAuth

Edite o arquivo `config/app.php` e adicione suas credenciais reais:

```php
// Google OAuth
define('GOOGLE_CLIENT_ID', 'SEU_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'SEU_GOOGLE_CLIENT_SECRET');

// Facebook OAuth  
define('FACEBOOK_CLIENT_ID', 'SEU_FACEBOOK_APP_ID');
define('FACEBOOK_CLIENT_SECRET', 'SEU_FACEBOOK_APP_SECRET');
```

## 🚨 Possíveis Erros

### "Table 'users' doesn't exist"
- Certifique-se de que está no banco correto: `u590097272_mercado_afilia`
- Verifique se a tabela users foi criada

### "Column already exists"
- Normal! Significa que alguns campos já existem
- A migração continuará normalmente

### "Access denied"
- Verifique se está usando o usuário correto: `u590097272_lucena1969`
- Confirme a senha do banco de dados

## ✅ Teste Final

Após executar a migração, teste o OAuth:

1. Acesse: `http://localhost/mercado_afiliado/login`
2. Você deve ver os botões "Google" e "Facebook"
3. ⚠️ **Importante**: Configure as credenciais OAuth antes de testar!

## 📞 Ajuda

Se tiver problemas:
1. Verifique se MySQL está rodando
2. Confirme o nome correto do banco de dados
3. Teste a conexão com o banco
4. Verifique os logs de erro do PHP