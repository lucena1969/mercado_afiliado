# Configuração de Widgets - Sidebar

## 📍 Como Configurar

1. WordPress Admin → **Aparência** → **Widgets**
2. Arrastar widgets para **Sidebar** (ou **Primary Sidebar**)
3. Configurar conforme abaixo

---

## Widget 1: CTA Principal (Teste Grátis)

**Tipo:** HTML Customizado

**Título:** (deixar vazio)

**Conteúdo:** Copiar de `blog/theme/sidebar-ctas.html` - Seção "CTA PRINCIPAL"

---

## Widget 2: Posts Recentes

**Tipo:** Artigos Recentes (padrão WordPress)

**Configurações:**
- Título: `📚 Artigos Recentes`
- Número de posts: `5`
- Exibir data: `Sim`

---

## Widget 3: Categorias

**Tipo:** Categorias (padrão WordPress)

**Configurações:**
- Título: `📁 Categorias`
- Exibir como dropdown: `Não`
- Mostrar contagem de posts: `Sim`
- Mostrar hierarquia: `Não`

---

## Widget 4: CTA Secundário (Features)

**Tipo:** HTML Customizado

**Título:** (deixar vazio)

**Conteúdo:** Copiar de `blog/theme/sidebar-ctas.html` - Seção "CTA SECUNDÁRIO"

---

## Widget 5: Busca

**Tipo:** Busca (padrão WordPress)

**Configurações:**
- Título: `🔍 Buscar`
- Placeholder: `Procurar artigos...`

---

## Widget 6: Tags (Opcional)

**Tipo:** Nuvem de Tags (padrão WordPress)

**Configurações:**
- Título: `🏷️ Tags Populares`
- Taxonomia: `Tags`
- Mostrar contagem: `Não`

---

## Ordem Final na Sidebar:

```
1. CTA Principal (Teste Grátis) ← Sticky no topo
2. Posts Recentes
3. Categorias
4. CTA Secundário (Features)
5. Busca
6. Tags (opcional)
```

---

## CSS para Sticky CTA (já incluído)

O CTA principal fica fixo ao rolar a página:

```css
.widget-cta {
    position: sticky;
    top: 2rem;
}
```

---

## Checklist:

- [ ] 6 widgets adicionados na sidebar
- [ ] CTA Principal com HTML correto
- [ ] Links apontando para mercadoafiliado.com.br/register
- [ ] Posts recentes mostrando 5 artigos
- [ ] Categorias configuradas
- [ ] Testado em mobile (widgets empilham abaixo do conteúdo)
