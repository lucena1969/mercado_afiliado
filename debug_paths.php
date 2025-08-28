<?php
/**
 * Debug de caminhos e URLs
 */

echo "<h2>🔍 Debug de Caminhos - Mercado Afiliado</h2>";

echo "<h3>📁 Informações do Servidor</h3>";
echo "<ul>";
echo "<li><strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "</li>";
echo "<li><strong>SERVER_NAME:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'N/A') . "</li>";
echo "<li><strong>REQUEST_URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "</li>";
echo "<li><strong>SCRIPT_NAME:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "</li>";
echo "<li><strong>DOCUMENT_ROOT:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</li>";
echo "</ul>";

echo "<h3>📂 Caminhos dos Arquivos</h3>";
echo "<ul>";
echo "<li><strong>__DIR__:</strong> " . __DIR__ . "</li>";
echo "<li><strong>__FILE__:</strong> " . __FILE__ . "</li>";
echo "</ul>";

$files_to_check = [
    'api/pixel/collect.php',
    'api/pixel/collect_simple.php', 
    'public/assets/js/pixel/pixel_br.js',
    'config/app.php',
    'config/database.php'
];

echo "<h3>🔍 Verificação de Arquivos</h3>";
echo "<ul>";
foreach ($files_to_check as $file) {
    $full_path = __DIR__ . "/" . $file;
    $exists = file_exists($full_path);
    $status = $exists ? "✅" : "❌";
    echo "<li>{$status} <strong>{$file}</strong>";
    if ($exists) {
        echo " (" . number_format(filesize($full_path)) . " bytes)";
    }
    echo "</li>";
}
echo "</ul>";

echo "<h3>🌐 URLs de Teste</h3>";
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/mercado_afiliado';

$urls_to_test = [
    'Coletor Original' => $base_url . '/api/pixel/collect.php',
    'Coletor Simples' => $base_url . '/api/pixel/collect_simple.php',
    'Script Pixel JS' => $base_url . '/public/assets/js/pixel/pixel_br.js',
    'Teste Connection' => $base_url . '/test_connection.php',
    'Teste Collector Direto' => $base_url . '/test_collector_direct.php'
];

echo "<ul>";
foreach ($urls_to_test as $name => $url) {
    echo "<li><strong>{$name}:</strong> <a href='{$url}' target='_blank'>{$url}</a></li>";
}
echo "</ul>";

echo "<h3>🧪 Teste AJAX Simples</h3>";
echo "<button onclick='testCollectorAjax()' class='btn'>Testar Coletor via AJAX</button>";
echo "<div id='ajax-result'></div>";

echo "<script>
function testCollectorAjax() {
    const result = document.getElementById('ajax-result');
    result.innerHTML = '<p>🔄 Testando...</p>';
    
    const testData = {
        event_name: 'test_debug',
        event_time: Math.floor(Date.now() / 1000),
        event_id: 'debug_' + Date.now(),
        source_url: window.location.href
    };
    
    fetch('{$base_url}/api/pixel/collect_simple.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(testData)
    })
    .then(response => {
        result.innerHTML += '<p><strong>Status:</strong> ' + response.status + '</p>';
        return response.text();
    })
    .then(text => {
        result.innerHTML += '<p><strong>Resposta:</strong></p><pre>' + text + '</pre>';
    })
    .catch(error => {
        result.innerHTML += '<p><strong>Erro:</strong> ' + error.message + '</p>';
    });
}
</script>";

echo "<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1000px; margin: 2rem auto; padding: 0 1rem; line-height: 1.6; }
    ul { padding-left: 1.5rem; }
    a { color: #3b82f6; text-decoration: none; }
    a:hover { text-decoration: underline; }
    .btn { background: #3b82f6; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; }
    .btn:hover { background: #2563eb; }
    pre { background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; }
    #ajax-result { margin-top: 1rem; }
</style>";
?>