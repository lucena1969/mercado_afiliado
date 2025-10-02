<?php
/**
 * Migração OAuth - Adicionar campos na tabela users
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Iniciando migração OAuth...\n";
    
    // Verificar se campos já existem
    $checkFields = [
        'uuid' => false,
        'phone' => false,
        'avatar' => false,
        'google_id' => false,
        'facebook_id' => false,
        'email_verified_at' => false,
        'last_login_at' => false
    ];
    
    $result = $db->query("DESCRIBE users");
    while ($row = $result->fetch()) {
        $fieldName = $row['Field'];
        if (isset($checkFields[$fieldName])) {
            $checkFields[$fieldName] = true;
            echo "Campo '$fieldName' já existe.\n";
        }
    }
    
    // Adicionar campos faltantes
    foreach ($checkFields as $field => $exists) {
        if (!$exists) {
            echo "Adicionando campo '$field'...\n";
            
            switch ($field) {
                case 'uuid':
                    $db->exec("ALTER TABLE users ADD COLUMN uuid VARCHAR(36) NULL AFTER id");
                    break;
                case 'phone':
                    $db->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER email");
                    break;
                case 'avatar':
                    $db->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(500) NULL AFTER phone");
                    break;
                case 'google_id':
                    $db->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(100) NULL AFTER avatar");
                    break;
                case 'facebook_id':
                    $db->exec("ALTER TABLE users ADD COLUMN facebook_id VARCHAR(100) NULL AFTER google_id");
                    break;
                case 'email_verified_at':
                    $db->exec("ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL AFTER facebook_id");
                    break;
                case 'last_login_at':
                    $db->exec("ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL AFTER email_verified_at");
                    break;
            }
            echo "Campo '$field' adicionado com sucesso.\n";
        }
    }
    
    // Gerar UUIDs para usuários existentes
    if (!$checkFields['uuid'] || $db->query("SELECT COUNT(*) FROM users WHERE uuid IS NULL OR uuid = ''")->fetchColumn() > 0) {
        echo "Gerando UUIDs para usuários existentes...\n";
        $db->exec("UPDATE users SET uuid = UUID() WHERE uuid IS NULL OR uuid = ''");
        echo "UUIDs gerados.\n";
    }
    
    // Adicionar índices
    echo "Verificando índices...\n";
    
    try {
        $db->exec("CREATE UNIQUE INDEX idx_uuid ON users(uuid)");
        echo "Índice único para UUID criado.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "Índice UUID já existe.\n";
        } else {
            echo "Erro ao criar índice UUID: " . $e->getMessage() . "\n";
        }
    }
    
    try {
        $db->exec("CREATE INDEX idx_google_id ON users(google_id)");
        echo "Índice Google ID criado.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "Índice Google ID já existe.\n";
        }
    }
    
    try {
        $db->exec("CREATE INDEX idx_facebook_id ON users(facebook_id)");
        echo "Índice Facebook ID criado.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "Índice Facebook ID já existe.\n";
        }
    }
    
    echo "\n✅ Migração OAuth concluída com sucesso!\n";
    echo "Campos OAuth adicionados à tabela users.\n";
    
} catch (Exception $e) {
    echo "❌ Erro na migração: " . $e->getMessage() . "\n";
    echo "Stacktrace: " . $e->getTraceAsString() . "\n";
}
?>