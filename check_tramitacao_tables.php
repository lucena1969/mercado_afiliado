<?php
require_once 'config.php';

try {
    $pdo = conectarDB();
    
    echo "=== VERIFICANDO TABELAS DE TRAMITAÇÃO ===\n";
    
    // Verificar tabelas que contêm 'tramit'
    $result = $pdo->query("SHOW TABLES LIKE '%tramit%'");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "❌ PROBLEMA: Nenhuma tabela de tramitação encontrada!\n";
        echo "As seguintes tabelas precisam ser criadas:\n";
        echo "- tramitacoes_kanban\n";
        echo "- tramitacoes_templates\n";
        echo "- v_tramitacoes_kanban (view)\n";
        
        // Criar tabelas necessárias
        echo "\n=== CRIANDO ESTRUTURA DE TRAMITAÇÕES ===\n";
        
        // Tabela principal
        $pdo->exec("
        CREATE TABLE IF NOT EXISTS tramitacoes_kanban (
            id INT PRIMARY KEY AUTO_INCREMENT,
            numero_tramite VARCHAR(20) UNIQUE,
            titulo VARCHAR(255) NOT NULL,
            descricao TEXT,
            tipo_demanda VARCHAR(100),
            modulo_origem ENUM('PLANEJAMENTO', 'LICITACAO', 'QUALIFICACAO', 'CONTRATOS') NOT NULL,
            modulo_destino ENUM('PLANEJAMENTO', 'LICITACAO', 'QUALIFICACAO', 'CONTRATOS') NOT NULL,
            status ENUM('TODO', 'EM_PROGRESSO', 'AGUARDANDO', 'CONCLUIDO') DEFAULT 'TODO',
            prioridade ENUM('BAIXA', 'MEDIA', 'ALTA', 'URGENTE') DEFAULT 'MEDIA',
            prazo_limite DATETIME NULL,
            situacao_prazo ENUM('NO_PRAZO', 'VENCENDO', 'ATRASADO') DEFAULT 'NO_PRAZO',
            dias_restantes INT DEFAULT 0,
            posicao INT DEFAULT 0,
            cor_card VARCHAR(7) DEFAULT '#3b82f6',
            tags JSON,
            observacoes TEXT,
            usuario_criador_id INT,
            usuario_responsavel_id INT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_criador_id) REFERENCES usuarios(id),
            FOREIGN KEY (usuario_responsavel_id) REFERENCES usuarios(id),
            INDEX idx_status (status),
            INDEX idx_modulo_origem (modulo_origem),
            INDEX idx_modulo_destino (modulo_destino),
            INDEX idx_prioridade (prioridade),
            INDEX idx_responsavel (usuario_responsavel_id),
            INDEX idx_prazo (prazo_limite)
        )
        ");
        echo "✅ Tabela tramitacoes_kanban criada\n";
        
        // Tabela de templates
        $pdo->exec("
        CREATE TABLE IF NOT EXISTS tramitacoes_templates (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nome VARCHAR(255) NOT NULL,
            descricao TEXT,
            tipo_demanda_padrao VARCHAR(100),
            modulo_origem ENUM('PLANEJAMENTO', 'LICITACAO', 'QUALIFICACAO', 'CONTRATOS'),
            modulo_destino ENUM('PLANEJAMENTO', 'LICITACAO', 'QUALIFICACAO', 'CONTRATOS'),
            prioridade_padrao ENUM('BAIXA', 'MEDIA', 'ALTA', 'URGENTE') DEFAULT 'MEDIA',
            prazo_padrao_dias INT NULL,
            cor_padrao VARCHAR(7) DEFAULT '#3b82f6',
            tags_padrao JSON,
            ativo BOOLEAN DEFAULT TRUE,
            usuario_criador_id INT,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_criador_id) REFERENCES usuarios(id)
        )
        ");
        echo "✅ Tabela tramitacoes_templates criada\n";
        
        // View para consultas
        $pdo->exec("
        CREATE OR REPLACE VIEW v_tramitacoes_kanban AS
        SELECT 
            t.*,
            uc.nome as usuario_criador_nome,
            uc.email as usuario_criador_email,
            ur.nome as usuario_responsavel_nome,
            ur.email as usuario_responsavel_email,
            ur.departamento as responsavel_departamento,
            (SELECT COUNT(*) FROM tramitacoes_comentarios tc WHERE tc.tramitacao_id = t.id) as total_comentarios
        FROM tramitacoes_kanban t
        LEFT JOIN usuarios uc ON t.usuario_criador_id = uc.id
        LEFT JOIN usuarios ur ON t.usuario_responsavel_id = ur.id
        ");
        echo "✅ View v_tramitacoes_kanban criada\n";
        
        // Tabela de comentários (opcional)
        $pdo->exec("
        CREATE TABLE IF NOT EXISTS tramitacoes_comentarios (
            id INT PRIMARY KEY AUTO_INCREMENT,
            tramitacao_id INT NOT NULL,
            usuario_id INT NOT NULL,
            comentario TEXT NOT NULL,
            tipo ENUM('COMENTARIO', 'STATUS_CHANGE', 'SYSTEM') DEFAULT 'COMENTARIO',
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (tramitacao_id) REFERENCES tramitacoes_kanban(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )
        ");
        echo "✅ Tabela tramitacoes_comentarios criada\n";
        
        // Inserir alguns templates padrão
        $pdo->exec("
        INSERT IGNORE INTO tramitacoes_templates (nome, descricao, tipo_demanda_padrao, modulo_origem, modulo_destino, prioridade_padrao, prazo_padrao_dias, tags_padrao, usuario_criador_id) VALUES
        ('Análise PCA → Licitação', 'Análise de contratação do PCA para início do processo licitatório', 'Análise Técnica', 'PLANEJAMENTO', 'LICITACAO', 'ALTA', 15, '[\"analise-pca\", \"licitacao\", \"tecnica\"]', 1),
        ('Elaboração de Edital', 'Elaboração de minuta de edital para licitação', 'Elaboração de Edital', 'LICITACAO', 'LICITACAO', 'ALTA', 20, '[\"edital\", \"minuta\", \"licitacao\"]', 1),
        ('Qualificação de Fornecedor', 'Análise de qualificação técnica de fornecedor', 'Análise de Qualificação', 'QUALIFICACAO', 'LICITACAO', 'MEDIA', 10, '[\"qualificacao\", \"fornecedor\", \"tecnica\"]', 1),
        ('Gestão de Contrato', 'Acompanhamento e gestão de contrato administrativo', 'Gestão Contratual', 'CONTRATOS', 'CONTRATOS', 'MEDIA', 30, '[\"contrato\", \"gestao\", \"acompanhamento\"]', 1)
        ");
        echo "✅ Templates padrão inseridos\n";
        
        // Inserir numeração automática
        $pdo->exec("
        CREATE TRIGGER IF NOT EXISTS trg_tramitacao_numero 
        BEFORE INSERT ON tramitacoes_kanban 
        FOR EACH ROW 
        BEGIN 
            DECLARE next_num INT;
            SELECT IFNULL(MAX(CAST(SUBSTRING(numero_tramite, 3) AS UNSIGNED)), 0) + 1 INTO next_num 
            FROM tramitacoes_kanban 
            WHERE numero_tramite LIKE CONCAT('TR', YEAR(CURDATE()), '%');
            SET NEW.numero_tramite = CONCAT('TR', YEAR(CURDATE()), LPAD(next_num, 4, '0'));
        END
        ");
        echo "✅ Trigger de numeração automática criado\n";
        
        echo "\n=== ESTRUTURA CRIADA COM SUCESSO! ===\n";
        
    } else {
        echo "✅ Tabelas encontradas:\n";
        foreach ($tables as $table) {
            echo "- $table\n";
        }
        
        // Verificar se a view existe
        $result = $pdo->query("SHOW FULL TABLES LIKE 'v_tramitacoes_kanban'");
        if ($result->rowCount() == 0) {
            echo "\n❌ PROBLEMA: View v_tramitacoes_kanban não encontrada!\n";
            echo "Criando view...\n";
            
            $pdo->exec("
            CREATE OR REPLACE VIEW v_tramitacoes_kanban AS
            SELECT 
                t.*,
                uc.nome as usuario_criador_nome,
                uc.email as usuario_criador_email,
                ur.nome as usuario_responsavel_nome,
                ur.email as usuario_responsavel_email,
                ur.departamento as responsavel_departamento,
                0 as total_comentarios
            FROM tramitacoes_kanban t
            LEFT JOIN usuarios uc ON t.usuario_criador_id = uc.id
            LEFT JOIN usuarios ur ON t.usuario_responsavel_id = ur.id
            ");
            echo "✅ View criada com sucesso!\n";
        }
    }
    
    // Testar a view
    echo "\n=== TESTANDO VIEW ===\n";
    $test = $pdo->query("SELECT COUNT(*) as total FROM v_tramitacoes_kanban")->fetch();
    echo "✅ View funcional - Total de tramitações: " . $test['total'] . "\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
?>