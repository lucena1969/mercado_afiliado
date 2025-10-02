<?php
/**
 * PlanValidationMiddleware - Middleware para validação de planos e recursos premium
 */

require_once __DIR__ . '/../services/PlanValidationService.php';

class PlanValidationMiddleware {
    private $planValidation;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->planValidation = new PlanValidationService($db);
    }

    /**
     * Verificar acesso a recurso premium
     */
    public function requireFeature($user_id, $feature) {
        if (!$this->planValidation->hasFeatureAccess($user_id, $feature)) {
            $this->returnUpgradeRequired($user_id, $feature);
            return false;
        }
        return true;
    }

    /**
     * Verificar limites antes de criar recurso
     */
    public function checkCreateLimits($user_id, $resource_type) {
        switch ($resource_type) {
            case 'short_link':
                $result = $this->planValidation->canCreateShortLink($user_id);
                break;
            case 'utm_template':
                $result = $this->planValidation->canCreateUtmTemplate($user_id);
                break;
            default:
                return ['allowed' => false, 'reason' => 'Tipo de recurso não reconhecido'];
        }

        if (!$result['allowed']) {
            $this->returnLimitExceeded($result);
            return false;
        }

        return true;
    }

    /**
     * Verificar avisos de trial
     */
    public function checkTrialWarning($user_id) {
        $warning = $this->planValidation->getTrialWarning($user_id);
        
        if ($warning) {
            return [
                'has_warning' => true,
                'warning' => $warning
            ];
        }

        return ['has_warning' => false];
    }

    /**
     * Obter estatísticas de uso para exibir na interface
     */
    public function getUsageStats($user_id) {
        return $this->planValidation->getUserUsageStats($user_id);
    }

    /**
     * Retornar resposta JSON para upgrade necessário
     */
    private function returnUpgradeRequired($user_id, $feature) {
        $usage_stats = $this->planValidation->getUserUsageStats($user_id);
        
        header('Content-Type: application/json');
        http_response_code(403);
        
        echo json_encode([
            'success' => false,
            'error' => 'Upgrade necessário',
            'upgrade_required' => true,
            'feature' => $feature,
            'current_plan' => $usage_stats['plan']['name'],
            'required_plan' => $this->getRequiredPlan($feature),
            'message' => $this->getFeatureMessage($feature)
        ]);
        exit;
    }

    /**
     * Retornar resposta JSON para limite excedido
     */
    private function returnLimitExceeded($limit_info) {
        header('Content-Type: application/json');
        http_response_code(429); // Too Many Requests
        
        echo json_encode([
            'success' => false,
            'error' => 'Limite atingido',
            'limit_exceeded' => true,
            'reason' => $limit_info['reason'],
            'current_count' => $limit_info['current_count'] ?? 0,
            'limit' => $limit_info['limit'] ?? 0,
            'message' => 'Você atingiu o limite do seu plano atual.'
        ]);
        exit;
    }

    /**
     * Obter plano necessário para um recurso
     */
    private function getRequiredPlan($feature) {
        $feature_plans = [
            'link_maestro' => 'Pro',
            'utm_templates' => 'Pro',
            'advanced_analytics' => 'Pro',
            'click_tracking' => 'Pro',
            'custom_domains' => 'Scale',
            'team_management' => 'Scale',
            'white_label' => 'Scale'
        ];

        return $feature_plans[$feature] ?? 'Pro';
    }

    /**
     * Obter mensagem de recurso
     */
    private function getFeatureMessage($feature) {
        $messages = [
            'link_maestro' => 'Link Maestro está disponível nos planos Pro e Scale. Crie links encurtados profissionais com analytics detalhado.',
            'utm_templates' => 'Templates UTM facilitam a criação de campanhas padronizadas para todas as suas plataformas.',
            'advanced_analytics' => 'Analytics avançado oferece insights detalhados sobre o desempenho dos seus links.',
            'click_tracking' => 'Rastreamento de cliques permite monitorar o comportamento dos seus visitantes em tempo real.',
            'custom_domains' => 'Domínios personalizados estão disponíveis no plano Scale para links com sua marca.',
            'team_management' => 'Gerenciamento de equipe permite colaboração com outros usuários no plano Scale.',
            'white_label' => 'Solução white label remove nossa marca e permite personalização completa no plano Scale.'
        ];

        return $messages[$feature] ?? 'Recurso premium disponível nos planos pagos.';
    }

    /**
     * Middleware para APIs - retorna dados de validação
     */
    public function validateApiRequest($user_id, $feature, $resource_type = null) {
        // Verificar acesso ao recurso
        if (!$this->planValidation->hasFeatureAccess($user_id, $feature)) {
            return [
                'valid' => false,
                'type' => 'upgrade_required',
                'data' => [
                    'feature' => $feature,
                    'current_plan' => $this->planValidation->getUserUsageStats($user_id)['plan']['name'],
                    'required_plan' => $this->getRequiredPlan($feature),
                    'message' => $this->getFeatureMessage($feature)
                ]
            ];
        }

        // Verificar limites se for criação de recurso
        if ($resource_type) {
            $can_create = $this->checkCreateLimits($user_id, $resource_type);
            if (!$can_create) {
                return [
                    'valid' => false,
                    'type' => 'limit_exceeded'
                ];
            }
        }

        // Verificar avisos de trial
        $trial_warning = $this->checkTrialWarning($user_id);

        return [
            'valid' => true,
            'trial_warning' => $trial_warning,
            'usage_stats' => $this->getUsageStats($user_id)
        ];
    }

    /**
     * Injetar dados de validação nas views
     */
    public function injectViewData($user_id) {
        return [
            'plan_validation' => [
                'usage_stats' => $this->getUsageStats($user_id),
                'trial_warning' => $this->checkTrialWarning($user_id),
                'features' => [
                    'link_maestro' => $this->planValidation->hasFeatureAccess($user_id, 'link_maestro'),
                    'utm_templates' => $this->planValidation->hasFeatureAccess($user_id, 'utm_templates'),
                    'advanced_analytics' => $this->planValidation->hasFeatureAccess($user_id, 'advanced_analytics'),
                    'click_tracking' => $this->planValidation->hasFeatureAccess($user_id, 'click_tracking')
                ]
            ]
        ];
    }
}
?>