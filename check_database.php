<?php
/**
 * Script para verificar se as tabelas do Pixel BR foram criadas
 */
require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<h2>🔍 Verificação das Tabelas do Pixel BR</h2>";
    
    $tables_to_check = [
        'pixel_events' => 'Eventos do Pixel',
        'pixel_configurations' => 'Configurações do Pixel', 
        'bridge_logs' => 'Logs dos Bridges'
    ];
    
    foreach ($tables_to_check as $table => $description) {
        echo "<h3>📋 Tabela: {$table} ({$description})</h3>";
        
        // Verificar se a tabela existe
        $stmt = $conn->prepare("SHOW TABLES LIKE '{$table}'");
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo "✅ <strong>Tabela existe</strong><br>";
            
            // Mostrar estrutura da tabela
            $stmt = $conn->prepare("DESCRIBE {$table}");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr style='background: #f0f0f0;'><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Contar registros
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM {$table}");
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<p>📊 <strong>{$count} registros</strong> na tabela</p>";
            
        } else {
            echo "❌ <strong>Tabela NÃO existe</strong><br>";
            echo "<p>Execute o arquivo <code>database/pixel_schema.sql</code> no phpMyAdmin</p>";
        }
        
        echo "<hr>";
    }
    
    // Verificar views
    echo "<h3>📊 Views</h3>";
    $views_to_check = ['user_pixel_summary', 'pixel_performance', 'utm_performance'];
    
    foreach ($views_to_check as $view) {
        $stmt = $conn->prepare("SHOW TABLES LIKE '{$view}'");
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo "✅ View <strong>{$view}</strong> existe<br>";
        } else {
            echo "❌ View <strong>{$view}</strong> não existe<br>";
        }
    }
    
    // Testar inserção
    echo "<hr><h3>🧪 Teste de Inserção</h3>";
    
    if (isset($_GET['test_insert'])) {
        try {
            $stmt = $conn->prepare("INSERT INTO pixel_events (event_name, event_time, event_id, source_url, custom_data_json) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                'test',
                time(),
                'test_' . time(),
                'http://localhost/test',
                json_encode(['test' => true])
            ]);
            
            if ($result) {
                echo "✅ Teste de inserção bem-sucedido!<br>";
                echo "ID inserido: " . $conn->lastInsertId() . "<br>";
            } else {
                echo "❌ Falha no teste de inserção<br>";
            }
        } catch (Exception $e) {
            echo "❌ Erro no teste de inserção: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "<a href='?test_insert=1'>🧪 Executar teste de inserção</a><br>";
    }
    
    echo "<hr>";
    echo "<p><a href='test_pixel.php'>🧪 Ir para página de teste do Pixel</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Erro de Conexão</h2>";
    echo "<p>Erro: " . $e->getMessage() . "</p>";
    echo "<p>Verifique:</p>";
    echo "<ul>";
    echo "<li>Se o XAMPP está rodando</li>";
    echo "<li>Se o MySQL está ativo</li>";
    echo "<li>Se o banco 'mercado_afiliado' existe</li>";
    echo "<li>As configurações em config/database.php</li>";
    echo "</ul>";
}
?>

<style>
    body { 
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1rem;
        line-height: 1.6;
    }
    table {
        width: 100%;
        font-size: 0.9rem;
    }
    th {
        background: #f8f9fa;
        font-weight: 600;
    }
    td, th {
        padding: 8px;
        text-align: left;
    }
    hr {
        margin: 2rem 0;
        border: none;
        border-top: 1px solid #e5e7eb;
    }
    a {
        color: #3b82f6;
        text-decoration: none;
    }
    a:hover {
        text-decoration: underline;
    }
</style>