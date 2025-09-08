<?php
/**
 * Model LinkClick - Registro e análise de cliques nos links
 */

class LinkClick {
    private $conn;
    private $table_name = "link_clicks";

    public $id;
    public $short_link_id;
    public $user_id;
    public $ip_address;
    public $user_agent;
    public $referer;
    public $country;
    public $region;
    public $city;
    public $device_type;
    public $browser;
    public $os;
    public $utm_source;
    public $utm_medium;
    public $utm_campaign;
    public $utm_content;
    public $utm_term;
    public $click_timestamp;
    public $session_id;
    public $is_unique;
    public $conversion_tracked;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Registrar novo clique
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 (short_link_id, user_id, ip_address, user_agent, referer, country, region, city,
                  device_type, browser, os, utm_source, utm_medium, utm_campaign, utm_content, 
                  utm_term, click_timestamp, session_id, is_unique) 
                 VALUES 
                 (:short_link_id, :user_id, :ip_address, :user_agent, :referer, :country, :region, :city,
                  :device_type, :browser, :os, :utm_source, :utm_medium, :utm_campaign, :utm_content,
                  :utm_term, :click_timestamp, :session_id, :is_unique)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados sensíveis
        $this->user_agent = htmlspecialchars(strip_tags($this->user_agent));
        $this->referer = filter_var($this->referer, FILTER_SANITIZE_URL);

        // Bind dos parâmetros
        $stmt->bindParam(':short_link_id', $this->short_link_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
        $stmt->bindParam(':ip_address', $this->ip_address);
        $stmt->bindParam(':user_agent', $this->user_agent);
        $stmt->bindParam(':referer', $this->referer);
        $stmt->bindParam(':country', $this->country);
        $stmt->bindParam(':region', $this->region);
        $stmt->bindParam(':city', $this->city);
        $stmt->bindParam(':device_type', $this->device_type);
        $stmt->bindParam(':browser', $this->browser);
        $stmt->bindParam(':os', $this->os);
        $stmt->bindParam(':utm_source', $this->utm_source);
        $stmt->bindParam(':utm_medium', $this->utm_medium);
        $stmt->bindParam(':utm_campaign', $this->utm_campaign);
        $stmt->bindParam(':utm_content', $this->utm_content);
        $stmt->bindParam(':utm_term', $this->utm_term);
        $stmt->bindParam(':click_timestamp', $this->click_timestamp);
        $stmt->bindParam(':session_id', $this->session_id);
        $stmt->bindParam(':is_unique', $this->is_unique, PDO::PARAM_BOOL);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    // Detectar informações do dispositivo a partir do User Agent
    public function parseUserAgent($user_agent) {
        $device_info = [
            'device_type' => 'other',
            'browser' => 'Unknown',
            'os' => 'Unknown'
        ];

        if (empty($user_agent)) {
            return $device_info;
        }

        // Detectar tipo de dispositivo
        if (preg_match('/Mobile|Android|iPhone|iPad/i', $user_agent)) {
            if (preg_match('/iPad/i', $user_agent)) {
                $device_info['device_type'] = 'tablet';
            } else {
                $device_info['device_type'] = 'mobile';
            }
        } else {
            $device_info['device_type'] = 'desktop';
        }

        // Detectar navegador
        if (preg_match('/Chrome/i', $user_agent)) {
            $device_info['browser'] = 'Chrome';
        } elseif (preg_match('/Firefox/i', $user_agent)) {
            $device_info['browser'] = 'Firefox';
        } elseif (preg_match('/Safari/i', $user_agent)) {
            $device_info['browser'] = 'Safari';
        } elseif (preg_match('/Edge/i', $user_agent)) {
            $device_info['browser'] = 'Edge';
        } elseif (preg_match('/Opera/i', $user_agent)) {
            $device_info['browser'] = 'Opera';
        }

        // Detectar sistema operacional
        if (preg_match('/Windows/i', $user_agent)) {
            $device_info['os'] = 'Windows';
        } elseif (preg_match('/Mac OS|macOS/i', $user_agent)) {
            $device_info['os'] = 'macOS';
        } elseif (preg_match('/Linux/i', $user_agent)) {
            $device_info['os'] = 'Linux';
        } elseif (preg_match('/Android/i', $user_agent)) {
            $device_info['os'] = 'Android';
        } elseif (preg_match('/iOS|iPhone|iPad/i', $user_agent)) {
            $device_info['os'] = 'iOS';
        }

        return $device_info;
    }

    // Verificar se é clique único (mesmo IP/sessão nas últimas 24h)
    public function isUniqueClick($short_link_id, $ip_address, $session_id = null) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                 WHERE short_link_id = :short_link_id AND ip_address = :ip_address 
                 AND click_timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)";

        if ($session_id) {
            $query .= " AND session_id = :session_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':short_link_id', $short_link_id, PDO::PARAM_INT);
        $stmt->bindParam(':ip_address', $ip_address);
        
        if ($session_id) {
            $stmt->bindParam(':session_id', $session_id);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['count'] == 0;
    }

    // Buscar cliques por link
    public function findByLink($short_link_id, $limit = 100, $offset = 0) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE short_link_id = :short_link_id 
                 ORDER BY click_timestamp DESC 
                 LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':short_link_id', $short_link_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Estatísticas por link
    public function getLinkStats($short_link_id) {
        $query = "SELECT 
                    COUNT(*) as total_clicks,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    COUNT(CASE WHEN is_unique = 1 THEN 1 END) as unique_clicks,
                    device_type,
                    COUNT(*) as clicks_by_device,
                    browser,
                    COUNT(*) as clicks_by_browser,
                    country,
                    COUNT(*) as clicks_by_country,
                    DATE(click_timestamp) as click_date,
                    COUNT(*) as clicks_by_date
                 FROM " . $this->table_name . " 
                 WHERE short_link_id = :short_link_id 
                 GROUP BY device_type, browser, country, click_date
                 ORDER BY click_timestamp DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':short_link_id', $short_link_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Relatório de cliques por período
    public function getClicksByPeriod($user_id, $start_date, $end_date) {
        $query = "SELECT 
                    DATE(lc.click_timestamp) as date,
                    COUNT(*) as total_clicks,
                    COUNT(CASE WHEN lc.is_unique = 1 THEN 1 END) as unique_clicks,
                    lc.utm_campaign,
                    lc.utm_source,
                    lc.utm_medium,
                    sl.title as link_title
                 FROM " . $this->table_name . " lc
                 JOIN short_links sl ON lc.short_link_id = sl.id 
                 WHERE lc.user_id = :user_id 
                 AND DATE(lc.click_timestamp) BETWEEN :start_date AND :end_date
                 GROUP BY DATE(lc.click_timestamp), lc.utm_campaign, lc.utm_source, lc.utm_medium, sl.title
                 ORDER BY lc.click_timestamp DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Top campanhas por cliques
    public function getTopCampaigns($user_id, $limit = 10) {
        $query = "SELECT 
                    utm_campaign,
                    COUNT(*) as total_clicks,
                    COUNT(DISTINCT ip_address) as unique_visitors,
                    COUNT(DISTINCT short_link_id) as unique_links,
                    MAX(click_timestamp) as last_click
                 FROM " . $this->table_name . " 
                 WHERE user_id = :user_id AND utm_campaign IS NOT NULL
                 GROUP BY utm_campaign
                 ORDER BY total_clicks DESC 
                 LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Análise geográfica
    public function getGeographicAnalysis($user_id) {
        $query = "SELECT 
                    country,
                    region,
                    city,
                    COUNT(*) as clicks,
                    COUNT(DISTINCT ip_address) as unique_visitors,
                    COUNT(CASE WHEN is_unique = 1 THEN 1 END) as unique_clicks
                 FROM " . $this->table_name . " 
                 WHERE user_id = :user_id AND country IS NOT NULL
                 GROUP BY country, region, city
                 ORDER BY clicks DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Análise de dispositivos
    public function getDeviceAnalysis($user_id) {
        $query = "SELECT 
                    device_type,
                    browser,
                    os,
                    COUNT(*) as clicks,
                    COUNT(DISTINCT ip_address) as unique_visitors,
                    ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage
                 FROM " . $this->table_name . " 
                 WHERE user_id = :user_id 
                 GROUP BY device_type, browser, os
                 ORDER BY clicks DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Horários de pico
    public function getPeakHours($user_id) {
        $query = "SELECT 
                    HOUR(click_timestamp) as hour,
                    COUNT(*) as clicks,
                    DAYOFWEEK(click_timestamp) as day_of_week
                 FROM " . $this->table_name . " 
                 WHERE user_id = :user_id 
                 GROUP BY HOUR(click_timestamp), DAYOFWEEK(click_timestamp)
                 ORDER BY clicks DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Marcar conversão (para integração com vendas)
    public function markConversion($id) {
        $query = "UPDATE " . $this->table_name . " 
                 SET conversion_tracked = 1 
                 WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Obter IP do visitante (com proxy support)
    public static function getVisitorIP() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = trim($_SERVER[$key]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
?>