<?php
/**
 * Script de teste direto do process.php
 * Simula uma requisição de edição de licitação
 */

// Simular sessão
session_start();

// IMPORTANTE: Configure aqui um usuário válido do seu sistema
$_SESSION['usuario_id'] = 1; // ID do usuário admin
$_SESSION['usuario_nivel'] = 1; // Nível coordenador
$_SESSION['usuario_tipo'] = 'admin';

// Simular POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['acao'] = 'editar_licitacao';
$_POST['id'] = '1'; // COLOQUE AQUI O ID DA LICITAÇÃO QUE VOCÊ QUER EDITAR
$_POST['nup'] = '25000.081296/2025-49';
$_POST['modalidade'] = 'PREGAO_ELETRONICO';
$_POST['tipo'] = 'MENOR_PRECO';
$_POST['situacao'] = 'EM_ANDAMENTO';
$_POST['objeto'] = 'Teste de edição';
$_POST['area_demandante'] = 'CGLIC';
$_POST['pregoeiro'] = 'Teste';
$_POST['data_entrada_dipli'] = '2025-01-01';
$_POST['data_abertura'] = '2025-02-01';
$_POST['valor_estimado'] = '1000.00';
$_POST['ano'] = '2025';

echo "=== TESTE DIRETO DO PROCESS.PHP ===\n\n";
echo "POST data:\n";
print_r($_POST);
echo "\n\n";
echo "=== INICIANDO PROCESSAMENTO ===\n\n";

// Capturar output
ob_start();

try {
    // Incluir o process.php
    include 'process.php';

    $output = ob_get_clean();

    echo "=== RESPOSTA DO PROCESS.PHP ===\n\n";
    echo "Tamanho: " . strlen($output) . " bytes\n";
    echo "Primeiro caractere: '" . substr($output, 0, 1) . "' (código: " . ord(substr($output, 0, 1)) . ")\n\n";

    // Verificar se é JSON
    $json = json_decode($output, true);

    if ($json !== null) {
        echo "✅ JSON VÁLIDO!\n\n";
        echo "Resposta formatada:\n";
        echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "\n";
    } else {
        echo "❌ NÃO É JSON VÁLIDO!\n\n";
        echo "Primeiros 1000 caracteres:\n";
        echo substr($output, 0, 1000);
        echo "\n\n";
        echo "Últimos 500 caracteres:\n";
        echo substr($output, -500);
        echo "\n";
    }

} catch (Exception $e) {
    ob_end_clean();
    echo "❌ ERRO FATAL:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
