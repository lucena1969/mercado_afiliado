<?php
// Script para forçar importação do PCA
require_once 'config.php';
require_once 'functions.php';

echo "<h1>🔄 Importação Forçada PCA</h1>\n";

// Simular sessão de usuário administrador
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nivel'] = 1;
$_SESSION['usuario_nome'] = 'Admin';

$arquivo = 'pca_2025.csv';

if (!file_exists($arquivo)) {
    echo "<p>❌ Arquivo '$arquivo' não encontrado!</p>\n";
    exit;
}

try {
    $pdo = conectarDB();

    // Limpar dados anteriores
    echo "<p>🧹 Limpando dados anteriores...</p>\n";
    $pdo->exec("DELETE FROM pca_dados");
    $pdo->exec("ALTER TABLE pca_dados AUTO_INCREMENT = 1");

    // Abrir arquivo
    $handle = fopen($arquivo, 'r');
    if (!$handle) {
        throw new Exception('Erro ao abrir arquivo');
    }

    // Detectar separador
    $primeira_linha = fgets($handle);
    rewind($handle);
    $separador = ';';

    // Pular cabeçalho
    $header = fgetcsv($handle, 0, $separador);

    echo "<p>📊 Iniciando importação...</p>\n";

    $total_inserido = 0;
    $erros = 0;

    while (($linha = fgetcsv($handle, 0, $separador)) !== FALSE) {
        if (empty($linha[0])) continue; // Pular linhas vazias

        try {
            // Mapear dados (usando mapeamento corrigido) - SEM CAMPO ID
            $dados = [
                'importacao_id' => 1,
                'numero_contratacao' => trim($linha[0] ?? ''),
                'status_contratacao' => trim($linha[1] ?? ''),
                'situacao_execucao' => trim($linha[2] ?? '') ?: 'Não iniciado',
                'titulo_contratacao' => trim($linha[3] ?? ''),
                'categoria_contratacao' => trim($linha[4] ?? ''),
                'uasg_atual' => trim($linha[5] ?? ''),
                'valor_total_contratacao' => null,
                'data_inicio_processo' => formatarDataDB($linha[6] ?? ''),
                'data_conclusao_processo' => formatarDataDB($linha[7] ?? ''),
                'prazo_duracao_dias' => !empty($linha[8]) ? intval($linha[8]) : null,
                'area_requisitante' => trim($linha[9] ?? ''),  // CORRIGIDO
                'numero_dfd' => trim($linha[10] ?? ''),
                'prioridade' => trim($linha[11] ?? ''),
                'numero_item_dfd' => trim($linha[12] ?? ''),
                'data_conclusao_dfd' => formatarDataDB($linha[13] ?? ''),
                'classificacao_contratacao' => trim($linha[14] ?? ''),
                'codigo_classe_grupo' => trim($linha[15] ?? ''),
                'nome_classe_grupo' => trim($linha[16] ?? ''),
                'codigo_pdm_material' => trim($linha[17] ?? ''),
                'nome_pdm_material' => trim($linha[18] ?? ''),
                'codigo_material_servico' => trim($linha[19] ?? ''),
                'descricao_material_servico' => trim($linha[20] ?? ''),
                'unidade_fornecimento' => trim($linha[21] ?? ''),
                'valor_unitario' => processarValorMonetario($linha[22] ?? ''),
                'quantidade' => !empty($linha[23]) ? intval($linha[23]) : null,
                'valor_total' => processarValorMonetario($linha[24] ?? '')
            ];

            // Preparar INSERT
            $campos = array_keys($dados);
            $placeholders = ':' . implode(', :', $campos);
            $sql = "INSERT INTO pca_dados (" . implode(', ', $campos) . ") VALUES (" . $placeholders . ")";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($dados);

            $total_inserido++;

        } catch (Exception $e) {
            $erros++;
            if ($erros <= 3) { // Mostrar apenas primeiros 3 erros
                echo "<p>⚠️ Erro linha " . ($total_inserido + $erros) . ": " . $e->getMessage() . "</p>\n";
            }
        }
    }

    fclose($handle);

    echo "<h3>✅ Importação Concluída!</h3>\n";
    echo "<p><strong>Total inserido:</strong> $total_inserido registros</p>\n";
    echo "<p><strong>Erros:</strong> $erros</p>\n";

    // Verificar áreas requisitantes
    $stmt = $pdo->query("SELECT COUNT(DISTINCT area_requisitante) as total_areas FROM pca_dados WHERE area_requisitante IS NOT NULL AND area_requisitante != ''");
    $total_areas = $stmt->fetch()['total_areas'];

    echo "<p><strong>Áreas Requisitantes Distintas:</strong> $total_areas</p>\n";

    // Mostrar algumas áreas
    $stmt = $pdo->query("SELECT DISTINCT area_requisitante FROM pca_dados WHERE area_requisitante IS NOT NULL AND area_requisitante != '' ORDER BY area_requisitante LIMIT 10");
    echo "<p><strong>Primeiras 10 áreas:</strong></p>\n<ul>\n";
    while ($row = $stmt->fetch()) {
        echo "<li>" . htmlspecialchars($row['area_requisitante']) . "</li>\n";
    }
    echo "</ul>\n";

} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>\n";
}

echo "<p><a href='dashboard.php'>← Voltar ao Dashboard</a></p>\n";
echo "<p><a href='relatorios_gerenciais.php?modulo=planejamento'>📊 Ver Relatórios PCA</a></p>\n";
?>