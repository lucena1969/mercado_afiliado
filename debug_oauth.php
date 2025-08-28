<?php
/**
 * Debug OAuth - Verificar por que botões não aparecem
 */

require_once 'config/app.php';

echo "<h2>🔍 Debug OAuth Status</h2>";

echo "<h3>1. Verificação de Autoload</h3>";
$autoload_path = APP_ROOT . '/vendor/autoload.php';
echo "Caminho autoload: <code>$autoload_path</code><br>";
echo "Arquivo existe: " . (file_exists($autoload_path) ? "✅ SIM" : "❌ NÃO") . "<br><br>";

echo "<h3>2. Verificação de Classes OAuth</h3>";
echo "Google OAuth disponível: " . (class_exists('League\OAuth2\Client\Provider\Google') ? "✅ SIM" : "❌ NÃO") . "<br>";
echo "Facebook OAuth disponível: " . (class_exists('League\OAuth2\Client\Provider\Facebook') ? "✅ SIM" : "❌ NÃO") . "<br><br>";

echo "<h3>3. Verificação de Credenciais</h3>";
echo "GOOGLE_CLIENT_ID definido: " . (defined('GOOGLE_CLIENT_ID') ? "✅ SIM" : "❌ NÃO") . "<br>";
echo "GOOGLE_CLIENT_ID preenchido: " . (!empty(GOOGLE_CLIENT_ID) ? "✅ SIM" : "❌ NÃO (vazio)") . "<br>";
echo "FACEBOOK: ❌ REMOVIDO (apenas Google OAuth disponível)<br><br>";

echo "<h3>4. Condições dos Botões</h3>";
$show_oauth_section = class_exists('League\OAuth2\Client\Provider\Google');
echo "Mostrar seção OAuth: " . ($show_oauth_section ? "✅ SIM" : "❌ NÃO") . "<br>";

$show_google = class_exists('League\OAuth2\Client\Provider\Google') && !empty(GOOGLE_CLIENT_ID);
echo "Mostrar botão Google: " . ($show_google ? "✅ SIM" : "❌ NÃO") . "<br>";

echo "Botão Facebook: ❌ REMOVIDO<br><br>";

echo "<h3>5. Bibliotecas Carregadas</h3>";
$loaded_classes = get_declared_classes();
$oauth_classes = array_filter($loaded_classes, function($class) {
    return strpos($class, 'League\\OAuth2') !== false;
});

if (empty($oauth_classes)) {
    echo "❌ Nenhuma classe OAuth encontrada<br>";
} else {
    echo "✅ Classes OAuth encontradas:<br>";
    foreach ($oauth_classes as $class) {
        echo "- <code>$class</code><br>";
    }
}

echo "<br><h3>6. Solução</h3>";
if (!file_exists($autoload_path)) {
    echo "🚨 <strong>PROBLEMA PRINCIPAL:</strong> Arquivo vendor/autoload.php não encontrado<br>";
    echo "<strong>Solução:</strong> Faça upload da pasta vendor/ para o servidor<br>";
} elseif (!$show_oauth_section) {
    echo "🚨 <strong>PROBLEMA:</strong> Classes OAuth não carregadas<br>";
    echo "<strong>Solução:</strong> Verificar se Composer instalou corretamente<br>";
} elseif (empty(GOOGLE_CLIENT_ID)) {
    echo "⚠️ <strong>Configuração necessária:</strong> Credenciais Google OAuth não configuradas<br>";
    echo "<strong>Solução:</strong> Preencher GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET em config/app.php<br>";
} else {
    echo "✅ Tudo configurado corretamente!<br>";
}

echo "<br><p><a href='/login'>← Voltar para Login</a></p>";
?>