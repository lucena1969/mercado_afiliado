<?php
/**
 * Configuração do Banco de Dados
 * Mercado Afiliado
 */

class Database {
    private $host = 'localhost'; // Substitua pelo host do seu provedor
    private $db_name = 'u590097272_mercado_afilia';
    private $username = 'u590097272_lucena1969'; // Substitua pelo usuário do banco
    private $password = 'Numse!2020'; // Adicione a senha do banco
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