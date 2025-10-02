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

// Primeiro, tentar buscar pixel ativo
if ($pixelConfig->readActiveByUserId($user_data['id'])) {
    $activeConfig = $pixelConfig;
} else {
    // Se não há pixel ativo, buscar o mais recente (incluindo status 'testing')
    $query = "SELECT * FROM pixel_configurations WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_data['id']);
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $pixelConfig = new PixelConfiguration($conn);
        foreach($row as $key => $value) {
            $pixelConfig->$key = $value;
        }
        $activeConfig = $pixelConfig;
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
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>

    <div class="app-layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <h2><i data-lucide="activity" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle;"></i> Pixel BR</h2>
                    <span class="sidebar-subtitle">Marketing Intelligence</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <h3 class="nav-section-title">CONFIGURAÇÃO</h3>
                    <ul class="nav-menu">
                        <li class="nav-item active">
                            <a href="#config" onclick="showTab('config')" class="nav-link">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="3"/>
                                    <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1m11-7a2 2 0 0 1 2 2v.01M12 21a2 2 0 0 1-2-2v-.01M21 12a2 2 0 0 1-2 2h-.01M3 12a2 2 0 0 1 2-2h-.01"/>
                                </svg>
                                <span>Setup Inicial</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#snippet" onclick="showTab('snippet')" class="nav-link">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="16,18 22,12 16,6"/>
                                    <polyline points="8,6 2,12 8,18"/>
                                </svg>
                                <span>Código & Implementação</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#bridges" onclick="showTab('bridges')" class="nav-link">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14,2 14,8 20,8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                                <span>Integrações CAPI</span>
                                <div class="nav-badge">3</div>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <h3 class="nav-section-title">ANALYTICS</h3>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="#events" onclick="showTab('events')" class="nav-link">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 3v18h18"/>
                                    <path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/>
                                </svg>
                                <span>Eventos em Tempo Real</span>
                                <?php if ($activeConfig && ($eventsSummary['total_events'] ?? 0) > 0): ?>
                                    <div class="nav-badge live">LIVE</div>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/pixel/simulator" class="nav-link">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="3"/>
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                </svg>
                                <span>Simulador de Testes</span>
                                <div class="nav-badge">🧪</div>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/dashboard" class="nav-link">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <path d="M9 9h6v6H9z"/>
                                </svg>
                                <span>Dashboard Principal</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <h3 class="nav-section-title">CONTA</h3>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/integrations" class="nav-link">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                    <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                                </svg>
                                <span>Outras Integrações</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/logout" class="nav-link text-red">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                    <polyline points="16,17 21,12 16,7"/>
                                    <line x1="21" y1="12" x2="9" y2="12"/>
                                </svg>
                                <span>Sair</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Sidebar Footer -->
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user_data['name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?= htmlspecialchars($user_data['name'] ?? 'Usuário') ?></div>
                        <div class="user-plan">Plano Premium</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>🎯 Configuração do Pixel BR</h1>
                <p>Sistema de tracking compatível com LGPD</p>
            </header>

            <!-- Messages Alert System -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success" id="success-alert">
                    <div class="alert-content">
                        <svg class="alert-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22,4 12,14.01 9,11.01"/>
                        </svg>
                        <span><?= htmlspecialchars($_SESSION['success_message']) ?></span>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.remove()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error" id="error-alert">
                    <div class="alert-content">
                        <svg class="alert-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                        <span><?= nl2br(htmlspecialchars($_SESSION['error_message'])) ?></span>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.remove()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Status Alert for Pixel -->
            <?php if ($activeConfig && $activeConfig->status !== 'active'): ?>
                <div class="alert alert-warning">
                    <div class="alert-content">
                        <svg class="alert-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5l-6.928-12c-.77-.833-2.694-.833-3.464 0l-6.928 12c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <span>
                            <strong>Pixel em modo <?= ucfirst($activeConfig->status) ?></strong><br>
                            <small>Para começar a coletar dados em produção, ative seu pixel após configurar as integrações.</small>
                        </span>
                    </div>
                    <div style="margin-left: auto;">
                        <?php if ($activeConfig->status === 'testing'): ?>
                            <form method="POST" action="<?= BASE_URL ?>/pixel/save" style="display: inline;">
                                <input type="hidden" name="action" value="activate">
                                <input type="hidden" name="config_id" value="<?= $activeConfig->id ?>">
                                <button type="submit" class="btn btn-success btn-sm">🚀 Ativar Pixel</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($activeConfig && $activeConfig->status === 'active'): ?>
                <div class="alert alert-success">
                    <div class="alert-content">
                        <svg class="alert-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22,4 12,14.01 9,11.01"/>
                        </svg>
                        <span>
                            <strong>🎯 Pixel Ativo e Funcionando!</strong><br>
                            <small>Seu pixel está coletando dados em tempo real.</small>
                        </span>
                    </div>
                </div>
            <?php endif; ?>

        <?php if ($activeConfig): ?>
        <div class="stats-grid">
            <div class="stat-card total-events">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= number_format($eventsSummary['total_events'] ?? 0) ?></div>
                    <div class="stat-label">Eventos Totais</div>
                    <div class="stat-period">últimos 30 dias</div>
                </div>
                <div class="stat-trend">
                    <span class="trend-indicator positive">↗</span>
                </div>
            </div>

            <div class="stat-card page-views">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= number_format($eventsSummary['page_views'] ?? 0) ?></div>
                    <div class="stat-label">Visualizações</div>
                    <div class="stat-period">páginas vistas</div>
                </div>
                <div class="stat-trend">
                    <span class="trend-indicator positive">↗</span>
                </div>
            </div>

            <div class="stat-card leads">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="m22 2-5 10-4-3-3 8"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= number_format($eventsSummary['leads'] ?? 0) ?></div>
                    <div class="stat-label">Leads Captados</div>
                    <div class="stat-period">contatos gerados</div>
                </div>
                <div class="stat-trend">
                    <span class="trend-indicator positive">↗</span>
                </div>
            </div>

            <div class="stat-card purchases">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="8" cy="21" r="1"/>
                        <circle cx="19" cy="21" r="1"/>
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= number_format($eventsSummary['purchases'] ?? 0) ?></div>
                    <div class="stat-label">Compras Realizadas</div>
                    <div class="stat-period">conversões confirmadas</div>
                </div>
                <div class="stat-trend">
                    <span class="trend-indicator positive">↗</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab-buttons">
                <button class="tab-button active" onclick="showTab('config')" data-tab="config">
                    <div class="tab-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1m11-7a2 2 0 0 1 2 2v.01M12 21a2 2 0 0 1-2-2v-.01M21 12a2 2 0 0 1-2 2h-.01M3 12a2 2 0 0 1 2-2h-.01"/>
                        </svg>
                    </div>
                    <div class="tab-content-text">
                        <span class="tab-title">Configuração</span>
                        <span class="tab-desc">Setup inicial do pixel</span>
                    </div>
                </button>
                
                <button class="tab-button" onclick="showTab('snippet')" data-tab="snippet">
                    <div class="tab-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="16,18 22,12 16,6"/>
                            <polyline points="8,6 2,12 8,18"/>
                        </svg>
                    </div>
                    <div class="tab-content-text">
                        <span class="tab-title">Código</span>
                        <span class="tab-desc">Implementação no site</span>
                    </div>
                </button>
                
                <button class="tab-button" onclick="showTab('bridges')" data-tab="bridges">
                    <div class="tab-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14,2 14,8 20,8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                    </div>
                    <div class="tab-content-text">
                        <span class="tab-title">Integrações</span>
                        <span class="tab-desc">Meta, Google, TikTok</span>
                    </div>
                </button>
                
                <button class="tab-button" onclick="showTab('events')" data-tab="events">
                    <div class="tab-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 3v18h18"/>
                            <path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/>
                        </svg>
                    </div>
                    <div class="tab-content-text">
                        <span class="tab-title">Eventos</span>
                        <span class="tab-desc">Atividade em tempo real</span>
                    </div>
                </button>
            </div>

            <!-- Tab: Configuração -->
            <div id="tab-config" class="tab-content active">
                <div class="card">
                    <div class="card-header">
                        <h3>Configuração do Pixel</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= BASE_URL ?>/pixel/save" id="pixelConfigForm">
                            <input type="hidden" name="action" value="save_config">
                            <?php if ($activeConfig): ?>
                                <input type="hidden" name="config_id" value="<?= $activeConfig->id ?>">
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="pixel_name" class="form-label">
                                    Nome do Pixel
                                    <span class="required-indicator">*</span>
                                </label>
                                <div class="input-wrapper">
                                    <input type="text" name="pixel_name" id="pixel_name" 
                                           class="form-input"
                                           value="<?= htmlspecialchars($activeConfig->pixel_name ?? '') ?>" 
                                           placeholder="Ex: Pixel Principal"
                                           required>
                                    <div class="input-validation">
                                        <span class="validation-icon"></span>
                                        <span class="validation-message"></span>
                                    </div>
                                </div>
                                <small class="field-help">Escolha um nome descritivo para identificar seu pixel</small>
                            </div>

                            <div class="form-group">
                                <label for="integration_id" class="form-label">
                                    Integração Associada
                                    <span class="optional-indicator">(opcional)</span>
                                </label>
                                <div class="select-wrapper">
                                    <select name="integration_id" id="integration_id" class="form-select">
                                        <option value="">Selecione uma integração</option>
                                        <?php
                                        $integrationQuery = "SELECT id, name, platform FROM integrations WHERE user_id = :user_id AND status = 'active'";
                                        $integrationStmt = $conn->prepare($integrationQuery);
                                        $integrationStmt->bindParam(':user_id', $user_data['id']);
                                        $integrationStmt->execute();
                                        
                                        while ($integration = $integrationStmt->fetch(PDO::FETCH_ASSOC)): ?>
                                            <option value="<?= $integration['id'] ?>" 
                                                    data-platform="<?= $integration['platform'] ?>"
                                                    <?= ($activeConfig && $activeConfig->integration_id == $integration['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($integration['name']) ?> (<?= ucfirst($integration['platform']) ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="select-icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="6,9 12,15 18,9"/>
                                        </svg>
                                    </div>
                                </div>
                                <small class="field-help">Conecte seu pixel a uma integração existente do seu dashboard</small>
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
                                <label for="consent_mode" class="form-label">
                                    Modo de Consentimento LGPD
                                    <span class="required-indicator">*</span>
                                </label>
                                <div class="select-wrapper">
                                    <select name="consent_mode" id="consent_mode" class="form-select">
                                        <option value="required" 
                                                <?= ($activeConfig && $activeConfig->consent_mode == 'required') ? 'selected' : 'selected' ?>>
                                            🛡️ Obrigatório (Recomendado)
                                        </option>
                                        <option value="optional" 
                                                <?= ($activeConfig && $activeConfig->consent_mode == 'optional') ? 'selected' : '' ?>>
                                            ⚠️ Opcional (Não recomendado)
                                        </option>
                                    </select>
                                    <div class="select-icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="6,9 12,15 18,9"/>
                                        </svg>
                                    </div>
                                </div>
                                <small class="field-help">
                                    Modo obrigatório garante conformidade total com a LGPD
                                    <a href="#" onclick="showLGPDModal()" class="lgpd-learn-more">Saiba mais</a>
                                </small>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary" id="saveConfigBtn">
                                    <span class="btn-text">Salvar Configuração</span>
                                    <span class="btn-loading" style="display: none;">
                                        <svg class="animate-spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                                        </svg>
                                        Salvando...
                                    </span>
                                </button>
                                <?php if ($activeConfig && $activeConfig->status !== 'active'): ?>
                                    <button type="submit" name="action" value="activate" class="btn btn-success" id="activateBtn">
                                        <span class="btn-text">🚀 Ativar Pixel</span>
                                        <span class="btn-loading" style="display: none;">
                                            <svg class="animate-spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                                            </svg>
                                            Ativando...
                                        </span>
                                    </button>
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
                                        <label for="facebook_access_token" class="form-label">
                                            Access Token
                                            <span class="required-indicator">*</span>
                                        </label>
                                        <div class="input-wrapper password-input">
                                            <input type="password" name="facebook_access_token" id="facebook_access_token"
                                                   class="form-input"
                                                   value="<?= htmlspecialchars($activeConfig->facebook_access_token ?? '') ?>"
                                                   placeholder="EAA...">
                                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('facebook_access_token')">
                                                <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                    <circle cx="12" cy="12" r="3"/>
                                                </svg>
                                                <svg class="eye-off-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                                </svg>
                                            </button>
                                        </div>
                                        <small class="field-help">Token de acesso da API do Facebook</small>
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
                                        <label for="tiktok_access_token" class="form-label">
                                            Access Token
                                            <span class="required-indicator">*</span>
                                        </label>
                                        <div class="input-wrapper password-input">
                                            <input type="password" name="tiktok_access_token" id="tiktok_access_token"
                                                   class="form-input"
                                                   value="<?= htmlspecialchars($activeConfig->tiktok_access_token ?? '') ?>"
                                                   placeholder="act_...">
                                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('tiktok_access_token')">
                                                <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                    <circle cx="12" cy="12" r="3"/>
                                                </svg>
                                                <svg class="eye-off-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                                </svg>
                                            </button>
                                        </div>
                                        <small class="field-help">Token de acesso da API do TikTok</small>
                                    </div>
                                </div>
                                <?php if (isset($bridgeStatus['tiktok'])): ?>
                                    <div class="bridge-status success">
                                        ✅ Últimos 30 dias: <?= $bridgeStatus['tiktok']['successful_sends'] ?>/<?= $bridgeStatus['tiktok']['total_attempts'] ?> eventos enviados
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary" id="saveBridgesBtn">
                                    <span class="btn-text">🔗 Salvar Integrações</span>
                                    <span class="btn-loading" style="display: none;">
                                        <svg class="animate-spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                                        </svg>
                                        Salvando...
                                    </span>
                                </button>
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

    <!-- Modal LGPD -->
    <div id="lgpdModal" class="lgpd-modal">
        <div class="lgpd-modal-overlay" onclick="hideLGPDModal()"></div>
        <div class="lgpd-modal-content">
            <div class="lgpd-modal-header">
                <h3>🛡️ Modo de Consentimento LGPD</h3>
                <button class="lgpd-modal-close" onclick="hideLGPDModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="lgpd-modal-body">
                <div class="lgpd-section">
                    <h4>Por que usar o Modo Obrigatório?</h4>
                    <div class="lgpd-advantages">
                        <div class="lgpd-advantage">
                            <div class="lgpd-advantage-icon">⚖️</div>
                            <div class="lgpd-advantage-content">
                                <strong>Conformidade Legal Total</strong>
                                <p>Garante que seu site está 100% em conformidade com a Lei Geral de Proteção de Dados (LGPD), evitando multas de até 2% do faturamento.</p>
                            </div>
                        </div>
                        <div class="lgpd-advantage">
                            <div class="lgpd-advantage-icon">🔒</div>
                            <div class="lgpd-advantage-content">
                                <strong>Proteção de Dados</strong>
                                <p>Coleta dados pessoais apenas após consentimento explícito, respeitando a privacidade dos usuários e construindo confiança.</p>
                            </div>
                        </div>
                        <div class="lgpd-advantage">
                            <div class="lgpd-advantage-icon">📈</div>
                            <div class="lgpd-advantage-content">
                                <strong>Dados de Maior Qualidade</strong>
                                <p>Usuários conscientes que dão consentimento tendem a ser mais engajados, resultando em dados mais valiosos e conversões melhores.</p>
                            </div>
                        </div>
                        <div class="lgpd-advantage">
                            <div class="lgpd-advantage-icon">🏆</div>
                            <div class="lgpd-advantage-content">
                                <strong>Vantagem Competitiva</strong>
                                <p>Sites conformes com LGPD transmitem maior credibilidade e profissionalismo, diferenciando-se da concorrência.</p>
                            </div>
                        </div>
                        <div class="lgpd-advantage">
                            <div class="lgpd-advantage-icon">🚫</div>
                            <div class="lgpd-advantage-content">
                                <strong>Evita Penalidades</strong>
                                <p>Previne advertências, multas e até mesmo a proibição de coleta de dados pela Autoridade Nacional de Proteção de Dados (ANPD).</p>
                            </div>
                        </div>
                        <div class="lgpd-advantage">
                            <div class="lgpd-advantage-icon">🤝</div>
                            <div class="lgpd-advantage-content">
                                <strong>Transparência e Confiança</strong>
                                <p>Demonstra respeito aos direitos dos usuários, aumentando a confiança na marca e melhorando a reputação online.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="lgpd-section">
                    <h4>Como Funciona?</h4>
                    <div class="lgpd-how-it-works">
                        <div class="lgpd-step">
                            <div class="lgpd-step-number">1</div>
                            <div class="lgpd-step-content">
                                <strong>Banner de Consentimento</strong>
                                <p>Exibe um banner claro informando sobre o uso de cookies e pixels de rastreamento.</p>
                            </div>
                        </div>
                        <div class="lgpd-step">
                            <div class="lgpd-step-number">2</div>
                            <div class="lgpd-step-content">
                                <strong>Consentimento Explícito</strong>
                                <p>O usuário deve clicar em "Aceitar" para autorizar a coleta de dados pessoais.</p>
                            </div>
                        </div>
                        <div class="lgpd-step">
                            <div class="lgpd-step-number">3</div>
                            <div class="lgpd-step-content">
                                <strong>Ativação do Pixel</strong>
                                <p>Apenas após o consentimento, o pixel começa a funcionar e coletar dados.</p>
                            </div>
                        </div>
                        <div class="lgpd-step">
                            <div class="lgpd-step-number">4</div>
                            <div class="lgpd-step-content">
                                <strong>Respeitamos a Escolha</strong>
                                <p>Se o usuário recusar, nenhum dado pessoal é coletado, respeitando totalmente sua privacidade.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lgpd-section">
                    <div class="lgpd-warning">
                        <div class="lgpd-warning-icon">⚠️</div>
                        <div>
                            <strong>Importante:</strong> O modo opcional não garante conformidade total com a LGPD e pode expor seu negócio a riscos legais. Recomendamos fortemente o uso do modo obrigatório para máxima proteção.
                        </div>
                    </div>
                </div>
            </div>
            <div class="lgpd-modal-footer">
                <button onclick="hideLGPDModal()" class="btn btn-primary">Entendi</button>
            </div>
        </div>
    </div>

    <script>
        // Enhanced Tab Management
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // Find the correct button by data-tab attribute
            const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
            if (activeButton) {
                activeButton.classList.add('active');
            }

            if (tabName === 'events' && <?= $activeConfig ? 'true' : 'false' ?>) {
                loadEvents();
            }
        }

        // Password Toggle Functionality
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const button = input.nextElementSibling;
            const eyeIcon = button.querySelector('.eye-icon');
            const eyeOffIcon = button.querySelector('.eye-off-icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'block';
            } else {
                input.type = 'password';
                eyeIcon.style.display = 'block';
                eyeOffIcon.style.display = 'none';
            }
        }

        // Real-time Form Validation
        function validateField(field, rules) {
            const wrapper = field.closest('.input-wrapper');
            const validationIcon = wrapper?.querySelector('.validation-icon');
            const validationMessage = wrapper?.querySelector('.validation-message');
            
            if (!wrapper || !validationIcon || !validationMessage) return;

            let isValid = true;
            let message = '';

            // Required validation
            if (rules.required && !field.value.trim()) {
                isValid = false;
                message = 'Este campo é obrigatório';
            }
            
            // Min length validation
            if (rules.minLength && field.value.length > 0 && field.value.length < rules.minLength) {
                isValid = false;
                message = `Mínimo ${rules.minLength} caracteres`;
            }
            
            // Custom validation patterns
            if (rules.pattern && field.value && !rules.pattern.test(field.value)) {
                isValid = false;
                message = rules.patternMessage || 'Formato inválido';
            }

            // Update UI
            if (field.value.length === 0) {
                // Empty field - neutral state
                wrapper.classList.remove('valid', 'invalid');
                validationIcon.innerHTML = '';
                validationMessage.textContent = '';
            } else if (isValid) {
                // Valid field
                wrapper.classList.remove('invalid');
                wrapper.classList.add('valid');
                validationIcon.innerHTML = '✓';
                validationMessage.textContent = '';
            } else {
                // Invalid field
                wrapper.classList.remove('valid');
                wrapper.classList.add('invalid');
                validationIcon.innerHTML = '✕';
                validationMessage.textContent = message;
            }

            return isValid;
        }

        // Enhanced Button Loading States
        function setButtonLoading(button, isLoading) {
            const btnText = button.querySelector('.btn-text');
            const btnLoading = button.querySelector('.btn-loading');
            
            if (isLoading) {
                btnText.style.display = 'none';
                btnLoading.style.display = 'flex';
                button.disabled = true;
                button.classList.add('loading');
            } else {
                btnText.style.display = 'flex';
                btnLoading.style.display = 'none';
                button.disabled = false;
                button.classList.remove('loading');
            }
        }

        // Enhanced Form Submission with Loading States
        function handleFormSubmit(form, button) {
            setButtonLoading(button, true);
            
            // Submit form immediately (no artificial delay)
            form.submit();
        }

        // Initialize form validation and loading states
        document.addEventListener('DOMContentLoaded', function() {
            // Validation rules
            const validationRules = {
                pixel_name: {
                    required: true,
                    minLength: 3
                },
                facebook_pixel_id: {
                    pattern: /^\d{15,16}$/,
                    patternMessage: 'Deve conter 15-16 dígitos'
                },
                facebook_access_token: {
                    pattern: /^EAA/,
                    patternMessage: 'Token deve começar com EAA'
                },
                google_conversion_id: {
                    pattern: /^AW-\d+$/,
                    patternMessage: 'Formato: AW-123456789'
                },
                tiktok_pixel_code: {
                    pattern: /^C4A/,
                    patternMessage: 'Código deve começar com C4A'
                },
                tiktok_access_token: {
                    pattern: /^act_/,
                    patternMessage: 'Token deve começar com act_'
                }
            };

            // Add event listeners for real-time validation
            Object.keys(validationRules).forEach(fieldName => {
                const field = document.getElementById(fieldName);
                if (field) {
                    field.addEventListener('input', function() {
                        validateField(this, validationRules[fieldName]);
                    });
                    
                    field.addEventListener('blur', function() {
                        validateField(this, validationRules[fieldName]);
                    });
                }
            });

            // Enhanced form submission handling
            const pixelConfigForm = document.getElementById('pixelConfigForm');
            if (pixelConfigForm) {
                pixelConfigForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const submitButton = e.submitter || this.querySelector('button[type="submit"]');
                    handleFormSubmit(this, submitButton);
                });
            }

            // Handle bridges form
            const bridgesForm = document.querySelector('form[action*="pixel/save"]');
            if (bridgesForm && bridgesForm !== pixelConfigForm) {
                bridgesForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const submitButton = e.submitter || this.querySelector('button[type="submit"]');
                    handleFormSubmit(this, submitButton);
                });
            }

            // Success/Error message auto-hide
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });

        function copyToClipboard(element) {
            let textToCopy = '';
            
            // Se for textarea ou input, use o value
            if (element.tagName === 'TEXTAREA' || element.tagName === 'INPUT') {
                element.select();
                textToCopy = element.value;
            } else {
                // Para outros elementos (code, div, etc), use o textContent
                textToCopy = element.textContent || element.innerText;
            }
            
            // Usar a API moderna de clipboard se disponível
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    showCopySuccess(element);
                }).catch(() => {
                    // Fallback para método antigo
                    fallbackCopy(textToCopy, element);
                });
            } else {
                // Fallback para navegadores mais antigos
                fallbackCopy(textToCopy, element);
            }
        }
        
        function fallbackCopy(text, element) {
            // Cria um elemento temporário para copiar
            const tempTextArea = document.createElement('textarea');
            tempTextArea.value = text;
            tempTextArea.style.position = 'fixed';
            tempTextArea.style.left = '-9999px';
            tempTextArea.style.top = '0';
            document.body.appendChild(tempTextArea);
            
            tempTextArea.select();
            try {
                document.execCommand('copy');
                showCopySuccess(element);
            } catch (err) {
                console.error('Falha ao copiar texto:', err);
            }
            
            document.body.removeChild(tempTextArea);
        }
        
        function showCopySuccess(element) {
            const btn = element.nextElementSibling;
            if (btn && btn.tagName === 'BUTTON') {
                const originalText = btn.textContent;
                btn.textContent = 'Copiado!';
                btn.classList.add('copied');
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.classList.remove('copied');
                }, 2000);
            }
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

        // LGPD Modal Functions
        function showLGPDModal() {
            document.getElementById('lgpdModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function hideLGPDModal() {
            document.getElementById('lgpdModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('lgpdModal').style.display === 'flex') {
                hideLGPDModal();
            }
        });
    </script>

    <style>
        /* Enhanced Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.total-events::before { background: linear-gradient(90deg, #8b5cf6, #a855f7); }
        .stat-card.page-views::before { background: linear-gradient(90deg, #3b82f6, #2563eb); }
        .stat-card.leads::before { background: linear-gradient(90deg, #10b981, #059669); }
        .stat-card.purchases::before { background: linear-gradient(90deg, #f59e0b, #d97706); }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -4px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .total-events .stat-icon { background: #f3e8ff; color: #8b5cf6; }
        .page-views .stat-icon { background: #dbeafe; color: #3b82f6; }
        .leads .stat-icon { background: #d1fae5; color: #10b981; }
        .purchases .stat-icon { background: #fef3c7; color: #f59e0b; }

        .stat-content {
            flex: 1;
        }

        .stat-number {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
            margin-bottom: 0.125rem;
        }

        .stat-period {
            font-size: 0.8rem;
            color: #6b7280;
            font-weight: 400;
        }

        .stat-trend {
            display: flex;
            align-items: center;
        }

        .trend-indicator {
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .trend-indicator.positive {
            background: #d1fae5;
            color: #059669;
        }

        /* Enhanced Tabs */
        .tabs {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #f1f5f9;
        }

        .tab-buttons {
            display: flex;
            border-bottom: 1px solid #e5e7eb;
            padding: 0.5rem;
            gap: 0.5rem;
        }

        .tab-button {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border: none;
            background: none;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.2s ease;
            flex: 1;
            text-align: left;
            color: #6b7280;
        }

        .tab-button:hover {
            background: #f8fafc;
            color: #374151;
        }

        .tab-button.active {
            background: var(--color-primary);
            color: white;
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2);
        }

        .tab-button.active .tab-icon svg {
            color: white;
        }

        .tab-icon {
            flex-shrink: 0;
        }

        .tab-content-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .tab-title {
            font-weight: 600;
            font-size: 0.9rem;
            line-height: 1.2;
        }

        .tab-desc {
            font-size: 0.75rem;
            opacity: 0.8;
            font-weight: 400;
            line-height: 1.3;
        }

        .tab-content {
            display: none;
            padding: 2rem;
        }

        .tab-content.active {
            display: block;
        }

        /* Enhanced Form Styles */
        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .required-indicator {
            color: #ef4444;
            margin-left: 0.25rem;
        }

        .input-wrapper {
            position: relative;
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            background: #fafafa;
            font-family: 'Inter', sans-serif;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
            background: white;
        }

        .password-input .form-input {
            padding-right: 3rem;
        }

        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #6b7280;
            padding: 0.5rem;
            border-radius: 4px;
            transition: color 0.2s;
        }

        .password-toggle:hover {
            color: #374151;
            background: #f3f4f6;
        }

        .input-validation {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.375rem;
            min-height: 1.25rem;
        }

        .validation-icon {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .input-wrapper.valid .validation-icon {
            background: #10b981;
            color: white;
        }

        .input-wrapper.invalid .validation-icon {
            background: #ef4444;
            color: white;
        }

        .validation-message {
            font-size: 0.8rem;
            font-weight: 500;
        }

        .input-wrapper.valid .validation-message {
            color: #059669;
        }

        .input-wrapper.invalid .validation-message {
            color: #dc2626;
        }

        .input-wrapper.valid .form-input {
            border-color: #10b981;
            background: #f0fdf4;
        }

        .input-wrapper.invalid .form-input {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .field-help {
            display: block;
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 0.25rem;
            font-style: italic;
        }

        /* Enhanced Button States */
        .btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: var(--color-primary);
            color: #1f2937;
        }

        .btn-primary:hover:not(:disabled) {
            background: var(--color-primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(245, 158, 11, 0.3);
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover:not(:disabled) {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
        }

        .btn:disabled,
        .btn.loading {
            opacity: 0.8;
            cursor: not-allowed;
            transform: none !important;
        }

        .btn-loading {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
            border: 1px solid #6b7280;
        }

        .btn-secondary:hover:not(:disabled) {
            background: #374151;
            border-color: #374151;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(107, 114, 128, 0.3);
        }

        .btn.copied {
            background: #10b981 !important;
            border-color: #10b981 !important;
            color: white !important;
        }

        /* Spinning Animation */
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }

        /* Enhanced Bridge Sections */
        .bridge-section {
            margin: 2rem 0;
            padding: 2rem;
            border: 2px solid #f1f5f9;
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .bridge-section:hover {
            border-color: #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .bridge-section h4 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        /* Enhanced Alert System */
        .alert {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid transparent;
            animation: slideInAlert 0.3s ease-out;
        }

        @keyframes slideInAlert {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
        }

        .alert-icon {
            flex-shrink: 0;
        }

        .alert-close {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            opacity: 0.7;
            transition: all 0.2s ease;
            margin-left: 1rem;
        }

        .alert-close:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 0.2);
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border-color: #10b981;
        }

        .alert-success .alert-icon {
            color: #10b981;
        }

        .alert-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-color: #ef4444;
        }

        .alert-error .alert-icon {
            color: #ef4444;
        }

        .alert-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border-color: #3b82f6;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%);
            color: #92400e;
            border-color: #f59e0b;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1.25rem;
            }
            
            .stat-number {
                font-size: 1.75rem;
            }
            
            .tab-buttons {
                flex-direction: column;
                gap: 0.25rem;
            }
            
            .tab-button {
                justify-content: flex-start;
            }

            .bridge-section {
                padding: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr !important;
            }
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

        /* ===========================================
           SIDEBAR & LAYOUT STYLES  
           =========================================== */
        
        .app-layout {
            display: flex;
            min-height: 100vh;
            background: #f8fafc;
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            display: none;
            background: var(--color-primary);
            border: none;
            border-radius: 8px;
            padding: 0.5rem;
            color: white;
            cursor: pointer;
        }

        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: white;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.05);
            position: relative;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 2rem 1.5rem 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .sidebar-logo h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .sidebar-subtitle {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Navigation */
        .sidebar-nav {
            flex: 1;
            padding: 1rem 0;
            overflow-y: auto;
        }

        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-section-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 1.5rem 0.75rem;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 0.9rem;
            position: relative;
        }

        .nav-link:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .nav-item.active .nav-link {
            background: #f0f9ff;
            color: var(--color-primary);
            border-right: 3px solid var(--color-primary);
        }

        .nav-link.text-red {
            color: #ef4444;
        }

        .nav-link.text-red:hover {
            background: #fef2f2;
            color: #dc2626;
        }

        .nav-badge {
            background: var(--color-primary);
            color: white;
            font-size: 0.7rem;
            padding: 0.125rem 0.375rem;
            border-radius: 10px;
            font-weight: 600;
            margin-left: auto;
        }

        .nav-badge.live {
            background: #ef4444;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        /* Sidebar Footer */
        .sidebar-footer {
            padding: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--color-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            font-size: 0.9rem;
        }

        .user-name {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.9rem;
        }

        .user-plan {
            font-size: 0.75rem;
            color: #6b7280;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-x: hidden;
        }

        .main-header {
            margin-bottom: 2rem;
        }

        .main-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .main-header p {
            color: #6b7280;
            font-size: 1rem;
        }

        /* Enhanced Select Styles */
        .select-wrapper {
            position: relative;
        }

        .form-select {
            width: 100%;
            padding: 0.875rem 3rem 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
            background: #fafafa;
            color: #374151;
            cursor: pointer;
            transition: all 0.2s ease;
            appearance: none;
            font-family: 'Inter', sans-serif;
        }

        .form-select:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
            background: white;
        }

        .select-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            pointer-events: none;
            transition: transform 0.2s ease;
        }

        .form-select:focus + .select-icon {
            transform: translateY(-50%) rotate(180deg);
        }

        .optional-indicator {
            color: #6b7280;
            font-weight: 400;
            font-size: 0.85rem;
        }

        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                position: fixed;
                left: -280px;
                top: 0;
                bottom: 0;
                transition: left 0.3s ease;
                z-index: 1000;
            }

            .sidebar.mobile-open {
                left: 0;
            }

            .mobile-menu-toggle {
                display: flex;
            }

            .mobile-menu-overlay.active {
                display: block;
            }

            .main-content {
                width: 100%;
                padding: 4rem 1rem 1rem;
            }

            .main-header h1 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 4rem 0.75rem 1rem;
            }

            .stats-grid {
                margin: 0 -0.75rem 2rem;
                padding: 0 0.75rem;
            }
        }

        /* LGPD Modal Styles */
        .lgpd-learn-more {
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 600;
            margin-left: 8px;
            transition: color 0.2s ease;
        }

        .lgpd-learn-more:hover {
            color: var(--color-primary-dark);
            text-decoration: underline;
        }

        .lgpd-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 1rem;
        }

        .lgpd-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        .lgpd-modal-content {
            background: white;
            border-radius: 16px;
            max-width: 700px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            z-index: 1;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .lgpd-modal-header {
            padding: 2rem 2rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 1.5rem;
        }

        .lgpd-modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
        }

        .lgpd-modal-close {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            color: #6b7280;
            transition: all 0.2s ease;
        }

        .lgpd-modal-close:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .lgpd-modal-body {
            padding: 2rem;
        }

        .lgpd-section {
            margin-bottom: 2.5rem;
        }

        .lgpd-section:last-child {
            margin-bottom: 0;
        }

        .lgpd-section h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--color-primary);
            padding-bottom: 0.5rem;
            display: inline-block;
        }

        .lgpd-advantages {
            display: grid;
            gap: 1.5rem;
        }

        .lgpd-advantage {
            display: flex;
            gap: 1rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 12px;
            border-left: 4px solid var(--color-primary);
            transition: all 0.2s ease;
        }

        .lgpd-advantage:hover {
            background: #f1f5f9;
            transform: translateX(4px);
        }

        .lgpd-advantage-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .lgpd-advantage-content strong {
            display: block;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .lgpd-advantage-content p {
            color: #6b7280;
            font-size: 0.9rem;
            line-height: 1.5;
            margin: 0;
        }

        .lgpd-how-it-works {
            display: grid;
            gap: 1rem;
        }

        .lgpd-step {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .lgpd-step-number {
            width: 32px;
            height: 32px;
            background: var(--color-primary);
            color: #1f2937;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .lgpd-step-content strong {
            display: block;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .lgpd-step-content p {
            color: #6b7280;
            font-size: 0.9rem;
            line-height: 1.5;
            margin: 0;
        }

        .lgpd-warning {
            display: flex;
            gap: 1rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%);
            border: 1px solid #f59e0b;
            border-radius: 12px;
            color: #92400e;
        }

        .lgpd-warning-icon {
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .lgpd-modal-footer {
            padding: 0 2rem 2rem;
            display: flex;
            justify-content: center;
        }

        /* Mobile responsive for modal */
        @media (max-width: 768px) {
            .lgpd-modal {
                padding: 0.5rem;
            }

            .lgpd-modal-content {
                max-height: 95vh;
            }

            .lgpd-modal-header,
            .lgpd-modal-body,
            .lgpd-modal-footer {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }

            .lgpd-advantage,
            .lgpd-warning {
                padding: 1rem;
            }

            .lgpd-advantage {
                flex-direction: column;
                text-align: center;
            }

            .lgpd-step {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>

    <script>
        // Mobile Menu Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileMenuOverlay');

            mobileToggle.addEventListener('click', function() {
                sidebar.classList.add('mobile-open');
                overlay.classList.add('active');
            });

            overlay.addEventListener('click', function() {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
            });

            // Update sidebar navigation based on current tab
            window.updateSidebarNavigation = function(activeTabName) {
                const navItems = document.querySelectorAll('.nav-item');
                navItems.forEach(item => {
                    item.classList.remove('active');
                    const link = item.querySelector('.nav-link');
                    if (link && link.getAttribute('href') === '#' + activeTabName) {
                        item.classList.add('active');
                    }
                });
            };
        });

        // Enhanced Tab Management with Sidebar Integration
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // Find the correct button by data-tab attribute
            const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
            if (activeButton) {
                activeButton.classList.add('active');
            }

            // Update sidebar navigation
            if (window.updateSidebarNavigation) {
                window.updateSidebarNavigation(tabName);
            }

            // Close mobile menu if open
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileMenuOverlay');
            if (sidebar && overlay) {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
            }

            if (tabName === 'events' && <?= $activeConfig ? 'true' : 'false' ?>) {
                loadEvents();
            }
        }
    </script>

        </main>
    </div>
</body>
</html>