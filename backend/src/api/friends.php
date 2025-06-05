<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../vendor/autoload.php';

use App\model\Friend;
use App\model\User;
use App\service\logger\Logger;

$logger = Logger::getInstance();
$logger->logRequest($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'] ?? '/api/friends', [
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'unknown'
]);

session_start();

function sendResponse($data, $status = 200) {
    $logger = Logger::getInstance();
    $responseData = json_encode($data);
    $logger->logResponse($status, "Friends API response sent: " . strlen($responseData) . " bytes");
    
    http_response_code($status);
    echo $responseData;
    exit;
}

// Check if user is authenticated
function requireAuth() {
    $logger = Logger::getInstance();
    
    if (!isset($_SESSION['user_id'])) {
        $logger->warning("Friends API authentication required - no user session", [
            'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
        ]);
        sendResponse(['error' => 'Authentication required'], 401);
    }
    
    $userId = $_SESSION['user_id'];
    $logger->debug("Friends API user authenticated", ['user_id' => $userId]);
    return $userId;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

$logger->info("Friends API request", [
    'method' => $method,
    'path' => $path,
    'path_parts' => $pathParts,
    'query_params' => $_GET ?? [],
    'has_session' => isset($_SESSION['user_id'])
]);

switch ($method) {
    case 'GET':
        $userId = requireAuth();
        
        if (isset($_GET['pending'])) {
            $logger->info("Fetching pending friend requests", ['user_id' => $userId]);
            
            $startTime = microtime(true);
            $requests = Friend::getPendingRequests($userId);
            $duration = (microtime(true) - $startTime) * 1000;
            
            $logger->logPerformance('pending_requests_fetch', $duration, [
                'user_id' => $userId,
                'requests_count' => count($requests)
            ]);
            
            $logger->info("Pending friend requests fetched successfully", [
                'user_id' => $userId,
                'requests_count' => count($requests),
                'performance_ms' => round($duration, 2)
            ]);
            
            sendResponse($requests);
            
        } elseif (isset($_GET['check']) && isset($_GET['friend_id'])) {
            $friendId = (int)$_GET['friend_id'];
            
            $logger->info("Checking friendship status", [
                'user_id' => $userId,
                'friend_id' => $friendId
            ]);
            
            $startTime = microtime(true);
            $friendship = Friend::getFriendship($userId, $friendId);
            $areFriends = Friend::areFriends($userId, $friendId);
            $duration = (microtime(true) - $startTime) * 1000;
            
            $logger->logPerformance('friendship_status_check', $duration, [
                'user_id' => $userId,
                'friend_id' => $friendId
            ]);
            
            if ($friendship) {
                $status = $friendship->getStatus();
                $logger->info("Friendship status checked successfully", [
                    'user_id' => $userId,
                    'friend_id' => $friendId,
                    'status' => $status,
                    'are_friends' => $areFriends,
                    'performance_ms' => round($duration, 2)
                ]);
                
                sendResponse([
                    'status' => $status,
                    'are_friends' => $areFriends
                ]);
            } else {
                $logger->info("No friendship found", [
                    'user_id' => $userId,
                    'friend_id' => $friendId,
                    'performance_ms' => round($duration, 2)
                ]);
                
                sendResponse(['status' => 'none', 'are_friends' => false]);
            }
            
        } else {
            $logger->info("Fetching friends list", ['user_id' => $userId]);
            
            $startTime = microtime(true);
            $friends = Friend::getFriends($userId);
            $duration = (microtime(true) - $startTime) * 1000;
            
            $logger->logPerformance('friends_list_fetch', $duration, [
                'user_id' => $userId,
                'friends_count' => count($friends)
            ]);
            
            $logger->info("Friends list fetched successfully", [
                'user_id' => $userId,
                'friends_count' => count($friends),
                'performance_ms' => round($duration, 2)
            ]);
            
            sendResponse($friends);
        }
        break;    case 'POST':
        $userId = requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        
        $logger->info("Friend request creation attempt", [
            'user_id' => $userId,
            'input_data' => $input
        ]);
        
        if (!isset($input['friend_id'])) {
            $logger->warning("Friend request creation failed - missing friend_id", [
                'user_id' => $userId,
                'input_data' => $input
            ]);
            sendResponse(['error' => 'Friend ID required'], 400);
        }
        
        $friendId = (int)$input['friend_id'];
        
        if ($userId === $friendId) {
            $logger->warning("Friend request creation failed - self friend attempt", [
                'user_id' => $userId,
                'friend_id' => $friendId
            ]);
            sendResponse(['error' => 'Cannot add yourself as friend'], 400);
        }
        
        $logger->info("Validating friend user exists", [
            'user_id' => $userId,
            'friend_id' => $friendId
        ]);
        
        $startTime = microtime(true);
        $friendUser = User::findById($friendId);
        $duration = (microtime(true) - $startTime) * 1000;
        
        $logger->logPerformance('friend_user_lookup', $duration, [
            'user_id' => $userId,
            'friend_id' => $friendId
        ]);
        
        if (!$friendUser) {
            $logger->warning("Friend request creation failed - friend user not found", [
                'user_id' => $userId,
                'friend_id' => $friendId,
                'lookup_time_ms' => round($duration, 2)
            ]);
            sendResponse(['error' => 'User not found'], 404);
        }
        
        $logger->info("Creating friend request", [
            'user_id' => $userId,
            'friend_id' => $friendId,
            'friend_username' => $friendUser->getUsername()
        ]);
        
        $startTime = microtime(true);
        $friendship = Friend::addFriend($userId, $friendId);
        $duration = (microtime(true) - $startTime) * 1000;
        
        $logger->logPerformance('friend_request_creation', $duration, [
            'user_id' => $userId,
            'friend_id' => $friendId
        ]);
        
        if ($friendship) {
            $logger->info("Friend request created successfully", [
                'user_id' => $userId,
                'friend_id' => $friendId,
                'friend_username' => $friendUser->getUsername(),
                'status' => $friendship->getStatus(),
                'creation_time_ms' => round($duration, 2)
            ]);
            
            sendResponse([
                'message' => 'Friend request sent',
                'status' => $friendship->getStatus()
            ], 201);
        } else {
            $logger->warning("Friend request creation failed", [
                'user_id' => $userId,
                'friend_id' => $friendId,
                'friend_username' => $friendUser->getUsername(),
                'creation_time_ms' => round($duration, 2),
                'reason' => 'Request already exists or database error'
            ]);
            
            sendResponse(['error' => 'Friend request already exists or failed'], 400);
        }
        break;    case 'PUT':
        if (!isset($pathParts[2]) || !is_numeric($pathParts[2])) {
            $logger->warning("Friend request update failed - invalid friend ID", [
                'path_parts' => $pathParts,
                'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null
            ]);
            sendResponse(['error' => 'Friend ID required'], 400);
        }
        
        $friendId = (int)$pathParts[2];
        $userId = requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        
        $logger->info("Friend request update attempt", [
            'user_id' => $userId,
            'friend_id' => $friendId,
            'action' => isset($input['action']) ? $input['action'] : null,
            'input_data' => $input
        ]);
        
        if (isset($input['action']) && $input['action'] === 'accept') {
            $logger->info("Accepting friend request", [
                'user_id' => $userId,
                'friend_id' => $friendId
            ]);
            
            $startTime = microtime(true);
            $success = Friend::acceptFriendship($userId, $friendId);
            $duration = (microtime(true) - $startTime) * 1000;
            
            $logger->logPerformance('friend_request_acceptance', $duration, [
                'user_id' => $userId,
                'friend_id' => $friendId
            ]);
            
            if ($success) {
                $logger->info("Friend request accepted successfully", [
                    'user_id' => $userId,
                    'friend_id' => $friendId,
                    'acceptance_time_ms' => round($duration, 2)
                ]);
                
                sendResponse(['message' => 'Friend request accepted']);
            } else {
                $logger->error("Friend request acceptance failed", [
                    'user_id' => $userId,
                    'friend_id' => $friendId,
                    'acceptance_time_ms' => round($duration, 2),
                    'reason' => 'Database operation failed or invalid request'
                ]);
                
                sendResponse(['error' => 'Failed to accept friend request'], 500);
            }
        } else {
            $logger->warning("Friend request update failed - invalid action", [
                'user_id' => $userId,
                'friend_id' => $friendId,
                'action' => isset($input['action']) ? $input['action'] : null,
                'input_data' => $input
            ]);
            
            sendResponse(['error' => 'Invalid action'], 400);
        }
        break;    case 'DELETE':
        if (!isset($pathParts[2]) || !is_numeric($pathParts[2])) {
            $logger->warning("Friendship removal failed - invalid friend ID", [
                'path_parts' => $pathParts,
                'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null
            ]);
            sendResponse(['error' => 'Friend ID required'], 400);
        }
        
        $friendId = (int)$pathParts[2];
        $userId = requireAuth();
        
        $logger->info("Friendship removal attempt", [
            'user_id' => $userId,
            'friend_id' => $friendId
        ]);
        
        $startTime = microtime(true);
        $success = Friend::removeFriendship($userId, $friendId);
        $duration = (microtime(true) - $startTime) * 1000;
        
        $logger->logPerformance('friendship_removal', $duration, [
            'user_id' => $userId,
            'friend_id' => $friendId
        ]);
        
        if ($success) {
            $logger->info("Friendship removed successfully", [
                'user_id' => $userId,
                'friend_id' => $friendId,
                'removal_time_ms' => round($duration, 2)
            ]);
            
            sendResponse(['message' => 'Friendship removed']);
        } else {
            $logger->error("Friendship removal failed", [
                'user_id' => $userId,
                'friend_id' => $friendId,
                'removal_time_ms' => round($duration, 2),
                'reason' => 'Database operation failed or friendship does not exist'
            ]);
            
            sendResponse(['error' => 'Failed to remove friendship'], 500);
        }
        break;

    default:
        sendResponse(['error' => 'Method not allowed'], 405);
}
?>