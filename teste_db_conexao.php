<?php
// Teste específico da conexão do banco
require_once 'config.php';

echo "=== TESTE DE CONEXÃO DB ===\n";

try {
    echo "Tentando conectar...\n";
    $pdo = conectarDB();
    echo "SUCESSO: Conexão estabelecida!\n";

    // Testar uma query simples
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "SUCESSO: Query executada, resultado: " . $result['test'] . "\n";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

echo "Fim do teste.\n";
?>