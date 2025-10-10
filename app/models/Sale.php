<?php
/**
 * Model Sale - Gerenciamento de vendas das redes
 */

class Sale {
    private $conn;
    private $table_name = "sales";

    public $id;
    public $integration_id;
    public $product_id;
    public $external_sale_id;
    public $customer_name;
    public $customer_email;
    public $customer_document;
    public $amount;
    public $commission_amount;
    public $currency;
    public $status;
    public $payment_method;
    public $utm_source;
    public $utm_medium;
    public $utm_campaign;
    public $utm_content;
    public $utm_term;
    public $conversion_date;
    public $approval_date;
    public $refund_date;
    public $metadata_json;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Criar ou atualizar venda
    public function createOrUpdate() {
        // Verificar se venda já existe
        $existing = $this->findByExternalId($this->integration_id, $this->external_sale_id);
        
        if ($existing) {
            return $this->update($existing['id']);
        } else {
            return $this->create();
        }
    }

    // Criar nova venda
    private function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET integration_id=:integration_id, product_id=:product_id,
                      external_sale_id=:external_sale_id, customer_name=:customer_name,
                      customer_email=:customer_email, customer_document=:customer_document,
                      amount=:amount, commission_amount=:commission_amount,
                      currency=:currency, status=:status, payment_method=:payment_method,
                      utm_source=:utm_source, utm_medium=:utm_medium, utm_campaign=:utm_campaign,
                      utm_content=:utm_content, utm_term=:utm_term,
                      conversion_date=:conversion_date, approval_date=:approval_date,
                      refund_date=:refund_date, metadata_json=:metadata_json";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":integration_id", $this->integration_id);
        $stmt->bindParam(":product_id", $this->product_id);
        $stmt->bindParam(":external_sale_id", $this->external_sale_id);
        $stmt->bindParam(":customer_name", $this->customer_name);
        $stmt->bindParam(":customer_email", $this->customer_email);
        $stmt->bindParam(":customer_document", $this->customer_document);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":commission_amount", $this->commission_amount);
        $stmt->bindParam(":currency", $this->currency);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":payment_method", $this->payment_method);
        $stmt->bindParam(":utm_source", $this->utm_source);
        $stmt->bindParam(":utm_medium", $this->utm_medium);
        $stmt->bindParam(":utm_campaign", $this->utm_campaign);
        $stmt->bindParam(":utm_content", $this->utm_content);
        $stmt->bindParam(":utm_term", $this->utm_term);
        $stmt->bindParam(":conversion_date", $this->conversion_date);
        $stmt->bindParam(":approval_date", $this->approval_date);
        $stmt->bindParam(":refund_date", $this->refund_date);
        $stmt->bindParam(":metadata_json", $this->metadata_json);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    // Atualizar venda existente
    private function update($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET customer_name=:customer_name, customer_email=:customer_email,
                      customer_document=:customer_document, amount=:amount,
                      commission_amount=:commission_amount, status=:status,
                      payment_method=:payment_method, approval_date=:approval_date,
                      refund_date=:refund_date, metadata_json=:metadata_json,
                      updated_at=NOW()
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":customer_name", $this->customer_name);
        $stmt->bindParam(":customer_email", $this->customer_email);
        $stmt->bindParam(":customer_document", $this->customer_document);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":commission_amount", $this->commission_amount);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":payment_method", $this->payment_method);
        $stmt->bindParam(":approval_date", $this->approval_date);
        $stmt->bindParam(":refund_date", $this->refund_date);
        $stmt->bindParam(":metadata_json", $this->metadata_json);
        $stmt->bindParam(":id", $id);
        
        $this->id = $id;
        return $stmt->execute();
    }

    // Buscar venda por ID externo
    public function findByExternalId($integration_id, $external_sale_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE integration_id = :integration_id AND external_sale_id = :external_sale_id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":integration_id", $integration_id);
        $stmt->bindParam(":external_sale_id", $external_sale_id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Buscar vendas do usuário
    public function getByUser($user_id, $status = null, $limit = 50, $offset = 0) {
        $query = "SELECT s.*, p.name as product_name, i.platform, i.name as integration_name
                  FROM " . $this->table_name . " s
                  JOIN integrations i ON s.integration_id = i.id
                  LEFT JOIN products p ON s.product_id = p.id
                  WHERE i.user_id = :user_id";
        
        if ($status) {
            $query .= " AND s.status = :status";
        }
        
        $query .= " ORDER BY s.conversion_date DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":limit", $limit);
        $stmt->bindParam(":offset", $offset);
        
        if ($status) {
            $stmt->bindParam(":status", $status);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Estatísticas gerais do usuário
    public function getUserStats($user_id, $period_days = 30) {
        $query = "SELECT 
                    COUNT(s.id) as total_sales,
                    SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved_sales,
                    SUM(CASE WHEN s.status = 'pending' THEN 1 ELSE 0 END) as pending_sales,
                    SUM(CASE WHEN s.status = 'refunded' THEN 1 ELSE 0 END) as refunded_sales,
                    SUM(CASE WHEN s.status = 'approved' THEN s.amount ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN s.status = 'approved' THEN s.commission_amount ELSE 0 END) as total_commission,
                    AVG(CASE WHEN s.status = 'approved' THEN s.amount ELSE NULL END) as avg_ticket,
                    MAX(s.conversion_date) as last_sale_date
                  FROM " . $this->table_name . " s
                  JOIN integrations i ON s.integration_id = i.id
                  WHERE i.user_id = :user_id 
                    AND s.conversion_date >= DATE_SUB(NOW(), INTERVAL :period_days DAY)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":period_days", $period_days);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Vendas por período (para gráficos)
    public function getSalesByPeriod($user_id, $period_days = 30, $group_by = 'day') {
        $date_format = $group_by === 'day' ? '%Y-%m-%d' : '%Y-%m';
        
        $query = "SELECT 
                    DATE_FORMAT(s.conversion_date, :date_format) as period,
                    COUNT(s.id) as total_sales,
                    SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved_sales,
                    SUM(CASE WHEN s.status = 'approved' THEN s.amount ELSE 0 END) as revenue,
                    SUM(CASE WHEN s.status = 'approved' THEN s.commission_amount ELSE 0 END) as commission
                  FROM " . $this->table_name . " s
                  JOIN integrations i ON s.integration_id = i.id
                  WHERE i.user_id = :user_id 
                    AND s.conversion_date >= DATE_SUB(NOW(), INTERVAL :period_days DAY)
                  GROUP BY period
                  ORDER BY period ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":date_format", $date_format);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":period_days", $period_days);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Top UTMs por performance
    public function getTopUTMs($user_id, $utm_type = 'source', $period_days = 30) {
        $utm_field = 'utm_' . $utm_type;
        
        $query = "SELECT 
                    s.$utm_field as utm_value,
                    COUNT(s.id) as total_sales,
                    SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved_sales,
                    SUM(CASE WHEN s.status = 'approved' THEN s.amount ELSE 0 END) as revenue,
                    SUM(CASE WHEN s.status = 'approved' THEN s.commission_amount ELSE 0 END) as commission,
                    ROUND((SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) * 100.0 / COUNT(s.id)), 2) as conversion_rate
                  FROM " . $this->table_name . " s
                  JOIN integrations i ON s.integration_id = i.id
                  WHERE i.user_id = :user_id 
                    AND s.$utm_field IS NOT NULL 
                    AND s.$utm_field != ''
                    AND s.conversion_date >= DATE_SUB(NOW(), INTERVAL :period_days DAY)
                  GROUP BY s.$utm_field
                  ORDER BY revenue DESC, approved_sales DESC
                  LIMIT 10";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":period_days", $period_days);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Vendas por integração
    public function getSalesByIntegration($user_id, $period_days = 30) {
        $query = "SELECT 
                    i.platform,
                    i.name as integration_name,
                    COUNT(s.id) as total_sales,
                    SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved_sales,
                    SUM(CASE WHEN s.status = 'approved' THEN s.amount ELSE 0 END) as revenue,
                    SUM(CASE WHEN s.status = 'approved' THEN s.commission_amount ELSE 0 END) as commission
                  FROM integrations i
                  LEFT JOIN " . $this->table_name . " s ON i.id = s.integration_id 
                    AND s.conversion_date >= DATE_SUB(NOW(), INTERVAL :period_days DAY)
                  WHERE i.user_id = :user_id AND i.status = 'active'
                  GROUP BY i.id
                  ORDER BY revenue DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":period_days", $period_days);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Buscar vendas recentes
    public function getRecentSales($user_id, $limit = 10) {
        $query = "SELECT s.*, p.name as product_name, i.platform
                  FROM " . $this->table_name . " s
                  JOIN integrations i ON s.integration_id = i.id
                  LEFT JOIN products p ON s.product_id = p.id
                  WHERE i.user_id = :user_id
                  ORDER BY s.conversion_date DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":limit", $limit);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}