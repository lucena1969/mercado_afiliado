# 📂 Scripts SQL - Sistema de Licitações

## 📋 Arquivos Disponíveis

### ✅ **sistema_licitacao_atualizado.SQL** (RECOMENDADO)
**Versão:** SEM DEFINER - Para hospedagem compartilhada  
**Tamanho:** ~5.1 MB  
**Status:** ✅ Pronto para uso

**Características:**
- ✅ Sem cláusulas DEFINER (evita erro #1227)
- ✅ Compatível com hospedagem compartilhada
- ✅ Cria banco automaticamente
- ✅ UTF-8 (utf8mb4) configurado
- ✅ ~30 tabelas + procedures + functions + views

**Use este arquivo se:**
- ❌ Você NÃO tem privilégio SUPER no MySQL
- ✅ Está em hospedagem compartilhada
- ✅ Recebeu erro "#1227 - Acesso negado"

---

### 📦 **sistema_licitacao_atualizado_COM_DEFINER.SQL**
**Versão:** COM DEFINER - Para servidor dedicado  
**Tamanho:** ~5.1 MB  
**Status:** ⚠️ Backup apenas

**Características:**
- ⚠️ Contém cláusulas DEFINER
- ⚠️ Requer privilégio SUPER no MySQL
- ✅ Ideal para servidor dedicado/VPS
- ✅ Mesma estrutura que versão sem DEFINER

**Use este arquivo se:**
- ✅ Você TEM privilégio SUPER no MySQL
- ✅ Está em servidor dedicado/VPS
- ✅ Quer manter usuários específicos nas procedures/views

---

## 🔐 Credenciais Configuradas

Ambos os arquivos usam as mesmas credenciais:

```
Banco:    u590097272_sistema_licita
Usuário:  u590097272_onesioneto
Senha:    Numse!2020
Host:     localhost
Charset:  utf8mb4
```

---

## 🚀 Como Importar

### Via phpMyAdmin (Recomendado)

1. Acesse phpMyAdmin no painel da hospedagem
2. Clique em **"Importar"**
3. Selecione: `sistema_licitacao_atualizado.SQL`
4. Charset: **utf8mb4**
5. Clique em **"Executar"**
6. Aguarde 2-3 minutos

### Via Linha de Comando (SSH)

```bash
mysql -u root -p < sistema_licitacao_atualizado.SQL
```

---

## ⚠️ Erros Comuns

### Erro #1227 - Acesso negado (privilégio SET USER)

**Causa:** Arquivo com DEFINER em hospedagem sem privilégio SUPER

**Solução:** Use `sistema_licitacao_atualizado.SQL` (sem DEFINER)

### Erro: max_allowed_packet

**Causa:** Arquivo muito grande (5MB)

**Solução:**
```sql
SET GLOBAL max_allowed_packet=1073741824;
```

Ou no phpMyAdmin: Configurações → SQL → max_allowed_packet

### Erro: Unknown collation utf8mb4_unicode_ci

**Causa:** MySQL muito antigo (< 5.5)

**Solução:** Atualizar MySQL ou substituir por:
```sql
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci
```

---

## 🔍 Verificar Importação

Após importar, verifique se tudo está OK:

```sql
-- Verificar banco
SHOW DATABASES LIKE 'u590097272_sistema_licita';

-- Usar banco
USE u590097272_sistema_licita;

-- Verificar tabelas (deve ter ~30)
SHOW TABLES;

-- Verificar usuário admin
SELECT id, nome, email, nivel_acesso 
FROM usuarios 
WHERE email = 'admin@cglic.gov.br';
```

**Resultado esperado:**
- ✅ Banco criado: `u590097272_sistema_licita`
- ✅ ~30 tabelas
- ✅ 1 usuário admin cadastrado

---

## 📊 Estrutura das Tabelas

**Principais tabelas:**
- `usuarios` - Usuários do sistema (4 níveis)
- `pca_dados` - Dados do PCA (2022-2026)
- `licitacoes` - Processos licitatórios
- `pca_riscos` - Gestão de riscos
- `qualificacoes` - Qualificações técnicas
- `backups_sistema` - Controle de backups
- `logs_sistema` - Logs de auditoria

**Procedures:**
- `sp_vincular_qualificacao_licitacao`

**Functions:**
- `fn_qualificacao_disponivel`

**Views:**
- `teste_view_qualificacoes`
- `view_qualificacoes_licitacoes_completa`
- `view_qualificacoes_para_licitacao`

---

## 📞 Suporte

Em caso de problemas durante a importação:

1. **Verificar logs do MySQL:**
   - phpMyAdmin → Status → Logs
   - Linha de comando: `tail -f /var/log/mysql/error.log`

2. **Testar conexão:**
   ```sql
   SELECT VERSION();
   SELECT @@character_set_database;
   ```

3. **Verificar privilégios:**
   ```sql
   SHOW GRANTS FOR CURRENT_USER;
   ```

---

## 📚 Documentação Relacionada

- `../GUIA_DEPLOY_CGLIC.md` - Guia completo de deploy
- `../RESUMO_DEPLOY.md` - Checklist rápido
- `INSTRUCOES_DEPLOY.md` - Instruções detalhadas do banco

---

**📌 Última atualização:** 06/10/2025  
**🎯 Arquivo recomendado:** `sistema_licitacao_atualizado.SQL` (SEM DEFINER)
