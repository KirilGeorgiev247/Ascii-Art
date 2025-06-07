<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../routes/web.php';

use App\model\User;
use App\model\Post;
use App\service\logger\Logger;

session_start();

$logger = Logger::getInstance();
$requestStart = microtime(true);

$logger->logRequest($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'] ?? '/api/users', [
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? null
]);

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

switch ($method) {
    case 'GET':
        if (isset($pathParts[2]) && is_numeric($pathParts[2])) {
            $userId = (int)$pathParts[2];
            
            $logger->info("Fetching user profile", [
                'user_id' => $userId,
                'requestor_id' => $_SESSION['user_id'] ?? null
            ]);
            
            $startTime = microtime(true);
            $user = User::findById($userId);
            $userLookupDuration = (microtime(true) - $startTime) * 1000;
            
            $logger->logPerformance('user_profile_lookup', $userLookupDuration, [
                'user_id' => $userId
            ]);
            
            if (!$user) {
                $logger->warning("User profile not found", [
                    'user_id' => $userId,
                    'requestor_id' => $_SESSION['user_id'] ?? null,
                    'lookup_time_ms' => round($userLookupDuration, 2)
                ]);
                apiSendResponse(['error' => 'User not found'], 404);
            }
            
            $logger->info("Fetching user posts", [
                'user_id' => $userId,
                'username' => $user->getUsername()
            ]);
            
            $startTime = microtime(true);
            $posts = Post::findByUserId($userId);
            $postsDuration = (microtime(true) - $startTime) * 1000;
            
            $logger->logPerformance('user_posts_fetch', $postsDuration, [
                'user_id' => $userId,
                'posts_count' => count($posts)
            ]);
            
            $logger->info("User profile fetched successfully", [
                'user_id' => $userId,
                'username' => $user->getUsername(),
                'posts_count' => count($posts),
                'lookup_time_ms' => round($userLookupDuration, 2),
                'posts_time_ms' => round($postsDuration, 2),
                'total_time_ms' => round($userLookupDuration + $postsDuration, 2)
            ]);
            
            apiSendResponse([
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'bio' => $user->getBio(),
                'profile_picture' => $user->getProfilePicture(),
                'created_at' => $user->getCreatedAt(),
                'posts_count' => count($posts),
                'posts' => array_map(function($post) {
                    return [
                        'id' => $post->getId(),
                        'title' => $post->getTitle(),
                        'content' => $post->getContent(),
                        'type' => $post->getType(),
                        'ascii_content' => $post->getAsciiContent(),
                        'likes_count' => $post->getLikesCount(),
                        'created_at' => $post->getCreatedAt()
                    ];
                }, $posts)
            ]);
            
        } elseif (isset($_GET['current'])) {
            $userId = apiRequireAuth();
            
            $logger->info("Fetching current user profile", [
                'user_id' => $userId
            ]);
            
            $startTime = microtime(true);
            $user = User::findById($userId);
            $userLookupDuration = (microtime(true) - $startTime) * 1000;
            
            $logger->logPerformance('current_user_lookup', $userLookupDuration, [
                'user_id' => $userId
            ]);
            
            if (!$user) {
                $logger->error("Current user not found in database", [
                    'user_id' => $userId,
                    'session_id' => session_id(),
                    'lookup_time_ms' => round($userLookupDuration, 2)
                ]);
                apiSendResponse(['error' => 'User not found'], 404);
            }
            
            $startTime = microtime(true);
            $posts = Post::findByUserId($userId);
            $postsDuration = (microtime(true) - $startTime) * 1000;
            
            $logger->logPerformance('current_user_posts_fetch', $postsDuration, [
                'user_id' => $userId,
                'posts_count' => count($posts)
            ]);
            
            $logger->info("Current user profile fetched successfully", [
                'user_id' => $userId,
                'username' => $user->getUsername(),
                'posts_count' => count($posts),
                'lookup_time_ms' => round($userLookupDuration, 2),
                'posts_time_ms' => round($postsDuration, 2)
            ]);
            
            apiSendResponse([
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'bio' => $user->getBio(),
                'profile_picture' => $user->getProfilePicture(),
                'created_at' => $user->getCreatedAt(),
                'posts_count' => count($posts),
                'posts' => array_map(function($post) {
                    return [
                        'id' => $post->getId(),
                        'title' => $post->getTitle(),
                        'content' => $post->getContent(),
                        'type' => $post->getType(),
                        'ascii_content' => $post->getAsciiContent(),
                        'likes_count' => $post->getLikesCount(),
                        'created_at' => $post->getCreatedAt()
                    ];
                }, $posts)
            ]);
            
        } else {
            $query = $_GET['search'] ?? '';
            
            $logger->info("User search request", [
                'query' => $query,
                'requestor_id' => $_SESSION['user_id'] ?? null
            ]);
            
            if (empty($query)) {
                $logger->warning("User search failed - empty query", [
                    'requestor_id' => $_SESSION['user_id'] ?? null
                ]);
                apiSendResponse(['error' => 'Search query required'], 400);
            }
            
            $logger->info("Performing user search", [
                'query' => $query,
                'query_length' => strlen($query)
            ]);
            
            $startTime = microtime(true);
            $users = User::searchByUsername($query);
            $searchDuration = (microtime(true) - $startTime) * 1000;
            
            $logger->logPerformance('user_search', $searchDuration, [
                'query' => $query,
                'results_count' => count($users)
            ]);
            
            $result = array_map(function($user) {
                return [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'bio' => $user->getBio(),
                    'profile_picture' => $user->getProfilePicture()
                ];
            }, $users);
            
            $logger->info("User search completed successfully", [
                'query' => $query,
                'results_count' => count($users),
                'search_time_ms' => round($searchDuration, 2)
            ]);
            
            apiSendResponse($result);
        }
        break;
    case 'PUT':
        if (isset($pathParts[2]) && is_numeric($pathParts[2])) {
            $profileUserId = (int)$pathParts[2];
            $currentUserId = apiRequireAuth();
            
            $logger->info("User profile update attempt", [
                'profile_user_id' => $profileUserId,
                'current_user_id' => $currentUserId
            ]);
            
            if ($profileUserId !== $currentUserId) {
                $logger->warning("Unauthorized profile update attempt", [
                    'profile_user_id' => $profileUserId,
                    'current_user_id' => $currentUserId,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null
                ]);
                apiSendResponse(['error' => 'Unauthorized'], 403);
            }
            
            $startTime = microtime(true);
            $user = User::findById($currentUserId);
            $userLookupDuration = (microtime(true) - $startTime) * 1000;
            
            $logger->logPerformance('profile_user_lookup', $userLookupDuration, [
                'user_id' => $currentUserId
            ]);
            
            if (!$user) {
                $logger->error("User not found for profile update", [
                    'user_id' => $currentUserId,
                    'lookup_time_ms' => round($userLookupDuration, 2)
                ]);
                apiSendResponse(['error' => 'User not found'], 404);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $logger->info("Processing profile update", [
                'user_id' => $currentUserId,
                'username' => $user->getUsername(),
                'update_fields' => array_keys($input ?? []),
                'input_data' => $input
            ]);
            
            $changes = [];
            $originalBio = $user->getBio();
            $originalProfilePicture = $user->getProfilePicture();
            
            if (isset($input['bio'])) {
                $user->setBio($input['bio']);
                $changes['bio'] = [
                    'old' => $originalBio,
                    'new' => $input['bio']
                ];
            }
            
            if (isset($input['profile_picture'])) {
                $user->setProfilePicture($input['profile_picture']);
                $changes['profile_picture'] = [
                    'old' => $originalProfilePicture,
                    'new' => $input['profile_picture']
                ];
            }
            
            $logger->info("Saving profile changes", [
                'user_id' => $currentUserId,
                'changes' => $changes,
                'changes_count' => count($changes)
            ]);
            
            $startTime = microtime(true);
            $saveSuccess = $user->save();
            $saveDuration = (microtime(true) - $startTime) * 1000;
            
            $logger->logPerformance('profile_save', $saveDuration, [
                'user_id' => $currentUserId,
                'changes_count' => count($changes)
            ]);
            
            if ($saveSuccess) {
                $logger->info("Profile updated successfully", [
                    'user_id' => $currentUserId,
                    'username' => $user->getUsername(),
                    'changes' => $changes,
                    'save_time_ms' => round($saveDuration, 2)
                ]);
                
                apiSendResponse([
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'bio' => $user->getBio(),
                    'profile_picture' => $user->getProfilePicture(),
                    'created_at' => $user->getCreatedAt()
                ]);
            } else {
                $logger->error("Profile update failed", [
                    'user_id' => $currentUserId,
                    'username' => $user->getUsername(),
                    'changes' => $changes,
                    'save_time_ms' => round($saveDuration, 2),
                    'reason' => 'Database save operation failed'
                ]);
                
                apiSendResponse(['error' => 'Failed to update profile'], 500);
            }
        } else {
            $logger->warning("Profile update failed - missing user ID", [
                'path_parts' => $pathParts,
                'current_user_id' => $_SESSION['user_id'] ?? null
            ]);
            apiSendResponse(['error' => 'User ID required'], 400);
        }
        break;
    default:
        $logger->warning("Unsupported HTTP method for users API", [
            'method' => $method,
            'uri' => $_SERVER['REQUEST_URI'],
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
        apiSendResponse(['error' => 'Method not allowed'], 405);
}

$totalDuration = (microtime(true) - $requestStart) * 1000;
$logger->info("Users API request completed", [
    'method' => $method,
    'total_time_ms' => round($totalDuration, 2),
    'user_id' => $_SESSION['user_id'] ?? null
]);
?>