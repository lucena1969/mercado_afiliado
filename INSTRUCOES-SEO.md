# 📋 Instruções de Uso - Sitemap.xml e Robots.txt

## 📁 Arquivos Criados

```
mercado_afiliado/
├── sitemap.xml           → Mapa do site para buscadores
├── robots.txt            → Regras para crawlers e bots de IA
└── generate-sitemap.php  → Gerador automático de sitemap
```

---

## 🤖 1. ROBOTS.TXT

### O que faz?
Informa aos bots (Google, ChatGPT, Claude, etc.) quais páginas podem ou não acessar.

### Localização:
```
https://mercadoafiliado.com.br/robots.txt
```

### ✅ O que está PERMITIDO:
- Homepage e páginas públicas
- Blog e documentação
- Assets (CSS, JS, imagens)
- Páginas institucionais

### ❌ O que está BLOQUEADO:
- Dashboard (área privada)
- APIs e webhooks
- Arquivos de configuração
- Diretórios internos (/app, /config, /database)
- Arquivos sensíveis (.env, .sql, .log)

### 🤖 Bots de IA PERMITIDOS:
- **GPTBot** (ChatGPT)
- **Claude-Web** (Claude AI)
- **Google-Extended** (Bard/Gemini)
- **CCBot** (Common Crawl - usado por várias IAs)
- **PerplexityBot** (Perplexity AI)
- **anthropic-ai** (Claude)
- **cohere-ai** (Cohere)

### 🚫 Bots de Scraping BLOQUEADOS:
- AhrefsBot
- SemrushBot
- DotBot
- MJ12bot
- BLEXBot

### Como usar:

**Já está pronto!** O arquivo está na raiz do projeto e já funciona.

Para verificar se está acessível:
1. Abra: `https://mercadoafiliado.com.br/robots.txt`
2. Você deve ver o conteúdo do arquivo

---

## 🗺️ 2. SITEMAP.XML

### O que faz?
Lista todas as páginas públicas do site para facilitar a indexação pelos buscadores.

### Localização:
```
https://mercadoafiliado.com.br/sitemap.xml
```

### Páginas incluídas:

**Prioridade ALTA (0.9-1.0):**
- Homepage (/)
- /precos
- /link-maestro
- /pixel-br
- /integrasync

**Prioridade MÉDIA (0.6-0.8):**
- /sobre
- /recursos
- /faq
- /blog
- /docs
- /cases

**Prioridade BAIXA (0.3-0.5):**
- /contato
- /politica-privacidade
- /termos-de-uso

### Como usar:

**Opção 1: Usar o sitemap estático (já criado)**
```
O arquivo sitemap.xml já está pronto na raiz
```

**Opção 2: Gerar dinamicamente (recomendado)**

Execute o gerador:

**Via navegador:**
```
http://localhost/mercado_afiliado/generate-sitemap.php
```

**Via linha de comando:**
```bash
cd C:\xampp\htdocs\mercado_afiliado
php generate-sitemap.php
```

Você verá:
```
✅ Sitemap gerado com sucesso!
📍 Local: C:\xampp\htdocs\mercado_afiliado\sitemap.xml
📊 Total de URLs: 25
🕐 Atualizado em: 09/01/2025 14:30:00
🌐 URL pública: https://mercadoafiliado.com.br/sitemap.xml
```

---

## 🚀 3. SUBMETER AOS BUSCADORES

### Google Search Console

**Passo 1: Adicionar propriedade**
1. Acesse: https://search.google.com/search-console
2. Clique em "Adicionar propriedade"
3. Escolha "Prefixo do URL"
4. Digite: `https://mercadoafiliado.com.br`

**Passo 2: Verificar propriedade**

Escolha um método:

**Método A - Tag HTML (mais fácil):**
```html
<!-- Adicione no <head> da homepage -->
<meta name="google-site-verification" content="CODIGO_QUE_O_GOOGLE_FORNECE" />
```

**Método B - Arquivo HTML:**
1. Google fornece um arquivo tipo: `google1234567890.html`
2. Faça upload na raiz do site
3. Clique em "Verificar"

**Passo 3: Enviar sitemap**
1. No painel do Search Console
2. Menu lateral: "Sitemaps"
3. Digite: `sitemap.xml`
4. Clique em "Enviar"

**Passo 4: Solicitar indexação**
1. Menu "Inspeção de URL"
2. Cole: `https://mercadoafiliado.com.br`
3. Clique em "Solicitar indexação"

Repita para páginas principais:
- /sobre
- /recursos
- /precos
- /faq

---

### Bing Webmaster Tools

**Passo 1: Adicionar site**
1. Acesse: https://www.bing.com/webmasters
2. "Adicionar um site"
3. Digite: `https://mercadoafiliado.com.br`

**Passo 2: Verificar (3 opções)**

**Opção 1 - Tag XML (recomendado):**
```xml
<meta name="msvalidate.01" content="CODIGO_DO_BING" />
```

**Opção 2 - Arquivo XML:**
Upload do arquivo `BingSiteAuth.xml` na raiz

**Opção 3 - Importar do Google:**
Se já verificou no Google, pode importar automaticamente

**Passo 3: Enviar sitemap**
1. Menu "Sitemaps"
2. Digite: `https://mercadoafiliado.com.br/sitemap.xml`
3. "Enviar"

---

### Perplexity AI

**Método 1 - Email direto:**
```
Para: sources@perplexity.ai
Assunto: Adicionar mercadoafiliado.com.br como fonte

Olá,

Gostaria de submeter o site Mercado Afiliado como fonte de informação:

URL: https://mercadoafiliado.com.br
Categoria: SaaS / Marketing Technology / Analytics
Descrição: Plataforma de rastreamento e análise para afiliados digitais brasileiros.

Recursos principais:
- Link Maestro (rastreamento de links)
- Pixel BR (pixel de conversão LGPD-compliant)
- IntegraSync (integrações com Hotmart, Eduzz, Facebook, Google)
- Painel Unificado de métricas

Conteúdo educacional em /blog e /docs

O site possui robots.txt permitindo PerplexityBot.

Obrigado!
```

**Método 2 - Esperar crawl natural:**
O Perplexity rastreia sites com bom SEO automaticamente (2-4 semanas)

---

### OpenAI (ChatGPT)

**Importante:** Não há submissão direta oficial.

**O que fazer:**
1. ✅ Garantir que `GPTBot` está permitido no robots.txt (já está!)
2. ✅ Ter conteúdo de qualidade e bem estruturado
3. ✅ Aguardar rastreamento natural (pode levar 1-2 meses)

**No futuro (quando disponível):**
- Criar um GPT customizado do Mercado Afiliado
- Usar OpenAI API para integração direta

---

## 📊 4. VERIFICAR SE ESTÁ FUNCIONANDO

### Teste 1: Arquivos acessíveis
```bash
# Via navegador ou curl
curl https://mercadoafiliado.com.br/robots.txt
curl https://mercadoafiliado.com.br/sitemap.xml
```

Deve retornar o conteúdo dos arquivos (não erro 404)

---

### Teste 2: Validar Sitemap
1. Acesse: https://www.xml-sitemaps.com/validate-xml-sitemap.html
2. Cole: `https://mercadoafiliado.com.br/sitemap.xml`
3. Clique em "Validate"

Deve retornar: ✅ "Your sitemap is valid"

---

### Teste 3: Verificar Robots.txt
1. Acesse: https://www.google.com/webmasters/tools/robots-testing-tool
2. Cole o conteúdo do robots.txt
3. Teste URLs:
   - ✅ Permitido: `/blog`
   - ❌ Bloqueado: `/dashboard`

---

### Teste 4: Indexação no Google
```
site:mercadoafiliado.com.br
```

Depois de alguns dias/semanas, deve mostrar páginas indexadas.

---

## 🔄 5. MANUTENÇÃO E ATUALIZAÇÃO

### Quando atualizar o sitemap?

**Atualizar SEMPRE que:**
- Criar nova página pública
- Criar novo post de blog
- Criar nova documentação
- Alterar estrutura de URLs

### Como atualizar:

**Método 1 - Manual:**
1. Edite `sitemap.xml` diretamente
2. Adicione a nova URL
3. Atualize a data `<lastmod>`

**Método 2 - Gerador (recomendado):**
1. Edite `generate-sitemap.php`
2. Adicione a URL no array `$urls`
3. Execute: `php generate-sitemap.php`

**Método 3 - Automático (cron):**

No cPanel ou servidor Linux:
```bash
# Executar todo dia à meia-noite
0 0 * * * /usr/bin/php /home/usuario/public_html/generate-sitemap.php
```

No Windows (Agendador de Tarefas):
```
Programa: C:\xampp\php\php.exe
Argumentos: C:\xampp\htdocs\mercado_afiliado\generate-sitemap.php
Frequência: Diária às 00:00
```

---

## 📈 6. MONITORAMENTO DE RESULTADOS

### Google Search Console - Métricas importantes:

**Cobertura:**
- URLs válidas vs. excluídas
- Meta: 90%+ das páginas indexadas

**Desempenho:**
- Impressões (quantas vezes apareceu)
- Cliques (quantos acessaram)
- CTR (taxa de clique)
- Posição média

**Sitemaps:**
- URLs enviadas
- URLs indexadas
- Erros

### Cronograma esperado:

```
Semana 1: Google/Bing descobrem o site
Semana 2-3: Primeiras páginas indexadas
Mês 1: 50-70% das páginas indexadas
Mês 2-3: IAs começam a citar em respostas
Mês 4+: Autoridade estabelecida
```

---

## ⚠️ 7. PROBLEMAS COMUNS

### Problema 1: "Sitemap não encontrado"

**Causa:** Arquivo não está na raiz ou URL errada

**Solução:**
```bash
# Verificar se existe
ls -la C:\xampp\htdocs\mercado_afiliado\sitemap.xml

# Testar acesso
curl https://mercadoafiliado.com.br/sitemap.xml
```

---

### Problema 2: "Erro de permissão ao gerar sitemap"

**Causa:** PHP não tem permissão de escrita

**Solução Windows (XAMPP):**
1. Botão direito na pasta `mercado_afiliado`
2. Propriedades → Segurança
3. Editar → Adicionar "Todos"
4. Marcar "Controle Total"

**Solução Linux:**
```bash
chmod 755 /caminho/mercado_afiliado
chmod 644 /caminho/mercado_afiliado/sitemap.xml
```

---

### Problema 3: "Google não está indexando"

**Possíveis causas:**
1. robots.txt bloqueando
2. Meta robots com noindex
3. Site muito novo (aguardar)
4. Conteúdo duplicado

**Diagnóstico:**
1. Search Console → Inspeção de URL
2. Ver motivo da não indexação
3. Corrigir e solicitar novamente

---

## 🎯 8. PRÓXIMOS PASSOS

Depois de configurar sitemap e robots.txt:

**1. Criar conteúdo otimizado:**
- [ ] Página /sobre com descrição completa
- [ ] Página /faq com Schema markup
- [ ] 5-10 posts de blog educacionais
- [ ] Documentação da API

**2. Otimizar páginas existentes:**
- [ ] Adicionar Schema.org markup
- [ ] Melhorar meta descriptions
- [ ] Adicionar alt text em imagens
- [ ] Otimizar velocidade (Core Web Vitals)

**3. Construir autoridade:**
- [ ] Guest posts em blogs de marketing
- [ ] Cadastro em diretórios (Product Hunt, Capterra)
- [ ] Reviews de usuários
- [ ] Backlinks de qualidade

---

## 📞 SUPORTE

Se tiver dúvidas ou problemas:

1. Verifique os testes acima
2. Consulte Google Search Console
3. Revise este documento
4. Google: "como resolver [problema]"

---

## ✅ CHECKLIST FINAL

Antes de considerar concluído:

```
□ robots.txt acessível em /robots.txt
□ sitemap.xml acessível em /sitemap.xml
□ Sitemap válido (teste no validador)
□ Submetido ao Google Search Console
□ Submetido ao Bing Webmaster Tools
□ Páginas principais solicitadas para indexação
□ robots.txt permite bots de IA (GPTBot, Claude, etc.)
□ Verificação de propriedade concluída (Google/Bing)
□ Monitoramento configurado (alerts no Search Console)
```

---

## 📚 RECURSOS ÚTEIS

**Ferramentas de teste:**
- Validador de Sitemap: https://www.xml-sitemaps.com/validate-xml-sitemap.html
- Teste Robots.txt: https://www.google.com/webmasters/tools/robots-testing-tool
- PageSpeed Insights: https://pagespeed.web.dev/
- Schema Validator: https://validator.schema.org/

**Documentação oficial:**
- Google SEO: https://developers.google.com/search/docs
- Sitemaps Protocol: https://www.sitemaps.org/
- Robots.txt Spec: https://www.robotstxt.org/

**Guias de IA:**
- OpenAI GPTBot: https://platform.openai.com/docs/gptbot
- Google Bard: https://support.google.com/webmasters/answer/13438966
- Perplexity: https://docs.perplexity.ai/

---

Última atualização: 09/01/2025
Versão: 1.0
