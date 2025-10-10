<?php
// Arquivo: api/get_licitacao.php

// Desabilitar exibição de erros para não contaminar JSON
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once '../config.php';
require_once '../functions.php';

// Verificar se está logado
verificarLogin();

// Verificar permissão para visualizar licitações
if (!temPermissao('licitacao_visualizar')) {
    echo json_encode([
        'success' => false,
        'message' => 'Você não tem permissão para visualizar dados de licitações.'
    ]);
    exit;
}

// Definir header JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // Verificar se foi passado o ID
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('ID da licitação não fornecido ou inválido.');
    }

    $id = intval($_GET['id']);

    $pdo = conectarDB();

    // CORREÇÃO: Buscar numero_contratacao direto da tabela licitacoes + JOIN com pca_dados como backup
    $sql = "SELECT
            l.*,
            u.nome as usuario_nome,
            COALESCE(l.numero_contratacao, p.numero_contratacao) as numero_contratacao_final,
            ql.qualificacao_id,
            q.nup AS qualificacao_nup,
            q.area_demandante AS qualificacao_area,
            q.modalidade AS qualificacao_modalidade,
            q.responsavel AS qualificacao_responsavel,
            q.objeto AS qualificacao_objeto,
            q.status AS qualificacao_status,
            q.valor_estimado AS qualificacao_valor_estimado,
            q.criado_em AS qualificacao_criado_em,
            q.observacoes AS qualificacao_observacoes,
            qp.numero_contratacao AS qualificacao_pca_numero_contratacao,
            qp.numero_dfd AS qualificacao_pca_numero_dfd,
            qp.titulo_contratacao AS qualificacao_pca_titulo
        FROM licitacoes l
        LEFT JOIN usuarios u ON l.usuario_id = u.id
        LEFT JOIN pca_dados p ON l.pca_dados_id = p.id
        LEFT JOIN qualificacoes_licitacoes ql ON ql.licitacao_id = l.id AND ql.status = 'ATIVA'
        LEFT JOIN qualificacoes q ON q.id = ql.qualificacao_id
        LEFT JOIN pca_dados qp ON q.pca_dados_id = qp.id
        WHERE l.id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $licitacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$licitacao) {
        throw new Exception('Licitação não encontrada.');
    }

    $qualificacao = null;
    if (!empty($licitacao['qualificacao_id'])) {
        $status = $licitacao['qualificacao_status'] ?? '';
        $qualificacao = [
            'id' => (int)$licitacao['qualificacao_id'],
            'nup' => $licitacao['qualificacao_nup'],
            'area_demandante' => $licitacao['qualificacao_area'],
            'modalidade' => $licitacao['qualificacao_modalidade'],
            'responsavel' => $licitacao['qualificacao_responsavel'],
            'objeto' => $licitacao['qualificacao_objeto'],
            'status' => $status,
            'valor_estimado' => $licitacao['qualificacao_valor_estimado'] !== null ? (float)$licitacao['qualificacao_valor_estimado'] : null,
            'valor_estimado_formatado' => $licitacao['qualificacao_valor_estimado'] !== null
                ? 'R$ ' . number_format((float)$licitacao['qualificacao_valor_estimado'], 2, ',', '.')
                : null,
            'criado_em' => $licitacao['qualificacao_criado_em'],
            'criado_em_formatado' => $licitacao['qualificacao_criado_em']
                ? date('d/m/Y', strtotime($licitacao['qualificacao_criado_em']))
                : null,
            'observacoes' => $licitacao['qualificacao_observacoes'],
            'pca_vinculado' => !empty($licitacao['qualificacao_pca_numero_contratacao']),
            'pca_dados' => [
                'numero_contratacao' => $licitacao['qualificacao_pca_numero_contratacao'],
                'numero_dfd' => $licitacao['qualificacao_pca_numero_dfd'],
                'titulo' => $licitacao['qualificacao_pca_titulo'],
            ],
            'busca_info' => [
                'status_classe' => strtolower(str_replace(' ', '_', $status)),
            ],
        ];
    }

    $licitacao['qualificacao'] = $qualificacao;

    // Retornar dados
    echo json_encode([
        'success' => true,
        'data' => $licitacao
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Log do erro
    error_log("Erro na API get_licitacao: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
