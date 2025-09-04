<?php
/**
 * Script de debug para testar credenciais Hotmart
 * Execute este arquivo diretamente para ver logs detalhados
 */

require_once 'config/app.php';
require_once 'app/services/HotmartService.php';

echo "🔍 Debug Hotmart Credentials\n";
echo "============================\n\n";

// Simulação de credenciais - substitua pelos valores reais para teste
$test_credentials = [
    // Teste com Basic token (exemplo)
    'basic' => [
        'api_key' => '',
        'api_secret' => 'Basic SEU_TOKEN_AQUI'
    ],
    // Teste com OAuth (exemplo)
    'oauth' => [
        'api_key' => 'SEU_CLIENT_ID',
        'api_secret' => 'SEU_CLIENT_SECRET'
    ]
];

echo "⚠️  IMPORTANTE: Edite este arquivo e substitua as credenciais de exemplo\n";
echo "    pelas credenciais reais do arquivo baixado da Hotmart.\n\n";

foreach ($test_credentials as $type => $creds) {
    echo "🧪 Testando credenciais $type:\n";
    echo "   API Key: " . ($creds['api_key'] ?: '(vazio)') . "\n";
    echo "   API Secret: " . substr($creds['api_secret'], 0, 20) . "...\n\n";
    
    try {
        $hotmart = new HotmartService($creds['api_key'], $creds['api_secret']);
        
        echo "📡 Testando validação...\n";
        $is_valid = $hotmart->validateCredentials();
        
        if ($is_valid) {
            echo "✅ Credenciais VÁLIDAS!\n";
        } else {
            echo "❌ Credenciais INVÁLIDAS\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Erro durante teste: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

echo "📋 Verifique os logs do PHP (error.log) para ver detalhes técnicos.\n";
echo "   Local típico: C:\\xampp\\apache\\logs\\error.log\n\n";

echo "🔧 Para usar este script:\n";
echo "1. Edite o arquivo e substitua as credenciais de exemplo\n";
echo "2. Execute: php test_hotmart_debug.php\n";
echo "3. Analise os logs de erro para identificar o problema\n";
?>