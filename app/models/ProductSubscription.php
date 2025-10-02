<?php
/**
 * Model ProductSubscription - Gerenciamento de assinaturas de produtos das integrações
 */

class ProductSubscription {
    private $conn;
    private $table_name = "product_subscriptions";

    // Propriedades
    public $id;
    public $integration_id;
    public $product_id;
    public $external_subscription_id;
    public $external_subscriber_code;
    public $external_plan_id;
    public $subscriber_name;
    public $subscriber_email;
    public $subscriber_phone_ddd;
    public $subscriber_phone_number;
    public $subscriber_cell_ddd;
    public $subscriber_cell_number;
    public $plan_name;
    public $status;
    public $actual_recurrence_value;
    public $currency;
    public $cancellation_date;
    public $date_next_charge;
    public $subscription_start_date;
    public $metadata_json;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Criar ou atualizar assinatura
    public function createOrUpdate() {
        // Verificar se já existe
        $existing = $this->findByExternalId($this->integration_id, $this->external_subscription_id);
        
        if ($existing) {
            $this->id = $existing['id'];
            return $this->update();
        } else {
            return $this->create();
        }
    }

    // Criar nova assinatura
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET integration_id=:integration_id, product_id=:product_id,
                      external_subscription_id=:external_subscription_id,
                      external_subscriber_code=:external_subscriber_code,
                      external_plan_id=:external_plan_id,
                      subscriber_name=:subscriber_name, subscriber_email=:subscriber_email,
                      subscriber_phone_ddd=:subscriber_phone_ddd,
                      subscriber_phone_number=:subscriber_phone_number,
                      subscriber_cell_ddd=:subscriber_cell_ddd,
                      subscriber_cell_number=:subscriber_cell_number,
                      plan_name=:plan_name, status=:status,
                      actual_recurrence_value=:actual_recurrence_value,
                      currency=:currency, cancellation_date=:cancellation_date,
                      date_next_charge=:date_next_charge,
                      subscription_start_date=:subscription_start_date,
                      metadata_json=:metadata_json";

        $stmt = $this->conn->prepare($query);

        $this->bindParameters($stmt);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    // Atualizar assinatura existente
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET product_id=:product_id, external_subscriber_code=:external_subscriber_code,
                      external_plan_id=:external_plan_id,
                      subscriber_name=:subscriber_name, subscriber_email=:subscriber_email,
                      subscriber_phone_ddd=:subscriber_phone_ddd,
                      subscriber_phone_number=:subscriber_phone_number,
                      subscriber_cell_ddd=:subscriber_cell_ddd,
                      subscriber_cell_number=:subscriber_cell_number,
                      plan_name=:plan_name, status=:status,
                      actual_recurrence_value=:actual_recurrence_value,
                      currency=:currency, cancellation_date=:cancellation_date,
                      date_next_charge=:date_next_charge,
                      subscription_start_date=:subscription_start_date,
                      metadata_json=:metadata_json,
                      updated_at=NOW()
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $this->bindParameters($stmt);

        return $stmt->execute();
    }

    // Bind de parâmetros (reutilizado em create e update)
    private function bindParameters($stmt) {
        $stmt->bindParam(":integration_id", $this->integration_id);
        $stmt->bindParam(":product_id", $this->product_id);
        $stmt->bindParam(":external_subscription_id", $this->external_subscription_id);
        $stmt->bindParam(":external_subscriber_code", $this->external_subscriber_code);
        $stmt->bindParam(":external_plan_id", $this->external_plan_id);
        $stmt->bindParam(":subscriber_name", $this->subscriber_name);
        $stmt->bindParam(":subscriber_email", $this->subscriber_email);
        $stmt->bindParam(":subscriber_phone_ddd", $this->subscriber_phone_ddd);
        $stmt->bindParam(":subscriber_phone_number", $this->subscriber_phone_number);
        $stmt->bindParam(":subscriber_cell_ddd", $this->subscriber_cell_ddd);
        $stmt->bindParam(":subscriber_cell_number", $this->subscriber_cell_number);
        $stmt->bindParam(":plan_name", $this->plan_name);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":actual_recurrence_value", $this->actual_recurrence_value);
        $stmt->bindParam(":currency", $this->currency);
        $stmt->bindParam(":cancellation_date", $this->cancellation_date);
        $stmt->bindParam(":date_next_charge", $this->date_next_charge);
        $stmt->bindParam(":subscription_start_date", $this->subscription_start_date);
        $stmt->bindParam(":metadata_json", $this->metadata_json);
    }

    // Buscar assinatura por ID externo
    public function findByExternalId($integration_id, $external_subscription_id) {
        $query = "SELECT ps.*, p.name as product_name, i.name as integration_name
                  FROM " . $this->table_name . " ps
                  LEFT JOIN products p ON ps.product_id = p.id
                  LEFT JOIN integrations i ON ps.integration_id = i.id
                  WHERE ps.integration_id = :integration_id 
                  AND ps.external_subscription_id = :external_subscription_id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":integration_id", $integration_id);
        $stmt->bindParam(":external_subscription_id", $external_subscription_id);
        $stmt->execute();

        return $stmt->fetch();
    }

    // Listar assinaturas com filtros
    public function getSubscriptions($integration_id = null, $status = null, $limit = 50, $offset = 0) {
        $where_conditions = [];
        $params = [];

        if ($integration_id) {
            $where_conditions[] = "ps.integration_id = :integration_id";
            $params[':integration_id'] = $integration_id;
        }

        if ($status) {
            $where_conditions[] = "ps.status = :status";
            $params[':status'] = $status;
        }

        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

        $query = "SELECT ps.*, p.name as product_name, i.name as integration_name, i.platform
                  FROM " . $this->table_name . " ps
                  LEFT JOIN products p ON ps.product_id = p.id
                  LEFT JOIN integrations i ON ps.integration_id = i.id
                  {$where_clause}
                  ORDER BY ps.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);

        // Bind dos parâmetros de filtro
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // Contar total de assinaturas (para paginação)
    public function countSubscriptions($integration_id = null, $status = null) {
        $where_conditions = [];
        $params = [];

        if ($integration_id) {
            $where_conditions[] = "ps.integration_id = :integration_id";
            $params[':integration_id'] = $integration_id;
        }

        if ($status) {
            $where_conditions[] = "ps.status = :status";
            $params[':status'] = $status;
        }

        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

        $query = "SELECT COUNT(*) as total
                  FROM " . $this->table_name . " ps
                  {$where_clause}";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetch();

        return $result['total'];
    }

    // Métricas para KPIs
    public function getMetrics($integration_id = null) {
        $where_condition = $integration_id ? "WHERE ps.integration_id = :integration_id" : "";

        $query = "SELECT 
                    COUNT(CASE WHEN ps.status = 'active' THEN 1 END) as active_count,
                    COUNT(CASE WHEN ps.status = 'cancelled' THEN 1 END) as cancelled_count,
                    SUM(CASE WHEN ps.status = 'active' THEN ps.actual_recurrence_value END) as mrr_total,
                    AVG(CASE WHEN ps.status = 'active' THEN ps.actual_recurrence_value END) as avg_subscription_value,
                    COUNT(CASE WHEN ps.status = 'active' AND ps.date_next_charge <= DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 1 END) as renewals_next_week
                  FROM " . $this->table_name . " ps
                  {$where_condition}";

        $stmt = $this->conn->prepare($query);
        
        if ($integration_id) {
            $stmt->bindParam(":integration_id", $integration_id);
        }
        
        $stmt->execute();
        return $stmt->fetch();
    }

    // Cancelar assinatura
    public function cancel($cancellation_date = null) {
        if (!$cancellation_date) {
            $cancellation_date = date('Y-m-d H:i:s');
        }

        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'cancelled', 
                      cancellation_date = :cancellation_date,
                      updated_at = NOW()
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":cancellation_date", $cancellation_date);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Registrar evento da assinatura
    public function logEvent($event_type, $previous_status, $new_status, $details = null) {
        $query = "INSERT INTO subscription_events 
                  SET subscription_id=:subscription_id, event_type=:event_type,
                      event_date=NOW(), previous_status=:previous_status,
                      new_status=:new_status, details_json=:details_json";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":subscription_id", $this->id);
        $stmt->bindParam(":event_type", $event_type);
        $stmt->bindParam(":previous_status", $previous_status);
        $stmt->bindParam(":new_status", $new_status);
        $stmt->bindParam(":details_json", $details ? json_encode($details) : null);

        return $stmt->execute();
    }
}