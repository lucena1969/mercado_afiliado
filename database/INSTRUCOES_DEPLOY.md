# Instruções de Deploy - Sistema de Licitações

## 🔐 Credenciais do Banco de Dados

- **Nome do Banco:** `u590097272_sistema_licita`
- **Usuário MySQL:** `u590097272_onesioneto`
- **Senha:** `Numse!2020`
- **Host:** `localhost` (ou IP do servidor)

## 📋 Passos para Deploy

### 1. Criar o Usuário MySQL (se necessário)

```sql
CREATE USER IF NOT EXISTS 'u590097272_onesioneto'@'localhost' IDENTIFIED BY 'Numse!2020';
CREATE USER IF NOT EXISTS 'u590097272_onesioneto'@'%' IDENTIFIED BY 'Numse!2020';

GRANT ALL PRIVILEGES ON `u590097272_sistema_licita`.* TO 'u590097272_onesioneto'@'localhost';
GRANT ALL PRIVILEGES ON `u590097272_sistema_licita`.* TO 'u590097272_onesioneto'@'%';

FLUSH PRIVILEGES;
```

### 2. Importar o Banco de Dados

```bash
mysql -u root -p < sistema_licitacao_atualizado.SQL
```

Ou via phpMyAdmin:
- Acessar phpMyAdmin
- O script já cria o banco automaticamente
- Importar o arquivo `sistema_licitacao_atualizado.SQL`

### 3. Atualizar config.php

Editar o arquivo `config.php` na raiz do projeto:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u590097272_sistema_licita');
define('DB_USER', 'u590097272_onesioneto');
define('DB_PASS', 'Numse!2020');
```

### 4. Verificar Permissões

```bash
# Garantir que os diretórios tenham permissões adequadas
chmod -R 755 /caminho/do/projeto
chmod -R 777 /caminho/do/projeto/uploads
chmod -R 777 /caminho/do/projeto/backups
chmod -R 777 /caminho/do/projeto/logs
chmod -R 777 /caminho/do/projeto/cache
```

### 5. Testar o Sistema

- Acessar: `http://seu-dominio.com/sistema_licitacao`
- Login padrão: `admin@cglic.gov.br` / `admin123`

## ⚠️ Notas Importantes

1. **Backup:** Sempre faça backup antes de importar
2. **Charset:** O banco usa UTF-8 (utf8mb4)
3. **Collation:** utf8mb4_unicode_ci
4. **Tamanho:** O arquivo SQL tem ~5MB
5. **Tempo de importação:** Pode levar alguns minutos

## 🔧 Troubleshooting

### Erro de DEFINER
Se ocorrer erro de DEFINER, execute:
```sql
SET GLOBAL log_bin_trust_function_creators = 1;
```

### Erro de MAX_ALLOWED_PACKET
Se o arquivo for muito grande:
```sql
SET GLOBAL max_allowed_packet=1073741824;
```

### Erro de SUPER Privilege
Editar my.cnf/my.ini:
```ini
[mysqld]
log_bin_trust_function_creators=1
```

## 📞 Suporte

Em caso de problemas, verificar:
- Logs do Apache/Nginx
- Logs do MySQL
- Arquivo `/logs/sistema.log` no projeto
