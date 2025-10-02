<?php
/**
 * Model UtmTemplate - Gerenciamento de templates de UTM
 */

class UtmTemplate {
    private $conn;
    private $table_name = "utm_templates";

    public $id;
    public $user_id;
    public $name;
    public $platform;
    public $description;
    public $utm_source;
    public $utm_medium;
    public $utm_campaign;
    public $utm_content;
    public $utm_term;
    public $is_default;
    public $status;
    public $usage_count;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Criar novo template
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 (user_id, name, platform, description, utm_source, utm_medium, 
                  utm_campaign, utm_content, utm_term, is_default, status) 
                 VALUES 
                 (:user_id, :name, :platform, :description, :utm_source, :utm_medium, 
                  :utm_campaign, :utm_content, :utm_term, :is_default, :status)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->platform = htmlspecialchars(strip_tags($this->platform));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->utm_source = htmlspecialchars(strip_tags($this->utm_source));
        $this->utm_medium = htmlspecialchars(strip_tags($this->utm_medium));
        $this->utm_campaign = htmlspecialchars(strip_tags($this->utm_campaign));
        $this->utm_content = htmlspecialchars(strip_tags($this->utm_content));
        $this->utm_term = htmlspecialchars(strip_tags($this->utm_term));

        // Bind dos parâmetros
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':platform', $this->platform);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':utm_source', $this->utm_source);
        $stmt->bindParam(':utm_medium', $this->utm_medium);
        $stmt->bindParam(':utm_campaign', $this->utm_campaign);
        $stmt->bindParam(':utm_content', $this->utm_content);
        $stmt->bindParam(':utm_term', $this->utm_term);
        $stmt->bindParam(':is_default', $this->is_default);
        $stmt->bindParam(':status', $this->status);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    // Buscar templates por usuário
    public function findByUser($user_id, $limit = 50) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE user_id = :user_id AND status = 'active' 
                 ORDER BY usage_count DESC, created_at DESC 
                 LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar templates por plataforma
    public function findByPlatform($user_id, $platform) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE user_id = :user_id AND platform = :platform AND status = 'active' 
                 ORDER BY usage_count DESC, created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':platform', $platform);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar template por ID
    public function findById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Atualizar template
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                 SET name = :name, platform = :platform, description = :description,
                     utm_source = :utm_source, utm_medium = :utm_medium, 
                     utm_campaign = :utm_campaign, utm_content = :utm_content, 
                     utm_term = :utm_term, status = :status,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->platform = htmlspecialchars(strip_tags($this->platform));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->utm_source = htmlspecialchars(strip_tags($this->utm_source));
        $this->utm_medium = htmlspecialchars(strip_tags($this->utm_medium));
        $this->utm_campaign = htmlspecialchars(strip_tags($this->utm_campaign));
        $this->utm_content = htmlspecialchars(strip_tags($this->utm_content));
        $this->utm_term = htmlspecialchars(strip_tags($this->utm_term));

        // Bind dos parâmetros
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':platform', $this->platform);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':utm_source', $this->utm_source);
        $stmt->bindParam(':utm_medium', $this->utm_medium);
        $stmt->bindParam(':utm_campaign', $this->utm_campaign);
        $stmt->bindParam(':utm_content', $this->utm_content);
        $stmt->bindParam(':utm_term', $this->utm_term);
        $stmt->bindParam(':status', $this->status);

        return $stmt->execute();
    }

    // Deletar template (soft delete)
    public function delete() {
        $query = "UPDATE " . $this->table_name . " 
                 SET status = 'inactive', updated_at = CURRENT_TIMESTAMP 
                 WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Incrementar contador de uso
    public function incrementUsage($id) {
        $query = "UPDATE " . $this->table_name . " 
                 SET usage_count = usage_count + 1, updated_at = CURRENT_TIMESTAMP 
                 WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Aplicar template a um link (substituir placeholders)
    public function applyTemplate($template, $variables = []) {
        $utm_params = [
            'utm_source' => $this->replacePlaceholders($template['utm_source'], $variables),
            'utm_medium' => $this->replacePlaceholders($template['utm_medium'], $variables),
            'utm_campaign' => $this->replacePlaceholders($template['utm_campaign'], $variables),
            'utm_content' => $this->replacePlaceholders($template['utm_content'], $variables),
            'utm_term' => $this->replacePlaceholders($template['utm_term'], $variables)
        ];

        // Remover parâmetros vazios
        return array_filter($utm_params, function($value) {
            return !empty($value);
        });
    }

    // Substituir placeholders como {campaign_name}, {ad_name}
    private function replacePlaceholders($text, $variables) {
        if (empty($text)) return $text;

        foreach ($variables as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }

        return $text;
    }

    // Estatísticas do usuário
    public function getUserStats($user_id) {
        $query = "SELECT 
                    COUNT(*) as total_templates,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_templates,
                    SUM(usage_count) as total_usage,
                    platform,
                    COUNT(*) as templates_by_platform
                 FROM " . $this->table_name . " 
                 WHERE user_id = :user_id 
                 GROUP BY platform";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>