<?php
/**
 * Verificar Integrações do Sistema
 */
require_once 'config/app.php';
require_once 'config/database.php';

echo "<h2>🔍 Verificação das Integrações (IntegraSync)</h2>";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Verificar se a tabela integrations existe
    $stmt = $conn->query("SHOW TABLES LIKE 'integrations'");
    if ($stmt->rowCount() == 0) {
        echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 4px; margin: 1rem 0;'>";
        echo "<h3>❌ Tabela 'integrations' não existe</h3>";
        echo "<p>Execute primeiro o arquivo <strong>create_users_table.sql</strong> que cria as tabelas base.</p>";
        echo "</div>";
        exit;
    }
    
    echo "<h3>✅ Tabela 'integrations' encontrada</h3>";
    
    // Contar total de integrações
    $stmt = $conn->query("SELECT COUNT(*) as total FROM integrations");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<p><strong>Total de integrações no sistema:</strong> {$total}</p>";
    
    if ($total == 0) {
        echo "<div style='background: #fff3cd; padding: 1rem; border-radius: 4px; margin: 1rem 0;'>";
        echo "<h3>⚠️ Nenhuma integração cadastrada</h3>";
        echo "<p>As integrações são conexões com as redes de afiliado:</p>";
        echo "<ul>";
        echo "<li><strong>Hotmart</strong> - API para produtos e vendas</li>";
        echo "<li><strong>Monetizze</strong> - Dados de comissões</li>";
        echo "<li><strong>Eduzz</strong> - Métricas de campanhas</li>";
        echo "<li><strong>Braip</strong> - Tracking de conversões</li>";
        echo "</ul>";
        
        echo "<h4>🚀 Como criar integrações:</h4>";
        echo "<ol>";
        echo "<li>Acesse o menu <strong>IntegraSync</strong> no dashboard</li>";
        echo "<li>Clique em <strong>Adicionar Integração</strong></li>";
        echo "<li>Configure suas APIs das redes de afiliado</li>";
        echo "<li>Depois volte ao Pixel BR para associar</li>";
        echo "</ol>";
        
        echo "<p><a href='" . BASE_URL . "/integrations' class='btn'>🔗 Ir para IntegraSync</a></p>";
        echo "</div>";
        
        // Inserir integração de exemplo para teste
        echo "<hr>";
        echo "<h3>🧪 Criar Integração de Teste</h3>";
        echo "<form method='POST'>";
        echo "<p>Para testar o Pixel BR, posso criar uma integração de exemplo:</p>";
        echo "<input type='hidden' name='create_test_integration' value='1'>";
        echo "<button type='submit' class='btn' style='background: #10b981; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer;'>Criar Integração de Teste</button>";
        echo "</form>";
        
        if (isset($_POST['create_test_integration'])) {
            try {
                // Verificar se usuário existe (usar ID 999 do teste ou criar um usuário)
                $userStmt = $conn->prepare("SELECT id FROM users LIMIT 1");
                $userStmt->execute();
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    echo "<p>❌ Nenhum usuário encontrado. Execute create_users_table.sql primeiro.</p>";
                } else {
                    $userId = $user['id'];
                    
                    $insertStmt = $conn->prepare("
                        INSERT INTO integrations (user_id, platform, name, status, config_json) 
                        VALUES (?, 'hotmart', 'Hotmart - Teste', 'active', ?)
                    ");
                    
                    $config = json_encode([
                        'test_mode' => true,
                        'facebook_pixel_id' => '123456789',
                        'created_for_pixel_test' => true
                    ]);
                    
                    $insertStmt->execute([$userId, $config]);
                    
                    echo "<div style='background: #d4edda; padding: 1rem; border-radius: 4px; margin: 1rem 0;'>";
                    echo "<h4>✅ Integração de teste criada com sucesso!</h4>";
                    echo "<p>ID da integração: " . $conn->lastInsertId() . "</p>";
                    echo "<p>Agora você pode voltar ao Pixel BR e selecionar esta integração.</p>";
                    echo "<p><a href='" . BASE_URL . "/pixel'>🎯 Voltar ao Pixel BR</a></p>";
                    echo "</div>";
                }
            } catch (Exception $e) {
                echo "<p>❌ Erro ao criar integração: " . $e->getMessage() . "</p>";
            }
        }
        
    } else {
        // Mostrar integrações existentes
        echo "<h3>📋 Integrações Cadastradas</h3>";
        
        $stmt = $conn->query("
            SELECT i.*, u.name as user_name 
            FROM integrations i 
            LEFT JOIN users u ON i.user_id = u.id 
            ORDER BY i.created_at DESC
        ");
        
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%; margin: 1rem 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>ID</th><th>Usuário</th><th>Nome</th><th>Plataforma</th><th>Status</th><th>Criado em</th>";
        echo "</tr>";
        
        while ($integration = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $statusColor = $integration['status'] == 'active' ? '#10b981' : '#6b7280';
            echo "<tr>";
            echo "<td>{$integration['id']}</td>";
            echo "<td>{$integration['user_name']}</td>";
            echo "<td>{$integration['name']}</td>";
            echo "<td>" . ucfirst($integration['platform']) . "</td>";
            echo "<td style='color: {$statusColor}; font-weight: bold;'>" . ucfirst($integration['status']) . "</td>";
            echo "<td>{$integration['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div style='background: #e0f2fe; padding: 1rem; border-radius: 4px; margin: 1rem 0;'>";
        echo "<h4>💡 Para que serve a Integração Associada?</h4>";
        echo "<p>Quando você associa o Pixel BR a uma integração:</p>";
        echo "<ul>";
        echo "<li>🎯 <strong>Tracking específico:</strong> Eventos ficam vinculados àquela rede de afiliado</li>";
        echo "<li>🌉 <strong>Bridges automáticos:</strong> Conversões são enviadas para Facebook/Google automaticamente</li>";
        echo "<li>📊 <strong>Relatórios segmentados:</strong> Pode filtrar eventos por integração</li>";
        echo "<li>⚙️ <strong>Configurações herdadas:</strong> Usa tokens/pixels da integração</li>";
        echo "</ul>";
        echo "<p><strong>Exemplo:</strong> Se associar à integração 'Hotmart - Produtos', todas as conversões do pixel serão marcadas como vindas dessa integração.</p>";
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<p><a href='" . BASE_URL . "/pixel'>🎯 Voltar ao Pixel BR</a></p>";
    echo "<p><a href='" . BASE_URL . "/integrations'>🔗 Gerenciar IntegraSync</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Erro de Conexão</h3>";
    echo "<p>Erro: " . $e->getMessage() . "</p>";
}

echo "<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1000px; margin: 2rem auto; padding: 0 1rem; line-height: 1.6; }
    table { font-size: 0.9rem; }
    .btn { background: #3b82f6; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; display: inline-block; }
    .btn:hover { background: #2563eb; }
    a { color: #3b82f6; text-decoration: none; }
    a:hover { text-decoration: underline; }
    ul { padding-left: 1.5rem; }
    hr { margin: 2rem 0; border: none; border-top: 1px solid #e5e7eb; }
</style>";
?>