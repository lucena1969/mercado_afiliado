<?php
/**
 * Controller de Autenticação
 */

require_once '../config/database.php';

use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Facebook;

class AuthController {
    private $db;
    private $user;
    private $subscription;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
        $this->subscription = new Subscription($this->db);
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('/login', 'Método inválido.');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validações básicas
        if (empty($email) || empty($password)) {
            $this->redirectWithError('/login', 'Email e senha são obrigatórios.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirectWithError('/login', 'Email inválido.');
        }

        // Tentar fazer login
        $userData = $this->user->login($email, $password);

        if ($userData) {
            // Login bem-sucedido
            $_SESSION['user'] = $userData;
            $_SESSION['logged_in'] = true;
            
            // Redirecionar para dashboard
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        } else {
            $this->redirectWithError('/login', 'Email ou senha incorretos.');
        }
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('/register', 'Método inválido.');
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $plan_slug = $_POST['plan'] ?? 'starter';

        // Validações
        $errors = [];

        if (empty($name)) $errors[] = 'Nome é obrigatório.';
        if (empty($email)) $errors[] = 'Email é obrigatório.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
        if (strlen($password) < 6) $errors[] = 'Senha deve ter no mínimo 6 caracteres.';
        if ($password !== $password_confirm) $errors[] = 'Senhas não coincidem.';

        // Verificar se email já existe
        if ($this->user->emailExists($email)) {
            $errors[] = 'Este email já está cadastrado.';
        }

        // Verificar se plano existe
        $plan = $this->subscription->getPlanBySlug($plan_slug);
        if (!$plan) {
            $errors[] = 'Plano selecionado é inválido.';
        }

        if (!empty($errors)) {
            $this->redirectWithError('/register', implode(' ', $errors));
        }

        // Criar usuário
        $this->user->name = $name;
        $this->user->email = $email;
        $this->user->password = $password;
        $this->user->phone = $phone;

        if ($this->user->create()) {
            // Criar trial
            $this->subscription->createTrial($this->user->id, $plan['id']);

            // Login automático
            $_SESSION['user'] = [
                'id' => $this->user->id,
                'uuid' => $this->user->uuid,
                'name' => $name,
                'email' => $email,
                'status' => 'active'
            ];
            $_SESSION['logged_in'] = true;
            $_SESSION['success_message'] = 'Conta criada com sucesso! Trial de 14 dias ativado.';

            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        } else {
            $this->redirectWithError('/register', 'Erro ao criar conta. Tente novamente.');
        }
    }

    public function logout() {
        session_destroy();
        header('Location: ' . BASE_URL . '/login');
        exit;
    }

    public function requireAuth() {
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    // Google OAuth
    public function googleLogin() {
        // Verificar se a classe Google OAuth existe
        if (!class_exists('League\OAuth2\Client\Provider\Google')) {
            $this->redirectWithError('/login', 'Dependências OAuth não encontradas. Contate o suporte.');
            return;
        }

        // Verificar se as credenciais estão configuradas
        if (empty(GOOGLE_CLIENT_ID) || empty(GOOGLE_CLIENT_SECRET)) {
            $this->redirectWithError('/login', 'Credenciais Google não configuradas.');
            return;
        }

        $provider = new Google([
            'clientId' => GOOGLE_CLIENT_ID,
            'clientSecret' => GOOGLE_CLIENT_SECRET,
            'redirectUri' => GOOGLE_REDIRECT_URI,
        ]);

        if (!isset($_GET['code'])) {
            $authUrl = $provider->getAuthorizationUrl([
                'scope' => ['openid', 'profile', 'email']
            ]);
            $_SESSION['oauth2state'] = $provider->getState();
            header('Location: ' . $authUrl);
            exit;
        } elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
            unset($_SESSION['oauth2state']);
            $this->redirectWithError('/login', 'Estado OAuth inválido.');
        } else {
            try {
                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $_GET['code']
                ]);
                $user = $provider->getResourceOwner($token);
                $this->processOAuthUser('google', $user->toArray());
            } catch (Exception $e) {
                $this->redirectWithError('/login', 'Erro na autenticação: ' . $e->getMessage());
            }
        }
    }

    // Facebook OAuth - REMOVIDO
    public function facebookLogin() {
        $this->redirectWithError('/login', 'Login com Facebook foi removido. Use Google ou login tradicional.');
        return;
    }

    // Processar usuário OAuth
    private function processOAuthUser($provider, $userData) {
        $email = $userData['email'] ?? '';
        $name = '';
        $providerId = '';
        $avatar = '';

        if ($provider === 'google') {
            $name = $userData['name'] ?? '';
            $providerId = $userData['sub'] ?? $userData['id'] ?? '';
            $avatar = $userData['picture'] ?? '';
        }

        if (empty($email) || empty($providerId)) {
            $this->redirectWithError('/login', 'Dados incompletos do provedor OAuth.');
        }

        $existingUser = $this->user->findByEmail($email);
        
        if (!$existingUser) {
            $existingUser = $this->user->findByOAuthProvider($provider, $providerId);
        }

        if ($existingUser) {
            $this->user->updateOAuthData($existingUser['id'], $provider, $providerId, $avatar);
            
            $_SESSION['user'] = [
                'id' => $existingUser['id'],
                'uuid' => $existingUser['uuid'],
                'name' => $existingUser['name'],
                'email' => $existingUser['email'],
                'status' => $existingUser['status']
            ];
            $_SESSION['logged_in'] = true;
            
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        } else {
            $this->user->name = $name;
            $this->user->email = $email;
            $this->user->password = bin2hex(random_bytes(16));
            $this->user->phone = '';

            if ($this->user->create()) {
                $this->user->updateOAuthData($this->user->id, $provider, $providerId, $avatar);

                $starterPlan = $this->subscription->getPlanBySlug('starter');
                if ($starterPlan) {
                    $this->subscription->createTrial($this->user->id, $starterPlan['id']);
                }

                $_SESSION['user'] = [
                    'id' => $this->user->id,
                    'uuid' => $this->user->uuid,
                    'name' => $name,
                    'email' => $email,
                    'status' => 'active'
                ];
                $_SESSION['logged_in'] = true;
                $_SESSION['success_message'] = 'Conta criada via ' . ucfirst($provider) . '! Trial de 14 dias ativado.';

                header('Location: ' . BASE_URL . '/dashboard');
                exit;
            } else {
                $this->redirectWithError('/login', 'Erro ao criar conta via ' . ucfirst($provider) . '.');
            }
        }
    }

    private function redirectWithError($path, $message) {
        $_SESSION['error_message'] = $message;
        header('Location: ' . BASE_URL . $path);
        exit;
    }
}