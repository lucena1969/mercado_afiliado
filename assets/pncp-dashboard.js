/**
 * PNCP Dashboard - Interface baseada no gov.br
 * Integração PCA ↔ PNCP com views inteligentes
 */

class PncpDashboard {
    constructor() {
        this.dadosPgc = [];
        this.dadosPncp = [];
        this.filtrosAtivos = {};
        this.ordenacaoAtual = { campo: null, direcao: 'asc' };

        this.init();
    }

    init() {
        this.carregarEstatisticas();
        this.carregarAreasRequitantes();
        this.configurarEventos();
    }

    /**
     * Carrega estatísticas iniciais do dashboard
     */
    async carregarEstatisticas() {
        try {
            const response = await fetch('api/get_estatisticas_pca_pncp.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Erro ao carregar estatísticas');

            const dados = await response.json();
            this.atualizarEstatisticas(dados);

        } catch (error) {
            console.error('Erro ao carregar estatísticas:', error);
            this.mostrarEstatisticasDefault();
        }
    }

    /**
     * Atualiza os cards de estatísticas
     */
    atualizarEstatisticas(dados) {
        document.getElementById('stat-qtd-dfd').textContent = dados.quantidade_dfd || '0';
        document.getElementById('stat-total-itens').textContent = dados.total_itens || '0';

        // Formatar valores monetários
        const valorPgc = dados.valor_total_pgc || 0;
        const valorPncp = dados.valor_total_pncp || 0;

        document.getElementById('stat-valor-pgc').textContent = this.formatarValor(valorPgc);
        document.getElementById('stat-valor-pncp').textContent = this.formatarValor(valorPncp);
    }

    /**
     * Estatísticas padrão quando não há dados
     */
    mostrarEstatisticasDefault() {
        document.getElementById('stat-qtd-dfd').textContent = '0';
        document.getElementById('stat-total-itens').textContent = '0';
        document.getElementById('stat-valor-pgc').textContent = '0';
        document.getElementById('stat-valor-pncp').textContent = '0';
    }

    /**
     * Carrega áreas requisitantes para o filtro
     */
    async carregarAreasRequitantes() {
        try {
            const response = await fetch('api/get_areas_requisitantes.php');
            const data = await response.json();

            const select = document.getElementById('filtro-area-demandante');
            if (!select) return;

            select.innerHTML = '<option value="">Todos</option>';

            // Verificar se retornou um array
            const areas = Array.isArray(data) ? data : (data.areas || []);

            areas.forEach(area => {
                const option = document.createElement('option');
                option.value = area.area_requisitante || area;
                option.textContent = area.area_requisitante || area;
                select.appendChild(option);
            });

        } catch (error) {
            console.error('Erro ao carregar áreas requisitantes:', error);
        }
    }

    /**
     * Configura eventos dos elementos
     */
    configurarEventos() {
        // Filtro em tempo real no campo DFD
        document.getElementById('filtro-dfd').addEventListener('input', this.debounce(() => {
            this.aplicarFiltros();
        }, 500));

        // Filtro em tempo real no identificador
        document.getElementById('filtro-identificador').addEventListener('input', this.debounce(() => {
            this.aplicarFiltros();
        }, 500));

        // Enter para filtrar
        document.getElementById('filtro-dfd').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.aplicarFiltros();
            }
        });
    }

    /**
     * Aplica todos os filtros e recarrega os dados
     */
    async aplicarFiltros() {
        const filtros = {
            area_demandante: document.getElementById('filtro-area-demandante').value,
            numero_dfd: document.getElementById('filtro-dfd').value,
            situacao_execucao: document.getElementById('filtro-situacao').value,
            identificador_pncp: document.getElementById('filtro-identificador').value
        };

        this.filtrosAtivos = filtros;

        // Mostrar loading
        this.mostrarLoading();

        try {
            // Carregar dados PGC
            await this.carregarDadosPgc(filtros);

            // Carregar dados PNCP relacionados
            await this.carregarDadosPncp(filtros);

        } catch (error) {
            console.error('Erro ao aplicar filtros:', error);
            this.mostrarErro('Erro ao carregar dados');
        }
    }

    /**
     * Carrega dados do PGC (PCA) com filtros
     */
    async carregarDadosPgc(filtros) {
        const params = new URLSearchParams();
        Object.keys(filtros).forEach(key => {
            if (filtros[key]) params.append(key, filtros[key]);
        });

        const response = await fetch(`api/get_dados_pgc_filtrados.php?${params}`);
        const dados = await response.json();

        this.dadosPgc = dados;
        this.renderizarTabelaPgc();
    }

    /**
     * Carrega dados do PNCP relacionados aos filtros
     */
    async carregarDadosPncp(filtros) {
        const params = new URLSearchParams();
        Object.keys(filtros).forEach(key => {
            if (filtros[key]) params.append(key, filtros[key]);
        });

        const response = await fetch(`api/get_dados_pncp_relacionados.php?${params}`);
        const dados = await response.json();

        this.dadosPncp = dados;
        this.renderizarTabelaPncp();
    }

    /**
     * Renderiza tabela dos dados PGC
     */
    renderizarTabelaPgc() {
        const tbody = document.getElementById('tabela-dados-pgc');

        if (this.dadosPgc.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" style="padding: 40px; text-align: center; color: #9ca3af;">
                        <i data-lucide="search" style="width: 32px; height: 32px; margin-bottom: 10px;"></i>
                        <div>Nenhum resultado encontrado</div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.dadosPgc.map(item => `
            <tr style="border-bottom: 1px solid #f1f5f9;" data-dfd="${item.numero_dfd}">
                <td style="padding: 12px; font-weight: 600; color: #3b82f6;">
                    ${item.numero_dfd || '-'}
                </td>
                <td style="padding: 12px; color: #374151;">
                    ${item.numero_contratacao || '-'}
                </td>
                <td style="padding: 12px; color: #374151; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                    title="${item.titulo_contratacao || ''}">
                    ${item.titulo_contratacao || '-'}
                </td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #059669;">
                    ${this.formatarValor(item.valor_total_contratacao)}
                </td>
            </tr>
        `).join('');

        // Re-inicializar ícones Lucide
        if (window.lucide) {
            lucide.createIcons();
        }
    }

    /**
     * Renderiza tabela dos dados PNCP
     */
    renderizarTabelaPncp() {
        const tbody = document.getElementById('tabela-dados-pncp');

        if (this.dadosPncp.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" style="padding: 40px; text-align: center; color: #9ca3af;">
                        <i data-lucide="cloud-off" style="width: 32px; height: 32px; margin-bottom: 10px;"></i>
                        <div>Nenhum dado PNCP encontrado</div>
                        <div style="font-size: 12px; margin-top: 5px;">Sincronize com o PNCP para obter dados</div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.dadosPncp.map(item => `
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 12px; color: #374151;">
                    ${item.identificador_futura_contratacao || '-'}
                </td>
                <td style="padding: 12px; font-weight: 600; color: #7c3aed;">
                    ${item.id_item_pca || '-'}
                </td>
                <td style="padding: 12px; color: #374151;">
                    ${item.codigo_classificacao_superior || '-'}
                </td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #059669;">
                    ${this.formatarValor(item.valor_total_estimado)}
                </td>
            </tr>
        `).join('');

        // Re-inicializar ícones Lucide
        if (window.lucide) {
            lucide.createIcons();
        }
    }

    /**
     * Mostra estado de loading
     */
    mostrarLoading() {
        document.getElementById('tabela-dados-pgc').innerHTML = `
            <tr>
                <td colspan="4" style="padding: 40px; text-align: center; color: #9ca3af;">
                    <i data-lucide="loader-2" style="width: 32px; height: 32px; margin-bottom: 10px; animation: spin 1s linear infinite;"></i>
                    <div>Carregando dados...</div>
                </td>
            </tr>
        `;

        document.getElementById('tabela-dados-pncp').innerHTML = `
            <tr>
                <td colspan="4" style="padding: 40px; text-align: center; color: #9ca3af;">
                    <i data-lucide="loader-2" style="width: 32px; height: 32px; margin-bottom: 10px; animation: spin 1s linear infinite;"></i>
                    <div>Carregando dados...</div>
                </td>
            </tr>
        `;

        // Re-inicializar ícones Lucide
        if (window.lucide) {
            lucide.createIcons();
        }
    }

    /**
     * Sincroniza dados com PNCP
     */
    async sincronizarComPncp() {
        const modal = document.getElementById('modal-progresso-pncp');
        modal.style.display = 'block';

        try {
            await this.executarSincronizacaoPncp();

            // Recarregar dados após sincronização
            await this.aplicarFiltros();
            await this.carregarEstatisticas();

            this.mostrarSucesso('Sincronização concluída com sucesso!');

        } catch (error) {
            console.error('Erro na sincronização:', error);
            this.mostrarErro('Erro durante a sincronização: ' + error.message);
        } finally {
            modal.style.display = 'none';
        }
    }

    /**
     * Executa o processo de sincronização
     */
    async executarSincronizacaoPncp() {
        // Etapa 1: Download
        this.atualizarProgresso(20, 'Baixando dados do PNCP...', 'Conectando com a API oficial');

        const downloadResponse = await fetch('api/sincronizar_pncp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ acao: 'download', ano: 2026 })
        });

        if (!downloadResponse.ok) {
            throw new Error('Falha no download dos dados PNCP');
        }

        // Etapa 2: Processamento
        this.atualizarProgresso(50, 'Processando dados...', 'Filtrando registros da UASG 250110');

        const processResponse = await fetch('api/sincronizar_pncp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ acao: 'processar' })
        });

        // Etapa 3: Importação
        this.atualizarProgresso(80, 'Importando para o banco...', 'Executando UPSERT na base de dados');

        const importResponse = await fetch('api/sincronizar_pncp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ acao: 'importar' })
        });

        // Finalização
        this.atualizarProgresso(100, 'Sincronização concluída!', 'Dados atualizados com sucesso');
    }

    /**
     * Atualiza progresso da sincronização
     */
    atualizarProgresso(percentual, etapa, detalhe) {
        document.getElementById('progresso-barra-pncp').style.width = percentual + '%';
        document.getElementById('progresso-percentual').textContent = percentual + '%';
        document.querySelector('#progresso-etapa div').textContent = etapa;
        document.getElementById('progresso-detalhe').textContent = detalhe;
    }

    /**
     * Ordenar tabela PGC
     */
    ordenarTabelaPgc(campo) {
        if (this.ordenacaoAtual.campo === campo) {
            this.ordenacaoAtual.direcao = this.ordenacaoAtual.direcao === 'asc' ? 'desc' : 'asc';
        } else {
            this.ordenacaoAtual.campo = campo;
            this.ordenacaoAtual.direcao = 'asc';
        }

        this.dadosPgc.sort((a, b) => {
            let valorA = a[campo] || '';
            let valorB = b[campo] || '';

            if (this.ordenacaoAtual.direcao === 'asc') {
                return valorA.toString().localeCompare(valorB.toString());
            } else {
                return valorB.toString().localeCompare(valorA.toString());
            }
        });

        this.renderizarTabelaPgc();
    }

    /**
     * Formatar valores monetários
     */
    formatarValor(valor) {
        if (!valor || valor === 0) return '0';

        const num = parseFloat(valor);
        if (num >= 1000000) {
            return (num / 1000000).toFixed(2).replace('.', ',') + ' Mil';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1).replace('.', ',') + ' K';
        } else {
            return num.toFixed(2).replace('.', ',');
        }
    }

    /**
     * Utilitário debounce
     */
    debounce(func, wait) {
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
     * Mostra mensagem de sucesso
     */
    mostrarSucesso(mensagem) {
        // Implementar toast notification
        console.log('Sucesso:', mensagem);
    }

    /**
     * Mostra mensagem de erro
     */
    mostrarErro(mensagem) {
        // Implementar toast notification
        console.error('Erro:', mensagem);
    }
}

// Funções globais para os botões
window.aplicarFiltrosPcaPncp = function() {
    if (window.pncpDashboard) {
        window.pncpDashboard.aplicarFiltros();
    }
};

window.sincronizarComPncp = function() {
    if (window.pncpDashboard) {
        window.pncpDashboard.sincronizarComPncp();
    }
};

window.visualizarPcaInterno = function() {
    // Redirecionar para a seção de dados PCA
    showSection('dashboard');
};

window.ordenarTabelaPgc = function(campo) {
    if (window.pncpDashboard) {
        window.pncpDashboard.ordenarTabelaPgc(campo);
    }
};

// Inicializar quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    // Só inicializar se estivermos na seção PNCP
    if (document.getElementById('pncp-integration')) {
        window.pncpDashboard = new PncpDashboard();
    }
});

// Inicializar quando a seção for exibida
document.addEventListener('sectionChanged', function(e) {
    if (e.detail.section === 'pncp-integration') {
        if (!window.pncpDashboard) {
            window.pncpDashboard = new PncpDashboard();
        }
    }
});