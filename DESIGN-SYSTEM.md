# 🎨 Design System CGLIC v2025

Sistema de design unificado e moderno para o Sistema de Informações CGLIC do Ministério da Saúde.

## 📋 Visão Geral

O Design System CGLIC foi criado para garantir **consistência visual**, **experiência de usuário profissional** e **manutenibilidade** em todo o sistema. Baseado nas melhores práticas do Material Design 3 e Fluent Design System.

## 🎯 Objetivos

- ✅ **Consistência Visual**: Padronização de cores, tipografia e componentes
- ✅ **Experiência Profissional**: Interface moderna e intuitiva
- ✅ **Acessibilidade**: Contraste adequado e usabilidade
- ✅ **Responsividade**: Mobile-first approach
- ✅ **Manutenibilidade**: CSS modular e reutilizável

## 🎨 Paleta de Cores

### Cores Primárias
```css
--primary-500: #3b82f6  /* Azul principal */
--primary-600: #2563eb  /* Azul hover */
--primary-700: #1d4ed8  /* Azul ativo */
```

### Cores de Status
```css
--success-500: #22c55e  /* Verde - Sucesso */
--warning-500: #f59e0b  /* Amarelo - Aviso */
--error-500: #ef4444    /* Vermelho - Erro */
--info-500: #0ea5e9     /* Azul claro - Informação */
```

### Cores Neutras
```css
--neutral-50: #fafafa   /* Fundo claro */
--neutral-800: #262626  /* Texto principal */
--neutral-600: #525252  /* Texto secundário */
```

## 📝 Tipografia

### Família de Fontes
```css
font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
```

### Escala Tipográfica
- **text-xs**: 12px - Labels pequenos
- **text-sm**: 14px - Texto padrão de tabelas
- **text-base**: 16px - Texto padrão
- **text-lg**: 18px - Subtítulos
- **text-xl**: 20px - Títulos de seção
- **text-2xl**: 24px - Títulos de modal
- **text-3xl**: 30px - Títulos principais
- **text-4xl**: 36px - Títulos de dashboard

## 🧩 Componentes Principais

### 1. Botões

#### Botão Primário
```html
<button class="btn btn-primary">
    <i data-lucide="plus"></i>
    Criar Novo
</button>
```

#### Botão de Ação
```html
<a href="#" class="action-btn action-btn-primary">
    <i data-lucide="history"></i>
    Ver Histórico
</a>
```

### 2. Cards

#### Card Básico
```html
<div class="card">
    <div class="card-header">
        <h3>Título do Card</h3>
    </div>
    <div class="card-body">
        Conteúdo do card...
    </div>
</div>
```

#### Card de Estatística
```html
<div class="stat-card stat-card-primary">
    <div class="stat-card-icon">
        <i data-lucide="database"></i>
    </div>
    <div class="stat-card-value">1,234</div>
    <div class="stat-card-label">Total de Itens</div>
</div>
```

### 3. Badges e Status

#### Badge de Status
```html
<span class="status-badge status-concluido">
    Concluído
</span>
```

#### Badge de Prioridade
```html
<span class="priority-badge priority-alta">
    Alta
</span>
```

### 4. Tabelas Modernas

```html
<div class="modern-table-container">
    <div class="modern-table-header">
        <h2>
            <i data-lucide="table"></i>
            Título da Tabela
        </h2>
    </div>
    <div class="modern-table-wrapper">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Coluna 1</th>
                    <th>Coluna 2</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Dados</td>
                    <td>Dados</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
```

### 5. Modais

```html
<div class="modern-modal-backdrop">
    <div class="modern-modal">
        <div class="modern-modal-header">
            <h2 class="modern-modal-title">Título do Modal</h2>
        </div>
        <div class="modern-modal-body">
            Conteúdo do modal...
        </div>
        <div class="modern-modal-footer">
            <button class="btn btn-secondary">Cancelar</button>
            <button class="btn btn-primary">Confirmar</button>
        </div>
    </div>
</div>
```

## 📐 Layout e Estrutura

### Container Principal
```html
<div class="dashboard-container">
    <div class="dashboard-content">
        <!-- Conteúdo do dashboard -->
    </div>
</div>
```

### Header de Dashboard
```html
<div class="dashboard-header">
    <div class="dashboard-header-content">
        <div class="dashboard-title">
            <h1>
                <i data-lucide="icon"></i>
                Título do Dashboard
            </h1>
            <div class="subtitle">Descrição do dashboard</div>
        </div>
        <div class="dashboard-actions">
            <a href="#" class="action-btn action-btn-primary">Ação</a>
        </div>
    </div>
</div>
```

### Grid de Estatísticas
```html
<div class="stats-grid">
    <div class="stat-card stat-card-primary">
        <!-- Card de estatística -->
    </div>
    <!-- Mais cards... -->
</div>
```

## 🎭 Estados e Variações

### Estados de Botões
- **Normal**: Estado padrão
- **Hover**: `transform: translateY(-2px)` + sombra
- **Active**: Estado pressionado
- **Disabled**: `opacity: 0.5` + `pointer-events: none`

### Variações de Cards
- **stat-card-primary**: Azul (dados gerais)
- **stat-card-success**: Verde (itens concluídos)
- **stat-card-warning**: Amarelo (itens em andamento)
- **stat-card-error**: Vermelho (itens com problema)
- **stat-card-info**: Azul claro (informações)
- **stat-card-secondary**: Cinza (dados secundários)

### Estados de Status
- **status-todo**: Cinza - A fazer
- **status-em-progresso**: Amarelo - Em progresso
- **status-aguardando**: Azul - Aguardando
- **status-concluido**: Verde - Concluído
- **status-cancelado**: Vermelho - Cancelado

## 📱 Responsividade

### Breakpoints
```css
/* Mobile */
@media (max-width: 768px)

/* Tablet */
@media (max-width: 1024px)

/* Desktop */
@media (min-width: 1025px)
```

### Adaptações Mobile
- **Grid**: Auto-colapso para 1 coluna
- **Botões**: Largura completa
- **Tabelas**: Scroll horizontal
- **Modais**: Margin reduzida
- **Stats**: Grid 2x2 → 1x1

## 🎨 Animações e Micro-interações

### Transições Padrão
```css
transition: all var(--transition-normal); /* 250ms */
```

### Efeitos de Hover
- **Cards**: `translateY(-2px)` + sombra aumentada
- **Botões**: `translateY(-2px)` + gradiente alterado
- **Tabelas**: Background highlight

### Animações
- **fadeIn**: Para modais e notificações
- **slideInUp**: Para elementos que aparecem
- **pulse**: Para elementos de urgência

## 🛠️ Implementação

### Arquivos Necessários
1. **`assets/design-system.css`** - Sistema base
2. **`assets/dashboard-unified.css`** - Componentes de dashboard

### Como Usar
```html
<head>
    <link rel="stylesheet" href="assets/design-system.css">
    <link rel="stylesheet" href="assets/dashboard-unified.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
```

### Inicialização
```javascript
// Sempre no final do body
<script>
    lucide.createIcons();
</script>
```

## 🔧 Customização

### CSS Custom Properties
Todas as cores, tamanhos e espacamentos são baseados em variáveis CSS:

```css
:root {
    --primary-500: #3b82f6;  /* Pode ser alterado */
    --space-4: 1rem;         /* Espaçamento padrão */
    --radius-lg: 0.5rem;     /* Bordas arredondadas */
}
```

### Classes Utilitárias
```css
.text-primary { color: var(--primary-600); }
.bg-success { background-color: var(--success-500); }
.p-4 { padding: var(--space-4); }
.rounded-lg { border-radius: var(--radius-lg); }
```

## ✅ Checklist de Implementação

### Para Novos Componentes
- [ ] Usar variáveis CSS do design system
- [ ] Implementar estados de hover/focus
- [ ] Adicionar responsividade mobile
- [ ] Incluir ícones Lucide quando apropriado
- [ ] Testar em diferentes navegadores
- [ ] Validar acessibilidade (contraste, ARIA)

### Para Páginas Existentes
- [ ] Substituir CSS inline por classes do sistema
- [ ] Atualizar estrutura HTML para usar componentes
- [ ] Testar funcionalidades existentes
- [ ] Verificar responsividade
- [ ] Otimizar performance

## 🎯 Benefícios

### Para Desenvolvedores
- **Produtividade**: Componentes prontos e reutilizáveis
- **Consistência**: Padrões visuais automáticos
- **Manutenibilidade**: CSS organizado e modular

### Para Usuários
- **Experiência Profissional**: Interface moderna e polida
- **Intuitividade**: Padrões familiares e consistentes
- **Performance**: Animações fluidas e responsivas

### Para o Sistema
- **Escalabilidade**: Fácil adição de novos módulos
- **Flexibilidade**: Customização via variáveis CSS
- **Futuro-proof**: Baseado em padrões modernos

## 📚 Referências

- **Material Design 3**: Principles e componentes
- **Fluent Design System**: Animações e micro-interações
- **Tailwind CSS**: Sistema de utilitários
- **CSS Custom Properties**: Variáveis nativas
- **Lucide Icons**: Biblioteca de ícones consistente

---

**📌 Nota**: Este design system é evolutivo e será aprimorado conforme necessidades do projeto e feedback dos usuários.

**🔄 Última atualização**: Agosto 2025 - v1.0