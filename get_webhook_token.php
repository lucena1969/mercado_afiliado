<?php
/**
 * Script temporário para visualizar tokens de webhook
 * APAGUE após usar por segurança!
 */

require_once 'config/app.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Buscar todas as integrações
    $query = "SELECT id, user_id, platform, name, webhook_token, status, created_at
              FROM integrations
              ORDER BY platform, created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $integrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>🔑 Tokens de Webhook das Integrações</h2>\n";
    echo "<style>
        body { font-family: monospace; padding: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:hover { background-color: #f5f5f5; }
        .token { background: #fffbcc; padding: 5px; border-radius: 3px; }
        .url { background: #e3f2fd; padding: 5px; border-radius: 3px; word-break: break-all; }
    </style>\n";

    if (empty($integrations)) {
        echo "<p>⚠️ Nenhuma integração encontrada no banco de dados.</p>\n";
        echo "<p>Você precisa criar uma integração Monetizze primeiro no painel do sistema.</p>\n";
    } else {
        echo "<table>\n";
        echo "<tr>
                <th>ID</th>
                <th>Plataforma</th>
                <th>Nome</th>
                <th>Status</th>
                <th>Webhook Token</th>
                <th>URL Completa</th>
              </tr>\n";

        foreach ($integrations as $integration) {
            $webhook_url = "http://localhost/mercado_afiliado/api/postback/monetizze.php?token=" . $integration['webhook_token'];

            echo "<tr>\n";
            echo "  <td>{$integration['id']}</td>\n";
            echo "  <td><strong>{$integration['platform']}</strong></td>\n";
            echo "  <td>{$integration['name']}</td>\n";
            echo "  <td>{$integration['status']}</td>\n";
            echo "  <td class='token'>{$integration['webhook_token']}</td>\n";
            echo "  <td class='url'>{$webhook_url}</td>\n";
            echo "</tr>\n";
        }

        echo "</table>\n";

        echo "<hr>\n";
        echo "<h3>📝 Como usar:</h3>\n";
        echo "<ol>\n";
        echo "  <li>Copie a <strong>URL Completa</strong> da integração Monetizze</li>\n";
        echo "  <li>No painel da Monetizze: <strong>Ferramentas → Postback</strong></li>\n";
        echo "  <li>Selecione tipo: <strong>Server to Server</strong></li>\n";
        echo "  <li>Cole a URL no campo indicado</li>\n";
        echo "  <li>Escolha o formato: <strong>JSON</strong> (recomendado) ou <strong>x-www-form-urlencoded</strong></li>\n";
        echo "  <li>Clique em <strong>Testar</strong> para enviar um postback de exemplo</li>\n";
        echo "</ol>\n";

        echo "<hr>\n";
        echo "<p style='color: red;'><strong>⚠️ IMPORTANTE:</strong> Apague este arquivo após visualizar os tokens!</p>\n";
    }

} catch (Exception $e) {
    echo "<h2>❌ Erro ao consultar banco de dados</h2>\n";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
