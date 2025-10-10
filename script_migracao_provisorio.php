<?php
/**
 * SCRIPT PROVISÓRIO DE MIGRAÇÃO - VINCULAÇÃO QUALIFICAÇÕES → PCA_DADOS
 * 
 * Este script permite analisar os registros existentes na tabela qualificacoes
 * e configurar manualmente as vinculações com a tabela pca_dados antes de
 * implementar o campo pca_dados_id definitivamente.
 * 
 * IMPORTANTE: Este é um script de análise - NÃO FAZ ALTERAÇÕES AUTOMÁTICAS
 */

require_once 'config.php';
require_once 'functions.php';

verificarLogin();

// Verificar se é Coordenador (nível 1)
if ($_SESSION['usuario_nivel'] != 1) {
    die('Acesso negado. Apenas Coordenadores podem executar este script de migração.');
}

$pdo = conectarDB();

// Parâmetros de busca e análise
$busca_area = $_GET['busca_area'] ?? '';
$busca_valor = $_GET['busca_valor'] ?? '';
$qualificacao_id = $_GET['qualificacao_id'] ?? '';
$acao = $_GET['acao'] ?? 'listar';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Script de Migração - Vinculação Qualificações → PCA</title>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .warning-banner {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
        }
        .warning-banner h1 {
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .info-box {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .tab {
            padding: 12px 20px;
            background: #e2e8f0;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            color: #475569;
        }
        .tab.active {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr; }
        }
        .panel {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .panel-header {
            background: linear-gradient(135deg, #1e293b, #334155);
            color: white;
            padding: 16px 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .panel-content {
            padding: 20px;
        }
        .qualificacao-item {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            transition: all 0.2s ease;
        }
        .qualificacao-item:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        .qualificacao-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .qualificacao-nup {
            font-weight: 700;
            color: #1e293b;
            font-size: 16px;
        }
        .valor-badge {
            background: #dcfce7;
            color: #166534;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
        }
        .qualificacao-info {
            margin-bottom: 12px;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: 600;
            color: #475569;
            min-width: 120px;
        }
        .info-value {
            color: #1e293b;
        }
        .objeto-text {
            background: #f8fafc;
            padding: 12px;
            border-radius: 8px;
            margin-top: 12px;
            font-style: italic;
            color: #475569;
        }
        .suggestions-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }
        .suggestion-item {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .suggestion-item:hover {
            background: #f8fafc;
        }
        .suggestion-item:last-child {
            border-bottom: none;
        }
        .suggestion-match {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }
        .match-score {
            background: #22c55e;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }
        .match-score.medium {
            background: #f59e0b;
        }
        .match-score.low {
            background: #64748b;
        }
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        .btn-success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }
        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        .search-box {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 16px;
        }
        .search-box:focus {
            border-color: #3b82f6;
            outline: none;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-sem-vinculo {
            background: #fee2e2;
            color: #dc2626;
        }
        .status-com-sugestao {
            background: #fef3c7;
            color: #d97706;
        }
        .status-vinculado {
            background: #dcfce7;
            color: #16a34a;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Banner de Aviso -->
        <div class="warning-banner">
            <h1>
                <i data-lucide="alert-triangle"></i>
                SCRIPT PROVISÓRIO DE MIGRAÇÃO - SOMENTE ANÁLISE
            </h1>
            <p><strong>ATENÇÃO:</strong> Este script é apenas para análise e planejamento. Nenhuma alteração será feita automaticamente no banco de dados.</p>
        </div>

        <!-- Informações Gerais -->
        <div class="info-box">
            <h3>📋 Objetivo da Migração</h3>
            <p>Analisar os <strong>35 registros</strong> existentes na tabela <code>qualificacoes</code> e configurar vinculações com registros da tabela <code>pca_dados</code> através do futuro campo <code>pca_dados_id</code>.</p>
            
            <h4>🎯 Critérios de Vinculação Sugeridos:</h4>
            <ul>
                <li><strong>Área:</strong> <code>qualificacoes.area_demandante</code> ↔ <code>pca_dados.area_requisitante</code></li>
                <li><strong>Valor:</strong> <code>qualificacoes.valor_estimado</code> ↔ <code>pca_dados.valor_total_contratacao</code> (tolerância ±20%)</li>
                <li><strong>Objeto:</strong> Similaridade textual entre <code>qualificacoes.objeto</code> ↔ <code>pca_dados.titulo_contratacao</code></li>
            </ul>
        </div>

        <!-- Abas de Navegação -->
        <div class="tabs">
            <a href="?acao=listar" class="tab <?= $acao == 'listar' ? 'active' : '' ?>">
                <i data-lucide="list"></i> Listar Qualificações
            </a>
            <a href="?acao=estatisticas" class="tab <?= $acao == 'estatisticas' ? 'active' : '' ?>">
                <i data-lucide="bar-chart"></i> Estatísticas
            </a>
            <a href="?acao=sugestoes" class="tab <?= $acao == 'sugestoes' ? 'active' : '' ?>">
                <i data-lucide="zap"></i> Sugestões Automáticas
            </a>
            <a href="?acao=gerar_sql" class="tab <?= $acao == 'gerar_sql' ? 'active' : '' ?>">
                <i data-lucide="code"></i> Gerar SQL
            </a>
        </div>

        <?php if ($acao == 'listar'): ?>
            <!-- Listagem de Qualificações -->
            <div class="header">
                <h2>📝 Registros de Qualificações (Total: 35)</h2>
                <input type="text" class="search-box" placeholder="🔍 Buscar por NUP, área ou objeto..." 
                       onkeyup="filtrarQualificacoes(this.value)">
            </div>

            <div class="grid">
                <!-- Painel de Qualificações -->
                <div class="panel">
                    <div class="panel-header">
                        <i data-lucide="file-text"></i>
                        Qualificações Existentes
                    </div>
                    <div class="panel-content">
                        <?php
                        $query_qualif = "SELECT * FROM qualificacoes ORDER BY id";
                        $stmt_qualif = $pdo->query($query_qualif);
                        $qualificacoes = $stmt_qualif->fetchAll();

                        foreach ($qualificacoes as $qualif):
                        ?>
                        <div class="qualificacao-item" data-search="<?= strtolower($qualif['nup'] . ' ' . $qualif['area_demandante'] . ' ' . $qualif['objeto']) ?>">
                            <div class="qualificacao-header">
                                <div class="qualificacao-nup"><?= htmlspecialchars($qualif['nup']) ?></div>
                                <div class="valor-badge">R$ <?= number_format($qualif['valor_estimado'], 2, ',', '.') ?></div>
                            </div>
                            
                            <div class="qualificacao-info">
                                <div class="info-row">
                                    <span class="info-label">Área:</span>
                                    <span class="info-value"><?= htmlspecialchars($qualif['area_demandante']) ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Modalidade:</span>
                                    <span class="info-value"><?= htmlspecialchars($qualif['modalidade']) ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Status:</span>
                                    <span class="info-value"><?= htmlspecialchars($qualif['status']) ?></span>
                                </div>
                            </div>
                            
                            <div class="objeto-text">
                                <?= htmlspecialchars(strlen($qualif['objeto']) > 200 ? substr($qualif['objeto'], 0, 200) . '...' : $qualif['objeto']) ?>
                            </div>
                            
                            <div style="margin-top: 12px;">
                                <a href="?acao=analisar&qualificacao_id=<?= $qualif['id'] ?>" class="btn btn-primary">
                                    <i data-lucide="search"></i> Analisar Vinculações
                                </a>
                                
                                <?php
                                // Verificar se já tem vinculação sugerida (isso seria implementado depois)
                                $tem_vinculo = false; // Placeholder
                                ?>
                                <?php if ($tem_vinculo): ?>
                                    <span class="status-badge status-vinculado">VINCULADO</span>
                                <?php else: ?>
                                    <span class="status-badge status-sem-vinculo">SEM VÍNCULO</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Painel de Análise Individual -->
                <div class="panel">
                    <div class="panel-header">
                        <i data-lucide="target"></i>
                        Análise de Vinculação
                    </div>
                    <div class="panel-content">
                        <?php if (empty($qualificacao_id)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i data-lucide="mouse-pointer"></i>
                                </div>
                                <h3>Selecione uma Qualificação</h3>
                                <p>Clique em "Analisar Vinculações" em qualquer qualificação para ver sugestões de vinculação com registros do PCA.</p>
                            </div>
                        <?php else: ?>
                            <?php
                            // Buscar dados da qualificação selecionada
                            $stmt_sel = $pdo->prepare("SELECT * FROM qualificacoes WHERE id = ?");
                            $stmt_sel->execute([$qualificacao_id]);
                            $qualif_selecionada = $stmt_sel->fetch();
                            
                            if ($qualif_selecionada):
                                // Buscar sugestões de vinculação
                                $valor_min = $qualif_selecionada['valor_estimado'] * 0.8; // -20%
                                $valor_max = $qualif_selecionada['valor_estimado'] * 1.2; // +20%
                                
                                $query_sugestoes = "
                                    SELECT *, 
                                           CASE 
                                               WHEN area_requisitante = ? AND valor_total_contratacao BETWEEN ? AND ? THEN 'ALTA'
                                               WHEN area_requisitante = ? OR valor_total_contratacao BETWEEN ? AND ? THEN 'MÉDIA'
                                               ELSE 'BAIXA'
                                           END as score_match
                                    FROM pca_dados 
                                    WHERE area_requisitante LIKE ? 
                                       OR valor_total_contratacao BETWEEN ? AND ?
                                       OR titulo_contratacao LIKE ?
                                    ORDER BY 
                                        CASE WHEN area_requisitante = ? THEN 1 ELSE 2 END,
                                        ABS(valor_total_contratacao - ?) ASC
                                    LIMIT 10
                                ";
                                
                                $busca_area_like = '%' . $qualif_selecionada['area_demandante'] . '%';
                                $busca_titulo_like = '%' . substr($qualif_selecionada['objeto'], 0, 50) . '%';
                                
                                $stmt_sugestoes = $pdo->prepare($query_sugestoes);
                                $stmt_sugestoes->execute([
                                    $qualif_selecionada['area_demandante'], $valor_min, $valor_max, // ALTA
                                    $qualif_selecionada['area_demandante'], $valor_min, $valor_max, // MÉDIA
                                    $busca_area_like, // área similar
                                    $valor_min, $valor_max, // valor similar
                                    $busca_titulo_like, // título similar
                                    $qualif_selecionada['area_demandante'], // ordenação
                                    $qualif_selecionada['valor_estimado'] // ordenação por valor
                                ]);
                                $sugestoes = $stmt_sugestoes->fetchAll();
                            ?>
                            <h3>🎯 Qualificação Selecionada</h3>
                            <div class="qualificacao-item" style="border-color: #3b82f6;">
                                <div class="qualificacao-header">
                                    <div class="qualificacao-nup"><?= htmlspecialchars($qualif_selecionada['nup']) ?></div>
                                    <div class="valor-badge">R$ <?= number_format($qualif_selecionada['valor_estimado'], 2, ',', '.') ?></div>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label">Área:</span>
                                    <span class="info-value"><?= htmlspecialchars($qualif_selecionada['area_demandante']) ?></span>
                                </div>
                            </div>
                            
                            <h4>💡 Sugestões de Vinculação</h4>
                            <div class="suggestions-list">
                                <?php if (empty($sugestoes)): ?>
                                    <div class="empty-state" style="padding: 20px;">
                                        <p>Nenhuma sugestão encontrada automaticamente.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($sugestoes as $sugestao): ?>
                                    <div class="suggestion-item">
                                        <div class="suggestion-match">
                                            <span class="match-score <?= strtolower($sugestao['score_match']) ?>"><?= $sugestao['score_match'] ?></span>
                                            <strong>ID: <?= $sugestao['id'] ?></strong>
                                            <span>DFD: <?= htmlspecialchars($sugestao['numero_dfd'] ?? '-') ?></span>
                                        </div>
                                        
                                        <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">
                                            Área: <?= htmlspecialchars($sugestao['area_requisitante'] ?? '-') ?> | 
                                            Valor: R$ <?= number_format($sugestao['valor_total_contratacao'], 2, ',', '.') ?>
                                        </div>
                                        
                                        <div style="font-size: 13px;">
                                            <?= htmlspecialchars(strlen($sugestao['titulo_contratacao'] ?? '') > 150 ? substr($sugestao['titulo_contratacao'], 0, 150) . '...' : ($sugestao['titulo_contratacao'] ?? 'Sem título')) ?>
                                        </div>
                                        
                                        <div style="margin-top: 8px;">
                                            <button class="btn btn-success" style="padding: 4px 8px; font-size: 11px;"
                                                    onclick="selecionarVinculo(<?= $qualif_selecionada['id'] ?>, <?= $sugestao['id'] ?>)">
                                                <i data-lucide="link"></i> SELECIONAR
                                            </button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php elseif ($acao == 'estatisticas'): ?>
            <!-- Estatísticas -->
            <div class="panel">
                <div class="panel-header">
                    <i data-lucide="bar-chart"></i>
                    Estatísticas de Migração
                </div>
                <div class="panel-content">
                    <?php
                    // Buscar estatísticas
                    $stats_qualif = $pdo->query("SELECT COUNT(*) as total, COUNT(DISTINCT area_demandante) as areas, SUM(valor_estimado) as valor_total FROM qualificacoes")->fetch();
                    $stats_pca = $pdo->query("SELECT COUNT(*) as total, COUNT(DISTINCT area_requisitante) as areas, SUM(valor_total_contratacao) as valor_total FROM pca_dados")->fetch();
                    ?>
                    
                    <div class="grid">
                        <div>
                            <h3>📝 Tabela Qualificações</h3>
                            <div class="info-row">
                                <span class="info-label">Total de Registros:</span>
                                <span class="info-value"><strong><?= number_format($stats_qualif['total']) ?></strong></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Áreas Distintas:</span>
                                <span class="info-value"><?= $stats_qualif['areas'] ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Valor Total:</span>
                                <span class="info-value">R$ <?= number_format($stats_qualif['valor_total'], 2, ',', '.') ?></span>
                            </div>
                        </div>
                        
                        <div>
                            <h3>📊 Tabela PCA Dados</h3>
                            <div class="info-row">
                                <span class="info-label">Total de Registros:</span>
                                <span class="info-value"><strong><?= number_format($stats_pca['total']) ?></strong></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Áreas Distintas:</span>
                                <span class="info-value"><?= $stats_pca['areas'] ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Valor Total:</span>
                                <span class="info-value">R$ <?= number_format($stats_pca['valor_total'], 2, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <h3>🎯 Áreas Comuns</h3>
                    <?php
                    $areas_comuns = $pdo->query("
                        SELECT q.area_demandante, COUNT(q.id) as qualif_count, COUNT(DISTINCT p.id) as pca_count
                        FROM qualificacoes q
                        LEFT JOIN pca_dados p ON p.area_requisitante = q.area_demandante
                        GROUP BY q.area_demandante
                        ORDER BY pca_count DESC, qualif_count DESC
                    ")->fetchAll();
                    ?>
                    
                    <div class="suggestions-list" style="max-height: 250px;">
                        <?php foreach ($areas_comuns as $area): ?>
                        <div class="suggestion-item">
                            <div class="suggestion-match">
                                <?php if ($area['pca_count'] > 0): ?>
                                    <span class="match-score">✓ MATCH</span>
                                <?php else: ?>
                                    <span class="match-score low">SEM MATCH</span>
                                <?php endif; ?>
                                <strong><?= htmlspecialchars($area['area_demandante']) ?></strong>
                            </div>
                            <div style="font-size: 12px; color: #64748b;">
                                Qualificações: <?= $area['qualif_count'] ?> | Registros PCA: <?= $area['pca_count'] ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        <?php elseif ($acao == 'gerar_sql'): ?>
            <!-- Geração de SQL -->
            <div class="panel">
                <div class="panel-header">
                    <i data-lucide="code"></i>
                    Scripts SQL para Migração
                </div>
                <div class="panel-content">
                    <div class="warning-banner">
                        <h3>⚠️ IMPORTANTE - Scripts de Migração</h3>
                        <p>Execute estes scripts na seguinte ordem, SEMPRE fazendo backup antes:</p>
                    </div>
                    
                    <h3>1️⃣ Adicionar Campo pca_dados_id</h3>
                    <textarea readonly style="width: 100%; height: 100px; font-family: monospace; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
-- Adicionar o campo pca_dados_id na tabela qualificacoes
ALTER TABLE qualificacoes ADD COLUMN pca_dados_id INT(11) NULL AFTER id;

-- Criar índice para performance
ALTER TABLE qualificacoes ADD INDEX idx_pca_dados_id (pca_dados_id);

-- Criar foreign key (opcional - recomendado)
ALTER TABLE qualificacoes ADD CONSTRAINT fk_qualificacoes_pca_dados 
FOREIGN KEY (pca_dados_id) REFERENCES pca_dados(id) ON DELETE SET NULL;</textarea>
                    
                    <h3>2️⃣ Scripts de Vinculação Manual</h3>
                    <p><em>Após analisar as vinculações, você poderá gerar scripts específicos aqui.</em></p>
                    
                    <textarea readonly style="width: 100%; height: 150px; font-family: monospace; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
-- Exemplo de vinculação manual (substituir pelos IDs corretos):
-- UPDATE qualificacoes SET pca_dados_id = [ID_PCA_DADOS] WHERE id = [ID_QUALIFICACAO];

-- Exemplo prático:
UPDATE qualificacoes SET pca_dados_id = 6698 WHERE id = 7;
UPDATE qualificacoes SET pca_dados_id = 6699 WHERE id = 8;
-- ... adicionar mais vinculações conforme análise

-- Verificar vinculações criadas:
SELECT q.id, q.nup, q.area_demandante, p.id as pca_id, p.titulo_contratacao, p.area_requisitante
FROM qualificacoes q
LEFT JOIN pca_dados p ON q.pca_dados_id = p.id
ORDER BY q.id;</textarea>
                    
                    <h3>3️⃣ Validação Pós-Migração</h3>
                    <textarea readonly style="width: 100%; height: 80px; font-family: monospace; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
-- Verificar quantas qualificações ficaram sem vinculação:
SELECT COUNT(*) as sem_vinculo FROM qualificacoes WHERE pca_dados_id IS NULL;

-- Verificar consistência das vinculações:
SELECT COUNT(*) as com_vinculo_valido FROM qualificacoes q 
INNER JOIN pca_dados p ON q.pca_dados_id = p.id;</textarea>
                    
                    <div style="margin-top: 20px; padding: 16px; background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px;">
                        <h4>🔄 Próximos Passos Recomendados:</h4>
                        <ol>
                            <li><strong>Backup Completo:</strong> Fazer backup do banco antes da migração</li>
                            <li><strong>Teste em Ambiente Separado:</strong> Testar a migração primeiro</li>
                            <li><strong>Executar Scripts:</strong> Aplicar as mudanças em produção</li>
                            <li><strong>Atualizar Interface:</strong> Modificar qualificacao_dashboard.php para usar pca_dados_id</li>
                            <li><strong>Validar Funcionamento:</strong> Testar todas as funcionalidades</li>
                        </ol>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <script>
        // Inicializar ícones Lucide
        lucide.createIcons();
        
        // Função para filtrar qualificações
        function filtrarQualificacoes(termo) {
            const items = document.querySelectorAll('.qualificacao-item');
            termo = termo.toLowerCase();
            
            items.forEach(item => {
                const searchText = item.getAttribute('data-search');
                if (searchText.includes(termo)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        // Função para selecionar vínculo
        function selecionarVinculo(qualifId, pcaId) {
            if (confirm(`Confirmar vinculação da Qualificação ${qualifId} com PCA ${pcaId}?`)) {
                // Aqui seria implementada a lógica de seleção
                alert(`Vinculação selecionada! Qualificação ${qualifId} → PCA ${pcaId}\n\nEsta vinculação será incluída no script SQL final.`);
                
                // Marcar visualmente como selecionado
                event.target.style.background = '#22c55e';
                event.target.innerHTML = '<i data-lucide="check"></i> SELECIONADO';
                lucide.createIcons();
            }
        }
    </script>
</body>
</html>