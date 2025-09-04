<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "🔍 Verificando status da tabela integrations\n";

// Verificar status da tabela
$query = "SHOW TABLE STATUS WHERE Name = 'integrations'";
$stmt = $db->prepare($query);
$stmt->execute();
$status = $stmt->fetch();

echo "📊 Status da tabela:\n";
foreach ($status as $key => $value) {
    echo "   {$key}: {$value}\n";
}

echo "\n📋 Integrações existentes:\n";
$query = "SELECT id, name, platform, user_id FROM integrations ORDER BY id";
$stmt = $db->prepare($query);
$stmt->execute();
$integrations = $stmt->fetchAll();

if (empty($integrations)) {
    echo "   Nenhuma integração encontrada\n";
} else {
    foreach ($integrations as $int) {
        echo "   ID: {$int['id']}, Nome: {$int['name']}, Plataforma: {$int['platform']}, User: {$int['user_id']}\n";
    }
}

// Corrigir AUTO_INCREMENT se necessário
$max_id = 0;
if (!empty($integrations)) {
    $max_id = max(array_column($integrations, 'id'));
}
$next_id = $max_id + 1;

echo "\n🔧 Definindo AUTO_INCREMENT para {$next_id}\n";
$query = "ALTER TABLE integrations AUTO_INCREMENT = {$next_id}";
$stmt = $db->prepare($query);
$stmt->execute();

echo "✅ AUTO_INCREMENT ajustado!\n";
?>