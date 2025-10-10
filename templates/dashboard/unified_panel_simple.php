<?php
// Debug simples
session_start();

echo "<h1>Debug do Painel Unificado</h1>";
echo "<h2>Sessão:</h2>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";

echo "<h2>Constantes:</h2>";
echo "APP_NAME: " . (defined('APP_NAME') ? APP_NAME : 'NÃO DEFINIDO') . "<br>";
echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NÃO DEFINIDO') . "<br>";

if (isset($_SESSION['user']['id'])) {
    echo "<h2>Teste básico:</h2>";
    $user_id = $_SESSION['user']['id'];
    echo "User ID: " . $user_id . "<br>";
    
    echo "<h2>Painel Unificado funcionando!</h2>";
    echo "<a href='/unified-panel'>Versão completa</a> | <a href='/dashboard'>Voltar ao Dashboard</a>";
} else {
    echo "<h2>❌ Usuário não logado</h2>";
    echo "<a href='/login'>Fazer login</a>";
}
?>