# Configuração de Permalinks - URLs Otimizadas

## 🔗 Estrutura Recomendada

WordPress Admin → **Settings** → **Permalinks**

---

## ✅ Configuração Recomendada: Post name

### Selecionar:
```
● Post name
   https://blog.mercadoafiliado.com.br/sample-post/
```

### Por quê?
- ✅ URLs limpas e legíveis
- ✅ Melhor para SEO
- ✅ Palavras-chave na URL
- ✅ Fácil de compartilhar

---

## ❌ Evitar:

### Plain (ruim para SEO):
```
https://blog.mercadoafiliado.com.br/?p=123
```

### Day and name (URL longa):
```
https://blog.mercadoafiliado.com.br/2025/01/15/sample-post/
```

### Month and name (URL longa):
```
https://blog.mercadoafiliado.com.br/2025/01/sample-post/
```

---

## 🎯 Estrutura de Categorias

Opcional - adicionar categoria na URL:

### Custom Structure:
```
/%category%/%postname%/
```

**Resultado:**
```
https://blog.mercadoafiliado.com.br/tutoriais/como-rastrear-vendas/
```

**Vantagens:**
- Organização clara
- Usuário sabe onde está
- SEO (topicalidade)

**Desvantagens:**
- URLs mais longas
- Difícil mudar categoria depois

**Recomendação:** Use /%postname%/ (sem categoria) para flexibilidade

---

## 📄 Páginas Estáticas

Estrutura automática:
```
https://blog.mercadoafiliado.com.br/sobre/
https://blog.mercadoafiliado.com.br/contato/
```

---

## 📁 Categorias

Base padrão:
```
https://blog.mercadoafiliado.com.br/category/tutoriais/
```

Opcional - Remover "category" da URL:

### Plugin: Yoast SEO
```
SEO → Search Appearance → Taxonomies
- Category URL: Remove "category"
```

**Resultado:**
```
https://blog.mercadoafiliado.com.br/tutoriais/
```

---

## 🏷️ Tags

Base padrão:
```
https://blog.mercadoafiliado.com.br/tag/eduzz/
```

---

## 🔧 .htaccess

WordPress cria automaticamente ao salvar permalinks.

**Localização:** `/blog/.htaccess`

**Conteúdo padrão:**
```apache
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
```

---

## ✏️ Slug do Post

Ao criar artigo, WordPress gera slug automaticamente.

**Exemplo:**
- Título: "Como Rastrear Vendas do Eduzz no Facebook Ads"
- Slug gerado: `como-rastrear-vendas-do-eduzz-no-facebook-ads`

**Dica:** Encurte manualmente para melhorar SEO:
- Slug otimizado: `rastrear-vendas-eduzz-facebook`

**Como editar:**
```
Editor de Post → Document → Permalink → Edit
```

---

## 🔍 SEO na URL

### Boas práticas:

✅ **Fazer:**
- Máximo 3-5 palavras
- Incluir palavra-chave principal
- Usar hífens (-) entre palavras
- Letras minúsculas
- Sem acentos ou caracteres especiais

❌ **Evitar:**
- URLs longas (>60 caracteres)
- Stop words (de, para, com, etc)
- Números de ano (a menos que relevante)
- Underscores (_)

**Exemplo:**

| ❌ Ruim | ✅ Bom |
|---------|--------|
| `como-configurar-o-pixel-do-facebook-para-afiliados-em-2025` | `pixel-facebook-afiliados` |
| `tutorial_completo_eduzz` | `tutorial-eduzz` |
| `dicas-de-trafego-pago-para-iniciantes-que-estao-comecando` | `trafego-pago-iniciantes` |

---

## 🔄 Redirecionar URLs Antigas

Se mudar permalink de um post publicado, criar redirect 301.

### Plugin: Redirection

```
Tools → Redirection → Add New

Source URL: /old-url/
Target URL: /new-url/
```

---

## 📊 URLs de Páginas Especiais

### Página de Autor:
```
https://blog.mercadoafiliado.com.br/author/seu-nome/
```

### Busca:
```
https://blog.mercadoafiliado.com.br/?s=pixel
```

### Data (archive):
```
https://blog.mercadoafiliado.com.br/2025/01/
```

---

## ✅ Checklist de Configuração:

- [ ] Permalink definido como "Post name"
- [ ] .htaccess criado automaticamente
- [ ] Slugs editados manualmente (encurtados)
- [ ] URLs sem acentos e caracteres especiais
- [ ] Categorias com slug limpo
- [ ] Plugin Redirection instalado (para redirects futuros)
- [ ] Testado: Artigos abrem corretamente
- [ ] Testado: Categorias funcionando

---

## 🚨 IMPORTANTE:

**NÃO MUDE PERMALINKS APÓS PUBLICAR MUITOS ARTIGOS!**

Trocar a estrutura quebra todas as URLs indexadas no Google.

Se precisar mudar:
1. Backup completo do site
2. Lista de todas URLs antigas
3. Criar redirects 301 para cada URL
4. Reenviar sitemap ao Google

**Melhor:** Definir estrutura correta ANTES de publicar conteúdo.
