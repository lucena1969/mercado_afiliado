# 📝 Blog Mercado Afiliado - Arquivos de Configuração

## 📁 Estrutura de Pastas

```
blog/
├── theme/              # Tema customizado GeneratePress
├── plugins/            # Configurações de plugins
├── configs/            # Arquivos de configuração WordPress
├── templates/          # Templates de artigos e páginas
└── assets/             # Imagens, ícones, fontes
```

## 🚀 Instalação

### Passo 1: WordPress já instalado em blog.mercadoafiliado.com.br ✅

### Passo 2: Instalar Tema GeneratePress
1. WordPress Admin → Aparência → Temas → Adicionar Novo
2. Buscar: "GeneratePress"
3. Instalar → Ativar

### Passo 3: Instalar Plugins Essenciais
```
- Yoast SEO (SEO automático)
- GenerateBlocks (blocos extras)
- Contact Form 7 (formulários)
- LiteSpeed Cache ou WP Rocket (performance)
```

### Passo 4: Importar Configurações

#### 4.1 Customizer Settings
- Importar: `configs/customizer-settings.json`
- Via: Aparência → Personalizar → Import/Export

#### 4.2 CSS Customizado
- Copiar conteúdo de: `theme/custom-style.css`
- Colar em: Aparência → Personalizar → Additional CSS

#### 4.3 Widgets
- Importar manualmente seguindo: `configs/widgets-config.md`

### Passo 5: Criar Menu
- Ver: `configs/menu-structure.md`

---

## 📄 Arquivos Incluídos

### Tema (`/theme`)
- `custom-style.css` - CSS customizado com cores da plataforma
- `functions-snippet.php` - Snippets PHP para adicionar ao functions.php
- `header-template.php` - Template do header
- `footer-template.php` - Template do footer
- `sidebar-ctas.html` - CTAs para sidebar

### Plugins (`/plugins`)
- `yoast-seo-config.json` - Configurações Yoast SEO
- `recommended-plugins.md` - Lista de plugins recomendados

### Configs (`/configs`)
- `customizer-settings.json` - Configurações do Personalizador
- `menu-structure.md` - Estrutura do menu
- `widgets-config.md` - Configuração dos widgets
- `permalink-settings.md` - Estrutura de URLs

### Templates (`/templates`)
- `article-template.md` - Template padrão de artigo
- `cta-boxes.html` - CTAs prontos
- `author-bio.html` - Bio do autor

---

## 🎨 Cores da Plataforma

```css
--primary: #e7b73b (Amarelo/Mostarda)
--primary-dark: #b38609
--blue: #2563eb
--blue-dark: #1d4ed8
--text: #1f2937
--gray: #6b7280
--border: #e5e7eb
```

---

## 📝 Próximos Passos

1. ✅ WordPress instalado
2. ⬜ Instalar tema GeneratePress
3. ⬜ Instalar plugins essenciais
4. ⬜ Importar CSS customizado
5. ⬜ Configurar menu
6. ⬜ Adicionar widgets na sidebar
7. ⬜ Publicar primeiro artigo

---

## 🆘 Suporte

Dúvidas? Consulte:
- `INTEGRACAO_BLOG.md` (raiz do projeto)
- `PLANO_LANCAMENTO.md` (estratégia de conteúdo)
