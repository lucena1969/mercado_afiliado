<?php
/**
 * PlanValidationService - Serviço para validação de planos e limites
 */

class PlanValidationService {
    private $db;
    
    // Limites por plano
    const PLAN_LIMITS = [
        'starter' => [
            'short_links' => 100, // TEMPORÁRIO: 100 links para testes (era 0)
            'utm_templates' => 10, // TEMPORÁRIO: 10 templates para testes (era 0)
            'clicks_per_month' => 10000, // TEMPORÁRIO: 10k cliques para testes (era 0)
            'analytics_retention_days' => 30 // TEMPORÁRIO: 30 dias para testes (era 0)
        ],
        'pro' => [
            'short_links' => 1000,
            'utm_templates' => 50,
            'clicks_per_month' => 50000,
            'analytics_retention_days' => 365
        ],
        'scale' => [
            'short_links' => -1, // ilimitado
            'utm_templates' => -1, // ilimitado
            'clicks_per_month' => -1, // ilimitado
            'analytics_retention_days' => 730
        ]
    ];

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Verificar se usuário tem acesso a uma funcionalidade
     */
    public function hasFeatureAccess($user_id, $feature) {
        // BYPASS TEMPORÁRIO PARA TESTES
        if ($feature === "link_maestro" || $feature === "utm_templates") {
            return true;
        }
        
        $user_plan = $this->getUserPlan($user_id);
        
        $feature_access = [
            'link_maestro' => ['pro', 'scale'],
            'utm_templates' => ['pro', 'scale'],
            'advanced_analytics' => ['pro', 'scale'],
            'click_tracking' => ['pro', 'scale'],
            'custom_domains' => ['scale'],
            'team_management' => ['scale'],
            'white_label' => ['scale']
        ];

        if (!isset($feature_access[$feature])) {
            return false;
        }

        return in_array($user_plan['slug'], $feature_access[$feature]);
    }

    /**
     * Verificar se usuário pode criar um novo link
     */
    public function canCreateShortLink($user_id) {
        $user_plan = $this->getUserPlan($user_id);
        
        // BYPASS TEMPORÁRIO
        // if (!in_array($user_plan['slug'], ['pro', 'scale'])) {
        //     return [
        //         'allowed' => false,
        //         'reason' => 'Link Maestro disponível apenas nos planos Pro e Scale',
        //         'required_plan' => 'pro'
        //     ];
        // }

        $current_count = $this->getUserShortLinkCount($user_id);
        $limit = self::PLAN_LIMITS[$user_plan['slug']]['short_links'];

        if ($limit !== -1 && $current_count >= $limit) {
            return [
                'allowed' => false,
                'reason' => "Limite de {$limit} links atingido. Upgrade para Scale para links ilimitados.",
                'current_count' => $current_count,
                'limit' => $limit
            ];
        }

        return [
            'allowed' => true,
            'current_count' => $current_count,
            'limit' => $limit
        ];
    }

    /**
     * Verificar se usuário pode criar um novo template UTM
     */
    public function canCreateUtmTemplate($user_id) {
        $user_plan = $this->getUserPlan($user_id);
        
        // BYPASS TEMPORÁRIO
        // if (!in_array($user_plan['slug'], ['pro', 'scale'])) {
        //     return [
        //         'allowed' => false,
        //         'reason' => 'Templates UTM disponíveis apenas nos planos Pro e Scale',
        //         'required_plan' => 'pro'
        //     ];
        // }

        $current_count = $this->getUserUtmTemplateCount($user_id);
        $limit = self::PLAN_LIMITS[$user_plan['slug']]['utm_templates'];

        if ($limit !== -1 && $current_count >= $limit) {
            return [
                'allowed' => false,
                'reason' => "Limite de {$limit} templates atingido. Upgrade para Scale para templates ilimitados.",
                'current_count' => $current_count,
                'limit' => $limit
            ];
        }

        return [
            'allowed' => true,
            'current_count' => $current_count,
            'limit' => $limit
        ];
    }

    /**
     * Verificar limites de cliques mensais
     */
    public function checkMonthlyClickLimit($user_id) {
        $user_plan = $this->getUserPlan($user_id);
        $limit = self::PLAN_LIMITS[$user_plan['slug']]['clicks_per_month'];

        if ($limit === -1) {
            return [
                'within_limit' => true,
                'unlimited' => true
            ];
        }

        $current_month_clicks = $this->getMonthlyClicks($user_id);

        return [
            'within_limit' => $current_month_clicks < $limit,
            'current_clicks' => $current_month_clicks,
            'limit' => $limit,
            'percentage_used' => $limit > 0 ? round(($current_month_clicks / $limit) * 100, 2) : 0
        ];
    }

    /**
     * Obter estatísticas de uso do usuário
     */
    public function getUserUsageStats($user_id) {
        $user_plan = $this->getUserPlan($user_id);
        $limits = self::PLAN_LIMITS[$user_plan['slug']];

        return [
            'plan' => $user_plan,
            'usage' => [
                'short_links' => [
                    'current' => $this->getUserShortLinkCount($user_id),
                    'limit' => $limits['short_links']
                ],
                'utm_templates' => [
                    'current' => $this->getUserUtmTemplateCount($user_id),
                    'limit' => $limits['utm_templates']
                ],
                'monthly_clicks' => [
                    'current' => $this->getMonthlyClicks($user_id),
                    'limit' => $limits['clicks_per_month']
                ]
            ]
        ];
    }

    /**
     * Obter plano atual do usuário
     */
    private function getUserPlan($user_id) {
        try {
            $query = "SELECT sp.id, sp.name, sp.slug, sp.price, us.status, us.trial_ends_at
                     FROM user_subscriptions us 
                     JOIN subscription_plans sp ON us.plan_id = sp.id 
                     WHERE us.user_id = ? AND us.status IN ('active', 'trial')
                     ORDER BY us.created_at DESC 
                     LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id]);
            $plan = $stmt->fetch();

            if (!$plan) {
                // Retornar plano starter por padrão
                return [
                    'id' => null,
                    'name' => 'Starter',
                    'slug' => 'starter',
                    'price' => 0,
                    'status' => 'active'
                ];
            }

            return $plan;

        } catch (Exception $e) {
            return [
                'id' => null,
                'name' => 'Starter',
                'slug' => 'starter',
                'price' => 0,
                'status' => 'active'
            ];
        }
    }

    /**
     * Contar links do usuário
     */
    private function getUserShortLinkCount($user_id) {
        try {
            $query = "SELECT COUNT(*) FROM short_links WHERE user_id = ? AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id]);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Contar templates UTM do usuário
     */
    private function getUserUtmTemplateCount($user_id) {
        try {
            $query = "SELECT COUNT(*) FROM utm_templates WHERE user_id = ? AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id]);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Obter cliques do mês atual
     */
    private function getMonthlyClicks($user_id) {
        try {
            $start_of_month = date('Y-m-01 00:00:00');
            $end_of_month = date('Y-m-t 23:59:59');

            $query = "SELECT COUNT(*) 
                     FROM link_clicks lc
                     JOIN short_links sl ON lc.short_link_id = sl.id
                     WHERE sl.user_id = ? 
                     AND lc.clicked_at BETWEEN ? AND ?";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id, $start_of_month, $end_of_month]);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Verificar se trial está próximo do vencimento
     */
    public function getTrialWarning($user_id) {
        $user_plan = $this->getUserPlan($user_id);

        if ($user_plan['status'] !== 'trial' || !$user_plan['trial_ends_at']) {
            return null;
        }

        $trial_end = strtotime($user_plan['trial_ends_at']);
        $now = time();
        $days_left = ceil(($trial_end - $now) / (24 * 60 * 60));

        if ($days_left <= 7) {
            return [
                'days_left' => max(0, $days_left),
                'urgent' => $days_left <= 2,
                'message' => $days_left <= 0 
                    ? 'Seu trial expirou. Faça upgrade para continuar usando o Link Maestro.'
                    : "Seu trial expira em {$days_left} dias. Não perca o acesso ao Link Maestro!"
            ];
        }

        return null;
    }
}
?>