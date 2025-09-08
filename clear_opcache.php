<?php
/**
 * Limpar cache do OPcode que pode estar causando o erro de strtoupper
 */

echo "<h1>🧹 Limpeza de Cache PHP</h1>";

// Limpar OPcache se disponível
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "<p>✅ <strong>OPcache limpo com sucesso!</strong></p>";
    } else {
        echo "<p>❌ Falha ao limpar OPcache</p>";
    }
} else {
    echo "<p>ℹ️ OPcache não está disponível</p>";
}

// Limpar cache de usuário se disponível
if (function_exists('apcu_clear_cache')) {
    if (apcu_clear_cache()) {
        echo "<p>✅ <strong>APCu cache limpo com sucesso!</strong></p>";
    } else {
        echo "<p>❌ Falha ao limpar APCu cache</p>";
    }
} else {
    echo "<p>ℹ️ APCu não está disponível</p>";
}

// Informações do sistema
echo "<h2>📊 Informações do Sistema</h2>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>SAPI:</strong> " . PHP_SAPI . "</p>";

// Verificar se strtoupper funciona
echo "<h2>🔍 Teste da Função strtoupper</h2>";
try {
    $result = strtoupper("teste");
    echo "<p>✅ <strong>strtoupper funcionando:</strong> {$result}</p>";
} catch (Error $e) {
    echo "<p>❌ <strong>Erro com strtoupper:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>💡 Próximos passos:</strong></p>";
echo "<ol>";
echo "<li>Reinicie o Apache/Nginx</li>";
echo "<li>Limpe o cache do navegador</li>";
echo "<li>Acesse novamente o Link Maestro</li>";
echo "</ol>";

echo "<p><a href='link-maestro'>🎯 Testar Link Maestro Agora</a></p>";
?>