0<?php
/**
 * Teste simples de conexão com o banco
 */

echo "<h2>🔍 Teste de Conexão - Mercado Afiliado</h2>";

// Verificar se o arquivo de configuração existe
if (!file_exists('config/database.php')) {
    echo "<h3>❌ Arquivo config/database.php não encontrado</h3>";
    echo "<p>Verifique se o arquivo existe no diretório correto.</p>";
    exit;
}

echo "<h3>✅ Arquivo de configuração encontrado</h3>";

require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<h3>✅ Conexão com banco estabelecida</h3>";
    
    // Verificar se o banco mercado_afiliado existe
    $stmt = $conn->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Banco atual:</strong> " . ($result['current_db'] ?? 'Não selecionado') . "</p>";
    
    // Listar todas as tabelas
    echo "<h3>📋 Tabelas Existentes</h3>";
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<p>❌ <strong>Nenhuma tabela encontrada no banco</strong></p>";
        echo "<div style='background: #fff3cd; padding: 1rem; border-radius: 4px; margin: 1rem 0;'>";
        echo "<h4>📋 Próximos passos:</h4>";
        echo "<ol>";
        echo "<li>Execute <strong>create_users_table.sql</strong> no phpMyAdmin primeiro</li>";
        echo "<li>Depois execute <strong>database/pixel_schema.sql</strong></li>";
        echo "<li>Volte aqui para verificar novamente</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>✅ {$table}</li>";
        }
        echo "</ul>";
        
        // Verificar tabelas específicas do Pixel BR
        $pixelTables = ['pixel_events', 'pixel_configurations', 'bridge_logs'];
        $missingTables = [];
        
        foreach ($pixelTables as $table) {
            if (!in_array($table, $tables)) {
                $missingTables[] = $table;
            }
        }
        
        if (!empty($missingTables)) {
            echo "<h3>⚠️ Tabelas do Pixel BR Faltando</h3>";
            echo "<ul>";
            foreach ($missingTables as $table) {
                echo "<li>❌ {$table}</li>";
            }
            echo "</ul>";
            echo "<p><strong>Execute database/pixel_schema.sql no phpMyAdmin</strong></p>";
        } else {
            echo "<h3>✅ Todas as tabelas do Pixel BR encontradas</h3>";
            
            // Testar inserção simples
            echo "<h3>🧪 Teste de Inserção</h3>";
            try {
                $testQuery = "INSERT INTO pixel_events (event_name, event_time, event_id, source_url) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($testQuery);
                $result = $stmt->execute(['test', time(), 'test_' . time(), 'http://localhost/test']);
                
                if ($result) {
                    echo "<p>✅ <strong>Teste de inserção bem-sucedido!</strong></p>";
                    echo "<p>ID inserido: " . $conn->lastInsertId() . "</p>";
                    
                    // Contar eventos
                    $stmt = $conn->query("SELECT COUNT(*) as count FROM pixel_events");
                    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    echo "<p>📊 Total de eventos no banco: <strong>{$count}</strong></p>";
                } else {
                    echo "<p>❌ Falha no teste de inserção</p>";
                }
            } catch (Exception $e) {
                echo "<p>❌ Erro no teste de inserção: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Verificar se o usuário de teste existe
    if (in_array('users', $tables)) {
        echo "<h3>👤 Verificação de Usuário de Teste</h3>";
        $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = 999");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<p>✅ Usuário de teste encontrado: <strong>{$user['name']}</strong> ({$user['email']})</p>";
        } else {
            echo "<p>⚠️ Usuário de teste não encontrado. Execute create_users_table.sql</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>🚀 Links de Teste</h3>";
    echo "<ul>";
    echo "<li><a href='test_pixel.php'>🧪 Página de Teste do Pixel</a></li>";
    echo "<li><a href='templates/pixel/index.php'>⚙️ Interface de Configuração</a></li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<h3>❌ Erro de Conexão com o Banco</h3>";
    echo "<p><strong>Erro:</strong> " . $e->getMessage() . "</p>";
    
    echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 4px; margin: 1rem 0;'>";
    echo "<h4>🔧 Verificações necessárias:</h4>";
    echo "<ol>";
    echo "<li><strong>XAMPP está rodando?</strong> Verifique o painel de controle</li>";
    echo "<li><strong>MySQL está ativo?</strong> Deve mostrar 'Running' no XAMPP</li>";
    echo "<li><strong>Banco 'mercado_afiliado' existe?</strong> Crie no phpMyAdmin se necessário</li>";
    echo "<li><strong>Configurações corretas?</strong> Verifique config/database.php</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h4>📄 Configuração atual (config/database.php):</h4>";
    echo "<pre style='background: #f8f9fa; padding: 1rem; border-radius: 4px;'>";
    if (class_exists('Database')) {
        $reflection = new ReflectionClass('Database');
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $name = $property->getName();
            if (in_array($name, ['host', 'db_name', 'username'])) {
                echo "{$name}: (verificar no arquivo)\n";
            }
        }
    }
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h3>❌ Erro Geral</h3>";
    echo "<p><strong>Erro:</strong> " . $e->getMessage() . "</p>";
}
?>

<style>
    body { 
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        max-width: 800px;
        margin: 2rem auto;
        padding: 0 1rem;
        line-height: 1.6;
    }
    h2 { color: #1f2937; }
    h3 { color: #374151; margin-top: 2rem; }
    pre { overflow-x: auto; }
    ul { padding-left: 1.5rem; }
    a { color: #3b82f6; text-decoration: none; }
    a:hover { text-decoration: underline; }
    hr { margin: 2rem 0; border: none; border-top: 1px solid #e5e7eb; }
</style>