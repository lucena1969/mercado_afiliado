<?php
/**
 * Model Subscription - Gerenciamento de assinaturas
 */

class Subscription {
    private $conn;
    private $table_name = "user_subscriptions"; // Nome correto no banco de produção
    private $plans_table = "subscription_plans";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Buscar planos ativos
    public function getActivePlans() {
        $query = "SELECT * FROM " . $this->plans_table . " 
                  WHERE is_active = 1 ORDER BY id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Buscar plano por slug
    public function getPlanBySlug($slug) {
        $query = "SELECT * FROM " . $this->plans_table . " 
                  WHERE slug = :slug AND is_active = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":slug", $slug);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Criar trial para usuário
    public function createTrial($user_id, $plan_id) {
        // Verificar se já tem trial ativo
        if($this->hasActiveSubscription($user_id)) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, plan_id, status, trial_ends_at, starts_at)
                  VALUES (:user_id, :plan_id, 'trial', 
                         DATE_ADD(NOW(), INTERVAL 14 DAY), 
                         NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":plan_id", $plan_id);
        
        return $stmt->execute();
    }

    // Buscar assinatura ativa do usuário
    public function getActiveSubscription($user_id) {
        $query = "SELECT s.*, p.name as plan_name, p.slug as plan_slug, p.features
                  FROM " . $this->table_name . " s
                  JOIN " . $this->plans_table . " p ON s.plan_id = p.id
                  WHERE s.user_id = :user_id 
                  AND s.status IN ('active', 'trial') 
                  AND (s.ends_at IS NULL OR s.ends_at > NOW())
                  ORDER BY s.created_at DESC LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Verificar se usuário tem assinatura ativa
    public function hasActiveSubscription($user_id) {
        return $this->getActiveSubscription($user_id) !== false;
    }

    // Verificar se usuário tem permissão para feature (versão simplificada)
    public function hasFeature($user_id, $feature) {
        $subscription = $this->getActiveSubscription($user_id);
        return $subscription !== false; // Por enquanto, qualquer assinatura ativa permite qualquer feature
    }

    // Buscar limite de uma feature (versão simplificada)
    public function getFeatureLimit($user_id, $feature) {
        $subscription = $this->getActiveSubscription($user_id);
        return $subscription ? 1000 : 0; // Limite padrão de 1000 para assinantes
    }

    // Verificar se trial está expirado
    public function isTrialExpired($user_id) {
        $query = "SELECT trial_ends_at FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND status = 'trial' 
                  ORDER BY created_at DESC LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch();
        
        if($result && $result['trial_ends_at']) {
            return strtotime($result['trial_ends_at']) < time();
        }
        
        return false;
    }

    // Atualizar status da assinatura
    public function updateStatus($subscription_id, $status) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status, updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $subscription_id);
        
        return $stmt->execute();
    }
}