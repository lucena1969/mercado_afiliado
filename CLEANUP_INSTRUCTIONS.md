# 🧹 Instruções de Limpeza - Arquivos de Teste

## Arquivos que podem ser REMOVIDOS após testes:

### 📄 Páginas de Teste
- `test_pixel.php` - Página completa de testes do pixel
- `test_connection.php` - Teste de conexão com banco  
- `test_collector_direct.php` - Teste específico do coletor
- `debug_paths.php` - Debug de URLs e caminhos
- `check_database.php` - Verificação de tabelas

### 🗂️ Arquivos Temporários  
- `api/pixel/collect_simple.php` - Coletor simplificado (usar apenas o `collect.php`)
- `logs/` - Diretório de logs criado pelos testes (se existir)
- `CLEANUP_INSTRUCTIONS.md` - Este próprio arquivo

## Arquivos que devem ser MANTIDOS:

### 🏗️ Sistema Principal
- `api/pixel/collect.php` - Coletor principal
- `public/assets/js/pixel/pixel_br.js` - Script JavaScript
- `templates/pixel/` - Interface de configuração
- `app/models/PixelConfiguration.php` - Model do pixel
- `database/pixel_schema.sql` - Schema do banco
- `create_users_table.sql` - Tabelas base

### 📚 Documentação
- `DOCS.md` - Documentação geral do projeto  
- `PIXEL_BR_GUIA.md` - Guia completo do Pixel BR

## 🚀 Como Limpar

### Opção 1: Manual
Remova cada arquivo listado acima manualmente.

### Opção 2: Script de Limpeza (Windows)
```batch
@echo off
echo Limpando arquivos de teste do Pixel BR...

del test_pixel.php 2>nul
del test_connection.php 2>nul  
del test_collector_direct.php 2>nul
del debug_paths.php 2>nul
del check_database.php 2>nul
del api\pixel\collect_simple.php 2>nul
rmdir /s /q logs 2>nul
del CLEANUP_INSTRUCTIONS.md 2>nul

echo Limpeza concluida!
pause
```

### Opção 3: Script de Limpeza (Linux/Mac)
```bash
#!/bin/bash
echo "Limpando arquivos de teste do Pixel BR..."

rm -f test_pixel.php
rm -f test_connection.php
rm -f test_collector_direct.php
rm -f debug_paths.php
rm -f check_database.php
rm -f api/pixel/collect_simple.php
rm -rf logs/
rm -f CLEANUP_INSTRUCTIONS.md

echo "Limpeza concluída!"
```

## ⚠️ Importante

**NÃO REMOVER** antes de ter certeza de que:
1. ✅ O sistema principal está funcionando
2. ✅ As tabelas foram criadas no banco
3. ✅ O Pixel BR está acessível via `/pixel` 
4. ✅ A coleta de eventos está funcionando

**Recomendação:** Mantenha os arquivos de teste por alguns dias até ter certeza absoluta de que tudo está funcionando em produção.