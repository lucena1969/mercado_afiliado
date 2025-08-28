<?php
/**
 * Debug Rotas OAuth - Verificar roteamento
 */

require_once 'config/app.php';

echo "<h2>🔍 Debug Rotas OAuth</h2>";

// Simular diferentes URLs para testar roteamento
$test_urls = [
    '/auth/google',
    '/auth/google/callback', 
    '/auth/facebook',
    '/auth/facebook/callback',
    '/api/auth/google',
    '/api/auth/google/callback',
    '/api/auth/facebook', 
    '/api/auth/facebook/callback'
];

echo "<h3>1. URLs de Teste</h3>";
foreach ($test_urls as $url) {
    echo "🔗 <a href='$url' target='_blank'>$url</a> - ";
    echo "<a href='https://mercadoafiliado.com.br$url' target='_blank'>Testar no servidor</a><br>";
}

echo "<h3>2. Configuração BASE_URL</h3>";
echo "BASE_URL atual: <code>" . BASE_URL . "</code><br>";

echo "<h3>3. Arquivo OAuth</h3>";
$oauth_file = __DIR__ . '/api/auth/oauth.php';
echo "Arquivo oauth.php existe: " . (file_exists($oauth_file) ? "✅ SIM" : "❌ NÃO") . "<br>";
echo "Caminho: <code>$oauth_file</code><br>";

echo "<h3>4. Teste Manual das Rotas</h3>";
echo "<p>Clique nos links acima para testar as rotas OAuth.</p>";
echo "<p>Se aparecer 404, o problema está no roteamento.</p>";
echo "<p>Se aparecer erro PHP, o problema está na configuração OAuth.</p>";

echo "<h3>5. Estrutura Esperada</h3>";
echo "<ul>";
echo "<li><code>/auth/google</code> → Deveria redirecionar para Google</li>";
echo "<li><code>/auth/facebook</code> → Deveria redirecionar para Facebook</li>";
echo "<li><code>/auth/google/callback</code> → Callback do Google</li>";
echo "<li><code>/auth/facebook/callback</code> → Callback do Facebook</li>";
echo "</ul>";

echo "<br><p><a href='/test_oauth_login.php'>← Voltar para login de teste</a></p>";
?>