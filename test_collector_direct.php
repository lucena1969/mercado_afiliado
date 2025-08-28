<?php
/**
 * Teste direto do coletor Pixel BR
 */

echo "<h2>🧪 Teste Direto do Coletor</h2>";

$collector_url = "http://localhost/mercado_afiliado/api/pixel/collect.php";

echo "<p><strong>URL do Coletor:</strong> {$collector_url}</p>";

// Verificar se o arquivo existe
$file_path = __DIR__ . "/api/pixel/collect.php";
if (file_exists($file_path)) {
    echo "<p>✅ <strong>Arquivo collect.php existe</strong></p>";
} else {
    echo "<p>❌ <strong>Arquivo collect.php NÃO existe em:</strong> {$file_path}</p>";
}

// Teste 1: GET request (deve retornar erro 405)
echo "<h3>🔍 Teste 1: Requisição GET (deve falhar)</h3>";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 5
    ]
]);

$response = @file_get_contents($collector_url, false, $context);
if ($response !== false) {
    echo "<p>Resposta GET: " . htmlspecialchars($response) . "</p>";
} else {
    echo "<p>❌ Erro na requisição GET (esperado)</p>";
}

// Teste 2: POST request com dados válidos
echo "<h3>🧪 Teste 2: Requisição POST (deve funcionar)</h3>";

$test_data = [
    'event_name' => 'test_connection',
    'event_time' => time(),
    'event_id' => 'test_' . time(),
    'source_url' => 'http://localhost/test',
    'consent' => 'granted'
];

$post_data = json_encode($test_data);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $post_data,
        'timeout' => 10
    ]
]);

echo "<p><strong>Dados enviados:</strong></p>";
echo "<pre>" . htmlspecialchars($post_data) . "</pre>";

$response = @file_get_contents($collector_url, false, $context);
$headers = $http_response_header ?? [];

echo "<p><strong>Headers de resposta:</strong></p>";
echo "<pre>" . implode("\n", $headers) . "</pre>";

if ($response !== false) {
    echo "<p>✅ <strong>Resposta recebida:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $json_response = json_decode($response, true);
    if ($json_response) {
        echo "<p>✅ <strong>JSON válido recebido</strong></p>";
        if (isset($json_response['ok']) && $json_response['ok']) {
            echo "<p>🎉 <strong>Coletor funcionando perfeitamente!</strong></p>";
        }
    }
} else {
    echo "<p>❌ <strong>Erro na requisição POST</strong></p>";
    $error = error_get_last();
    if ($error) {
        echo "<p><strong>Erro:</strong> " . htmlspecialchars($error['message']) . "</p>";
    }
}

// Teste 3: Verificar dependências
echo "<h3>🔧 Verificação de Dependências</h3>";

// Verificar se os arquivos necessários existem
$required_files = [
    'config/app.php',
    'config/database.php',
    'app/models/PixelConfiguration.php'
];

foreach ($required_files as $file) {
    $full_path = __DIR__ . "/" . $file;
    if (file_exists($full_path)) {
        echo "<p>✅ {$file}</p>";
    } else {
        echo "<p>❌ {$file} <strong>(FALTANDO)</strong></p>";
    }
}

// Teste 4: Acessar o coletor diretamente no navegador
echo "<hr>";
echo "<h3>🌐 Teste Manual</h3>";
echo "<p>Clique no link abaixo para testar o coletor diretamente:</p>";
echo "<p><a href='{$collector_url}' target='_blank'>{$collector_url}</a></p>";
echo "<p><small>Deve mostrar erro 405 (Method not allowed) - isso é normal!</small></p>";

echo "<hr>";
echo "<p><a href='test_pixel.php'>← Voltar para página de teste</a></p>";
?>

<style>
    body { 
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        max-width: 800px;
        margin: 2rem auto;
        padding: 0 1rem;
        line-height: 1.6;
    }
    pre {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 4px;
        overflow-x: auto;
        font-size: 0.9rem;
    }
    a { color: #3b82f6; text-decoration: none; }
    a:hover { text-decoration: underline; }
    hr { margin: 2rem 0; border: none; border-top: 1px solid #e5e7eb; }
</style>