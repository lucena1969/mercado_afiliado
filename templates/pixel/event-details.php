<?php
/**
 * Detalhes de um evento específico do Pixel BR
 */
// Arquivo já foi incluído via router, então config já foi carregado

require_once __DIR__ . '/../../app/controllers/AuthController.php';

$auth = new AuthController();
$auth->requireAuth();

$user_data = $_SESSION['user'] ?? null;
if (!$user_data || !isset($_GET['event_id'])) {
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$query = "SELECT 
            pe.*,
            p.name as product_name,
            i.name as integration_name,
            i.platform as integration_platform
          FROM pixel_events pe
          LEFT JOIN products p ON pe.product_id = p.id
          LEFT JOIN integrations i ON pe.integration_id = i.id
          WHERE pe.event_id = :event_id AND pe.user_id = :user_id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':event_id', $_GET['event_id']);
$stmt->bindParam(':user_id', $user_data['id']);
$stmt->execute();

$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "<p>Evento não encontrado.</p>";
    exit;
}

$eventTime = new DateTime();
$eventTime->setTimestamp($event['event_time']);

$userData = json_decode($event['user_data_json'] ?? '{}', true);
$customData = json_decode($event['custom_data_json'] ?? '{}', true);
?>

<div class="event-details">
    <div class="detail-section">
        <h4>📊 Informações Básicas</h4>
        <div class="detail-grid">
            <div><strong>ID do Evento:</strong> <?= htmlspecialchars($event['event_id']) ?></div>
            <div><strong>Tipo:</strong> <span class="event-badge event-<?= $event['event_name'] ?>"><?= ucfirst(str_replace('_', ' ', $event['event_name'])) ?></span></div>
            <div><strong>Data/Hora:</strong> <?= $eventTime->format('d/m/Y H:i:s') ?></div>
            <div><strong>Consentimento:</strong> 
                <span class="consent-badge consent-<?= $event['consent_status'] ?>">
                    <?= $event['consent_status'] === 'granted' ? '✓ Concedido' : '✗ Negado' ?>
                </span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h4>🌐 Origem</h4>
        <div class="detail-grid">
            <?php if ($event['source_url']): ?>
                <div><strong>URL:</strong> <a href="<?= htmlspecialchars($event['source_url']) ?>" target="_blank"><?= htmlspecialchars($event['source_url']) ?></a></div>
            <?php endif; ?>
            
            <?php if ($event['referrer_url']): ?>
                <div><strong>Referrer:</strong> <?= htmlspecialchars($event['referrer_url']) ?></div>
            <?php endif; ?>
            
            <?php if ($event['integration_name']): ?>
                <div><strong>Integração:</strong> <?= htmlspecialchars($event['integration_name']) ?> (<?= ucfirst($event['integration_platform']) ?>)</div>
            <?php endif; ?>
            
            <?php if ($event['product_name']): ?>
                <div><strong>Produto:</strong> <?= htmlspecialchars($event['product_name']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($event['utm_source'] || $event['utm_medium'] || $event['utm_campaign']): ?>
    <div class="detail-section">
        <h4>🎯 UTMs</h4>
        <div class="detail-grid">
            <?php if ($event['utm_source']): ?>
                <div><strong>Source:</strong> <code><?= htmlspecialchars($event['utm_source']) ?></code></div>
            <?php endif; ?>
            
            <?php if ($event['utm_medium']): ?>
                <div><strong>Medium:</strong> <code><?= htmlspecialchars($event['utm_medium']) ?></code></div>
            <?php endif; ?>
            
            <?php if ($event['utm_campaign']): ?>
                <div><strong>Campaign:</strong> <code><?= htmlspecialchars($event['utm_campaign']) ?></code></div>
            <?php endif; ?>
            
            <?php if ($event['utm_content']): ?>
                <div><strong>Content:</strong> <code><?= htmlspecialchars($event['utm_content']) ?></code></div>
            <?php endif; ?>
            
            <?php if ($event['utm_term']): ?>
                <div><strong>Term:</strong> <code><?= htmlspecialchars($event['utm_term']) ?></code></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($userData)): ?>
    <div class="detail-section">
        <h4>👤 Dados do Usuário</h4>
        <div class="detail-grid">
            <?php foreach ($userData as $key => $value): ?>
                <?php if (!empty($value)): ?>
                    <div><strong><?= ucfirst($key) ?>:</strong> 
                        <?php if (in_array($key, ['em', 'ph'])): ?>
                            <code>[HASH] <?= substr($value, 0, 8) ?>...</code>
                        <?php else: ?>
                            <?= htmlspecialchars($value) ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($customData)): ?>
    <div class="detail-section">
        <h4>⚡ Dados Customizados</h4>
        <?php if (isset($customData['value']) && $customData['value'] > 0): ?>
            <div class="highlight-value">
                💰 <strong>Valor: R$ <?= number_format($customData['value'], 2, ',', '.') ?></strong>
                <?php if (isset($customData['currency'])): ?>
                    (<?= htmlspecialchars($customData['currency']) ?>)
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="json-data">
            <pre><?= json_encode($customData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
        </div>
    </div>
    <?php endif; ?>

    <div class="detail-section">
        <h4>🔧 Dados Técnicos</h4>
        <div class="detail-grid">
            <div><strong>IP:</strong> <?= htmlspecialchars($event['ip_address'] ?? 'N/A') ?></div>
            <div><strong>User Agent:</strong> 
                <small><?= htmlspecialchars(substr($event['user_agent'] ?? 'N/A', 0, 100)) ?><?= strlen($event['user_agent'] ?? '') > 100 ? '...' : '' ?></small>
            </div>
            <div><strong>Criado em:</strong> <?= $event['created_at'] ?></div>
        </div>
    </div>

    <?php
    // Verificar logs de bridge para este evento
    $bridgeQuery = "SELECT * FROM bridge_logs WHERE pixel_event_id = :pixel_event_id ORDER BY created_at DESC";
    $bridgeStmt = $conn->prepare($bridgeQuery);
    $bridgeStmt->bindParam(':pixel_event_id', $event['id']);
    $bridgeStmt->execute();
    $bridgeLogs = $bridgeStmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php if (!empty($bridgeLogs)): ?>
    <div class="detail-section">
        <h4>🌉 Logs de Bridge</h4>
        <div class="bridge-logs">
            <?php foreach ($bridgeLogs as $log): ?>
                <div class="bridge-log-item status-<?= $log['status'] ?>">
                    <div class="bridge-header">
                        <strong><?= ucfirst($log['platform']) ?></strong>
                        <span class="bridge-status"><?= ucfirst($log['status']) ?></span>
                        <small><?= $log['created_at'] ?></small>
                    </div>
                    
                    <?php if ($log['status'] === 'failed' && $log['error_message']): ?>
                        <div class="bridge-error">
                            ❌ <?= htmlspecialchars($log['error_message']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($log['http_status_code']): ?>
                        <div class="bridge-http">
                            HTTP <?= $log['http_status_code'] ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    .event-details {
        font-size: 0.9rem;
    }
    
    .detail-section {
        margin: 1.5rem 0;
        padding: 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
    }
    
    .detail-section h4 {
        margin: 0 0 1rem 0;
        color: #374151;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 0.5rem;
    }
    
    .detail-grid {
        display: grid;
        gap: 0.5rem;
    }
    
    .detail-grid > div {
        padding: 0.25rem 0;
    }
    
    .highlight-value {
        background: #fef3c7;
        padding: 0.75rem;
        border-radius: 4px;
        margin-bottom: 1rem;
        font-size: 1.1rem;
    }
    
    .json-data {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 4px;
        padding: 0.75rem;
        margin-top: 0.5rem;
    }
    
    .json-data pre {
        margin: 0;
        font-size: 0.8rem;
        white-space: pre-wrap;
        word-break: break-word;
    }
    
    .bridge-logs {
        space-y: 0.5rem;
    }
    
    .bridge-log-item {
        padding: 0.75rem;
        border-radius: 4px;
        border-left: 4px solid #6b7280;
    }
    
    .bridge-log-item.status-sent {
        background: #d4edda;
        border-left-color: #10b981;
    }
    
    .bridge-log-item.status-failed {
        background: #f8d7da;
        border-left-color: #ef4444;
    }
    
    .bridge-log-item.status-pending {
        background: #fff3cd;
        border-left-color: #f59e0b;
    }
    
    .bridge-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }
    
    .bridge-status {
        padding: 0.2rem 0.5rem;
        border-radius: 3px;
        background: #e5e7eb;
        font-size: 0.75rem;
        text-transform: uppercase;
        font-weight: 600;
    }
    
    .bridge-error {
        color: #dc2626;
        font-size: 0.85rem;
        margin-top: 0.5rem;
    }
    
    .bridge-http {
        font-size: 0.8rem;
        color: #6b7280;
    }
</style>