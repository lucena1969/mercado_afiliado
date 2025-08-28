<?php
/**
 * Teste rápido das rotas do Pixel BR
 */
require_once 'config/app.php';
require_once 'config/database.php';

echo "<h2>🧪 Teste das Rotas do Pixel BR</h2>";

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/mercado_afiliado/public';

$routes_to_test = [
    'Pixel Principal' => $base_url . '/pixel',
    'Salvar Config (GET)' => $base_url . '/pixel/save', 
    'Eventos' => $base_url . '/pixel/events',
    'Detalhes de Evento' => $base_url . '/pixel/event-details?event_id=test'
];

echo "<h3>🌐 URLs do Sistema</h3>";
echo "<ul>";
foreach ($routes_to_test as $name => $url) {
    echo "<li><strong>{$name}:</strong> <a href='{$url}' target='_blank'>{$url}</a></li>";
}
echo "</ul>";

// Verificar se os arquivos existem
echo "<h3>📁 Verificação de Arquivos</h3>";
$files_to_check = [
    'templates/pixel/index.php',
    'templates/pixel/save.php', 
    'templates/pixel/events.php',
    'templates/pixel/event-details.php',
    'app/controllers/AuthController.php'
];

echo "<ul>";
foreach ($files_to_check as $file) {
    $full_path = __DIR__ . "/" . $file;
    $exists = file_exists($full_path);
    $status = $exists ? "✅" : "❌";
    echo "<li>{$status} <strong>{$file}</strong></li>";
}
echo "</ul>";

// Verificar se o banco está configurado
echo "<h3>🗄️ Verificação do Banco</h3>";
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("SHOW TABLES LIKE 'pixel_configurations'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Tabela <strong>pixel_configurations</strong> existe</p>";
    } else {
        echo "<p>❌ Tabela <strong>pixel_configurations</strong> não existe</p>";
    }
    
    $stmt = $conn->query("SHOW TABLES LIKE 'pixel_events'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Tabela <strong>pixel_events</strong> existe</p>";
    } else {
        echo "<p>❌ Tabela <strong>pixel_events</strong> não existe</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro de conexão com banco: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🎯 Status Geral</h3>";
echo "<p>Se todos os itens acima estiverem ✅, o sistema deve funcionar corretamente.</p>";
echo "<p><a href='{$base_url}/pixel'>🚀 Testar Pixel BR</a></p>";

echo "<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 800px; margin: 2rem auto; padding: 0 1rem; line-height: 1.6; }
    a { color: #3b82f6; text-decoration: none; }
    a:hover { text-decoration: underline; }
    ul { padding-left: 1.5rem; }
    hr { margin: 2rem 0; border: none; border-top: 1px solid #e5e7eb; }
</style>";
?>