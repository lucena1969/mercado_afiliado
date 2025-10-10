<?php
/**
 * Teste DIRETO - Simula chamada ao process_relatorios.php
 * sem fazer requisição HTTP
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Teste Direto - process_relatorios.php</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 1000px; margin: 0 auto; }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .result { padding: 15px; margin: 15px 0; border-radius: 6px; font-family: monospace; font-size: 13px; white-space: pre-wrap; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 6px; overflow-x: auto; max-height: 400px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Teste Direto - process_relatorios.php</h1>

        <h3>1️⃣ Verificar arquivo .htaccess</h3>
        <?php
        $htaccess = __DIR__ . '/.htaccess';
        if (file_exists($htaccess)) {
            echo '<div class="result info">';
            echo "📋 Arquivo .htaccess ENCONTRADO:\n\n";
            echo htmlspecialchars(file_get_contents($htaccess));
            echo '</div>';
        } else {
            echo '<div class="result success">✅ Nenhum arquivo .htaccess encontrado</div>';
        }
        ?>

        <h3>2️⃣ Simular requisição POST diretamente</h3>
        <?php
        // Simular POST
        $_POST = array(
            'acao' => 'dashboard_executivo_geral',
            'ano' => 2025
        );
        $_SERVER['REQUEST_METHOD'] = 'POST';

        echo '<div class="result info">';
        echo "📤 Simulando POST:\n";
        echo "acao = dashboard_executivo_geral\n";
        echo "ano = 2025\n";
        echo '</div>';

        // Capturar output
        ob_start();

        // Incluir o arquivo process_relatorios.php
        $arquivo = __DIR__ . '/process_relatorios.php';

        if (!file_exists($arquivo)) {
            echo '<div class="result error">❌ Arquivo process_relatorios.php NÃO ENCONTRADO!</div>';
        } else {
            echo '<div class="result info">📁 Incluindo arquivo: ' . $arquivo . '</div>';

            try {
                include $arquivo;
                $output = ob_get_clean();

                echo '<div class="result success">';
                echo "✅ Arquivo executado com sucesso!\n\n";
                echo "📊 Output capturado (" . strlen($output) . " bytes):\n";
                echo '</div>';

                // Verificar se é JSON
                $json = @json_decode($output, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    echo '<div class="result success">';
                    echo "✅ Resposta JSON válida:\n\n";
                    echo '<pre>' . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
                    echo '</div>';

                    if (isset($json['success']) && $json['success']) {
                        echo '<div class="result success">';
                        echo "🎉 SUCESSO! A ação dashboard_executivo_geral funcionou!\n\n";
                        echo "Dados retornados:\n";
                        echo "- dados_planejadas: " . (isset($json['dados_planejadas']) ? count($json['dados_planejadas']) : 0) . " registros\n";
                        echo "- dados_executadas: " . (isset($json['dados_executadas']) ? count($json['dados_executadas']) : 0) . " registros\n";
                        echo "- dados_taxa_execucao: " . (isset($json['dados_taxa_execucao']) ? count($json['dados_taxa_execucao']) : 0) . " registros\n";
                        echo '</div>';
                    } else {
                        echo '<div class="result error">';
                        echo "❌ Erro na resposta:\n";
                        echo htmlspecialchars($json['message'] ?? 'Erro desconhecido');
                        echo '</div>';
                    }
                } else {
                    echo '<div class="result error">';
                    echo "❌ Resposta NÃO é JSON válido:\n\n";
                    echo "Erro: " . json_last_error_msg() . "\n\n";
                    echo "Output:\n";
                    echo '<pre>' . htmlspecialchars($output) . '</pre>';
                    echo '</div>';
                }

            } catch (Exception $e) {
                ob_end_clean();
                echo '<div class="result error">';
                echo "❌ Erro ao executar arquivo:\n";
                echo htmlspecialchars($e->getMessage());
                echo '</div>';
            }
        }
        ?>

        <h3>3️⃣ Verificar URL de acesso direto</h3>
        <?php
        $url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/process_relatorios.php';
        echo '<div class="result info">';
        echo "🌐 URL completa do arquivo:\n";
        echo htmlspecialchars($url) . "\n\n";
        echo "Tente acessar diretamente no navegador:\n";
        echo '<a href="' . htmlspecialchars($url) . '" target="_blank">' . htmlspecialchars($url) . '</a>';
        echo '</div>';
        ?>

        <h3>4️⃣ Listar arquivos .php na raiz</h3>
        <?php
        $files = glob(__DIR__ . '/*.php');
        echo '<div class="result info">';
        echo "📁 Arquivos .php encontrados na raiz:\n\n";
        foreach ($files as $file) {
            $nome = basename($file);
            if ($nome === 'process_relatorios.php') {
                echo "✅ " . $nome . " (" . number_format(filesize($file)) . " bytes)\n";
            } else {
                echo "   " . $nome . "\n";
            }
        }
        echo '</div>';
        ?>
    </div>
</body>
</html>
