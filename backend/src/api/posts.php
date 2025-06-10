<?php
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../routes/web.php';

use App\model\Post;
use App\model\User;
use App\service\logger\Logger;

set_error_handler(function ($severity, $message, $file, $line) {
    $logger = Logger::getInstance();
    $logger->error("PHP Error in posts API: $message", [
        'severity' => $severity,
        'file' => $file,
        'line' => $line
    ]);
    return true;
});

$logger = Logger::getInstance();
$requestStart = microtime(true);

$logger->logRequest($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'] ?? '/api/posts', [
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

session_start();

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

try {

    $logger->info("Processing posts API request", [
        'method' => $method,
        'path' => $path,
        'path_parts' => $pathParts
    ]);
    switch ($method) {
        case 'GET':
            if (isset($_GET['user_id'])) {
                $userId = (int) $_GET['user_id'];
                $logger->info("Fetching posts for specific user", ['target_user_id' => $userId]);

                $startTime = microtime(true);
                $posts = Post::findByUserId($userId);
                $duration = (microtime(true) - $startTime) * 1000;

                $logger->logPerformance('user_posts_fetch', $duration, [
                    'user_id' => $userId,
                    'post_count' => count($posts)
                ]);

                $result = array_map(function ($post) {
                    return [
                        'id' => $post->getId(),
                        'user_id' => $post->getUserId(),
                        'title' => $post->getTitle(),
                        'content' => $post->getContent(),
                        'ascii_content' => $post->getAsciiContent(),
                        'type' => $post->getType(),
                        'visibility' => $post->getVisibility(),
                        'likes_count' => $post->getLikesCount(),
                        'created_at' => $post->getCreatedAt()
                    ];
                }, $posts);

                apiSendResponse($result);

            } elseif (isset($_GET['feed'])) {
                $userId = apiRequireAuth();
                $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;

                $logger->info("Fetching feed posts for user", [
                    'user_id' => $userId,
                    'limit' => $limit
                ]);

                $startTime = microtime(true);
                $posts = Post::getFeedForUser($userId, $limit);
                $duration = (microtime(true) - $startTime) * 1000;

                $logger->logPerformance('feed_fetch', $duration, [
                    'user_id' => $userId,
                    'post_count' => count($posts),
                    'limit' => $limit
                ]);

                $result = array_map(function ($post) {
                    return [
                        'id' => $post->getId(),
                        'user_id' => $post->getUserId(),
                        'username' => $post->getUsername(),
                        'title' => $post->getTitle(),
                        'content' => $post->getContent(),
                        'ascii_content' => $post->getAsciiContent(),
                        'type' => $post->getType(),
                        'visibility' => $post->getVisibility(),
                        'likes_count' => $post->getLikesCount(),
                        'created_at' => $post->getCreatedAt()
                    ];
                }, $posts);

                apiSendResponse($result);

            } else {
                $logger->info("vliza li tuka");
                $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
                $logger->info("Fetching recent posts", ['limit' => $limit]);

                $startTime = microtime(true);
                $posts = Post::getFeedForUser($userId);
                $duration = (microtime(true) - $startTime) * 1000;

                $logger->logPerformance('recent_posts_fetch', $duration, [
                    'post_count' => count($posts),
                    'limit' => $limit
                ]);

                $logger->info("fetch recent 123", [
                    'post_count' => count($posts),
                    'limit' => $limit
                ]);

                $result = array_map(function ($post) {
                    return [
                        'id' => $post->getId(),
                        'user_id' => $post->getUserId(),
                        'username' => $post->getUsername(),
                        'title' => $post->getTitle(),
                        'content' => $post->getContent(),
                        'ascii_content' => $post->getAsciiContent(),
                        'type' => $post->getType(),
                        'visibility' => $post->getVisibility(),
                        'likes_count' => $post->getLikesCount(),
                        'created_at' => $post->getCreatedAt()
                    ];
                }, $posts);
                apiSendResponse($result);
            }
            break;

        case 'POST':
            $logger->info("API post creation request received");
            $userId = apiRequireAuth();
            $logger->info("User authenticated for post creation", ['user_id' => $userId]);

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }

            $title = $input['title'] ?? '';
            $content = $input['content'] ?? '';
            $type = $input['type'] ?? 'ascii_art';
            $asciiContent = $input['ascii_content'] ?? null;
            $visibility = $input['visibility'] ?? 'public';

            if (empty($title) || empty($content)) {
                $logger->warning("Post creation validation failed", [
                    'user_id' => $userId,
                    'title_missing' => empty($title),
                    'content_missing' => empty($content)
                ]);
                apiSendResponse(['error' => 'Title and content are required'], 400);
            }

            $logger->info("Creating new post", [
                'user_id' => $userId,
                'title' => $title,
                'content_length' => strlen($content),
                'type' => $type,
                'visibility' => $visibility
            ]);

            try {
                $startTime = microtime(true);
                $post = Post::create($userId, $title, $content, $type, null, $asciiContent, $visibility);
                $duration = (microtime(true) - $startTime) * 1000;

                $logger->logPerformance('post_creation', $duration, [
                    'user_id' => $userId,
                    'post_id' => $post->getId()
                ]);

                $logger->info("Post created successfully", [
                    'post_id' => $post->getId(),
                    'user_id' => $userId,
                    'title' => $title
                ]);

                apiSendResponse([
                    'success' => true,
                    'post_id' => $post->getId(),
                    'post' => [
                        'id' => $post->getId(),
                        'user_id' => $post->getUserId(),
                        'title' => $post->getTitle(),
                        'content' => $post->getContent(),
                        'ascii_content' => $post->getAsciiContent(),
                        'type' => $post->getType(),
                        'visibility' => $post->getVisibility(),
                        'likes_count' => $post->getLikesCount(),
                        'created_at' => $post->getCreatedAt()
                    ]
                ]);

            } catch (Exception $e) {
                $logger->logException($e, 'Post creation failed');
                apiSendResponse(['error' => 'Failed to create post: ' . $e->getMessage()], 500);
            }
            break;

        case 'PUT':
            if (!isset($pathParts[2]) || !is_numeric($pathParts[2])) {
                $logger->warning("PUT request missing post ID", ['path_parts' => $pathParts]);
                apiSendResponse(['error' => 'Post ID required'], 400);
            }

            $postId = (int) $pathParts[2];
            $userId = apiRequireAuth();

            $logger->info("Post update request", [
                'post_id' => $postId,
                'user_id' => $userId
            ]);

            $post = Post::findById($postId);
            if (!$post) {
                $logger->warning("Post not found for update", ['post_id' => $postId]);
                apiSendResponse(['error' => 'Post not found'], 404);
            }

            if ($post->getUserId() !== $userId) {
                $logger->warning("Unauthorized post update attempt", [
                    'post_id' => $postId,
                    'post_owner' => $post->getUserId(),
                    'requester' => $userId
                ]);
                apiSendResponse(['error' => 'Not authorized to update this post'], 403);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }

            $changes = [];

            if (isset($input['title'])) {
                $post->setTitle($input['title']);
                $changes[] = 'title';
            }

            if (isset($input['content'])) {
                $post->setContent($input['content']);
                $changes[] = 'content';
            }

            if (isset($input['ascii_content'])) {
                $post->setAsciiContent($input['ascii_content']);
                $changes[] = 'ascii_content';
            }

            if (isset($input['visibility'])) {
                $post->setVisibility($input['visibility']);
                $changes[] = 'visibility';
            }

            if (isset($input['type'])) {
                $post->setType($input['type']);
                $changes[] = 'type';
            }

            if (empty($changes)) {
                $logger->warning("No changes provided for update", ['post_id' => $postId]);
                apiSendResponse(['error' => 'No changes provided'], 400);
            }

            $logger->info("Updating post", [
                'post_id' => $postId,
                'user_id' => $userId,
                'changes' => $changes
            ]);

            $startTime = microtime(true);
            $success = $post->save();
            $duration = (microtime(true) - $startTime) * 1000;

            $logger->logPerformance('post_update', $duration, [
                'post_id' => $postId,
                'user_id' => $userId,
                'changes_count' => count($changes)
            ]);

            if ($success) {
                $logger->info("Post updated successfully", [
                    'post_id' => $postId,
                    'user_id' => $userId,
                    'changes' => $changes
                ]);

                apiSendResponse([
                    'success' => true,
                    'post' => [
                        'id' => $post->getId(),
                        'user_id' => $post->getUserId(),
                        'title' => $post->getTitle(),
                        'content' => $post->getContent(),
                        'ascii_content' => $post->getAsciiContent(),
                        'type' => $post->getType(),
                        'visibility' => $post->getVisibility(),
                        'likes_count' => $post->getLikesCount(),
                        'created_at' => $post->getCreatedAt(),
                        'updated_at' => $post->getUpdatedAt()
                    ]
                ]);
            } else {
                $logger->error("Post update failed", [
                    'post_id' => $postId,
                    'user_id' => $userId
                ]);
                apiSendResponse(['error' => 'Failed to update post'], 500);
            }
            break;

        case 'DELETE':
            if (!isset($pathParts[2]) || !is_numeric($pathParts[2])) {
                $logger->warning("DELETE request missing post ID", ['path_parts' => $pathParts]);
                apiSendResponse(['error' => 'Post ID required'], 400);
            }

            $postId = (int) $pathParts[2];
            $userId = apiRequireAuth();

            $logger->info("Post deletion request", [
                'post_id' => $postId,
                'user_id' => $userId
            ]);

            $post = Post::findById($postId);
            if (!$post) {
                $logger->warning("Post not found for deletion", ['post_id' => $postId]);
                apiSendResponse(['error' => 'Post not found'], 404);
            }

            if ($post->getUserId() !== $userId) {
                $logger->warning("Unauthorized post deletion attempt", [
                    'post_id' => $postId,
                    'post_owner' => $post->getUserId(),
                    'requester' => $userId
                ]);
                apiSendResponse(['error' => 'Not authorized to delete this post'], 403);
            }

            $logger->info("Deleting post", [
                'post_id' => $postId,
                'user_id' => $userId,
                'title' => $post->getTitle()
            ]);

            $startTime = microtime(true);
            $success = $post->delete();
            $duration = (microtime(true) - $startTime) * 1000;

            $logger->logPerformance('post_deletion', $duration, [
                'post_id' => $postId,
                'user_id' => $userId
            ]);

            if ($success) {
                $logger->info("Post deleted successfully", [
                    'post_id' => $postId,
                    'user_id' => $userId
                ]);
                apiSendResponse(['success' => true, 'message' => 'Post deleted successfully']);
            } else {
                $logger->error("Post deletion failed", [
                    'post_id' => $postId,
                    'user_id' => $userId
                ]);
                apiSendResponse(['error' => 'Failed to delete post'], 500);
            }
            break;

        default:
            $logger->warning("Unsupported HTTP method for posts API", [
                'method' => $method,
                'supported_methods' => ['GET', 'POST', 'PUT', 'DELETE']
            ]);
            apiSendResponse(['error' => 'Method not allowed', 'success' => false], 405);
    }

} catch (Exception $e) {
    $logger->logException($e, 'Critical error in posts API');
    apiSendResponse(['error' => 'Internal server error: ' . $e->getMessage(), 'success' => false], 500);
} catch (Throwable $e) {
    $logger->critical("Fatal error in posts API", [
        'error_message' => $e->getMessage(),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    apiSendResponse(['error' => 'Internal server error', 'success' => false], 500);
}