<?php
/**
 * API: Buscar tarefas por módulo
 * Retorna lista de tarefas disponíveis para cada módulo
 */

require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

try {
    verificarLogin();
    
    $modulo = $_GET['modulo'] ?? '';
    
    if (empty($modulo)) {
        throw new Exception('Módulo não especificado');
    }
    
    $pdo = conectarDB();
    
    // Por enquanto, retornar tarefas mockadas
    $tarefas_por_modulo = [
        'PLANEJAMENTO' => [
            ['id' => 1, 'ordem' => 1, 'nome_tarefa' => 'Análise de Demanda', 'descricao' => 'Verificar necessidade e justificativa da contratação'],
            ['id' => 2, 'ordem' => 2, 'nome_tarefa' => 'Elaboração do DFD', 'descricao' => 'Preparar Documento de Formalização da Demanda'],
            ['id' => 3, 'ordem' => 3, 'nome_tarefa' => 'Aprovação Orçamentária', 'descricao' => 'Validar disponibilidade orçamentária'],
            ['id' => 4, 'ordem' => 4, 'nome_tarefa' => 'Encaminhamento para Licitação', 'descricao' => 'Enviar processo para módulo de licitações']
        ],
        'LICITACAO' => [
            ['id' => 5, 'ordem' => 1, 'nome_tarefa' => 'Elaboração do Edital', 'descricao' => 'Preparar minuta do edital de licitação'],
            ['id' => 6, 'ordem' => 2, 'nome_tarefa' => 'Análise Jurídica', 'descricao' => 'Revisão jurídica do edital'],
            ['id' => 7, 'ordem' => 3, 'nome_tarefa' => 'Publicação', 'descricao' => 'Publicar edital nos meios oficiais'],
            ['id' => 8, 'ordem' => 4, 'nome_tarefa' => 'Condução do Pregão', 'descricao' => 'Realizar sessão pública do pregão']
        ],
        'QUALIFICACAO' => [
            ['id' => 9, 'ordem' => 1, 'nome_tarefa' => 'Análise Documental', 'descricao' => 'Verificar documentação dos fornecedores'],
            ['id' => 10, 'ordem' => 2, 'nome_tarefa' => 'Avaliação Técnica', 'descricao' => 'Avaliar capacidade técnica'],
            ['id' => 11, 'ordem' => 3, 'nome_tarefa' => 'Verificação de Idoneidade', 'descricao' => 'Consultar órgãos de controle'],
            ['id' => 12, 'ordem' => 4, 'nome_tarefa' => 'Emissão de Parecer', 'descricao' => 'Elaborar parecer de qualificação']
        ],
        'CONTRATOS' => [
            ['id' => 13, 'ordem' => 1, 'nome_tarefa' => 'Elaboração de Contrato', 'descricao' => 'Preparar minuta do contrato'],
            ['id' => 14, 'ordem' => 2, 'nome_tarefa' => 'Assinatura', 'descricao' => 'Colher assinaturas das partes'],
            ['id' => 15, 'ordem' => 3, 'nome_tarefa' => 'Publicação', 'descricao' => 'Publicar extrato do contrato'],
            ['id' => 16, 'ordem' => 4, 'nome_tarefa' => 'Gestão e Fiscalização', 'descricao' => 'Acompanhar execução contratual']
        ]
    ];
    
    $tarefas = $tarefas_por_modulo[$modulo] ?? [];
    
    echo json_encode($tarefas);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>