<?php
/**
 * Script de Verificação - process_relatorios.php
 * Verifica se o arquivo existe e lista as ações disponíveis
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Verificação - process_relatorios.php</title>
    <style>
        body { font-family: Arial; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .result { padding: 15px; margin: 15px 0; border-radius: 6px; font-family: monospace; font-size: 13px; white-space: pre-wrap; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificação - process_relatorios.php</h1>

        <h3>1️⃣ Verificar Existência do Arquivo</h3>
        <?php
        $arquivo = __DIR__ . '/process_relatorios.php';

        if (file_exists($arquivo)) {
            echo '<div class="result success">';
            echo "✅ Arquivo ENCONTRADO: process_relatorios.php\n";
            echo "📏 Tamanho: " . number_format(filesize($arquivo)) . " bytes\n";
            echo "📅 Modificado: " . date('d/m/Y H:i:s', filemtime($arquivo)) . "\n";
            echo "📂 Caminho: " . $arquivo;
            echo '</div>';

            $arquivo_existe = true;
        } else {
            echo '<div class="result error">';
            echo "❌ Arquivo NÃO ENCONTRADO: process_relatorios.php\n";
            echo "📂 Caminho esperado: " . $arquivo . "\n\n";
            echo "⚠️ SOLUÇÃO: Você precisa fazer upload do arquivo process_relatorios.php para a raiz do sistema!";
            echo '</div>';

            $arquivo_existe = false;
        }
        ?>

        <?php if ($arquivo_existe): ?>
        <h3>2️⃣ Verificar Conteúdo do Arquivo</h3>
        <?php
        $conteudo = file_get_contents($arquivo);

        // Verificar BOM UTF-8
        $primeiro_byte = ord($conteudo[0]);
        if ($primeiro_byte === 239) {
            echo '<div class="result warning">';
            echo "⚠️ AVISO: Arquivo tem BOM UTF-8 (pode causar problemas)\n";
            echo "Primeiro byte: $primeiro_byte (deveria ser 60 para '<?php')\n";
            echo "Recomendação: Salvar arquivo como UTF-8 SEM BOM";
            echo '</div>';
        } else {
            echo '<div class="result success">';
            echo "✅ Encoding correto (sem BOM)\n";
            echo "Primeiro byte: $primeiro_byte (correto para '<?')";
            echo '</div>';
        }
        ?>

        <h3>3️⃣ Verificar Ações Disponíveis</h3>
        <?php
        // Procurar por cases no switch
        preg_match_all("/case\s+['\"]([^'\"]+)['\"]/", $conteudo, $matches);

        if (!empty($matches[1])) {
            $acoes = $matches[1];
            echo '<div class="result success">';
            echo "✅ Ações encontradas no arquivo (" . count($acoes) . " total):\n\n";
            foreach ($acoes as $acao) {
                $icone = '📊';
                if (strpos($acao, 'pca') !== false) $icone = '📋';
                if (strpos($acao, 'qualificacoes') !== false) $icone = '📝';
                if (strpos($acao, 'licitacoes') !== false) $icone = '⚖️';

                echo "$icone $acao\n";
            }
            echo '</div>';

            // Verificar se tem a ação dashboard_executivo_geral
            if (in_array('dashboard_executivo_geral', $acoes)) {
                echo '<div class="result success">';
                echo "✅ Ação 'dashboard_executivo_geral' ENCONTRADA!";
                echo '</div>';
            } else {
                echo '<div class="result error">';
                echo "❌ Ação 'dashboard_executivo_geral' NÃO ENCONTRADA!\n";
                echo "⚠️ O arquivo pode estar corrompido ou incompleto.";
                echo '</div>';
            }
        } else {
            echo '<div class="result error">';
            echo "❌ Nenhuma ação encontrada no arquivo!\n";
            echo "⚠️ O arquivo pode estar vazio ou corrompido.";
            echo '</div>';
        }
        ?>

        <h3>4️⃣ Verificar Estrutura SQL (valor_total_contratacao)</h3>
        <?php
        // Verificar se usa a coluna correta
        $usa_coluna_errada = strpos($conteudo, 'valor_total_estimado') !== false;
        $usa_coluna_correta = strpos($conteudo, 'valor_total_contratacao') !== false;

        if ($usa_coluna_errada) {
            echo '<div class="result error">';
            echo "❌ ERRO: Arquivo ainda usa 'valor_total_estimado' (INCORRETO)\n";
            echo "⚠️ Precisa substituir por 'valor_total_contratacao'";
            echo '</div>';
        } elseif ($usa_coluna_correta) {
            echo '<div class="result success">';
            echo "✅ Arquivo usa 'valor_total_contratacao' (CORRETO)";
            echo '</div>';
        } else {
            echo '<div class="result warning">';
            echo "⚠️ Não foi possível verificar as colunas SQL";
            echo '</div>';
        }
        ?>

        <h3>5️⃣ Testar Requisição Real</h3>
        <div class="result info">
            <strong>📋 Para testar a requisição real:</strong><br><br>
            1. Abra o Console do navegador (F12)<br>
            2. Execute este comando JavaScript:<br><br>
            <code style="display:block; background:#2c3e50; color:#ecf0f1; padding:10px; border-radius:4px;">
fetch('process_relatorios.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'acao=dashboard_executivo_geral&ano=2025'
})
.then(r => r.text())
.then(t => console.log('Resposta:', t))
.catch(e => console.error('Erro:', e));
            </code>
        </div>
        <?php endif; ?>

        <h3>📋 Resumo</h3>
        <div class="result info">
            <strong>Status da Verificação:</strong><br><br>
            <?php if ($arquivo_existe): ?>
                ✅ Arquivo existe no servidor<br>
                ⏭️ Próximo passo: Verificar se as ações estão corretas acima
            <?php else: ?>
                ❌ Arquivo NÃO existe no servidor<br>
                ⚠️ Próximo passo: Fazer upload de process_relatorios.php
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
