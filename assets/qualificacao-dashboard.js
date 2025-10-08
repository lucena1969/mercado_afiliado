/**
 * Qualificação Dashboard JavaScript - Sistema CGLIC
 * Funcionalidades do painel de controle de qualificações
 * Baseado em licitacao-dashboard.js com adaptações para qualificação
 */

// ==================== TOGGLE SIDEBAR ====================

/**
 * Toggle da sidebar - abre/fecha a barra lateral
 */
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const toggleIcon = document.querySelector('#sidebarToggle i');
    
    if (sidebar && mainContent) {
        // Verificar se estamos em mobile
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            // Comportamento mobile - toggle da classe mobile-open
            sidebar.classList.toggle('mobile-open');
            
            // Alterar ícone
            if (sidebar.classList.contains('mobile-open')) {
                toggleIcon.setAttribute('data-lucide', 'x');
            } else {
                toggleIcon.setAttribute('data-lucide', 'menu');
            }
        } else {
            // Comportamento desktop - toggle da classe collapsed
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('collapsed');
            
            // Alterar ícone baseado no estado
            if (sidebar.classList.contains('collapsed')) {
                toggleIcon.setAttribute('data-lucide', 'panel-left-open');
            } else {
                toggleIcon.setAttribute('data-lucide', 'menu');
            }
            
            // Salvar estado no localStorage (apenas para desktop)
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }
        
        // Reinicializar os ícones Lucide para atualizar o ícone alterado
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

/**
 * Restaurar estado da sidebar do localStorage
 */
function restoreSidebarState() {
    // Só restaurar estado se não estivermos em mobile
    const isMobile = window.innerWidth <= 768;
    
    if (!isMobile) {
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const toggleIcon = document.querySelector('#sidebarToggle i');
            
            if (sidebar && mainContent) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('collapsed');
                toggleIcon.setAttribute('data-lucide', 'panel-left-open');
                
                // Reinicializar os ícones Lucide
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
        }
    }
}

/**
 * Lidar com redimensionamento da janela
 */
function handleResize() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const toggleIcon = document.querySelector('#sidebarToggle i');
    const isMobile = window.innerWidth <= 768;
    
    if (sidebar && mainContent && toggleIcon) {
        if (isMobile) {
            // Reset para comportamento mobile
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('collapsed');
            sidebar.classList.remove('mobile-open');
            toggleIcon.setAttribute('data-lucide', 'menu');
        } else {
            // Restaurar estado desktop
            sidebar.classList.remove('mobile-open');
            restoreSidebarState();
        }
        
        // Reinicializar os ícones Lucide
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

// ==================== NAVEGAÇÃO ENTRE SEÇÕES ====================

/**
 * Mostrar seção específica e atualizar navegação
 */
function showSection(sectionId) {
    // Esconder todas as seções
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => {
        section.classList.remove('active');
    });
    
    // Mostrar seção específica
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.classList.add('active');
    }
    
    // Atualizar navegação ativa
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.classList.remove('active');
    });
    
    // Ativar item de navegação correspondente
    const activeNavItem = document.querySelector(`[onclick*="${sectionId}"]`);
    if (activeNavItem) {
        activeNavItem.classList.add('active');
    }
    
    // Reinicializar componentes específicos da seção
    if (sectionId === 'dashboard') {
        initializeDashboardCharts();
    }
    
    // Salvar seção ativa
    localStorage.setItem('activeSection', sectionId);
    
    // Reinicializar os ícones Lucide
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

/**
 * Restaurar seção ativa do localStorage
 */
function restoreActiveSection() {
    const activeSection = localStorage.getItem('activeSection') || 'dashboard';
    showSection(activeSection);
}

// ==================== FORMULÁRIOS E VALIDAÇÃO ====================

/**
 * Inicializar formulários com validação
 */
function initializeForms() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', handleFormSubmit);
        
        // Adicionar validação em tempo real
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', validateField);
            input.addEventListener('input', clearFieldError);
        });
    });
}

/**
 * Validar campo individual
 */
function validateField(event) {
    const field = event.target;
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    // Validação de campo obrigatório
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'Este campo é obrigatório.';
    }
    
    // Validação de email
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Digite um email válido.';
        }
    }
    
    // Validação de valores monetários
    if (field.classList.contains('currency') && value) {
        // Remover formatação para validar
        const cleanValue = value.replace(/[^\d.,]/g, '').replace(/\./g, '').replace(',', '.');
        const numericValue = parseFloat(cleanValue);
        
        if (isNaN(numericValue) || numericValue <= 0) {
            isValid = false;
            errorMessage = 'Digite um valor válido maior que zero.';
        }
    }
    
    // Mostrar/esconder erro
    if (!isValid) {
        showFieldError(field, errorMessage);
    } else {
        clearFieldError({ target: field });
    }
    
    return isValid;
}

/**
 * Mostrar erro em campo
 */
function showFieldError(field, message) {
    field.classList.add('error');
    
    // Remover erro anterior se existir
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    
    // Adicionar nova mensagem de erro
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.color = '#e74c3c';
    errorDiv.style.fontSize = '12px';
    errorDiv.style.marginTop = '4px';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

/**
 * Limpar erro de campo
 */
function clearFieldError(event) {
    const field = event.target;
    field.classList.remove('error');
    
    const errorDiv = field.parentNode.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

/**
 * Processar envio de formulário
 */
function handleFormSubmit(event) {
    const form = event.target;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isFormValid = true;
    
    // Validar todos os campos obrigatórios
    inputs.forEach(input => {
        if (!validateField({ target: input })) {
            isFormValid = false;
        }
    });
    
    if (!isFormValid) {
        event.preventDefault();
        showNotification('Por favor, corrija os erros antes de continuar.', 'error');
        return false;
    }
    
    return true;
}

// ==================== SISTEMA DE NOTIFICAÇÕES ====================

/**
 * Mostrar notificação persistente e mais visível
 */
function showNotificationPersistent(message, type = 'success', duration = 10000) {
    // Remover notificações existentes
    const existingNotifications = document.querySelectorAll('.notification-persistent');
    existingNotifications.forEach(notification => {
        notification.remove();
    });
    
    // Criar nova notificação
    const notification = document.createElement('div');
    notification.className = `notification-persistent ${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        padding: 25px 35px;
        border-radius: 12px;
        color: white;
        font-weight: bold;
        font-size: 18px;
        z-index: 99999;
        min-width: 400px;
        text-align: center;
        box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        animation: pulseIn 0.5s ease-out;
        border: 3px solid rgba(255,255,255,0.3);
    `;
    
    // Definir cor baseada no tipo
    switch (type) {
        case 'success':
            notification.style.background = 'linear-gradient(135deg, #27ae60, #2ecc71)';
            notification.innerHTML = `<div style="display: flex; align-items: center; justify-content: center; gap: 15px;"><span style="font-size: 24px;">✅</span><span>${message}</span></div>`;
            break;
        case 'error':
            notification.style.background = 'linear-gradient(135deg, #e74c3c, #c0392b)';
            notification.innerHTML = `<div style="display: flex; align-items: center; justify-content: center; gap: 15px;"><span style="font-size: 24px;">❌</span><span>${message}</span></div>`;
            break;
        default:
            notification.style.background = 'linear-gradient(135deg, #3498db, #2980b9)';
            notification.innerHTML = `<div style="display: flex; align-items: center; justify-content: center; gap: 15px;"><span style="font-size: 24px;">ℹ️</span><span>${message}</span></div>`;
    }
    
    document.body.appendChild(notification);
    
    // Auto-remover após duração especificada
    setTimeout(() => {
        notification.style.animation = 'fadeOut 0.5s ease-in';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 500);
    }, duration);
}

/**
 * Mostrar notificação
 */
function showNotification(message, type = 'info', duration = 5000) {
    // Remover notificações existentes
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => {
        notification.remove();
    });
    
    // Criar nova notificação
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        border-radius: 8px;
        color: white;
        font-weight: 600;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideInRight 0.3s ease-out;
    `;
    
    // Definir cor baseada no tipo
    switch (type) {
        case 'success':
            notification.style.background = 'linear-gradient(135deg, #27ae60, #2ecc71)';
            break;
        case 'error':
            notification.style.background = 'linear-gradient(135deg, #e74c3c, #c0392b)';
            break;
        case 'warning':
            notification.style.background = 'linear-gradient(135deg, #f59e0b, #d97706)';
            break;
        default:
            notification.style.background = 'linear-gradient(135deg, #3498db, #2980b9)';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Auto-remover após duração especificada
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, duration);
}

// ==================== GRÁFICOS E DASHBOARD ====================

/**
 * Inicializar gráficos do dashboard
 */
function initializeDashboardCharts() {
    // Aguardar um pouco para garantir que a seção está visível
    setTimeout(() => {
        loadChartsData();
    }, 100);
}

/**
 * Carregar dados dos gráficos via AJAX
 */
function loadChartsData() {
    fetch('process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'acao=dashboard_stats_qualificacao'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            initializeStatusChart(data.data.status_chart);
            initializePerformanceChart(data.data.performance_chart);
        } else {
            console.error('Erro ao carregar dados dos gráficos:', data.message);
            // Em caso de erro, inicializar com dados zerados
            initializeStatusChart();
            initializePerformanceChart();
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
        // Em caso de erro, inicializar com dados zerados
        initializeStatusChart();
        initializePerformanceChart();
    });
}


/**
 * Gráfico de status das qualificações
 */
function initializeStatusChart(chartData = null) {
    const ctx = document.getElementById('statusChart');
    if (!ctx || typeof Chart === 'undefined') return;
    
    // Destruir gráfico existente se houver
    if (window.statusChartInstance) {
        window.statusChartInstance.destroy();
    }
    
    // Usar dados passados ou valores padrão zerados
    const labels = chartData ? chartData.labels : ['Jul', 'Ago', 'Set'];
    const emAnalise = chartData ? chartData.em_analise : [10, 15, 8];
    const concluido = chartData ? chartData.concluido : [5, 8, 12];
    const arquivado = chartData ? chartData.arquivado : [2, 1, 3];
    
    window.statusChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Em Análise',
                    data: emAnalise,
                    backgroundColor: '#f59e0b',
                    borderRadius: 4,
                    borderSkipped: false,
                },
                {
                    label: 'Concluído',
                    data: concluido,
                    backgroundColor: '#27ae60',
                    borderRadius: 4,
                    borderSkipped: false,
                },
                {
                    label: 'Arquivado',
                    data: arquivado,
                    backgroundColor: '#6c757d',
                    borderRadius: 4,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            size: 12,
                            family: "'Inter', 'Segoe UI', Roboto, sans-serif"
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

/**
 * Gráfico de performance por responsável
 */
function initializePerformanceChart(chartData = null) {
    const ctx = document.getElementById('performanceChart');
    if (!ctx || typeof Chart === 'undefined') return;
    
    // Destruir gráfico existente se houver
    if (window.performanceChartInstance) {
        window.performanceChartInstance.destroy();
    }
    
    // Usar dados passados ou valores padrão
    const labels = chartData ? chartData.labels : ['LARYSSA', 'VALÉRIA', 'DÉBORAH', 'FABIANA', 'RAFAEL'];
    const totais = chartData ? chartData.totais : [17, 14, 12, 9, 8];
    const cores = chartData ? chartData.cores : ['#27ae60', '#f39c12', '#e74c3c', '#f39c12', '#27ae60'];
    
    window.performanceChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total de Processos',
                data: totais,
                backgroundColor: cores,
                borderColor: cores,
                borderWidth: 1,
                borderRadius: 4,
                borderSkipped: false,
            }]
        },
        options: {
            indexAxis: 'y', // Torna o gráfico horizontal
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // Esconder legend para mais espaço
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            const taxa = chartData && chartData.taxa ? chartData.taxa[context.dataIndex] : '0.0';
                            return `Taxa de conclusão: ${taxa}%`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    },
                    ticks: {
                        font: {
                            size: 11,
                            family: "'Inter', 'Segoe UI', Roboto, sans-serif"
                        }
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11,
                            family: "'Inter', 'Segoe UI', Roboto, sans-serif",
                            weight: '500'
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'nearest'
            }
        }
    });
}

// ==================== PROCESSAMENTO DE FORMULÁRIOS ====================

// Função removida - usando pattern simples igual às licitações

// ==================== UTILITÁRIOS ====================

/**
 * Formatar valor como moeda
 */
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

/**
 * Formatar data para exibição
 */
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

/**
 * Debounce para otimizar chamadas de função
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Copiar texto para clipboard
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showNotification('Texto copiado para a área de transferência!', 'success');
    } catch (err) {
        // Fallback para navegadores mais antigos
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            showNotification('Texto copiado para a área de transferência!', 'success');
        } catch (fallbackErr) {
            showNotification('Não foi possível copiar o texto.', 'error');
        }
        document.body.removeChild(textArea);
    }
}

// ==================== INICIALIZAÇÃO ====================

/**
 * Inicializar todas as funcionalidades quando o DOM estiver pronto
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 Qualificação Dashboard - Inicializando...');
    
    // Inicializar ícones Lucide
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Configurar event listeners
    setupEventListeners();
    
    // NOVA ABORDAGEM: Event listener direto no botão submit
    const btnSubmitQualificacao = document.getElementById('btn-criar-qualificacao');
    if (btnSubmitQualificacao) {
        btnSubmitQualificacao.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            console.log('🎯 BOTÃO CLICADO - Processando submissão...');

            const form = document.getElementById('formCriarQualificacao');
            if (!form) {
                console.error('Formulário não encontrado!');
                return;
            }

            const formData = new FormData(form);
            
            // DEBUG: Verificar dados antes de enviar
            console.log('=== DADOS DO FORMULÁRIO ===');
            console.log('Ação:', formData.get('acao'));
            console.log('ID:', formData.get('id'));
            console.log('NUP:', formData.get('nup'));
            console.log('PCA_DADOS_ID:', formData.get('pca_dados_id'));
            console.log('===========================');

            // Converter valor monetário antes de enviar
            const valorEstimado = formData.get('valor_estimado');
            if (valorEstimado) {
                let cleanValue = valorEstimado.toString().trim();
                if (cleanValue.includes(',')) {
                    cleanValue = cleanValue.replace(/\./g, '').replace(',', '.');
                } else if (cleanValue.includes('.')) {
                    const parts = cleanValue.split('.');
                    if (parts.length === 2 && parts[1].length <= 2) {
                        cleanValue = cleanValue;
                    } else {
                        cleanValue = cleanValue.replace(/\./g, '');
                    }
                }
                formData.set('valor_estimado', cleanValue);
            }

            // Mostrar loading
            const originalText = this.innerHTML;
            const isEdicao = formData.get('acao') === 'editar_qualificacao';
            const loadingText = isEdicao ? 'Salvando...' : 'Criando...';
            this.innerHTML = `<i data-lucide="loader-2" style="animation: spin 1s linear infinite;"></i> ${loadingText}`;
            this.disabled = true;

            fetch('process.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                console.log('📡 Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('📋 Response data:', data);
                if (data.success) {
                    const successMsg = isEdicao ? '🔄 QUALIFICAÇÃO EDITADA E SALVA COM SUCESSO!' : '✅ Qualificação criada com sucesso!';
                    
                    showNotificationPersistent(successMsg, 'success');
                    fecharModal('modalCriarQualificacao');
                    
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    const errorMsg = isEdicao ? 'Erro ao atualizar qualificação' : 'Erro ao criar qualificação';
                    console.error('❌ Erro no backend:', data.message);
                    showNotificationPersistent('❌ ERRO: ' + (data.message || errorMsg), 'error');
                }
            })
            .catch(error => {
                console.error('🚨 Erro de rede:', error);
                showNotificationPersistent('❌ ERRO DE CONEXÃO: Verifique sua conexão e tente novamente', 'error');
            })
            .finally(() => {
                this.innerHTML = originalText;
                this.disabled = false;
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
        });
    }
    
    // Event listener para formulário de relatórios (IGUAL LICITAÇÕES)
    const formRelatorioQualificacao = document.getElementById('formRelatorioQualificacao');
    if (formRelatorioQualificacao) {
        formRelatorioQualificacao.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const params = new URLSearchParams();

            for (const [key, value] of formData) {
                if (value) params.append(key, value);
            }

            const formato = formData.get('formato');
            const url = 'relatorios/gerar_relatorio_qualificacao.php?' + params.toString();

            if (formato === 'html') {
                // Abrir em nova aba
                window.open(url, '_blank');
            } else {
                // Download direto
                window.location.href = url;
            }

            // Fechar modal
            fecharModal('modalRelatorioQualificacao');
        });
    }
    
    // Restaurar estados salvos
    restoreSidebarState();
    restoreActiveSection();
    
    // Inicializar formulários
    initializeForms();
    
    // Configurar resize handler com debounce
    const debouncedResize = debounce(handleResize, 250);
    window.addEventListener('resize', debouncedResize);
    
    console.log('✅ Qualificação Dashboard - Inicialização concluída!');
});

/**
 * Configurar event listeners principais
 */
function setupEventListeners() {
    // Toggle sidebar
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    // Configurar filtros automáticos
    setupFiltrosAutomaticos();
    
    // Fechar sidebar ao clicar fora (apenas mobile)
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile && sidebar && 
            sidebar.classList.contains('mobile-open') && 
            !sidebar.contains(event.target) && 
            !sidebarToggle.contains(event.target)) {
            
            sidebar.classList.remove('mobile-open');
            const toggleIcon = sidebarToggle.querySelector('i');
            if (toggleIcon) {
                toggleIcon.setAttribute('data-lucide', 'menu');
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
        }
    });
    
    // Configurar máscaras para campos de entrada
    setupInputMasks();
    
    // Configurar botões de ação
    setupActionButtons();
}

/**
 * Configurar máscaras de entrada
 */
function setupInputMasks() {
    // Máscara para NUP (igual à licitação)
    const nupInput = document.querySelector('input[name="nup"]');
    if (nupInput) {
        nupInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.substring(0, 17);
                let formatted = '';
                
                if (value.length > 0) {
                    formatted = value.substring(0, 5);
                }
                if (value.length > 5) {
                    formatted += '.' + value.substring(5, 11);
                }
                if (value.length > 11) {
                    formatted += '/' + value.substring(11, 15);
                }
                if (value.length > 15) {
                    formatted += '-' + value.substring(15, 17);
                }
                
                e.target.value = formatted;
            }
        });
    }
    
    // Máscara para valores monetários
    const currencyInputs = document.querySelectorAll('.currency');
    currencyInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                // Converter para centavos e depois para reais
                let numericValue = parseInt(value);
                let formattedValue = (numericValue / 100).toFixed(2);
                
                // Adicionar separador de milhares
                let parts = formattedValue.split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                
                // Usar vírgula como separador decimal
                e.target.value = parts.join(',');
            } else {
                e.target.value = '';
            }
        });
        
        input.addEventListener('blur', function(e) {
            if (e.target.value) {
                // Limpar formatação e converter
                let cleanValue = e.target.value.replace(/\./g, '').replace(',', '.');
                let numericValue = parseFloat(cleanValue);
                
                if (!isNaN(numericValue) && numericValue > 0) {
                    // Formatar como moeda brasileira
                    e.target.value = new Intl.NumberFormat('pt-BR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(numericValue);
                }
            }
        });
        
        // Permitir apenas números, vírgula e ponto
        input.addEventListener('keypress', function(e) {
            const char = String.fromCharCode(e.which);
            if (!/[\d.,]/.test(char)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Configurar botões de ação
 */
function setupActionButtons() {
    // Botões de confirmação
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    confirmButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm');
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // Botões de loading
    const loadingButtons = document.querySelectorAll('[data-loading]');
    loadingButtons.forEach(button => {
        button.addEventListener('click', function() {
            const originalText = this.textContent;
            const loadingText = this.getAttribute('data-loading') || 'Carregando...';
            
            this.textContent = loadingText;
            this.disabled = true;
            
            // Restaurar após 5 segundos (fallback)
            setTimeout(() => {
                this.textContent = originalText;
                this.disabled = false;
            }, 5000);
        });
    });
}

// ==================== SISTEMA DE FILTROS AUTOMÁTICOS ====================

/**
 * Configurar filtros automáticos
 */
function setupFiltrosAutomaticos() {
    const formFiltro = document.getElementById('filtroQualificacoes');
    if (!formFiltro) return;
    
    // Auto-submit ao alterar selects
    const selects = formFiltro.querySelectorAll('select');
    selects.forEach(select => {
        select.addEventListener('change', function() {
            formFiltro.submit();
        });
    });
    
    // Debounce para campo de busca
    const campoBusca = document.getElementById('busca');
    if (campoBusca) {
        let timeoutId;
        campoBusca.addEventListener('input', function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                formFiltro.submit();
            }, 800); // 800ms de delay
        });
    }
}

// ==================== FUNÇÕES DE AÇÕES DA TABELA ====================

/**
 * Visualizar qualificação
 */
function visualizarQualificacao(id) {
    // Buscar dados via AJAX
    fetch('process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'acao=buscar_qualificacao&id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const qual = data.data;
            
            // Preencher modal de visualização
            const modal = document.getElementById('modalVisualizacao');
            if (!modal) {
                // Se modal não existe, criar uma única vez
                criarModalVisualizacao();
            }
            
            // Preencher dados no modal
            preencherModalVisualizacao(qual);
            
            // Mostrar modal (igual ao padrão licitações)
            const modalElement = document.getElementById('modalVisualizacao');
            modalElement.classList.add('show');
            modalElement.style.display = 'block';
            
            // Inicializar ícones Lucide
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        } else {
            showNotification(data.message || 'Erro ao buscar qualificação', 'error');
        }
    })
    .catch(error => {
        showNotification('Erro de conexão', 'error');
    });
}

/**
 * Editar qualificação - Reutilizar modal de criação
 */
function editarQualificacao(id) {
    // Buscar dados da qualificação via AJAX
    fetch('process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'acao=buscar_qualificacao&id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const qual = data.data;
            
            // Configurar modal para modo edição
            configurarModalParaEdicao(qual);
            
            // Carregar dados PCA vinculado se existir
            if (qual.pca_dados_id) {
                carregarPcaVinculadoEdicao(qual.pca_dados_id);
            }
            
            // Mostrar modal
            abrirModal('modalCriarQualificacao');
            
        } else {
            showNotification(data.message || 'Erro ao buscar qualificação', 'error');
        }
    })
    .catch(error => {
        showNotification('Erro de conexão', 'error');
    });
}

// Modal de edição removido - agora usa modal unificado

// Função preencherModalEdicao removida - agora usa preencherCamposFormulario

// Função salvarEdicao removida - agora usa o form unificado

/**
 * Configurar máscara de moeda para input
 */
function setupCurrencyMask(input) {
    input.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 0) {
            let numericValue = parseInt(value);
            let formattedValue = (numericValue / 100).toFixed(2);
            let parts = formattedValue.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            e.target.value = parts.join(',');
        } else {
            e.target.value = '';
        }
    });
}

/**
 * Excluir qualificação
 */
function excluirQualificacao(id) {
    // Confirmação dupla para segurança
    const confirmacao1 = confirm('⚠️ ATENÇÃO: Você tem certeza que deseja EXCLUIR esta qualificação?\\n\\nEsta ação NÃO pode ser desfeita!');
    
    if (!confirmacao1) {
        return;
    }
    
    const confirmacao2 = confirm('🚨 CONFIRMAÇÃO FINAL: Excluir definitivamente a qualificação?');
    
    if (!confirmacao2) {
        return;
    }
    
    // Enviar requisição de exclusão
    fetch('process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'acao=excluir_qualificacao&id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Qualificação excluída com sucesso!', 'success');
            // Recarregar a página após 1 segundo
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message || 'Erro ao excluir qualificação', 'error');
        }
    })
    .catch(error => {
        showNotification('Erro de conexão', 'error');
    });
}

/**
 * Criar modal de visualização (uma única vez)
 */
function criarModalVisualizacao() {
    const modalHtml = `
        <div id="modalVisualizacao" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i data-lucide="eye"></i> Detalhes da Qualificação</h3>
                    <span class="close" onclick="fecharModal('modalVisualizacao')">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="form-grid" id="dadosVisualizacao">
                        <!-- Conteúdo será preenchido dinamicamente -->
                    </div>
                </div>
                <div class="modal-footer" style="display: flex; gap: 15px; justify-content: flex-end; padding: 20px; border-top: 1px solid #e5e7eb;">
                    <button type="button" class="btn-secondary" onclick="fecharModal('modalVisualizacao')" style="display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="x"></i> Fechar
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

/**
 * Preencher dados no modal de visualização
 */
function preencherModalVisualizacao(qual) {
    const container = document.getElementById('dadosVisualizacao');
    if (container) {
        container.innerHTML = `
            <div class="form-group">
                <label><strong>NUP:</strong></label>
                <p>${qual.nup}</p>
            </div>
            <div class="form-group">
                <label><strong>Área Demandante:</strong></label>
                <p>${qual.area_demandante}</p>
            </div>
            <div class="form-group">
                <label><strong>Responsável:</strong></label>
                <p>${qual.responsavel}</p>
            </div>
            <div class="form-group">
                <label><strong>Modalidade:</strong></label>
                <p>${qual.modalidade}</p>
            </div>
            <div class="form-group">
                <label><strong>Status:</strong></label>
                <p><span class="status-badge status-${qual.status.toLowerCase().replace(' ', '-')}">${qual.status}</span></p>
            </div>
            <div class="form-group">
                <label><strong>Valor Estimado:</strong></label>
                <p>R$ ${parseFloat(qual.valor_estimado).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
            </div>
            <div class="form-group form-full">
                <label><strong>Objeto:</strong></label>
                <p>${qual.objeto}</p>
            </div>
            <div class="form-group form-full">
                <label><strong>Palavras-chave:</strong></label>
                <p>${qual.palavras_chave || 'Nenhuma'}</p>
            </div>
            <div class="form-group form-full">
                <label><strong>Observações:</strong></label>
                <p>${qual.observacoes || 'Nenhuma observação'}</p>
            </div>
            <div class="form-group">
                <label><strong>Criado em:</strong></label>
                <p>${new Date(qual.criado_em).toLocaleString('pt-BR')}</p>
            </div>
            <div class="form-group">
                <label><strong>Atualizado em:</strong></label>
                <p>${new Date(qual.atualizado_em).toLocaleString('pt-BR')}</p>
            </div>
        `;
    }
}

/**
 * Abrir modal (IGUAL LICITAÇÕES)
 */
function abrirModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) {
        console.warn('Modal não encontrado:', modalId);
        return;
    }

    // Se for o modal de qualificação, verificar se é criação ou edição
    if (modalId === 'modalCriarQualificacao') {
        const isEdicao = document.getElementById('acaoFormQualificacao')?.value === 'editar_qualificacao';

        if (!isEdicao) {
            // Modal aberto para criação - configurar modo criação
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
            }
            configurarModalParaCriacao();
        }

        // Inicializar sistema de abas
        console.log('Inicializando sistema de abas para qualificação');
        setTimeout(() => {
            mostrarAbaQualificacao('informacoes-gerais');
        }, 50);
    }

    // Mostrar modal
    modal.style.display = 'block';
    modal.classList.add('show');

    // Reinicializar ícones
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

/**
 * Fechar modal (IGUAL LICITAÇÕES)
 */
function fecharModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) {
        console.warn('Modal não encontrado:', modalId);
        return;
    }

    // Remover classe show e forçar display none
    modal.classList.remove('show');
    modal.style.display = 'none';
}

// ==================== SISTEMA DE RELATÓRIOS ====================

/**
 * Gerar relatório de qualificação (IGUAL LICITAÇÕES)
 */
function gerarRelatorioQualificacao(tipo) {
    // Definir títulos por tipo
    const titulos = {
        'status': 'Relatório por Status',
        'modalidade': 'Relatório por Modalidade', 
        'area': 'Relatório por Área Demandante',
        'financeiro': 'Relatório Financeiro'
    };
    
    // Configurar modal
    document.getElementById('tipo_relatorio_qualificacao').value = tipo;
    document.getElementById('tituloRelatorioQualificacao').textContent = titulos[tipo] || 'Configurar Relatório';
    
    // Abrir modal
    abrirModal('modalRelatorioQualificacao');
}

// ==================== EXPORTAR FUNÇÕES GLOBAIS ====================

// Disponibilizar funções principais globalmente
window.QualificacaoDashboard = {
    toggleSidebar,
    showSection,
    showNotification,
    formatCurrency,
    formatDate,
    copyToClipboard,
    initializeDashboardCharts
};

// Disponibilizar funções de ação globalmente
window.visualizarQualificacao = visualizarQualificacao;
window.editarQualificacao = editarQualificacao;
window.excluirQualificacao = excluirQualificacao;
window.abrirModal = abrirModal;
window.fecharModal = fecharModal;

// Disponibilizar funções de navegação diretamente (para compatibilidade com onclick)
window.showSection = showSection;
window.toggleSidebar = toggleSidebar;
window.showNotification = showNotification;
window.gerarRelatorioQualificacao = gerarRelatorioQualificacao;

// FUNÇÃO GLOBAL PARA SUBMISSÃO - SOLUÇÃO DEFINITIVA
window.submitQualificacaoForm = function() {
    console.log('🚀 FUNÇÃO GLOBAL CHAMADA - submitQualificacaoForm()');
    
    const form = document.getElementById('formCriarQualificacao');
    if (!form) {
        console.error('❌ Formulário não encontrado!');
        alert('Erro: Formulário não encontrado');
        return;
    }

    const formData = new FormData(form);
    
    // Log completo dos dados
    console.log('=== TODOS OS DADOS DO FORM ===');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }
    console.log('===============================');

    // Verificar se é edição
    const acao = formData.get('acao');
    const isEdicao = acao === 'editar_qualificacao';
    
    console.log(`📝 Modo: ${isEdicao ? 'EDIÇÃO' : 'CRIAÇÃO'}`);

    // Converter valor monetário
    const valorEstimado = formData.get('valor_estimado');
    if (valorEstimado) {
        let cleanValue = valorEstimado.toString().trim();
        if (cleanValue.includes(',')) {
            cleanValue = cleanValue.replace(/\./g, '').replace(',', '.');
        }
        formData.set('valor_estimado', cleanValue);
    }

    // Botão de submit
    const btn = document.getElementById('btn-criar-qualificacao');
    const originalText = btn.innerHTML;
    const loadingText = isEdicao ? 'Salvando...' : 'Criando...';
    
    btn.innerHTML = `<i data-lucide="loader-2"></i> ${loadingText}`;
    btn.disabled = true;

    // Fazer requisição
    fetch('process.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams(formData)
    })
    .then(response => {
        console.log('📡 Status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('📊 Resposta:', data);
        
        if (data.success) {
            const msg = isEdicao ? '🔄 QUALIFICAÇÃO EDITADA E SALVA COM SUCESSO!' : '✅ QUALIFICAÇÃO CRIADA COM SUCESSO!';
            
            if (typeof showNotificationPersistent === 'function') {
                showNotificationPersistent(msg, 'success');
            } else {
                alert(msg);
            }
            
            fecharModal('modalCriarQualificacao');
            setTimeout(() => location.reload(), 1500);
            
        } else {
            const errorMsg = data.message || 'Erro desconhecido';
            console.error('❌ Erro do servidor:', errorMsg);
            alert(`❌ ERRO: ${errorMsg}`);
        }
    })
    .catch(error => {
        console.error('🚨 Erro de conexão:', error);
        alert(`❌ ERRO DE CONEXÃO: ${error.message}`);
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
};

// ==================== SISTEMA DE ABAS PARA MODAL DE QUALIFICAÇÃO ====================

// Sistema de abas para o modal de qualificação
let abaAtualQualificacao = 0;
const abasQualificacao = ['informacoes-gerais', 'detalhes-objeto', 'vinculacao-pca', 'valores-observacoes'];

function mostrarAbaQualificacao(nomeAba) {
    console.log('Mostrando aba:', nomeAba);
    
    // Ocultar todas as abas
    document.querySelectorAll('#modalCriarQualificacao .tab-content').forEach(aba => {
        aba.classList.remove('active');
    });
    
    // Remover classe active de todos os botões
    document.querySelectorAll('#modalCriarQualificacao .tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Mostrar aba selecionada
    const abaElement = document.getElementById('aba-' + nomeAba);
    if (abaElement) {
        abaElement.classList.add('active');
    }
    
    // Adicionar classe active ao botão correspondente
    const btnElement = document.querySelector(`#modalCriarQualificacao .tab-button[onclick*="${nomeAba}"]`);
    if (btnElement) {
        btnElement.classList.add('active');
    }
    
    // Atualizar índice da aba atual
    abaAtualQualificacao = abasQualificacao.indexOf(nomeAba);
    
    // Controlar visibilidade dos botões de navegação
    atualizarBotoesNavegacaoQualificacao();
    
    // Recriar ícones Lucide
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function proximaAbaQualificacao() {
    if (abaAtualQualificacao < abasQualificacao.length - 1) {
        abaAtualQualificacao++;
        mostrarAbaQualificacao(abasQualificacao[abaAtualQualificacao]);
    }
}

function abaAnteriorQualificacao() {
    if (abaAtualQualificacao > 0) {
        abaAtualQualificacao--;
        mostrarAbaQualificacao(abasQualificacao[abaAtualQualificacao]);
    }
}

function atualizarBotoesNavegacaoQualificacao() {
    const btnAnterior = document.getElementById('btn-anterior-qualificacao');
    const btnProximo = document.getElementById('btn-proximo-qualificacao');
    const btnCriar = document.getElementById('btn-criar-qualificacao');
    
    if (btnAnterior) {
        btnAnterior.style.display = abaAtualQualificacao > 0 ? 'inline-flex' : 'none';
    }
    
    if (btnProximo) {
        btnProximo.style.display = abaAtualQualificacao < abasQualificacao.length - 1 ? 'inline-flex' : 'none';
    }
    
    if (btnCriar) {
        btnCriar.style.display = abaAtualQualificacao === abasQualificacao.length - 1 ? 'inline-flex' : 'none';
    }
}

function resetarFormularioQualificacao() {
    document.getElementById('formCriarQualificacao').reset();
    // Limpar PCA selecionado
    removerPcaSelecionadoCriacao();
    // Voltar para primeira aba
    mostrarAbaQualificacao('informacoes-gerais');
}

// Disponibilizar funções das abas globalmente
window.mostrarAbaQualificacao = mostrarAbaQualificacao;
window.proximaAbaQualificacao = proximaAbaQualificacao;
window.abaAnteriorQualificacao = abaAnteriorQualificacao;
window.resetarFormularioQualificacao = resetarFormularioQualificacao;

// ==================== CONFIGURAÇÃO MODAL MODO DUPLO ====================

/**
 * Configurar modal para modo criação
 */
function configurarModalParaCriacao() {
    // Configurar título e ícone
    const titulo = document.getElementById('tituloModalQualificacao');
    if (titulo) {
        titulo.innerHTML = '<i data-lucide="plus-circle"></i> Criar Nova Qualificação';
    }
    
    // Configurar ação do formulário
    document.getElementById('acaoFormQualificacao').value = 'criar_qualificacao';
    document.getElementById('idQualificacao').value = '';
    
    // Configurar texto do botão
    const textoBotao = document.getElementById('textoBtn');
    if (textoBotao) {
        textoBotao.textContent = 'Criar Qualificação';
    }
    
    // Limpar PCA selecionado
    removerPcaSelecionadoCriacao();
    
    // Reinicializar ícones
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

/**
 * Configurar modal para modo edição
 */
function configurarModalParaEdicao(qualificacao) {
    // Configurar título e ícone
    const titulo = document.getElementById('tituloModalQualificacao');
    if (titulo) {
        titulo.innerHTML = '<i data-lucide="edit"></i> Editar Qualificação';
    }
    
    // Configurar ação do formulário
    document.getElementById('acaoFormQualificacao').value = 'editar_qualificacao';
    document.getElementById('idQualificacao').value = qualificacao.id;
    
    // Configurar texto do botão
    const textoBotao = document.getElementById('textoBtn');
    if (textoBotao) {
        textoBotao.textContent = 'Salvar Alterações';
    }
    
    // Preencher campos do formulário
    preencherCamposFormulario(qualificacao);
    
    // Reinicializar ícones
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

/**
 * Preencher campos do formulário com dados da qualificação
 */
function preencherCamposFormulario(qual) {
    // Aba 1: Informações Gerais
    document.querySelector('input[name="nup"]').value = qual.nup || '';
    document.querySelector('input[name="area_demandante"]').value = qual.area_demandante || '';
    document.querySelector('input[name="responsavel"]').value = qual.responsavel || '';
    document.querySelector('select[name="modalidade"]').value = qual.modalidade || '';
    document.querySelector('select[name="status"]').value = qual.status || '';
    
    // Aba 2: Detalhes do Objeto
    document.querySelector('textarea[name="objeto"]').value = qual.objeto || '';
    document.querySelector('input[name="palavras_chave"]').value = qual.palavras_chave || '';
    
    // Aba 4: Valores e Observações
    if (qual.valor_estimado) {
        const valorFormatado = parseFloat(qual.valor_estimado).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        document.querySelector('input[name="valor_estimado"]').value = valorFormatado;
    }
    
    document.querySelector('textarea[name="observacoes"]').value = qual.observacoes || '';
}

/**
 * Carregar dados do PCA vinculado durante a edição
 */
async function carregarPcaVinculadoEdicao(pcaId) {
    try {
        // Buscar dados do PCA específico
        const response = await fetch(`api/get_pca_data.php?id=${pcaId}`);
        if (!response.ok) {
            console.warn('Erro ao carregar dados do PCA vinculado');
            return;
        }
        
        const pcaData = await response.json();
        
        // Se é um array, pegar o primeiro item
        const pca = Array.isArray(pcaData) ? pcaData[0] : pcaData;
        
        if (pca && pca.id) {
            // Definir PCA selecionado
            pcaSelecionadoCriacao = pca.id;
            document.getElementById('pca_dados_id_criar').value = pca.id;
            
            // Mostrar informações do PCA vinculado
            const infoContainer = document.getElementById('info_pca_selecionado_criar');
            infoContainer.innerHTML = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px;">
                    <div>
                        <strong>Contratação:</strong><br>
                        <span style="color: #2c3e50;">${pca.numero_contratacao || 'N/A'}</span>
                    </div>
                    <div>
                        <strong>DFD:</strong><br>
                        <span style="color: #2c3e50;">${pca.numero_dfd || 'N/A'}</span>
                    </div>
                    <div>
                        <strong>Valor:</strong><br>
                        <span style="color: #28a745; font-weight: 600;">R$ ${formatarMoedaCriacao(pca.valor_total_contratacao)}</span>
                    </div>
                    <div>
                        <strong>Situação:</strong><br>
                        <span style="color: #6c757d;">${pca.situacao_execucao || 'N/A'}</span>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <strong>Área:</strong><br>
                        <span style="color: #2c3e50;">${pca.area_requisitante || 'N/A'}</span>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <strong>Título:</strong><br>
                        <span style="color: #495057; font-size: 12px; line-height: 1.4;">${pca.titulo_contratacao || 'N/A'}</span>
                    </div>
                </div>
            `;
            
            // Mostrar seção do PCA selecionado
            document.getElementById('pca_selecionado_criar').style.display = 'block';
            
            // Ocultar resultados da busca
            document.getElementById('resultado_busca_pca_criar').style.display = 'none';
            
            console.log('PCA vinculado carregado:', pca);
        }
        
    } catch (error) {
        console.error('Erro ao carregar PCA vinculado:', error);
    }
}

// ==================== SELETOR PCA PARA CRIAÇÃO ====================

// Variáveis para controle do seletor PCA na criação
let pcaDataCriacao = [];
let pcaSelecionadoCriacao = null;

/**
 * Buscar PCAs para vinculação durante a criação
 */
async function buscarPcaParaCriacao() {
    const termoBusca = document.getElementById('busca_pca_criar').value.trim();
    const container = document.getElementById('resultado_busca_pca_criar');
    
    try {
        // Se não há termo de busca, carregar todos os PCAs
        let url = 'api/get_pca_data.php';
        if (termoBusca) {
            url += '?busca=' + encodeURIComponent(termoBusca);
        }
        
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error('Erro ao carregar dados');
        }
        
        pcaDataCriacao = await response.json();
        exibirResultadosPcaCriacao();
        container.style.display = 'block';
        
    } catch (error) {
        console.error('Erro ao buscar PCAs:', error);
        container.innerHTML = '<div style="padding: 15px; text-align: center; color: #dc3545;">Erro ao carregar dados do PCA</div>';
        container.style.display = 'block';
    }
}

/**
 * Exibir resultados da busca de PCA para criação
 */
function exibirResultadosPcaCriacao() {
    const container = document.getElementById('resultado_busca_pca_criar');
    
    if (!pcaDataCriacao || pcaDataCriacao.length === 0) {
        container.innerHTML = '<div style="padding: 15px; text-align: center; color: #6c757d;">Nenhum resultado encontrado</div>';
        return;
    }
    
    let html = '';
    pcaDataCriacao.slice(0, 30).forEach(pca => {
        html += `
            <div class="pca-item" onclick="selecionarPcaCriacao(${pca.id}, this)" style="padding: 12px; border-bottom: 1px solid #e9ecef; cursor: pointer; transition: background-color 0.2s;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                    <strong style="color: #2c3e50;">Contratação: ${pca.numero_contratacao || 'N/A'}</strong>
                    <span style="color: #28a745; font-weight: 600;">R$ ${formatarMoedaCriacao(pca.valor_total_contratacao)}</span>
                </div>
                <div style="font-size: 13px; color: #6c757d; margin-bottom: 4px;">DFD: ${pca.numero_dfd || 'N/A'}</div>
                <div style="font-size: 13px; color: #6c757d; margin-bottom: 4px;">${pca.area_requisitante || 'Área não informada'}</div>
                <div style="font-size: 12px; color: #495057; line-height: 1.4;">${pca.titulo_contratacao ? pca.titulo_contratacao.substring(0, 80) + '...' : 'Título não informado'}</div>
                <div style="font-size: 11px; color: #6c757d; margin-top: 4px;">${pca.situacao_execucao || 'Situação não informada'}</div>
            </div>
        `;
    });
    
    if (pcaDataCriacao.length > 30) {
        html += '<div style="padding: 10px; text-align: center; color: #6c757d; font-size: 12px; background: #f8f9fa;">Mostrando 30 de ' + pcaDataCriacao.length + ' resultados. Use a busca para refinar.</div>';
    }
    
    container.innerHTML = html;
}

/**
 * Selecionar um PCA durante a criação
 */
function selecionarPcaCriacao(pcaId, elemento) {
    // Remover seleção anterior
    document.querySelectorAll('#resultado_busca_pca_criar .pca-item').forEach(item => {
        item.style.backgroundColor = '';
        item.style.borderLeft = '';
    });
    
    // Selecionar novo item
    elemento.style.backgroundColor = '#e3f2fd';
    elemento.style.borderLeft = '4px solid #2196f3';
    
    // Buscar dados completos do PCA selecionado
    const pcaSelecionado = pcaDataCriacao.find(pca => pca.id == pcaId);
    
    if (pcaSelecionado) {
        pcaSelecionadoCriacao = pcaId;
        
        // Preencher campo hidden
        document.getElementById('pca_dados_id_criar').value = pcaId;
        
        // Mostrar informações do PCA selecionado
        const infoContainer = document.getElementById('info_pca_selecionado_criar');
        infoContainer.innerHTML = `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px;">
                <div>
                    <strong>Contratação:</strong><br>
                    <span style="color: #2c3e50;">${pcaSelecionado.numero_contratacao}</span>
                </div>
                <div>
                    <strong>DFD:</strong><br>
                    <span style="color: #2c3e50;">${pcaSelecionado.numero_dfd}</span>
                </div>
                <div>
                    <strong>Valor:</strong><br>
                    <span style="color: #28a745; font-weight: 600;">R$ ${formatarMoedaCriacao(pcaSelecionado.valor_total_contratacao)}</span>
                </div>
                <div>
                    <strong>Situação:</strong><br>
                    <span style="color: #6c757d;">${pcaSelecionado.situacao_execucao}</span>
                </div>
                <div style="grid-column: 1 / -1;">
                    <strong>Área:</strong><br>
                    <span style="color: #2c3e50;">${pcaSelecionado.area_requisitante}</span>
                </div>
                <div style="grid-column: 1 / -1;">
                    <strong>Título:</strong><br>
                    <span style="color: #495057; font-size: 12px; line-height: 1.4;">${pcaSelecionado.titulo_contratacao}</span>
                </div>
            </div>
        `;
        
        // Mostrar seção do PCA selecionado
        document.getElementById('pca_selecionado_criar').style.display = 'block';
        
        // Ocultar resultados da busca
        document.getElementById('resultado_busca_pca_criar').style.display = 'none';
        
        // Limpar campo de busca
        document.getElementById('busca_pca_criar').value = '';
    }
}

/**
 * Remover PCA selecionado durante a criação
 */
function removerPcaSelecionadoCriacao() {
    pcaSelecionadoCriacao = null;
    document.getElementById('pca_dados_id_criar').value = '';
    document.getElementById('pca_selecionado_criar').style.display = 'none';
    document.getElementById('resultado_busca_pca_criar').style.display = 'none';
}

/**
 * Formatar moeda para o seletor de criação
 */
function formatarMoedaCriacao(valor) {
    if (!valor) return '0,00';
    return parseFloat(valor).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/**
 * Adicionar evento de Enter no campo de busca
 */
document.addEventListener('DOMContentLoaded', function() {
    const campoBusca = document.getElementById('busca_pca_criar');
    if (campoBusca) {
        campoBusca.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarPcaParaCriacao();
            }
        });
    }
});

// Disponibilizar funções globalmente
window.buscarPcaParaCriacao = buscarPcaParaCriacao;
window.selecionarPcaCriacao = selecionarPcaCriacao;
window.removerPcaSelecionadoCriacao = removerPcaSelecionadoCriacao;

console.log('📋 Qualificação Dashboard JavaScript carregado com sucesso!');