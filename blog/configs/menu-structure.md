# Configuração do Menu - Blog Mercado Afiliado

## 📍 Estrutura do Menu Principal

### Como Configurar:
1. WordPress Admin → **Aparência** → **Menus**
2. Criar novo menu: **"Menu Principal"**
3. Adicionar itens conforme abaixo
4. Atribuir localização: **Primary Menu**

---

## Menu Items:

### 1. Home (Link para plataforma principal)
```
Texto: Home
URL: https://mercadoafiliado.com.br
```

### 2. Blog
```
Texto: Blog
URL: https://blog.mercadoafiliado.com.br
```

### 3. Recursos (Dropdown - opcional)
```
Texto: Recursos
URL: #

Submenu:
  ├─ IntegraSync
  │  URL: https://mercadoafiliado.com.br/#integrasync
  │
  ├─ Pixel BR
  │  URL: https://mercadoafiliado.com.br/#pixel
  │
  └─ Link Maestro
     URL: https://mercadoafiliado.com.br/#link-maestro
```

### 4. Preços
```
Texto: Preços
URL: https://mercadoafiliado.com.br/#precos
```

### 5. Login
```
Texto: Login
URL: https://mercadoafiliado.com.br/login
```

### 6. Teste Grátis (Botão destacado)
```
Texto: Teste Grátis
URL: https://mercadoafiliado.com.br/register

Classes CSS: menu-item-teste-gratis
(Isso aplicará o estilo de botão - já configurado no custom-style.css)
```

---

## Configuração Visual:

### No Customizer:
```
Aparência → Personalizar → Primary Navigation

- Menu Location: Primary Menu
- Dropdown Arrow: ▼ (se tiver submenu)
- Mobile Menu Breakpoint: 768px
```

### CSS Adicional (já incluído no custom-style.css):
```css
/* Botão "Teste Grátis" destacado */
.menu-item-teste-gratis a {
    background: #2563eb !important;
    color: #fff !important;
    padding: 0.6rem 1.2rem !important;
    border-radius: 8px;
    font-weight: 600;
    margin-left: 0.5rem;
}
```

---

## Menu Secundário (Footer) - Opcional

### Localização: Footer Menu

```
Sobre → https://mercadoafiliado.com.br/#sobre
Termos de Uso → https://mercadoafiliado.com.br/termos
Política de Privacidade → https://mercadoafiliado.com.br/privacidade
Contato → https://mercadoafiliado.com.br/#contato
```

---

## Menu Mobile

### Configuração:
- Hamburger icon: ☰
- Off-canvas (desliza da lateral)
- Mesmo menu do desktop

### Verificar:
```
Aparência → Personalizar → Mobile Menu
- Style: Slide Out
- Location: Right
- Close Icon: ×
```

---

## Exemplo de Código (se precisar customizar via functions.php):

```php
// Registrar menu
function mercado_afiliado_register_menus() {
    register_nav_menus([
        'primary' => 'Menu Principal',
        'footer' => 'Menu Footer'
    ]);
}
add_action('init', 'mercado_afiliado_register_menus');
```

---

## Checklist:

- [ ] Menu criado em Aparência → Menus
- [ ] 6 itens adicionados (Home, Blog, Recursos, Preços, Login, Teste Grátis)
- [ ] Classe `menu-item-teste-gratis` adicionada ao último item
- [ ] Menu atribuído à localização "Primary"
- [ ] Testado em desktop e mobile
- [ ] Links funcionando corretamente
