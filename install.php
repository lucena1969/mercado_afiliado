<?php
/**
 * Script de Instalação Automática
 * Sistema de Licitações CGLIC
 */

// Verificar se já está instalado
if (file_exists('.env') && file_exists('config.php')) {
    $check_config = true;
    try {
        require_once 'config.php';
        $pdo = conectarDB();
        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios LIMIT 1");
        if ($stmt && $stmt->fetchColumn() > 0) {
            header('Location: index.php');
            exit;
        }
    } catch (Exception $e) {
        $check_config = false;
    }
    
    if ($check_config) {
        header('Location: index.php');
        exit;
    }
}

$step = $_GET['step'] ?? 1;
$errors = [];
$success = [];

// Processar formulários
if ($_POST) {
    switch ($step) {
        case 1:
            // Verificar conexão com banco
            $host = $_POST['db_host'] ?? 'localhost';
            $port = $_POST['db_port'] ?? '3306';
            $name = $_POST['db_name'] ?? 'sistema_licitacao';
            $user = $_POST['db_user'] ?? 'root';
            $pass = $_POST['db_pass'] ?? '';
            
            try {
                $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
                $pdo = new PDO($dsn, $user, $pass);
                
                // Verificar se banco existe, se não, criar
                $stmt = $pdo->query("SHOW DATABASES LIKE '$name'");
                if (!$stmt->fetch()) {
                    $pdo->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $success[] = "Banco de dados '$name' criado com sucesso!";
                } else {
                    $success[] = "Banco de dados '$name' já existe.";
                }
                
                // Criar arquivo .env
                $env_content = "# Configurações do Sistema de Licitações\n";
                $env_content .= "DB_HOST=$host\n";
                $env_content .= "DB_PORT=$port\n";
                $env_content .= "DB_NAME=$name\n";
                $env_content .= "DB_USER=$user\n";
                $env_content .= "DB_PASS=$pass\n\n";
                
                // Auto-detectar URL
                $auto_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                $auto_url .= $_SERVER['HTTP_HOST'] ?? 'localhost';
                $auto_url .= dirname($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) . '/' : '/';
                $auto_url = str_replace('\\', '/', $auto_url);
                
                $env_content .= "SITE_URL=$auto_url\n";
                $env_content .= "DEBUG_MODE=false\n";
                $env_content .= "TIMEZONE=America/Sao_Paulo\n";
                
                file_put_contents('.env', $env_content);
                $success[] = "Arquivo .env criado com sucesso!";
                
                header('Location: install.php?step=2');
                exit;
                
            } catch (PDOException $e) {
                $errors[] = "Erro na conexão: " . $e->getMessage();
            }
            break;
            
        case 2:
            // Executar script SQL
            try {
                require_once 'config.php';
                $pdo = conectarDB();
                
                // Verificar se existe script SQL
                $sql_files = [
                    'database/estrutura_completa_2025.sql',
                    'database/sistema_licitacao.sql',
                    'backups/sistema_licitacao.sql'
                ];
                
                $sql_file = null;
                foreach ($sql_files as $file) {
                    if (file_exists($file)) {
                        $sql_file = $file;
                        break;
                    }
                }
                
                if ($sql_file) {
                    $sql = file_get_contents($sql_file);
                    $statements = array_filter(array_map('trim', explode(';', $sql)));
                    
                    foreach ($statements as $statement) {
                        if (!empty($statement)) {
                            $pdo->exec($statement);
                        }
                    }
                    
                    $success[] = "Estrutura do banco criada com sucesso!";
                } else {
                    $errors[] = "Arquivo SQL não encontrado. Verifique se existe database/estrutura_completa_2025.sql";
                }
                
                header('Location: install.php?step=3');
                exit;
                
            } catch (Exception $e) {
                $errors[] = "Erro ao executar SQL: " . $e->getMessage();
            }
            break;
            
        case 3:
            // Criar usuário administrador
            $nome = $_POST['admin_nome'] ?? '';
            $email = $_POST['admin_email'] ?? '';
            $senha = $_POST['admin_senha'] ?? '';
            
            if ($nome && $email && $senha) {
                try {
                    require_once 'config.php';
                    $pdo = conectarDB();
                    
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    
                    $sql = "INSERT INTO usuarios (nome, email, senha, tipo_usuario, nivel_acesso, departamento, ativo) 
                            VALUES (?, ?, ?, 'Coordenador', 1, 'CGLIC', 1)
                            ON DUPLICATE KEY UPDATE
                            nome = VALUES(nome), senha = VALUES(senha)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$nome, $email, $senha_hash]);
                    
                    $success[] = "Usuário administrador criado com sucesso!";
                    header('Location: install.php?step=4');
                    exit;
                    
                } catch (Exception $e) {
                    $errors[] = "Erro ao criar usuário: " . $e->getMessage();
                }
            } else {
                $errors[] = "Todos os campos são obrigatórios.";
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Sistema CGLIC</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; text-align: center; margin-bottom: 30px; }
        .step-indicator { display: flex; justify-content: center; margin-bottom: 30px; }
        .step { width: 40px; height: 40px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; margin: 0 10px; font-weight: bold; }
        .step.active { background: #3498db; color: white; }
        .step.completed { background: #27ae60; color: white; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        button { background: #3498db; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; width: 100%; }
        button:hover { background: #2980b9; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏥 Sistema CGLIC - Instalação</h1>
        
        <div class="step-indicator">
            <div class="step <?= $step >= 1 ? 'active' : '' ?><?= $step > 1 ? ' completed' : '' ?>">1</div>
            <div class="step <?= $step >= 2 ? 'active' : '' ?><?= $step > 2 ? ' completed' : '' ?>">2</div>
            <div class="step <?= $step >= 3 ? 'active' : '' ?><?= $step > 3 ? ' completed' : '' ?>">3</div>
            <div class="step <?= $step >= 4 ? 'active' : '' ?>">4</div>
        </div>
        
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
        
        <?php foreach ($success as $msg): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
        <?php endforeach; ?>
        
        <?php if ($step == 1): ?>
            <h2>Passo 1: Configuração do Banco de Dados</h2>
            <div class="info">
                <strong>ℹ️ Informação:</strong> Configure a conexão com o banco de dados MySQL/MariaDB.
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Host do Banco:</label>
                    <input type="text" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label>Porta:</label>
                    <input type="number" name="db_port" value="3306" required>
                </div>
                <div class="form-group">
                    <label>Nome do Banco:</label>
                    <input type="text" name="db_name" value="sistema_licitacao" required>
                </div>
                <div class="form-group">
                    <label>Usuário:</label>
                    <input type="text" name="db_user" value="root" required>
                </div>
                <div class="form-group">
                    <label>Senha:</label>
                    <input type="password" name="db_pass">
                </div>
                <button type="submit">Conectar e Continuar</button>
            </form>
            
        <?php elseif ($step == 2): ?>
            <h2>Passo 2: Criação das Tabelas</h2>
            <div class="info">
                <strong>ℹ️ Informação:</strong> Agora vamos criar a estrutura do banco de dados.
            </div>
            
            <form method="POST">
                <p>Conexão estabelecida com sucesso! Clique para criar as tabelas do sistema.</p>
                <button type="submit">Criar Estrutura do Banco</button>
            </form>
            
        <?php elseif ($step == 3): ?>
            <h2>Passo 3: Usuário Administrador</h2>
            <div class="info">
                <strong>ℹ️ Informação:</strong> Crie o usuário administrador para acessar o sistema.
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Nome Completo:</label>
                    <input type="text" name="admin_nome" required>
                </div>
                <div class="form-group">
                    <label>E-mail:</label>
                    <input type="email" name="admin_email" required>
                </div>
                <div class="form-group">
                    <label>Senha:</label>
                    <input type="password" name="admin_senha" required minlength="6">
                </div>
                <button type="submit">Criar Administrador</button>
            </form>
            
        <?php elseif ($step == 4): ?>
            <h2>🎉 Instalação Concluída!</h2>
            <div class="alert alert-success">
                ✅ Sistema instalado com sucesso!
            </div>
            
            <div class="info">
                <strong>📋 Próximos passos:</strong><br>
                1. Remova o arquivo install.php por segurança<br>
                2. Configure o arquivo .env conforme necessário<br>
                3. Acesse o sistema e comece a usar!
            </div>
            
            <a href="index.php" style="display: inline-block; background: #27ae60; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; text-align: center; width: 100%; box-sizing: border-box;">
                🚀 Acessar Sistema
            </a>
        <?php endif; ?>
    </div>
</body>
</html>