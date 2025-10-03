/*
 * Dashboard Unificado - Scripts Compartilhados
 */

// Função para "Coming Soon"
function showComingSoon(feature) {
    alert(`${feature} - Em breve! 🚀`);
}

// Inicializar ícones Lucide quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se Lucide está disponível
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

// Função para gerar menu sidebar padrão
function generateSidebarMenu(activePage = '') {
    const menuItems = [
        { href: '/dashboard', icon: 'bar-chart-3', text: 'Dashboard', key: 'dashboard' },
        { href: '/unified-panel', icon: 'trending-up', text: 'Painel Unificado', key: 'unified-panel' },
        { href: '/integrations', icon: 'link', text: 'IntegraSync', key: 'integrations' },
        { href: '/link-maestro', icon: 'target', text: 'Link Maestro', key: 'link-maestro' },
        { href: '/pixel', icon: 'eye', text: 'Pixel BR', key: 'pixel' },
        { href: '#', icon: 'alert-triangle', text: 'Alerta Queda', key: 'alerta-queda', onclick: "showComingSoon('Alerta Queda')" },
        { href: '#', icon: 'bridge', text: 'CAPI Bridge', key: 'capi-bridge', onclick: "showComingSoon('CAPI Bridge')" }
    ];

    let menuHTML = '<ul class="sidebar-menu">';

    menuItems.forEach(item => {
        const isActive = item.key === activePage;
        const activeClass = isActive ? ' class="active"' : '';
        const onclickAttr = item.onclick ? ` onclick="${item.onclick}"` : '';

        menuHTML += `
            <li>
                <a href="${item.href}"${activeClass}${onclickAttr}>
                    <i data-lucide="${item.icon}" style="width: 16px; height: 16px; margin-right: 6px;"></i>
                    ${item.text}
                </a>
            </li>
        `;
    });

    menuHTML += '</ul>';
    return menuHTML;
}

// Controle do dropdown do usuário
function toggleUserDropdown() {
    const dropdown = document.querySelector('.dropdown');
    if (dropdown) {
        dropdown.classList.toggle('active');
    }
}

// Fechar dropdown quando clicar fora
document.addEventListener('click', function(event) {
    const dropdown = document.querySelector('.dropdown');
    if (dropdown && !dropdown.contains(event.target)) {
        dropdown.classList.remove('active');
    }
});

// Função para gerar header unificado
function generateAppHeader(userName = 'Usuário') {
    const userInitials = userName.split(' ').map(n => n[0]).join('').toUpperCase();

    return `
        <header class="app-header">
            <a href="${BASE_URL || ''}/dashboard" class="app-header-logo">
                <div class="logo-icon">M</div>
                Mercado Afiliado
            </a>

            <div class="app-header-actions">
                <div class="dropdown">
                    <div class="app-header-user" onclick="toggleUserDropdown()">
                        <div class="user-avatar">${userInitials}</div>
                        <span class="user-name">${userName}</span>
                        <i data-lucide="chevron-down" style="width: 16px; height: 16px;"></i>
                    </div>
                    <div class="dropdown-content">
                        <a href="${BASE_URL || ''}/dashboard" class="dropdown-item">
                            <i data-lucide="user" style="width: 16px; height: 16px;"></i>
                            Meu Perfil
                        </a>
                        <a href="${BASE_URL || ''}/subscribe" class="dropdown-item">
                            <i data-lucide="credit-card" style="width: 16px; height: 16px;"></i>
                            Assinatura
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="${BASE_URL || ''}/logout" class="dropdown-item">
                            <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                            Sair
                        </a>
                    </div>
                </div>
            </div>
        </header>
    `;
}

// Função para gerar header padrão
function generatePanelHeader(title, subtitle = '', actions = '') {
    const actionsHTML = actions ? `<div>${actions}</div>` : '';

    return `
        <div class="panel-header">
            <div>
                <h1>${title}</h1>
                ${subtitle ? `<p>${subtitle}</p>` : ''}
            </div>
            ${actionsHTML}
        </div>
    `;
}