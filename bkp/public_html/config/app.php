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
        APP_ROOT . '/app/utils/',
        APP_ROOT . '/config/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Incluir Database class manualmente se necessário
require_once APP_ROOT . '/config/database.php';

// Configurações MercadoPago
define('MP_ACCESS_TOKEN', ''); // Preencher com token real
define('MP_PUBLIC_KEY', '');   // Preencher com chave pública real
define('MP_WEBHOOK_SECRET', generate_webhook_secret());

// Configurações OAuth - Google
define('GOOGLE_CLIENT_ID', '41618611981-h8rrgi15kailmmdhh1pgcp7e97bmfue3.apps.googleusercontent.com'); // COLE SEU CLIENT ID AQUI
define('GOOGLE_CLIENT_SECRET', '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'); // COLE SEU CLIENT SECRET AQUI
define('GOOGLE_REDIRECT_URI', BASE_URL . '/auth/google/callback');

// Facebook OAuth REMOVIDO - Constantes vazias para evitar erros
define('FACEBOOK_CLIENT_ID', '');
define('FACEBOOK_CLIENT_SECRET', '');
define('FACEBOOK_REDIRECT_URI', '');

// Configurações SMTP - E-mail de contato
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587); // TLS/STARTTLS
define('SMTP_USERNAME', 'contato@mercadoafiliado.com.br');
define('SMTP_PASSWORD', ''); // PREENCHER COM A SENHA REAL DO E-MAIL
define('SMTP_FROM_EMAIL', 'contato@mercadoafiliado.com.br');
define('SMTP_FROM_NAME', 'Mercado Afiliado');
define('CONTACT_EMAIL', 'contato@mercadoafiliado.com.br');

// Incluir autoload do Composer (se existir)
if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require_once APP_ROOT . '/vendor/autoload.php';
}

function generate_webhook_secret() {
    return bin2hex(random_bytes(32));
}