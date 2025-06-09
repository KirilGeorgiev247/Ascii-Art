<?php
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../routes/web.php';

use App\model\Like;
use App\model\Post;
use App\service\logger\Logger;

set_error_handler(function($severity, $message, $file, $line) {
    $logger = Logger::getInstance();
    $logger->error("PHP Error in likes API: $message", [
        'severity' => $severity,
        'file' => $file,
        'line' => $line
    ]);
    return true;
});

$logger = Logger::getInstance();
$requestStart = microtime(true);

$logger->logRequest($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'] ?? '/api/likes', [
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

session_start();

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

try {
    switch ($method) {
        case 'POST':
            // /api/likes/{post_id}
            if (isset($pathParts[2]) && is_numeric($pathParts[2])) {
                $postId = (int)$pathParts[2];
                $userId = apiRequireAuth();

                $logger->info("Like/unlike request", [
                    'user_id' => $userId,
                    'post_id' => $postId
                ]);

                $post = Post::findById($postId);
                if (!$post) {
                    apiSendResponse(['error' => 'Post not found'], 404);
                }

                $liked = Like::isPostLikedByUser($userId, $postId);
                if ($liked) {
                    Like::unlikePost($userId, $postId);
                    $action = 'unliked';
                } else {
                    Like::likePost($userId, $postId);
                    $action = 'liked';
                }
                $post = Post::findById($postId); // Refresh likes count
                apiSendResponse([
                    'success' => true,
                    'action' => $action,
                    'likes_count' => $post->getLikesCount()
                ]);
            } else {
                apiSendResponse(['error' => 'Post ID required'], 400);
            }
            break;

        case 'GET':
            // /api/likes/{post_id}
            if (isset($pathParts[2]) && is_numeric($pathParts[2])) {
                $postId = (int)$pathParts[2];
                $likes = Like::getLikesForPost($postId);
                $result = array_map(function($like) {
                    return [
                        'id' => $like->getId(),
                        'user_id' => $like->getUserId(),
                        'username' => $like->getUsername(),
                        'created_at' => $like->getCreatedAt()
                    ];
                }, $likes);
                apiSendResponse($result);
            } else {
                apiSendResponse(['error' => 'Post ID required'], 400);
            }
            break;

        default:
            $logger->warning("Unsupported HTTP method for likes API", [
                'method' => $method,
                'supported_methods' => ['GET', 'POST']
            ]);
            apiSendResponse(['error' => 'Method not allowed', 'success' => false], 405);
    }
} catch (Exception $e) {
    $logger->logException($e, 'Critical error in likes API');
    apiSendResponse(['error' => 'Internal server error: ' . $e->getMessage(), 'success' => false], 500);
} catch (Throwable $e) {
    $logger->critical("Fatal error in likes API", [
        'error_message' => $e->getMessage(),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    apiSendResponse(['error' => 'Internal server error', 'success' => false], 500);
}