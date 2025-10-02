<?php
/**
 * Model ShortLink - Gerenciamento de links encurtados
 */

class ShortLink {
    private $conn;
    private $table_name = "short_links";

    public $id;
    public $user_id;
    public $utm_template_id;
    public $short_code;
    public $original_url;
    public $final_url;
    public $title;
    public $description;
    public $campaign_name;
    public $ad_name;
    public $creative_name;
    public $utm_source;
    public $utm_medium;
    public $utm_campaign;
    public $utm_content;
    public $utm_term;
    public $status;
    public $expires_at;
    public $click_count;
    public $last_clicked_at;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Criar novo link encurtado
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 (user_id, utm_template_id, short_code, original_url, final_url, title, description,
                  campaign_name, ad_name, creative_name, utm_source, utm_medium, utm_campaign, 
                  utm_content, utm_term, status, expires_at) 
                 VALUES 
                 (:user_id, :utm_template_id, :short_code, :original_url, :final_url, :title, :description,
                  :campaign_name, :ad_name, :creative_name, :utm_source, :utm_medium, :utm_campaign,
                  :utm_content, :utm_term, :status, :expires_at)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->original_url = filter_var($this->original_url, FILTER_SANITIZE_URL);
        $this->final_url = filter_var($this->final_url, FILTER_SANITIZE_URL);
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->campaign_name = htmlspecialchars(strip_tags($this->campaign_name));
        $this->ad_name = htmlspecialchars(strip_tags($this->ad_name));
        $this->creative_name = htmlspecialchars(strip_tags($this->creative_name));

        // Bind dos parâmetros
        $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
        $stmt->bindParam(':utm_template_id', $this->utm_template_id, PDO::PARAM_INT);
        $stmt->bindParam(':short_code', $this->short_code);
        $stmt->bindParam(':original_url', $this->original_url);
        $stmt->bindParam(':final_url', $this->final_url);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':campaign_name', $this->campaign_name);
        $stmt->bindParam(':ad_name', $this->ad_name);
        $stmt->bindParam(':creative_name', $this->creative_name);
        $stmt->bindParam(':utm_source', $this->utm_source);
        $stmt->bindParam(':utm_medium', $this->utm_medium);
        $stmt->bindParam(':utm_campaign', $this->utm_campaign);
        $stmt->bindParam(':utm_content', $this->utm_content);
        $stmt->bindParam(':utm_term', $this->utm_term);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':expires_at', $this->expires_at);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    // Gerar código único para o link encurtado
    public function generateShortCode($length = 8) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $max_attempts = 10;
        $attempt = 0;

        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }

            // Verificar se código já existe
            $exists = $this->findByShortCode($code);
            $attempt++;
            
        } while ($exists && $attempt < $max_attempts);

        if ($attempt >= $max_attempts) {
            throw new Exception('Não foi possível gerar um código único após ' . $max_attempts . ' tentativas');
        }

        return $code;
    }

    // Buscar link por código encurtado
    public function findByShortCode($short_code) {
        $query = "SELECT sl.*, ut.name as template_name 
                 FROM " . $this->table_name . " sl
                 LEFT JOIN utm_templates ut ON sl.utm_template_id = ut.id 
                 WHERE sl.short_code = :short_code AND sl.status = 'active' 
                 LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':short_code', $short_code);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Buscar links por usuário
    public function findByUser($user_id, $limit = 50, $offset = 0) {
        $query = "SELECT sl.*, ut.name as template_name,
                         (SELECT COUNT(*) FROM link_clicks WHERE short_link_id = sl.id) as total_clicks
                 FROM " . $this->table_name . " sl
                 LEFT JOIN utm_templates ut ON sl.utm_template_id = ut.id 
                 WHERE sl.user_id = :user_id AND sl.status IN ('active', 'expired')
                 ORDER BY sl.created_at DESC 
                 LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar link por ID
    public function findById($id) {
        $query = "SELECT sl.*, ut.name as template_name,
                         (SELECT COUNT(*) FROM link_clicks WHERE short_link_id = sl.id) as total_clicks
                 FROM " . $this->table_name . " sl
                 LEFT JOIN utm_templates ut ON sl.utm_template_id = ut.id 
                 WHERE sl.id = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Atualizar link
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                 SET title = :title, description = :description, campaign_name = :campaign_name,
                     ad_name = :ad_name, creative_name = :creative_name, status = :status,
                     expires_at = :expires_at, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->campaign_name = htmlspecialchars(strip_tags($this->campaign_name));
        $this->ad_name = htmlspecialchars(strip_tags($this->ad_name));
        $this->creative_name = htmlspecialchars(strip_tags($this->creative_name));

        // Bind dos parâmetros
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':campaign_name', $this->campaign_name);
        $stmt->bindParam(':ad_name', $this->ad_name);
        $stmt->bindParam(':creative_name', $this->creative_name);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':expires_at', $this->expires_at);

        return $stmt->execute();
    }

    // Deletar link (soft delete)
    public function delete() {
        $query = "UPDATE " . $this->table_name . " 
                 SET status = 'inactive', updated_at = CURRENT_TIMESTAMP 
                 WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Incrementar contador de cliques (usando trigger, mas backup manual)
    public function incrementClicks($timestamp = null) {
        if (!$timestamp) {
            $timestamp = date('Y-m-d H:i:s');
        }

        $query = "UPDATE " . $this->table_name . " 
                 SET click_count = click_count + 1, last_clicked_at = :timestamp, 
                     updated_at = CURRENT_TIMESTAMP 
                 WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->bindParam(':timestamp', $timestamp);

        return $stmt->execute();
    }

    // Construir URL final com parâmetros UTM
    public function buildFinalUrl($original_url, $utm_params) {
        // Parse da URL original
        $parsed_url = parse_url($original_url);
        
        if (!$parsed_url) {
            throw new Exception('URL inválida: ' . $original_url);
        }

        // Construir query string existente
        $query_params = [];
        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query_params);
        }

        // Adicionar parâmetros UTM (sobrescrever se já existirem)
        foreach ($utm_params as $key => $value) {
            if (!empty($value)) {
                $query_params[$key] = $value;
            }
        }

        // Reconstruir URL
        $final_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
        
        if (isset($parsed_url['port'])) {
            $final_url .= ':' . $parsed_url['port'];
        }
        
        if (isset($parsed_url['path'])) {
            $final_url .= $parsed_url['path'];
        }
        
        if (!empty($query_params)) {
            $final_url .= '?' . http_build_query($query_params);
        }
        
        if (isset($parsed_url['fragment'])) {
            $final_url .= '#' . $parsed_url['fragment'];
        }

        return $final_url;
    }

    // Verificar se link expirou
    public function isExpired() {
        if (!$this->expires_at) {
            return false;
        }

        return strtotime($this->expires_at) < time();
    }

    // Estatísticas por usuário
    public function getUserStats($user_id) {
        $query = "SELECT 
                    COUNT(*) as total_links,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_links,
                    COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired_links,
                    SUM(click_count) as total_clicks,
                    AVG(click_count) as avg_clicks_per_link,
                    MAX(click_count) as max_clicks,
                    campaign_name,
                    COUNT(*) as links_by_campaign
                 FROM " . $this->table_name . " 
                 WHERE user_id = :user_id 
                 GROUP BY campaign_name";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Links mais clicados
    public function getTopLinks($user_id, $limit = 10) {
        $query = "SELECT *, 
                         (SELECT COUNT(*) FROM link_clicks WHERE short_link_id = sl.id) as total_clicks
                 FROM " . $this->table_name . " sl
                 WHERE user_id = :user_id AND status = 'active'
                 ORDER BY click_count DESC, created_at DESC 
                 LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>