<?php
// Verificar autenticação
require_once '../config/app.php';
require_once '../app/controllers/AuthController.php';

$auth = new AuthController();
$auth->requireAuth();

// Buscar dados do usuário e instâncias
$user_data = $_SESSION['user'];
$database = new Database();
$db = $database->getConnection();
$integration = new Integration($db);

// ---- Processamento do formulário ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitização básica
    $platform   = isset($_POST['platform']) ? strtolower(trim($_POST['platform'])) : '';
    $name       = isset($_POST['name']) ? trim($_POST['name']) : '';
    $api_key    = isset($_POST['api_key']) ? trim($_POST['api_key']) : '';
    $api_secret = isset($_POST['api_secret']) ? trim($_POST['api_secret']) : '';

    $should_create = false;

    // Verificar limite de integrações por plano
    if (!$integration->canCreateIntegration($user_data['id'])) {
        $_SESSION['error_message'] = 'Limite de integrações atingido para seu plano atual.';
    } else {
        // Verificações básicas
        if ($platform === '' || $name === '') {
            $_SESSION['error_message'] = 'Preencha todos os campos obrigatórios.';
        } else {
            // Permitir apenas plataformas conhecidas (defesa extra)
            $allowed = ['hotmart','monetizze','eduzz','braip'];
            if (!in_array($platform, $allowed, true)) {
                $_SESSION['error_message'] = 'Plataforma inválida.';
            } else {
                // Checar se já existe integração para a plataforma do usuário
                $existing = $integration->findByPlatformAndUser($platform, $user_data['id']);
                if ($existing) {
                    $_SESSION['error_message'] = 'Você já possui uma integração configurada para ' . ucfirst($platform) . '. Apenas uma integração por plataforma é permitida.';
                } else {
                    // Preparar entidade
                    $integration->user_id   = (int)$user_data['id'];
                    $integration->platform  = $platform;
                    $integration->name      = $name;

                    if ($platform === 'hotmart') {
                        // Para Hotmart, aceitar Basic token no API Secret OU credenciais OAuth
                        if ($api_key === '' && $api_secret === '') {
                            $_SESSION['error_message'] = 'Para Hotmart, forneça pelo menos o Basic token no campo API Secret ou as credenciais OAuth.';
                        } else {
                            $integration->api_key     = $api_key !== '' ? $api_key : null;
                            $integration->api_secret  = $api_secret !== '' ? $api_secret : null;
                            $integration->config_json = json_encode(['created_via' => 'manual'], JSON_UNESCAPED_UNICODE);
                            $should_create = true;
                        }
                    } else {
                        // Outras plataformas exigem API Key
                        if ($api_key === '') {
                            $_SESSION['error_message'] = 'API Key é obrigatória para esta plataforma.';
                        } else {
                            $integration->api_key     = $api_key;
                            $integration->api_secret  = $api_secret !== '' ? $api_secret : null;
                            $integration->config_json = json_encode(['created_via' => 'manual'], JSON_UNESCAPED_UNICODE);
                            $should_create = true;
                        }
                    }
                }
            }
        }
    }

    // Criar se validado
    if ($should_create) {
        try {
            if ($integration->create()) {
                $_SESSION['success_message'] = 'Integração criada com sucesso! URL do webhook: ' . ($integration->webhook_url ?? '');
                header('Location: ' . BASE_URL . '/integrations');
                exit;
            }
            $_SESSION['error_message'] = 'Erro ao criar integração. Tente novamente.';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $_SESSION['error_message'] = 'Já existe uma integração para essa plataforma. Apenas uma integração por plataforma é permitida.';
            } else {
                $_SESSION['error_message'] = 'Erro no banco de dados: ' . $e->getMessage();
            }
        }
    }
}

// ---- Carregar plataformas já configuradas para desabilitar no select ----
$existing_integrations = [];
try {
    $existing_integrations = $integration->getByUser($user_data['id']);
} catch (Throwable $e) {
    // Evita quebrar a página se houver falha de consulta
    $existing_integrations = [];
}
$used_platforms = array_map(static function ($row) {
    return isset($row['platform']) ? strtolower((string)$row['platform']) : '';
}, $existing_integrations);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Integração - <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
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
                    <li><a href="<?= BASE_URL ?>/integrations">← Voltar</a></li>
                    <li><a href="<?= BASE_URL ?>/logout">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container" style="max-width: 600px; margin: 2rem auto; padding: 0 1rem;">
        <!-- Header da página -->
        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: var(--font-size-3xl); font-weight: 800; margin-bottom: 0.5rem;">🔗 Nova Integração</h1>
            <p style="color: var(--color-gray);">Configure uma nova conexão com uma rede de afiliados</p>
        </div>

        <!-- Mensagens -->
        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <!-- Formulário -->
        <div class="card">
            <div class="card-header">
                <h2>Configurar integração</h2>
            </div>
            <div class="card-body">
                <form method="POST" autocomplete="off" novalidate>
                    <div class="form-group">
                        <label for="platform" class="form-label">Plataforma *</label>
                        <select id="platform" name="platform" class="form-input" required onchange="updatePlatformInfo()">
                            <option value="">Selecione uma plataforma</option>
                            <option value="hotmart">Hotmart</option>
                            <option value="monetizze">Monetizze</option>
                            <option value="eduzz">Eduzz</option>
                            <option value="braip">Braip</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="name" class="form-label">Nome da integração *</label>
                        <input type="text" id="name" name="name" class="form-input" placeholder="Ex: Minha conta Hotmart" required>
                    </div>

                    <div class="form-group">
                        <label for="api_key" class="form-label" id="api_key_label">API Key *</label>
                        <input type="text" id="api_key" name="api_key" class="form-input" placeholder="Sua chave da API" required>
                        <div id="api_key_help" style="font-size: var(--font-size-sm); color: var(--color-gray); margin-top: 0.5rem;">
                            Selecione uma plataforma para ver instruções específicas
                        </div>
                    </div>

                    <div class="form-group" id="api_secret_group" style="display: none;">
                        <label for="api_secret" class="form-label">API Secret</label>
                        <input type="password" id="api_secret" name="api_secret" class="form-input" placeholder="Seu secret da API">
                        <div id="api_secret_help" style="font-size: var(--font-size-sm); color: var(--color-gray); margin-top: 0.5rem;">
                            Necessário apenas para algumas plataformas
                        </div>
                    </div>

                    <div id="platform_instructions" style="display: none; margin-top: 1rem; padding: 1rem; background: #f0f9ff; border-radius: 0.5rem; border-left: 4px solid var(--color-primary);">
                        <div id="instructions_content"></div>
                    </div>

                    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            🚀 Criar integração
                        </button>
                        <a href="<?= BASE_URL ?>/integrations" class="btn btn-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Informações adicionais -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h3>💡 Como funciona</h3>
            </div>
            <div class="card-body">
                <ol style="color: var(--color-gray); line-height: 1.6;">
                    <li><strong>Configure a integração</strong> com suas credenciais da API</li>
                    <li><strong>Copie a URL do webhook</strong> que será gerada automaticamente</li>
                    <li><strong>Configure o webhook</strong> na plataforma de afiliados</li>
                    <li><strong>Teste a conexão</strong> para verificar se está funcionando</li>
                    <li><strong>Suas vendas</strong> serão sincronizadas automaticamente!</li>
                </ol>
            </div>
        </div>
    </div>

    <script>
        function updatePlatformInfo() {
            const platform = document.getElementById('platform').value;
            const apiKeyHelp = document.getElementById('api_key_help');
            const apiSecretGroup = document.getElementById('api_secret_group');
            const instructions = document.getElementById('platform_instructions');
            const instructionsContent = document.getElementById('instructions_content');

            if (!platform) {
                apiKeyHelp.textContent = 'Selecione uma plataforma para ver instruções específicas';
                apiSecretGroup.style.display = 'none';
                instructions.style.display = 'none';
                // Reset do label/required do campo
                const apiKeyInput = document.getElementById('api_key');
                const apiKeyLabel = document.getElementById('api_key_label');
                apiKeyLabel.textContent = 'API Key *';
                apiKeyInput.required = true;
                apiKeyInput.placeholder = 'Sua chave da API';
                return;
            }

            const platformInfo = {
                hotmart: {
                    apiKeyLabel: 'Client ID (ou deixe vazio se usar Basic)',
                    apiKeyHelp: 'Você receberá 3 credenciais: Client ID, Client Secret e Basic. Use qualquer uma das opções abaixo.',
                    needsSecret: true,
                    instructions: '<strong>📍 Como configurar (2 opções):</strong><br><br><strong>OPÇÃO 1 - Usar Basic Token (Recomendado):</strong><br>• API Key: pode deixar vazio<br>• API Secret: cole o token "Basic abc123..." completo<br><br><strong>OPÇÃO 2 - Usar OAuth:</strong><br>• API Key: cole o Client ID<br>• API Secret: cole o Client Secret (sem "Basic ")<br><br><strong>Onde encontrar:</strong><br>Hotmart > Ferramentas > Integrações > API > Gerar credenciais'
                },
                monetizze: {
                    apiKeyLabel: 'API Key da Monetizze',
                    apiKeyHelp: 'Encontre em: Monetizze > Minha Conta > Integrações',
                    needsSecret: false,
                    instructions: '<strong>📍 Como configurar:</strong><br>1. Acesse Monetizze > Minha Conta > Integrações<br>2. Gere uma nova API Key<br>3. Copie a chave gerada<br>4. Configure os webhooks na seção de notificações'
                },
                eduzz: {
                    apiKeyLabel: 'API Key da Eduzz',
                    apiKeyHelp: 'Encontre em: Eduzz > Configurações > API',
                    needsSecret: false,
                    instructions: '<strong>📍 Como configurar:</strong><br>1. Acesse Eduzz > Configurações > API<br>2. Gere uma nova chave de API<br>3. Copie a chave gerada<br>4. Configure os webhooks para receber notificações'
                },
                braip: {
                    apiKeyLabel: 'API Key da Braip',
                    apiKeyHelp: 'Encontre em: Braip > Configurações > Integrações',
                    needsSecret: false,
                    instructions: '<strong>📍 Como configurar:</strong><br>1. Acesse Braip > Configurações > Integrações<br>2. Gere uma nova API Key<br>3. Copie a chave gerada<br>4. Configure os webhooks para eventos de venda'
                }
            };

            const info = platformInfo[platform];
            if (info) {
                const apiKeyInput = document.getElementById('api_key');
                const apiKeyLabel = document.getElementById('api_key_label');

                if (platform === 'hotmart') {
                    apiKeyLabel.textContent = info.apiKeyLabel;
                    apiKeyInput.required = false;
                    apiKeyInput.placeholder = "Opcional - deixe vazio se usar Basic token";
                } else {
                    apiKeyLabel.textContent = info.apiKeyLabel + ' *';
                    apiKeyInput.required = true;
                    apiKeyInput.placeholder = "Sua chave da API";
                }

                apiKeyHelp.textContent = info.apiKeyHelp;
                apiSecretGroup.style.display = info.needsSecret ? 'block' : 'none';
                instructionsContent.innerHTML = info.instructions;
                instructions.style.display = 'block';
            }
        }

        // Plataformas já usadas (vindo do PHP)
        const existingPlatforms = <?=
            json_encode(array_values(array_filter($used_platforms)), JSON_UNESCAPED_UNICODE)
        ?>;

        // Desabilitar opções já utilizadas
        const platformSelect = document.getElementById('platform');
        const options = platformSelect.querySelectorAll('option');
        options.forEach(option => {
            if (option.value && existingPlatforms.includes(option.value.toLowerCase())) {
                option.disabled = true;
                if (!option.text.includes('(Já configurada)')) {
                    option.text = option.text + ' (Já configurada)';
                }
            }
        });

        // Auto-focus no primeiro campo
        platformSelect.focus();
    </script>
</body>
</html>
