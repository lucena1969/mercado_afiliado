# Plugins Recomendados - Blog Mercado Afiliado

## 🔌 Plugins Essenciais (Instalar Agora)

### 1. Yoast SEO ⭐⭐⭐⭐⭐
**Função:** Otimização SEO automática

**Como instalar:**
```
Plugins → Adicionar Novo → Buscar "Yoast SEO"
Instalar → Ativar
```

**Configuração:**
```
SEO → Geral → Configuração Inicial
- Tipo de site: Blog
- Organização: Mercado Afiliado
- Logo: Upload logo da plataforma
- Social: Preencher perfis
```

**Configurações importantes:**
- Title separator: `|`
- Homepage title: `Blog Mercado Afiliado | Tutoriais para Afiliados`
- Meta description: `Dicas, tutoriais e ferramentas para afiliados. Aprenda a rastrear vendas, configurar pixels e automatizar integrações.`

---

### 2. GenerateBlocks ⭐⭐⭐⭐⭐
**Função:** Blocos extras para editor Gutenberg

**Como instalar:**
```
Plugins → Adicionar Novo → Buscar "GenerateBlocks"
Instalar → Ativar
```

**Uso:**
- Criar CTAs visuais
- Botões customizados
- Grids e layouts

---

### 3. LiteSpeed Cache ⭐⭐⭐⭐⭐
**Função:** Cache e otimização de performance

**Como instalar:**
```
Plugins → Adicionar Novo → Buscar "LiteSpeed Cache"
Instalar → Ativar
```

**Configuração:**
```
LiteSpeed Cache → Geral
- Enable Cache: ON
- Guest Mode: ON
- Guest Optimization: ON

Otimização:
- CSS Minify: ON
- JS Minify: ON
- CSS/JS Combine: ON
- Lazy Load Images: ON
```

**Alternativa:** WP Rocket (pago, R$ 200/ano, mais poderoso)

---

### 4. Contact Form 7 ⭐⭐⭐⭐
**Função:** Formulários de contato

**Como instalar:**
```
Plugins → Adicionar Novo → Buscar "Contact Form 7"
Instalar → Ativar
```

**Criar formulário newsletter:**
```
Contact → Add New
Nome: Newsletter

Formulário:
[email* email placeholder "Seu melhor e-mail"]
[submit "Quero Receber"]

Email:
To: seu@email.com
Subject: Nova inscrição newsletter
```

---

## 🔌 Plugins Opcionais (Úteis)

### 5. UpdraftPlus (Backup) ⭐⭐⭐⭐
**Função:** Backup automático

**Configuração:**
```
Settings → UpdraftPlus Backups
- Schedule: Weekly
- Destination: Google Drive ou Dropbox
- Files to backup: All
```

---

### 6. Wordfence Security ⭐⭐⭐⭐
**Função:** Firewall e segurança

**Configuração:**
```
Wordfence → All Options
- Firewall: Extended Protection
- Scan Schedule: Daily
```

---

### 7. Redirection ⭐⭐⭐
**Função:** Gerenciar redirects 301

**Uso:**
- Redirecionar URLs antigas
- Corrigir links quebrados
- SEO (evitar 404s)

---

### 8. MonsterInsights (Google Analytics) ⭐⭐⭐⭐
**Função:** Integração fácil com Google Analytics

**Configuração:**
```
Insights → Settings
- Conectar Google Analytics
- Enable Enhanced Tracking
```

**Alternativa grátis:** GA Google Analytics

---

### 9. Rank Math SEO ⭐⭐⭐⭐⭐
**Função:** Alternativa ao Yoast (mais completo)

**Escolha:** Yoast OU Rank Math (não use ambos!)

**Vantagens Rank Math:**
- Schema.org automático
- Mais features grátis
- Interface mais moderna

---

### 10. ShortPixel (Otimização de Imagens) ⭐⭐⭐⭐
**Função:** Comprimir imagens automaticamente

**Configuração:**
```
Settings → ShortPixel
- Compression: Lossy (melhor)
- Resize large images: 1200px width
- Auto-optimize new uploads: ON
```

**Quota grátis:** 100 imagens/mês
**Plano pago:** R$ 20/mês (ilimitado)

---

## 🚫 Plugins para EVITAR

### ❌ Jetpack
**Por quê:** Muito pesado, conflita com cache

### ❌ All in One SEO Pack
**Por quê:** Yoast ou Rank Math são melhores

### ❌ W3 Total Cache
**Por quê:** Complexo demais, use LiteSpeed Cache

### ❌ Elementor (para blog)
**Por quê:** Desnecessário, use Gutenberg + GenerateBlocks

---

## 📦 Setup Completo Recomendado

### Configuração Mínima (5 plugins):
1. ✅ Yoast SEO
2. ✅ GenerateBlocks
3. ✅ LiteSpeed Cache
4. ✅ Contact Form 7
5. ✅ UpdraftPlus

### Configuração Ideal (8 plugins):
1. ✅ Yoast SEO (ou Rank Math)
2. ✅ GenerateBlocks
3. ✅ LiteSpeed Cache (ou WP Rocket)
4. ✅ Contact Form 7
5. ✅ UpdraftPlus
6. ✅ Wordfence Security
7. ✅ MonsterInsights
8. ✅ ShortPixel

---

## 🎯 Instalação em Sequência

```bash
# Ordem de instalação recomendada:

1. LiteSpeed Cache (configurar antes de tudo)
2. Yoast SEO (configuração básica)
3. GenerateBlocks
4. Contact Form 7
5. UpdraftPlus (configurar backup)
6. Wordfence (scan inicial)
7. ShortPixel (otimizar imagens existentes)
8. MonsterInsights (conectar Analytics)
```

---

## ⚙️ Configurações Globais

### Após instalar todos os plugins:

```
Settings → Reading:
- Blog pages show at most: 10 posts
- For each post show: Summary

Settings → Discussion:
- Allow comments: Yes (moderar)
- Comment author must fill: Name and Email
- Comment moderation: Hold for approval

Settings → Permalinks:
- Post name: /sample-post/
```

---

## 📊 Monitoramento

### Plugins instalados corretamente se:
- [ ] Tempo de carregamento < 2 segundos (GTmetrix)
- [ ] PageSpeed Score > 80 (Google PageSpeed)
- [ ] Yoast mostra "SEO: Good" nos artigos
- [ ] Backup agendado funcionando
- [ ] Cache gerando arquivos

---

## 🆘 Troubleshooting

### Site lento após instalar plugin?
1. Desabilitar um por um para identificar culpado
2. Verificar conflito de cache
3. Limpar cache (LiteSpeed → Purge All)

### Plugin causando erro?
1. Desativar via FTP: `/wp-content/plugins/nome-plugin`
2. Renomear pasta do plugin
3. Site volta ao normal

---

## Checklist Final:

- [ ] Yoast SEO instalado e configurado
- [ ] LiteSpeed Cache ativo e gerando cache
- [ ] Backup automático agendado
- [ ] Formulário de newsletter criado
- [ ] Google Analytics conectado
- [ ] Imagens otimizadas
- [ ] Firewall ativo
- [ ] Todos os plugins atualizados
