<?php
/**
 * Componente: Card do Kanban
 * Renderiza um card individual para o board Kanban
 * Variável $card deve estar disponível no escopo
 */

// Verificar se a variável $card existe
if (!isset($card) || !is_array($card)) {
    return;
}

// Dados do card
$cardId = $card['id'] ?? 0;
$titulo = htmlspecialchars($card['titulo'] ?? 'Sem título');
$descricao = htmlspecialchars($card['descricao'] ?? '');
$prioridade = strtolower($card['prioridade'] ?? 'media');
$numero_tramite = htmlspecialchars($card['numero_tramite'] ?? '');
$tipo_demanda = htmlspecialchars($card['tipo_demanda'] ?? '');
$responsavel_nome = htmlspecialchars($card['responsavel_nome'] ?? '');
$prazo_limite = $card['prazo_limite'] ?? null;
$situacao_prazo = $card['situacao_prazo'] ?? '';
$modulo_origem = htmlspecialchars($card['modulo_origem'] ?? '');
$modulo_destino = htmlspecialchars($card['modulo_destino'] ?? '');
$tags = $card['tags'] ?? '';
$cor_card = $card['cor_card'] ?? '#3b82f6';
$criado_em = $card['criado_em'] ?? null;

// Processar tags
$tagsArray = [];
if (!empty($tags)) {
    $tagsArray = array_map('trim', explode(',', $tags));
    $tagsArray = array_filter($tagsArray); // Remover tags vazias
}

// Formatar prazo
$prazoFormatado = '';
$classePrazo = '';
if ($prazo_limite) {
    $prazoDate = new DateTime($prazo_limite);
    $now = new DateTime();
    
    if ($situacao_prazo === 'ATRASADO') {
        $classePrazo = 'atrasado';
        $prazoFormatado = 'Atrasado - ' . $prazoDate->format('d/m/Y H:i');
    } elseif ($situacao_prazo === 'VENCENDO') {
        $classePrazo = 'vencendo';
        $prazoFormatado = 'Vence em breve - ' . $prazoDate->format('d/m/Y H:i');
    } else {
        $prazoFormatado = $prazoDate->format('d/m/Y H:i');
    }
} else {
    $prazoFormatado = 'Sem prazo';
}

// Formatar data de criação
$criadoFormatado = '';
if ($criado_em) {
    $criadoDate = new DateTime($criado_em);
    $criadoFormatado = $criadoDate->format('d/m/Y');
}
?>

<div class="kanban-card prioridade-<?php echo $prioridade; ?>" 
     data-id="<?php echo $cardId; ?>"
     data-numero="<?php echo $numero_tramite; ?>"
     style="border-left-color: <?php echo $cor_card; ?>;">
     
    <div class="card-header">
        <div class="card-numero">
            <?php if ($numero_tramite): ?>
                #<?php echo $numero_tramite; ?>
            <?php else: ?>
                #<?php echo $cardId; ?>
            <?php endif; ?>
        </div>
        <div class="card-prioridade prioridade-<?php echo $prioridade; ?>">
            <?php echo ucfirst($prioridade); ?>
        </div>
    </div>
    
    <div class="card-titulo">
        <?php echo $titulo; ?>
    </div>
    
    <?php if ($tipo_demanda): ?>
    <div class="card-tipo">
        <i data-lucide="tag"></i>
        <?php echo $tipo_demanda; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($descricao): ?>
    <div class="card-descricao" style="font-size: 13px; color: #6b7280; margin: 8px 0; line-height: 1.4;">
        <?php echo strlen($descricao) > 100 ? substr($descricao, 0, 100) . '...' : $descricao; ?>
    </div>
    <?php endif; ?>
    
    <!-- Módulos -->
    <div style="display: flex; align-items: center; gap: 8px; margin: 8px 0; font-size: 11px; color: #9ca3af;">
        <span style="display: flex; align-items: center; gap: 4px;">
            <i data-lucide="arrow-right" size="12"></i>
            <?php echo $modulo_origem; ?> → <?php echo $modulo_destino; ?>
        </span>
    </div>
    
    <!-- Tags -->
    <?php if (!empty($tagsArray)): ?>
    <div class="card-tags">
        <?php foreach (array_slice($tagsArray, 0, 3) as $tag): ?>
            <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
        <?php endforeach; ?>
        <?php if (count($tagsArray) > 3): ?>
            <span class="tag" style="background: #e5e7eb; color: #6b7280;">+<?php echo count($tagsArray) - 3; ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="card-info">
        <div class="card-responsavel">
            <?php if ($responsavel_nome): ?>
                <i data-lucide="user" size="12"></i>
                <span><?php echo $responsavel_nome; ?></span>
            <?php else: ?>
                <i data-lucide="user-x" size="12"></i>
                <span>Sem responsável</span>
            <?php endif; ?>
        </div>
        
        <div class="card-prazo <?php echo $classePrazo; ?>">
            <i data-lucide="clock" size="12"></i>
            <span><?php echo $prazoFormatado; ?></span>
        </div>
    </div>
    
    <!-- Barra de ações (aparece no hover) -->
    <div class="card-actions" style="display: none; margin-top: 12px; padding-top: 8px; border-top: 1px solid #e5e7eb;">
        <div style="display: flex; gap: 8px; justify-content: flex-end;">
            <button onclick="abrirModalDetalhes(<?php echo $cardId; ?>)" 
                    style="background: #f3f4f6; border: none; border-radius: 4px; padding: 4px 8px; cursor: pointer; font-size: 11px; color: #374151;"
                    title="Ver detalhes">
                <i data-lucide="eye" size="12"></i>
            </button>
            
            <?php if ($pode_editar): ?>
            <button onclick="abrirModalEditarTramitacao(<?php echo $cardId; ?>)" 
                    style="background: #f3f4f6; border: none; border-radius: 4px; padding: 4px 8px; cursor: pointer; font-size: 11px; color: #374151;"
                    title="Editar">
                <i data-lucide="edit" size="12"></i>
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para o card */
.kanban-card:hover .card-actions {
    display: block !important;
}

.card-descricao {
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.sortable-ghost {
    opacity: 0.4;
    background: #f3f4f6 !important;
}

.sortable-chosen {
    cursor: grabbing !important;
}

.sortable-drag {
    transform: rotate(3deg);
    box-shadow: 0 8px 25px rgba(0,0,0,0.2) !important;
}
</style>

<script>
// Garantir que os ícones sejam renderizados quando o card for criado
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>