<?php

use App\service\logger\Logger;

$logger = Logger::getInstance();

function requireAuth() {
    global $logger;
    
    if (!isset($_SESSION['user_id'])) {
        $logger->warning("Authentication required - redirecting to login", [
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        header('Location: /login');
        exit;
    }
    
    $logger->debug("User authentication verified", [
        'user_id' => $_SESSION['user_id'],
        'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ]);
}

function guestOnly() {
    global $logger;
    
    if (isset($_SESSION['user_id'])) {
        $logger->info("Authenticated user accessing guest-only route - redirecting to feed", [
            'user_id' => $_SESSION['user_id'],
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'redirect_to' => '/feed'
        ]);
        
        header('Location: /feed');
        exit;
    }
    
    $logger->debug("Guest-only access verified", [
        'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ]);
}

function adminOnly() {
    global $logger;
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        $logger->warning("Unauthorized access to admin area", [
            'user_id' => $_SESSION['user_id'] ?? null,
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
        
        header('Location: /');
        exit;
    }
    
    $logger->debug("Admin access verified", [
        'user_id' => $_SESSION['user_id'],
        'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ]);
}

// API Helpers - moved from individual API files to centralize code
function apiRequireAuth() {
    global $logger;
    
    if (!isset($_SESSION['user_id'])) {
        $logger->warning("API authentication required", [
            'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
        
        apiSendResponse(['error' => 'Authentication required', 'success' => false], 401);
    }
    
    $userId = $_SESSION['user_id'];
    $logger->debug("API user authenticated", ['user_id' => $userId]);
    return $userId;
}

function apiSendResponse($data, $status = 200) {
    global $logger, $requestStart;
    
    // Clean any previous output
    if (ob_get_length()) {
        ob_clean();
    }
    
    $duration = isset($requestStart) ? (microtime(true) - $requestStart) * 1000 : 0;
    
    $responseData = json_encode($data);
    if ($responseData === false) {
        // Handle JSON encode errors
        $logger->error("JSON encode error", [
            'error' => json_last_error_msg(),
            'data_preview' => substr(print_r($data, true), 0, 200)
        ]);
        
        $responseData = json_encode([
            'error' => 'Server error: Could not encode response',
            'success' => false
        ]);
    }
    
    $logger->logResponse($status, sprintf(
        "API response - Size: %d bytes, Time: %.2fms, User: %s",
        strlen($responseData),
        $duration,
        $_SESSION['user_id'] ?? 'anonymous'
    ));
    
    $logger->logPerformance('api_request', $duration, [
        'method' => $_SERVER['REQUEST_METHOD'],
        'status' => $status,
        'user_id' => $_SESSION['user_id'] ?? null,
        'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ]);
    
    http_response_code($status);
    echo $responseData;
    exit;
}