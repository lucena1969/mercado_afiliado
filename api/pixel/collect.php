<?php
/**
 * Pixel BR - Coletor de Eventos
 * Mercado Afiliado
 */

// Verificar se os arquivos existem antes de incluir
$config_files = [
    '../../config/app.php',
    '../../config/database.php'
];

foreach ($config_files as $file) {
    if (!file_exists($file)) {
        http_response_code(500);
        echo json_encode(['error' => 'Configuration file missing: ' . $file]);
        exit;
    }
    require_once $file;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON payload');
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    $pixelEvent = new PixelEvent($conn);
    $result = $pixelEvent->collect($input);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}

class PixelEvent {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    public function collect($eventData) {
        $eventData = $this->validateAndEnrich($eventData);
        
        if ($eventData['consent'] !== 'granted') {
            return [
                'ok' => false,
                'reason' => 'consent_denied'
            ];
        }
        
        $eventId = $this->saveEvent($eventData);
        
        $dispatchResult = [
            'event_id' => $eventId,
            'bridges_triggered' => 0
        ];
        
        if ($this->shouldDispatchToBridges($eventData)) {
            $dispatchResult['bridges_triggered'] = $this->dispatchToBridges($eventData);
        }
        
        return [
            'ok' => true,
            'dispatch' => $dispatchResult
        ];
    }
    
    private function validateAndEnrich($data) {
        $enriched = [
            'event_name' => $data['event_name'] ?? 'custom',
            'event_time' => $data['event_time'] ?? time(),
            'event_id' => $data['event_id'] ?? $this->generateEventId(),
            'user_id' => $data['user_id'] ?? null,
            'integration_id' => $data['integration_id'] ?? null,
            'product_id' => $data['product_id'] ?? null,
            'source_url' => $data['source_url'] ?? $_SERVER['HTTP_REFERER'] ?? null,
            'referrer_url' => $data['referrer_url'] ?? null,
            'utm' => $data['utm'] ?? [],
            'user_data' => $data['user_data'] ?? [],
            'custom_data' => $data['custom_data'] ?? [],
            'consent' => $data['consent'] ?? 'granted',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if (!in_array($enriched['event_name'], ['page_view', 'click', 'lead', 'purchase', 'custom'])) {
            throw new Exception('Invalid event name');
        }
        
        if (!filter_var($enriched['source_url'], FILTER_VALIDATE_URL) && $enriched['source_url'] !== null) {
            throw new Exception('Invalid source URL');
        }
        
        if (isset($enriched['user_data']['em']) && !$this->isValidHash($enriched['user_data']['em'])) {
            $enriched['user_data']['em'] = $this->hashEmail($enriched['user_data']['em']);
        }
        
        if (isset($enriched['user_data']['ph']) && !$this->isValidHash($enriched['user_data']['ph'])) {
            $enriched['user_data']['ph'] = $this->hashPhone($enriched['user_data']['ph']);
        }
        
        return $enriched;
    }
    
    private function saveEvent($eventData) {
        $sql = "INSERT INTO pixel_events (
            event_name, event_time, event_id, user_id, integration_id, product_id,
            source_url, referrer_url, utm_source, utm_medium, utm_campaign, 
            utm_content, utm_term, user_data_json, custom_data_json, 
            consent_status, ip_address, user_agent, created_at
        ) VALUES (
            :event_name, :event_time, :event_id, :user_id, :integration_id, :product_id,
            :source_url, :referrer_url, :utm_source, :utm_medium, :utm_campaign,
            :utm_content, :utm_term, :user_data_json, :custom_data_json,
            :consent_status, :ip_address, :user_agent, :created_at
        )";
        
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bindParam(':event_name', $eventData['event_name']);
        $stmt->bindParam(':event_time', $eventData['event_time']);
        $stmt->bindParam(':event_id', $eventData['event_id']);
        $stmt->bindParam(':user_id', $eventData['user_id']);
        $stmt->bindParam(':integration_id', $eventData['integration_id']);
        $stmt->bindParam(':product_id', $eventData['product_id']);
        $stmt->bindParam(':source_url', $eventData['source_url']);
        $stmt->bindParam(':referrer_url', $eventData['referrer_url']);
        $stmt->bindParam(':utm_source', $eventData['utm']['source'] ?? null);
        $stmt->bindParam(':utm_medium', $eventData['utm']['medium'] ?? null);
        $stmt->bindParam(':utm_campaign', $eventData['utm']['campaign'] ?? null);
        $stmt->bindParam(':utm_content', $eventData['utm']['content'] ?? null);
        $stmt->bindParam(':utm_term', $eventData['utm']['term'] ?? null);
        
        $userDataJson = json_encode($eventData['user_data']);
        $customDataJson = json_encode($eventData['custom_data']);
        
        $stmt->bindParam(':user_data_json', $userDataJson);
        $stmt->bindParam(':custom_data_json', $customDataJson);
        $stmt->bindParam(':consent_status', $eventData['consent']);
        $stmt->bindParam(':ip_address', $eventData['ip_address']);
        $stmt->bindParam(':user_agent', $eventData['user_agent']);
        $stmt->bindParam(':created_at', $eventData['created_at']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save event');
        }
        
        return $this->conn->lastInsertId();
    }
    
    private function shouldDispatchToBridges($eventData) {
        if (!$eventData['integration_id']) {
            return false;
        }
        
        $importantEvents = ['purchase', 'lead'];
        return in_array($eventData['event_name'], $importantEvents);
    }
    
    private function dispatchToBridges($eventData) {
        $bridgesTriggered = 0;
        
        $sql = "SELECT * FROM integrations WHERE id = :integration_id AND status = 'active'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':integration_id', $eventData['integration_id']);
        $stmt->execute();
        
        $integration = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$integration) {
            return $bridgesTriggered;
        }
        
        $config = json_decode($integration['config_json'] ?? '{}', true);
        
        if (isset($config['facebook_pixel_id']) && isset($config['facebook_access_token'])) {
            $this->dispatchToFacebook($eventData, $config);
            $bridgesTriggered++;
        }
        
        if (isset($config['google_conversion_id']) && isset($config['google_conversion_label'])) {
            $this->dispatchToGoogle($eventData, $config);
            $bridgesTriggered++;
        }
        
        if (isset($config['tiktok_pixel_code']) && isset($config['tiktok_access_token'])) {
            $this->dispatchToTikTok($eventData, $config);
            $bridgesTriggered++;
        }
        
        return $bridgesTriggered;
    }
    
    private function dispatchToFacebook($eventData, $config) {
        $payload = [
            'data' => [[
                'event_name' => $this->mapEventNameForFacebook($eventData['event_name']),
                'event_time' => $eventData['event_time'],
                'event_id' => $eventData['event_id'],
                'action_source' => 'website',
                'event_source_url' => $eventData['source_url'],
                'user_data' => array_filter([
                    'em' => $eventData['user_data']['em'] ?? null,
                    'ph' => $eventData['user_data']['ph'] ?? null,
                    'client_ip_address' => $eventData['ip_address'],
                    'client_user_agent' => $eventData['user_agent']
                ]),
                'custom_data' => $eventData['custom_data']
            ]]
        ];
        
        $this->logBridgeDispatch('facebook', $payload);
    }
    
    private function dispatchToGoogle($eventData, $config) {
        $payload = [
            'conversion_action' => $config['google_conversion_id'],
            'conversion_date_time' => date('Y-m-d H:i:s', $eventData['event_time']),
            'conversion_value' => $eventData['custom_data']['value'] ?? 0,
            'currency_code' => $eventData['custom_data']['currency'] ?? 'BRL'
        ];
        
        $this->logBridgeDispatch('google', $payload);
    }
    
    private function dispatchToTikTok($eventData, $config) {
        $payload = [
            'pixel_code' => $config['tiktok_pixel_code'],
            'event' => $this->mapEventNameForTikTok($eventData['event_name']),
            'event_id' => $eventData['event_id'],
            'timestamp' => $eventData['event_time'],
            'properties' => $eventData['custom_data']
        ];
        
        $this->logBridgeDispatch('tiktok', $payload);
    }
    
    private function logBridgeDispatch($platform, $payload) {
        $sql = "INSERT INTO bridge_logs (platform, payload_json, created_at) VALUES (:platform, :payload, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':platform', $platform);
        $stmt->bindParam(':payload', json_encode($payload));
        $stmt->execute();
    }
    
    private function mapEventNameForFacebook($eventName) {
        $map = [
            'purchase' => 'Purchase',
            'lead' => 'Lead',
            'page_view' => 'PageView',
            'click' => 'ClickButton'
        ];
        
        return $map[$eventName] ?? 'CustomEvent';
    }
    
    private function mapEventNameForTikTok($eventName) {
        $map = [
            'purchase' => 'CompletePayment',
            'lead' => 'SubmitForm',
            'page_view' => 'ViewContent',
            'click' => 'ClickButton'
        ];
        
        return $map[$eventName] ?? 'CustomEvent';
    }
    
    private function generateEventId() {
        return 'evt_' . time() . '_' . uniqid();
    }
    
    private function isValidHash($hash) {
        return is_string($hash) && preg_match('/^[a-f0-9]{64}$/i', $hash);
    }
    
    private function hashEmail($email) {
        return hash('sha256', strtolower(trim($email)));
    }
    
    private function hashPhone($phone) {
        $cleaned = preg_replace('/\D/', '', $phone);
        return hash('sha256', $cleaned);
    }
}