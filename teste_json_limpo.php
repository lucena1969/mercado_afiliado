<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'functions.php';

try {
    $pdo = conectarDB();

    // Simular os dados que vêm do JavaScript
    $ano_selecionado = '2025';
    $tabela_pca = getPcaTableName($ano_selecionado);

    echo "=== TESTE MANUAL DO CASE relatorio_execucao_pca ===\n";
    echo "Ano selecionado: $ano_selecionado\n";
    echo "Tabela PCA: $tabela_pca\n";

    // Buscar IDs das importações do ano para filtrar corretamente
    $stmt_importacoes = $pdo->prepare("
        SELECT GROUP_CONCAT(id) as ids
        FROM pca_importacoes
        WHERE ano_pca = ? AND status = 'concluido'
    ");
    $stmt_importacoes->execute([$ano_selecionado]);
    $importacoes_result = $stmt_importacoes->fetch();
    $importacoes_ids_str = $importacoes_result['ids'] ?? '';

    echo "IDs de importações encontradas: $importacoes_ids_str\n";

    if (empty($importacoes_ids_str)) {
        echo "PROBLEMA: Nenhuma importação concluída encontrada para o ano $ano_selecionado\n";
        exit;
    }

    $importacoes_ids = explode(',', $importacoes_ids_str);
    $where_stats = "importacao_id IN (" . implode(',', $importacoes_ids) . ")";

    echo "Condição WHERE: $where_stats\n";

    // Testar a query principal
    $sql_base = "
        SELECT COUNT(*) as total
        FROM {$tabela_pca} pca
        WHERE {$where_stats}
        AND pca.numero_dfd IS NOT NULL AND pca.numero_dfd != ''
    ";

    $stmt = $pdo->prepare($sql_base);
    $stmt->execute();
    $resultado = $stmt->fetch();

    echo "Total de registros encontrados: " . $resultado['total'] . "\n";

    if ($resultado['total'] == 0) {
        echo "PROBLEMA: Nenhum registro encontrado com as condições especificadas\n";

        // Testar sem filtros
        $sql_sem_filtro = "SELECT COUNT(*) as total FROM {$tabela_pca}";
        $stmt2 = $pdo->prepare($sql_sem_filtro);
        $stmt2->execute();
        $resultado2 = $stmt2->fetch();
        echo "Total de registros na tabela sem filtros: " . $resultado2['total'] . "\n";

        // Verificar se há registros com importacao_id válido
        $sql_importacao = "SELECT COUNT(*) as total, GROUP_CONCAT(DISTINCT importacao_id) as ids FROM {$tabela_pca} WHERE importacao_id IS NOT NULL";
        $stmt3 = $pdo->prepare($sql_importacao);
        $stmt3->execute();
        $resultado3 = $stmt3->fetch();
        echo "Registros com importacao_id: " . $resultado3['total'] . "\n";
        echo "IDs de importação na tabela: " . $resultado3['ids'] . "\n";
    }

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
?>