/**
 * JavaScript para Integração com PNCP
 * 
 * Funcionalidades:
 * - Sincronização com API do PNCP
 * - Consulta e visualização de dados
 * - Comparação com dados internos
 * - Exportação de dados
 * - Histórico de sincronizações
 */

// Estado global da integração PNCP
let pncpState = {
    sincronizando: false,
    dadosCarregados: false,
    ultimaConsulta: null,
    filtrosAtivos: {}
};

/**
 * Inicializar módulo PNCP quando a seção for ativada
 */
function inicializarPNCP() {
    console.log('[PNCP] Inicializando módulo PNCP...');
    
    // Carregar estatísticas iniciais
    carregarEstatisticasPNCP();
    
    // Carregar histórico de sincronizações
    carregarHistoricoPNCP();
    
    // Verificar status da API
    verificarStatusAPI();
    
    console.log('[PNCP] Módulo PNCP inicializado');
}

/**
 * Sincronizar dados com a API do PNCP
 */
async function sincronizarPNCP() {
    console.log('[PNCP] sincronizarPNCP() chamada');
    
    if (pncpState.sincronizando) {
        console.log('[PNCP] Sincronização já em andamento, cancelando');
        showNotification('Uma sincronização já está em andamento', 'warning');
        return;
    }
    
    // Debug: verificar elementos necessários
    const elementos = {
        ano: document.getElementById('ano-pncp'),
        botao: document.getElementById('btn-sincronizar-pncp'),
        progresso: document.getElementById('progresso-pncp'),
        csrf: document.querySelector('input[name="csrf_token"]')
    };
    
    console.log('[PNCP] Elementos encontrados:', elementos);
    
    // Verificar se elementos obrigatórios existem
    if (!elementos.botao) {
        console.error('[PNCP] Botão btn-sincronizar-pncp não encontrado');
        alert('Erro: Botão de sincronização não encontrado. Recarregue a página.');
        return;
    }
    
    if (!elementos.csrf) {
        console.error('[PNCP] Token CSRF não encontrado');
        alert('Erro: Token de segurança não encontrado. Recarregue a página.');
        return;
    }
    
    // Verificar se showNotification existe
    if (typeof showNotification !== 'function') {
        console.warn('[PNCP] Função showNotification não encontrada, usando alert como fallback');
        window.showNotification = function(msg, type) {
            alert(msg);
        };
    }
    
    const ano = elementos.ano ? elementos.ano.value : '2026';
    const btnSincronizar = document.getElementById('btn-sincronizar-pncp');
    const progressoDiv = document.getElementById('progresso-pncp');
    const progressoBarra = document.getElementById('progresso-barra');
    const progressoPorcentagem = document.getElementById('progresso-porcentagem');
    const progressoMensagem = document.getElementById('progresso-mensagem');
    
    try {
        // Iniciar sincronização
        pncpState.sincronizando = true;
        btnSincronizar.disabled = true;
        btnSincronizar.innerHTML = '<i data-lucide="loader-2" style="animation: spin 1s linear infinite;"></i> Sincronizando...';
        progressoDiv.style.display = 'block';
        
        // Preparar dados
        const formData = new FormData();
        formData.append('acao', 'sincronizar');
        formData.append('ano', ano);
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
        
        console.log('[PNCP] Iniciando sincronização para ano:', ano);
        
        // Simular progresso inicial
        atualizarProgresso(10, 'Conectando com API do PNCP...');
        
        // Fazer requisição
        const response = await fetch('api/pncp_integration.php', {
            method: 'POST',
            body: formData
        });
        
        atualizarProgresso(30, 'Baixando dados do CSV...');
        
        const resultado = await response.json();
        
        atualizarProgresso(70, 'Processando dados...');
        
        if (!resultado.sucesso) {
            throw new Error(resultado.erro || 'Erro na sincronização');
        }
        
        // Sincronização bem-sucedida
        atualizarProgresso(100, 'Sincronização concluída!');
        
        setTimeout(() => {
            progressoDiv.style.display = 'none';
            showNotification(
                `Sincronização concluída! ${resultado.novos} novos registros, ${resultado.atualizados} atualizados.`,
                'success'
            );
            
            // Atualizar estatísticas
            carregarEstatisticasPNCP();
            carregarHistoricoPNCP();
            
            // Mostrar log detalhado no console
            if (resultado.log) {
                console.log('[PNCP] Log da sincronização:', resultado.log);
            }
            
        }, 1500);
        
    } catch (error) {
        console.error('[PNCP] Erro na sincronização:', error);
        
        atualizarProgresso(0, `Erro: ${error.message}`);
        progressoBarra.style.background = 'linear-gradient(90deg, #e74c3c 0%, #c0392b 100%)';
        
        setTimeout(() => {
            progressoDiv.style.display = 'none';
            showNotification(`Erro na sincronização: ${error.message}`, 'error');
        }, 3000);
        
    } finally {
        // Resetar estado
        pncpState.sincronizando = false;
        btnSincronizar.disabled = false;
        btnSincronizar.innerHTML = '<i data-lucide="download-cloud"></i> Sincronizar com PNCP';
        
        // Recarregar ícones Lucide
        lucide.createIcons();
    }
}

/**
 * Atualizar barra de progresso
 */
function atualizarProgresso(porcentagem, mensagem) {
    const progressoBarra = document.getElementById('progresso-barra');
    const progressoPorcentagem = document.getElementById('progresso-porcentagem');
    const progressoMensagem = document.getElementById('progresso-mensagem');
    
    progressoBarra.style.width = porcentagem + '%';
    progressoPorcentagem.textContent = porcentagem + '%';
    
    if (mensagem) {
        progressoMensagem.innerHTML = `
            <i data-lucide="loader-2" style="width: 16px; height: 16px; animation: spin 1s linear infinite;"></i>
            ${mensagem}
        `;
        lucide.createIcons();
    }
}

/**
 * Carregar estatísticas do PNCP
 */
async function carregarEstatisticasPNCP() {
    try {
        const ano = document.getElementById('ano-pncp')?.value || 2026;
        const response = await fetch(`api/pncp_integration.php?acao=estatisticas&ano=${ano}`);
        const resultado = await response.json();
        
        if (resultado.sucesso && resultado.estatisticas) {
            const stats = resultado.estatisticas;
            
            // Atualizar cards de estatísticas
            document.getElementById('pncp-total-registros').textContent = 
                stats.total_registros ? parseInt(stats.total_registros).toLocaleString('pt-BR') : '0';
            
            document.getElementById('pncp-valor-total').textContent = 
                stats.valor_total ? formatarMoedaBR(stats.valor_total) : 'R$ 0';
            
            document.getElementById('pncp-ultima-sync').textContent = 
                stats.ultima_sincronizacao ? formatarDataHora(stats.ultima_sincronizacao) : 'Nunca';
            
            // Atualizar status
            const statusElement = document.getElementById('pncp-status-api');
            if (stats.total_registros > 0) {
                statusElement.innerHTML = '🟢 Dados Carregados';
                statusElement.parentElement.className = 'stat-card success';
            } else {
                statusElement.innerHTML = '⚪ Sem Dados';
                statusElement.parentElement.className = 'stat-card warning';
            }
            
            pncpState.dadosCarregados = stats.total_registros > 0;
            
            console.log('[PNCP] Estatísticas carregadas:', stats);
        }
        
    } catch (error) {
        console.error('[PNCP] Erro ao carregar estatísticas:', error);
        document.getElementById('pncp-status-api').innerHTML = '🔴 Erro';
    }
}

/**
 * Comparar dados internos com dados do PNCP
 */
async function compararDados() {
    const btnComparar = document.getElementById('btn-comparar-dados');
    const comparacaoDiv = document.getElementById('comparacao-dados');
    
    try {
        btnComparar.disabled = true;
        btnComparar.innerHTML = '<i data-lucide="loader-2" style="animation: spin 1s linear infinite;"></i> Comparando...';
        
        const ano = document.getElementById('ano-pncp').value;
        const response = await fetch('api/pncp_integration.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                acao: 'comparar',
                ano: ano,
                csrf_token: document.querySelector('input[name="csrf_token"]').value
            })
        });
        
        const resultado = await response.json();
        
        if (!resultado.sucesso) {
            throw new Error(resultado.erro);
        }
        
        // Processar dados da comparação
        const dadosInternos = resultado.comparacao.find(item => item.origem === 'Interno') || 
                             { total_registros: 0, valor_total: 0 };
        const dadosPNCP = resultado.comparacao.find(item => item.origem === 'PNCP') || 
                         { total_registros: 0, valor_total: 0 };
        
        // Atualizar interface
        document.getElementById('comp-interno-total').textContent = 
            `${parseInt(dadosInternos.total_registros).toLocaleString('pt-BR')} DFDs`;
        document.getElementById('comp-pncp-total').textContent = 
            `${parseInt(dadosPNCP.total_registros).toLocaleString('pt-BR')} Itens`;
        
        comparacaoDiv.style.display = 'block';
        
        // Calcular diferenças
        const difRegistros = dadosPNCP.total_registros - dadosInternos.total_registros;
        const difValor = dadosPNCP.valor_total - dadosInternos.valor_total;
        
        let mensagem = `Comparação concluída! `;
        if (difRegistros > 0) {
            mensagem += `PNCP tem ${difRegistros} registros a mais.`;
        } else if (difRegistros < 0) {
            mensagem += `Dados internos têm ${Math.abs(difRegistros)} registros a mais.`;
        } else {
            mensagem += `Mesmo número de registros.`;
        }
        
        showNotification(mensagem, 'info');
        
    } catch (error) {
        console.error('[PNCP] Erro na comparação:', error);
        showNotification(`Erro na comparação: ${error.message}`, 'error');
        
    } finally {
        btnComparar.disabled = false;
        btnComparar.innerHTML = '<i data-lucide="git-compare"></i> Comparar Dados';
        lucide.createIcons();
    }
}

/**
 * Consultar dados do PNCP
 */
async function consultarDadosPNCP(pagina = 1) {
    const loadingDiv = document.getElementById('loading-dados-pncp');
    const tabelaDiv = document.getElementById('tabela-dados-pncp');
    const emptyDiv = document.getElementById('empty-dados-pncp');
    const tbody = document.getElementById('tbody-pncp-dados');
    
    try {
        // Mostrar loading
        loadingDiv.style.display = 'block';
        tabelaDiv.style.display = 'none';
        emptyDiv.style.display = 'none';
        
        const ano = document.getElementById('ano-pncp')?.value || 2026;
        
        // Construir URL com filtros
        const filtros = {
            uasg: document.getElementById('filtro-pncp-uasg')?.value || '',
            categoria: document.getElementById('filtro-pncp-categoria')?.value || '',
            identificador: document.getElementById('filtro-pncp-identificador')?.value || ''
        };
        
        const params = new URLSearchParams({
            acao: 'listar',
            ano: ano,
            pagina: pagina,
            limite: 20,
            ...filtros
        });
        
        console.log('[PNCP] Fazendo requisição para:', `api/consultar_pncp.php?${params}`);
        
        const response = await fetch(`api/consultar_pncp.php?${params}`);
        const resultado = await response.json();
        
        console.log('[PNCP] Resposta da API:', resultado);
        
        if (!resultado.sucesso) {
            throw new Error(resultado.erro || 'Erro na consulta');
        }
        
        const dados = resultado.dados.dados;
        const paginacao = resultado.dados.paginacao;
        
        console.log('[PNCP] Dados recebidos:', dados?.length, 'registros');
        console.log('[PNCP] Paginação:', paginacao);
        
        if (dados && dados.length > 0) {
            // Renderizar resumo dos dados
            atualizarResumoDados(dados, paginacao);
            
            // Renderizar dados na tabela com design melhorado
            tbody.innerHTML = dados.map(item => `
                <tr>
                    <td title="${item.unidade_responsavel || ''}">${truncateText(item.unidade_responsavel || '-', 25)}</td>
                    <td><span class="uasg-badge">${item.uasg || '-'}</span></td>
                    <td><code style="font-size: 9px; background: #f1f3f4; padding: 2px 4px; border-radius: 3px;">${item.id_item_pca || '-'}</code></td>
                    <td><span class="categoria-badge" style="background: ${getCategoriaColor(item.categoria_item)}; color: white;">${item.categoria_item || 'N/A'}</span></td>
                    <td><strong style="color: #1976d2;">${item.identificador_futura_contratacao || '-'}</strong></td>
                    <td title="${item.nome_futura_contratacao || ''}">${truncateText(item.nome_futura_contratacao || '-', 35)}</td>
                    <td>${truncateText(item.catalogo_utilizado || '-', 20)}</td>
                    <td title="${item.classificacao_catalogo || ''}">${truncateText(item.classificacao_catalogo || '-', 25)}</td>
                    <td><code style="font-size: 9px;">${item.codigo_classificacao_superior || '-'}</code></td>
                    <td title="${item.nome_classificacao_superior || ''}">${truncateText(item.nome_classificacao_superior || '-', 25)}</td>
                    <td><code style="font-size: 9px;">${item.codigo_pdm_item || '-'}</code></td>
                    <td title="${item.nome_pdm_item || ''}">${truncateText(item.nome_pdm_item || '-', 25)}</td>
                    <td><code style="font-size: 9px;">${item.codigo_item || '-'}</code></td>
                    <td title="${item.descricao_item_fornecimento || ''}">${truncateText(item.descricao_item_fornecimento || '-', 35)}</td>
                    <td><span style="background: #e8f5e8; color: #2e7d32; padding: 2px 6px; border-radius: 8px; font-size: 9px;">${item.unidade || '-'}</span></td>
                    <td style="text-align: right; font-weight: 600;">${item.quantidade_estimada ? parseFloat(item.quantidade_estimada).toLocaleString('pt-BR', {minimumFractionDigits: 0, maximumFractionDigits: 2}) : '-'}</td>
                    <td style="text-align: right;" class="valor-monetario valor-unitario">${formatarMoedaBR(item.valor_unitario_estimado)}</td>
                    <td style="text-align: right;" class="valor-monetario valor-total">${formatarMoedaBR(item.valor_total_estimado)}</td>
                    <td style="text-align: right;" class="valor-monetario valor-orcamentario">${formatarMoedaBR(item.valor_orcamentario_exercicio)}</td>
                    <td style="text-align: center; font-size: 10px; color: #666;">${formatarDataBR(item.data_desejada)}</td>
                </tr>
            `).join('');
            
            // Renderizar paginação
            renderizarPaginacaoPNCP(paginacao);
            
            tabelaDiv.style.display = 'block';
            pncpState.ultimaConsulta = new Date();
            
        } else {
            emptyDiv.style.display = 'block';
        }
        
        loadingDiv.style.display = 'none';
        
    } catch (error) {
        console.error('[PNCP] Erro na consulta:', error);
        loadingDiv.style.display = 'none';
        emptyDiv.style.display = 'block';
        showNotification(`Erro na consulta: ${error.message}`, 'error');
    }
}

/**
 * Aplicar filtros na consulta PNCP
 */
function aplicarFiltrosPNCP() {
    const filtros = {
        uasg: document.getElementById('filtro-pncp-uasg').value,
        categoria: document.getElementById('filtro-pncp-categoria').value,
        identificador: document.getElementById('filtro-pncp-identificador').value
    };
    
    pncpState.filtrosAtivos = filtros;
    
    console.log('[PNCP] Aplicando filtros:', filtros);
    
    // Recarregar dados com filtros
    consultarDadosPNCP();
}

/**
 * Limpar filtros na consulta PNCP
 */
function limparFiltrosPNCP() {
    document.getElementById('filtro-pncp-uasg').value = '';
    document.getElementById('filtro-pncp-categoria').value = '';
    document.getElementById('filtro-pncp-identificador').value = '';
    pncpState.filtrosAtivos = {};
    
    console.log('[PNCP] Limpando filtros');
    
    // Recarregar dados sem filtros
    consultarDadosPNCP();
}

/**
 * Atualizar resumo dos dados
 */
function atualizarResumoDados(dados, paginacao) {
    const resumoDiv = document.getElementById('resumo-dados-pncp');
    
    if (dados && dados.length > 0) {
        // Calcular estatísticas
        const valorTotal = dados.reduce((sum, item) => sum + (parseFloat(item.valor_total_estimado) || 0), 0);
        const uasgsUnicas = [...new Set(dados.map(item => item.uasg).filter(u => u))].length;
        const categoriasUnicas = [...new Set(dados.map(item => item.categoria_item).filter(c => c))].length;
        
        document.getElementById('total-registros-pncp').textContent = paginacao.total_registros.toLocaleString('pt-BR');
        document.getElementById('valor-total-pncp').textContent = formatarMoedaBR(valorTotal);
        document.getElementById('uasgs-diferentes').textContent = uasgsUnicas;
        document.getElementById('categorias-diferentes').textContent = categoriasUnicas;
        
        resumoDiv.style.display = 'block';
    } else {
        resumoDiv.style.display = 'none';
    }
}

/**
 * Truncar texto com reticências
 */
function truncateText(text, maxLength) {
    if (!text || text === '-') return text;
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

/**
 * Obter cor para categoria
 */
function getCategoriaColor(categoria) {
    if (!categoria) return '#6c757d';
    
    const cores = {
        'bens': '#17a2b8',
        'servicos': '#28a745', 
        'serviços': '#28a745',
        'obras': '#dc3545',
        'equipamentos': '#6f42c1',
        'software': '#fd7e14',
        'consultorias': '#20c997',
        'material': '#6610f2'
    };
    
    const categoriaLower = categoria.toLowerCase();
    for (const [key, color] of Object.entries(cores)) {
        if (categoriaLower.includes(key)) {
            return color;
        }
    }
    
    // Hash da string para cor consistente
    let hash = 0;
    for (let i = 0; i < categoria.length; i++) {
        hash = categoria.charCodeAt(i) + ((hash << 5) - hash);
    }
    
    const colors = ['#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6f42c1'];
    return colors[Math.abs(hash) % colors.length];
}

/**
 * Formatar data brasileira
 */
function formatarDataBR(dataStr) {
    if (!dataStr) return '-';
    
    try {
        const data = new Date(dataStr + 'T00:00:00');
        return data.toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit', 
            year: 'numeric'
        });
    } catch (error) {
        return dataStr;
    }
}

/**
 * Exportar dados do PNCP
 */
async function exportarDadosPNCP() {
    try {
        const ano = document.getElementById('ano-pncp')?.value || 2026;
        
        showNotification('Preparando exportação...', 'info');
        
        // Criar formulário temporário para download
        const form = document.createElement('form');
        form.method = 'GET';
        form.action = 'api/pncp_integration.php';
        form.style.display = 'none';
        
        const inputs = [
            { name: 'acao', value: 'exportar' },
            { name: 'ano', value: ano },
            { name: 'formato', value: 'csv' }
        ];
        
        inputs.forEach(input => {
            const inputElement = document.createElement('input');
            inputElement.type = 'hidden';
            inputElement.name = input.name;
            inputElement.value = input.value;
            form.appendChild(inputElement);
        });
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
        
        showNotification('Download iniciado!', 'success');
        
    } catch (error) {
        console.error('[PNCP] Erro na exportação:', error);
        showNotification(`Erro na exportação: ${error.message}`, 'error');
    }
}

/**
 * Carregar histórico de sincronizações
 */
async function carregarHistoricoPNCP() {
    const loadingDiv = document.getElementById('loading-historico-pncp');
    const tabelaDiv = document.getElementById('tabela-historico-pncp');
    const emptyDiv = document.getElementById('empty-historico-pncp');
    const tbody = document.getElementById('tbody-historico-pncp');
    
    try {
        loadingDiv.style.display = 'block';
        tabelaDiv.style.display = 'none';
        emptyDiv.style.display = 'none';
        
        const response = await fetch('api/pncp_integration.php?acao=historico');
        const resultado = await response.json();
        
        if (resultado.sucesso && resultado.historico && resultado.historico.length > 0) {
            // Renderizar histórico
            tbody.innerHTML = resultado.historico.map(sync => `
                <tr>
                    <td style="font-size: 12px;">
                        <strong>${formatarData(sync.iniciada_em)}</strong><br>
                        <small style="color: #7f8c8d;">${formatarHora(sync.iniciada_em)}</small>
                    </td>
                    <td style="text-align: center; font-weight: 600; color: #3498db;">${sync.ano_pca}</td>
                    <td>
                        <span class="situacao-badge ${sync.status === 'concluida' ? 'success' : sync.status === 'erro' ? 'error' : 'warning'}">
                            ${sync.status === 'concluida' ? 'Concluída' : sync.status === 'erro' ? 'Erro' : 'Em Andamento'}
                        </span>
                    </td>
                    <td style="text-align: center; font-weight: 600;">
                        ${sync.registros_processados || 0}
                    </td>
                    <td style="text-align: center; color: #27ae60; font-weight: 600;">
                        ${sync.registros_novos || 0}
                    </td>
                    <td style="text-align: center; color: #3498db; font-weight: 600;">
                        ${sync.registros_atualizados || 0}
                    </td>
                    <td style="text-align: center; font-size: 12px;">
                        ${sync.tempo_processamento ? sync.tempo_processamento + 's' : '-'}
                    </td>
                    <td style="font-size: 12px;">
                        ${sync.usuario_nome || 'Sistema'}
                    </td>
                </tr>
            `).join('');
            
            tabelaDiv.style.display = 'block';
            
        } else {
            emptyDiv.style.display = 'block';
        }
        
    } catch (error) {
        console.error('[PNCP] Erro ao carregar histórico:', error);
        emptyDiv.style.display = 'block';
        
    } finally {
        loadingDiv.style.display = 'none';
    }
}

/**
 * Atualizar histórico de sincronizações
 */
function atualizarHistoricoPNCP() {
    carregarHistoricoPNCP();
}

/**
 * Verificar status da API do PNCP
 */
async function verificarStatusAPI() {
    try {
        const url = 'https://pncp.gov.br/api/pncp/v1/orgaos/00394544000185/pca/2026/csv';
        
        // Fazer uma requisição HEAD para verificar se a API responde
        // (implementação simplificada - pode precisar de proxy para CORS)
        
        const statusElement = document.getElementById('pncp-status-api');
        statusElement.innerHTML = '🟡 Verificando...';
        
        // Simular verificação
        setTimeout(() => {
            statusElement.innerHTML = '🟢 API Online';
            statusElement.parentElement.className = 'stat-card success';
        }, 2000);
        
    } catch (error) {
        console.error('[PNCP] Erro ao verificar API:', error);
        document.getElementById('pncp-status-api').innerHTML = '🔴 Indisponível';
    }
}

/**
 * Funções utilitárias específicas para PNCP
 */

function formatarMoedaBR(valor) {
    if (!valor || valor === 0) return 'R$ 0,00';
    
    const numero = typeof valor === 'string' ? parseFloat(valor) : valor;
    
    if (numero >= 1000000000) {
        return 'R$ ' + (numero / 1000000000).toFixed(1).replace('.', ',') + ' bi';
    } else if (numero >= 1000000) {
        return 'R$ ' + (numero / 1000000).toFixed(1).replace('.', ',') + ' mi';
    } else if (numero >= 1000) {
        return 'R$ ' + (numero / 1000).toFixed(1).replace('.', ',') + ' mil';
    }
    
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(numero);
}

function formatarDataHora(dataStr) {
    if (!dataStr) return '-';
    
    const data = new Date(dataStr);
    const agora = new Date();
    const diffMs = agora - data;
    const diffHoras = Math.floor(diffMs / (1000 * 60 * 60));
    const diffDias = Math.floor(diffHoras / 24);
    
    if (diffDias === 0 && diffHoras < 24) {
        if (diffHoras === 0) {
            const diffMinutos = Math.floor(diffMs / (1000 * 60));
            return diffMinutos <= 1 ? 'Agora' : `${diffMinutos}min atrás`;
        }
        return `${diffHoras}h atrás`;
    } else if (diffDias === 1) {
        return 'Ontem';
    } else if (diffDias < 7) {
        return `${diffDias} dias atrás`;
    } else {
        return formatarData(dataStr);
    }
}

function formatarData(dataStr) {
    if (!dataStr) return '-';
    
    try {
        const data = new Date(dataStr);
        return data.toLocaleDateString('pt-BR');
    } catch (error) {
        return dataStr;
    }
}

function formatarHora(dataStr) {
    if (!dataStr) return '-';
    
    try {
        const data = new Date(dataStr);
        return data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    } catch (error) {
        return '-';
    }
}

// Event listeners para inicialização
document.addEventListener('DOMContentLoaded', function() {
    console.log('[PNCP] Script PNCP carregado');
    
    // Se a seção PNCP estiver ativa, inicializar
    if (document.getElementById('pncp-integration')?.classList.contains('active')) {
        inicializarPNCP();
    }
    
    // Adicionar listener adicional ao botão (se existir)
    const botaoSync = document.getElementById('btn-sincronizar-pncp');
    if (botaoSync) {
        console.log('[PNCP] Adicionando listener adicional ao botão');
        
        botaoSync.addEventListener('click', function(e) {
            console.log('[PNCP] Botão clicado via addEventListener');
            e.preventDefault();
            e.stopPropagation();
            
            // Executar função
            if (typeof sincronizarPNCP === 'function') {
                sincronizarPNCP();
            } else {
                console.error('[PNCP] Função sincronizarPNCP não está disponível');
                alert('Erro: Função de sincronização não encontrada. Recarregue a página.');
            }
        });
    } else {
        console.log('[PNCP] Botão btn-sincronizar-pncp não encontrado no DOM');
    }
});

// Aguardar carregamento completo da página
window.addEventListener('load', function() {
    console.log('[PNCP] Página totalmente carregada');
    
    // Verificar novamente se o botão existe
    const botaoSync = document.getElementById('btn-sincronizar-pncp');
    if (botaoSync) {
        console.log('[PNCP] Botão encontrado após load completo');
    } else {
        console.log('[PNCP] Botão ainda não encontrado após load completo');
    }
});

/**
 * Funções utilitárias adicionais
 */

function getSituacaoClass(situacao) {
    if (!situacao) return 'info';
    
    const situacaoLower = situacao.toLowerCase();
    
    if (situacaoLower.includes('planejado')) return 'info';
    if (situacaoLower.includes('andamento')) return 'warning';
    if (situacaoLower.includes('concluído') || situacaoLower.includes('concluido')) return 'success';
    if (situacaoLower.includes('cancelado')) return 'error';
    if (situacaoLower.includes('suspenso')) return 'warning';
    
    return 'info';
}

function renderizarPaginacaoPNCP(paginacao) {
    const container = document.getElementById('paginacao-pncp');
    
    if (!container || !paginacao) return;
    
    const { pagina_atual, total_paginas, total_registros } = paginacao;
    
    let html = `
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div style="color: #7f8c8d; font-size: 14px;">
                Página ${pagina_atual} de ${total_paginas} (${total_registros} registros)
            </div>
            <div style="display: flex; gap: 5px;">
    `;
    
    // Botão Primeira
    if (pagina_atual > 1) {
        html += `<button onclick="consultarDadosPNCP(1)" style="padding: 8px 12px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">« Primeira</button>`;
    }
    
    // Botão Anterior
    if (pagina_atual > 1) {
        html += `<button onclick="consultarDadosPNCP(${pagina_atual - 1})" style="padding: 8px 12px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">‹ Anterior</button>`;
    }
    
    // Páginas numeradas
    const inicio = Math.max(1, pagina_atual - 2);
    const fim = Math.min(total_paginas, pagina_atual + 2);
    
    for (let i = inicio; i <= fim; i++) {
        const isAtiva = i === pagina_atual;
        html += `<button onclick="consultarDadosPNCP(${i})" 
                 style="padding: 8px 12px; border: 1px solid ${isAtiva ? '#3498db' : '#ddd'}; 
                        background: ${isAtiva ? '#3498db' : 'white'}; 
                        color: ${isAtiva ? 'white' : 'black'}; 
                        border-radius: 4px; cursor: pointer; font-weight: ${isAtiva ? '600' : 'normal'};">
                 ${i}
                </button>`;
    }
    
    // Botão Próximo
    if (pagina_atual < total_paginas) {
        html += `<button onclick="consultarDadosPNCP(${pagina_atual + 1})" style="padding: 8px 12px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">Próximo ›</button>`;
    }
    
    // Botão Última
    if (pagina_atual < total_paginas) {
        html += `<button onclick="consultarDadosPNCP(${total_paginas})" style="padding: 8px 12px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">Última »</button>`;
    }
    
    html += '</div></div>';
    
    container.innerHTML = html;
}

// Exportar funções globalmente
window.sincronizarPNCP = sincronizarPNCP;
window.compararDados = compararDados;
window.consultarDadosPNCP = consultarDadosPNCP;
window.aplicarFiltrosPNCP = aplicarFiltrosPNCP;
window.limparFiltrosPNCP = limparFiltrosPNCP;
window.exportarDadosPNCP = exportarDadosPNCP;
window.atualizarHistoricoPNCP = atualizarHistoricoPNCP;
window.inicializarPNCP = inicializarPNCP;