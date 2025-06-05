<?php

use App\service\logger\Logger;
use \App\controller\AuthController;
use \App\controller\ProfileController;

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
    
    require_once __DIR__ . '/../views/home.php';
});

// TODO: impl
$router->get('/admin', function() use ($logger) {
    $logger->warning("Admin dashboard accessed", [
        'user_id' => $_SESSION['user_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    require_once __DIR__ . '/../views/admin.php';
});

$router->get('/login', function() {
    guestOnly();
    (new AuthController())->showLogin();
});

$router->post('/login', function() {
    guestOnly();
    (new AuthController())->login();
});

$router->get('/register', function() {
    guestOnly();
    (new AuthController())->showRegister();
});

$router->post('/register', function() {
    guestOnly();
    (new AuthController())->register();
});

$router->get('/logout', function() {
    (new AuthController())->logout();
});

// // Feed routes
// $router->get('/feed', function() {
//     requireAuth();
//     (new \App\controller\FeedController())->index();
// });

// $router->post('/feed/post', function() {
//     requireAuth();
//     (new \App\controller\FeedController())->createPost();
// });

// $router->post('/feed/like/{id}', function($id) {
//     requireAuth();
//     (new \App\controller\FeedController())->likePost($id);
// });

// $router->delete('/feed/post/{id}', function($id) {
//     requireAuth();
//     (new \App\controller\FeedController())->deletePost($id);
// });

// $router->get('/feed/search', function() {
//     requireAuth();
//     (new \App\controller\FeedController())->search();
// });

$router->get('/profile', function() {
    requireAuth();
    (new ProfileController())->index($_SESSION['user_id']);
});

$router->get('/profile/{id}', function($id) {
    requireAuth();
    (new ProfileController())->index($id);
});

$router->post('/profile/edit', function() {
    requireAuth();
    (new ProfileController())->edit();
});

$router->post('/profile/friend/add/{id}', function($id) {
    requireAuth();
    (new ProfileController())->addFriend($id);
});

$router->post('/profile/friend/accept/{id}', function($id) {
    requireAuth();
    (new ProfileController())->acceptFriend($id);
});

$router->delete('/profile/friend/remove/{id}', function($id) {
    requireAuth();
    (new ProfileController())->removeFriend($id);
});

$router->get('/profile/{id}/friends', function($id) {
    requireAuth();
    (new ProfileController())->friends($id);
});

$router->get('/search/users', function() {
    requireAuth();
    (new ProfileController())->searchUsers();
});

// // Drawing routes
// $router->get('/draw', function() {
//     requireAuth();
//     (new DrawController())->index();
// });

// $router->post('/draw/save', function() {
//     requireAuth();
//     (new DrawController())->save();
// });

// $router->post('/draw/flood-fill', function() {
//     requireAuth();
//     (new DrawController())->floodFill();
// });

// $router->post('/draw/color-change', function() {
//     requireAuth();
//     (new DrawController())->colorChange();
// });

// $router->post('/draw/edge-detection', function() {
//     requireAuth();
//     (new DrawController())->edgeDetection();
// });

// $router->get('/draw/presets', function() {
//     requireAuth();
//     (new DrawController())->getPresets();
// });

// $router->get('/draw/palettes', function() {
//     requireAuth();
//     (new DrawController())->getPalettes();
// });

// // Image processing routes
// $router->get('/image', function() {
//     requireAuth();
//     require_once __DIR__ . '/../views/image.php';
// });

// $router->post('/image/upload', function() {
//     requireAuth();
//     (new \App\controller\ImageController())->upload();
// });

// $router->post('/image/convert', function() {
//     requireAuth();
//     (new \App\controller\ImageController())->convert();
// });

// $router->post('/image/save-post', function() {
//     requireAuth();
//     (new \App\controller\ImageController())->saveAsPost();
// });

// $router->get('/image/export/{id}', function($id) {
//     requireAuth();
//     (new \App\controller\ImageController())->export($id);
// });

// API routes for AJAX requests
$router->get('/api/posts', function() {
    header('Content-Type: application/json');
    require_once __DIR__ . '/../api/posts.php';
});

$router->post('/api/posts', function() {
    header('Content-Type: application/json');
    require_once __DIR__ . '/../api/posts.php';
});

$router->get('/api/friends', function() {
    requireAuth();
    header('Content-Type: application/json');
    (new ProfileController())->apiFriends();
});

$router->get('/api/friend-requests', function() {
    requireAuth();
    header('Content-Type: application/json');
    (new ProfileController())->apiFriendRequests();
});

// Static file serving for uploads
$router->get('/uploads/{filename}', function($filename) {
    $filePath = __DIR__ . '/../uploads/' . $filename;
    if (file_exists($filePath)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
    } else {
        http_response_code(404);
        echo 'File not found';
    }
});