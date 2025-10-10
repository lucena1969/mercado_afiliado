<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teste Completo - process_relatorios.php</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .test { background: white; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #3498db; }
        .success { border-color: #10b981; background: #d1fae5; }
        .error { border-color: #ef4444; background: #fee2e2; }
        h3 { margin: 0 0 10px 0; color: #2c3e50; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
    </style>
</head>
<body>
    <h1>🧪 Teste Completo - process_relatorios.php</h1>

    <?php
    // Simular sessão
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Teste';
    $_SESSION['user_tipo'] = 'COORDENADOR';

    $testes = [
        'dashboard_executivo_geral' => ['acao' => 'dashboard_executivo_geral', 'ano' => 2025],
        'dashboard_executivo_pca' => ['acao' => 'dashboard_executivo_pca', 'ano' => 2025],
        'dashboard_executivo_qualificacoes' => ['acao' => 'dashboard_executivo_qualificacoes'],
        'dashboard_executivo_licitacoes' => ['acao' => 'dashboard_executivo_licitacoes'],
        'relatorio_area_demandante' => ['acao' => 'relatorio_area_demandante', 'data_inicio' => '2024-01-01', 'data_fim' => '2024-12-31'],
        'get_areas_qualificacoes' => ['acao' => 'get_areas_qualificacoes']
    ];

    foreach ($testes as $nome => $params) {
        echo "<div class='test'>";
        echo "<h3>🔍 Teste: $nome</h3>";

        $_POST = $params;
        $_SERVER['REQUEST_METHOD'] = 'POST';

        ob_start();
        try {
            include 'process_relatorios.php';
            $output = ob_get_clean();

            $json = @json_decode($output, true);
            if ($json && isset($json['success'])) {
                if ($json['success']) {
                    echo "<div style='color: #10b981; font-weight: bold;'>✅ SUCESSO</div>";
                    echo "<pre>" . json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                } else {
                    echo "<div style='color: #ef4444; font-weight: bold;'>❌ ERRO</div>";
                    echo "<p>Mensagem: " . ($json['message'] ?? 'Desconhecido') . "</p>";
                    echo "<pre>" . json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                }
            } else {
                echo "<div style='color: #f59e0b; font-weight: bold;'>⚠️ RESPOSTA NÃO É JSON</div>";
                echo "<p>Primeiros 500 caracteres:</p>";
                echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre>";
            }

        } catch (Exception $e) {
            ob_end_clean();
            echo "<div style='color: #ef4444; font-weight: bold;'>❌ EXCEPTION</div>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }

        echo "</div>";

        // Limpar para próximo teste
        $_POST = [];
    }
    ?>

</body>
</html>
