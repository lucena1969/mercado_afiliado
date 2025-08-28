<?php
/**
 * Arquivo de debug para identificar problemas
 */

// Exibir todos os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug - Mercado Afiliado</h1>";

// 1. Teste básico PHP
echo "<h2>1. PHP Status</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// 2. Teste de arquivos
echo "<h2>2. Arquivos necessários</h2>";
$files = [
    'config/database.php',
    'config/app.php',
    'templates/landing.php',
    'public/index.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file existe<br>";
    } else {
        echo "❌ $file NÃO encontrado<br>";
    }
}

// 3. Teste de conexão com banco
echo "<h2>3. Teste de Banco de Dados</h2>";
try {
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn) {
        echo "✅ Conexão com banco OK<br>";
        
        // Testar uma query simples
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "✅ Query teste OK - " . $result['total'] . " usuários<br>";
    }
} catch (Exception $e) {
    echo "❌ Erro no banco: " . $e->getMessage() . "<br>";
}

// 4. Verificar constantes
echo "<h2>4. Constantes do sistema</h2>";
try {
    require_once 'config/app.php';
    echo "APP_NAME: " . (defined('APP_NAME') ? APP_NAME : 'NÃO DEFINIDA') . "<br>";
    echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NÃO DEFINIDA') . "<br>";
} catch (Exception $e) {
    echo "❌ Erro nas constantes: " . $e->getMessage() . "<br>";
}

// 5. Teste de paths
echo "<h2>5. Paths e diretórios</h2>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Path: " . __FILE__ . "<br>";
echo "Working Directory: " . getcwd() . "<br>";

// 6. Extensões PHP necessárias
echo "<h2>6. Extensões PHP</h2>";
$extensions = ['pdo', 'pdo_mysql', 'json', 'curl'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext disponível<br>";
    } else {
        echo "❌ $ext NÃO disponível<br>";
    }
}

echo "<p><strong>Se tudo estiver OK aqui, o problema pode ser no .htaccess ou configuração do servidor.</strong></p>";
?>