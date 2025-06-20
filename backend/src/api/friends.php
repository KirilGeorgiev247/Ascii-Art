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

use App\model\Friend;
use App\model\User;
use App\service\logger\Logger;

session_start();

$logger = Logger::getInstance();
$requestStart = microtime(true);

$logger->logRequest($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'] ?? '/api/friends', [
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

try {
    switch ($method) {
        case 'GET':
            $userId = apiRequireAuth();

            if (isset($_GET['pending'])) {
                $logger->info("Fetching pending friend requests", ['user_id' => $userId]);

                $startTime = microtime(true);
                $pendingRequests = Friend::getPendingRequests($userId);
                $duration = (microtime(true) - $startTime) * 1000;

                $logger->logPerformance('pending_requests_fetch', $duration, [
                    'user_id' => $userId,
                    'count' => count($pendingRequests)
                ]);

                $result = array_map(function ($request) {
                    $sender = User::findById($request->getUserId());
                    return [
                        'id' => $request->getId(),
                        'user_id' => $request->getUserId(),
                        'username' => $sender ? $sender->getUsername() : 'Unknown',
                        'status' => $request->getStatus(),
                        'created_at' => $request->getCreatedAt()
                    ];
                }, $pendingRequests);

                apiSendResponse($result);

            } elseif (isset($_GET['check']) && isset($_GET['friend_id'])) {
                $friendId = (int) $_GET['friend_id'];
                $logger->info("Checking friendship status", [
                    'user_id' => $userId,
                    'friend_id' => $friendId
                ]);

                $startTime = microtime(true);
                $status = Friend::getFriendshipStatus($userId, $friendId);
                $duration = (microtime(true) - $startTime) * 1000;

                $logger->logPerformance('friendship_status_check', $duration, [
                    'user_id' => $userId,
                    'friend_id' => $friendId
                ]);

                apiSendResponse([
                    'status' => $status,
                    'are_friends' => $status === 'accepted',
                    'is_pending' => $status === 'pending',
                    'user_id' => $userId,
                    'friend_id' => $friendId
                ]);

            } else {
                $logger->info("Fetching friends list", ['user_id' => $userId]);

                $startTime = microtime(true);
                $friends = Friend::getFriends($userId);
                $duration = (microtime(true) - $startTime) * 1000;

                $logger->logPerformance('friends_list_fetch', $duration, [
                    'user_id' => $userId,
                    'count' => count($friends)
                ]);

                $result = array_map(function ($friend) use ($userId) {
                    $friendUserId = $friend->getUserId() == $userId ? $friend->getFriendId() : $friend->getUserId();

                    $friendUser = User::findById($friendUserId);

                    return [
                        'id' => $friend->getId(),
                        'user_id' => $friendUserId,
                        'username' => $friendUser ? $friendUser->getUsername() : 'Unknown',
                        'profile_picture' => $friendUser ? $friendUser->getProfilePicture() : null,
                        'created_at' => $friend->getCreatedAt()
                    ];
                }, $friends);

                apiSendResponse($result);
            }
            break;

        case 'POST':
            if (isset($pathParts[2]) && $pathParts[2] === 'reject') {
                $userId = apiRequireAuth();
                $input = json_decode(file_get_contents('php://input'), true);

                if (!isset($input['friend_id']) || !is_numeric($input['friend_id'])) {
                    $logger->warning("Reject friend request missing friend_id", ['user_id' => $userId]);
                    apiSendResponse(['error' => 'Friend ID required'], 400);
                }

                $friendId = (int) $input['friend_id'];

                $logger->info("Rejecting friend request", [
                    'user_id' => $userId,
                    'friend_id' => $friendId
                ]);

                $success = Friend::removeFriendship($friendId, $userId);
                if ($success) {
                    apiSendResponse(['success' => true, 'message' => 'Friend request rejected']);
                } else {
                    apiSendResponse(['error' => 'Failed to reject friend request'], 500);
                }
                break;
            }

            if (isset($pathParts[2]) && $pathParts[2] === 'accept') {
                $userId = apiRequireAuth();
                $input = json_decode(file_get_contents('php://input'), true);

                if (!isset($input['friend_id']) || !is_numeric($input['friend_id'])) {
                    $logger->warning("Accept friend request missing friend_id", ['user_id' => $userId]);
                    apiSendResponse(['error' => 'Friend ID required'], 400);
                }

                $friendId = (int) $input['friend_id'];

                $logger->info("Accepting friend request", [
                    'user_id' => $userId,
                    'friend_id' => $friendId
                ]);

                $accepted = Friend::acceptFriendship($userId, $friendId);
                if ($accepted) {
                    apiSendResponse(['success' => true, 'message' => 'Friend request accepted']);
                } else {
                    apiSendResponse(['error' => 'Failed to accept friend request'], 500);
                }
                break;
            }

            if (isset($pathParts[2]) && $pathParts[2] === 'remove') {
                $userId = apiRequireAuth();
                $input = json_decode(file_get_contents('php://input'), true);

                if (!isset($input['friend_id']) || !is_numeric($input['friend_id'])) {
                    $logger->warning("Remove friend missing friend_id", ['user_id' => $userId]);
                    apiSendResponse(['error' => 'Friend ID required'], 400);
                }

                $friendId = (int) $input['friend_id'];

                $logger->info("Removing friend", [
                    'user_id' => $userId,
                    'friend_id' => $friendId
                ]);

                $success = Friend::removeFriendship($userId, $friendId);
                if ($success) {
                    apiSendResponse(['success' => true, 'message' => 'Friend removed']);
                } else {
                    apiSendResponse(['error' => 'Failed to remove friend'], 500);
                }
                break;
            }

            if (isset($pathParts[2]) && $pathParts[2] === 'add') {
                $userId = apiRequireAuth();
                $input = json_decode(file_get_contents('php://input'), true);

                if (!isset($input['friend_id']) || !is_numeric($input['friend_id'])) {
                    $logger->warning("Friend request missing friend_id", ['user_id' => $userId]);
                    apiSendResponse(['error' => 'Friend ID required'], 400);
                }

                $friendId = (int) $input['friend_id'];

                if ($userId === $friendId) {
                    $logger->warning("User attempted to friend themselves", ['user_id' => $userId]);
                    apiSendResponse(['error' => 'Cannot send friend request to yourself'], 400);
                }

                $logger->info("Sending friend request", [
                    'user_id' => $userId,
                    'friend_id' => $friendId
                ]);

                $status = Friend::getFriendshipStatus($userId, $friendId);
                if ($status === 'accepted') {
                    apiSendResponse(['error' => 'Already friends', 'status' => 'accepted'], 400);
                } elseif ($status === 'pending') {
                    apiSendResponse(['error' => 'Friend request already pending', 'status' => 'pending'], 400);
                }

                $result = Friend::addFriend($userId, $friendId);
                if ($result) {
                    apiSendResponse(['success' => true, 'message' => 'Friend request sent']);
                } else {
                    apiSendResponse(['error' => 'Failed to send friend request'], 500);
                }
                break;
            }

            apiSendResponse(['error' => 'Invalid endpoint'], 404);
            break;

        case 'PUT':
            $userId = apiRequireAuth();
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['friend_id']) || !is_numeric($input['friend_id'])) {
                $logger->warning("Accept friend request missing friend_id", ['user_id' => $userId]);
                apiSendResponse(['error' => 'Friend ID required'], 400);
            }

            $friendId = (int) $input['friend_id'];

            $logger->info("Accepting friend request", [
                'user_id' => $userId,
                'friend_id' => $friendId
            ]);

            $startTime = microtime(true);

            $status = Friend::getFriendshipStatus($friendId, $userId);
            if ($status !== 'pending') {
                $logger->warning("No pending friend request to accept", [
                    'user_id' => $userId,
                    'friend_id' => $friendId,
                    'status' => $status
                ]);
                apiSendResponse(['error' => 'No pending friend request found'], 400);
            }

            $success = Friend::acceptFriendship($userId, $friendId);
            $duration = (microtime(true) - $startTime) * 1000;

            $logger->logPerformance('friend_request_accept', $duration, [
                'user_id' => $userId,
                'friend_id' => $friendId
            ]);

            if ($success) {
                $logger->info("Friend request accepted successfully", [
                    'user_id' => $userId,
                    'friend_id' => $friendId
                ]);
                apiSendResponse(['success' => true, 'message' => 'Friend request accepted']);
            } else {
                $logger->error("Failed to accept friend request", [
                    'user_id' => $userId,
                    'friend_id' => $friendId
                ]);
                apiSendResponse(['error' => 'Failed to accept friend request'], 500);
            }
            break;

        case 'DELETE':
            $userId = apiRequireAuth();

            if (!isset($pathParts[2]) || !is_numeric($pathParts[2])) {
                $logger->warning("DELETE request missing friend ID", ['path_parts' => $pathParts]);
                apiSendResponse(['error' => 'Friend ID required'], 400);
            }

            $friendId = (int) $pathParts[2];

            $logger->info("Removing friend or canceling request", [
                'user_id' => $userId,
                'friend_id' => $friendId
            ]);

            $startTime = microtime(true);
            $success = Friend::removeFriendship($userId, $friendId);
            $duration = (microtime(true) - $startTime) * 1000;

            $logger->logPerformance('friend_removal', $duration, [
                'user_id' => $userId,
                'friend_id' => $friendId
            ]);

            if ($success) {
                $logger->info("Friend removed successfully", [
                    'user_id' => $userId,
                    'friend_id' => $friendId
                ]);
                apiSendResponse(['success' => true, 'message' => 'Friend removed']);
            } else {
                $logger->error("Failed to remove friend", [
                    'user_id' => $userId,
                    'friend_id' => $friendId
                ]);
                apiSendResponse(['error' => 'Failed to remove friend'], 500);
            }
            break;

        default:
            $logger->warning("Unsupported HTTP method for friends API", [
                'method' => $method,
                'supported_methods' => ['GET', 'POST', 'PUT', 'DELETE']
            ]);
            apiSendResponse(['error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    $logger->logException($e, 'Friends API error');
    apiSendResponse(['error' => $e->getMessage()], 500);
}