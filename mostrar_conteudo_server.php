<?php
/**
 * Mostrar conteúdo EXATO do process_relatorios.php no servidor
 */

header('Content-Type: text/html; charset=utf-8');

$arquivo = __DIR__ . '/process_relatorios.php';

if (!file_exists($arquivo)) {
    die('<div style="background:#f8d7da; color:#721c24; padding:20px; margin:20px; border-radius:8px; font-family:Arial;">
        ❌ Arquivo process_relatorios.php NÃO ENCONTRADO no servidor!
    </div>');
}

$conteudo = file_get_contents($arquivo);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Conteúdo do Arquivo no Servidor</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 1200px; margin: 0 auto; }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin: 15px 0; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 12px; line-height: 1.5; }
        .linha-destaque { background: #f39c12; color: #000; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📄 Conteúdo do process_relatorios.php no SERVIDOR</h1>

        <div class="info">
            <strong>📊 Informações do Arquivo:</strong><br>
            Tamanho: <?php echo number_format(filesize($arquivo)); ?> bytes<br>
            Modificado: <?php echo date('d/m/Y H:i:s', filemtime($arquivo)); ?><br>
            Caminho: <?php echo $arquivo; ?>
        </div>

        <h3>🔍 Linhas 50-60 (Primeira query com valor_total)</h3>
        <?php
        $linhas = explode("\n", $conteudo);
        echo '<pre>';
        for ($i = 49; $i < 60 && $i < count($linhas); $i++) {
            $num_linha = $i + 1;
            $linha = $linhas[$i];

            if (strpos($linha, 'valor_total') !== false) {
                echo '<span class="linha-destaque">';
                printf("%4d: %s\n", $num_linha, htmlspecialchars($linha));
                echo '</span>';
            } else {
                printf("%4d: %s\n", $num_linha, htmlspecialchars($linha));
            }
        }
        echo '</pre>';
        ?>

        <h3>🔍 Linhas 135-145 (Segunda query com valor_total)</h3>
        <?php
        echo '<pre>';
        for ($i = 134; $i < 145 && $i < count($linhas); $i++) {
            $num_linha = $i + 1;
            $linha = $linhas[$i];

            if (strpos($linha, 'valor_total') !== false) {
                echo '<span class="linha-destaque">';
                printf("%4d: %s\n", $num_linha, htmlspecialchars($linha));
                echo '</span>';
            } else {
                printf("%4d: %s\n", $num_linha, htmlspecialchars($linha));
            }
        }
        echo '</pre>';
        ?>

        <h3>🔍 Linhas 150-160 (Terceira query com valor_total)</h3>
        <?php
        echo '<pre>';
        for ($i = 149; $i < 160 && $i < count($linhas); $i++) {
            $num_linha = $i + 1;
            $linha = $linhas[$i];

            if (strpos($linha, 'valor_total') !== false) {
                echo '<span class="linha-destaque">';
                printf("%4d: %s\n", $num_linha, htmlspecialchars($linha));
                echo '</span>';
            } else {
                printf("%4d: %s\n", $num_linha, htmlspecialchars($linha));
            }
        }
        echo '</pre>';
        ?>

        <h3>🔍 Linhas 165-175 (Quarta query com valor_total)</h3>
        <?php
        echo '<pre>';
        for ($i = 164; $i < 175 && $i < count($linhas); $i++) {
            $num_linha = $i + 1;
            $linha = $linhas[$i];

            if (strpos($linha, 'valor_total') !== false) {
                echo '<span class="linha-destaque">';
                printf("%4d: %s\n", $num_linha, htmlspecialchars($linha));
                echo '</span>';
            } else {
                printf("%4d: %s\n", $num_linha, htmlspecialchars($linha));
            }
        }
        echo '</pre>';
        ?>

        <h3>🔍 Buscar TODAS as ocorrências de "valor_total_estimado"</h3>
        <?php
        $ocorrencias = [];
        foreach ($linhas as $num => $linha) {
            if (stripos($linha, 'valor_total_estimado') !== false) {
                $ocorrencias[] = [
                    'linha' => $num + 1,
                    'conteudo' => $linha
                ];
            }
        }

        if (empty($ocorrencias)) {
            echo '<div class="success">✅ NENHUMA ocorrência de "valor_total_estimado" encontrada!</div>';
        } else {
            echo '<div class="error">❌ Encontradas ' . count($ocorrencias) . ' ocorrências de "valor_total_estimado":</div>';
            echo '<pre>';
            foreach ($ocorrencias as $oc) {
                printf("%4d: %s\n", $oc['linha'], htmlspecialchars($oc['conteudo']));
            }
            echo '</pre>';
        }
        ?>

        <h3>🔍 Buscar TODAS as ocorrências de "valor_total_contratacao"</h3>
        <?php
        $ocorrencias_corretas = [];
        foreach ($linhas as $num => $linha) {
            if (stripos($linha, 'valor_total_contratacao') !== false) {
                $ocorrencias_corretas[] = [
                    'linha' => $num + 1,
                    'conteudo' => $linha
                ];
            }
        }

        if (empty($ocorrencias_corretas)) {
            echo '<div class="error">❌ NENHUMA ocorrência de "valor_total_contratacao" encontrada!</div>';
        } else {
            echo '<div class="success">✅ Encontradas ' . count($ocorrencias_corretas) . ' ocorrências de "valor_total_contratacao":</div>';
            echo '<pre>';
            foreach ($ocorrencias_corretas as $oc) {
                printf("%4d: %s\n", $oc['linha'], htmlspecialchars($oc['conteudo']));
            }
            echo '</pre>';
        }
        ?>

        <h3>📋 Primeiros 100 caracteres do arquivo</h3>
        <?php
        $inicio = substr($conteudo, 0, 100);
        echo '<pre>' . htmlspecialchars($inicio) . '</pre>';

        // Verificar BOM
        $bom = substr($conteudo, 0, 3);
        if ($bom === "\xEF\xBB\xBF") {
            echo '<div class="error">⚠️ Arquivo tem BOM UTF-8 (pode causar problemas)</div>';
        } else {
            echo '<div class="success">✅ Arquivo sem BOM</div>';
        }
        ?>
    </div>
</body>
</html>
