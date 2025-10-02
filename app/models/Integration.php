<?php
/**
 * Model Integration - Gerenciamento de integrações com redes
 */

class Integration {
    private $conn;
    private $table_name = "integrations";

    public $id;
    public $user_id;
    public $platform;
    public $name;
    public $api_key;
    public $api_secret;
    public $webhook_token;
    public $webhook_url;
    public $status;
    public $last_sync_at;
    public $last_error;
    public $config_json;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Criar nova integração
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id, platform=:platform, name=:name, 
                      api_key=:api_key, api_secret=:api_secret, 
                      webhook_token=:webhook_token, config_json=:config_json";
        
        $stmt = $this->conn->prepare($query);
        
        // Gerar webhook token se não fornecido
        if (empty($this->webhook_token)) {
            $this->webhook_token = bin2hex(random_bytes(32));
        }
        
        // Gerar webhook URL
        $this->webhook_url = BASE_URL . "/api/webhooks/" . $this->platform . "/" . $this->webhook_token;
        
        // Bind valores
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":platform", $this->platform);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":api_key", $this->api_key);
        $stmt->bindParam(":api_secret", $this->api_secret);
        $stmt->bindParam(":webhook_token", $this->webhook_token);
        $stmt->bindParam(":config_json", $this->config_json);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    // Buscar integrações do usuário
    public function getByUser($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Buscar por ID
    public function findById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Buscar por plataforma e usuário
    public function findByPlatformAndUser($platform, $user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE platform = :platform AND user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":platform", $platform);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Atualizar status da integração
    public function updateStatus($id, $status, $error = null) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status, last_error = :error, updated_at = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":error", $error);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }

    // Atualizar último sync
    public function updateLastSync($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET last_sync_at = NOW(), updated_at = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }

    // Buscar integrações ativas
    public function getActiveIntegrations($user_id = null) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE status = 'active'";
        
        if ($user_id) {
            $query .= " AND user_id = :user_id";
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($user_id) {
            $stmt->bindParam(":user_id", $user_id);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Verificar se usuário pode criar mais integrações (baseado no plano)
    public function canCreateIntegration($user_id) {
        // Buscar limite do plano do usuário
        $subscription = new Subscription($this->conn);
        $limit = $subscription->getFeatureLimit($user_id, 'integrations');
        
        // Contar integrações existentes
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND status != 'inactive'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result['total'] < $limit;
    }

    // Buscar por webhook token
    public function findByWebhookToken($token) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE webhook_token = :token AND status = 'active' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Atualizar configuração
    public function updateConfig($id, $config_json) {
        $query = "UPDATE " . $this->table_name . " 
                  SET config_json = :config_json, updated_at = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":config_json", $config_json);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }

    // Atualizar credenciais da integração
    public function updateCredentials($id, $name, $api_key, $api_secret = null, $webhook_token = null) {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, api_key = :api_key, api_secret = :api_secret, 
                      webhook_token = :webhook_token, updated_at = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":api_key", $api_key);
        $stmt->bindParam(":api_secret", $api_secret);
        $stmt->bindParam(":webhook_token", $webhook_token);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }

    // Deletar integração
    public function delete($id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $user_id);
        
        return $stmt->execute();
    }

    // Validar credenciais da API (método abstrato - será implementado nos services)
    public function validateCredentials($platform, $api_key, $api_secret = null) {
        // Este método será implementado nos services específicos de cada plataforma
        // Por enquanto, retorna true
        return true;
    }

    // Estatísticas da integração
    public function getStats($id) {
        $query = "SELECT 
                    COUNT(s.id) as total_sales,
                    SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved_sales,
                    SUM(CASE WHEN s.status = 'approved' THEN s.amount ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN s.status = 'approved' THEN s.commission_amount ELSE 0 END) as total_commission,
                    MAX(s.conversion_date) as last_sale_date
                  FROM sales s 
                  WHERE s.integration_id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
}