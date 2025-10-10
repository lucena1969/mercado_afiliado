<?php
/**
 * Debug completo da requisição para process_relatorios.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Debug - Requisição Dashboard Executivo</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 1000px; margin: 0 auto; }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .result { padding: 15px; margin: 15px 0; border-radius: 6px; font-family: monospace; font-size: 13px; white-space: pre-wrap; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        button { background: #3498db; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 14px; margin: 5px; }
        button:hover { background: #2980b9; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 6px; overflow-x: auto; max-height: 400px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug - Dashboard Executivo</h1>

        <div class="info">
            <strong>📋 Este teste vai:</strong><br>
            1. Fazer requisição POST para process_relatorios.php<br>
            2. Capturar a resposta COMPLETA (incluindo possíveis erros PHP)<br>
            3. Mostrar headers, status HTTP, e corpo da resposta<br>
            4. Tentar parsear como JSON
        </div>

        <h3>Teste 1: Requisição via PHP (server-side)</h3>
        <button onclick="testarPHP()">▶️ Testar via PHP</button>
        <div id="resultado-php"></div>

        <h3>Teste 2: Requisição via JavaScript (client-side)</h3>
        <button onclick="testarJS()">▶️ Testar via JavaScript</button>
        <div id="resultado-js"></div>

        <h3>Teste 3: Verificar arquivo process_relatorios.php</h3>
        <?php
        $arquivo = __DIR__ . '/process_relatorios.php';
        if (file_exists($arquivo)) {
            echo '<div class="result success">';
            echo "✅ Arquivo existe\n";
            echo "📏 Tamanho: " . number_format(filesize($arquivo)) . " bytes\n";
            echo "📅 Modificado: " . date('d/m/Y H:i:s', filemtime($arquivo));
            echo '</div>';

            // Verificar se tem a ação
            $conteudo = file_get_contents($arquivo);
            if (strpos($conteudo, "case 'dashboard_executivo_geral':") !== false) {
                echo '<div class="result success">✅ Ação "dashboard_executivo_geral" encontrada no arquivo</div>';
            } else {
                echo '<div class="result error">❌ Ação "dashboard_executivo_geral" NÃO encontrada no arquivo</div>';
            }
        } else {
            echo '<div class="result error">❌ Arquivo NÃO existe</div>';
        }
        ?>
    </div>

    <script>
        // Teste via PHP (AJAX para endpoint PHP)
        async function testarPHP() {
            const container = document.getElementById('resultado-php');
            container.innerHTML = '<div class="result info">⏳ Testando...</div>';

            try {
                const response = await fetch('debug_requisicao_backend.php', {
                    method: 'POST'
                });

                const text = await response.text();
                container.innerHTML = text;

            } catch (error) {
                container.innerHTML = '<div class="result error">❌ Erro: ' + error.message + '</div>';
            }
        }

        // Teste via JavaScript (direto para process_relatorios.php)
        async function testarJS() {
            const container = document.getElementById('resultado-js');
            container.innerHTML = '<div class="result info">⏳ Testando...</div>';

            try {
                console.log('🚀 Iniciando requisição para process_relatorios.php');

                const bodyData = 'acao=dashboard_executivo_geral&ano=2025';
                console.log('📤 Body:', bodyData);

                const response = await fetch('process_relatorios.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: bodyData
                });

                console.log('📡 Status HTTP:', response.status);
                console.log('📋 Headers:', response.headers);

                // Ler como texto primeiro
                const responseText = await response.text();
                console.log('📄 Resposta (texto):', responseText);

                let html = '<div class="result info">';
                html += '<strong>Status HTTP:</strong> ' + response.status + '<br>';
                html += '<strong>Content-Type:</strong> ' + response.headers.get('Content-Type') + '<br>';
                html += '<strong>Tamanho da resposta:</strong> ' + responseText.length + ' bytes<br>';
                html += '</div>';

                // Verificar se é JSON válido
                let isJSON = false;
                let jsonData = null;

                try {
                    jsonData = JSON.parse(responseText);
                    isJSON = true;
                } catch (e) {
                    // Não é JSON
                }

                if (isJSON) {
                    html += '<div class="result ' + (jsonData.success ? 'success' : 'error') + '">';
                    html += '<strong>✅ Resposta JSON válida:</strong><br>';
                    html += '<pre>' + JSON.stringify(jsonData, null, 2) + '</pre>';
                    html += '</div>';
                } else {
                    html += '<div class="result error">';
                    html += '<strong>❌ Resposta NÃO é JSON válido:</strong><br>';
                    html += '<strong>Primeiros 500 caracteres:</strong><br>';
                    html += '<pre>' + escapeHtml(responseText.substring(0, 500)) + '</pre>';
                    html += '</div>';

                    // Verificar se tem erro PHP
                    if (responseText.includes('Fatal error') || responseText.includes('Warning') || responseText.includes('Notice')) {
                        html += '<div class="result error">';
                        html += '<strong>⚠️ ERRO PHP DETECTADO:</strong><br>';
                        html += '<pre>' + escapeHtml(responseText) + '</pre>';
                        html += '</div>';
                    }
                }

                container.innerHTML = html;

            } catch (error) {
                console.error('❌ Erro:', error);
                container.innerHTML = '<div class="result error">❌ Erro: ' + error.message + '</div>';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
