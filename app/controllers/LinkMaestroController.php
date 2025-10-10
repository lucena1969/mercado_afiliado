<?php
/**
 * LinkMaestroController - Controller do Link Maestro
 */

require_once __DIR__ . '/../services/LinkMaestroService.php';
require_once __DIR__ . '/../middleware/PlanValidationMiddleware.php';

class LinkMaestroController {
    private $db;
    private $linkMaestroService;
    private $planValidation;
    private $user_id;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->linkMaestroService = new LinkMaestroService($this->db);
        $this->planValidation = new PlanValidationMiddleware($this->db);
        
        // Verificar autenticação
        $this->checkAuth();
    }

    // Verificar se usuário está logado
    private function checkAuth() {
        if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
            header('Location: /login');
            exit;
        }
        
        $this->user_id = $_SESSION['user']['id'];
    }

    // Dashboard principal do Link Maestro
    public function index() {
        try {
            // Verificar se tem acesso Pro
            if (!$this->hasProAccess()) {
                $this->showUpgradeMessage();
                return;
            }

            // Buscar dados do dashboard
            $dashboard_data = $this->linkMaestroService->getUserDashboard($this->user_id);
            
            // Buscar templates do usuário
            $utm_template = new UtmTemplate($this->db);
            $templates = $utm_template->findByUser($this->user_id, 10);
            
            // Buscar links recentes
            $short_link = new ShortLink($this->db);
            $recent_links = $short_link->findByUser($this->user_id, 10);

            // Preparar dados para a view
            $data = [
                'dashboard' => $dashboard_data,
                'templates' => $templates,
                'recent_links' => $recent_links,
                'user_id' => $this->user_id
            ];

            $this->loadView('link_maestro/index', $data);

        } catch (Exception $e) {
            $this->handleError('Erro ao carregar dashboard: ' . $e->getMessage());
        }
    }

    // API: Criar novo link encurtado
    public function createLink() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            return;
        }

        try {
            // Validar acesso e limites usando middleware
            $validation = $this->planValidation->validateApiRequest($this->user_id, 'link_maestro', 'short_link');
            
            if (!$validation['valid']) {
                if ($validation['type'] === 'upgrade_required') {
                    http_response_code(403);
                    echo json_encode([
                        'success' => false,
                        'upgrade_required' => true,
                        'data' => $validation['data']
                    ]);
                } elseif ($validation['type'] === 'limit_exceeded') {
                    http_response_code(429);
                    echo json_encode([
                        'success' => false,
                        'limit_exceeded' => true,
                        'message' => 'Limite de links atingido para seu plano atual.'
                    ]);
                }
                return;
            }

            // Processar dados da requisição (FormData ou JSON)
            $input = [];
            
            // Primeiro tentar FormData/POST
            if (!empty($_POST)) {
                $input = $_POST;
            } else {
                // Fallback para JSON
                $json_input = json_decode(file_get_contents('php://input'), true);
                if ($json_input) {
                    $input = $json_input;
                }
            }
            
            // Processar variáveis do template se houver
            $template_variables = [];
            foreach ($input as $key => $value) {
                if (strpos($key, 'var_') === 0) {
                    $var_name = substr($key, 4); // Remove 'var_' prefix
                    $template_variables[$var_name] = $value;
                    unset($input[$key]); // Remove do input principal
                }
            }
            
            // Adicionar variáveis do template ao input
            if (!empty($template_variables)) {
                $input['template_variables'] = $template_variables;
            }

            // Validar dados obrigatórios
            if (empty($input['original_url'])) {
                throw new Exception('URL é obrigatória');
            }

            // Criar link através do service
            $result = $this->linkMaestroService->createShortLink($this->user_id, $input);

            if ($result['success']) {
                http_response_code(201);
                // Incluir dados de trial warning se houver
                if (isset($validation['trial_warning']['has_warning']) && $validation['trial_warning']['has_warning']) {
                    $result['trial_warning'] = $validation['trial_warning']['warning'];
                }
                echo json_encode($result);
            } else {
                if (isset($result['upgrade_required']) && $result['upgrade_required']) {
                    http_response_code(403);
                } else {
                    http_response_code(400);
                }
                echo json_encode($result);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Redirecionamento do link encurtado (/l/{code})
    public function redirect($short_code) {
        try {
            // Processar clique
            $result = $this->linkMaestroService->processClick($short_code, $_SERVER);

            if ($result['success']) {
                // Redirecionar para URL final
                header('Location: ' . $result['redirect_url'], true, 302);
                exit;
            } else {
                // Mostrar página de erro amigável
                $this->show404('Link não encontrado ou expirado');
            }

        } catch (Exception $e) {
            error_log('Erro no redirecionamento: ' . $e->getMessage());
            $this->show404('Erro interno do servidor');
        }
    }

    // API: Obter estatísticas de um link
    public function getLinkStats($link_id) {
        header('Content-Type: application/json');

        try {
            $short_link = new ShortLink($this->db);
            $link = $short_link->findById($link_id);

            if (!$link || $link['user_id'] != $this->user_id) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Link não encontrado']);
                return;
            }

            $link_click = new LinkClick($this->db);
            $stats = $link_click->getLinkStats($link_id);

            echo json_encode([
                'success' => true,
                'link' => $link,
                'stats' => $stats
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // API: Criar template UTM
    public function createTemplate() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            return;
        }

        try {
            // Validar acesso e limites usando middleware
            $validation = $this->planValidation->validateApiRequest($this->user_id, 'utm_templates', 'utm_template');
            
            if (!$validation['valid']) {
                if ($validation['type'] === 'upgrade_required') {
                    http_response_code(403);
                    echo json_encode([
                        'success' => false,
                        'upgrade_required' => true,
                        'data' => $validation['data']
                    ]);
                } elseif ($validation['type'] === 'limit_exceeded') {
                    http_response_code(429);
                    echo json_encode([
                        'success' => false,
                        'limit_exceeded' => true,
                        'message' => 'Limite de templates atingido para seu plano atual.'
                    ]);
                }
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }

            // Validar dados obrigatórios
            if (empty($input['name']) || empty($input['platform'])) {
                throw new Exception('Nome e plataforma são obrigatórios');
            }

            // Criar template
            $utm_template = new UtmTemplate($this->db);
            $utm_template->user_id = $this->user_id;
            $utm_template->name = $input['name'];
            $utm_template->platform = $input['platform'];
            $utm_template->description = $input['description'] ?? '';
            $utm_template->utm_source = $input['utm_source'] ?? '';
            $utm_template->utm_medium = $input['utm_medium'] ?? '';
            $utm_template->utm_campaign = $input['utm_campaign'] ?? '';
            $utm_template->utm_content = $input['utm_content'] ?? '';
            $utm_template->utm_term = $input['utm_term'] ?? '';
            $utm_template->status = 'active';
            $utm_template->is_default = 0;

            if ($utm_template->create()) {
                http_response_code(201);
                $response = [
                    'success' => true,
                    'template_id' => $utm_template->id,
                    'message' => 'Template criado com sucesso'
                ];

                // Incluir aviso de trial se houver
                if (isset($validation['trial_warning']['has_warning']) && $validation['trial_warning']['has_warning']) {
                    $response['trial_warning'] = $validation['trial_warning']['warning'];
                }

                echo json_encode($response);
            } else {
                throw new Exception('Erro ao criar template');
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // API: Buscar presets por plataforma
    public function getPresets($platform) {
        header('Content-Type: application/json');

        try {
            $presets = $this->linkMaestroService->getPresetsByPlatform($platform);

            echo json_encode([
                'success' => true,
                'presets' => $presets
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // API: Relatório de analytics
    public function getAnalytics() {
        header('Content-Type: application/json');

        try {
            $start_date = $_GET['start_date'] ?? null;
            $end_date = $_GET['end_date'] ?? null;

            $analytics = $this->linkMaestroService->getAnalyticsReport($this->user_id, $start_date, $end_date);

            echo json_encode([
                'success' => true,
                'analytics' => $analytics
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // API: Listar links do usuário
    public function getLinks() {
        header('Content-Type: application/json');

        try {
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = min(50, max(10, intval($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            $short_link = new ShortLink($this->db);
            $links = $short_link->findByUser($this->user_id, $limit, $offset);

            echo json_encode([
                'success' => true,
                'links' => $links,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit
                ]
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // API: Obter todos os presets organizados por plataforma
    public function getAllPresets() {
        header('Content-Type: application/json');

        try {
            $query = "SELECT * FROM utm_presets 
                     WHERE status = 'active' 
                     ORDER BY platform, sort_order ASC, preset_name ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $all_presets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Organizar por plataforma
            $presets_by_platform = [];
            foreach ($all_presets as $preset) {
                if (!isset($presets_by_platform[$preset['platform']])) {
                    $presets_by_platform[$preset['platform']] = [];
                }
                $presets_by_platform[$preset['platform']][] = $preset;
            }

            echo json_encode([
                'success' => true,
                'presets' => $presets_by_platform,
                'total' => count($all_presets)
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // API: Obter templates do usuário por plataforma
    public function getTemplates() {
        header('Content-Type: application/json');

        try {
            $platform = $_GET['platform'] ?? null;
            
            $query = "SELECT * FROM utm_templates 
                     WHERE user_id = ? AND status = 'active'";
            $params = [$this->user_id];

            if ($platform && $platform !== 'custom') {
                $query .= " AND platform = ?";
                $params[] = $platform;
            }

            $query .= " ORDER BY created_at DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'templates' => $templates,
                'total' => count($templates)
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // API: Registrar aceite de compliance
    public function recordCompliance() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['action'])) {
                throw new Exception('Dados de compliance inválidos');
            }

            // Registrar aceite de compliance
            $query = "INSERT INTO user_compliance_log (
                user_id, 
                action_type, 
                details, 
                ip_address, 
                user_agent, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $this->user_id,
                $input['action'],
                json_encode($input),
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Compliance registrado com sucesso'
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // API: Obter estatísticas de uso e limites
    public function getUsageStats() {
        header('Content-Type: application/json');

        try {
            $usage_stats = $this->planValidation->getUsageStats($this->user_id);
            $trial_warning = $this->planValidation->checkTrialWarning($this->user_id);

            // Instanciar PlanValidationService diretamente para hasFeatureAccess
            $planValidationService = new PlanValidationService($this->db);
            
            echo json_encode([
                'success' => true,
                'usage_stats' => $usage_stats,
                'trial_warning' => $trial_warning,
                'features' => [
                    'link_maestro' => $planValidationService->hasFeatureAccess($this->user_id, 'link_maestro'),
                    'utm_templates' => $planValidationService->hasFeatureAccess($this->user_id, 'utm_templates'),
                    'advanced_analytics' => $planValidationService->hasFeatureAccess($this->user_id, 'advanced_analytics'),
                    'click_tracking' => $planValidationService->hasFeatureAccess($this->user_id, 'click_tracking')
                ]
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Verificar acesso Pro
    private function hasProAccess() {
        try {
            $query = "SELECT sp.slug 
                     FROM user_subscriptions us 
                     JOIN subscription_plans sp ON us.plan_id = sp.id 
                     WHERE us.user_id = ? AND us.status IN ('active', 'trial')
                     ORDER BY us.created_at DESC 
                     LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$this->user_id]);
            $plan = $stmt->fetch();

            return $plan && in_array($plan['slug'], ['pro', 'scale']);

        } catch (Exception $e) {
            return false;
        }
    }

    // Mostrar mensagem de upgrade
    private function showUpgradeMessage() {
        $data = [
            'required_plan' => 'Pro',
            'current_plan' => $this->getCurrentPlan(),
            'feature_name' => 'Link Maestro'
        ];

        $this->loadView('link_maestro/upgrade_required', $data);
    }

    // Obter plano atual
    private function getCurrentPlan() {
        try {
            $query = "SELECT sp.name, sp.slug 
                     FROM user_subscriptions us 
                     JOIN subscription_plans sp ON us.plan_id = sp.id 
                     WHERE us.user_id = ? AND us.status IN ('active', 'trial')
                     ORDER BY us.created_at DESC 
                     LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$this->user_id]);
            $plan = $stmt->fetch();

            return $plan ? $plan['name'] : 'Starter';

        } catch (Exception $e) {
            return 'Desconhecido';
        }
    }

    // Carregar view
    private function loadView($view, $data = []) {
        extract($data);
        $view_path = dirname(dirname(__DIR__)) . '/templates/' . $view . '.php';
        
        if (file_exists($view_path)) {
            include $view_path;
        } else {
            throw new Exception("View não encontrada: {$view}");
        }
    }

    // Tratar erros
    private function handleError($message) {
        error_log($message);
        $data = ['error_message' => $message];
        $this->loadView('link_maestro/error', $data);
    }

    // Página 404 customizada
    private function show404($message = 'Página não encontrada') {
        http_response_code(404);
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>404 - Não Encontrado</title>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                .container { max-width: 500px; margin: 0 auto; }
                h1 { color: #e74c3c; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h1>404</h1>
                <p>{$message}</p>
                <a href='/dashboard'>← Voltar ao Dashboard</a>
            </div>
        </body>
        </html>";
        exit;
    }
}
?>