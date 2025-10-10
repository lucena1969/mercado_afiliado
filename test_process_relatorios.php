<?php
/**
 * Teste Rápido - Verificar se process_relatorios.php está funcionando
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Teste - process_relatorios.php</title>
    <style>
        body { font-family: Arial; max-width: 1000px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .result { padding: 15px; margin: 15px 0; border-radius: 6px; font-family: monospace; font-size: 13px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        button { background: #3498db; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 14px; margin: 5px; }
        button:hover { background: #2980b9; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 6px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Teste - process_relatorios.php</h1>

        <div class="result info">
            <strong>📋 O que este teste faz:</strong><br>
            1. Verifica se o arquivo process_relatorios.php existe<br>
            2. Tenta chamar a ação dashboard_executivo_geral<br>
            3. Mostra o resultado JSON retornado
        </div>

        <h3>1️⃣ Verificar Arquivo</h3>
        <?php
        $arquivo = __DIR__ . '/process_relatorios.php';
        if (file_exists($arquivo)) {
            echo '<div class="result success">';
            echo "✅ Arquivo encontrado: process_relatorios.php<br>";
            echo "📏 Tamanho: " . number_format(filesize($arquivo)) . " bytes<br>";
            echo "📅 Modificado: " . date('d/m/Y H:i:s', filemtime($arquivo));
            echo '</div>';
        } else {
            echo '<div class="result error">';
            echo "❌ Arquivo NÃO ENCONTRADO: process_relatorios.php<br>";
            echo "📂 Caminho esperado: " . $arquivo;
            echo '</div>';
            echo '<div class="result info">⚠️ Você precisa fazer upload do arquivo process_relatorios.php para a raiz do sistema!</div>';
            exit;
        }
        ?>

        <h3>2️⃣ Testar Ação dashboard_executivo_geral</h3>
        <button onclick="testarAcao()">▶️ Executar Teste</button>
        <div id="resultado"></div>

        <h3>3️⃣ Testar Outras Ações</h3>
        <button onclick="testarAcao('dashboard_executivo_pca', {ano: 2025})">▶️ Testar PCA</button>
        <button onclick="testarAcao('dashboard_executivo_qualificacoes')">▶️ Testar Qualificações</button>
        <button onclick="testarAcao('dashboard_executivo_licitacoes')">▶️ Testar Licitações</button>
        <div id="resultado-extras"></div>
    </div>

    <script>
        async function testarAcao(acao = 'dashboard_executivo_geral', params = {}) {
            const container = acao === 'dashboard_executivo_geral' ?
                document.getElementById('resultado') :
                document.getElementById('resultado-extras');

            container.innerHTML = '<div class="result info">⏳ Testando ação: ' + acao + '...</div>';

            try {
                // Preparar dados
                const formData = new URLSearchParams();
                formData.append('acao', acao);

                // Adicionar parâmetros extras
                for (const [key, value] of Object.entries(params)) {
                    formData.append(key, value);
                }

                console.log('📤 Enviando:', formData.toString());

                // Fazer requisição
                const response = await fetch('process_relatorios.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData.toString()
                });

                console.log('📡 Status HTTP:', response.status);

                // Ler resposta como texto primeiro
                const responseText = await response.text();
                console.log('📄 Resposta (texto):', responseText);

                // Tentar parsear JSON
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (jsonError) {
                    throw new Error('Resposta não é JSON válido. Primeira linha: ' + responseText.substring(0, 100));
                }

                console.log('📊 Dados parseados:', data);

                // Mostrar resultado
                if (data.success) {
                    let html = '<div class="result success">';
                    html += '<strong>✅ Sucesso! Ação: ' + acao + '</strong><br><br>';
                    html += '<strong>Dados retornados:</strong><br>';
                    html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    html += '</div>';
                    container.innerHTML = html;
                } else {
                    let html = '<div class="result error">';
                    html += '<strong>❌ Erro retornado pelo servidor:</strong><br>';
                    html += data.message || 'Erro desconhecido';
                    html += '<br><br><strong>Resposta completa:</strong><br>';
                    html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    html += '</div>';
                    container.innerHTML = html;
                }

            } catch (error) {
                console.error('❌ Erro:', error);
                let html = '<div class="result error">';
                html += '<strong>❌ Erro ao testar:</strong><br>';
                html += error.message;
                html += '</div>';
                container.innerHTML = html;
            }
        }

        // Auto-executar o primeiro teste
        window.addEventListener('DOMContentLoaded', function() {
            console.log('✅ Página carregada, pronto para testes');
        });
    </script>
</body>
</html>
