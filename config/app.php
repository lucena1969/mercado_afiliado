<?php
/**
 * Configurações gerais da aplicação
 * Mercado Afiliado
 */

// Configurações básicas
define('APP_NAME', 'Mercado Afiliado');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'https://mercadoafiliado.com.br');
define('APP_ROOT', dirname(__DIR__));

// Configurações de sessão
ini_set('session.cookie_lifetime', 86400); // 24 horas
ini_set('session.gc_maxlifetime', 86400);
session_start();

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de erro (desenvolvimento)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Autoload classes
spl_autoload_register(function ($class) {
    $paths = [
        APP_ROOT . '/app/models/',
        APP_ROOT . '/app/controllers/',
        APP_ROOT . '/app/middleware/',
        APP_ROOT . '/app/services/',
        APP_ROOT . '/app/utils/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Configurações MercadoPago
define('MP_ACCESS_TOKEN', ''); // Preencher com token real
define('MP_PUBLIC_KEY', '');   // Preencher com chave pública real
define('MP_WEBHOOK_SECRET', generate_webhook_secret());

// Configurações OAuth - Google
define('GOOGLE_CLIENT_ID', 'teste-google-id'); // Substituir por Client ID real do Google Cloud Console
define('GOOGLE_CLIENT_SECRET', 'teste-google-secret'); // Substituir por Client Secret real
define('GOOGLE_REDIRECT_URI', BASE_URL . '/auth/google/callback');

// Facebook OAuth REMOVIDO - Constantes vazias para evitar erros
define('FACEBOOK_CLIENT_ID', '');
define('FACEBOOK_CLIENT_SECRET', '');
define('FACEBOOK_REDIRECT_URI', '');

// Incluir autoload do Composer (se existir)
if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require_once APP_ROOT . '/vendor/autoload.php';
}

function generate_webhook_secret() {
    return bin2hex(random_bytes(32));
}