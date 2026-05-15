<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);

$configFile = file_exists(BASE_PATH . '/config.php')
    ? BASE_PATH . '/config.php'
    : BASE_PATH . '/config.sample.php';

$GLOBALS['app_config'] = require $configFile;

date_default_timezone_set($GLOBALS['app_config']['app']['timezone'] ?? 'Asia/Dhaka');

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

ini_set('display_errors', !empty($GLOBALS['app_config']['app']['debug']) ? '1' : '0');
error_reporting(E_ALL);

session_name($GLOBALS['app_config']['security']['session_name'] ?? 'sp_store_session');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

spl_autoload_register(static function (string $class): void {
    $path = BASE_PATH . '/includes/' . $class . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

require_once BASE_PATH . '/includes/helpers.php';
require_once BASE_PATH . '/includes/store.php';

