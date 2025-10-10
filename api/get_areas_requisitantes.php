<?php
// API para carregar áreas requisitantes dinamicamente
include_once '../config.php';
include_once '../functions.php';

// Configurar headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Configurar e iniciar sessão segura
configurarSessaoSegura();

// Verificar login
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não logado'
    ]);
    exit;
}

try {
    $pdo = conectarDB();

    // Receber parâmetros
    $ano_selecionado = $_GET['ano'] ?? date('Y');

    // Obter tabela PCA
    $tabela_pca = getPcaTableName($ano_selecionado);

    // Buscar importações concluídas do ano
    $stmt_importacoes = $pdo->prepare("
        SELECT GROUP_CONCAT(id) as ids
        FROM pca_importacoes
        WHERE ano_pca = ? AND status = 'concluido'
    ");
    $stmt_importacoes->execute([$ano_selecionado]);
    $importacoes_result = $stmt_importacoes->fetch();
    $importacoes_ids_str = $importacoes_result['ids'] ?? '';

    if (empty($importacoes_ids_str)) {
        echo json_encode([
            'success' => true,
            'areas' => []
        ]);
        exit;
    }

    $importacoes_ids = explode(',', $importacoes_ids_str);
    $where_importacoes = "importacao_id IN (" . implode(',', $importacoes_ids) . ")";

    // Consulta para áreas requisitantes
    $sql = "
        SELECT
            area_requisitante,
            COUNT(*) as quantidade_registros,
            COUNT(DISTINCT numero_contratacao) as quantidade_contratacoes
        FROM {$tabela_pca}
        WHERE {$where_importacoes}
        AND area_requisitante IS NOT NULL
        AND area_requisitante != ''
        AND area_requisitante != 'NULL'
        GROUP BY area_requisitante
        ORDER BY quantidade_contratacoes DESC, quantidade_registros DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $areas = $stmt->fetchAll();

    // Formatar resultado
    $areas_formatadas = [];
    foreach ($areas as $area) {
        $areas_formatadas[] = [
            'codigo' => $area['area_requisitante'],
            'nome' => $area['area_requisitante'],
            'quantidade_registros' => $area['quantidade_registros'],
            'quantidade_contratacoes' => $area['quantidade_contratacoes']
        ];
    }

    echo json_encode([
        'success' => true,
        'ano' => $ano_selecionado,
        'total_areas' => count($areas_formatadas),
        'areas' => $areas_formatadas
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar áreas: ' . $e->getMessage()
    ]);
}
?>