/**
 * Navigation Fix - Garante navegação correta para Link Maestro
 */

document.addEventListener('DOMContentLoaded', function() {
    // Encontrar todos os links do Link Maestro
    const linkMaestroLinks = document.querySelectorAll('a[href*="link-maestro"], a[href*="/link-maestro"]');
    
    linkMaestroLinks.forEach(function(link) {
        // Remover qualquer onclick que possa estar interferindo
        link.removeAttribute('onclick');
        
        // Garantir que o href está correto
        const baseUrl = window.location.origin + '/mercado_afiliado';
        link.href = baseUrl + '/link-maestro';
        
        // Adicionar listener de clique limpo
        link.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Log para debug
            console.log('🎯 Navegando para Link Maestro:', link.href);
            
            // Navegar
            window.location.href = link.href;
        });
        
        // Log para debug
        console.log('✅ Link Maestro corrigido:', link.href);
    });
    
    // Override da função showComingSoon para evitar interferência
    window.showComingSoon = function(feature) {
        if (feature.toLowerCase().includes('link') && feature.toLowerCase().includes('maestro')) {
            // Redirecionar para Link Maestro em vez de mostrar alerta
            console.log('🔄 Redirecionando Link Maestro em vez de mostrar "coming soon"');
            const baseUrl = window.location.origin + '/mercado_afiliado';
            window.location.href = baseUrl + '/link-maestro';
            return;
        }
        
        // Para outras funcionalidades, mostrar o alerta normal
        alert('🚧 ' + feature + ' estará disponível em breve!');
    };
    
    console.log('🛠️ Navigation Fix carregado - Link Maestro será redirecionado corretamente');
});