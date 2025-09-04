<?php
/**
 * Script para verificar se as melhorias de debug estão funcionando
 */

require_once 'config/app.php';

echo "🔍 Verificando melhorias de debug Hotmart\n";
echo "========================================\n\n";

$files_to_check = [
    'app/services/HotmartService.php' => 'HotmartService',
    'app/services/SyncService.php' => 'SyncService', 
    'api/integration_config.php' => 'API Integration Config',
    'test_hotmart_debug.php' => 'Debug Test Script'
];

foreach ($files_to_check as $file => $description) {
    echo "📁 {$description}:\n";
    
    if (file_exists($file)) {
        $size = filesize($file);
        $modified = date('Y-m-d H:i:s', filemtime($file));
        echo "   ✅ Existe ({$size} bytes, modificado em {$modified})\n";
        
        // Verificar se contém melhorias de debug
        $content = file_get_contents($file);
        $debug_indicators = [
            'error_log(' => 'Logging',
            'HTTP Code:' => 'HTTP Debug', 
            'Credenciais inválidas' => 'Error Messages',
            'validateCredentials' => 'Credential Validation'
        ];
        
        foreach ($debug_indicators as $indicator => $feature) {
            if (strpos($content, $indicator) !== false) {
                echo "   🔧 {$feature}: ✅\n";
            }
        }
    } else {
        echo "   ❌ Arquivo não encontrado\n";
    }
    
    echo "\n";
}

echo "📋 Próximos passos:\n";
echo "1. Teste criar uma integração Hotmart usando Basic token\n";
echo "2. Verifique os logs em: C:\\xampp\\apache\\logs\\error.log\n";
echo "3. Se ainda houver erro, use o script test_hotmart_debug.php\n";
echo "4. Envie os logs detalhados para análise\n\n";

echo "💡 Para testar manualmente:\n";
echo "   php test_hotmart_debug.php\n";
?>