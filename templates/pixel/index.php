<?php
/**
 * Página de Configuração do Pixel BR
 */
// Arquivo já foi incluído via router, então config já foi carregado
// Verificar autenticação usando o mesmo padrão do dashboard
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
$pixelConfig->user_id = $user_data['id'];

$configs = $pixelConfig->readByUserId($user_data['id']);
$activeConfig = null;

// Buscar pixel ativo
if ($pixelConfig->readActiveByUserId($user_data['id'])) {
    $activeConfig = $pixelConfig;
} else {
    // Se não houver ativo, buscar o mais recente (qualquer status)
    $stmt = $configs;
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $pixelConfig->id = $row['id'];
        if ($pixelConfig->read()) {
            $activeConfig = $pixelConfig;
        }
    }
}

$eventsSummary = null;
$bridgeStatus = [];

if ($activeConfig) {
    $eventsSummary = $activeConfig->getEventsSummary(30);
    $bridgeStatus = $activeConfig->getBridgeStatus();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pixel BR - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/dashboard-unified.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="<?= BASE_URL ?>/public/assets/js/dashboard-unified.js"></script>
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
                    <h1><i data-lucide="eye" style="width: 20px; height: 20px; margin-right: 8px;"></i>Pixel BR</h1>
                    <p>Sistema de tracking compatível com LGPD</p>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>/pixel/simulator" class="btn btn-primary">
                        <i data-lucide="play-circle" style="width: 16px; height: 16px; margin-right: 6px;"></i>
                        Simulador
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <span><?= htmlspecialchars($_SESSION['success_message']) ?></span>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?= htmlspecialchars($_SESSION['error_message']) ?></span>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

        <?php if ($activeConfig): ?>
        <div class="cards-grid">
            <div class="card">
                <div class="card-body">
                    <h3><?= number_format($eventsSummary['total_events'] ?? 0) ?></h3>
                    <p>Eventos (30 dias)</p>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h3><?= number_format($eventsSummary['page_views'] ?? 0) ?></h3>
                    <p>Page Views</p>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h3><?= number_format($eventsSummary['leads'] ?? 0) ?></h3>
                    <p>Leads</p>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h3><?= number_format($eventsSummary['purchases'] ?? 0) ?></h3>
                    <p>Compras</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab-buttons">
                <button class="tab-button active" onclick="showTab('config')">Configuração</button>
                <button class="tab-button" onclick="showTab('snippet')">Código</button>
                <button class="tab-button" onclick="showTab('bridges')">Bridges</button>
                <button class="tab-button" onclick="showTab('events')">Eventos</button>
                <button class="tab-button" onclick="window.location.href='<?= BASE_URL ?>/pixel/simulator'">🧪 Simulador</button>
            </div>

            <!-- Tab: Configuração -->
            <div id="tab-config" class="tab-content active">
                <div class="card">
                    <div class="card-header">
                        <h3>Configuração do Pixel</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= BASE_URL ?>/pixel/save">
                            <input type="hidden" name="action" value="save_config">
                            <?php if ($activeConfig): ?>
                                <input type="hidden" name="config_id" value="<?= $activeConfig->id ?>">
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="pixel_name">Nome do Pixel</label>
                                <input type="text" name="pixel_name" id="pixel_name" 
                                       value="<?= htmlspecialchars($activeConfig->pixel_name ?? '') ?>" 
                                       placeholder="Ex: Pixel Principal" required>
                            </div>

                            <div class="form-group">
                                <label for="integration_id">Integração Associada</label>
                                <select name="integration_id" id="integration_id">
                                    <option value="">Selecione uma integração (opcional)</option>
                                    <?php
                                    $integrationQuery = "SELECT id, name, platform FROM integrations WHERE user_id = :user_id AND status = 'active'";
                                    $integrationStmt = $conn->prepare($integrationQuery);
                                    $integrationStmt->bindParam(':user_id', $user_data['id']);
                                    $integrationStmt->execute();
                                    
                                    while ($integration = $integrationStmt->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?= $integration['id'] ?>" 
                                                <?= ($activeConfig && $activeConfig->integration_id == $integration['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($integration['name']) ?> (<?= ucfirst($integration['platform']) ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="auto_track_pageviews" 
                                               <?= ($activeConfig && $activeConfig->auto_track_pageviews) ? 'checked' : 'checked' ?>>
                                        Rastrear page views automaticamente
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="auto_track_clicks" 
                                               <?= ($activeConfig && $activeConfig->auto_track_clicks) ? 'checked' : '' ?>>
                                        Rastrear cliques automaticamente
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="consent_mode">Modo de Consentimento</label>
                                <select name="consent_mode" id="consent_mode">
                                    <option value="required" <?= ($activeConfig && $activeConfig->consent_mode == 'required') ? 'selected' : 'selected' ?>>
                                        Obrigatório (LGPD compliant)
                                    </option>
                                    <option value="optional" <?= ($activeConfig && $activeConfig->consent_mode == 'optional') ? 'selected' : '' ?>>
                                        Opcional
                                    </option>
                                </select>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <?= $activeConfig ? 'Atualizar Configuração' : 'Criar Pixel' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab: Código -->
            <div id="tab-snippet" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h3>Código do Pixel</h3>
                        <?php if ($activeConfig): ?>
                            <p style="margin-top: 8px;">
                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #d1fae5; color: #065f46; border-radius: 6px; font-size: 0.875rem; font-weight: 600;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    Pixel ID: <?= $activeConfig->id ?> - Status: <?= ucfirst($activeConfig->status) ?>
                                </span>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($activeConfig): ?>
                            <?php $snippet = $activeConfig->generatePixelSnippet(); ?>

                            <h4>Código para inserir no site</h4>
                            <p>Cole este código antes da tag <code>&lt;/head&gt;</code> do seu site:</p>
                            <div class="code-block">
                                <textarea readonly onclick="this.select()"><?= htmlspecialchars($snippet['snippet']) ?></textarea>
                                <button onclick="copyToClipboard(this.previousElementSibling)" class="btn btn-secondary">Copiar</button>
                            </div>

                            <h4>Tracking manual de eventos</h4>
                            <p>Para rastrear eventos customizados, use o JavaScript:</p>
                            <div class="code-examples">
                                <h5>Lead:</h5>
                                <div class="code-block">
                                    <code>PixelBR.trackLead({ email: 'user@example.com', phone: '+5511999999999' });</code>
                                    <button onclick="copyToClipboard(this.previousElementSibling)" class="btn btn-secondary">Copiar</button>
                                </div>

                                <h5>Compra:</h5>
                                <div class="code-block">
                                    <code>PixelBR.trackPurchase({ 
  value: 197.00, 
  currency: 'BRL', 
  order_id: 'ORDER-123',
  email: 'user@example.com'
});</code>
                                    <button onclick="copyToClipboard(this.previousElementSibling)" class="btn btn-secondary">Copiar</button>
                                </div>
                            </div>

                        <?php else: ?>
                            <p>Configure seu pixel primeiro para gerar o código.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tab: Bridges -->
            <div id="tab-bridges" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h3>CAPI Bridges</h3>
                        <p>Configurações para envio de eventos para plataformas de anúncios</p>
                    </div>
                    <div class="card-body">
                        <?php if ($activeConfig): ?>
                        <form method="POST" action="<?= BASE_URL ?>/pixel/save">
                            <input type="hidden" name="action" value="save_bridges">
                            <input type="hidden" name="config_id" value="<?= $activeConfig->id ?>">

                            <!-- Facebook/Meta -->
                            <div class="bridge-section">
                                <h4>🔵 Meta (Facebook)</h4>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="facebook_pixel_id">Pixel ID</label>
                                        <input type="text" name="facebook_pixel_id" id="facebook_pixel_id"
                                               value="<?= htmlspecialchars($activeConfig->facebook_pixel_id ?? '') ?>"
                                               placeholder="123456789012345">
                                    </div>
                                    <div class="form-group">
                                        <label for="facebook_access_token">Access Token</label>
                                        <input type="password" name="facebook_access_token" id="facebook_access_token"
                                               value="<?= htmlspecialchars($activeConfig->facebook_access_token ?? '') ?>"
                                               placeholder="EAA...">
                                    </div>
                                </div>
                                <?php if (isset($bridgeStatus['facebook'])): ?>
                                    <div class="bridge-status success">
                                        ✅ Últimos 30 dias: <?= $bridgeStatus['facebook']['successful_sends'] ?>/<?= $bridgeStatus['facebook']['total_attempts'] ?> eventos enviados
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Google -->
                            <div class="bridge-section">
                                <h4>🔴 Google Ads</h4>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="google_conversion_id">Conversion ID</label>
                                        <input type="text" name="google_conversion_id" id="google_conversion_id"
                                               value="<?= htmlspecialchars($activeConfig->google_conversion_id ?? '') ?>"
                                               placeholder="AW-123456789">
                                    </div>
                                    <div class="form-group">
                                        <label for="google_conversion_label">Conversion Label</label>
                                        <input type="text" name="google_conversion_label" id="google_conversion_label"
                                               value="<?= htmlspecialchars($activeConfig->google_conversion_label ?? '') ?>"
                                               placeholder="abcdefghijklmnopqr">
                                    </div>
                                </div>
                                <?php if (isset($bridgeStatus['google'])): ?>
                                    <div class="bridge-status success">
                                        ✅ Últimos 30 dias: <?= $bridgeStatus['google']['successful_sends'] ?>/<?= $bridgeStatus['google']['total_attempts'] ?> eventos enviados
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- TikTok -->
                            <div class="bridge-section">
                                <h4>⚫ TikTok</h4>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="tiktok_pixel_code">Pixel Code</label>
                                        <input type="text" name="tiktok_pixel_code" id="tiktok_pixel_code"
                                               value="<?= htmlspecialchars($activeConfig->tiktok_pixel_code ?? '') ?>"
                                               placeholder="C4A...">
                                    </div>
                                    <div class="form-group">
                                        <label for="tiktok_access_token">Access Token</label>
                                        <input type="password" name="tiktok_access_token" id="tiktok_access_token"
                                               value="<?= htmlspecialchars($activeConfig->tiktok_access_token ?? '') ?>"
                                               placeholder="act_...">
                                    </div>
                                </div>
                                <?php if (isset($bridgeStatus['tiktok'])): ?>
                                    <div class="bridge-status success">
                                        ✅ Últimos 30 dias: <?= $bridgeStatus['tiktok']['successful_sends'] ?>/<?= $bridgeStatus['tiktok']['total_attempts'] ?> eventos enviados
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Salvar Bridges</button>
                            </div>
                        </form>
                        <?php else: ?>
                            <p>Configure seu pixel primeiro para habilitar os bridges.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tab: Eventos -->
            <div id="tab-events" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h3>Eventos Recentes</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($activeConfig): ?>
                            <div id="events-table">
                                <p>Carregando eventos...</p>
                            </div>
                        <?php else: ?>
                            <p>Configure seu pixel primeiro para ver os eventos.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            
            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.classList.add('active');

            if (tabName === 'events' && <?= $activeConfig ? 'true' : 'false' ?>) {
                loadEvents();
            }
        }

        function copyToClipboard(element) {
            element.select();
            document.execCommand('copy');
            
            const btn = element.nextElementSibling;
            const originalText = btn.textContent;
            btn.textContent = 'Copiado!';
            setTimeout(() => btn.textContent = originalText, 2000);
        }

        function loadEvents() {
            fetch('<?= BASE_URL ?>/pixel/events')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('events-table').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('events-table').innerHTML = '<p>Erro ao carregar eventos.</p>';
                });
        }
    </script>

    <style>
        :root {
            --pixel-primary: #3b82f6;
            --pixel-primary-hover: #2563eb;
            --pixel-success: #10b981;
            --pixel-warning: #f59e0b;
            --pixel-danger: #ef4444;
            --pixel-gray-50: #f9fafb;
            --pixel-gray-100: #f3f4f6;
            --pixel-gray-200: #e5e7eb;
            --pixel-gray-300: #d1d5db;
            --pixel-gray-500: #6b7280;
            --pixel-gray-700: #374151;
            --pixel-gray-900: #111827;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        /* Tabs Container */
        .tabs {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
            overflow: hidden;
        }

        /* Tab Buttons */
        .tab-buttons {
            display: flex;
            border-bottom: 2px solid var(--pixel-gray-200);
            background: var(--pixel-gray-50);
            flex-wrap: wrap;
        }

        .tab-button {
            padding: 1rem 1.75rem;
            border: none;
            background: transparent;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--pixel-gray-500);
            transition: all 0.2s ease;
            position: relative;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .tab-button:hover {
            background: white;
            color: var(--pixel-primary);
        }

        .tab-button.active {
            border-bottom-color: var(--pixel-primary);
            color: var(--pixel-primary);
            background: white;
        }

        /* Tab Content */
        .tab-content {
            display: none;
            padding: 2rem;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Card Styling */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--pixel-gray-200);
            background: var(--pixel-gray-50);
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--pixel-gray-900);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .card-header p {
            margin: 0.5rem 0 0;
            font-size: 0.875rem;
            color: var(--pixel-gray-500);
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-body h4 {
            margin: 1.5rem 0 0.75rem;
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--pixel-gray-900);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .card-body h4:first-child {
            margin-top: 0;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--pixel-gray-700);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--pixel-gray-300);
            border-radius: 8px;
            font-size: 0.9375rem;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--pixel-primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        /* Code Blocks */
        .code-block {
            position: relative;
            margin: 1.5rem 0;
        }

        .code-block textarea,
        .code-block code {
            background: var(--pixel-gray-900);
            color: #10b981;
            border: 1px solid var(--pixel-gray-700);
            border-radius: 8px;
            padding: 1.25rem;
            font-family: 'Courier New', Consolas, Monaco, monospace;
            font-size: 0.875rem;
            width: 100%;
            min-height: 120px;
            line-height: 1.6;
        }

        .code-block code {
            display: block;
            white-space: pre;
            overflow-x: auto;
        }

        .code-block button {
            margin-top: 0.75rem;
        }

        .code-examples {
            margin-top: 2rem;
        }

        .code-examples h5 {
            margin: 1.5rem 0 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            color: var(--pixel-gray-700);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* Bridge Sections */
        .bridge-section {
            margin: 1.75rem 0;
            padding: 1.75rem;
            border: 2px solid var(--pixel-gray-200);
            border-radius: 12px;
            background: var(--pixel-gray-50);
        }

        .bridge-section h4 {
            margin: 0 0 1.25rem;
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--pixel-gray-900);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .bridge-status {
            margin-top: 1rem;
            padding: 0.875rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .bridge-status.success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--pixel-success);
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9375rem;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: var(--pixel-primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--pixel-primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
        }

        .btn-secondary {
            background: var(--pixel-gray-200);
            color: var(--pixel-gray-700);
        }

        .btn-secondary:hover {
            background: var(--pixel-gray-300);
        }

        .btn-success {
            background: var(--pixel-success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--pixel-gray-200);
        }

        /* Alerts */
        .alert {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9375rem;
            font-weight: 500;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--pixel-success);
        }

        .alert-success svg {
            flex-shrink: 0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid var(--pixel-danger);
        }

        .alert-error svg {
            flex-shrink: 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .tab-buttons {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .tab-button {
                white-space: nowrap;
                padding: 0.875rem 1.25rem;
                font-size: 0.875rem;
            }

            .alert {
                font-size: 0.875rem;
                padding: 0.875rem 1rem;
            }
        }
    </style>
        </main>
    </div>
</body>
</html>