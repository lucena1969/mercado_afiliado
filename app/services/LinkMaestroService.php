<?php
/**
 * LinkMaestroService - Lógica de negócio do Link Maestro
 */

require_once __DIR__ . '/../models/UtmTemplate.php';
require_once __DIR__ . '/../models/ShortLink.php';
require_once __DIR__ . '/../models/LinkClick.php';
require_once __DIR__ . '/PlanValidationService.php';

class LinkMaestroService {
    private $db;
    private $utm_template_model;
    private $short_link_model;
    private $link_click_model;
    private $plan_validation;

    public function __construct($database_connection) {
        $this->db = $database_connection;
        $this->utm_template_model = new UtmTemplate($this->db);
        $this->short_link_model = new ShortLink($this->db);
        $this->link_click_model = new LinkClick($this->db);
        $this->plan_validation = new PlanValidationService($this->db);
    }

    // Criar link encurtado com template UTM
    public function createShortLink($user_id, $data) {
        try {
            // Validar dados obrigatórios
            if (empty($data['original_url'])) {
                throw new Exception('URL original é obrigatória');
            }

            if (!filter_var($data['original_url'], FILTER_VALIDATE_URL)) {
                throw new Exception('URL inválida');
            }

            // Verificar limites e permissões usando o PlanValidationService
            $canCreate = $this->plan_validation->canCreateShortLink($user_id);
            
            if (!$canCreate['allowed']) {
                return [
                    'success' => false,
                    'error' => $canCreate['reason'],
                    'upgrade_required' => !$this->plan_validation->hasFeatureAccess($user_id, 'link_maestro'),
                    'usage_info' => isset($canCreate['current_count']) ? [
                        'current' => $canCreate['current_count'],
                        'limit' => $canCreate['limit']
                    ] : null
                ];
            }

            // Aplicar template UTM se especificado
            $utm_params = [];
            if (!empty($data['template_id'])) {
                $template = $this->utm_template_model->findById($data['template_id']);
                if ($template && $template['user_id'] == $user_id) {
                    $variables = [
                        'campaign_name' => $data['campaign_name'] ?? '',
                        'ad_name' => $data['ad_name'] ?? '',
                        'creative_name' => $data['creative_name'] ?? '',
                        'keyword' => $data['keyword'] ?? '',
                        'target_audience' => $data['target_audience'] ?? '',
                        'ad_group' => $data['ad_group'] ?? ''
                    ];

                    $utm_params = $this->utm_template_model->applyTemplate($template, $variables);
                    
                    // Incrementar contador de uso do template
                    $this->utm_template_model->incrementUsage($template['id']);
                }
            } else {
                // UTM manual
                $utm_params = [
                    'utm_source' => $data['utm_source'] ?? '',
                    'utm_medium' => $data['utm_medium'] ?? '',
                    'utm_campaign' => $data['utm_campaign'] ?? '',
                    'utm_content' => $data['utm_content'] ?? '',
                    'utm_term' => $data['utm_term'] ?? ''
                ];
            }

            // Construir URL final com UTMs
            $final_url = $this->short_link_model->buildFinalUrl($data['original_url'], $utm_params);

            // Gerar código único
            $short_code = $this->short_link_model->generateShortCode();

            // Preparar dados do link
            $this->short_link_model->user_id = $user_id;
            $this->short_link_model->utm_template_id = $data['template_id'] ?? null;
            $this->short_link_model->short_code = $short_code;
            $this->short_link_model->original_url = $data['original_url'];
            $this->short_link_model->final_url = $final_url;
            $this->short_link_model->title = $data['title'] ?? '';
            $this->short_link_model->description = $data['description'] ?? '';
            $this->short_link_model->campaign_name = $data['campaign_name'] ?? '';
            $this->short_link_model->ad_name = $data['ad_name'] ?? '';
            $this->short_link_model->creative_name = $data['creative_name'] ?? '';
            $this->short_link_model->utm_source = $utm_params['utm_source'] ?? null;
            $this->short_link_model->utm_medium = $utm_params['utm_medium'] ?? null;
            $this->short_link_model->utm_campaign = $utm_params['utm_campaign'] ?? null;
            $this->short_link_model->utm_content = $utm_params['utm_content'] ?? null;
            $this->short_link_model->utm_term = $utm_params['utm_term'] ?? null;
            $this->short_link_model->status = 'active';
            $this->short_link_model->expires_at = $data['expires_at'] ?? null;

            // Criar link
            if ($this->short_link_model->create()) {
                return [
                    'success' => true,
                    'link_id' => $this->short_link_model->id,
                    'short_code' => $short_code,
                    'short_url' => $this->getShortUrl($short_code),
                    'final_url' => $final_url
                ];
            } else {
                throw new Exception('Erro ao criar link encurtado');
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Processar clique no link
    public function processClick($short_code, $request_data = []) {
        try {
            // Buscar link pelo código
            $link = $this->short_link_model->findByShortCode($short_code);
            
            if (!$link) {
                throw new Exception('Link não encontrado');
            }

            // Verificar se link expirou
            if ($link['status'] !== 'active' || $this->short_link_model->isExpired()) {
                throw new Exception('Link expirado ou inativo');
            }

            // Verificar limites de cliques mensais do usuário
            $click_limit = $this->plan_validation->checkMonthlyClickLimit($link['user_id']);
            
            if (!$click_limit['within_limit']) {
                // Registrar tentativa de clique mas não redirecionar
                error_log("Limite de cliques mensais excedido para usuário: {$link['user_id']}");
                
                // Ainda assim registrar o clique para analytics
                $this->registerClick($link, $request_data, false); // false = não contar para o limite
                
                // Redirecionar para página de upgrade
                throw new Exception('Limite de cliques mensais atingido. Faça upgrade para continuar.');
            }

            // Registrar o clique
            $this->registerClick($link, $request_data);

            return [
                'success' => true,
                'redirect_url' => $link['final_url'],
                'link_title' => $link['title']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Registrar clique no link
    private function registerClick($link, $request_data, $count_for_limit = true) {
        try {
            // Coletar dados do clique
            $click_data = $this->collectClickData($request_data);
            
            // Verificar se é clique único
            $is_unique = $this->link_click_model->isUniqueClick(
                $link['id'], 
                $click_data['ip_address'], 
                $click_data['session_id']
            );

            // Registrar clique
            $this->link_click_model->short_link_id = $link['id'];
            $this->link_click_model->user_id = $link['user_id'];
            $this->link_click_model->ip_address = $click_data['ip_address'];
            $this->link_click_model->user_agent = $click_data['user_agent'];
            $this->link_click_model->referer = $click_data['referer'];
            $this->link_click_model->country = $click_data['country'];
            $this->link_click_model->region = $click_data['region'];
            $this->link_click_model->city = $click_data['city'];
            $this->link_click_model->device_type = $click_data['device_type'];
            $this->link_click_model->browser = $click_data['browser'];
            $this->link_click_model->os = $click_data['os'];
            $this->link_click_model->utm_source = $link['utm_source'];
            $this->link_click_model->utm_medium = $link['utm_medium'];
            $this->link_click_model->utm_campaign = $link['utm_campaign'];
            $this->link_click_model->utm_content = $link['utm_content'];
            $this->link_click_model->utm_term = $link['utm_term'];
            $this->link_click_model->click_timestamp = date('Y-m-d H:i:s');
            $this->link_click_model->session_id = $click_data['session_id'];
            $this->link_click_model->is_unique = $is_unique;

            $this->link_click_model->create();

            // Se count_for_limit for false, não incluir nas estatísticas de limite
            // (usado quando clique excede limite mas queremos registrar para analytics)
            
            return true;

        } catch (Exception $e) {
            error_log('Erro ao registrar clique: ' . $e->getMessage());
            return false;
        }
    }

    // Coletar dados do clique
    private function collectClickData($request_data) {
        $ip_address = LinkClick::getVisitorIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        
        // Parse do user agent
        $device_info = $this->link_click_model->parseUserAgent($user_agent);
        
        // Geolocalização (simulada - em produção usar serviço real)
        $geo_data = $this->getGeolocation($ip_address);
        
        // Session ID
        $session_id = session_id() ?: uniqid('sess_');

        return [
            'ip_address' => $ip_address,
            'user_agent' => $user_agent,
            'referer' => $referer,
            'country' => $geo_data['country'] ?? null,
            'region' => $geo_data['region'] ?? null,
            'city' => $geo_data['city'] ?? null,
            'device_type' => $device_info['device_type'],
            'browser' => $device_info['browser'],
            'os' => $device_info['os'],
            'session_id' => $session_id
        ];
    }

    // Geolocalização básica (placeholder)
    private function getGeolocation($ip_address) {
        // Em produção, integrar com serviço como MaxMind, IPStack, etc.
        // Por ora, retornar dados padrão
        return [
            'country' => 'BR',
            'region' => null,
            'city' => null
        ];
    }

    // Verificar se usuário tem acesso Pro
    private function hasProAccess($user_id) {
        try {
            $query = "SELECT sp.slug 
                     FROM user_subscriptions us 
                     JOIN subscription_plans sp ON us.plan_id = sp.id 
                     WHERE us.user_id = ? AND us.status IN ('active', 'trial')
                     ORDER BY us.created_at DESC 
                     LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id]);
            $plan = $stmt->fetch();

            return $plan && in_array($plan['slug'], ['pro', 'scale']);

        } catch (Exception $e) {
            return false;
        }
    }

    // Verificar limites do usuário
    private function checkUserLimits($user_id) {
        try {
            // Buscar plano do usuário
            $query = "SELECT sp.limits_json 
                     FROM user_subscriptions us 
                     JOIN subscription_plans sp ON us.plan_id = sp.id 
                     WHERE us.user_id = ? AND us.status IN ('active', 'trial')
                     ORDER BY us.created_at DESC 
                     LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();

            if (!$result) return false;

            $limits = json_decode($result['limits_json'], true);
            
            // Verificar se tem acesso ao Link Maestro
            if (!($limits['link_maestro'] ?? false)) {
                return false;
            }

            // Contar links ativos do usuário neste mês
            $count_query = "SELECT COUNT(*) as total 
                           FROM short_links 
                           WHERE user_id = ? 
                           AND status = 'active' 
                           AND MONTH(created_at) = MONTH(CURRENT_DATE())
                           AND YEAR(created_at) = YEAR(CURRENT_DATE())";

            $count_stmt = $this->db->prepare($count_query);
            $count_stmt->execute([$user_id]);
            $count_result = $count_stmt->fetch();

            $current_links = $count_result['total'] ?? 0;
            $link_limit = $limits['links_per_month'] ?? 50; // Default para Pro

            return $current_links < $link_limit;

        } catch (Exception $e) {
            return false;
        }
    }

    // Gerar URL encurtada completa
    private function getShortUrl($short_code) {
        $base_url = $_SERVER['HTTPS'] ? 'https://' : 'http://';
        $base_url .= $_SERVER['HTTP_HOST'];
        return $base_url . '/l/' . $short_code;
    }

    // Buscar presets por plataforma
    public function getPresetsByPlatform($platform) {
        $query = "SELECT * FROM utm_presets 
                 WHERE platform = ? AND status = 'active' 
                 ORDER BY sort_order ASC, preset_name ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$platform]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Dashboard resumo do usuário
    public function getUserDashboard($user_id) {
        // Estatísticas básicas
        $stats_query = "SELECT 
                          COUNT(CASE WHEN sl.status = 'active' THEN 1 END) as active_links,
                          COUNT(CASE WHEN sl.status = 'expired' THEN 1 END) as expired_links,
                          SUM(sl.click_count) as total_clicks,
                          COUNT(CASE WHEN DATE(sl.created_at) = CURDATE() THEN 1 END) as links_today
                       FROM short_links sl 
                       WHERE sl.user_id = ?";

        $stmt = $this->db->prepare($stats_query);
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Links mais clicados
        $top_links = $this->short_link_model->getTopLinks($user_id, 5);

        // Campanhas top
        $top_campaigns = $this->link_click_model->getTopCampaigns($user_id, 5);

        return [
            'stats' => $stats,
            'top_links' => $top_links,
            'top_campaigns' => $top_campaigns
        ];
    }

    // Relatório completo de analytics
    public function getAnalyticsReport($user_id, $start_date = null, $end_date = null) {
        if (!$start_date) $start_date = date('Y-m-d', strtotime('-30 days'));
        if (!$end_date) $end_date = date('Y-m-d');

        return [
            'period' => ['start' => $start_date, 'end' => $end_date],
            'clicks_by_period' => $this->link_click_model->getClicksByPeriod($user_id, $start_date, $end_date),
            'geographic_analysis' => $this->link_click_model->getGeographicAnalysis($user_id),
            'device_analysis' => $this->link_click_model->getDeviceAnalysis($user_id),
            'peak_hours' => $this->link_click_model->getPeakHours($user_id),
            'top_campaigns' => $this->link_click_model->getTopCampaigns($user_id, 10)
        ];
    }
}
?>