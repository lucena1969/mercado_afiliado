# 📝 Integração do Blog ao Mercado Afiliado

## Documento Técnico - Opções de Implementação

---

## 1. Visão Geral

Integrar um blog ao domínio `mercadoafiliado.com.br` com:
- ✅ URL: `mercadoafiliado.com.br/blog`
- ✅ Design consistente com plataforma principal
- ✅ CTAs automáticos para trials
- ✅ SEO otimizado
- ✅ Fácil gerenciamento de conteúdo

---

## 2. Opções de Implementação

### **Opção 1: WordPress em Subdiretório (RECOMENDADA)**

#### Arquitetura
```
mercadoafiliado.com.br/
├── public/              (Aplicação PHP atual)
├── blog/               (WordPress instalado aqui)
│   ├── wp-admin/
│   ├── wp-content/
│   └── index.php
└── config/
```

#### Vantagens
- ✅ **Mais fácil de gerenciar** - Interface WordPress conhecida
- ✅ **Plugins prontos** - SEO (Yoast), analytics, cache
- ✅ **Temas personalizáveis** - Adaptar ao design da plataforma
- ✅ **Mínimo desenvolvimento** - Instalar e configurar
- ✅ **Editor WYSIWYG** - Escrever sem código
- ✅ **Banco de dados separado** - Não mistura com dados da plataforma

#### Desvantagens
- ⚠️ Peso adicional (WordPress é pesado)
- ⚠️ Requer manutenção (atualizações de segurança)
- ⚠️ Performance inferior ao PHP puro

#### Instalação (30 minutos)

**Passo 1: Download WordPress**
```bash
cd /workspaces/mercado_afiliado
wget https://wordpress.org/latest.zip
unzip latest.zip
mv wordpress blog
```

**Passo 2: Configurar Banco de Dados**
```sql
CREATE DATABASE mercado_afiliado_blog;
CREATE USER 'blog_user'@'localhost' IDENTIFIED BY 'senha_segura';
GRANT ALL PRIVILEGES ON mercado_afiliado_blog.* TO 'blog_user'@'localhost';
FLUSH PRIVILEGES;
```

**Passo 3: Configurar wp-config.php**
```php
define('DB_NAME', 'mercado_afiliado_blog');
define('DB_USER', 'blog_user');
define('DB_PASSWORD', 'senha_segura');
define('DB_HOST', 'localhost');

// URLs
define('WP_HOME', 'https://mercadoafiliado.com.br/blog');
define('WP_SITEURL', 'https://mercadoafiliado.com.br/blog');
```

**Passo 4: Ajustar .htaccess do blog**
```apache
# blog/.htaccess
RewriteEngine On
RewriteBase /blog/
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /blog/index.php [L]
```

**Passo 5: Instalar tema customizado**
- Copiar header/footer da plataforma principal
- Adaptar `style.css` com variáveis CSS da plataforma
- Adicionar CTAs fixos (sidebar, rodapé)

#### Plugins Recomendados
1. **Yoast SEO** - Otimização automática
2. **WP Rocket** - Cache e performance
3. **Advanced Custom Fields** - CTAs personalizados
4. **Contact Form 7** - Formulários de contato
5. **Really Simple SSL** - Forçar HTTPS

#### Custo
- **Desenvolvimento:** 4-8 horas
- **Hospedagem adicional:** R$ 0 (mesmo servidor)
- **Plugins:** R$ 0-200/mês (se usar premium)

---

### **Opção 2: Sistema de Blog PHP Customizado**

#### Arquitetura
```
mercadoafiliado.com.br/
├── public/
│   └── router.php (adicionar rotas /blog*)
├── templates/
│   └── blog/
│       ├── index.php (lista de posts)
│       ├── post.php (artigo individual)
│       └── admin.php (painel de criação)
├── app/
│   └── models/
│       └── BlogPost.php
└── database/
    └── blog_schema.sql
```

#### Vantagens
- ✅ **100% integrado** - Usa mesma autenticação e design
- ✅ **Performance máxima** - PHP puro, sem overhead
- ✅ **Controle total** - Personalização ilimitada
- ✅ **SEO nativo** - URLs limpas via router existente

#### Desvantagens
- ⚠️ **Muito desenvolvimento** (20-40 horas)
- ⚠️ **Sem editor visual** - Escrever em Markdown ou HTML
- ⚠️ **Manutenção contínua** - Adicionar features manualmente

#### Schema de Banco de Dados
```sql
CREATE TABLE blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content LONGTEXT,
    excerpt TEXT,
    featured_image VARCHAR(500),
    author_id INT,
    status ENUM('draft', 'published', 'scheduled') DEFAULT 'draft',
    published_at DATETIME,
    meta_title VARCHAR(255),
    meta_description VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id),
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_published (published_at)
);

CREATE TABLE blog_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE blog_post_categories (
    post_id INT,
    category_id INT,
    PRIMARY KEY (post_id, category_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE CASCADE
);
```

#### Rotas no Router
```php
// Adicionar ao public/router.php
$routes = [
    // ... rotas existentes ...
    'blog' => $root_path . '/templates/blog/index.php',
    'blog/admin' => $root_path . '/templates/blog/admin.php',
];

// Rota dinâmica para posts individuais
if (preg_match('/^blog\/(.+)$/', $route, $matches)) {
    $_GET['slug'] = $matches[1];
    include $root_path . '/templates/blog/post.php';
    exit;
}
```

#### Interface de Criação (Simples)
```php
// templates/blog/admin.php
<form method="post" action="/api/blog/save.php">
    <input type="text" name="title" placeholder="Título do artigo" required>
    <input type="text" name="slug" placeholder="url-do-artigo" required>
    <textarea name="content" rows="20" placeholder="Conteúdo (HTML ou Markdown)"></textarea>
    <input type="text" name="meta_description" placeholder="Descrição SEO">
    <select name="status">
        <option value="draft">Rascunho</option>
        <option value="published">Publicar</option>
    </select>
    <button type="submit">Salvar Artigo</button>
</form>
```

#### Custo
- **Desenvolvimento:** 20-40 horas
- **Manutenção:** Alta (adicionar features conforme necessário)

---

### **Opção 3: Ghost CMS (Alternativa Moderna ao WordPress)**

#### Arquitetura
```
mercadoafiliado.com.br/blog → Proxy reverso para Ghost (porta 2368)
```

Ghost roda em Node.js em paralelo ao PHP.

#### Vantagens
- ✅ **Moderno e rápido** - Built for blogging
- ✅ **Editor excelente** - Markdown + WYSIWYG
- ✅ **SEO nativo** - Otimizado por padrão
- ✅ **Design limpo** - Temas minimalistas
- ✅ **API headless** - Pode consumir via React/Vue

#### Desvantagens
- ⚠️ **Requer Node.js** - Stack diferente do PHP
- ⚠️ **Configuração complexa** - Nginx/Apache proxy
- ⚠️ **Custo de hospedagem** - VPS necessário (R$ 50-100/mês)

#### Instalação (Servidor com Node.js)
```bash
# Instalar Ghost CLI
npm install ghost-cli@latest -g

# Criar diretório
cd /var/www/
mkdir ghost-blog
cd ghost-blog

# Instalar Ghost
ghost install --url https://mercadoafiliado.com.br/blog --db mysql
```

#### Configuração Nginx (Proxy Reverso)
```nginx
# Redirecionar /blog para Ghost (porta 2368)
location /blog {
    proxy_pass http://localhost:2368;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

#### Custo
- **Desenvolvimento:** 8-12 horas
- **Hospedagem VPS:** R$ 50-100/mês
- **Temas premium:** R$ 0-300 (one-time)

---

## 3. Comparação das Opções

| Critério | WordPress | PHP Custom | Ghost CMS |
|----------|-----------|------------|-----------|
| **Facilidade de uso** | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐ |
| **Tempo de setup** | 4-8h | 20-40h | 8-12h |
| **Performance** | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ |
| **SEO** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Custo inicial** | Baixo | Médio | Médio |
| **Custo mensal** | R$ 0-200 | R$ 0 | R$ 50-100 |
| **Manutenção** | Média | Alta | Baixa |
| **Flexibilidade** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |

---

## 4. Recomendação Final

### **Para Lançamento Rápido (Próximos 30 dias):**
➡️ **Opção 1: WordPress**

**Justificativa:**
- Foco deve estar em **escrever conteúdo**, não desenvolver sistema
- 50 artigos existentes podem ser migrados rapidamente
- Plugins de SEO economizam tempo (Yoast faz 80% do trabalho)
- Editor visual acelera produção de conteúdo
- Você pode começar a escrever HOJE

**Roadmap:**
1. Dia 1: Instalar WordPress em `/blog`
2. Dia 2-3: Customizar tema (copiar header/footer da plataforma)
3. Dia 4-5: Configurar plugins e SEO
4. Dia 6-7: Migrar 5 artigos melhores do banco antigo
5. Dia 8+: Publicar 2 artigos novos/semana

---

### **Para Longo Prazo (3-6 meses):**
➡️ **Migrar para PHP Custom** (quando tiver tráfego consistente)

Quando blog estiver gerando 10k+ visitas/mês, vale migrar para sistema próprio para:
- Performance (reduzir tempo de carregamento)
- Integração nativa (mesma sessão, recomendações personalizadas)
- Controle total (A/B tests, analytics customizados)

---

## 5. Implementação Recomendada (WordPress)

### 5.1 Estrutura de Arquivos

```
mercado_afiliado/
├── blog/                          # WordPress instalado aqui
│   ├── wp-admin/
│   ├── wp-content/
│   │   ├── themes/
│   │   │   └── mercado-afiliado/  # Tema customizado
│   │   │       ├── header.php     # Header da plataforma
│   │   │       ├── footer.php     # Footer da plataforma
│   │   │       ├── sidebar.php    # CTAs de trial
│   │   │       ├── single.php     # Template de artigo
│   │   │       └── style.css      # CSS adaptado
│   │   └── plugins/
│   └── wp-config.php
├── public/                        # Aplicação principal
└── config/
```

### 5.2 Tema Customizado - Estrutura Mínima

#### header.php (Adaptar da plataforma)
```php
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <!-- CSS da plataforma principal -->
    <link rel="stylesheet" href="https://mercadoafiliado.com.br/assets/css/style.css">
</head>
<body <?php body_class(); ?>>

<!-- Header idêntico à plataforma -->
<header class="header">
    <div class="container">
        <nav class="nav">
            <a href="https://mercadoafiliado.com.br" class="nav-brand">
                <div style="width: 32px; height: 32px; background: var(--color-primary); border-radius: 6px;"></div>
                Mercado Afiliado
            </a>
            <ul class="nav-links">
                <li><a href="https://mercadoafiliado.com.br/blog">Blog</a></li>
                <li><a href="https://mercadoafiliado.com.br/login">Login</a></li>
                <li><a href="https://mercadoafiliado.com.br/register" class="btn btn-primary">Teste Grátis</a></li>
            </ul>
        </nav>
    </div>
</header>
```

#### single.php (Template de artigo)
```php
<?php get_header(); ?>

<div class="container" style="display: grid; grid-template-columns: 1fr 300px; gap: 2rem; margin-top: 2rem;">

    <!-- Conteúdo do artigo -->
    <article class="blog-post">
        <?php while (have_posts()) : the_post(); ?>

            <h1><?php the_title(); ?></h1>

            <div class="post-meta">
                <span><?php echo get_the_date(); ?></span>
                <span>•</span>
                <span><?php echo get_the_author(); ?></span>
            </div>

            <?php if (has_post_thumbnail()) : ?>
                <img src="<?php the_post_thumbnail_url('large'); ?>" alt="<?php the_title(); ?>">
            <?php endif; ?>

            <div class="post-content">
                <?php the_content(); ?>
            </div>

            <!-- CTA no final do artigo -->
            <div class="cta-box" style="background: #fff4d6; padding: 2rem; border-radius: 12px; margin-top: 3rem;">
                <h3>⚡ Automatize tudo isso com Mercado Afiliado</h3>
                <p>Rastreie vendas, configure pixels e integre todas suas plataformas em um só lugar.</p>
                <a href="https://mercadoafiliado.com.br/register" class="btn btn-primary">
                    Testar Grátis por 30 Dias →
                </a>
            </div>

        <?php endwhile; ?>
    </article>

    <!-- Sidebar com CTAs -->
    <?php get_sidebar(); ?>

</div>

<?php get_footer(); ?>
```

#### sidebar.php (CTAs fixos)
```php
<aside class="blog-sidebar">

    <!-- CTA Principal -->
    <div class="sticky-cta" style="position: sticky; top: 2rem;">
        <div style="background: linear-gradient(135deg, #e7b73b, #b38609); color: white; padding: 2rem; border-radius: 12px;">
            <h4 style="margin: 0 0 1rem; color: white;">🚀 Teste Grátis</h4>
            <p style="font-size: 0.9rem; margin: 0 0 1rem;">Configure suas integrações em 5 minutos. Sem cartão de crédito.</p>
            <a href="https://mercadoafiliado.com.br/register" style="display: block; background: white; color: #b38609; padding: 0.8rem; border-radius: 8px; text-align: center; font-weight: 600; text-decoration: none;">
                Começar Agora →
            </a>
        </div>

        <!-- Artigos relacionados -->
        <div style="margin-top: 2rem;">
            <h4>📚 Leia também</h4>
            <?php
            $related = new WP_Query([
                'post__not_in' => [get_the_ID()],
                'posts_per_page' => 3,
                'orderby' => 'rand'
            ]);
            while ($related->have_posts()) : $related->the_post();
            ?>
                <a href="<?php the_permalink(); ?>" style="display: block; padding: 0.8rem 0; border-bottom: 1px solid #eee;">
                    <?php the_title(); ?>
                </a>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>

</aside>
```

### 5.3 SEO Automático (Plugin Yoast)

Configuração recomendada:
```
Título SEO: {{title}} | Mercado Afiliado
Meta Description: Automática (primeiros 155 caracteres)
Breadcrumbs: Ativado
Sitemap XML: Ativado
Schema.org: Article + Organization
```

### 5.4 Performance (WP Rocket)

```
Cache de página: Ativado
Minificação CSS/JS: Ativado
LazyLoad imagens: Ativado
CDN: Cloudflare (grátis)
```

---

## 6. Integração Avançada (Futuro)

### 6.1 Single Sign-On (SSO)
Permitir login com mesma conta da plataforma no WordPress.

**Plugin:** Wordpress JWT Authentication
```php
// Validar JWT do Mercado Afiliado
add_filter('jwt_auth_token_before_dispatch', function($data, $user) {
    $data['platform_token'] = create_platform_token($user->ID);
    return $data;
}, 10, 2);
```

### 6.2 Recomendações Personalizadas
Mostrar artigos baseados nas integrações do usuário.

```php
// Exemplo: Se usuário usa Eduzz, mostrar artigos sobre Eduzz
if (is_user_logged_in()) {
    $user_integrations = get_user_integrations($user_id);
    $related_posts = get_posts_by_integration($user_integrations);
}
```

### 6.3 Analytics Unificado
Rastrear leitura de artigos no dashboard da plataforma.

```javascript
// Enviar evento de leitura
fetch('https://mercadoafiliado.com.br/api/analytics/blog-read', {
    method: 'POST',
    body: JSON.stringify({
        post_id: <?php echo get_the_ID(); ?>,
        user_id: getCurrentUserId()
    })
});
```

---

## 7. Checklist de Implementação

### Semana 1: Setup
- [ ] Instalar WordPress em `/blog`
- [ ] Configurar banco de dados separado
- [ ] Instalar tema básico (Twenty Twenty-Four)
- [ ] Configurar permalink: `/%postname%/`
- [ ] Instalar SSL/HTTPS

### Semana 2: Customização
- [ ] Criar tema filho "mercado-afiliado"
- [ ] Adaptar header/footer da plataforma
- [ ] Adicionar CTAs na sidebar
- [ ] Configurar cores/fontes (CSS)
- [ ] Testar responsividade mobile

### Semana 3: SEO e Performance
- [ ] Instalar Yoast SEO
- [ ] Configurar sitemap XML
- [ ] Instalar WP Rocket (cache)
- [ ] Configurar Cloudflare
- [ ] Testar velocidade (GTmetrix)

### Semana 4: Conteúdo
- [ ] Migrar 5 melhores artigos antigos
- [ ] Escrever 3 artigos novos (lista prioritária)
- [ ] Criar categorias (Tutoriais, Ferramentas, Dicas)
- [ ] Adicionar imagens featured
- [ ] Revisar SEO de cada post

---

## 8. Custo Total e Timeline

### Investimento
| Item | Custo | Observação |
|------|-------|------------|
| WordPress | R$ 0 | Open source |
| Tema customizado | R$ 0 | Desenvolver manualmente |
| Plugins essenciais | R$ 0 | Versões gratuitas |
| Plugins premium (opcional) | R$ 200/mês | WP Rocket, Rank Math Pro |
| Hospedagem | R$ 0 | Mesmo servidor atual |
| **Total inicial** | **R$ 0-200** | |

### Timeline
- **Setup completo:** 7-14 dias
- **Primeiro artigo publicado:** Dia 1 após setup
- **Ritmo de publicação:** 2-3 artigos/semana
- **SEO começar a funcionar:** 30-60 dias

---

## 9. Próximos Passos Imediatos

**Quer que eu ajude com:**
1. ✅ Criar script de instalação automatizada do WordPress?
2. ✅ Desenvolver tema customizado (header/footer adaptados)?
3. ✅ Escrever outline dos 10 primeiros artigos?
4. ✅ Configurar arquivo .htaccess para rotas do blog?
5. ✅ Criar templates de CTAs para inserir nos artigos?

**Qual você quer fazer primeiro?**
