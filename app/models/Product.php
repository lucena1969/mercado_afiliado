<?php
/**
 * Model Product - Gerenciamento de produtos das redes
 */

class Product {
    private $conn;
    private $table_name = "products";

    public $id;
    public $integration_id;
    public $external_id;
    public $name;
    public $category;
    public $price;
    public $currency;
    public $commission_percentage;
    public $status;
    public $metadata_json;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Criar ou atualizar produto
    public function createOrUpdate() {
        // Verificar se produto já existe
        $existing = $this->findByExternalId($this->integration_id, $this->external_id);
        
        if ($existing) {
            return $this->update($existing['id']);
        } else {
            return $this->create();
        }
    }

    // Criar novo produto
    private function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET integration_id=:integration_id, external_id=:external_id, 
                      name=:name, category=:category, price=:price, 
                      currency=:currency, commission_percentage=:commission_percentage,
                      status=:status, metadata_json=:metadata_json";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":integration_id", $this->integration_id);
        $stmt->bindParam(":external_id", $this->external_id);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":currency", $this->currency);
        $stmt->bindParam(":commission_percentage", $this->commission_percentage);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":metadata_json", $this->metadata_json);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    // Atualizar produto existente
    private function update($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET name=:name, category=:category, price=:price,
                      currency=:currency, commission_percentage=:commission_percentage,
                      status=:status, metadata_json=:metadata_json,
                      updated_at=NOW()
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":currency", $this->currency);
        $stmt->bindParam(":commission_percentage", $this->commission_percentage);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":metadata_json", $this->metadata_json);
        $stmt->bindParam(":id", $id);
        
        $this->id = $id;
        return $stmt->execute();
    }

    // Buscar produto por ID externo
    public function findByExternalId($integration_id, $external_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE integration_id = :integration_id AND external_id = :external_id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":integration_id", $integration_id);
        $stmt->bindParam(":external_id", $external_id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Buscar produtos por integração
    public function getByIntegration($integration_id, $status = null) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE integration_id = :integration_id";
        
        if ($status) {
            $query .= " AND status = :status";
        }
        
        $query .= " ORDER BY name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":integration_id", $integration_id);
        
        if ($status) {
            $stmt->bindParam(":status", $status);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Buscar produtos do usuário
    public function getByUser($user_id, $platform = null) {
        $query = "SELECT p.*, i.platform, i.name as integration_name 
                  FROM " . $this->table_name . " p
                  JOIN integrations i ON p.integration_id = i.id
                  WHERE i.user_id = :user_id AND p.status = 'active'";
        
        if ($platform) {
            $query .= " AND i.platform = :platform";
        }
        
        $query .= " ORDER BY p.name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        
        if ($platform) {
            $stmt->bindParam(":platform", $platform);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Buscar por ID
    public function findById($id) {
        $query = "SELECT p.*, i.platform, i.name as integration_name 
                  FROM " . $this->table_name . " p
                  JOIN integrations i ON p.integration_id = i.id
                  WHERE p.id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Estatísticas do produto
    public function getStats($id) {
        $query = "SELECT 
                    COUNT(s.id) as total_sales,
                    SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved_sales,
                    SUM(CASE WHEN s.status = 'approved' THEN s.amount ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN s.status = 'approved' THEN s.commission_amount ELSE 0 END) as total_commission,
                    AVG(CASE WHEN s.status = 'approved' THEN s.amount ELSE NULL END) as avg_ticket,
                    MAX(s.conversion_date) as last_sale_date,
                    MIN(s.conversion_date) as first_sale_date
                  FROM sales s 
                  WHERE s.product_id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Top produtos por usuário
    public function getTopProducts($user_id, $limit = 10, $period_days = 30) {
        $query = "SELECT 
                    p.id,
                    p.name,
                    p.price,
                    i.platform,
                    i.name as integration_name,
                    COUNT(s.id) as sales_count,
                    SUM(CASE WHEN s.status = 'approved' THEN s.amount ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN s.status = 'approved' THEN s.commission_amount ELSE 0 END) as total_commission
                  FROM " . $this->table_name . " p
                  JOIN integrations i ON p.integration_id = i.id
                  LEFT JOIN sales s ON p.id = s.product_id 
                    AND s.conversion_date >= DATE_SUB(NOW(), INTERVAL :period_days DAY)
                  WHERE i.user_id = :user_id AND p.status = 'active'
                  GROUP BY p.id
                  ORDER BY total_revenue DESC, sales_count DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":period_days", $period_days);
        $stmt->bindParam(":limit", $limit);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Atualizar status
    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status, updated_at = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }

    // Buscar produtos com vendas recentes
    public function getProductsWithRecentSales($user_id, $days = 7) {
        $query = "SELECT DISTINCT 
                    p.id,
                    p.name,
                    p.price,
                    i.platform,
                    COUNT(s.id) as recent_sales
                  FROM " . $this->table_name . " p
                  JOIN integrations i ON p.integration_id = i.id
                  JOIN sales s ON p.id = s.product_id
                  WHERE i.user_id = :user_id 
                    AND s.conversion_date >= DATE_SUB(NOW(), INTERVAL :days DAY)
                    AND s.status = 'approved'
                  GROUP BY p.id
                  ORDER BY recent_sales DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":days", $days);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}