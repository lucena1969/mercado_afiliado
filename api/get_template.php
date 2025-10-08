<?php
/**
 * API: Buscar dados de um template de tramitação
 * Retorna dados do template para preenchimento automático do formulário
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
    echo json_encode(['success' => false, 'message' => 'ID do template é obrigatório']);
    exit;
}

try {
    $pdo = conectarDB();
    
    // Buscar dados do template
    $sql = "SELECT * FROM tramitacoes_templates WHERE id = ? AND ativo = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $template = $stmt->fetch();
    
    if (!$template) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Template não encontrado']);
        exit;
    }
    
    // Retornar dados do template com mapeamento correto das colunas
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $template['id'],
            'nome' => $template['nome'],
            'tipo_demanda' => $template['tipo_demanda_padrao'],
            'titulo' => $template['nome'], // Usar nome como título padrão
            'descricao' => $template['descricao'],
            'tags' => $template['tags_padrao'],
            'prioridade' => $template['prioridade_padrao'],
            'modulo_origem' => $template['modulo_origem'],
            'modulo_destino' => $template['modulo_destino'],
            'cor_card' => $template['cor_padrao'],
            'prazo_dias' => $template['prazo_padrao_dias']
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Erro ao buscar template: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor'
    ]);
}
?>