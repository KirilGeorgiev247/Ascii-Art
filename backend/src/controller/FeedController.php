<?php

namespace App\controller;

use App\db\repository\post\PostRepository;
use App\model\Post;
use App\model\User;
use App\model\Friend;
use App\model\Like;
use App\service\logger\Logger;
use Exception;

class FeedController
{
    public function index()
    {
        $logger = Logger::getInstance();
        $logger->logRequest($_SERVER['REQUEST_METHOD'], '/feed');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            $logger->warning("Unauthorized access to feed - redirecting to login");
            header('Location: /login');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $logger->info("Loading feed for user", ['user_id' => $userId]);

        try {
            $user = User::findById($userId);
            if (!$user) {
                $logger->error("User not found for session", ['user_id' => $userId]);
                header('Location: /login');
                exit;
            }

            $logger->debug("User found, loading feed data", [
                'user_id' => $userId,
                'username' => $user->getUsername()
            ]);

            $posts = Post::getFeedForUser($userId, 20);
            $logger->info("Feed posts loaded", [
                'user_id' => $userId,
                'post_count' => count($posts)
            ]);

            $pendingRequests = Friend::getPendingRequests($userId);
            $logger->debug("Friend requests loaded", [
                'user_id' => $userId,
                'pending_requests_count' => count($pendingRequests)
            ]);

            $logger->info("Feed data loaded successfully, rendering view", [
                'user_id' => $userId,
                'post_count' => count($posts),
                'pending_requests' => count($pendingRequests)
            ]);

            $viewPath = dirname(dirname(__DIR__)) . '/views/feed/feed.php';
            require_once $viewPath;
        } catch (Exception $e) {
            $logger->logException($e, 'Feed loading failed');
            header('Location: /error');
            exit;
        }
    }

    public function createPost()
    {
        $logger = Logger::getInstance();
        $logger->logRequest($_SERVER['REQUEST_METHOD'], '/feed/post');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            $logger->warning("Unauthorized post creation attempt");
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $logger->warning("Invalid method for post creation", ['method' => $_SERVER['REQUEST_METHOD']]);
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $userId = $_SESSION['user_id'];
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $asciiContent = $_POST['ascii_content'] ?? '';
        $type = $_POST['type'] ?? 'ascii_art';
        $visibility = $_POST['visibility'] ?? 'public';

        $logger->info("Post creation attempt", [
            'user_id' => $userId,
            'title' => $title,
            'content_length' => strlen($content),
            'ascii_content_length' => strlen($asciiContent),
            'type' => $type,
            'visibility' => $visibility
        ]);

        if (empty($title) || empty($content)) {
            $logger->warning("Post creation failed - missing required fields", [
                'user_id' => $userId,
                'title_empty' => empty($title),
                'content_empty' => empty($content)
            ]);
            http_response_code(400);
            echo json_encode(['error' => 'Title and content are required']);
            exit;
        }

        try {
            $startTime = microtime(true);
            $post = Post::create($userId, $title, $content, $type, null, $asciiContent, $visibility);
            $duration = (microtime(true) - $startTime) * 1000;

            $logger->logPerformance('post_creation', $duration, [
                'user_id' => $userId,
                'post_id' => $post->getId()
            ]);

            $logger->info("Post created successfully", [
                'user_id' => $userId,
                'post_id' => $post->getId(),
                'title' => $title,
                'type' => $type,
                'visibility' => $visibility
            ]);
            if ($_POST['ajax'] ?? false) {
                $logger->debug("Returning JSON response for post creation");
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Post created successfully',
                    'post_id' => $post->getId()
                ]);
            } else {
                $logger->debug("Redirecting to feed after post creation");
                header('Location: /feed');
            }
        } catch (Exception $e) {
            $logger->logException($e, 'Post creation failed');
            if ($_POST['ajax'] ?? false) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create post']);
            } else {
                header('Location: /feed?error=Failed to create post');
            }
        }
    }

    /**
     * Like a post by ID
     * Method signature updated to match web.php route parameter
     */
    public function likePost($id)
    {
        $logger = Logger::getInstance();
        $logger->logRequest($_SERVER['REQUEST_METHOD'], '/feed/like/' . $id);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            $logger->warning("Unauthorized like attempt");
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $userId = $_SESSION['user_id'];
        $postId = $id;

        if (!$postId) {
            http_response_code(400);
            echo json_encode(['error' => 'Post ID required']);
            exit;
        }

        try {
            $isLiked = Like::isPostLikedByUser($userId, $postId);

            if ($isLiked) {
                Like::unlikePost($userId, $postId);
                $action = 'unliked';
            } else {
                Like::likePost($userId, $postId);
                $action = 'liked';
            }

            $post = Post::findById($postId);
            $likesCount = $post ? $post->getLikesCount() : 0;

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'action' => $action,
                'likes_count' => $likesCount
            ]);

        } catch (Exception $e) {
            $logger->logException($e, 'Like operation failed');
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update like']);
        }
    }

    /**
     * Delete a post by ID
     * Method signature updated to match web.php route parameter
     */
    public function deletePost($id)
    {
        $logger = Logger::getInstance();
        $logger->logRequest($_SERVER['REQUEST_METHOD'], '/feed/post/' . $id);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            $logger->warning("Unauthorized post deletion attempt");
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $userId = $_SESSION['user_id'];
        $postId = $id;

        if (!$postId) {
            http_response_code(400);
            echo json_encode(['error' => 'Post ID required']);
            exit;
        }

        try {
            $post = Post::findById($postId);

            if (!$post) {
                $logger->warning("Post deletion failed - post not found", [
                    'user_id' => $userId,
                    'post_id' => $postId
                ]);
                http_response_code(404);
                echo json_encode(['error' => 'Post not found']);
                exit;
            }

            if ($post->getUserId() !== $userId) {
                $logger->warning("Post deletion failed - unauthorized", [
                    'user_id' => $userId,
                    'post_id' => $postId,
                    'post_owner_id' => $post->getUserId()
                ]);
                http_response_code(403);
                echo json_encode(['error' => 'Not authorized to delete this post']);
                exit;
            }

            $success = $post->delete();

            if ($success) {
                $logger->info("Post deleted successfully", [
                    'user_id' => $userId,
                    'post_id' => $postId
                ]);

                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
                } else {
                    header('Location: /profile/' . $userId);
                }
            } else {
                $logger->error("Post deletion failed", [
                    'user_id' => $userId,
                    'post_id' => $postId
                ]);
                throw new Exception('Failed to delete post');
            }

        } catch (Exception $e) {
            $logger->logException($e, 'Post deletion failed');

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete post']);
            } else {
                header('Location: /feed?error=Failed to delete post');
            }
        }
    }

    public function search()
    {
        $logger = Logger::getInstance();
        $logger->logRequest($_SERVER['REQUEST_METHOD'], '/feed/search');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            $logger->warning("Unauthorized search attempt");
            header('Location: /login');
            exit;
        }

        $query = $_GET['q'] ?? '';
        $posts = [];

        $logger->info("Search request", [
            'query' => $query,
            'user_id' => $_SESSION['user_id']
        ]);

        if (!empty($query)) {
            $posts = Post::searchByQuery($query);

            $logger->info("Search results", [
                'query' => $query,
                'result_count' => count($posts)
            ]);
        }

        $viewPath = dirname(dirname(__DIR__)) . '/views/search/search.php';
        require_once $viewPath;
    }
}