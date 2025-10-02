<?php
/**
 * Configuração do Banco de Dados MySQL
 * Mercado Afiliado - Produção
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'u590097272_mercado_afilia';
    private $username = 'u590097272_lucena1969';
    private $password = 'Numse!2020';
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