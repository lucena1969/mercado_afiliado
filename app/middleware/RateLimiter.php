<?php
/**
 * Middleware RateLimiter - Controle de taxa de requisições
 * Previne flood/spam em webhooks
 */

class RateLimiter {
    private $conn;
    private $table_name = "rate_limit_log";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Verificar se a requisição está dentro do limite
     *
     * @param string $key - Identificador único (ex: integration_id, ip)
     * @param int $max_requests - Máximo de requisições permitidas
     * @param int $window_seconds - Janela de tempo em segundos
     * @return bool - True se permitido, False se excedeu o limite
     */
    public function check($key, $max_requests = 100, $window_seconds = 60) {
        // Limpar registros antigos
        $this->cleanup($window_seconds);

        // Contar requisições no período
        $count = $this->getRequestCount($key, $window_seconds);

        if ($count >= $max_requests) {
            error_log("Rate limit excedido: {$key} - {$count}/{$max_requests} em {$window_seconds}s");
            return false;
        }

        // Registrar esta requisição
        $this->logRequest($key);

        return true;
    }

    /**
     * Contar requisições da chave no período
     */
    private function getRequestCount($key, $window_seconds) {
        $query = "SELECT COUNT(*) as count
                  FROM " . $this->table_name . "
                  WHERE rate_key = :key
                  AND created_at >= DATE_SUB(NOW(), INTERVAL :window SECOND)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":key", $key);
        $stmt->bindParam(":window", $window_seconds);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['count'];
    }

    /**
     * Registrar requisição
     */
    private function logRequest($key) {
        $query = "INSERT INTO " . $this->table_name . "
                  SET rate_key = :key, created_at = NOW()";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":key", $key);
        return $stmt->execute();
    }

    /**
     * Limpar registros antigos (manutenção)
     */
    private function cleanup($keep_seconds = 3600) {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE created_at < DATE_SUB(NOW(), INTERVAL :keep SECOND)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":keep", $keep_seconds);
        return $stmt->execute();
    }

    /**
     * Verificar limite por integração (100/min)
     */
    public function checkIntegrationLimit($integration_id) {
        return $this->check("integration_{$integration_id}", 100, 60);
    }

    /**
     * Verificar limite por IP (10/segundo - proteção contra DDoS)
     */
    public function checkIpLimit($ip) {
        return $this->check("ip_{$ip}", 10, 1);
    }

    /**
     * Verificar limite global (1000/min - proteção do servidor)
     */
    public function checkGlobalLimit() {
        return $this->check("global", 1000, 60);
    }

    /**
     * Obter tempo de espera até próxima requisição permitida
     */
    public function getRetryAfter($key, $max_requests, $window_seconds) {
        $query = "SELECT MIN(created_at) as oldest
                  FROM (
                      SELECT created_at
                      FROM " . $this->table_name . "
                      WHERE rate_key = :key
                      AND created_at >= DATE_SUB(NOW(), INTERVAL :window SECOND)
                      ORDER BY created_at DESC
                      LIMIT :max_requests
                  ) as recent_requests";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":key", $key);
        $stmt->bindParam(":window", $window_seconds);
        $stmt->bindParam(":max_requests", $max_requests, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['oldest']) {
            $oldest_timestamp = strtotime($result['oldest']);
            $retry_after = ($oldest_timestamp + $window_seconds) - time();
            return max(1, $retry_after); // Mínimo 1 segundo
        }

        return $window_seconds;
    }

    /**
     * Resetar limite para uma chave (útil para testes)
     */
    public function reset($key) {
        $query = "DELETE FROM " . $this->table_name . " WHERE rate_key = :key";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":key", $key);
        return $stmt->execute();
    }
}
