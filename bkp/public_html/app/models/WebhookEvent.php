<?php
/**
 * Model WebhookEvent - Gerenciamento de eventos de webhook
 */

class WebhookEvent {
    private $conn;
    private $table_name = "webhook_events";

    public $id;
    public $integration_id;
    public $platform;
    public $event_type;
    public $external_id;
    public $payload_json;
    public $processed;
    public $processing_error;
    public $received_at;
    public $processed_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Criar novo evento
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET integration_id=:integration_id, platform=:platform,
                      event_type=:event_type, external_id=:external_id,
                      payload_json=:payload_json, processed=:processed";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":integration_id", $this->integration_id);
        $stmt->bindParam(":platform", $this->platform);
        $stmt->bindParam(":event_type", $this->event_type);
        $stmt->bindParam(":external_id", $this->external_id);
        $stmt->bindParam(":payload_json", $this->payload_json);
        $stmt->bindParam(":processed", $this->processed);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    // Marcar como processado
    public function markAsProcessed($id, $error = null) {
        $query = "UPDATE " . $this->table_name . " 
                  SET processed = 1, processed_at = NOW(), processing_error = :error 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":error", $error);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }

    // Buscar eventos não processados
    public function getUnprocessed($limit = 100) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE processed = 0 
                  ORDER BY received_at ASC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Buscar eventos por integração
    public function getByIntegration($integration_id, $limit = 50) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE integration_id = :integration_id 
                  ORDER BY received_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":integration_id", $integration_id);
        $stmt->bindParam(":limit", $limit);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Buscar eventos por usuário
    public function getByUser($user_id, $limit = 50) {
        $query = "SELECT we.*, i.name as integration_name
                  FROM " . $this->table_name . " we
                  LEFT JOIN integrations i ON we.integration_id = i.id
                  WHERE (i.user_id = :user_id OR we.integration_id IS NULL)
                  ORDER BY we.received_at DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":limit", $limit);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Buscar eventos com erro
    public function getWithErrors($limit = 50) {
        $query = "SELECT we.*, i.name as integration_name, i.platform
                  FROM " . $this->table_name . " we
                  LEFT JOIN integrations i ON we.integration_id = i.id
                  WHERE we.processed = 1 AND we.processing_error IS NOT NULL
                  ORDER BY we.received_at DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Estatísticas de webhooks
    public function getStats($integration_id = null, $period_days = 7) {
        $where_clause = "WHERE received_at >= DATE_SUB(NOW(), INTERVAL :period_days DAY)";
        
        if ($integration_id) {
            $where_clause .= " AND integration_id = :integration_id";
        }
        
        $query = "SELECT 
                    COUNT(*) as total_events,
                    SUM(CASE WHEN processed = 1 AND processing_error IS NULL THEN 1 ELSE 0 END) as successful_events,
                    SUM(CASE WHEN processed = 1 AND processing_error IS NOT NULL THEN 1 ELSE 0 END) as failed_events,
                    SUM(CASE WHEN processed = 0 THEN 1 ELSE 0 END) as pending_events,
                    COUNT(DISTINCT platform) as platforms_count,
                    MAX(received_at) as last_event_at
                  FROM " . $this->table_name . " 
                  {$where_clause}";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":period_days", $period_days);
        
        if ($integration_id) {
            $stmt->bindParam(":integration_id", $integration_id);
        }
        
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Reprocessar evento
    public function reprocess($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET processed = 0, processing_error = NULL, processed_at = NULL 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }

    // Limpeza de eventos antigos
    public function cleanOldEvents($days_to_keep = 30) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE received_at < DATE_SUB(NOW(), INTERVAL :days_to_keep DAY)
                  AND processed = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":days_to_keep", $days_to_keep);
        
        return $stmt->execute();
    }

    // Buscar por ID
    public function findById($id) {
        $query = "SELECT we.*, i.name as integration_name, i.platform
                  FROM " . $this->table_name . " we
                  LEFT JOIN integrations i ON we.integration_id = i.id
                  WHERE we.id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Buscar eventos duplicados
    public function findDuplicates($external_id, $platform, $hours = 24) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE external_id = :external_id 
                  AND platform = :platform 
                  AND received_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
                  ORDER BY received_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":external_id", $external_id);
        $stmt->bindParam(":platform", $platform);
        $stmt->bindParam(":hours", $hours);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Estatísticas por plataforma
    public function getStatsByPlatform($period_days = 30) {
        $query = "SELECT 
                    platform,
                    COUNT(*) as total_events,
                    SUM(CASE WHEN processed = 1 AND processing_error IS NULL THEN 1 ELSE 0 END) as successful_events,
                    SUM(CASE WHEN processed = 1 AND processing_error IS NOT NULL THEN 1 ELSE 0 END) as failed_events,
                    MAX(received_at) as last_event_at
                  FROM " . $this->table_name . " 
                  WHERE received_at >= DATE_SUB(NOW(), INTERVAL :period_days DAY)
                  GROUP BY platform
                  ORDER BY total_events DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":period_days", $period_days);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}