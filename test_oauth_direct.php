<?php
/**
 * Teste direto OAuth - Simula chamada para auth/google
 */

echo "<h2>🔍 Teste Direto OAuth</h2>";

echo "<h3>Teste de Redirecionamento</h3>";
echo "<p>Clique nos links abaixo para testar o redirecionamento OAuth:</p>";

echo "<ul>";
echo "<li><a href='/auth/google' target='_blank'>🔗 Teste Google OAuth</a></li>";
echo "<li><a href='/auth/facebook' target='_blank'>🔗 Teste Facebook OAuth</a></li>";
echo "</ul>";

echo "<h3>Informações de Debug</h3>";
echo "URL atual: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "Método: " . $_SERVER['REQUEST_METHOD'] . "<br>";

echo "<h3>Teste Manual</h3>";
echo "<p>Se você está vendo esta página, significa que:</p>";
echo "<ul>";
echo "<li>✅ As rotas básicas estão funcionando</li>";
echo "<li>✅ O sistema PHP está rodando</li>";
echo "</ul>";

echo "<p>Agora teste os links OAuth acima. O que deve acontecer:</p>";
echo "<ul>";
echo "<li><strong>Se OAuth estiver configurado:</strong> Redireciona para Google/Facebook</li>";
echo "<li><strong>Se credenciais vazias:</strong> Mostra erro \"Credenciais não configuradas\"</li>";
echo "<li><strong>Se vendor não existe:</strong> Mostra erro \"Dependências não encontradas\"</li>";
echo "<li><strong>Se rota não funciona:</strong> Erro 404 ou página em branco</li>";
echo "</ul>";

// Teste direto da função OAuth (se existir)
if (file_exists('config/app.php')) {
    require_once 'config/app.php';
    
    echo "<h3>Status das Classes</h3>";
    echo "AuthController existe: " . (class_exists('AuthController') ? '✅' : '❌') . "<br>";
    echo "Google OAuth disponível: " . (class_exists('League\OAuth2\Client\Provider\Google') ? '✅' : '❌') . "<br>";
    echo "Facebook OAuth disponível: " . (class_exists('League\OAuth2\Client\Provider\Facebook') ? '✅' : '❌') . "<br>";
}

echo "<br><p><a href='/test_oauth_login.php'>← Voltar para login de teste</a></p>";
?>