# 🎯 Link Maestro - Guia Completo

## O Que é o Link Maestro?

O **Link Maestro** é o sistema de links inteligentes do Mercado Afiliado que permite:
- ✅ Encurtar links longos
- ✅ Adicionar UTMs automaticamente
- ✅ Rastrear cliques em tempo real
- ✅ Analisar performance por canal
- ✅ Otimizar campanhas com dados

---

## 📚 Índice

1. [Por Que Usar Links Curtos?](#por-que-usar-links-curtos)
2. [Como Funcionam os UTMs?](#como-funcionam-os-utms)
3. [Criando Seu Primeiro Link](#criando-seu-primeiro-link)
4. [Templates UTM Explicados](#templates-utm-explicados)
5. [Estratégias de Uso](#estrategias-de-uso)
6. [Analytics e Relatórios](#analytics-e-relatorios)
7. [Casos de Uso Práticos](#casos-de-uso-praticos)
8. [Perguntas Frequentes](#perguntas-frequentes)

---

## 🤔 Por Que Usar Links Curtos?

### **Problema Comum:**

Você está divulgando um produto de afiliado e seu link fica assim:

```
https://go.hotmart.com/V12345678?dp=1&src=email-list&utm_source=email&utm_medium=newsletter&utm_campaign=lancamento-janeiro-2025&utm_content=email-dia-1&utm_term=afiliados&a=seu-codigo-afiliado
```

❌ **Feio e longo**
❌ **Difícil de lembrar**
❌ **Assusta usuários**
❌ **Não cabe em bio do Instagram**
❌ **Impossível falar em áudio**

### **Solução com Link Maestro:**

```
https://seu-dominio.com/curso-mkt
```

✅ **Limpo e profissional**
✅ **Fácil de lembrar**
✅ **Passa confiança**
✅ **Cabe em qualquer lugar**
✅ **Pode falar no podcast**

### **Mas Tem Mais:**

Além de ser mais bonito, o Link Maestro:

1. **Adiciona UTMs automaticamente**
   - Você não precisa ficar colando manualmente
   - Garante padronização em todas as campanhas
   - Evita erros de digitação

2. **Rastreia TUDO**
   - Quantos cliques teve
   - De onde vieram (Google, Facebook, etc)
   - Que dispositivo usaram
   - Que país/cidade
   - Que hora do dia

3. **Cruza com Vendas**
   - Quando uma venda acontece via webhook
   - O sistema sabe de qual link veio
   - Você vê o ROI real de cada canal

---

## 🏷️ Como Funcionam os UTMs?

### **O Que São UTMs?**

UTM = **U**rchin **T**racking **M**odule

São parâmetros que você adiciona no final de uma URL para rastrear de onde vem o tráfego.

### **Os 5 Parâmetros:**

#### **1. utm_source (OBRIGATÓRIO)**
**O QUE:** De onde vem o tráfego?
**EXEMPLOS:** google, facebook, instagram, email, youtube

```
utm_source=google     → Veio do Google
utm_source=facebook   → Veio do Facebook
utm_source=instagram  → Veio do Instagram
```

#### **2. utm_medium (OBRIGATÓRIO)**
**O QUE:** Que tipo de tráfego?
**EXEMPLOS:** cpc, organic, social, email, referral

```
utm_medium=cpc       → Tráfego pago (custo por clique)
utm_medium=organic   → Tráfego orgânico (grátis)
utm_medium=social    → Rede social
```

#### **3. utm_campaign (OBRIGATÓRIO)**
**O QUE:** Qual campanha específica?
**EXEMPLOS:** lancamento-janeiro, black-friday, webinar-gratis

```
utm_campaign=lancamento-janeiro
utm_campaign=black-friday-2025
utm_campaign=webinar-afiliados
```

#### **4. utm_content (OPCIONAL)**
**O QUE:** Qual anúncio/conteúdo específico?
**EXEMPLOS:** anuncio-01, video-vsl, email-dia-1

```
utm_content=anuncio-headline-beneficios
utm_content=video-vsl-30min
utm_content=email-dia-3-urgencia
```

#### **5. utm_term (OPCIONAL)**
**O QUE:** Qual palavra-chave? (Google Ads)
**EXEMPLOS:** marketing-digital, curso-afiliados

```
utm_term=marketing-digital
utm_term=curso-online
```

### **Exemplo Completo:**

```
URL Original:
https://produto.com/checkout

Com UTMs:
https://produto.com/checkout?
  utm_source=google&
  utm_medium=cpc&
  utm_campaign=lancamento-janeiro&
  utm_content=anuncio-beneficios&
  utm_term=marketing-digital
```

### **Como o Google Analytics Vê:**

```
📊 GOOGLE ANALYTICS

Sessões: 1.245
Conversões: 45 (3.6%)

Por Origem:
├─ google / cpc: 678 sessões (32 conversões)
├─ facebook / cpc: 412 sessões (10 conversões)
└─ instagram / organic: 155 sessões (3 conversões)

Por Campanha:
├─ lancamento-janeiro: 890 sessões (38 conversões)
└─ webinar-gratis: 355 sessões (7 conversões)

Por Conteúdo:
├─ anuncio-beneficios: 450 sessões (22 conversões) ← VENCEDOR!
├─ anuncio-urgencia: 300 sessões (12 conversões)
└─ anuncio-prova-social: 240 sessões (6 conversões)
```

**Decisão:** Pausar "prova-social", investir mais em "beneficios"!

---

## 🚀 Criando Seu Primeiro Link

### **Passo 1: Acesse o Link Maestro**

```
Dashboard → Sidebar → Link Maestro
ou
https://seu-dominio.com/link-maestro
```

### **Passo 2: Clique em "Novo Link"**

Você verá um formulário com os campos:

### **Passo 3: Preencha os Campos**

#### **🔗 URL de Destino** (obrigatório)
Cole o link COMPLETO do produto que você quer divulgar.

```
✅ CERTO:
https://go.hotmart.com/V12345678
https://pay.kiwify.com.br/abc123
https://checkout.eduzz.com/987654

❌ ERRADO:
hotmart.com/produto
www.produto.com (sem https://)
```

#### **🏷️ Alias Personalizado** (opcional)
Como você quer que o link fique?

```
✅ BOM:
curso-marketing
lancamento-jan
ebook-vendas

❌ EVITAR:
abc123 (sem sentido)
meu-link-1 (genérico)
aaaa (preguiçoso)
```

**Dica:** Use algo descritivo e fácil de lembrar!

#### **📝 Nome do Link** (obrigatório)
Descrição interna para você se organizar.

```
EXEMPLOS:
"Curso Marketing Digital - Google Ads"
"E-book Vendas - Instagram Bio"
"Webinar Gratuito - Facebook"
```

#### **📋 Template UTM** (opcional, mas RECOMENDADO)
Escolha um template pré-configurado ou crie personalizado.

**Templates Disponíveis:**
- **Google Ads** - Para campanhas no Google
- **Facebook Ads** - Para anúncios no Facebook/Instagram
- **Email Marketing** - Para newsletters
- **Instagram Bio** - Para link na bio
- **YouTube** - Para vídeos
- **WhatsApp** - Para mensagens
- **Personalizado** - Criar do zero

### **Passo 4: Salvar**

Clique em **"Criar Link"** e pronto!

O sistema gera:
```
Link Curto: https://seu-dominio.com/curso-marketing
```

### **Passo 5: Copiar e Usar**

Clique em **"Copiar Link"** e cole onde quiser:
- Anúncios
- Redes sociais
- Emails
- WhatsApp
- Qualquer lugar!

---

## 📋 Templates UTM Explicados

### **Template: Google Ads**

```yaml
utm_source: google
utm_medium: cpc
utm_campaign: [você escolhe]
utm_content: [você escolhe]
utm_term: [palavra-chave do anúncio]
```

**Quando usar:**
- Campanhas no Google Ads
- Google Shopping
- Display Network

**Exemplo de uso:**
```
Campanha: lancamento-curso
Conteúdo: anuncio-beneficios
Termo: marketing digital

Resultado:
?utm_source=google
&utm_medium=cpc
&utm_campaign=lancamento-curso
&utm_content=anuncio-beneficios
&utm_term=marketing-digital
```

---

### **Template: Facebook Ads**

```yaml
utm_source: facebook
utm_medium: cpc
utm_campaign: [você escolhe]
utm_content: [você escolhe]
```

**Quando usar:**
- Facebook Ads
- Instagram Ads
- Messenger Ads

**Exemplo:**
```
Campanha: black-friday
Conteúdo: video-vsl

Resultado:
?utm_source=facebook
&utm_medium=cpc
&utm_campaign=black-friday
&utm_content=video-vsl
```

---

### **Template: Instagram Bio**

```yaml
utm_source: instagram
utm_medium: bio
utm_campaign: perfil-principal
```

**Quando usar:**
- Link único da bio do Instagram
- Stories com link
- Reels com CTA

**Exemplo:**
```
Resultado:
?utm_source=instagram
&utm_medium=bio
&utm_campaign=perfil-principal
```

---

### **Template: Email Marketing**

```yaml
utm_source: email
utm_medium: newsletter
utm_campaign: [você escolhe]
utm_content: [qual email]
```

**Quando usar:**
- Newsletters
- Sequências de email
- Broadcasts

**Exemplo:**
```
Campanha: lancamento-janeiro
Conteúdo: email-dia-3

Resultado:
?utm_source=email
&utm_medium=newsletter
&utm_campaign=lancamento-janeiro
&utm_content=email-dia-3
```

---

### **Template Personalizado**

Crie suas próprias regras!

**Exemplo: Afiliados**
```yaml
utm_source: afiliado
utm_medium: referral
utm_campaign: [nome-do-afiliado]
utm_content: [onde-divulgou]
```

**Uso:**
```
João Silva divulgou no YouTube dele:

utm_source=afiliado
utm_medium=referral
utm_campaign=joao-silva
utm_content=video-youtube

Link: seu-dominio.com/aff-joao-yt
```

---

## 🎯 Estratégias de Uso

### **Estratégia 1: Um Link por Canal**

**Objetivo:** Descobrir qual canal vende mais

```
Produto: Curso de Marketing (R$ 197)

Google Ads:
seu-dominio.com/curso-google

Facebook Ads:
seu-dominio.com/curso-facebook

Instagram Bio:
seu-dominio.com/curso-insta

Email:
seu-dominio.com/curso-email

YouTube:
seu-dominio.com/curso-youtube
```

**Análise após 30 dias:**
```
📊 RESULTADOS

Google Ads:
├─ 450 cliques
├─ 23 vendas (5.1%)
├─ R$ 4.531 em vendas
├─ Custo: R$ 1.200
└─ ROI: 277% ✅

Facebook Ads:
├─ 820 cliques
├─ 12 vendas (1.5%)
├─ R$ 2.364 em vendas
├─ Custo: R$ 2.100
└─ ROI: 12% ⚠️

Instagram Bio:
├─ 1.240 cliques
├─ 31 vendas (2.5%)
├─ R$ 6.107 em vendas
├─ Custo: R$ 0
└─ ROI: ∞ 🔥
```

**Decisão:**
- ✅ Investir mais no Google (melhor ROI pago)
- 🔥 Focar em crescer Instagram (orgânico top)
- ⚠️ Otimizar ou pausar Facebook

---

### **Estratégia 2: Teste A/B de Anúncios**

**Objetivo:** Descobrir qual mensagem converte mais

```
Produto: E-book de Vendas (R$ 47)

Anúncio A (Benefícios):
Headline: "Aprenda a Vender 10x Mais"
Link: seu-dominio.com/ebook-beneficios

Anúncio B (Urgência):
Headline: "Só Hoje: 70% OFF"
Link: seu-dominio.com/ebook-urgencia

Anúncio C (Prova Social):
Headline: "+5.000 Alunos Satisfeitos"
Link: seu-dominio.com/ebook-prova
```

**Análise após 7 dias:**
```
Investimento total: R$ 300 (R$ 100 cada)

Anúncio A:
├─ 120 cliques
├─ 8 vendas (6.7%)
├─ R$ 376 em vendas
└─ ROI: 276%

Anúncio B:
├─ 230 cliques
├─ 18 vendas (7.8%) ← VENCEDOR!
├─ R$ 846 em vendas
└─ ROI: 746%

Anúncio C:
├─ 95 cliques
├─ 4 vendas (4.2%)
├─ R$ 188 em vendas
└─ ROI: 88%
```

**Decisão:**
- 🏆 Escalar anúncio B com todo o budget
- ❌ Pausar anúncio C
- 🔄 Testar variação do anúncio B

---

### **Estratégia 3: Links por Afiliado**

**Objetivo:** Rastrear performance de cada afiliado

```
Afiliado João Silva:
seu-dominio.com/aff-joao

Afiliado Maria Costa:
seu-dominio.com/aff-maria

Afiliado Pedro Santos:
seu-dominio.com/aff-pedro
```

**Análise mensal:**
```
João Silva:
├─ 45 vendas
├─ R$ 8.865 em vendas
├─ R$ 3.546 em comissões
└─ TOP AFILIADO 🥇

Maria Costa:
├─ 32 vendas
├─ R$ 6.304 em vendas
├─ R$ 2.521 em comissões
└─ 2º LUGAR 🥈

Pedro Santos:
├─ 890 cliques
├─ 0 vendas
├─ R$ 0 em vendas
└─ 🚨 FRAUDE DETECTADA!
```

**Ações:**
- 🎁 Bonificar João e Maria
- ❌ Bloquear Pedro (cliques falsos)

---

## 📊 Analytics e Relatórios

### **O Que Você Vê no Dashboard:**

```
📈 VISÃO GERAL DO LINK

Link: seu-dominio.com/curso-mkt
Destino: go.hotmart.com/V12345678
Criado: 01/01/2025
Status: Ativo ✅

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📊 ESTATÍSTICAS (Últimos 30 dias)

Total de Cliques: 2.456
Taxa de Clique Único: 78.5% (1.928)
Média Diária: 81 cliques

Conversões: 89 vendas
Taxa de Conversão: 3.6%
Receita Gerada: R$ 17.533,00
Comissões: R$ 7.013,00

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🌍 ORIGEM GEOGRÁFICA

Brasil: 2.105 (85.7%)
├─ São Paulo: 842
├─ Rio de Janeiro: 421
├─ Minas Gerais: 318
└─ Outros: 524

Portugal: 245 (10.0%)
Estados Unidos: 68 (2.8%)
Outros: 38 (1.5%)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📱 DISPOSITIVOS

Mobile: 1.598 (65.0%)
Desktop: 735 (30.0%)
Tablet: 123 (5.0%)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🌐 NAVEGADORES

Chrome: 1.473 (60.0%)
Safari: 491 (20.0%)
Firefox: 246 (10.0%)
Edge: 147 (6.0%)
Outros: 99 (4.0%)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🔗 REFERRERS (De onde vieram)

Google Ads: 892 (36.3%) - 45 vendas
Facebook: 654 (26.6%) - 25 vendas
Instagram: 523 (21.3%) - 15 vendas
Direto: 245 (10.0%) - 3 vendas
Email: 142 (5.8%) - 1 venda

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

⏰ HORÁRIOS DE PICO

Melhor hora: 20h-21h (245 cliques)
Melhor dia: Terça-feira (418 cliques)

Por período:
├─ 00h-06h: 89 cliques (3.6%)
├─ 06h-12h: 589 cliques (24.0%)
├─ 12h-18h: 892 cliques (36.3%) ← PICO
├─ 18h-00h: 886 cliques (36.1%)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📈 TENDÊNCIA (7 dias)

SEG TER QUA QUI SEX SAB DOM
 89  124  98  112  145  67  82
 ↑   ↑↑  ↑   ↑↑  ↑↑↑  ↓   ↑
```

---

## 💡 Casos de Uso Práticos

### **Caso 1: Lançamento de Produto**

```
CENÁRIO:
Você vai lançar um curso novo e quer saber
qual canal traz mais resultados.

ESTRATÉGIA:
Criar 5 links diferentes, um para cada canal.

LINKS:
├─ lancamento-google (Google Ads)
├─ lancamento-facebook (Facebook Ads)
├─ lancamento-insta (Instagram)
├─ lancamento-email (Email para lista)
└─ lancamento-youtube (Vídeo de lançamento)

BUDGET:
R$ 500 por canal durante a semana de lançamento

RESULTADO APÓS 7 DIAS:
┌──────────────┬────────┬────────┬──────────┬──────┐
│ Canal        │ Gasto  │ Clicks │ Vendas   │ ROI  │
├──────────────┼────────┼────────┼──────────┼──────┤
│ Google       │ R$ 500 │ 245    │ 12 (4.9%)│ 372% │
│ Facebook     │ R$ 500 │ 412    │ 8 (1.9%) │ 215% │
│ Instagram    │ R$ 500 │ 328    │ 6 (1.8%) │ 136% │
│ Email        │ R$ 0   │ 892    │ 45 (5.0%)│ ∞    │
│ YouTube      │ R$ 0   │ 156    │ 3 (1.9%) │ ∞    │
└──────────────┴────────┴────────┴──────────┴──────┘

APRENDIZADOS:
✅ Email foi o CAMPEÃO (lista engajada!)
✅ Google teve melhor ROI pago
⚠️ Instagram precisa otimização
💡 YouTube tem potencial (aumentar produção)

PRÓXIMOS PASSOS:
1. Investir mais no Google
2. Nutrir mais a lista de email
3. Pausar Instagram temporariamente
4. Criar mais vídeos no YouTube
```

---

### **Caso 2: Recuperação de Carrinho**

```
CENÁRIO:
Muitas pessoas clicam mas não compram.
Criar estratégia de recuperação.

ESTRATÉGIA:
Diferentes links para diferentes táticas.

LINKS:
├─ recuperacao-email1 (Email após 1h)
├─ recuperacao-email2 (Email após 24h)
├─ recuperacao-whatsapp (WhatsApp pessoal)
└─ recuperacao-desconto (Cupom 10% OFF)

FLUXO:
1. Pessoa clica em link original
2. Não compra
3. Sistema detecta carrinho abandonado
4. Envia email 1h depois com link "email1"
5. Se não comprar, email 24h com "email2"
6. Se não comprar, WhatsApp com "whatsapp"
7. Último recurso: cupom com "desconto"

RESULTADOS:
┌─────────────────┬────────┬───────┬──────────┐
│ Tática          │ Envios │ Opens │ Vendas   │
├─────────────────┼────────┼───────┼──────────┤
│ Email 1h        │ 450    │ 245   │ 12 (4.9%)│
│ Email 24h       │ 405    │ 189   │ 23 (12%) │ ← MELHOR
│ WhatsApp        │ 382    │ 342   │ 45 (13%) │ ← TOP!
│ Cupom 10%       │ 337    │ 289   │ 67 (23%) │ ← ROI?
└─────────────────┴────────┴───────┴──────────┘

APRENDIZADOS:
🔥 WhatsApp tem ALTA conversão
💰 Cupom converte mas reduz margem
⏰ Email 24h > Email 1h (timing certo)

OTIMIZAÇÕES:
1. Começar direto pelo WhatsApp
2. Usar cupom só em último caso
3. Testar email após 12h (meio termo)
```

---

### **Caso 3: Afiliados Diferentes**

```
CENÁRIO:
Você tem 10 afiliados divulgando seu produto.
Precisa saber quem vende de verdade.

ESTRATÉGIA:
Um link único por afiliado.

LINKS:
├─ aff-joao (João Silva)
├─ aff-maria (Maria Costa)
├─ aff-pedro (Pedro Santos)
├─ aff-ana (Ana Oliveira)
└─ ... (mais 6)

CONFIGURAÇÃO:
└─ Template UTM:
    utm_source: afiliado
    utm_medium: referral
    utm_campaign: [nome-do-afiliado]

DASHBOARD ESPECIAL:
┌──────────────┬────────┬────────┬──────────┬───────────┐
│ Afiliado     │ Clicks │ Vendas │ Receita  │ Comissão  │
├──────────────┼────────┼────────┼──────────┼───────────┤
│ João Silva   │ 1.245  │ 67     │ R$13.199 │ R$ 5.279  │ 🥇
│ Maria Costa  │ 892    │ 45     │ R$ 8.865 │ R$ 3.546  │ 🥈
│ Ana Oliveira │ 678    │ 34     │ R$ 6.698 │ R$ 2.679  │ 🥉
│ Pedro Santos │ 3.456  │ 0      │ R$ 0     │ R$ 0      │ 🚨
│ Carlos Lima  │ 234    │ 12     │ R$ 2.364 │ R$ 945    │
│ ...          │ ...    │ ...    │ ...      │ ...       │
└──────────────┴────────┴────────┴──────────┴───────────┘

ANÁLISE DE PEDRO SANTOS:
├─ 3.456 cliques em 2 dias
├─ Todos do mesmo IP (191.123.45.67)
├─ Todos entre 2h-4h da manhã
├─ User-Agent suspeito (bot)
└─ 0 vendas

AÇÕES:
✅ Bonificar João, Maria e Ana
✅ Criar programa VIP para top 3
❌ Bloquear Pedro (fraude)
⚠️ Investigar Carlos (conversão baixa)
📧 Email para afiliados inativos

RESULTADO:
├─ Economia de R$ 0 em comissões fraudulentas
├─ Motivação dos melhores afiliados
└─ Foco em quem realmente vende
```

---

## ❓ Perguntas Frequentes

### **1. Quantos links posso criar?**

Depende do seu plano:
- **Starter:** 100 links
- **Pro:** 1.000 links
- **Scale:** Ilimitado

### **2. Os links expiram?**

Não! Os links são permanentes enquanto:
- Sua conta estiver ativa
- Você não deletar o link

### **3. Posso editar um link depois de criado?**

Sim, você pode editar:
- ✅ Nome do link (descrição interna)
- ✅ URL de destino
- ✅ UTMs
- ❌ Alias (o link curto em si)

**Por quê não mudar o alias?**
Se você já divulgou o link, mudar o alias
quebraria todos os lugares onde está publicado.

### **4. E se eu deletar um link sem querer?**

O link para de funcionar imediatamente.
Não há como recuperar.

**Solução:** Não delete, apenas desative!

### **5. Posso usar meu próprio domínio?**

Sim, no plano **Scale** você pode:
- links.seudominio.com
- go.seudominio.com
- click.seudominio.com

### **6. Os links funcionam em redes sociais?**

Sim! Funcionam em:
- ✅ Instagram (bio, stories)
- ✅ Facebook (posts, anúncios)
- ✅ TikTok (bio, vídeos)
- ✅ YouTube (descrição)
- ✅ WhatsApp
- ✅ Email
- ✅ Qualquer lugar!

### **7. Como sei se alguém clicou no meu link?**

Acesse o dashboard do link e veja:
- Cliques em tempo real
- Gráfico por hora/dia
- Mapa de origem
- Dispositivos usados

### **8. E se o link de destino mudar?**

Basta editar o link e atualizar a URL de destino.
Todos os cliques futuros irão para o novo destino.

### **9. Posso criar um link sem UTM?**

Sim, mas não é recomendado!
UTMs são essenciais para rastreamento.

### **10. Como uso com afiliados?**

Opção 1: Link único por afiliado
```
aff-joao
aff-maria
aff-pedro
```

Opção 2: Link com código do afiliado
```
curso-mkt?ref=joao
curso-mkt?ref=maria
curso-mkt?ref=pedro
```

---

## 🎓 Próximos Passos

Agora que você entendeu o Link Maestro:

1. ✅ **Crie seu primeiro link** agora!
2. ✅ **Escolha um template UTM**
3. ✅ **Divulgue e monitore**
4. ✅ **Analise os resultados**
5. ✅ **Otimize baseado em dados**

**Dúvidas?**
- 📧 Suporte: suporte@mercadoafiliado.com
- 💬 Chat: Dentro da plataforma
- 📚 Mais guias: /docs

---

**Boas vendas! 🚀**
