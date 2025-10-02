<?php
/**
 * Configurações de Produção
 * Mercado Afiliado - Servidor de Hospedagem
 * 
 * INSTRUÇÕES:
 * 1. Substitua os valores abaixo pelas credenciais do seu provedor
 * 2. Renomeie database.php para database_local.php (backup)
 * 3. Renomeie este arquivo para database.php
 */

class Database {
    // Configurações do servidor de hospedagem
    private $host = 'localhost'; // Ex: mysql.seudominio.com.br ou IP do servidor
    private $db_name = 'u590097272_mercado_afilia';
    private $username = 'u590097272_lucena1969'; // Substitua pelo usuário fornecido
    private $password = 'Numse!2020'; // Substitua pela senha fornecida
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
            die();
        }

        return $this->conn;
    }
}