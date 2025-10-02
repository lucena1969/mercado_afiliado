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

if ($pixelConfig->readActiveByUserId($user_data['id'])) {
    $activeConfig = $pixelConfig;
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
</head>
<body>
    <div class="container">
        <header class="page-header">
            <h1>🎯 Pixel BR</h1>
            <p>Sistema de tracking compatível com LGPD</p>
        </header>

        <?php if ($activeConfig): ?>
        <div class="stats-grid">
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
                                <button type="submit" class="btn btn-primary">Salvar Configuração</button>
                                <?php if ($activeConfig && $activeConfig->status !== 'active'): ?>
                                    <button type="submit" name="action" value="activate" class="btn btn-success">Ativar Pixel</button>
                                <?php endif; ?>
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .tabs {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .tab-buttons {
            display: flex;
            border-bottom: 1px solid #e5e7eb;
        }

        .tab-button {
            padding: 1rem 1.5rem;
            border: none;
            background: none;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }

        .tab-button.active {
            border-bottom-color: var(--color-primary);
            color: var(--color-primary);
        }

        .tab-content {
            display: none;
            padding: 2rem;
        }

        .tab-content.active {
            display: block;
        }

        .code-block {
            position: relative;
            margin: 1rem 0;
        }

        .code-block textarea,
        .code-block code {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 1rem;
            font-family: monospace;
            font-size: 0.9rem;
            width: 100%;
            min-height: 100px;
        }

        .code-block code {
            display: block;
            white-space: pre;
            overflow-x: auto;
        }

        .bridge-section {
            margin: 2rem 0;
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }

        .bridge-status {
            margin-top: 0.5rem;
            padding: 0.5rem;
            border-radius: 4px;
        }

        .bridge-status.success {
            background: #d4edda;
            color: #155724;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
    </style>
</body>
</html>