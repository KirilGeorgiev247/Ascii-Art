<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use App\db\Database;
use App\router\Router;
use App\service\logger\Logger;

//TODO maybe delete
require_once __DIR__ . '/../routes/web.php';

$logger = Logger::getInstance();
$logger->info("Application started", [
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Sofia');

set_error_handler(function($severity, $message, $file, $line) use ($logger) {
    $logger->error("PHP Error: $message", [
        'severity' => $severity,
        'file' => $file,
        'line' => $line
    ]);
});

set_exception_handler(function($exception) use ($logger) {
    $logger->logException($exception, 'Uncaught exception');
    http_response_code(500);
    echo "<h1>Internal Server Error</h1>";
    echo "<p>An error occurred while processing your request.</p>";
});

$logger->info("Testing database connection");
try {
    $startTime = microtime(true);
    $db = Database::getInstance();
    $duration = (microtime(true) - $startTime) * 1000;

    $logger->logPerformance('database_connection', $duration);
    $logger->info("Database connection successful");
} catch (Exception $e) {
    $logger->critical("Database connection failed", [
        'error_message' => $e->getMessage(),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine()
    ]);
    http_response_code(500);
    echo "<h1>Database Connection Error</h1>";
    echo "<p>Unable to connect to the database: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

$logger->debug("Initializing router");
$router = new Router();

$logger->debug("Loading routes");
require_once __DIR__ . '/../routes/routes.php';

$logger->info("Dispatching request", [
    'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
]);

try {
    $startTime = microtime(true);
    $router->dispatch();
    $duration = (microtime(true) - $startTime) * 1000;

    $logger->logPerformance('request_processing', $duration, [
        'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
    ]);

    $logger->info("Request processed successfully");
} catch (Exception $e) {
    $logger->logException($e, 'Request dispatch failed');
    http_response_code(500);
    echo "<h1>Internal Server Error</h1>";
    echo "<p>An error occurred while processing your request.</p>";
}


// TODO: old logic, delete at some point
// require_once __DIR__ . '/../vendor/autoload.php';

// use App\db\Database;

// try {
//     $db = new Database();
//     echo "<p> Database connection successful.</p>";
// } catch (Exception $e) {
//     echo "<p> Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
//     exit;
// }

// echo "<p> PHP server is running.</p>";