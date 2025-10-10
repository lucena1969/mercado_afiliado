<?php
/**
 * Script de instalação automática dos arquivos corrigidos
 *
 * INSTRUÇÕES:
 * 1. Faça upload deste arquivo (install_fix.php) para a raiz do sistema
 * 2. Faça upload do arquivos_corrigidos.zip para a raiz
 * 3. Acesse: http://seu-servidor.com/sistema_licitacao/install_fix.php
 * 4. Siga as instruções
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Instalação de Correções</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
.container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
.success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745; }
.error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #dc3545; }
.info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #17a2b8; }
.warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #ffc107; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
.btn { display: inline-block; padding: 12px 24px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; cursor: pointer; border: none; font-size: 16px; }
.btn:hover { background: #2980b9; }
.btn-danger { background: #dc3545; }
.btn-danger:hover { background: #c82333; }
</style></head><body><div class='container'>";

echo "<h1>🔧 Instalação de Correções - Sistema Licitação</h1>";

$zipFile = __DIR__ . '/arquivos_corrigidos.zip';
$backupDir = __DIR__ . '/backup_before_fix_' . date('Y-m-d_H-i-s');

if (isset($_POST['action']) && $_POST['action'] === 'install') {

    echo "<div class='info'>🚀 <strong>Iniciando instalação...</strong></div>";

    // Verificar se ZIP existe
    if (!file_exists($zipFile)) {
        echo "<div class='error'>❌ <strong>Erro:</strong> Arquivo 'arquivos_corrigidos.zip' não encontrado!</div>";
        echo "<div class='info'>Faça upload do arquivo ZIP para a raiz do sistema.</div>";
    } else {

        echo "<div class='success'>✅ Arquivo ZIP encontrado: " . basename($zipFile) . "</div>";

        // Criar backup dos arquivos atuais
        echo "<div class='info'>📦 Criando backup dos arquivos atuais...</div>";

        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filesToBackup = [
            'process.php',
            'functions.php',
            'licitacao_dashboard.php',
            'assets/licitacao-dashboard.js',
            'api/get_licitacao.php'
        ];

        foreach ($filesToBackup as $file) {
            $source = __DIR__ . '/' . $file;
            if (file_exists($source)) {
                $dest = $backupDir . '/' . basename($file);
                copy($source, $dest);
                echo "<div class='success'>✅ Backup: " . basename($file) . "</div>";
            }
        }

        // Extrair ZIP
        echo "<div class='info'>📂 Extraindo arquivos corrigidos...</div>";

        $zip = new ZipArchive;
        if ($zip->open($zipFile) === TRUE) {

            // Extrair cada arquivo
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $fileinfo = pathinfo($filename);

                // Criar diretório se necessário
                $targetDir = __DIR__ . '/' . dirname($filename);
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                // Extrair arquivo
                $content = $zip->getFromIndex($i);
                $targetFile = __DIR__ . '/' . $filename;

                file_put_contents($targetFile, $content);
                chmod($targetFile, 0644);

                // Verificar BOM
                $firstByte = ord(substr($content, 0, 1));
                $bomStatus = ($firstByte === 60) ? '✅ Correto (<?php)' : '⚠️ Byte: ' . $firstByte;

                echo "<div class='success'>✅ Instalado: $filename - $bomStatus</div>";
            }

            $zip->close();

            echo "<div class='success'><strong>🎉 Instalação concluída com sucesso!</strong></div>";
            echo "<div class='info'>📁 Backup salvo em: " . basename($backupDir) . "/</div>";
            echo "<div class='warning'>⚠️ <strong>IMPORTANTE:</strong> Teste o sistema agora! Se houver problemas, restaure o backup.</div>";

            echo "<div style='margin-top: 20px;'>";
            echo "<a href='licitacao_dashboard.php' class='btn'>🔗 Testar Sistema</a>";
            echo "<a href='debug_server.php' class='btn'>🔍 Verificar Debug</a>";
            echo "</div>";

        } else {
            echo "<div class='error'>❌ <strong>Erro:</strong> Não foi possível abrir o arquivo ZIP!</div>";
        }
    }

} else {

    // Exibir informações
    echo "<div class='info'>";
    echo "<h3>📋 Pré-requisitos</h3>";
    echo "<ul>";
    echo "<li>✅ PHP ZipArchive disponível: " . (class_exists('ZipArchive') ? 'SIM' : 'NÃO') . "</li>";
    echo "<li>✅ Arquivo ZIP presente: " . (file_exists($zipFile) ? 'SIM' : 'NÃO') . "</li>";
    echo "<li>✅ Diretório gravável: " . (is_writable(__DIR__) ? 'SIM' : 'NÃO') . "</li>";
    echo "</ul>";
    echo "</div>";

    if (!file_exists($zipFile)) {
        echo "<div class='error'>";
        echo "<h3>❌ Arquivo ZIP não encontrado!</h3>";
        echo "<p>Faça upload do arquivo <strong>arquivos_corrigidos.zip</strong> para:</p>";
        echo "<pre>" . __DIR__ . "/arquivos_corrigidos.zip</pre>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<h3>✅ Pronto para instalar!</h3>";
        echo "<p>Os seguintes arquivos serão atualizados:</p>";
        echo "<ul>";
        echo "<li>process.php</li>";
        echo "<li>functions.php</li>";
        echo "<li>licitacao_dashboard.php</li>";
        echo "<li>assets/licitacao-dashboard.js</li>";
        echo "<li>api/get_licitacao.php</li>";
        echo "</ul>";
        echo "<p><strong>Um backup automático será criado antes da instalação.</strong></p>";
        echo "</div>";

        echo "<form method='POST'>";
        echo "<input type='hidden' name='action' value='install'>";
        echo "<button type='submit' class='btn'>🚀 Instalar Correções</button>";
        echo "</form>";
    }
}

echo "</div></body></html>";
