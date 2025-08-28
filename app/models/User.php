<?php
/**
 * Model User - Gerenciamento de usuários
 */

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $uuid;
    public $name;
    public $email;
    public $password;
    public $phone;
    public $avatar;
    public $email_verified_at;
    public $status;
    public $last_login_at;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Criar usuário
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET uuid=:uuid, name=:name, email=:email, password=:password, phone=:phone";
        
        $stmt = $this->conn->prepare($query);
        
        // Gerar UUID
        $this->uuid = $this->generateUUID();
        
        // Hash da senha
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        
        // Bind valores
        $stmt->bindParam(":uuid", $this->uuid);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":phone", $this->phone);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    // Login do usuário
    public function login($email, $password) {
        $query = "SELECT id, uuid, name, email, password, status FROM " . $this->table_name . " 
                  WHERE email = :email AND status = 'active' LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            
            if(password_verify($password, $row['password'])) {
                // Atualizar último login
                $this->updateLastLogin($row['id']);
                
                return [
                    'id' => $row['id'],
                    'uuid' => $row['uuid'],
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'status' => $row['status']
                ];
            }
        }
        
        return false;
    }

    // Buscar por ID
    public function findById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Buscar por email
    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Atualizar último login
    private function updateLastLogin($user_id) {
        $query = "UPDATE " . $this->table_name . " SET last_login_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $user_id);
        $stmt->execute();
    }

    // Gerar UUID v4
    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    // Verificar se email existe
    public function emailExists($email) {
        return $this->findByEmail($email) !== false;
    }

    // Buscar por ID do provedor OAuth
    public function findByOAuthProvider($provider, $providerId) {
        $column = $provider . '_id';
        $query = "SELECT * FROM " . $this->table_name . " WHERE {$column} = :provider_id AND status = 'active' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":provider_id", $providerId);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Atualizar dados OAuth
    public function updateOAuthData($userId, $provider, $providerId, $avatar = '') {
        $column = $provider . '_id';
        $query = "UPDATE " . $this->table_name . " SET 
                  {$column} = :provider_id, 
                  avatar = COALESCE(NULLIF(:avatar, ''), avatar),
                  updated_at = NOW() 
                  WHERE id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":provider_id", $providerId);
        $stmt->bindParam(":avatar", $avatar);
        $stmt->bindParam(":user_id", $userId);
        return $stmt->execute();
    }
}