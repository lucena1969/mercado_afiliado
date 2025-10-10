<?php
/**
 * API: Buscar detalhes de uma tramitação
 * Retorna dados completos para exibição em modal
 */

require_once '../config.php';
require_once '../functions.php';

// Verificar login
verificarLogin();

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Parâmetros
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID da tramitação é obrigatório']);
    exit;
}

try {
    $pdo = conectarDB();
    
    // Buscar dados completos da tramitação
    $sql = "SELECT * FROM v_tramitacoes_kanban WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $tramitacao = $stmt->fetch();
    
    if (!$tramitacao) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tramitação não encontrada']);
        exit;
    }
    
    // Processar dados para exibição
    $tramitacao['tags_array'] = [];
    if (!empty($tramitacao['tags'])) {
        $tramitacao['tags_array'] = array_map('trim', explode(',', $tramitacao['tags']));
        $tramitacao['tags_array'] = array_filter($tramitacao['tags_array']);
    }
    
    // Formatar datas
    if ($tramitacao['prazo_limite']) {
        $prazoDate = new DateTime($tramitacao['prazo_limite']);
        $tramitacao['prazo_formatado'] = $prazoDate->format('d/m/Y H:i');
    }
    
    if ($tramitacao['criado_em']) {
        $criadoDate = new DateTime($tramitacao['criado_em']);
        $tramitacao['criado_formatado'] = $criadoDate->format('d/m/Y H:i');
    }
    
    if ($tramitacao['atualizado_em']) {
        $atualizadoDate = new DateTime($tramitacao['atualizado_em']);
        $tramitacao['atualizado_formatado'] = $atualizadoDate->format('d/m/Y H:i');
    }
    
    // Retornar dados
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $tramitacao
    ]);
    
} catch (Exception $e) {
    error_log('Erro ao buscar detalhes da tramitação: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor'
    ]);
}
?>