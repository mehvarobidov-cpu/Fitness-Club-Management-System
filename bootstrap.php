<?php
declare(strict_types=1);

use App\Core\Database;

if (!defined('FCMS_ROOT')) {
    define('FCMS_ROOT', dirname(__DIR__));
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('Asia/Dushanbe');

$configFile = FCMS_ROOT . '/config/database.php';
if (is_file($configFile)) {
    $databaseConfig = require $configFile;
    if (!is_array($databaseConfig)) {
        throw new RuntimeException('config/database.php must return an array.');
    }
    Database::configure($databaseConfig);
}
