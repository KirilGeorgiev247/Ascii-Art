<?php

namespace App\controller;

use App\model\User;
use App\model\Post;
use App\model\Friend;
use App\db\repository\friend\FriendRepository;
use App\service\logger\Logger;
use Exception;

class ProfileController
{
    private FriendRepository $friendRepository;

    public function __construct()
    {
        $this->friendRepository = new FriendRepository();
    }

    public function index($userId = null)
    {
        $logger = Logger::getInstance();
        $logger->logRequest($_SERVER['REQUEST_METHOD'], '/profile');

        // TODO: check if we need this
        // if (session_status() === PHP_SESSION_NONE) {
        //     session_start();
        // }
        if (!isset($_SESSION['user_id'])) {
            $logger->warning("Unauthorized access to profile - redirecting to login");
            header('Location: /login');
            exit;
        }

        $currentUserId = $_SESSION['user_id'];
        $profileUserId = $userId ?? $currentUserId;
        $isOwnProfile = ($currentUserId === $profileUserId);

        $logger->info("Loading profile", [
            'current_user_id' => $currentUserId,
            'profile_user_id' => $profileUserId,
            'is_own_profile' => $isOwnProfile
        ]);

        $user = User::findById($profileUserId);
        if (!$user) {
            $logger->warning("Profile user not found", ['profile_user_id' => $profileUserId]);
            http_response_code(404);
            require_once dirname(dirname(__DIR__)) . '/views/errors/404.php';
            exit;
        }

        $logger->debug("Profile user found", [
            'profile_user_id' => $profileUserId,
            'username' => $user->getUsername()
        ]);

        $friendship = null;
        $areFriends = false;

        if (!$isOwnProfile) {
            $logger->debug("Checking friendship status", [
                'current_user_id' => $currentUserId,
                'profile_user_id' => $profileUserId
            ]);

            $friendship = $this->friendRepository->getFriendship($currentUserId, $profileUserId);
            $areFriends = $this->friendRepository->areFriends($currentUserId, $profileUserId);

            $logger->debug("Friendship status determined", [
                'are_friends' => $areFriends,
                'friendship_exists' => $friendship !== null
            ]);
        }

        $posts = [];
        if ($isOwnProfile || $areFriends) {
            $logger->debug("Loading all posts for authorized viewer");
            $posts = Post::findByUserId($profileUserId);
        } else {
            $logger->debug("Loading public posts for non-friend viewer");
            $posts = [];
            foreach (Post::findByUserId($profileUserId) as $post) {
                if ($post->getVisibility() === 'public') {
                    $posts[] = $post;
                }
            }
        }

        $logger->info("Posts loaded for profile", [
            'profile_user_id' => $profileUserId,
            'post_count' => count($posts),
            'is_own_profile' => $isOwnProfile,
            'are_friends' => $areFriends
        ]);

        try {
            $friends = $this->friendRepository->getFriends($profileUserId);
            $logger->debug("Friends list loaded", [
                'profile_user_id' => $profileUserId,
                'friends_count' => count($friends)
            ]);
        } catch (Exception $e) {
            $logger->logException($e, 'Failed to load friends list');
            $friends = [];
        }

        $logger->info("Profile data loaded successfully, rendering view", [
            'profile_user_id' => $profileUserId,
            'username' => $user->getUsername(),
            'post_count' => count($posts),
            'friends_count' => count($friends)
        ]);

        require_once dirname(dirname(__DIR__)) . '/views/profile.php';
    }

    public function edit()
    {
        $logger = Logger::getInstance();
        $logger->logRequest($_SERVER['REQUEST_METHOD'], '/profile/edit');

        // TODO: check if we need this
        // if (session_status() === PHP_SESSION_NONE) {
        //     session_start();
        // }
        if (!isset($_SESSION['user_id'])) {
            $logger->warning("Unauthorized access to profile edit - redirecting to login");
            header('Location: /login');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $logger->info("Loading profile edit for user", ['user_id' => $userId]);
        $user = User::findById($userId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $bio = $_POST['bio'] ?? '';

            $logger->info("Profile edit attempt", [
                'user_id' => $userId,
                'new_username' => $username,
                'new_email' => $email
            ]);

            $errors = [];

            if (empty($username)) {
                $errors[] = 'Username is required';
            } elseif ($username !== $user->getUsername()) {
                $existingUser = User::findByUsername($username);
                if ($existingUser) {
                    $logger->warning("Profile edit failed - username already taken", [
                        'user_id' => $userId,
                        'attempted_username' => $username
                    ]);
                    $errors[] = 'Username already taken';
                }
            }

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Valid email is required';
            } elseif ($email !== $user->getEmail()) {
                $existingUser = User::findByEmail($email);
                if ($existingUser) {
                    $logger->warning("Profile edit failed - email already registered", [
                        'user_id' => $userId,
                        'attempted_email' => $email
                    ]);
                    $errors[] = 'Email already registered';
                }
            }

            if (empty($errors)) {
                $user->setUsername($username);
                $user->setEmail($email);
                $user->setBio($bio);

                if ($user->save()) {
                    $_SESSION['success'] = 'Profile updated successfully';
                    $logger->info("Profile updated successfully", [
                        'user_id' => $userId,
                        'username' => $username,
                        'email' => $email
                    ]);
                    header('Location: /profile');
                    exit;
                } else {
                    $errors[] = 'Failed to update profile';
                    $logger->error("Failed to save profile changes", ['user_id' => $userId]);
                }
            } else {
                $logger->warning("Profile edit validation failed", [
                    'user_id' => $userId,
                    'errors' => $errors
                ]);
            }

            $_SESSION['errors'] = $errors;
        }

        $logger->debug("Rendering profile edit view", ['user_id' => $userId]);
        require_once dirname(dirname(__DIR__)) . '/views/profile_edit.php';
    }

    public function addFriend($id)
    {
        $logger = Logger::getInstance();
        $logger->logRequest($_SERVER['REQUEST_METHOD'], '/profile/friend/add/' . $id);

        if (!isset($_SESSION['user_id'])) {
            $logger->warning("Unauthorized friend addition attempt");
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $logger->warning("Invalid method for adding friend", ['method' => $_SERVER['REQUEST_METHOD']]);
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $userId = $_SESSION['user_id'];
        $friendId = $id;

        $logger->info("Friend addition attempt", [
            'user_id' => $userId,
            'friend_id' => $friendId
        ]);

        if (!$friendId || $friendId == $userId) {
            $logger->warning("Invalid friend ID for addition", [
                'user_id' => $userId,
                'friend_id' => $friendId
            ]);
            http_response_code(400);
            echo json_encode(['error' => 'Invalid friend ID']);
            exit;
        }

        try {
            $friend = $this->friendRepository->addFriend($userId, (int)$friendId);

            if ($friend) {
                $logger->info("Friend request sent successfully", [
                    'from_user_id' => $userId,
                    'to_user_id' => $friendId
                ]);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Friend request sent'
                ]);
            } else {
                $logger->warning("Friend request already exists", [
                    'from_user_id' => $userId,
                    'to_user_id' => $friendId
                ]);
                http_response_code(400);
                echo json_encode(['error' => 'Friend request already exists']);
            }
        } catch (Exception $e) {
            $logger->logException($e, 'Failed to send friend request');
            http_response_code(500);
            echo json_encode(['error' => 'Failed to send friend request']);
        }
    }

    public function acceptFriend($id)
    {
        $logger = Logger::getInstance();
        $logger->logRequest($_SERVER['REQUEST_METHOD'], '/profile/friend/accept/' . $id);

        if (!isset($_SESSION['user_id'])) {
            $logger->warning("Unauthorized friend acceptance attempt");
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $logger->warning("Invalid method for accepting friend", ['method' => $_SERVER['REQUEST_METHOD']]);
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $userId = $_SESSION['user_id'];
        $friendId = $id;

        $logger->info("Friend acceptance attempt", [
            'user_id' => $userId,
            'friend_id' => $friendId
        ]);

        if (!$friendId) {
            $logger->warning("Friend acceptance attempted without friend ID", ['user_id' => $userId]);
            http_response_code(400);
            echo json_encode(['error' => 'Friend ID required']);
            exit;
        }

        try {
            $success = $this->friendRepository->acceptFriendRequest($userId, (int)$friendId);

            if ($success) {
                $logger->info("Friend request accepted successfully", [
                    'user_id' => $userId,
                    'friend_id' => $friendId
                ]);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Friend request accepted'
                ]);
            } else {
                $logger->warning("Failed to accept friend request", [
                    'user_id' => $userId,
                    'friend_id' => $friendId
                ]);
                http_response_code(400);
                echo json_encode(['error' => 'Failed to accept friend request']);
            }
        } catch (Exception $e) {
            $logger->logException($e, 'Error accepting friend request');
            http_response_code(500);
            echo json_encode(['error' => 'Server error']);
        }
    }

    public function removeFriend($id)
    {
        $logger = Logger::getInstance();
        $logger->logRequest($_SERVER['REQUEST_METHOD'], '/profile/friend/remove/' . $id);

        if (!isset($_SESSION['user_id'])) {
            $logger->warning("Unauthorized friend removal attempt");
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $logger->warning("Invalid method for removing friend", ['method' => $_SERVER['REQUEST_METHOD']]);
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $userId = $_SESSION['user_id'];
        $friendId = $id;

        $logger->info("Friend removal attempt", [
            'user_id' => $userId,
            'friend_id' => $friendId
        ]);

        if (!$friendId) {
            $logger->warning("Friend removal attempted without friend ID", ['user_id' => $userId]);
            http_response_code(400);
            echo json_encode(['error' => 'Friend ID required']);
            exit;
        }

        try {
            $success = $this->friendRepository->removeFriend($userId, (int)$friendId);

            if ($success) {
                $logger->info("Friend removed successfully", [
                    'user_id' => $userId,
                    'friend_id' => $friendId
                ]);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Friend removed'
                ]);
            } else {
                $logger->warning("Failed to remove friend", [
                    'user_id' => $userId,
                    'friend_id' => $friendId
                ]);
                http_response_code(400);
                echo json_encode(['error' => 'Failed to remove friend']);
            }
        } catch (Exception $e) {
            $logger->logException($e, 'Error removing friend');
            http_response_code(500);
            echo json_encode(['error' => 'Server error']);
        }
    }

    public function friends($id)
    {
        $logger = Logger::getInstance();
        $logger->logRequest($_SERVER['REQUEST_METHOD'], '/profile/' . $id . '/friends');

        if (!isset($_SESSION['user_id'])) {
            $logger->warning("Unauthorized access to friends list - redirecting to login");
            header('Location: /login');
            exit;
        }

        $userId = $id;
        $logger->info("Loading friends list", ['user_id' => $userId]);

        try {
            $friends = $this->friendRepository->getFriends($userId);
            $pendingRequests = $this->friendRepository->getPendingRequests($userId);

            $logger->info("Friends list loaded successfully", [
                'user_id' => $userId,
                'friends_count' => count($friends),
                'pending_requests_count' => count($pendingRequests)
            ]);
        } catch (Exception $e) {
            $logger->logException($e, 'Failed to load friends list');
            $friends = [];
            $pendingRequests = [];
        }

        require_once dirname(dirname(__DIR__)) . '/views/friends.php';
    }

    public function apiFriends()
{
    $logger = Logger::getInstance();

    if (!isset($_SESSION['user_id'])) {
        $logger->warning("API friends: Unauthorized access attempt");
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $logger->info("API friends: Fetching friends", ['user_id' => $userId]);

    try {
        $friends = $this->friendRepository->getFriends($userId);
        header('Content-Type: application/json');
        echo json_encode(['friends' => $friends]);
    } catch (Exception $e) {
        $logger->logException($e, 'API friends: Failed to fetch friends');
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch friends']);
    }
}

public function apiFriendRequests()
{
    $logger = Logger::getInstance();

    if (!isset($_SESSION['user_id'])) {
        $logger->warning("API friend requests: Unauthorized access attempt");
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $logger->info("API friend requests: Fetching pending requests", ['user_id' => $userId]);

    try {
        $requests = $this->friendRepository->getPendingRequests($userId);
        header('Content-Type: application/json');
        echo json_encode(['requests' => $requests]);
    } catch (Exception $e) {
        $logger->logException($e, 'API friend requests: Failed to fetch requests');
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch friend requests']);
    }
}

    // TODO: delete or use
    // public function friendsList()
    // {
    //     $logger = Logger::getInstance();
    //     $logger->logRequest($_SERVER['REQUEST_METHOD'], '/profile/friendsList');

    //     // session_start();
    //     if (!isset($_SESSION['user_id'])) {
    //         $logger->warning("Unauthorized access to friends list - redirecting to login");
    //         header('Location: /login');
    //         exit;
    //     }

    //     $userId = $_SESSION['user_id'];
    //     $logger->info("Loading friends list", ['user_id' => $userId]);

    //     try {
    //         $friends = $this->friendRepository->getFriends($userId);
    //         $pendingRequests = $this->friendRepository->getPendingRequests($userId);

    //         $logger->info("Friends list loaded successfully", [
    //             'user_id' => $userId,
    //             'friends_count' => count($friends),
    //             'pending_requests_count' => count($pendingRequests)
    //         ]);
    //     } catch (Exception $e) {
    //         $logger->logException($e, 'Failed to load friends list');
    //         $friends = [];
    //         $pendingRequests = [];
    //     }

    //     require_once dirname(dirname(__DIR__)) . '/views/friends.php';
    // }

    public function searchUsers()
    {
        $logger = Logger::getInstance();
        $logger->logRequest($_SERVER['REQUEST_METHOD'], '/profile/searchUsers');

        // TODO: check if we need this
        // session_start();
        if (!isset($_SESSION['user_id'])) {
            $logger->warning("Unauthorized access to user search - redirecting to login");
            header('Location: /login');
            exit;
        }

        $query = $_GET['q'] ?? '';
        $users = [];

        if (!empty($query)) {
            $logger->info("User search initiated", [
                'user_id' => $_SESSION['user_id'],
                'query' => $query
            ]);

            try {
                $users = User::searchByUsername($query);
                // Optionally, you can enrich with friendship status using $this->friendRepository
            } catch (Exception $e) {
                $logger->logException($e, 'User search failed');
                $users = [];
            }
        } else {
            $logger->debug("User search executed with empty query", ['user_id' => $_SESSION['user_id']]);
        }

        $logger->debug("Rendering user search results", [
            'user_id' => $_SESSION['user_id'],
            'results_count' => count($users)
        ]);

        require_once dirname(dirname(__DIR__)) . '/views/search_users.php';
    }

    public function viewUser($userId)
    {
        $this->index($userId);
    }
}