<?php
/**
 * Model SyncLog - Gerenciamento de logs de sincronização
 */

class SyncLog {
    private $conn;
    private $table_name = "sync_logs";

    public $id;
    public $integration_id;
    public $sync_type;
    public $operation;
    public $status;
    public $records_processed;
    public $records_created;
    public $records_updated;
    public $records_errors;
    public $error_message;
    public $processing_time_ms;
    public $metadata_json;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Criar novo log
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET integration_id=:integration_id, sync_type=:sync_type,
                      operation=:operation, status=:status,
                      records_processed=:records_processed, records_created=:records_created,
                      records_updated=:records_updated, records_errors=:records_errors,
                      error_message=:error_message, processing_time_ms=:processing_time_ms,
                      metadata_json=:metadata_json";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":integration_id", $this->integration_id);
        $stmt->bindParam(":sync_type", $this->sync_type);
        $stmt->bindParam(":operation", $this->operation);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":records_processed", $this->records_processed);
        $stmt->bindParam(":records_created", $this->records_created);
        $stmt->bindParam(":records_updated", $this->records_updated);
        $stmt->bindParam(":records_errors", $this->records_errors);
        $stmt->bindParam(":error_message", $this->error_message);
        $stmt->bindParam(":processing_time_ms", $this->processing_time_ms);
        $stmt->bindParam(":metadata_json", $this->metadata_json);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    // Buscar logs por integração
    public function getByIntegration($integration_id, $limit = 50) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE integration_id = :integration_id 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":integration_id", $integration_id);
        $stmt->bindParam(":limit", $limit);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Buscar logs do usuário
    public function getByUser($user_id, $limit = 50) {
        $query = "SELECT sl.*, i.platform, i.name as integration_name
                  FROM " . $this->table_name . " sl
                  JOIN integrations i ON sl.integration_id = i.id
                  WHERE i.user_id = :user_id
                  ORDER BY sl.created_at DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":limit", $limit);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Buscar últimos erros
    public function getRecentErrors($user_id = null, $limit = 10) {
        $query = "SELECT sl.*, i.platform, i.name as integration_name
                  FROM " . $this->table_name . " sl
                  JOIN integrations i ON sl.integration_id = i.id
                  WHERE sl.status = 'error'";
        
        if ($user_id) {
            $query .= " AND i.user_id = :user_id";
        }
        
        $query .= " ORDER BY sl.created_at DESC LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        
        if ($user_id) {
            $stmt->bindParam(":user_id", $user_id);
        }
        
        $stmt->bindParam(":limit", $limit);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Estatísticas de sincronização
    public function getSyncStats($integration_id, $period_days = 7) {
        $query = "SELECT 
                    COUNT(*) as total_syncs,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_syncs,
                    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as failed_syncs,
                    SUM(records_processed) as total_records_processed,
                    SUM(records_created) as total_records_created,
                    SUM(records_updated) as total_records_updated,
                    SUM(records_errors) as total_records_errors,
                    AVG(processing_time_ms) as avg_processing_time_ms,
                    MAX(created_at) as last_sync_at
                  FROM " . $this->table_name . "
                  WHERE integration_id = :integration_id
                    AND created_at >= DATE_SUB(NOW(), INTERVAL :period_days DAY)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":integration_id", $integration_id);
        $stmt->bindParam(":period_days", $period_days);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Limpeza de logs antigos
    public function cleanOldLogs($days_to_keep = 30) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE created_at < DATE_SUB(NOW(), INTERVAL :days_to_keep DAY)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":days_to_keep", $days_to_keep);
        
        return $stmt->execute();
    }

    // Buscar por ID
    public function findById($id) {
        $query = "SELECT sl.*, i.platform, i.name as integration_name
                  FROM " . $this->table_name . " sl
                  JOIN integrations i ON sl.integration_id = i.id
                  WHERE sl.id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Helper para criar log de sucesso
    public static function logSuccess($db, $integration_id, $sync_type, $operation, $stats = []) {
        $log = new SyncLog($db);
        $log->integration_id = $integration_id;
        $log->sync_type = $sync_type;
        $log->operation = $operation;
        $log->status = 'success';
        $log->records_processed = $stats['processed'] ?? 0;
        $log->records_created = $stats['created'] ?? 0;
        $log->records_updated = $stats['updated'] ?? 0;
        $log->records_errors = $stats['errors'] ?? 0;
        $log->processing_time_ms = $stats['processing_time_ms'] ?? null;
        $log->metadata_json = isset($stats['metadata']) ? json_encode($stats['metadata']) : null;
        
        return $log->create();
    }

    // Helper para criar log de erro
    public static function logError($db, $integration_id, $sync_type, $operation, $error_message, $stats = []) {
        $log = new SyncLog($db);
        $log->integration_id = $integration_id;
        $log->sync_type = $sync_type;
        $log->operation = $operation;
        $log->status = 'error';
        $log->records_processed = $stats['processed'] ?? 0;
        $log->records_created = $stats['created'] ?? 0;
        $log->records_updated = $stats['updated'] ?? 0;
        $log->records_errors = $stats['errors'] ?? 0;
        $log->error_message = $error_message;
        $log->processing_time_ms = $stats['processing_time_ms'] ?? null;
        $log->metadata_json = isset($stats['metadata']) ? json_encode($stats['metadata']) : null;
        
        return $log->create();
    }
}