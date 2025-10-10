<?php
/**
 * Script para corrigir a estrutura da tabela tramitacoes_kanban
 * Adiciona as colunas que estão faltando
 */

require_once 'config.php';

try {
    $pdo = conectarDB();
    
    echo "<h1>🔧 Corrigindo estrutura da tabela tramitacoes_kanban</h1>";
    
    // Verificar se as colunas existem antes de adicionar
    $colunas_para_adicionar = [
        'situacao_prazo' => "ENUM('NO_PRAZO', 'VENCENDO', 'ATRASADO') DEFAULT 'NO_PRAZO'",
        'dias_restantes' => "INT DEFAULT 0"
    ];
    
    foreach ($colunas_para_adicionar as $coluna => $definicao) {
        // Verificar se a coluna já existe
        $check = $pdo->query("SHOW COLUMNS FROM tramitacoes_kanban LIKE '$coluna'");
        
        if ($check->rowCount() == 0) {
            echo "<p>🔄 Adicionando coluna: <strong>$coluna</strong></p>";
            
            $sql = "ALTER TABLE tramitacoes_kanban ADD COLUMN $coluna $definicao";
            $pdo->exec($sql);
            
            echo "<p style='color: green;'>✅ Coluna <strong>$coluna</strong> adicionada com sucesso!</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ Coluna <strong>$coluna</strong> já existe</p>";
        }
    }
    
    echo "<h2>📋 Estrutura final da tabela:</h2>";
    $result = $pdo->query('DESCRIBE tramitacoes_kanban');
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Default</th></tr>";
    
    while ($row = $result->fetch()) {
        echo "<tr>";
        echo "<td><strong>" . $row['Field'] . "</strong></td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . ($row['Default'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>🎉 Correção concluída!</h2>";
    echo "<p><a href='tramitacao_kanban.php'>🔙 Voltar para Tramitações Kanban</a></p>";
    echo "<p><a href='debug_table_structure.php'>🔍 Verificar estrutura novamente</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 20px 0; }
    th, td { padding: 8px 12px; text-align: left; }
    th { background: #f0f0f0; }
    a { text-decoration: none; background: #007cba; color: white; padding: 8px 16px; border-radius: 4px; margin: 5px; display: inline-block; }
</style>