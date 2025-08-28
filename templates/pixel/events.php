<?php
/**
 * Carregamento de eventos para o Pixel BR
 */
// Arquivo já foi incluído via router, então config já foi carregado
require_once __DIR__ . '/../../app/controllers/AuthController.php';

$auth = new AuthController();
$auth->requireAuth();

$user_data = $_SESSION['user'] ?? null;
if (!$user_data) {
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$limit = $_GET['limit'] ?? 50;
$offset = $_GET['offset'] ?? 0;

$query = "SELECT 
            pe.id,
            pe.event_name,
            pe.event_time,
            pe.event_id,
            pe.source_url,
            pe.utm_campaign,
            pe.utm_source,
            pe.utm_medium,
            pe.custom_data_json,
            pe.consent_status,
            pe.created_at,
            p.name as product_name
          FROM pixel_events pe
          LEFT JOIN products p ON pe.product_id = p.id
          WHERE pe.user_id = :user_id
          ORDER BY pe.created_at DESC
          LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_data['id'], PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($events)): ?>
    <div style="text-align: center; padding: 2rem;">
        <p>Nenhum evento encontrado nos últimos dias.</p>
        <p><small>Os eventos aparecerão aqui assim que o pixel for instalado no seu site.</small></p>
    </div>
<?php else: ?>
    <div class="events-table">
        <table>
            <thead>
                <tr>
                    <th>Evento</th>
                    <th>Data/Hora</th>
                    <th>Origem</th>
                    <th>UTM Campaign</th>
                    <th>Valor</th>
                    <th>Consent</th>
                    <th>Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                    <?php 
                    $customData = json_decode($event['custom_data_json'] ?? '{}', true);
                    $eventTime = new DateTime();
                    $eventTime->setTimestamp($event['event_time']);
                    ?>
                    <tr>
                        <td>
                            <span class="event-badge event-<?= $event['event_name'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $event['event_name'])) ?>
                            </span>
                        </td>
                        <td>
                            <div><?= $eventTime->format('d/m/Y') ?></div>
                            <small><?= $eventTime->format('H:i:s') ?></small>
                        </td>
                        <td>
                            <?php if ($event['source_url']): ?>
                                <a href="<?= htmlspecialchars($event['source_url']) ?>" target="_blank" class="url-link">
                                    <?= htmlspecialchars(parse_url($event['source_url'], PHP_URL_HOST)) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($event['utm_campaign']): ?>
                                <code><?= htmlspecialchars($event['utm_campaign']) ?></code>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($customData['value']) && $customData['value'] > 0): ?>
                                <strong>R$ <?= number_format($customData['value'], 2, ',', '.') ?></strong>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="consent-badge consent-<?= $event['consent_status'] ?>">
                                <?= $event['consent_status'] === 'granted' ? '✓' : '✗' ?>
                            </span>
                        </td>
                        <td>
                            <button onclick="showEventDetails('<?= $event['event_id'] ?>')" class="btn btn-sm">
                                Ver
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (count($events) >= $limit): ?>
            <div class="load-more">
                <button onclick="loadMoreEvents()" class="btn btn-secondary">Carregar mais eventos</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal de detalhes do evento -->
    <div id="event-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalhes do Evento</h3>
                <button onclick="closeEventModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body" id="event-details">
                <!-- Conteúdo carregado via AJAX -->
            </div>
        </div>
    </div>

    <script>
        let eventsOffset = <?= $offset + count($events) ?>;

        function loadMoreEvents() {
            fetch(`<?= BASE_URL ?>/pixel/events?offset=${eventsOffset}&limit=<?= $limit ?>`)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newRows = doc.querySelectorAll('tbody tr');
                    
                    const tbody = document.querySelector('.events-table tbody');
                    newRows.forEach(row => tbody.appendChild(row));
                    
                    eventsOffset += newRows.length;
                    
                    if (newRows.length < <?= $limit ?>) {
                        document.querySelector('.load-more').style.display = 'none';
                    }
                });
        }

        function showEventDetails(eventId) {
            fetch(`<?= BASE_URL ?>/pixel/event-details?event_id=${eventId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('event-details').innerHTML = html;
                    document.getElementById('event-modal').style.display = 'flex';
                });
        }

        function closeEventModal() {
            document.getElementById('event-modal').style.display = 'none';
        }
    </script>

    <style>
        .events-table {
            overflow-x: auto;
        }

        .events-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .events-table th,
        .events-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .events-table th {
            font-weight: 600;
            color: #374151;
            background: #f9fafb;
        }

        .event-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .event-page_view { background: #dbeafe; color: #1e40af; }
        .event-click { background: #fef3c7; color: #d97706; }
        .event-lead { background: #d1fae5; color: #059669; }
        .event-purchase { background: #fce7f3; color: #be185d; }
        .event-custom { background: #f3e8ff; color: #7c3aed; }

        .consent-badge {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            text-align: center;
            line-height: 20px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .consent-granted { background: #10b981; color: white; }
        .consent-denied { background: #ef4444; color: white; }

        .url-link {
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.875rem;
        }

        .url-link:hover {
            text-decoration: underline;
        }

        .text-muted {
            color: #6b7280;
        }

        .load-more {
            text-align: center;
            padding: 1rem;
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            max-height: 80%;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
        }

        .modal-body {
            padding: 1rem;
        }
    </style>
<?php endif; ?>