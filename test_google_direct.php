<?php
/**
 * Teste direto do Google OAuth - Simula o fluxo completo
 */
require_once 'config/app.php';

echo "<h2>🧪 Teste Google OAuth</h2>";

// Verificar status atual
echo "<h3>Status Atual:</h3>";
echo "Google Client ID: " . GOOGLE_CLIENT_ID . "<br>";
echo "Google Secret: " . (GOOGLE_CLIENT_SECRET ? '✅ Configurado' : '❌ Vazio') . "<br>";
echo "Redirect URI: " . GOOGLE_REDIRECT_URI . "<br><br>";

if (GOOGLE_CLIENT_ID === 'teste-google-id') {
    echo "<div style='background: #fef3c7; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
    echo "<strong>⚠️ CREDENCIAIS DE TESTE DETECTADAS</strong><br>";
    echo "Para funcionar com Google real, você precisa:<br>";
    echo "1. Ir ao Google Cloud Console<br>";
    echo "2. Criar projeto OAuth 2.0<br>";
    echo "3. Configurar credenciais reais<br>";
    echo "4. Atualizar config/app.php<br>";
    echo "</div>";
    
    // Simular o que aconteceria
    echo "<h3>Simulação do Fluxo OAuth:</h3>";
    echo "<ol>";
    echo "<li>✅ Usuário clica 'Continuar com Google'</li>";
    echo "<li>❌ Sistema tentaria redirecionar para Google (mas credenciais são falsas)</li>";
    echo "<li>❌ Google rejeitaria por credenciais inválidas</li>";
    echo "<li>❌ Usuário veria erro ou página em branco</li>";
    echo "</ol>";
    
    echo "<h3>Como Configurar Credenciais Reais:</h3>";
    echo "<ol>";
    echo "<li><strong>Google Cloud Console:</strong> https://console.cloud.google.com</li>";
    echo "<li><strong>Criar projeto</strong> ou usar existente</li>";
    echo "<li><strong>Ativar</strong> Google+ API</li>";
    echo "<li><strong>Configurar tela de consentimento</strong> OAuth</li>";
    echo "<li><strong>Criar credenciais</strong> OAuth 2.0:</li>";
    echo "<ul>";
    echo "<li>Tipo: Aplicação da web</li>";
    echo "<li>URIs de origem autorizadas: <code>https://mercadoafiliado.com.br</code></li>";
    echo "<li>URIs de redirecionamento: <code>https://mercadoafiliado.com.br/auth/google/callback</code></li>";
    echo "</ul>";
    echo "<li><strong>Copiar</strong> Client ID e Client Secret</li>";
    echo "<li><strong>Atualizar</strong> config/app.php:</li>";
    echo "</ol>";
    
    echo "<pre style='background: #f3f4f6; padding: 1rem; border-radius: 8px;'>";
    echo "define('GOOGLE_CLIENT_ID', 'SEU_CLIENT_ID_REAL_AQUI');\n";
    echo "define('GOOGLE_CLIENT_SECRET', 'SEU_CLIENT_SECRET_REAL_AQUI');";
    echo "</pre>";
    
} else {
    echo "<div style='background: #d1fae5; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
    echo "<strong>✅ CREDENCIAIS CONFIGURADAS</strong><br>";
    echo "Sistema pronto para usar Google OAuth!<br>";
    echo "</div>";
    
    echo "<h3>Teste OAuth Real:</h3>";
    echo "<a href='/auth/google' style='display: inline-block; padding: 1rem 2rem; background: #4285f4; color: white; text-decoration: none; border-radius: 8px;'>";
    echo "🚀 Testar Login com Google";
    echo "</a>";
}

echo "<h3>URLs Importantes:</h3>";
echo "<ul>";
echo "<li><a href='/login-manual'>Login Manual</a> - Interface de login</li>";
echo "<li><a href='/debug_oauth.php'>Debug OAuth</a> - Status detalhado</li>";
echo "<li><a href='/auth/google'>Teste Google</a> - Link direto OAuth</li>";
echo "</ul>";

echo "<h3>O que Cada Erro Significa:</h3>";
echo "<ul>";
echo "<li><strong>404 Not Found:</strong> Problema nas rotas (router.php)</li>";
echo "<li><strong>\"Credenciais não configuradas\":</strong> Credenciais de teste (normal)</li>";
echo "<li><strong>\"redirect_uri_mismatch\":</strong> URL callback não cadastrada no Google</li>";
echo "<li><strong>\"invalid_client\":</strong> Client ID/Secret incorretos</li>";
echo "<li><strong>Página em branco:</strong> Erro PHP (verificar logs)</li>";
echo "</ul>";
?>