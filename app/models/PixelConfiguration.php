<?php
/**
 * Model: PixelConfiguration
 * Gerencia configurações do Pixel BR por usuário
 */

class PixelConfiguration {
    private $conn;
    private $table_name = "pixel_configurations";

    public $id;
    public $user_id;
    public $integration_id;
    public $pixel_name;
    public $pixel_hash;
    public $facebook_pixel_id;
    public $facebook_access_token;
    public $facebook_test_event_code;
    public $google_conversion_id;
    public $google_conversion_label;
    public $google_developer_token;
    public $google_refresh_token;
    public $tiktok_pixel_code;
    public $tiktok_access_token;
    public $tiktok_advertiser_id;
    public $auto_track_pageviews;
    public $auto_track_clicks;
    public $consent_mode;
    public $data_retention_days;
    public $custom_domain;
    public $custom_script_url;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct($database) {
        $this->conn = $database;
    }

    public function create() {
        // Gerar hash único para o pixel
        $this->pixel_hash = bin2hex(random_bytes(16)); // 32 caracteres hexadecimais

        $query = "INSERT INTO " . $this->table_name . "
                  SET user_id=:user_id,
                      integration_id=:integration_id,
                      pixel_name=:pixel_name,
                      pixel_hash=:pixel_hash,
                      facebook_pixel_id=:facebook_pixel_id,
                      facebook_access_token=:facebook_access_token,
                      facebook_test_event_code=:facebook_test_event_code,
                      google_conversion_id=:google_conversion_id,
                      google_conversion_label=:google_conversion_label,
                      google_developer_token=:google_developer_token,
                      google_refresh_token=:google_refresh_token,
                      tiktok_pixel_code=:tiktok_pixel_code,
                      tiktok_access_token=:tiktok_access_token,
                      tiktok_advertiser_id=:tiktok_advertiser_id,
                      auto_track_pageviews=:auto_track_pageviews,
                      auto_track_clicks=:auto_track_clicks,
                      consent_mode=:consent_mode,
                      data_retention_days=:data_retention_days,
                      custom_domain=:custom_domain,
                      custom_script_url=:custom_script_url,
                      status=:status";

        $stmt = $this->conn->prepare($query);

        $this->pixel_name = htmlspecialchars(strip_tags($this->pixel_name));
        $this->facebook_pixel_id = htmlspecialchars(strip_tags($this->facebook_pixel_id ?? ''));
        $this->status = $this->status ?? 'inactive';
        $this->auto_track_pageviews = $this->auto_track_pageviews ?? true;
        $this->auto_track_clicks = $this->auto_track_clicks ?? false;
        $this->consent_mode = $this->consent_mode ?? 'required';
        $this->data_retention_days = $this->data_retention_days ?? 365;

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":integration_id", $this->integration_id);
        $stmt->bindParam(":pixel_name", $this->pixel_name);
        $stmt->bindParam(":pixel_hash", $this->pixel_hash);
        $stmt->bindParam(":facebook_pixel_id", $this->facebook_pixel_id);
        $stmt->bindParam(":facebook_access_token", $this->facebook_access_token);
        $stmt->bindParam(":facebook_test_event_code", $this->facebook_test_event_code);
        $stmt->bindParam(":google_conversion_id", $this->google_conversion_id);
        $stmt->bindParam(":google_conversion_label", $this->google_conversion_label);
        $stmt->bindParam(":google_developer_token", $this->google_developer_token);
        $stmt->bindParam(":google_refresh_token", $this->google_refresh_token);
        $stmt->bindParam(":tiktok_pixel_code", $this->tiktok_pixel_code);
        $stmt->bindParam(":tiktok_access_token", $this->tiktok_access_token);
        $stmt->bindParam(":tiktok_advertiser_id", $this->tiktok_advertiser_id);
        $stmt->bindParam(":auto_track_pageviews", $this->auto_track_pageviews);
        $stmt->bindParam(":auto_track_clicks", $this->auto_track_clicks);
        $stmt->bindParam(":consent_mode", $this->consent_mode);
        $stmt->bindParam(":data_retention_days", $this->data_retention_days);
        $stmt->bindParam(":custom_domain", $this->custom_domain);
        $stmt->bindParam(":custom_script_url", $this->custom_script_url);
        $stmt->bindParam(":status", $this->status);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->user_id = $row['user_id'];
            $this->integration_id = $row['integration_id'];
            $this->pixel_name = $row['pixel_name'];
            $this->pixel_hash = $row['pixel_hash'];
            $this->facebook_pixel_id = $row['facebook_pixel_id'];
            $this->facebook_access_token = $row['facebook_access_token'];
            $this->facebook_test_event_code = $row['facebook_test_event_code'];
            $this->google_conversion_id = $row['google_conversion_id'];
            $this->google_conversion_label = $row['google_conversion_label'];
            $this->google_developer_token = $row['google_developer_token'];
            $this->google_refresh_token = $row['google_refresh_token'];
            $this->tiktok_pixel_code = $row['tiktok_pixel_code'];
            $this->tiktok_access_token = $row['tiktok_access_token'];
            $this->tiktok_advertiser_id = $row['tiktok_advertiser_id'];
            $this->auto_track_pageviews = $row['auto_track_pageviews'];
            $this->auto_track_clicks = $row['auto_track_clicks'];
            $this->consent_mode = $row['consent_mode'];
            $this->data_retention_days = $row['data_retention_days'];
            $this->custom_domain = $row['custom_domain'];
            $this->custom_script_url = $row['custom_script_url'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }

        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET pixel_name=:pixel_name,
                      integration_id=:integration_id,
                      facebook_pixel_id=:facebook_pixel_id,
                      facebook_access_token=:facebook_access_token,
                      facebook_test_event_code=:facebook_test_event_code,
                      google_conversion_id=:google_conversion_id,
                      google_conversion_label=:google_conversion_label,
                      google_developer_token=:google_developer_token,
                      google_refresh_token=:google_refresh_token,
                      tiktok_pixel_code=:tiktok_pixel_code,
                      tiktok_access_token=:tiktok_access_token,
                      tiktok_advertiser_id=:tiktok_advertiser_id,
                      auto_track_pageviews=:auto_track_pageviews,
                      auto_track_clicks=:auto_track_clicks,
                      consent_mode=:consent_mode,
                      data_retention_days=:data_retention_days,
                      custom_domain=:custom_domain,
                      custom_script_url=:custom_script_url,
                      status=:status
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->pixel_name = htmlspecialchars(strip_tags($this->pixel_name));
        $this->facebook_pixel_id = htmlspecialchars(strip_tags($this->facebook_pixel_id ?? ''));

        $stmt->bindParam(':pixel_name', $this->pixel_name);
        $stmt->bindParam(':integration_id', $this->integration_id);
        $stmt->bindParam(':facebook_pixel_id', $this->facebook_pixel_id);
        $stmt->bindParam(':facebook_access_token', $this->facebook_access_token);
        $stmt->bindParam(':facebook_test_event_code', $this->facebook_test_event_code);
        $stmt->bindParam(':google_conversion_id', $this->google_conversion_id);
        $stmt->bindParam(':google_conversion_label', $this->google_conversion_label);
        $stmt->bindParam(':google_developer_token', $this->google_developer_token);
        $stmt->bindParam(':google_refresh_token', $this->google_refresh_token);
        $stmt->bindParam(':tiktok_pixel_code', $this->tiktok_pixel_code);
        $stmt->bindParam(':tiktok_access_token', $this->tiktok_access_token);
        $stmt->bindParam(':tiktok_advertiser_id', $this->tiktok_advertiser_id);
        $stmt->bindParam(':auto_track_pageviews', $this->auto_track_pageviews);
        $stmt->bindParam(':auto_track_clicks', $this->auto_track_clicks);
        $stmt->bindParam(':consent_mode', $this->consent_mode);
        $stmt->bindParam(':data_retention_days', $this->data_retention_days);
        $stmt->bindParam(':custom_domain', $this->custom_domain);
        $stmt->bindParam(':custom_script_url', $this->custom_script_url);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        return $stmt->execute();
    }

    public function readByUserId($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt;
    }

    public function readActiveByUserId($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id AND status = 'active' LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            foreach($row as $key => $value) {
                $this->$key = $value;
            }
            return true;
        }

        return false;
    }

    public function generatePixelSnippet($base_url = null) {
        if (!$base_url) {
            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        }

        $script_url = $this->custom_script_url ?: $base_url . '/assets/js/pixel/pixel_br.js';
        $collector_url = $base_url . '/api/pixel/collect.php';

        // Usar apenas pixel_hash para segurança
        $params = [
            'pixel_id=' . $this->pixel_hash,
            'debug=false'
        ];

        $query_string = implode('&', $params);

        return [
            'snippet' => "<script>window.PIXELBR_COLLECTOR_URL = '{$collector_url}';</script>\n<script src=\"{$script_url}?{$query_string}\" async></script>",
            'script_url' => $script_url,
            'collector_url' => $collector_url,
            'pixel_hash' => $this->pixel_hash,
            'manual_init' => "PixelBR.init({pixelId: '{$this->pixel_hash}', collector: '{$collector_url}'});"
        ];
    }

    public function getEventsSummary($days = 30) {
        $query = "SELECT 
                    COUNT(*) as total_events,
                    SUM(CASE WHEN event_name = 'page_view' THEN 1 ELSE 0 END) as page_views,
                    SUM(CASE WHEN event_name = 'lead' THEN 1 ELSE 0 END) as leads,
                    SUM(CASE WHEN event_name = 'purchase' THEN 1 ELSE 0 END) as purchases,
                    SUM(CASE WHEN consent_status = 'granted' THEN 1 ELSE 0 END) as consented_events
                  FROM pixel_events 
                  WHERE user_id = :user_id 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':days', $days);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getBridgeStatus() {
        $query = "SELECT 
                    bl.platform,
                    COUNT(*) as total_attempts,
                    SUM(CASE WHEN bl.status = 'sent' THEN 1 ELSE 0 END) as successful_sends,
                    MAX(bl.sent_at) as last_success
                  FROM bridge_logs bl
                  JOIN pixel_events pe ON bl.pixel_event_id = pe.id
                  WHERE pe.user_id = :user_id
                  AND bl.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  GROUP BY bl.platform";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[$row['platform']] = $row;
        }

        return $results;
    }
}