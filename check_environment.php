<?php
/**
 * Verificador de Ambiente - Sistema CGLIC
 * Verifica se o ambiente está configurado corretamente
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Ambiente - Sistema CGLIC</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; text-align: center; margin-bottom: 30px; }
        .check-item { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 5px solid #6c757d; }
        .check-item.success { border-left-color: #28a745; background: #d4edda; }
        .check-item.warning { border-left-color: #ffc107; background: #fff3cd; }
        .check-item.error { border-left-color: #dc3545; background: #f8d7da; }
        .check-title { font-weight: bold; margin-bottom: 5px; }
        .check-detail { font-size: 0.9em; color: #6c757d; }
        .section-title { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin: 30px 0 20px 0; }
        .status-summary { display: flex; justify-content: space-around; margin: 20px 0; }
        .status-box { text-align: center; padding: 15px; border-radius: 8px; color: white; font-weight: bold; }
        .status-success { background: #28a745; }
        .status-warning { background: #ffc107; }
        .status-error { background: #dc3545; }
        .recommendations { background: #e3f2fd; padding: 15px; border-radius: 8px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificação de Ambiente - Sistema CGLIC</h1>
        
        <?php
        $checks = [];
        $warnings = 0;
        $errors = 0;
        $success = 0;
        
        // Função para adicionar verificação
        function addCheck($title, $status, $detail, $recommendation = '') {
            global $checks, $warnings, $errors, $success;
            
            $checks[] = [
                'title' => $title,
                'status' => $status,
                'detail' => $detail,
                'recommendation' => $recommendation
            ];
            
            switch($status) {
                case 'success': $success++; break;
                case 'warning': $warnings++; break;
                case 'error': $errors++; break;
            }
        }
        
        // 1. Verificações do PHP
        $php_version = phpversion();
        if (version_compare($php_version, '7.4', '>=')) {
            addCheck('Versão do PHP', 'success', "PHP $php_version (Compatível)");
        } else {
            addCheck('Versão do PHP', 'error', "PHP $php_version (Necessário 7.4+)", 'Atualize o PHP para versão 7.4 ou superior');
        }
        
        // 2. Extensões PHP
        $required_extensions = ['pdo', 'pdo_mysql', 'json', 'session', 'fileinfo'];
        foreach ($required_extensions as $ext) {
            if (extension_loaded($ext)) {
                addCheck("Extensão PHP: $ext", 'success', 'Carregada');
            } else {
                addCheck("Extensão PHP: $ext", 'error', 'Não encontrada', "Instale a extensão php-$ext");
            }
        }
        
        // 3. Verificação de permissões de arquivos
        $writable_dirs = ['uploads', 'cache', 'logs', 'backups'];
        foreach ($writable_dirs as $dir) {
            if (!file_exists($dir)) {
                if (mkdir($dir, 0755, true)) {
                    addCheck("Diretório: $dir", 'success', 'Criado com sucesso');
                } else {
                    addCheck("Diretório: $dir", 'error', 'Não foi possível criar', "Crie manualmente: mkdir $dir");
                }
            } elseif (is_writable($dir)) {
                addCheck("Permissão: $dir", 'success', 'Gravável');
            } else {
                addCheck("Permissão: $dir", 'warning', 'Sem permissão de escrita', "Execute: chmod 755 $dir");
            }
        }
        
        // 4. Verificação de configuração
        if (file_exists('.env')) {
            addCheck('Arquivo .env', 'success', 'Encontrado');
        } else {
            addCheck('Arquivo .env', 'warning', 'Não encontrado', 'Execute o instalador ou copie .env.example para .env');
        }
        
        if (file_exists('config.php')) {
            addCheck('Arquivo config.php', 'success', 'Encontrado');
        } else {
            addCheck('Arquivo config.php', 'error', 'Não encontrado', 'Arquivo essencial ausente');
        }
        
        // 5. Teste de conexão com banco
        try {
            if (file_exists('config.php')) {
                require_once 'config.php';
                $pdo = conectarDB();
                addCheck('Conexão MySQL', 'success', 'Conectado com sucesso');
                
                // Verificar tabelas
                $tables = ['usuarios', 'pca_dados', 'licitacoes'];
                foreach ($tables as $table) {
                    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                    if ($stmt->rowCount() > 0) {
                        addCheck("Tabela: $table", 'success', 'Existe');
                    } else {
                        addCheck("Tabela: $table", 'error', 'Não encontrada', 'Execute o script SQL de instalação');
                    }
                }
            }
        } catch (Exception $e) {
            addCheck('Conexão MySQL', 'error', $e->getMessage(), 'Verifique as credenciais no .env ou config.php');
        }
        
        // 6. Verificações de servidor web
        $document_root = $_SERVER['DOCUMENT_ROOT'] ?? 'N/A';
        $server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'N/A';
        addCheck('Servidor Web', 'success', $server_software);
        addCheck('Document Root', 'success', $document_root);
        
        // Auto-detectar URL
        $auto_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $auto_url .= $_SERVER['HTTP_HOST'] ?? 'localhost';
        $auto_url .= dirname($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) . '/' : '/';
        $auto_url = str_replace('\\', '/', $auto_url);
        addCheck('URL Auto-detectada', 'success', $auto_url);
        
        // 7. Verificações de memória e limites
        $memory_limit = ini_get('memory_limit');
        $upload_max = ini_get('upload_max_filesize');
        $post_max = ini_get('post_max_size');
        
        addCheck('Limite de Memória', 'success', $memory_limit);
        addCheck('Upload Máximo', 'success', $upload_max);
        addCheck('POST Máximo', 'success', $post_max);
        
        // Mostrar resumo
        $total = count($checks);
        ?>
        
        <div class="status-summary">
            <div class="status-box status-success">
                ✅ Sucesso<br><?= $success ?>/<?= $total ?>
            </div>
            <div class="status-box status-warning">
                ⚠️ Avisos<br><?= $warnings ?>
            </div>
            <div class="status-box status-error">
                ❌ Erros<br><?= $errors ?>
            </div>
        </div>
        
        <?php if ($errors == 0 && $warnings == 0): ?>
            <div class="check-item success">
                <div class="check-title">🎉 Ambiente Perfeito!</div>
                <div class="check-detail">Todas as verificações passaram. O sistema está pronto para uso!</div>
            </div>
        <?php elseif ($errors == 0): ?>
            <div class="check-item warning">
                <div class="check-title">⚠️ Ambiente Funcional com Avisos</div>
                <div class="check-detail">O sistema funcionará, mas recomendamos resolver os avisos abaixo.</div>
            </div>
        <?php else: ?>
            <div class="check-item error">
                <div class="check-title">❌ Problemas Encontrados</div>
                <div class="check-detail">Resolva os erros abaixo antes de usar o sistema.</div>
            </div>
        <?php endif; ?>
        
        <h2 class="section-title">Detalhes das Verificações</h2>
        
        <?php foreach ($checks as $check): ?>
            <div class="check-item <?= $check['status'] ?>">
                <div class="check-title">
                    <?php 
                    switch($check['status']) {
                        case 'success': echo '✅'; break;
                        case 'warning': echo '⚠️'; break;
                        case 'error': echo '❌'; break;
                    }
                    ?>
                    <?= htmlspecialchars($check['title']) ?>
                </div>
                <div class="check-detail"><?= htmlspecialchars($check['detail']) ?></div>
                <?php if (!empty($check['recommendation'])): ?>
                    <div style="margin-top: 8px; font-weight: bold; color: #856404;">
                        💡 Recomendação: <?= htmlspecialchars($check['recommendation']) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <?php if ($errors > 0 || $warnings > 0): ?>
            <div class="recommendations">
                <h3>🛠️ Próximos Passos Recomendados:</h3>
                <ol>
                    <?php if ($errors > 0): ?>
                        <li><strong>Resolver Erros:</strong> Corrija os itens marcados com ❌</li>
                    <?php endif; ?>
                    <?php if ($warnings > 0): ?>
                        <li><strong>Resolver Avisos:</strong> Melhore os itens marcados com ⚠️</li>
                    <?php endif; ?>
                    <?php if (!file_exists('.env')): ?>
                        <li><strong>Executar Instalador:</strong> Acesse <a href="install.php">install.php</a></li>
                    <?php endif; ?>
                    <li><strong>Testar Sistema:</strong> Acesse <a href="index.php">index.php</a></li>
                    <li><strong>Ler Documentação:</strong> Consulte README.md e CLAUDE.md</li>
                </ol>
            </div>
        <?php else: ?>
            <div style="text-align: center; margin-top: 30px;">
                <a href="index.php" style="display: inline-block; background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">
                    🚀 Acessar Sistema
                </a>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-size: 0.9em; color: #6c757d;">
            <strong>📋 Informações do Sistema:</strong><br>
            <strong>Sistema:</strong> <?= php_uname() ?><br>
            <strong>PHP SAPI:</strong> <?= php_sapi_name() ?><br>
            <strong>Timezone:</strong> <?= date_default_timezone_get() ?><br>
            <strong>Data/Hora:</strong> <?= date('Y-m-d H:i:s') ?>
        </div>
    </div>
</body>
</html>