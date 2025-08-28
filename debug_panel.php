<?php
// Debug super simples na raiz
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Debug Painel Unificado</h1>";

// Teste 1: PHP básico
echo "<h2>✅ PHP funcionando</h2>";

// Teste 2: Sessão
session_start();
echo "<h2>📋 Sessão:</h2>";
if (isset($_SESSION['user'])) {
    echo "✅ Usuário logado: " . $_SESSION['user']['name'] . "<br>";
    echo "✅ User ID: " . $_SESSION['user']['id'] . "<br>";
} else {
    echo "❌ Usuário não logado<br>";
}

// Teste 3: Arquivos
echo "<h2>📁 Arquivos:</h2>";
$files_to_check = [
    'config/app.php',
    'config/database.php', 
    'app/models/UnifiedPanel.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file existe<br>";
    } else {
        echo "❌ $file NÃO EXISTE<br>";
    }
}

// Teste 4: Constantes
echo "<h2>⚙️ Constantes:</h2>";
if (defined('APP_NAME')) {
    echo "✅ APP_NAME: " . APP_NAME . "<br>";
} else {
    echo "❌ APP_NAME não definida<br>";
}

// Teste 5: Banco de dados
echo "<h2>🗄️ Banco de dados:</h2>";
try {
    if (file_exists('config/database.php')) {
        require_once 'config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        echo "✅ Conexão com banco OK<br>";
        
        // Teste query simples
        $stmt = $db->query("SELECT COUNT(*) as total FROM users");
        $result = $stmt->fetch();
        echo "✅ Query teste OK - " . $result['total'] . " usuários<br>";
        
    } else {
        echo "❌ Arquivo database.php não encontrado<br>";
    }
} catch (Exception $e) {
    echo "❌ Erro no banco: " . $e->getMessage() . "<br>";
}

echo "<br><a href='/unified-panel'>🚀 Tentar Painel Completo</a>";
echo "<br><a href='/dashboard'>📊 Voltar ao Dashboard</a>";
?>