<?php
declare(strict_types=1);

/**
 * FCMS public front controller.
 * The existing PHP application remains one directory above /public.
 * Only explicitly mapped application pages can be executed.
 */

$appRoot = dirname(__DIR__);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) && $path !== '' ? $path : '/';
$path = rtrim($path, '/') ?: '/';

$routes = [
    '/'             => 'index.php',
    '/home'         => 'index.php',
    '/index.php'    => 'index.php',
    '/login'        => 'login.php',
    '/login.php'    => 'login.php',
    '/register'     => 'register.php',
    '/register.php' => 'register.php',
    '/logout'       => 'logout.php',
    '/logout.php'   => 'logout.php',
    '/schedule'     => 'schedule.php',
    '/schedule.php' => 'schedule.php',
    '/account'      => 'account.php',
    '/account.php'  => 'account.php',
    '/trainer'      => 'trainer.php',
    '/trainer.php'  => 'trainer.php',
    '/admin'        => 'admin.php',
    '/admin.php'    => 'admin.php',
];

$target = $routes[$path] ?? null;

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

if ($target === null || !is_file($appRoot . DIRECTORY_SEPARATOR . $target)) {
    http_response_code(404);
    ?><!doctype html>
    <html lang="tg">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Саҳифа ёфт нашуд — FCMS</title>
      <link rel="stylesheet" href="/assets/css/app.css">
    </head>
    <body class="error-page">
      <main class="error-card">
        <p class="eyebrow">FCMS · 404</p>
        <h1>Саҳифа ёфт нашуд</h1>
        <p class="muted">Суроға нодуруст аст ё саҳифа дигар дастрас нест.</p>
        <a class="btn" href="/">Ба саҳифаи асосӣ</a>
      </main>
    </body>
    </html><?php
    exit;
}

// Existing scripts use relative includes such as require 'config.php'.
// Setting the working directory preserves that behavior after moving the web root to /public.
chdir($appRoot);
require $appRoot . DIRECTORY_SEPARATOR . $target;
