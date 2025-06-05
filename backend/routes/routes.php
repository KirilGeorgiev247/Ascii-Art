<?php

use App\service\logger\Logger;
use App\controller\AuthController;
// use App\controller\DrawController;
use App\controller\ProfileController;

$logger = Logger::getInstance();
$logger->debug("Loading additional routes", [
    'routes_file' => 'routes/routes.php',
    'timestamp' => date('Y-m-d H:i:s')
]);

$router->get('/', function() use ($logger) {
    $logger->info("Home page route accessed", [
        'route' => '/',
        'method' => 'GET',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    require_once __DIR__ . '/../views/home/home.php';
});

// // Drawing page
// $router->get('/draw', function() use ($logger) {
//     $logger->info("Draw page route accessed", [
//         'route' => '/draw',
//         'method' => 'GET',
//         'user_id' => $_SESSION['user_id'] ?? 'guest'
//     ]);
//     (new DrawController())->index();
// });

// Profile page
$router->get('/profile', function() use ($logger) {
    $logger->info("Profile page route accessed", [
        'route' => '/profile',
        'method' => 'GET',
        'user_id' => $_SESSION['user_id'] ?? null
    ]);
    (new ProfileController())->index($_SESSION['user_id'] ?? null);
});

// Auth routes
$router->get('/login', function() use ($logger) {
    $logger->info("Login page route accessed", [
        'route' => '/login',
        'method' => 'GET',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    (new AuthController())->showLogin();
});

$router->post('/login', function() use ($logger) {
    $logger->info("Login form submission route accessed", [
        'route' => '/login',
        'method' => 'POST',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    (new AuthController())->login();
});

$router->get('/register', function() use ($logger) {
    $logger->info("Registration page route accessed", [
        'route' => '/register',
        'method' => 'GET',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    (new AuthController())->showRegister();
});

$router->post('/register', function() use ($logger) {
    $logger->info("Registration form submission route accessed", [
        'route' => '/register',
        'method' => 'POST',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    (new AuthController())->register();
});

$router->get('/logout', function() use ($logger) {
    $logger->info("Logout route accessed", [
        'route' => '/logout',
        'method' => 'GET',
        'user_id' => $_SESSION['user_id'] ?? null
    ]);
    (new AuthController())->logout();
});

$logger->debug("Additional routes loaded successfully", [
    'routes_count' => 8,
    'routes_loaded' => ['/', '/draw', '/profile', '/login', '/register', '/logout']
]);