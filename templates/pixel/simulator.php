<?php
/**
 * Simulador de Eventos do Pixel BR
 * Interface avançada para testar e monitorar eventos do pixel
 */
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

// Buscar configuração ativa do pixel
$pixelConfig = new PixelConfiguration($conn);
$activeConfig = null;

if ($pixelConfig->readActiveByUserId($user_data['id'])) {
    $activeConfig = $pixelConfig;
} else {
    // Buscar o mais recente
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

// Processar ação de simulação
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'simulate_event') {
    header('Content-Type: application/json');
    
    $eventType = $_POST['event_type'] ?? '';
    $eventData = json_decode($_POST['event_data'] ?? '{}', true);
    
    // Simular envio do evento
    $response = [
        'success' => true,
        'event_type' => $eventType,
        'event_data' => $eventData,
        'timestamp' => time(),
        'message' => "Evento '$eventType' simulado com sucesso",
        'config_id' => $activeConfig ? $activeConfig->id : null
    ];
    
    echo json_encode($response);
    exit;
}

// Buscar eventos recentes se disponível
$recentEvents = [];
$tableExists = false;

if ($activeConfig) {
    try {
        // Verificar se a tabela pixel_events existe
        $checkTable = $conn->query("SHOW TABLES LIKE 'pixel_events'");
        $tableExists = $checkTable->rowCount() > 0;
        
        if ($tableExists) {
            // Tentar buscar eventos
            $query = "SELECT * FROM pixel_events WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 20";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_data['id']);
            $stmt->execute();
            $recentEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        // Se houver qualquer erro, continuar sem eventos
        $recentEvents = [];
        $tableExists = false;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulador de Eventos - Pixel BR</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        /* Layout com sidebar */
        .app-layout {
            display: flex;
            min-height: 100vh;
            background: #f8fafc;
        }

        /* Mobile Menu Toggle */
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

        .nav-badge.scale {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

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

        .plan-scale {
            color: #667eea;
            font-weight: 600;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-x: hidden;
        }

        /* Estilos específicos do simulador */
        .simulator-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .simulator-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .simulator-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .simulator-header p {
            color: #6b7280;
            font-size: 1.1rem;
        }

        .simulator-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .simulator-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #f1f5f9;
        }

        .simulator-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .event-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .event-btn {
            padding: 1rem;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            background: white;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            font-weight: 600;
        }

        .event-btn:hover {
            border-color: var(--color-primary);
            background: #fffbeb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
        }

        .event-btn.active {
            border-color: var(--color-primary);
            background: var(--color-primary);
            color: #1f2937;
        }

        .event-icon {
            font-size: 2rem;
            display: block;
            margin-bottom: 0.5rem;
        }

        .event-form {
            display: none;
        }

        .event-form.active {
            display: block;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-input, .form-textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
            font-family: 'Consolas', 'Monaco', monospace;
        }

        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        .monitor-container {
            grid-column: 1 / -1;
        }

        .monitor-tabs {
            display: flex;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }

        .monitor-tab {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #6b7280;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .monitor-tab.active {
            color: var(--color-primary);
            border-bottom-color: var(--color-primary);
        }

        .monitor-content {
            display: none;
        }

        .monitor-content.active {
            display: block;
        }

        .realtime-log {
            background: #1f2937;
            border-radius: 8px;
            padding: 1.5rem;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.85rem;
        }

        .log-entry {
            margin-bottom: 0.75rem;
            padding: 0.5rem;
            border-radius: 4px;
            border-left: 4px solid transparent;
        }

        .log-success {
            color: #10b981;
            background: rgba(16, 185, 129, 0.1);
            border-left-color: #10b981;
        }

        .log-error {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
            border-left-color: #ef4444;
        }

        .log-info {
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
            border-left-color: #3b82f6;
        }

        .log-timestamp {
            color: #9ca3af;
            margin-right: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            border: 2px solid #e5e7eb;
            transition: all 0.2s ease;
        }

        .stat-item:hover {
            border-color: var(--color-primary);
            background: #fffbeb;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            display: block;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .events-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .events-table th,
        .events-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .events-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
        }

        .events-table tbody tr:hover {
            background: #f8fafc;
        }

        .event-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .event-badge.page_view {
            background: #dbeafe;
            color: #1e40af;
        }

        .event-badge.lead {
            background: #d1fae5;
            color: #065f46;
        }

        .event-badge.purchase {
            background: #fef3c7;
            color: #92400e;
        }

        .btn {
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

        .btn-primary:hover {
            background: var(--color-primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #374151;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }

        .btn-block {
            width: 100%;
        }

        @media (max-width: 768px) {
            .simulator-grid {
                grid-template-columns: 1fr;
            }
            
            .event-buttons {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
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
                    <h2>🧪 Pixel BR Scale</h2>
                    <span class="sidebar-subtitle">Advanced Testing Suite</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <h3 class="nav-section-title">PIXEL BR</h3>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/pixel" class="nav-link">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="3"/>
                                    <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1m11-7a2 2 0 0 1 2 2v.01M12 21a2 2 0 0 1-2-2v-.01M21 12a2 2 0 0 1-2 2h-.01M3 12a2 2 0 0 1 2-2h-.01"/>
                                </svg>
                                <span>Setup & Configuração</span>
                            </a>
                        </li>
                        <li class="nav-item active">
                            <a href="<?= BASE_URL ?>/pixel/simulator" class="nav-link">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="3"/>
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                </svg>
                                <span>Simulador Avançado</span>
                                <div class="nav-badge scale">SCALE</div>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" onclick="window.open('/pixel-test.html', '_blank')" class="nav-link">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14,2 14,8 20,8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                    <polyline points="10,9 9,9 8,9"/>
                                </svg>
                                <span>Página de Teste</span>
                                <div class="nav-badge">📄</div>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <h3 class="nav-section-title">ANALYTICS</h3>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/pixel/events" class="nav-link">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 3v18h18"/>
                                    <path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/>
                                </svg>
                                <span>Eventos em Tempo Real</span>
                                <?php if ($activeConfig && ($activeConfig->getEventsSummary(30)['total_events'] ?? 0) > 0): ?>
                                    <div class="nav-badge live">LIVE</div>
                                <?php endif; ?>
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
                                <span>Integrações</span>
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
                        <div class="user-plan plan-scale">Plano Scale</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="simulator-container">
                <!-- Header -->
                <div class="simulator-header">
                    <h1>🧪 Simulador de Eventos do Pixel BR</h1>
                    <p>Interface avançada para testar e monitorar eventos do sistema de tracking</p>
                    <?php if (!$activeConfig): ?>
                        <div style="background: #fef3c7; color: #92400e; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                            ⚠️ Nenhum pixel configurado. <a href="<?= BASE_URL ?>/pixel">Configure seu pixel primeiro</a>.
                        </div>
                    <?php endif; ?>
                </div>

        <!-- Simulator Grid -->
        <div class="simulator-grid">
            <!-- Event Simulator -->
            <div class="simulator-card">
                <h3>🎯 Simulador de Eventos</h3>
                
                <!-- Event Type Buttons -->
                <div class="event-buttons">
                    <button class="event-btn active" data-event="page_view">
                        <span class="event-icon">👁️</span>
                        Page View
                    </button>
                    <button class="event-btn" data-event="lead">
                        <span class="event-icon">👤</span>
                        Lead
                    </button>
                    <button class="event-btn" data-event="purchase">
                        <span class="event-icon">💰</span>
                        Purchase
                    </button>
                    <button class="event-btn" data-event="custom">
                        <span class="event-icon">⚙️</span>
                        Custom
                    </button>
                </div>

                <!-- Event Forms -->
                <form id="eventForm">
                    <!-- Page View Form -->
                    <div class="event-form active" data-form="page_view">
                        <div class="form-group">
                            <label class="form-label">URL da Página:</label>
                            <input type="url" name="page_url" class="form-input" value="https://exemplo.com/pagina-teste" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Referrer:</label>
                            <input type="url" name="referrer" class="form-input" value="https://google.com">
                        </div>
                    </div>

                    <!-- Lead Form -->
                    <div class="event-form" data-form="lead">
                        <div class="form-group">
                            <label class="form-label">Email:</label>
                            <input type="email" name="lead_email" class="form-input" value="teste@exemplo.com" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Telefone:</label>
                            <input type="tel" name="lead_phone" class="form-input" value="+5511999999999">
                        </div>
                        <div class="form-group">
                            <label class="form-label">ID do Produto:</label>
                            <input type="text" name="product_id" class="form-input" value="PRODUTO_123">
                        </div>
                    </div>

                    <!-- Purchase Form -->
                    <div class="event-form" data-form="purchase">
                        <div class="form-group">
                            <label class="form-label">Valor (R$):</label>
                            <input type="number" name="purchase_value" class="form-input" value="197.00" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ID do Pedido:</label>
                            <input type="text" name="order_id" class="form-input" value="PEDIDO-<?= time() ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email:</label>
                            <input type="email" name="purchase_email" class="form-input" value="cliente@exemplo.com" required>
                        </div>
                    </div>

                    <!-- Custom Event Form -->
                    <div class="event-form" data-form="custom">
                        <div class="form-group">
                            <label class="form-label">Nome do Evento:</label>
                            <input type="text" name="event_name" class="form-input" value="evento_personalizado" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Dados JSON:</label>
                            <textarea name="custom_data" class="form-textarea" placeholder='{"chave": "valor", "numero": 123}'>{
  "test_mode": true,
  "simulator": "pixel_br",
  "timestamp": "<?= date('c') ?>"
}</textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        🚀 Simular Evento
                    </button>
                </form>
            </div>

            <!-- Quick Actions -->
            <div class="simulator-card">
                <h3>⚡ Ações Rápidas</h3>
                
                <button class="btn btn-secondary btn-block" onclick="sendBatchEvents()" style="margin-bottom: 1rem;">
                    📊 Enviar Lote de Eventos
                </button>
                
                <button class="btn btn-secondary btn-block" onclick="testConsentFlow()" style="margin-bottom: 1rem;">
                    🍪 Testar Fluxo LGPD
                </button>
                
                <button class="btn btn-secondary btn-block" onclick="clearSimulatorLogs()" style="margin-bottom: 1rem;">
                    🗑️ Limpar Logs
                </button>
                
                <button class="btn btn-secondary btn-block" onclick="exportTestData()">
                    💾 Exportar Dados de Teste
                </button>
            </div>
        </div>

        <!-- Monitor -->
        <div class="simulator-card monitor-container">
            <h3>📊 Monitor em Tempo Real</h3>
            
            <div class="monitor-tabs">
                <button class="monitor-tab active" data-tab="realtime">Logs em Tempo Real</button>
                <button class="monitor-tab" data-tab="stats">Estatísticas</button>
                <button class="monitor-tab" data-tab="events">Eventos Recentes</button>
            </div>

            <!-- Real-time Logs -->
            <div class="monitor-content active" data-content="realtime">
                <div class="realtime-log" id="realtimeLog">
                    <div class="log-entry log-info">
                        <span class="log-timestamp">[<?= date('H:i:s') ?>]</span>
                        🧪 Simulador carregado - Pronto para testes
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="monitor-content" data-content="stats">
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-number" id="totalSimulated">0</span>
                        <div class="stat-label">Total Simulado</div>
       