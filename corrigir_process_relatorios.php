<?php
/**
 * Script de Correção Automática - process_relatorios.php
 * Corrige as 4 ocorrências de valor_total_estimado para valor_total_contratacao
 */

header('Content-Type: text/html; charset=utf-8');

$arquivo = __DIR__ . '/process_relatorios.php';

if (!file_exists($arquivo)) {
    die('<div style="background:#f8d7da; color:#721c24; padding:20px; border-radius:8px; font-family:Arial;">
        ❌ Arquivo process_relatorios.php não encontrado!<br>
        📂 Caminho: ' . $arquivo . '
    </div>');
}

// Fazer backup do arquivo original
$backup = $arquivo . '.backup.' . date('Y-m-d_H-i-s');
if (!copy($arquivo, $backup)) {
    die('<div style="background:#f8d7da; color:#721c24; padding:20px; border-radius:8px; font-family:Arial;">
        ❌ Erro ao criar backup do arquivo!
    </div>');
}

// Ler conteúdo do arquivo
$conteudo = file_get_contents($arquivo);
$conteudo_original = $conteudo;

// Contar ocorrências ANTES
$ocorrencias_antes = substr_count($conteudo, 'valor_total_estimado');

// Fazer as substituições ESPECÍFICAS apenas na tabela pca_dados
// NÃO substituir na tabela licitacoes (que usa valor_estimado corretamente)

// Substituir APENAS as 4 ocorrências específicas na query de pca_dados
$conteudo = preg_replace(
    '/SUM\(valor_total_estimado\)\s+as\s+valor_total\s+FROM\s+pca_dados/i',
    'SUM(valor_total_contratacao) as valor_total FROM pca_dados',
    $conteudo,
    -1,
    $count
);

// Contar ocorrências DEPOIS
$ocorrencias_depois = substr_count($conteudo, 'valor_total_estimado');

// Verificar se houve mudanças
if ($conteudo === $conteudo_original) {
    echo '<div style="background:#fff3cd; color:#856404; padding:20px; border-radius:8px; font-family:Arial; margin:20px;">';
    echo '⚠️ Nenhuma substituição foi necessária.<br>';
    echo 'O arquivo já pode estar correto ou não contém o padrão esperado.';
    echo '</div>';
    unlink($backup); // Remover backup desnecessário
} else {
    // Salvar arquivo corrigido
    if (file_put_contents($arquivo, $conteudo) === false) {
        die('<div style="background:#f8d7da; color:#721c24; padding:20px; border-radius:8px; font-family:Arial; margin:20px;">
            ❌ Erro ao salvar arquivo corrigido!
        </div>');
    }

    echo '<div style="background:#d4edda; color:#155724; padding:20px; border-radius:8px; font-family:Arial; margin:20px;">';
    echo '<h2 style="margin-top:0;">✅ Correção Aplicada com Sucesso!</h2>';
    echo '<strong>Substituições realizadas:</strong> ' . $count . '<br>';
    echo '<strong>Ocorrências de "valor_total_estimado" ANTES:</strong> ' . $ocorrencias_antes . '<br>';
    echo '<strong>Ocorrências de "valor_total_estimado" DEPOIS:</strong> ' . $ocorrencias_depois . '<br>';
    echo '<strong>Backup criado em:</strong> ' . basename($backup) . '<br><br>';
    echo '📋 <strong>Próximo passo:</strong> Teste o Dashboard Executivo em relatorios_gerenciais.php';
    echo '</div>';

    // Mostrar detalhes das linhas corrigidas
    echo '<div style="background:#d1ecf1; color:#0c5460; padding:20px; border-radius:8px; font-family:monospace; font-size:12px; margin:20px;">';
    echo '<strong>Linhas que devem ter sido corrigidas:</strong><br><br>';

    $linhas = explode("\n", $conteudo);
    $linha_num = 0;
    foreach ($linhas as $linha) {
        $linha_num++;
        if (strpos($linha, 'valor_total_contratacao') !== false &&
            strpos($linha, 'pca_dados') === false &&
            strpos($linha, 'SUM') !== false) {
            echo "Linha $linha_num: " . htmlspecialchars(trim($linha)) . "<br>";
        }
    }
    echo '</div>';
}

// Verificação final
echo '<div style="background:#f8f9fa; padding:20px; border-radius:8px; font-family:Arial; margin:20px;">';
echo '<h3>🔍 Verificação Final</h3>';

$conteudo_final = file_get_contents($arquivo);
$tem_erro = strpos($conteudo_final, 'SUM(valor_total_estimado) as valor_total FROM pca_dados') !== false;

if ($tem_erro) {
    echo '<div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:6px;">';
    echo '❌ ATENÇÃO: Ainda existem ocorrências incorretas no arquivo!<br>';
    echo 'Pode ser necessário correção manual.';
    echo '</div>';
} else {
    echo '<div style="background:#d4edda; color:#155724; padding:15px; border-radius:6px;">';
    echo '✅ Verificação OK: Não foram encontradas ocorrências incorretas de "valor_total_estimado" em queries de pca_dados.';
    echo '</div>';
}

echo '</div>';

// Botão para testar
echo '<div style="text-align:center; margin:30px;">';
echo '<a href="verificar_process_relatorios.php" style="display:inline-block; background:#3498db; color:white; padding:15px 30px; text-decoration:none; border-radius:6px; font-family:Arial; font-weight:bold;">
    🔍 Verificar Correção
</a>';
echo ' ';
echo '<a href="relatorios_gerenciais.php" style="display:inline-block; background:#27ae60; color:white; padding:15px 30px; text-decoration:none; border-radius:6px; font-family:Arial; font-weight:bold;">
    📊 Testar Dashboard
</a>';
echo '</div>';
?>
