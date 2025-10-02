<?php
// Verificar autenticação
require_once '../config/app.php';
require_once '../app/controllers/AuthController.php';

$auth = new AuthController();
$auth->requireAuth();

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$user_data = $_SESSION['user'];
$integration = new Integration($db);
$webhookEvent = new WebhookEvent($db);
$syncLog = new SyncLog($db);

// Buscar integrações do usuário
$integrations = $integration->getByUser($user_data['id']);

// Buscar eventos recentes
$recent_events = $webhookEvent->getByUser($user_data['id'], 20);

// Buscar logs recentes
$recent_logs = $syncLog->getByUser($user_data['id'], 20);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Integrações - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body style="background: #f9fafb;">
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="nav">
                <a href="<?= BASE_URL ?>/dashboard" class="nav-brand">
                    <div style="width: 32px; height: 32px; background: var(--color-primary); border-radius: 6px;"></div>
                    Mercado Afiliado
                </a>
                <ul class="nav-links">
                    <li><a href="<?= BASE_URL ?>/integrations">← Integrações</a></li>
                    <li><a href="<?= BASE_URL ?>/logout">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container" style="max-width: 1000px; margin: 2rem auto; padding: 0 1rem;">
        <!-- Header da página -->
        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: var(--font-size-3xl); font-weight: 800; margin-bottom: 0.5rem;">🧪 Teste de Integrações</h1>
            <p style="color: var(--color-gray);">Teste webhooks, sincronizações e monitore eventos em tempo real</p>
        </div>

        <!-- Teste de Webhooks -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h2>🔗 Testar Webhooks</h2>
            </div>
            <div class="card-body">
                <?php if (empty($integrations)): ?>
                    <div style="text-align: center; padding: 2rem; color: var(--color-gray);">
                        <p>Você precisa criar uma integração primeiro para testar webhooks.</p>
                        <a href="<?= BASE_URL ?>/integrations/add" class="btn btn-primary" style="margin-top: 1rem;">
                            + Criar primeira integração
                        </a>
                    </div>
                <?php else: ?>
                    <div style="display: grid; gap: 1rem;">
                        <?php foreach ($integrations as $int): ?>
                            <div style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem;">
                                <div style="display: flex; justify-content: between; align-items: center;">
                                    <div style="flex: 1;">
                                        <h3 style="font-weight: 600; margin-bottom: 0.5rem;"><?= htmlspecialchars($int['name']) ?></h3>
                                        <p style="color: var(--color-gray); font-size: var(--font-size-sm); margin-bottom: 0.5rem;">
                                            <?= ucfirst($int['platform']) ?> • Status: 
                                            <span style="color: <?= $int['status'] === 'active' ? '#10b981' : '#ef4444' ?>;">
                                                <?= $int['status'] === 'active' ? 'Ativa' : ucfirst($int['status']) ?>
                                            </span>
                                        </p>
                                        <div style="font-family: monospace; font-size: var(--font-size-sm); color: var(--color-gray); background: #f3f4f6; padding: 0.5rem; border-radius: 0.25rem; margin-bottom: 1rem;">
                                            <strong>Webhook URL:</strong><br>
                                            <span style="word-break: break-all;"><?= BASE_URL ?>/api/webhooks/<?= $int['platform'] ?>/<?= $int['webhook_token'] ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <button class="btn btn-primary" onclick="testWebhook('<?= $int['platform'] ?>', '<?= $int['webhook_token'] ?>')">
                                        🧪 Testar Webhook
                                    </button>
                                    <button class="btn" style="background: #f3f4f6; color: var(--color-gray);" onclick="copyWebhookUrl('<?= BASE_URL ?>/api/webhooks/<?= $int['platform'] ?>/<?= $int['webhook_token'] ?>')">
                                        📋 Copiar URL
                                    </button>
                                    <button class="btn" style="background: #f3f4f6; color: var(--color-gray);" onclick="showWebhookInstructions('<?= $int['platform'] ?>')">
                                        📖 Instruções
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Eventos Recentes -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Webhooks Recebidos -->
            <div class="card">
                <div class="card-header">
                    <h3>📡 Webhooks Recebidos</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_events)): ?>
                        <p style="color: var(--color-gray); text-align: center; padding: 2rem;">
                            Nenhum webhook recebido ainda
                        </p>
                    <?php else: ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($recent_events as $event): ?>
                                <div style="border-bottom: 1px solid #f3f4f6; padding: 0.75rem 0;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                        <span style="font-weight: 600; font-size: var(--font-size-sm);">
                                            <?= htmlspecialchars($event['event_type']) ?>
                                        </span>
                                        <span style="font-size: var(--font-size-xs); color: var(--color-gray);">
                                            <?= date('H:i:s', strtotime($event['received_at'])) ?>
                                        </span>
                                    </div>
                                    <div style="font-size: var(--font-size-sm); color: var(--color-gray);">
                                        <?= ucfirst($event['platform']) ?>
                                        <?php if ($event['processed']): ?>
                                            <span style="color: #10b981;">✓ Processado</span>
                                        <?php else: ?>
                                            <span style="color: #f59e0b;">⏳ Pendente</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Logs de Sincronização -->
            <div class="card">
                <div class="card-header">
                    <h3>📋 Logs de Sincronização</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_logs)): ?>
                        <p style="color: var(--color-gray); text-align: center; padding: 2rem;">
                            Nenhuma sincronização realizada
                        </p>
                    <?php else: ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($recent_logs as $log): ?>
                                <div style="border-bottom: 1px solid #f3f4f6; padding: 0.75rem 0;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                        <span style="font-weight: 600; font-size: var(--font-size-sm);">
                                            <?= htmlspecialchars($log['operation']) ?>
                                        </span>
                                        <span style="font-size: var(--font-size-xs); color: var(--color-gray);">
                                            <?= date('H:i:s', strtotime($log['created_at'])) ?>
                                        </span>
                                    </div>
                                    <div style="font-size: var(--font-size-sm); color: var(--color-gray);">
                                        <?= htmlspecialchars($log['integration_name']) ?> • 
                                        <span style="color: <?= $log['status'] === 'success' ? '#10b981' : '#ef4444' ?>;">
                                            <?= $log['status'] === 'success' ? '✓ Sucesso' : '✗ Erro' ?>
                                        </span>
                                        <?php if ($log['records_processed'] > 0): ?>
                                            • <?= $log['records_processed'] ?> registros
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Status em tempo real -->
        <div id="test-results" style="margin-top: 2rem; display: none;">
            <div class="card">
                <div class="card-header">
                    <h3>📊 Resultado do Teste</h3>
                </div>
                <div class="card-body">
                    <div id="test-content">
                        <!-- Conteúdo será inserido via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function testWebhook(platform, token) {
            const testResults = document.getElementById('test-results');
            const testContent = document.getElementById('test-content');
            
            testContent.innerHTML = '<p>🔄 Testando webhook...</p>';
            testResults.style.display = 'block';
            
            fetch(`<?= BASE_URL ?>/api/webhooks/${platform}/${token}?test=1`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({test: true})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    testContent.innerHTML = `
                        <div style="color: #10b981;">
                            <h4>✅ Teste realizado com sucesso!</h4>
                            <p>Webhook está funcionando corretamente. Verifique os logs acima para mais detalhes.</p>
                        </div>
                    `;
                } else {
                    testContent.innerHTML = `
                        <div style="color: #ef4444;">
                            <h4>❌ Erro no teste</h4>
                            <p>${data.error || 'Erro desconhecido'}</p>
                        </div>
                    `;
                }
                setTimeout(() => location.reload(), 2000);
            })
            .catch(error => {
                testContent.innerHTML = `
                    <div style="color: #ef4444;">
                        <h4>❌ Erro de conexão</h4>
                        <p>${error.message}</p>
                    </div>
                `;
            });
        }

        function copyWebhookUrl(url) {
            navigator.clipboard.writeText(url).then(() => {
                alert('✅ URL copiada para a área de transferência!');
            });
        }

        function showWebhookInstructions(platform) {
            const instructions = {
                hotmart: "1. Acesse Hotmart > Ferramentas > Integrações\n2. Clique em 'Webhooks'\n3. Adicione a URL do webhook\n4. Selecione os eventos: PURCHASE_COMPLETE, PURCHASE_REFUNDED",
                monetizze: "1. Acesse Monetizze > Configurações > Integrações\n2. Vá em 'Webhooks'\n3. Adicione a URL do webhook\n4. Configure para receber eventos de venda",
                eduzz: "1. Acesse Eduzz > Configurações > Webhooks\n2. Adicione a URL do webhook\n3. Selecione os eventos desejados\n4. Salve as configurações",
                braip: "1. Acesse Braip > Configurações > Integrações\n2. Vá em 'Webhooks'\n3. Adicione a URL do webhook\n4. Configure os eventos de venda"
            };
            
            alert(`📖 Instruções para ${platform.toUpperCase()}:\n\n${instructions[platform] || 'Instruções não disponíveis'}`);
        }

        // Auto-refresh a cada 30 segundos
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>