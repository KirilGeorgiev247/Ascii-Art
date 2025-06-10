<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}
$title = "Search Users - ASCII Art Social Network";
ob_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/search_users.css" rel="stylesheet">
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-search"></i> Discover ASCII Artists</h1>
            <p>Find and connect with fellow ASCII art enthusiasts</p>
        </div>

        <div class="search-container">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search by username, email, or interests..."
                    onkeyup="searchUsers()">
                <i class="fas fa-search search-icon"></i>
            </div>
        </div>

        <div class="filters">
            <button class="filter-btn active" data-filter="all">All Users</button>
            <button class="filter-btn" data-filter="recent">Recently Active</button>
            <button class="filter-btn" data-filter="popular">Most Popular</button>
            <button class="filter-btn" data-filter="artists">Top Artists</button>
        </div>

        <div id="usersContainer" class="users-grid">
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>Start your search</h3>
                <p>Enter a username or keyword to find other ASCII art creators</p>
                <p><small>Try searching for "artist", "creative", or any username</small></p>
            </div>
        </div>

        <div style="margin-top: 40px; text-align: center;">
            <a href="/feed" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Feed
            </a>
            <a href="/friends" class="btn btn-primary">
                <i class="fas fa-users"></i> My Friends
            </a>
        </div>
    </div>
    <script src="/assets/js/search_users.js"></script>
</body>

</html>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout/layout.php';
?>