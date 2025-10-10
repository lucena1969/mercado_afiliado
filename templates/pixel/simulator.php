<?php
/**
 * Simulador de Eventos do Pixel BR
 * Permite testar eventos antes da implementação em produção
 */

// Verificar autenticação
require_once __DIR__ . '/../../app/controllers/AuthController.php';

$auth = new AuthController();
$auth->requireAuth();

$user_data = $_SESSION['user'] ?? null;
if (!$user_data) {
    header('Location: ' . BASE_URL . '/login');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$pixelConfig = new PixelConfiguration($conn);
$activeConfig = null;

if ($pixelConfig->readActiveByUserId($user_data['id'])) {
    $activeConfig = $pixelConfig;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulador Pixel BR - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/dashboard-unified.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="<?= BASE_URL ?>/public/assets/js/dashboard-unified.js"></script>
    <style>
        /* Ajustes específicos do simulador seguindo o padrão do projeto */
        .simulator-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .event-form {
            background: var(--color-white);
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
            border: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }

        .event-form:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .event-preview {
            background: var(--color-dark);
            color: var(--color-white);
            padding: 1.5rem;
            border-radius: 0.75rem;
            font-family: 'Courier New', 'Monaco', monospace;
            font-size: var(--font-size-sm);
            overflow-x: auto;
            box-shadow: var(--shadow);
            border: 1px solid #f1f5f9;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            color: var(--color-dark);
            font-size: var(--font-size-sm);
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid #e5e7eb;
            border-radius: var(--border-radius);
            font-family: 'Inter', sans-serif;
            font-size: var(--font-size-base);
            transition: all 0.2s ease;
            background-color: var(--color-white);
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        .event-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .btn-test {
            background: var(--color-primary);
            color: var(--color-dark);
            border: none;
            padding: 0.875rem 1.5rem;
            border-radius: var(--border-radius);
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .btn-test:hover {
            background: var(--color-primary-dark);
            box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        .btn-clear {
            background: transparent;
            color: var(--color-gray);
            border: 1.5px solid var(--color-gray);
            padding: 0.875rem 1.5rem;
            border-radius: var(--border-radius);
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-clear:hover {
            background: var(--color-gray);
            color: var(--color-white);
            transform: translateY(-1px);
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            font-family: 'Inter', sans-serif;
            font-size: var(--font-size-sm);
            font-weight: 500;
            box-shadow: var(--shadow);
        }

        .status-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .status-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .status-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .test-results {
            margin-top: 1.5rem;
        }

        .result-item {
            background: var(--color-white);
            border: 1px solid #f1f5f9;
            border-radius: 0.75rem;
            padding: 1.25rem;
            margin-bottom: 0.75rem;
            box-shadow: var(--shadow);
            transition: all 0.2s ease;
        }

        .result-item:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-1px);
        }

        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .result-platform {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: var(--color-dark);
        }

        .result-status {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: var(--font-size-xs);
            font-weight: 600;
        }

        .result-success {
            background: #dcfce7;
            color: #166534;
        }

        .result-error {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Responsivo */
        @media (max-width: 1024px) {
            .simulator-container {
                grid-template-columns: 1fr;
            }
        }

        /* Ajustes específicos para detalhes */
        details summary {
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--color-dark);
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: background-color 0.2s ease;
        }

        details summary:hover {
            background-color: var(--color-light-gray);
        }

        details[open] summary {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Header principal com logo -->
    <?php include __DIR__ . '/../../app/components/header.php'; ?>

    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="<?= BASE_URL ?>/dashboard"><i data-lucide="bar-chart-3" style="width: 16px; height: 16px; margin-right: 6px;"></i>Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>/unified-panel"><i data-lucide="trending-up" style="width: 16px; height: 16px; margin-right: 6px;"></i>Painel Unificado</a></li>
                <li><a href="<?= BASE_URL ?>/integrations"><i data-lucide="link" style="width: 16px; height: 16px; margin-right: 6px;"></i>IntegraSync</a></li>
                <li><a href="<?= BASE_URL ?>/link-maestro"><i data-lucide="target" style="width: 16px; height: 16px; margin-right: 6px;"></i>Link Maestro</a></li>
                <li><a href="<?= BASE_URL ?>/pixel" class="active"><i data-lucide="eye" style="width: 16px; height: 16px; margin-right: 6px;"></i>Pixel BR</a></li>
                <li><a href="#" onclick="showComingSoon('Alerta Queda')"><i data-lucide="alert-triangle" style="width: 16px; height: 16px; margin-right: 6px;"></i>Alerta Queda</a></li>
                <li><a href="#" onclick="showComingSoon('CAPI Bridge')"><i data-lucide="bridge" style="width: 16px; height: 16px; margin-right: 6px;"></i>CAPI Bridge</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="panel-header">
                <div>
                    <h1><i data-lucide="play-circle" style="width: 20px; height: 20px; margin-right: 8px;"></i>Simulador de Eventos</h1>
                    <p>Teste seus eventos do Pixel BR antes da implementação</p>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>/pixel" style="color: #64748b; text-decoration: none; font-size: 14px;">← Voltar ao Pixel</a>
                </div>
            </div>

            <?php if (!$activeConfig): ?>
                <div class="status-indicator status-warning">
                    <i data-lucide="alert-triangle" style="width: 16px; height: 16px;"></i>
                    Configure seu pixel primeiro antes de usar o simulador.
                    <a href="<?= BASE_URL ?>/pixel" style="margin-left: 8px; color: inherit; font-weight: 600;">Configurar agora</a>
                </div>
            <?php else: ?>
                <div class="status-indicator status-success">
                    <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i>
                    Pixel configurado e ativo. Pronto para testar eventos!
                </div>
            <?php endif; ?>

            <div class="simulator-container">
                <!-- Formulário de Evento -->
                <div class="event-form">
                    <h3 class="font-marketing text-xl" style="margin-bottom: 1.25rem; color: var(--color-dark); display: flex; align-items: center;">
                        <i data-lucide="settings" style="width: 18px; height: 18px; margin-right: 0.5rem;"></i>
                        Configurar Evento
                    </h3>

                    <form id="eventForm">
                        <div class="form-group">
                            <label for="eventType">Tipo de Evento</label>
                            <select id="eventType" name="eventType" onchange="updateEventForm()">
                                <option value="page_view">Page View</option>
                                <option value="view_content">View Content</option>
                                <option value="add_to_cart">Add to Cart</option>
                                <option value="initiate_checkout">Initiate Checkout</option>
                                <option value="lead">Lead</option>
                                <option value="purchase">Purchase</option>
                                <option value="custom">Evento Customizado</option>
                            </select>
                        </div>

                        <div class="form-group" id="customEventName" style="display: none;">
                            <label for="customName">Nome do Evento Customizado</label>
                            <input type="text" id="customName" name="customName" placeholder="meu_evento_personalizado">
                        </div>

                        <div class="form-group">
                            <label for="sourceUrl">URL da Página</label>
                            <input type="url" id="sourceUrl" name="sourceUrl" value="https://exemplo.com/pagina" placeholder="https://exemplo.com">
                        </div>

                        <div class="form-group">
                            <label for="userEmail">Email do Usuário (Opcional)</label>
                            <input type="email" id="userEmail" name="userEmail" placeholder="usuario@exemplo.com">
                        </div>

                        <!-- Campos condicionais para eventos de compra -->
                        <div id="purchaseFields" style="display: none;">
                            <div class="form-group">
                                <label for="orderValue">Valor do Pedido (R$)</label>
                                <input type="number" id="orderValue" name="orderValue" step="0.01" placeholder="197.00">
                            </div>

                            <div class="form-group">
                                <label for="orderId">ID do Pedido</label>
                                <input type="text" id="orderId" name="orderId" placeholder="ORDER-123456">
                            </div>

                            <div class="form-group">
                                <label for="currency">Moeda</label>
                                <select id="currency" name="currency">
                                    <option value="BRL">BRL</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                </select>
                            </div>
                        </div>

                        <!-- Campos UTM -->
                        <details style="margin: 16px 0;">
                            <summary style="cursor: pointer; font-weight: 600; margin-bottom: 12px;">Parâmetros UTM (Opcional)</summary>
                            <div class="form-group">
                                <label for="utmSource">UTM Source</label>
                                <input type="text" id="utmSource" name="utmSource" placeholder="facebook">
                            </div>

                            <div class="form-group">
                                <label for="utmMedium">UTM Medium</label>
                                <input type="text" id="utmMedium" name="utmMedium" placeholder="cpc">
                            </div>

                            <div class="form-group">
                                <label for="utmCampaign">UTM Campaign</label>
                                <input type="text" id="utmCampaign" name="utmCampaign" placeholder="black-friday-2024">
                            </div>
                        </details>

                        <div class="event-buttons">
                            <button type="button" class="btn-test" onclick="sendTestEvent()" <?= !$activeConfig ? 'disabled' : '' ?>>
                                <i data-lucide="send" style="width: 16px; height: 16px; margin-right: 6px;"></i>
                                Testar Evento
                            </button>
                            <button type="button" class="btn-clear" onclick="clearForm()">
                                <i data-lucide="refresh-cw" style="width: 16px; height: 16px; margin-right: 6px;"></i>
                                Limpar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Preview do Código -->
                <div class="event-preview">
                    <h3 class="font-marketing text-xl" style="margin-bottom: 1rem; color: var(--color-white); display: flex; align-items: center;">
                        <i data-lucide="code" style="width: 18px; height: 18px; margin-right: 0.5rem;"></i>
                        Preview do Código JavaScript
                    </h3>
                    <pre id="codePreview">// Configure um evento para ver o código aqui</pre>
                </div>
            </div>

            <!-- Resultados dos Testes -->
            <div class="test-results" id="testResults" style="display: none;">
                <h3 class="font-marketing text-xl" style="margin-bottom: 1rem; color: var(--color-dark); display: flex; align-items: center;">
                    <i data-lucide="activity" style="width: 18px; height: 18px; margin-right: 0.5rem;"></i>
                    Resultados do Teste
                </h3>
                <div id="resultsContainer"></div>
            </div>
        </main>
    </div>

    <script>
        // Atualizar formulário baseado no tipo de evento
        function updateEventForm() {
            const eventType = document.getElementById('eventType').value;
            const customEventName = document.getElementById('customEventName');
            const purchaseFields = document.getElementById('purchaseFields');

            // Mostrar/ocultar campo de evento customizado
            customEventName.style.display = eventType === 'custom' ? 'block' : 'none';

            // Mostrar/ocultar campos de compra
            const showPurchaseFields = ['purchase', 'add_to_cart', 'initiate_checkout'].includes(eventType);
            purchaseFields.style.display = showPurchaseFields ? 'block' : 'none';

            updateCodePreview();
        }

        // Atualizar preview do código
        function updateCodePreview() {
            const form = document.getElementById('eventForm');
            const formData = new FormData(form);

            let eventName = formData.get('eventType');
            if (eventName === 'custom') {
                eventName = formData.get('customName') || 'custom_event';
            }

            let eventData = {
                event_name: eventName,
                source_url: formData.get('sourceUrl') || window.location.href
            };

            // Adicionar email se fornecido
            if (formData.get('userEmail')) {
                eventData.user_data = {
                    em: formData.get('userEmail')
                };
            }

            // Adicionar dados de compra se aplicável
            if (['purchase', 'add_to_cart', 'initiate_checkout'].includes(formData.get('eventType'))) {
                eventData.custom_data = {};

                if (formData.get('orderValue')) {
                    eventData.custom_data.value = parseFloat(formData.get('orderValue'));
                }

                if (formData.get('orderId')) {
                    eventData.custom_data.order_id = formData.get('orderId');
                }

                if (formData.get('currency')) {
                    eventData.custom_data.currency = formData.get('currency');
                }
            }

            // Adicionar UTMs se fornecidos
            const utmFields = ['utmSource', 'utmMedium', 'utmCampaign'];
            utmFields.forEach(field => {
                const value = formData.get(field);
                if (value) {
                    eventData[field.toLowerCase()] = value;
                }
            });

            const codePreview = `// Enviar evento via Pixel BR
pixelBR.track('${eventName}', ${JSON.stringify(eventData, null, 2)});

// Ou via fetch direto para a API
fetch('<?= BASE_URL ?>/api/pixel/collect.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(${JSON.stringify(eventData, null, 2)})
});`;

            document.getElementById('codePreview').textContent = codePreview;
        }

        // Enviar evento de teste
        async function sendTestEvent() {
            const btn = event.target;
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader" style="width: 16px; height: 16px; margin-right: 6px;"></i>Enviando...';
            lucide.createIcons();

            const form = document.getElementById('eventForm');
            const formData = new FormData(form);

            let eventName = formData.get('eventType');
            if (eventName === 'custom') {
                eventName = formData.get('customName') || 'custom_event';
            }

            let eventData = {
                event_name: eventName,
                source_url: formData.get('sourceUrl') || window.location.href,
                pixel_id: '<?= $activeConfig->pixel_hash ?? '' ?>', // ID único do pixel
                test_mode: true // Marcar como teste
            };

            // Construir dados do evento (mesmo código do preview)
            if (formData.get('userEmail')) {
                eventData.user_data = {
                    em: formData.get('userEmail')
                };
            }

            if (['purchase', 'add_to_cart', 'initiate_checkout'].includes(formData.get('eventType'))) {
                eventData.custom_data = {};

                if (formData.get('orderValue')) {
                    eventData.custom_data.value = parseFloat(formData.get('orderValue'));
                }

                if (formData.get('orderId')) {
                    eventData.custom_data.order_id = formData.get('orderId');
                }

                if (formData.get('currency')) {
                    eventData.custom_data.currency = formData.get('currency');
                }
            }

            const utmFields = ['utmSource', 'utmMedium', 'utmCampaign'];
            utmFields.forEach(field => {
                const value = formData.get(field);
                if (value) {
                    eventData[field.toLowerCase()] = value;
                }
            });

            try {
                const response = await fetch('<?= BASE_URL ?>/api/pixel/collect.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(eventData)
                });

                const result = await response.json();
                showTestResults(result, eventData);

            } catch (error) {
                console.error('Erro ao enviar evento:', error);
                showTestResults({success: false, error: error.message}, eventData);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHTML;
                lucide.createIcons();
            }
        }

        // Mostrar resultados do teste
        function showTestResults(result, eventData) {
            const testResults = document.getElementById('testResults');
            const resultsContainer = document.getElementById('resultsContainer');

            testResults.style.display = 'block';

            const timestamp = new Date().toLocaleString('pt-BR');

            // Determinar se foi sucesso baseado na estrutura da API
            const isSuccess = result.ok === true || result.success === true;
            const eventId = result.dispatch?.event_id || 'N/A';
            const bridgesCount = result.dispatch?.bridges_triggered || 0;

            let resultsHTML = `
                <div class="result-item">
                    <div class="result-header">
                        <span class="result-platform">Teste realizado em ${timestamp}</span>
                        <span class="result-status ${isSuccess ? 'result-success' : 'result-error'}">
                            ${isSuccess ? '✅ Sucesso' : '❌ Erro'}
                        </span>
                    </div>
                    <p><strong>Evento:</strong> ${eventData.event_name}</p>
                    <p><strong>URL:</strong> ${eventData.source_url}</p>
                    ${eventData.pixel_id ? `<p><strong>Pixel ID:</strong> ${eventData.pixel_id}</p>` : ''}
                    ${isSuccess ? `
                        <div style="margin-top: 12px; padding: 12px; background: #dcfce7; border-radius: 8px; border: 1px solid #bbf7d0;">
                            <p style="color: #166534; margin: 0; font-weight: 600;">✓ Evento registrado com sucesso!</p>
                            <p style="color: #166534; margin: 4px 0 0 0; font-size: 13px;">Event ID: ${eventId}</p>
                            ${bridgesCount > 0 ? `<p style="color: #166534; margin: 4px 0 0 0; font-size: 13px;">Bridges acionadas: ${bridgesCount}</p>` : ''}
                        </div>
                    ` : `
                        <div style="margin-top: 12px; padding: 12px; background: #fee2e2; border-radius: 8px; border: 1px solid #fecaca;">
                            <p style="color: #991b1b; margin: 0; font-weight: 600;">✗ Erro ao processar evento</p>
                            <p style="color: #991b1b; margin: 4px 0 0 0; font-size: 13px;">${result.error || result.reason || 'Erro desconhecido. Verifique o console do navegador.'}</p>
                        </div>
                    `}
                </div>
            `;

            resultsContainer.innerHTML = resultsHTML + resultsContainer.innerHTML;

            // Scroll suave para os resultados
            testResults.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // Limpar formulário
        function clearForm() {
            document.getElementById('eventForm').reset();
            updateCodePreview();
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            updateEventForm();

            // Atualizar preview quando qualquer campo mudar
            const form = document.getElementById('eventForm');
            form.addEventListener('input', updateCodePreview);
            form.addEventListener('change', updateCodePreview);
       