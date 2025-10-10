<?php
/**
 * API Endpoint - Criar Link de Pagamento MercadoPago
 */

require_once '../config/app.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Verificar se tem credenciais do MercadoPago
if (empty(MP_ACCESS_TOKEN)) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Credenciais do MercadoPago não configuradas'
    ]);
    exit;
}

try {
    // Receber dados
    $input = json_decode(file_get_contents('php://input'), true);
    
    $plan = $input['plan'] ?? 'starter';
    $amount = floatval($input['amount'] ?? 79.00);
    $user_id = intval($input['user_id'] ?? 0);
    
    // Verificar usuário logado
    if (!$user_id) {
        throw new Exception('Usuário não autenticado');
    }
    
    // Buscar dados do usuário
    $db = new Database();
    $conn = $db->connect();
    
    $query = "SELECT id, name, email FROM users WHERE id = :user_id LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $user = $stmt->fetch();
    if (!$user) {
        throw new Exception('Usuário não encontrado');
    }
    
    // Preparar dados para o MercadoPago
    $preference_data = [
        'items' => [[
            'title' => 'Mercado Afiliado - Plano ' . ucfirst($plan),
            'description' => 'Assinatura mensal do plano ' . ucfirst($plan),
            'quantity' => 1,
            'currency_id' => 'BRL',
            'unit_price' => $amount
        ]],
        'payer' => [
            'name' => $user['name'],
            'email' => $user['email']
        ],
        'back_urls' => [
            'success' => BASE_URL . '/payment/success?status=approved',
            'failure' => BASE_URL . '/payment/success?status=rejected',
            'pending' => BASE_URL . '/payment/success?status=pending'
        ],
        'auto_return' => 'approved',
        'external_reference' => 'user_' . $user_id . '_plan_' . $plan . '_' . time(),
        'notification_url' => BASE_URL . '/webhook/mercadopago',
        'statement_descriptor' => 'MERCADO AFILIADO',
        'expires' => true,
        'expiration_date_from' => date('c'),
        'expiration_date_to' => date('c', strtotime('+1 hour'))
    ];
    
    // Fazer requisição para MercadoPago
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.mercadopago.com/checkout/preferences',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . MP_ACCESS_TOKEN,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($preference_data),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);
    
    if ($curl_error) {
        throw new Exception('Erro de conexão: ' . $curl_error);
    }
    
    if ($http_code !== 201) {
        error_log("MercadoPago API Error - HTTP $http_code: $response");
        throw new Exception('Erro na API do MercadoPago (HTTP ' . $http_code . ')');
    }
    
    $mp_response = json_decode($response, true);
    
    if (!$mp_response || !isset($mp_response['init_point'])) {
        throw new Exception('Resposta inválida do MercadoPago');
    }
    
    // Log da transação
    error_log("Payment created for user $user_id: " . $mp_response['id']);
    
    // Salvar no banco (opcional - para tracking)
    try {
        $query = "INSERT INTO payment_intents (user_id, preference_id, amount, plan_name, status, created_at) 
                  VALUES (:user_id, :preference_id, :amount, :plan_name, 'pending', NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':preference_id', $mp_response['id']);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':plan_name', $plan);
        $stmt->execute();
    } catch (Exception $e) {
        // Se der erro no banco, continua (não é crítico)
        error_log("Error saving payment intent: " . $e->getMessage());
    }
    
    // Resposta de sucesso
    echo json_encode([
        'success' => true,
        'payment_url' => $mp_response['init_point'],
        'preference_id' => $mp_response['id'],
        'external_reference' => $preference_data['external_reference']
    ]);
    
} catch (Exception $e) {
    error_log("Create payment error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}