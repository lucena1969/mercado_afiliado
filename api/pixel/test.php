<?php
/**
 * Endpoint de Teste para Pixel BR
 * Permite testar a funcionalidade do pixel sem afetar dados de produção
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Responder a requests OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir configurações
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/PixelConfiguration.php';

// Função para log de debug
function debugLog($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = [
        'timestamp' => $timestamp,
        'message' => $message,
        'data' => $data
    ];
    
    // Em produção, você pode salvar isso em um arquivo de log
    error_log('[PIXEL-TEST] ' . json_encode($logEntry));
    
    return $logEntry;
}

// Função para simular resposta das APIs (Meta, Google, TikTok)
function simulateApiResponse($platform, $eventData) {
    // Simular diferentes cenários
    $scenarios = ['success', 'success', 'success', 'error']; // 75% sucesso
    $scenario = $scenarios[array_rand($scenarios)];
    
    // Simular delay de rede
    usleep(rand(100000, 500000)); // 100-500ms
    
    if ($scenario === 'success') {
        return [
            'success' => true,
            'platform' => $platform,
            'message' => "Evento enviado com sucesso para {$platform}",
            'event_id' => uniqid("{$platform}_"),
            'response_time' => rand(150, 800) . 'ms'
        ];
    } else {
        return [
            'success' => false,
            'platform' => $platform,
            'error' => "Erro simulado na API do {$platform}",
            'error_code' => rand(400, 500),
            'response_time' => rand(1000, 3000) . 'ms'
        ];
    }
}

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido. Use POST.');
    }

    // Obter dados do evento
    $input = file_get_contents('php://input');
    $eventData = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Dados JSON inválidos: ' . json_last_error_msg());
    }

    // Validar dados básicos
    $requiredFields = ['event_name', 'event_time'];
    foreach ($requiredFields as $field) {
        if (!isset($eventData[$field])) {
            throw new Exception("Campo obrigatório ausente: {$field}");
        }
    }

    // Log do evento recebido
    $receiveLog = debugLog('Evento recebido para teste', $eventData);

    // Simular processamento do pixel
    $processingResults = [
        'event_processed' => true,
        'event_id' => $eventData['event_id'] ?? uniqid('test_'),
        'event_name' => $eventData['event_name'],
        'timestamp' => time(),
        'test_mode' => true
    ];

    // Validar configuração (se fornecida)
    $configValidation = ['valid' => true, 'message' => 'Configuração válida'];
    
    if (isset($eventData['user_id']) || isset($eventData['integration_id'])) {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            // Verificar se existe configuração para o usuário
            $query = "SELECT * FROM pixel_configurations WHERE user_id = :user_id OR integration_id = :integration_id LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $eventData['user_id'] ?? '');
            $stmt->bindParam(':integration_id', $eventData['integration_id'] ?? '');
            $stmt->execute();
            
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($config) {
                $processingResults['config_found'] = true;
                $processingResults['config_name'] = $config['pixel_name'];
                $processingResults['config_status'] = $config['status'];
            } else {
                $configValidation = [
                    'valid' => false,
                    'message' => 'Configuração não encontrada - usando configuração padrão de teste'
                ];
            }
            
        } catch (Exception $e) {
            $configValidation = [
                'valid' => false,
                'message' => 'Erro ao validar configuração: ' . $e->getMessage()
            ];
        }
    }

    // Simular verificação de consentimento LGPD
    $consentCheck = [
        'consent_required' => true,
        'consent_status' => $eventData['consent'] ?? 'granted',
        'data_collection_allowed' => ($eventData['consent'] ?? 'granted') === 'granted'
    ];

    // Simular processamento de dados pessoais
    $dataProcessing = [
        'email_hashed' => isset($eventData['user_data']['em']),
        'phone_hashed' => isset($eventData['user_data']['ph']),
        'user_agent_collected' => isset($eventData['user_data']['ua']),
        'personal_data_processed' => $consentCheck['data_collection_allowed']
    ];

    // Simular envio para plataformas (se aplicável)
    $platformResults = [];
    
    if ($consentCheck['data_collection_allowed']) {
        // Simular Meta/Facebook
        $platformResults['meta'] = simulateApiResponse('Meta', $eventData);
        
        // Simular Google Ads
        $platformResults['google'] = simulateApiResponse('Google Ads', $eventData);
        
        // Simular TikTok
        $platformResults['tiktok'] = simulateApiResponse('TikTok', $eventData);
    } else {
        $platformResults = [
            'meta' => ['skipped' => true, 'reason' => 'Consentimento não concedido'],
            'google' => ['skipped' => true, 'reason' => 'Consentimento não concedido'],
            'tiktok' => ['skipped' => true, 'reason' => 'Consentimento não concedido']
        ];
    }

    // Simular armazenamento no queue local (navegador)
    $queueSimulation = [
        'added_to_queue' => true,
        'queue_position' => rand(1, 5),
        'estimated_send_time' => '< 1 segundo',
        'retry_attempts' => 0
    ];

    // Calcular estatísticas
    $stats = [
        'processing_time' => rand(50, 200) . 'ms',
        'total_events_today' => rand(100, 1000),
        'success_rate' => rand(85, 99) . '%',
        'avg_response_time' => rand(200, 800) . 'ms'
    ];

    // Preparar resposta completa
    $response = [
        'success' => true,
        'test_mode' => true,
        'message' => 'Evento processado com sucesso no modo de teste',
        'timestamp' => date('Y-m-d H:i:s'),
        
        'event_processing' => $processingResults,
        'config_validation' => $configValidation,
        'consent_check' => $consentCheck,
        'data_processing' => $dataProcessing,
        'platform_results' => $platformResults,
        'queue_simulation' => $queueSimulation,
        'performance_stats' => $stats,
        
        'debug_info' => [
            'received_at' => $receiveLog['timestamp'],
            'server_info' => [
                'php_version' => PHP_VERSION,
                'server_time' => date('c'),
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB'
            ],
            'request_info' => [
                'method' => $_SERVER['REQUEST_METHOD'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
                'content_length' => strlen($input) . ' bytes'
            ]
        ]
    ];

    // Log da resposta
    debugLog('Resposta de teste enviada', ['success' => true, 'event_name' => $eventData['event_name']]);

    // Retornar resposta
    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // Log do erro
    debugLog('Erro no teste do pixel', ['error' => $e->getMessage()]);
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'test_mode' => true,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'debug_info' => [
            'php_error' => $e->getTraceAsString(),
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'N/A'
        ]
    ], JSON_PRETTY_PRINT);
}
?>