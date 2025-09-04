<?php
/**
 * Model UnifiedPanel - Painel Unificado de Métricas
 * Centraliza e agrega dados de todas as redes de afiliação
 */

class UnifiedPanel {
    private $conn;
    private $sale_model;

    public function __construct($db) {
        $this->conn = $db;
        $this->sale_model = new Sale($db);
    }

    /**
     * Dashboard principal - KPIs consolidados
     */
    public function getDashboardKPIs($user_id, $period_days = 30) {
        $query = "SELECT 
                    COUNT(s.id) as total_conversions,
                    SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved_conversions,
                    SUM(CASE WHEN s.status = 'pending' THEN 1 ELSE 0 END) as pending_conversions,
                    SUM(CASE WHEN s.status = 'refunded' THEN 1 ELSE 0 END) as refunded_conversions,
                    SUM(CASE WHEN s.status = 'approved' THEN s.amount ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN s.status = 'approved' THEN s.commission_amount ELSE 0 END) as total_commission,
                    AVG(CASE WHEN s.status = 'approved' THEN s.amount ELSE NULL END) as avg_ticket,
                    ROUND((SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(s.id), 0)), 2) as conversion_rate,
                    COUNT(DISTINCT i.platform) as active_networks
                  FROM sales s
                  JOIN integrations i ON s.integration_id = i.id
                  WHERE i.user_id = :user_id 
                    AND s.conversion_date >= DATE_SUB(NOW(), INTERVAL :period_days DAY)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":period_days", $period_days);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Comparativo de performance entre redes
     */
    public function getNetworkComparison($user_id, $period_days = 30) {
        $query = "SELECT 
                    i.platform,
                    i.name as integration_name,
                    COUNT(s.id) as total_conversions,
                    SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved_conversions,
                    SUM(CASE WHEN s.status = 'approved' THEN s.amount ELSE 0 END) as revenue,
                    SUM(CASE WHEN s.status = 'approved' THEN s.commission_amount ELSE 0 END) as commission,
                    AVG(CASE WHEN s.status = 'approved' THEN s.amount ELSE NULL END) as avg_ticket,
                    ROUND((SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(s.id), 0)), 2) as conversion_rate
                  FROM integrations i
                  LEFT JOIN sales s ON i.id = s.integration_id 
                    AND s.conversion_date >= DATE_SUB(NOW(), INTERVAL :period_days DAY)
                  WHERE i.user_id = :user_id AND i.status = 'active'
                  GROUP BY i.id, i.platform, i.name
                  ORDER BY revenue DESC, commission DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":period_days", $period_days);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Evolução das vendas no tempo (para gráfico de linha)
     */
    public function getRevenueEvolution($user_id, $period_days = 30) {
        $query = "SELECT 
                    DATE(s.conversion_date) as date,
                    SUM(CASE WHEN s.status = 'approved' THEN s.amount ELSE 0 END) as daily_revenue,
                    SUM(CASE WHEN s.status = 'approved' THEN s.commission_amount ELSE 0 END) as daily_commission,
                    COUNT(CASE WHEN s.status = 'approved' THEN 1 END) as daily_conversions
                  FROM sales s
                  JOIN integrations i ON s.integration_id = i.id
                  WHERE i.user_id = :user_id 
                    AND s.conversion_date >= DATE_SUB(NOW(), INTERVAL :period_days DAY)
                  GROUP BY DATE(s.conversion_date)
                  ORDER BY date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":period_days", $period_days);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Top produtos por performance
     */
    public function getTopProducts($user_id, $period_days = 30, $limit = 10) {
        $query = "SELECT 
                    p.name as product_name,
                    i.platform,
                    COUNT(s.id) as total_conversions,
                    SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved_conversions,
                    SUM(CASE WHEN s.status = 'approved' THEN s.amount ELSE 0 END) as revenue,
                    SUM(CASE WHEN s.status = 'approved' THEN s.commission_amount ELSE 0 END) as commission,
                    ROUND((SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(s.id), 0)), 2) as conversion_rate
                  FROM sales s
                  JOIN integrations i ON s.integration_id = i.id
                  LEFT JOIN products p ON s.product_id = p.id
                  WHERE i.user_id = :user_id 
                    AND s.conversion_date >= DATE_SUB(NOW(), INTERVAL :period_days DAY)
                    AND p.name IS NOT NULL
                  GROUP BY s.product_id, p.name, i.platform
                  ORDER BY revenue DESC, commission DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":period_days", $period_days);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Análise de UTMs consolidada
     */
    public function getUTMAnalysis($user_id, $period_days = 30) {
        $utm_types = ['source', 'medium', 'campaign'];
        $results = [];
        
        foreach ($utm_types as $utm_type) {
            $results[$utm_type] = $this->sale_model->getTopUTMs($user_id, $utm_type, $period_days);
        }
        
        return $results;
    }

    /**
     * Status das integrações ativas
     */
    public function getIntegrationStatus($user_id) {
        $query = "SELECT 
                    platform,
                    name,
                    status,
                    last_sync_at,
                    last_error,
                    CASE 
                        WHEN last_sync_at IS NULL THEN 'Nunca sincronizado'
                        WHEN last_sync_at < DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 'Desatualizado'
                        ELSE 'Atualizado'
                    END as sync_status
                  FROM integrations
                  WHERE user_id = :user_id
                  ORDER BY platform ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Resumo de vendas por status
     */
    public function getSalesStatusBreakdown($user_id, $period_days = 30) {
        // Primeiro, pegar o total
        $total_query = "SELECT COUNT(*) as total FROM sales s
                       JOIN integrations i ON s.integration_id = i.id
                       WHERE i.user_id = :user_id 
                       AND s.conversion_date >= DATE_SUB(NOW(), INTERVAL :period_days DAY)";
        
        $total_stmt = $this->conn->prepare($total_query);
        $total_stmt->bindParam(":user_id", $user_id);
        $total_stmt->bindParam(":period_days", $period_days);
        $total_stmt->execute();
        $total_count = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Agora pegar por status
        $query = "SELECT 
                    s.status,
                    COUNT(s.id) as count,
                    SUM(s.amount) as total_amount
                  FROM sales s
                  JOIN integrations i ON s.integration_id = i.id
                  WHERE i.user_id = :user_id 
                    AND s.conversion_date >= DATE_SUB(NOW(), INTERVAL :period_days DAY)
                  GROUP BY s.status
                  ORDER BY count DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":period_days", $period_days);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular percentual
        foreach ($results as &$result) {
            $result['percentage'] = $total_count > 0 ? round(($result['count'] * 100.0) / $total_count, 2) : 0;
        }
        
        return $results;
    }

    /**
     * Métricas de crescimento (comparação com período anterior)
     */
    public function getGrowthMetrics($user_id, $period_days = 30) {
        // Período atual
        $current = $this->getDashboardKPIs($user_id, $period_days);
        
        // Período anterior (mesmo intervalo, mas deslocado)
        $previous = $this->getDashboardKPIsForPeriod($user_id, $period_days * 2, $period_days);
        
        $growth = [];
        $metrics = ['total_revenue', 'total_commission', 'approved_conversions', 'avg_ticket'];
        
        foreach ($metrics as $metric) {
            $current_val = $current[$metric] ?? 0;
            $previous_val = $previous[$metric] ?? 0;
            
            if ($previous_val > 0) {
                $growth[$metric . '_growth'] = round((($current_val - $previous_val) / $previous_val) * 100, 2);
            } else {
                $growth[$metric . '_growth'] = $current_val > 0 ? 100 : 0;
            }
        }
        
        return $growth;
    }

    /**
     * KPIs para um período específico (helper para crescimento)
     */
    private function getDashboardKPIsForPeriod($user_id, $start_days_ago, $end_days_ago) {
        $query = "SELECT 
                    COUNT(s.id) as total_conversions,
                    SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved_conversions,
                    SUM(CASE WHEN s.status = 'approved' THEN s.amount ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN s.status = 'approved' THEN s.commission_amount ELSE 0 END) as total_commission,
                    AVG(CASE WHEN s.status = 'approved' THEN s.amount ELSE NULL END) as avg_ticket
                  FROM sales s
                  JOIN integrations i ON s.integration_id = i.id
                  WHERE i.user_id = :user_id 
                    AND s.conversion_date >= DATE_SUB(NOW(), INTERVAL :start_days DAY)
                    AND s.conversion_date < DATE_SUB(NOW(), INTERVAL :end_days DAY)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":start_days", $start_days_ago);
        $stmt->bindParam(":end_days", $end_days_ago);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}