/**
 * Sistema Kanban - Tramitações CGLIC
 * Funcionalidades: Drag & Drop, Modais, Gestão de Cards
 */

// Debug - confirmar que o arquivo foi carregado
console.log('%c🚀 KANBAN.JS CARREGADO!', 'background: #00ff00; color: #000; padding: 5px; font-weight: bold;');

// IMPORTANTE: Não sobrescrever funções que já existem
console.log('🔍 Verificando funções existentes...');
if (typeof window.abrirModalNovaTramitacao === 'function') {
    console.log('✅ abrirModalNovaTramitacao já existe - não sobrescrever');
    // Salvar referência para a função inline que funciona
    window.abrirModalNovaTramitacaoOriginal = window.abrirModalNovaTramitacao;
}
if (typeof window.fecharModal === 'function') {
    console.log('✅ fecharModal já existe - não sobrescrever');
    window.fecharModalOriginal = window.fecharModal;
}

// Variáveis globais
let sortableInstances = [];
let draggedCard = null;

/**
 * Inicializar o sistema Kanban
 */
function initializeKanban() {
    console.log('🚀 Inicializando Sistema Kanban...');
    
    // Configurar drag and drop para todas as colunas
    setupSortable();
    
    // Configurar eventos dos modais
    setupModalEvents();
    
    // Configurar outros eventos
    setupOtherEvents();
    
    console.log('✅ Sistema Kanban inicializado com sucesso!');
}

/**
 * Configurar Sortable.js para drag and drop
 */
function setupSortable() {
    console.log('📋 Configurando Sortable.js...');
    
    // Verificar se Sortable está disponível
    if (typeof Sortable === 'undefined') {
        console.error('❌ Sortable.js não está carregado!');
        return;
    }
    
    const columns = document.querySelectorAll('.cards-container');
    console.log(`🔍 Encontradas ${columns.length} colunas para configurar`);
    
    if (columns.length === 0) {
        console.error('❌ Nenhuma coluna .cards-container encontrada!');
        return;
    }
    
    columns.forEach((column, index) => {
        console.log(`🔧 Configurando coluna ${index + 1}: ${column.id}`);
        
        // Verificar se já tem cards
        const cards = column.querySelectorAll('.kanban-card');
        console.log(`  📝 Cards encontrados: ${cards.length}`);
        cards.forEach((card, cardIndex) => {
            console.log(`    Card ${cardIndex + 1}: data-id="${card.dataset.id}"`);
        });
        const sortable = Sortable.create(column, {
            group: 'kanban-cards',
            animation: 200,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            filter: '.add-card-btn, .empty-column',
            preventOnFilter: false,
            draggable: '.kanban-card',
            
            onStart: function(evt) {
                console.log('🏃 Iniciando drag...', {
                    item: evt.item,
                    from: evt.from.id,
                    oldIndex: evt.oldIndex,
                    itemDataId: evt.item.dataset.id
                });
                draggedCard = evt.item;
                draggedCard.classList.add('dragging');
                
                // Adicionar classe drag-over nas colunas
                document.querySelectorAll('.kanban-column').forEach(col => {
                    col.addEventListener('dragenter', handleDragEnter);
                    col.addEventListener('dragleave', handleDragLeave);
                });
            },
            
            onMove: function(evt) {
                console.log('📍 Movendo card...', {
                    from: evt.from.id,
                    to: evt.to.id,
                    related: evt.related.className,
                    willInsertAfter: evt.willInsertAfter
                });
                
                // Não permitir mover para botões
                const isBlocked = evt.related.classList.contains('add-card-btn') || 
                                evt.related.classList.contains('empty-column');
                
                if (isBlocked) {
                    console.log('🚫 Movimento bloqueado para:', evt.related.className);
                }
                
                return !isBlocked;
            },
            
            onEnd: function(evt) {
                console.log('🎯 Finalizando drag...', {
                    from: evt.from.id,
                    to: evt.to.id,
                    oldIndex: evt.oldIndex,
                    newIndex: evt.newIndex,
                    item: evt.item,
                    changed: evt.from !== evt.to
                });
                
                if (draggedCard) {
                    draggedCard.classList.remove('dragging');
                }
                
                // Remover event listeners
                document.querySelectorAll('.kanban-column').forEach(col => {
                    col.removeEventListener('dragenter', handleDragEnter);
                    col.removeEventListener('dragleave', handleDragLeave);
                    col.classList.remove('drag-over');
                });
                
                // Atualizar contadores e estatísticas IMEDIATAMENTE após o movimento visual
                updateColumnCounters();
                updateTopStats();
                
                // Se mudou de coluna, atualizar no servidor
                if (evt.from !== evt.to) {
                    console.log('🔄 Card mudou de coluna - iniciando atualização...');
                    updateCardStatus(evt.item, evt.to);
                } else if (evt.oldIndex !== evt.newIndex) {
                    // Mudança de posição na mesma coluna
                    console.log('📍 Card mudou de posição na mesma coluna');
                    updateCardPosition(evt.item, evt.newIndex);
                } else {
                    console.log('ℹ️ Nenhuma mudança detectada');
                }
                
                draggedCard = null;
            }
        });
        
        sortableInstances.push(sortable);
        console.log(`  ✅ Coluna ${index + 1} configurada com sucesso`);
    });
    
    console.log(`✅ ${sortableInstances.length} colunas configuradas para drag & drop`);
    
    // Teste adicional - verificar se os cards são detectados como arrastáveis
    setTimeout(() => {
        const allCards = document.querySelectorAll('.kanban-card');
        console.log(`🔍 Teste pós-configuração: ${allCards.length} cards detectados`);
        allCards.forEach((card, index) => {
            const style = window.getComputedStyle(card);
            console.log(`  Card ${index + 1}: cursor=${style.cursor}, pointer-events=${style.pointerEvents}, draggable=${card.draggable}`);
            
            // Testar eventos de mouse
            card.addEventListener('mousedown', function(e) {
                console.log('🖱️ Mouse down no card:', card.dataset.id, e);
            }, { once: true });
            
            card.addEventListener('dragstart', function(e) {
                console.log('🚀 Drag start no card:', card.dataset.id, e);
            }, { once: true });
        });
        
        // Teste de funcionalidade do Sortable
        console.log('🧪 Testando instâncias do Sortable...');
        sortableInstances.forEach((instance, i) => {
            console.log(`  Instância ${i + 1}:`, {
                el: instance.el.id,
                options: instance.options,
                enabled: !instance.option('disabled')
            });
        });
        
        // Testar botões de ação nos cards
        console.log('🔘 Testando botões de ação nos cards...');
        const actionButtons = document.querySelectorAll('.card-actions button');
        console.log(`  Botões de ação encontrados: ${actionButtons.length}`);
        actionButtons.forEach((btn, index) => {
            console.log(`    Botão ${index + 1}: onclick="${btn.getAttribute('onclick')}", visible=${window.getComputedStyle(btn.parentElement).display !== 'none'}`);
        });
    }, 1000);
}

/**
 * Configurar eventos dos modais
 */
function setupModalEvents() {
    console.log('🪟 Configurando eventos de modais...');
    
    // Definir funções globais para modais APENAS se não existirem
    if (typeof window.abrirModalNovaTramitacao !== 'function') {
        console.log('🆕 Criando função abrirModalNovaTramitacao...');
        window.abrirModalNovaTramitacao = function(statusInicial = 'TODO') {
            console.log('📂 Abrindo modal Nova Tramitação...', { statusInicial });
            
            const modal = document.getElementById('modalNovaTramitacao');
            if (!modal) {
                console.error('❌ Modal não encontrado!');
                return;
            }
            
            // Definir status inicial se fornecido
            const statusSelect = modal.querySelector('select[name="status"]');
            if (statusSelect && statusInicial) {
                statusSelect.value = statusInicial;
                console.log(`✅ Status inicial definido: ${statusInicial}`);
            }
            
            // Mostrar modal
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            // Focar no primeiro campo
            const firstInput = modal.querySelector('select, input');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
            
            console.log('✅ Modal aberto com sucesso!');
        };
    } else {
        console.log('✅ Usando função abrirModalNovaTramitacao existente');
    }
    
    if (typeof window.fecharModal !== 'function') {
        console.log('🆕 Criando função fecharModal...');
        window.fecharModal = function(modalId) {
            console.log('🔐 Fechando modal:', modalId);
            
            const modal = document.getElementById(modalId);
            if (!modal) {
                console.error('❌ Modal não encontrado:', modalId);
                return;
            }
            
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Limpar formulário
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
                console.log('✅ Formulário resetado');
            }
            
            console.log('✅ Modal fechado!');
        };
    } else {
        console.log('✅ Usando função fecharModal existente');
    }
    
    window.abrirModalEditarTramitacao = function(id) {
        console.log('✏️ Abrindo modal Editar Tramitação:', id);
        
        const modal = document.getElementById('modalEditar');
        console.log('🔍 Modal editar encontrado:', modal);
        
        if (!modal) {
            console.error('❌ Modal de edição não encontrado!');
            alert('Erro: Modal de edição não encontrado no DOM!');
            return;
        }
        
        // FORÇAR estilos inline para garantir visibilidade
        modal.style.display = 'block';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.right = '0';
        modal.style.bottom = '0';
        modal.style.background = 'rgba(0, 0, 0, 0.8)';
        modal.style.zIndex = '99999';
        modal.style.backdropFilter = 'blur(5px)';
        
        // Forçar também no conteúdo interno
        const modalContent = modal.querySelector('.modal');
        if (modalContent) {
            modalContent.style.visibility = 'visible';
            modalContent.style.opacity = '1';
            modalContent.style.display = 'block';
            console.log('✅ Conteúdo do modal editar configurado');
        } else {
            console.error('❌ Conteúdo do modal editar (.modal) não encontrado!');
        }
        
        document.body.style.overflow = 'hidden';
        console.log('✅ Modal de edição exibido');
        
        // Definir ID da tramitação
        const idInput = document.getElementById('editTramitacaoId');
        if (idInput) {
            idInput.value = id;
            console.log('✅ ID da tramitação definido:', id);
        } else {
            console.error('❌ Campo editTramitacaoId não encontrado!');
        }
        
        // Carregar dados para edição
        carregarDadosEdicao(id);
    };
    
    window.abrirModalDetalhes = function(id) {
        console.log('👁️ Abrindo modal Detalhes:', id);
        
        const modal = document.getElementById('modalDetalhes');
        console.log('🔍 Modal encontrado:', modal);
        
        if (!modal) {
            console.error('❌ Modal de detalhes não encontrado!');
            alert('Erro: Modal de detalhes não encontrado no DOM!');
            return;
        }
        
        // FORÇAR estilos inline para garantir visibilidade
        modal.style.display = 'block';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.right = '0';
        modal.style.bottom = '0';
        modal.style.background = 'rgba(0, 0, 0, 0.8)';
        modal.style.zIndex = '99999';
        modal.style.backdropFilter = 'blur(5px)';
        
        // Forçar também no conteúdo interno
        const modalContent = modal.querySelector('.modal');
        if (modalContent) {
            modalContent.style.visibility = 'visible';
            modalContent.style.opacity = '1';
            modalContent.style.display = 'block';
            console.log('✅ Conteúdo do modal configurado');
        } else {
            console.error('❌ Conteúdo do modal (.modal) não encontrado!');
        }
        
        document.body.style.overflow = 'hidden';
        console.log('✅ Modal de detalhes exibido');
        
        // Carregar detalhes
        carregarDetalhes(id);
    };
    
    // Testar se as funções foram criadas corretamente
    console.log('🧪 Testando funções criadas:');
    console.log('  abrirModalDetalhes:', typeof window.abrirModalDetalhes);
    console.log('  abrirModalEditarTramitacao:', typeof window.abrirModalEditarTramitacao);
    console.log('  fecharModal:', typeof window.fecharModal);
    
    // Verificar se os modais existem no DOM
    console.log('🔍 Verificando modais no DOM:');
    console.log('  modalDetalhes:', document.getElementById('modalDetalhes') ? '✅' : '❌');
    console.log('  modalEditar:', document.getElementById('modalEditar') ? '✅' : '❌');
    console.log('  modalNovaTramitacao:', document.getElementById('modalNovaTramitacao') ? '✅' : '❌');
    
    console.log('✅ Funções de modal configuradas');
}

/**
 * Configurar outros eventos
 */
function setupOtherEvents() {
    console.log('⚙️ Configurando eventos adicionais...');
    
    // Event listeners para fechar modal
    document.addEventListener('click', function(e) {
        // Fechar modal ao clicar fora
        if (e.target.classList.contains('modal-overlay')) {
            const modalId = e.target.id;
            if (typeof fecharModal === 'function') {
                fecharModal(modalId);
            }
        }
        
        // Fechar modal com botão X
        if (e.target.closest('.modal-close')) {
            const modal = e.target.closest('.modal-overlay');
            if (modal && typeof fecharModal === 'function') {
                fecharModal(modal.id);
            }
        }
    });
    
    // Fechar modal com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modalsAbertos = document.querySelectorAll('.modal-overlay[style*="block"]');
            modalsAbertos.forEach(modal => {
                if (typeof fecharModal === 'function') {
                    fecharModal(modal.id);
                }
            });
        }
    });
    
    console.log('✅ Eventos adicionais configurados');
}

/**
 * Manipular entrada de drag nas colunas
 */
function handleDragEnter(e) {
    const column = e.currentTarget;
    if (column.classList.contains('kanban-column')) {
        column.classList.add('drag-over');
    }
}

/**
 * Manipular saída de drag das colunas
 */
function handleDragLeave(e) {
    const column = e.currentTarget;
    if (column.classList.contains('kanban-column')) {
        // Verificar se realmente saiu da coluna
        if (!column.contains(e.relatedTarget)) {
            column.classList.remove('drag-over');
        }
    }
}

/**
 * Atualizar status do card no servidor
 */
async function updateCardStatus(cardElement, newColumn) {
    const cardId = cardElement.dataset.id;
    
    // Corrigir: buscar data-status na coluna pai (.kanban-column)
    const columnElement = newColumn.closest('.kanban-column');
    const newStatus = columnElement ? columnElement.dataset.status : null;
    
    console.log('🔄 Atualizando status do card:', { 
        cardId, 
        newStatus, 
        newColumn: newColumn.id,
        columnElement: columnElement?.className 
    });
    
    if (!cardId || !newStatus) {
        console.error('❌ Dados insuficientes para atualizar card', {
            cardId: cardId || 'MISSING',
            newStatus: newStatus || 'MISSING',
            columnElement: columnElement?.className || 'NOT_FOUND'
        });
        return;
    }
    
    try {
        const response = await fetch('process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'atualizar_status_tramitacao',
                id: cardId,
                status: newStatus
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            console.log('✅ Status atualizado com sucesso!');
            showKanbanNotification('Status atualizado com sucesso!', 'success');
            
            // Atualizar contadores das colunas E estatísticas do topo
            updateColumnCounters();
            updateTopStats();
            console.log('🔢 Contadores e estatísticas atualizados!');
        } else {
            throw new Error(result.message || 'Erro desconhecido');
        }
        
    } catch (error) {
        console.error('❌ Erro ao atualizar status:', error);
        showKanbanNotification('Erro ao atualizar status do card!', 'error');
        
        // Reverter posição do card
        // TODO: Implementar lógica de reversão
    }
}

/**
 * Atualizar posição do card na mesma coluna
 */
async function updateCardPosition(cardElement, newPosition) {
    const cardId = cardElement.dataset.id;
    
    console.log('📍 Atualizando posição do card:', { cardId, newPosition });
    
    try {
        const response = await fetch('process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'atualizar_posicao_tramitacao',
                id: cardId,
                posicao: newPosition
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            console.log('✅ Posição atualizada com sucesso!');
        } else {
            console.warn('⚠️ Falha ao atualizar posição:', result.message);
        }
        
    } catch (error) {
        console.error('❌ Erro ao atualizar posição:', error);
    }
}

/**
 * Atualizar contadores das colunas
 */
function updateColumnCounters() {
    console.log('🔢 Atualizando contadores das colunas...');
    
    document.querySelectorAll('.kanban-column').forEach(column => {
        const status = column.dataset.status;
        const cardsContainer = column.querySelector('.cards-container');
        const cardsCount = cardsContainer ? cardsContainer.querySelectorAll('.kanban-card').length : 0;
        const counter = column.querySelector('.column-count');
        
        console.log(`  📊 Coluna ${status}: ${cardsCount} cards`);
        
        if (counter) {
            const oldCount = counter.textContent;
            counter.textContent = cardsCount;
            
            if (oldCount !== cardsCount.toString()) {
                // Animação visual de mudança
                counter.style.transition = 'all 0.3s ease';
                counter.style.transform = 'scale(1.2)';
                counter.style.color = '#3b82f6';
                
                setTimeout(() => {
                    counter.style.transform = 'scale(1)';
                    counter.style.color = '';
                }, 300);
            }
            
            console.log(`    ✅ Contador atualizado: ${oldCount} → ${cardsCount}`);
        } else {
            console.warn(`    ⚠️ Contador não encontrado para coluna ${status}`);
        }
    });
    
    console.log('✅ Todos os contadores atualizados!');
}

/**
 * Atualizar estatísticas do topo da página (cards TOTAL, A FAZER, etc.)
 */
function updateTopStats() {
    console.log('📈 Atualizando estatísticas do topo...');
    
    // Contar cards por status
    const todoCount = document.querySelectorAll('[data-status="TODO"] .kanban-card').length;
    const emProgressoCount = document.querySelectorAll('[data-status="EM_PROGRESSO"] .kanban-card').length;
    const aguardandoCount = document.querySelectorAll('[data-status="AGUARDANDO"] .kanban-card').length;
    const concluidoCount = document.querySelectorAll('[data-status="CONCLUIDO"] .kanban-card').length;
    const totalCount = todoCount + emProgressoCount + aguardandoCount + concluidoCount;
    
    // Contar cards atrasados (assumindo que têm classe 'atrasado' ou similar)
    const atrasadasCount = document.querySelectorAll('.kanban-card .card-prazo.atrasado').length;
    
    const stats = {
        total: totalCount,
        todo: todoCount,
        'em-progresso': emProgressoCount,
        aguardando: aguardandoCount,
        concluido: concluidoCount,
        atrasadas: atrasadasCount
    };
    
    console.log('📊 Novas estatísticas:', stats);
    
    // Atualizar cada card de estatística
    Object.entries(stats).forEach(([key, value]) => {
        const statCard = document.querySelector(`.stat-card.${key} .stat-number`);
        if (statCard) {
            const oldValue = statCard.textContent;
            statCard.textContent = value;
            
            if (oldValue !== value.toString()) {
                console.log(`  ✅ ${key}: ${oldValue} → ${value}`);
                
                // Animação visual de mudança
                animateStatChange(statCard);
            }
        } else {
            console.warn(`  ⚠️ Card de estatística não encontrado: ${key}`);
        }
    });
    
    console.log('✅ Estatísticas do topo atualizadas!');
}

/**
 * Animar mudança nos cards de estatística
 */
function animateStatChange(element) {
    // Animação mais visível para os cards grandes do topo
    element.style.transition = 'all 0.4s ease';
    element.style.transform = 'scale(1.3)';
    element.style.color = '#3b82f6';
    element.style.fontWeight = '700';
    
    // Efeito de "pulso"
    element.parentElement.style.transform = 'scale(1.05)';
    element.parentElement.style.transition = 'transform 0.4s ease';
    element.parentElement.style.boxShadow = '0 8px 25px rgba(59, 130, 246, 0.2)';
    
    setTimeout(() => {
        element.style.transform = 'scale(1)';
        element.style.color = '';
        element.style.fontWeight = '';
        
        element.parentElement.style.transform = 'scale(1)';
        element.parentElement.style.boxShadow = '';
    }, 400);
}

/**
 * Mostrar notificação (local do Kanban)
 */
function showKanbanNotification(message, type = 'info') {
    console.log(`📢 Notificação Kanban [${type}]:`, message);
    
    // Verificar se existe sistema de notificações global
    if (typeof window.showNotification === 'function') {
        // Usar sistema de notificações global se disponível
        window.showNotification(message, type);
        return;
    }
    
    // Fallback: alert simples
    if (type === 'error') {
        alert('Erro: ' + message);
    } else if (type === 'success') {
        console.log('✅ ' + message);
        // Não mostrar alert para sucesso, apenas log
    } else {
        alert(message);
    }
}

/**
 * Aplicar template à nova tramitação
 */
async function aplicarTemplate(templateId) {
    if (!templateId) {
        console.log('🗑️ Limpando template...');
        // Limpar campos do formulário
        const form = document.querySelector('#modalNovaTramitacao form');
        if (form) {
            // Manter apenas módulos e status, limpar outros campos
            const camposParaLimpar = ['tipo_demanda', 'titulo', 'descricao', 'tags', 'observacoes'];
            camposParaLimpar.forEach(campo => {
                const input = form.querySelector(`[name="${campo}"]`);
                if (input) input.value = '';
            });
        }
        return;
    }
    
    console.log('📋 Aplicando template:', templateId);
    
    try {
        const response = await fetch(`api/get_template.php?id=${templateId}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('📄 Resposta da API:', result);
        
        if (!result.success) {
            throw new Error(result.message || 'Erro ao buscar template');
        }
        
        const template = result.data;
        console.log('📋 Template carregado:', template);
        
        // Preencher campos do formulário
        const form = document.querySelector('#modalNovaTramitacao form');
        if (form) {
            // Preencher campos se existirem no template
            if (template.modulo_origem) {
                const select = form.querySelector('[name="modulo_origem"]');
                if (select) select.value = template.modulo_origem;
            }
            
            if (template.modulo_destino) {
                const select = form.querySelector('[name="modulo_destino"]');
                if (select) select.value = template.modulo_destino;
            }
            
            if (template.tipo_demanda) {
                const input = form.querySelector('[name="tipo_demanda"]');
                if (input) input.value = template.tipo_demanda;
            }
            
            if (template.titulo) {
                const input = form.querySelector('[name="titulo"]');
                if (input) input.value = template.titulo;
            }
            
            if (template.descricao) {
                const input = form.querySelector('[name="descricao"]');
                if (input) input.value = template.descricao;
            }
            
            if (template.tags) {
                const input = form.querySelector('[name="tags"]');
                if (input) input.value = template.tags;
            }
            
            if (template.prioridade) {
                const select = form.querySelector('[name="prioridade"]');
                if (select) select.value = template.prioridade;
            }
            
            if (template.cor_card) {
                const input = form.querySelector('[name="cor_card"]');
                if (input) input.value = template.cor_card;
            }
        }
        
        showKanbanNotification(`Template "${template.nome}" aplicado com sucesso!`, 'success');
        
    } catch (error) {
        console.error('❌ Erro ao aplicar template:', error);
        showKanbanNotification(`Erro ao aplicar template: ${error.message}`, 'error');
    }
}

/**
 * Carregar detalhes da tramitação
 */
async function carregarDetalhes(id) {
    console.log('📋 Carregando detalhes da tramitação:', id);
    
    const content = document.getElementById('detalhesContent');
    if (!content) return;
    
    try {
        const response = await fetch(`api/get_tramitacao_detalhes.php?id=${id}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Erro desconhecido');
        }
        
        const data = result.data;
        
        // Gerar HTML dos detalhes
        const html = `
            <div style="display: grid; gap: 20px;">
                <!-- Informações Básicas -->
                <div style="background: #f8fafc; border-radius: 12px; padding: 20px;">
                    <h3 style="margin: 0 0 16px 0; color: #1f2937; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="info"></i>
                        Informações Básicas
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                        <div>
                            <label style="font-weight: 600; color: #374151; font-size: 14px;">Número:</label>
                            <p style="margin: 4px 0 0 0; color: #6b7280;">#${data.numero_tramite || data.id}</p>
                        </div>
                        <div>
                            <label style="font-weight: 600; color: #374151; font-size: 14px;">Status:</label>
                            <p style="margin: 4px 0 0 0;">
                                <span style="padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; background: #e5e7eb; color: #374151;">
                                    ${data.status === 'TODO' ? 'A Fazer' : 
                                      data.status === 'EM_PROGRESSO' ? 'Em Progresso' :
                                      data.status === 'AGUARDANDO' ? 'Aguardando' : 'Concluído'}
                                </span>
                            </p>
                        </div>
                        <div>
                            <label style="font-weight: 600; color: #374151; font-size: 14px;">Prioridade:</label>
                            <p style="margin: 4px 0 0 0;">
                                <span style="padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; background: #fef3c7; color: #d97706;">
                                    ${data.prioridade?.charAt(0).toUpperCase() + data.prioridade?.slice(1).toLowerCase()}
                                </span>
                            </p>
                        </div>
                        <div>
                            <label style="font-weight: 600; color: #374151; font-size: 14px;">Tipo de Demanda:</label>
                            <p style="margin: 4px 0 0 0; color: #6b7280;">${data.tipo_demanda || 'Não informado'}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Título e Descrição -->
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
                    <h3 style="margin: 0 0 16px 0; color: #1f2937; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="file-text"></i>
                        Título e Descrição
                    </h3>
                    <div>
                        <label style="font-weight: 600; color: #374151; font-size: 14px;">Título:</label>
                        <p style="margin: 4px 0 16px 0; font-size: 16px; color: #1f2937;">${data.titulo || 'Sem título'}</p>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #374151; font-size: 14px;">Descrição:</label>
                        <p style="margin: 4px 0 0 0; color: #6b7280; line-height: 1.5;">${data.descricao || 'Nenhuma descrição fornecida'}</p>
                    </div>
                </div>
                
                <!-- Módulos e Responsável -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div style="background: #fef3c7; border-radius: 12px; padding: 20px;">
                        <h3 style="margin: 0 0 16px 0; color: #d97706; display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="arrow-right"></i>
                            Tramitação
                        </h3>
                        <div style="margin-bottom: 12px;">
                            <label style="font-weight: 600; color: #92400e; font-size: 14px;">De:</label>
                            <p style="margin: 4px 0 0 0; color: #d97706;">${data.modulo_origem}</p>
                        </div>
                        <div>
                            <label style="font-weight: 600; color: #92400e; font-size: 14px;">Para:</label>
                            <p style="margin: 4px 0 0 0; color: #d97706;">${data.modulo_destino}</p>
                        </div>
                    </div>
                    
                    <div style="background: #dbeafe; border-radius: 12px; padding: 20px;">
                        <h3 style="margin: 0 0 16px 0; color: #1d4ed8; display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="user"></i>
                            Responsável
                        </h3>
                        <div>
                            <label style="font-weight: 600; color: #1e40af; font-size: 14px;">Responsável:</label>
                            <p style="margin: 4px 0 0 0; color: #1d4ed8;">${data.responsavel_nome || 'Sem responsável'}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Observações se existirem -->
                ${data.observacoes ? `
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
                    <h3 style="margin: 0 0 16px 0; color: #1f2937; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="message-square"></i>
                        Observações
                    </h3>
                    <p style="margin: 0; color: #6b7280; line-height: 1.5;">${data.observacoes}</p>
                </div>
                ` : ''}
            </div>
        `;
        
        content.innerHTML = html;
        
        // Atualizar ícones Lucide
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        console.log('✅ Detalhes carregados com sucesso!');
        
    } catch (error) {
        console.error('❌ Erro ao carregar detalhes:', error);
        content.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #ef4444;">
                <i data-lucide="alert-circle" size="48"></i>
                <h3>Erro ao carregar detalhes</h3>
                <p>${error.message}</p>
                <button onclick="carregarDetalhes(${id})" style="margin-top: 16px; padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    Tentar Novamente
                </button>
            </div>
        `;
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

/**
 * Carregar dados para edição
 */
async function carregarDadosEdicao(id) {
    console.log('✏️ Carregando dados para edição:', id);
    
    const content = document.getElementById('editarContent');
    if (!content) return;
    
    try {
        const response = await fetch(`api/get_tramitacao_detalhes.php?id=${id}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Erro desconhecido');
        }
        
        const data = result.data;
        
        // Gerar formulário de edição
        const html = `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="font-weight: 600; color: #374151; font-size: 14px;">Módulo Origem</label>
                    <select name="modulo_origem" required style="padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                        <option value="PLANEJAMENTO" ${data.modulo_origem === 'PLANEJAMENTO' ? 'selected' : ''}>Planejamento</option>
                        <option value="LICITACAO" ${data.modulo_origem === 'LICITACAO' ? 'selected' : ''}>Licitação</option>
                        <option value="QUALIFICACAO" ${data.modulo_origem === 'QUALIFICACAO' ? 'selected' : ''}>Qualificação</option>
                        <option value="CONTRATOS" ${data.modulo_origem === 'CONTRATOS' ? 'selected' : ''}>Contratos</option>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="font-weight: 600; color: #374151; font-size: 14px;">Módulo Destino</label>
                    <select name="modulo_destino" required style="padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                        <option value="PLANEJAMENTO" ${data.modulo_destino === 'PLANEJAMENTO' ? 'selected' : ''}>Planejamento</option>
                        <option value="LICITACAO" ${data.modulo_destino === 'LICITACAO' ? 'selected' : ''}>Licitação</option>
                        <option value="QUALIFICACAO" ${data.modulo_destino === 'QUALIFICACAO' ? 'selected' : ''}>Qualificação</option>
                        <option value="CONTRATOS" ${data.modulo_destino === 'CONTRATOS' ? 'selected' : ''}>Contratos</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="font-weight: 600; color: #374151; font-size: 14px;">Prioridade</label>
                    <select name="prioridade" style="padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                        <option value="BAIXA" ${data.prioridade === 'BAIXA' ? 'selected' : ''}>Baixa</option>
                        <option value="MEDIA" ${data.prioridade === 'MEDIA' ? 'selected' : ''}>Média</option>
                        <option value="ALTA" ${data.prioridade === 'ALTA' ? 'selected' : ''}>Alta</option>
                        <option value="URGENTE" ${data.prioridade === 'URGENTE' ? 'selected' : ''}>Urgente</option>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="font-weight: 600; color: #374151; font-size: 14px;">Status</label>
                    <select name="status" style="padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                        <option value="TODO" ${data.status === 'TODO' ? 'selected' : ''}>A Fazer</option>
                        <option value="EM_PROGRESSO" ${data.status === 'EM_PROGRESSO' ? 'selected' : ''}>Em Progresso</option>
                        <option value="AGUARDANDO" ${data.status === 'AGUARDANDO' ? 'selected' : ''}>Aguardando</option>
                        <option value="CONCLUIDO" ${data.status === 'CONCLUIDO' ? 'selected' : ''}>Concluído</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                <label style="font-weight: 600; color: #374151; font-size: 14px;">Tipo de Demanda</label>
                <input type="text" name="tipo_demanda" value="${data.tipo_demanda || ''}" placeholder="Ex: Análise Técnica, Elaboração de Edital..." required style="padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                <label style="font-weight: 600; color: #374151; font-size: 14px;">Título</label>
                <input type="text" name="titulo" value="${data.titulo || ''}" placeholder="Título resumido da tramitação" required style="padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                <label style="font-weight: 600; color: #374151; font-size: 14px;">Descrição</label>
                <textarea name="descricao" placeholder="Descreva detalhadamente a demanda..." required style="min-height: 100px; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; resize: vertical; font-size: 14px;">${data.descricao || ''}</textarea>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="font-weight: 600; color: #374151; font-size: 14px;">Tags (separadas por vírgula)</label>
                    <input type="text" name="tags" value="${data.tags || ''}" placeholder="analise-tecnica, urgente, pca" style="padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="font-weight: 600; color: #374151; font-size: 14px;">Cor do Card</label>
                    <input type="color" name="cor_card" value="${data.cor_card || '#3b82f6'}" style="height: 48px; padding: 4px; border: 1px solid #d1d5db; border-radius: 8px; cursor: pointer;">
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                <label style="font-weight: 600; color: #374151; font-size: 14px;">Observações</label>
                <textarea name="observacoes" placeholder="Observações adicionais (opcional)" style="min-height: 80px; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; resize: vertical; font-size: 14px;">${data.observacoes || ''}</textarea>
            </div>
        `;
        
        content.innerHTML = html;
        console.log('✅ Dados de edição carregados com sucesso!');
        
    } catch (error) {
        console.error('❌ Erro ao carregar dados para edição:', error);
        content.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #ef4444;">
                <i data-lucide="alert-circle" size="48"></i>
                <h3>Erro ao carregar dados</h3>
                <p>${error.message}</p>
                <button onclick="carregarDadosEdicao(${id})" style="margin-top: 16px; padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    Tentar Novamente
                </button>
            </div>
        `;
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

/**
 * Funções utilitárias para debugging
 */
window.debugKanban = {
    getSortableInstances: () => sortableInstances,
    getDraggedCard: () => draggedCard,
    testModal: () => abrirModalNovaTramitacao('TODO'),
    updateCounters: updateColumnCounters
};

// Expor funções globalmente para compatibilidade
window.initializeKanban = initializeKanban;
window.aplicarTemplate = aplicarTemplate;

console.log('📚 Sistema Kanban carregado e pronto para inicialização!');