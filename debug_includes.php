<?php
/**
 * Debug de includes para identificar conflitos
 */

echo "<h1>🔍 Debug de Includes</h1>";

echo "<h2>📋 Arquivos Incluídos:</h2>";
echo "<pre>";
print_r(get_included_files());
echo "</pre>";

echo "<h2>🧪 Teste de Inclusão Manual:</h2>";

$files_to_test = [
    'config/app.php',
    'config/database.php', 
    'app/controllers/LinkMaestroController.php',
    'templates/link_maestro/compliance_modal.php'
];

foreach ($files_to_test as $file) {
    echo "<h3>Testando: {$file}</h3>";
    
    if (file_exists($file)) {
        echo "<p>✅ Arquivo existe</p>";
        
        try {
            // Capturar output
            ob_start();
            $result = include_once $file;
            $output = ob_get_clean();
            
            echo "<p>✅ Incluído sem erro</p>";
            if ($output) {
                echo "<p><strong>Output capturado:</strong></p>";
                echo "<pre>" . htmlspecialchars($output) . "</pre>";
            }
        } catch (Error $e) {
            echo "<p>❌ <strong>ERRO:</strong> " . $e->getMessage() . "</p>";
            echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
            echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
            break; // Parar no primeiro erro
        } catch (Exception $e) {
            echo "<p>❌ <strong>EXCEPTION:</strong> " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>❌ Arquivo não existe: {$file}</p>";
    }
    
    echo "<hr>";
}

echo "<h2>🔍 Teste final da função strtoupper:</h2>";
try {
    $test_result = strtoupper("funciona");
    echo "<p>✅ <strong>strtoupper OK:</strong> {$test_result}</p>";
} catch (Error $e) {
    echo "<p>❌ <strong>strtoupper com ERRO:</strong> " . $e->getMessage() . "</p>";
}

?>