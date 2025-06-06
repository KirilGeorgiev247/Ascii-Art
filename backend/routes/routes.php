<?php

use App\service\logger\Logger;
use App\controller\AuthController;
use App\controller\ProfileController;
use App\controller\FeedController;

$logger = Logger::getInstance();
$logger->debug("Loading routes", [
    'routes_file' => 'routes/routes.php',
    'timestamp' => date('Y-m-d H:i:s')
]);

// Basic routes
$router->get('/', function() use ($logger) {
    $logger->info("Home page accessed", [
        'user_id' => $_SESSION['user_id'] ?? null,
        'is_authenticated' => isset($_SESSION['user_id'])
    ]);
    
    if (isset($_SESSION['user_id'])) {
        $logger->info("Authenticated user accessing home - redirecting to feed", [
            'user_id' => $_SESSION['user_id']
        ]);
        header('Location: /feed');
        exit;
    }
    
    require_once __DIR__ . '/../views/home/home.php';
});

$router->get('/admin', function() use ($logger) {
    adminOnly();
    $logger->warning("Admin dashboard accessed", [
        'user_id' => $_SESSION['user_id'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    require_once __DIR__ . '/../views/admin/admin.php';
});

// Auth routes
$router->get('/login', function() use ($logger) {
    guestOnly();
    $logger->info("Login page route accessed", [
        'route' => '/login',
        'method' => 'GET',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    (new AuthController())->showLogin();
});

$router->post('/login', function() use ($logger) {
    guestOnly();
    $logger->info("Login form submission route accessed", [
        'route' => '/login',
        'method' => 'POST',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    (new AuthController())->login();
});

$router->get('/register', function() use ($logger) {
    guestOnly();
    $logger->info("Registration page route accessed", [
        'route' => '/register',
        'method' => 'GET',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    (new AuthController())->showRegister();
});

$router->post('/register', function() use ($logger) {
    guestOnly();
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

// Feed routes
$router->get('/feed', function() use ($logger) {
    requireAuth();
    $logger->info("Feed page route accessed", [
        'route' => '/feed',
        'method' => 'GET',
        'user_id' => $_SESSION['user_id']
    ]);
    (new FeedController())->index();
});

$router->post('/feed/post', function() use ($logger) {
    requireAuth();
    $logger->info("Creating new post", [
        'user_id' => $_SESSION['user_id']
    ]);
    (new FeedController())->createPost();
});

$router->post('/feed/like/{id}', function($id) use ($logger) {
    requireAuth();
    $logger->info("Liking/unliking post", [
        'user_id' => $_SESSION['user_id'],
        'post_id' => $id
    ]);
    (new FeedController())->likePost($id);
});

$router->delete('/feed/post/{id}', function($id) use ($logger) {
    requireAuth();
    $logger->info("Deleting post", [
        'user_id' => $_SESSION['user_id'],
        'post_id' => $id
    ]);
    (new FeedController())->deletePost($id);
});

$router->get('/feed/search', function() use ($logger) {
    requireAuth();
    $logger->info("Searching posts", [
        'user_id' => $_SESSION['user_id'],
        'query' => $_GET['q'] ?? ''
    ]);
    (new FeedController())->search();
});

// Profile routes
$router->get('/profile', function() use ($logger) {
    requireAuth();
    $logger->info("Profile page accessed (own)", [
        'user_id' => $_SESSION['user_id']
    ]);
    (new ProfileController())->index($_SESSION['user_id']);
});

$router->get('/profile/{id}', function($id) use ($logger) {
    requireAuth();
    $logger->info("Profile page accessed (other user)", [
        'user_id' => $_SESSION['user_id'],
        'profile_id' => $id
    ]);
    (new ProfileController())->index($id);
});

$router->post('/profile/edit', function() use ($logger) {
    requireAuth();
    $logger->info("Editing profile", [
        'user_id' => $_SESSION['user_id']
    ]);
    (new ProfileController())->edit();
});

$router->post('/profile/friend/add/{id}', function($id) use ($logger) {
    requireAuth();
    $logger->info("Sending friend request", [
        'user_id' => $_SESSION['user_id'],
        'friend_id' => $id
    ]);
    (new ProfileController())->addFriend($id);
});

$router->post('/profile/friend/accept/{id}', function($id) use ($logger) {
    requireAuth();
    $logger->info("Accepting friend request", [
        'user_id' => $_SESSION['user_id'],
        'friend_id' => $id
    ]);
    (new ProfileController())->acceptFriend($id);
});

$router->delete('/profile/friend/remove/{id}', function($id) use ($logger) {
    requireAuth();
    $logger->info("Removing friend", [
        'user_id' => $_SESSION['user_id'],
        'friend_id' => $id
    ]);
    (new ProfileController())->removeFriend($id);
});

$router->get('/profile/{id}/friends', function($id) use ($logger) {
    requireAuth();
    $logger->info("Viewing friends list", [
        'user_id' => $_SESSION['user_id'],
        'profile_id' => $id
    ]);
    (new ProfileController())->friends($id);
});

$router->get('/search/users', function() use ($logger) {
    requireAuth();
    $logger->info("Searching users", [
        'user_id' => $_SESSION['user_id'],
        'query' => $_GET['q'] ?? ''
    ]);
    (new ProfileController())->searchUsers();
});

// API routes for AJAX requests
$router->get('/api/posts', function() use ($logger) {
    $logger->debug("API: Getting posts");
    header('Content-Type: application/json');
    require_once __DIR__ . '/../api/posts.php';
});

$router->post('/api/posts', function() use ($logger) {
    $logger->debug("API: Posting to posts endpoint");
    header('Content-Type: application/json');
    require_once __DIR__ . '/../api/posts.php';
});

$router->get('/api/friends', function() use ($logger) {
    requireAuth();
    $logger->debug("API: Getting friends list");
    header('Content-Type: application/json');
    (new ProfileController())->apiFriends();
});

$router->get('/api/friend-requests', function() use ($logger) {
    requireAuth();
    $logger->debug("API: Getting friend requests");
    header('Content-Type: application/json');
    (new ProfileController())->apiFriendRequests();
});

// Static file serving for uploads
$router->get('/uploads/{filename}', function($filename) use ($logger) {
    $logger->debug("Serving file", ['filename' => $filename]);
    $filePath = __DIR__ . '/../uploads/' . $filename;
    if (file_exists($filePath)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
    } else {
        $logger->warning("File not found", ['filename' => $filename]);
        http_response_code(404);
        echo 'File not found';
    }
});

// Drawing/image processing routes (uncomment as needed)
/*
$router->get('/draw', function() use ($logger) {
    requireAuth();
    $logger->info("Draw page accessed", [
        'user_id' => $_SESSION['user_id']
    ]);
    (new DrawController())->index();
});

$router->get('/image', function() use ($logger) {
    requireAuth();
    $logger->info("Image conversion page accessed", [
        'user_id' => $_SESSION['user_id']
    ]);
    require_once __DIR__ . '/../views/image.php';
});
*/

$logger->debug("Routes loaded successfully");