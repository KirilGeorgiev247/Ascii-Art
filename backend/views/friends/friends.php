<?php

$hideHeader = true;
$minimalLayout = true;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION['user_id'];

if (!isset($userId)) {
    header('Location: /login');
    exit;
}

use App\model\User;

$title = "Friends - ASCII Art Social Network";

$friendUserIds = [];
foreach ($friends as $friend) {
    $friendUserIds[] = $friend->getUserId() == $userId ? $friend->getFriendId() : $friend->getUserId();
}
$friendUserIds[] = $userId; // Exclude yourself as well

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="/assets/css/friends.css" rel="stylesheet" />
</head>

<body>
    <main class="main-container">
        <header class="page-header">
            <h1><i class="fas fa-users"></i> Friends</h1>
            <p>Manage your connections</p>
        </header>

        <nav class="tabs" aria-label="Friends navigation">
            <button class="tab active" onclick="showTab('friends')">
                <i class="fas fa-users"></i> Friends (<?= count($friends) ?>)
            </button>
            <button class="tab" onclick="showTab('requests')">
                <i class="fas fa-user-clock"></i> Pending Requests (<?= count($pendingRequests) ?>)
            </button>
            <button class="tab" onclick="showTab('search')">
                <i class="fas fa-search"></i> Find Friends
            </button>
        </nav>

        <section id="friends" class="tab-content active" aria-labelledby="friends-tab">
            <?php if (empty($friends)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-friends"></i>
                    <h3>No friends yet</h3>
                    <p>Start connecting with other ASCII art enthusiasts!</p>
                    <button id="findFriendsBtn" class="btn btn-primary" onclick="showTab('search')">
                        <i class="fas fa-search"></i> Find Friends
                    </button>
                </div>
            <?php else: ?>
                <div class="friends-grid">
                    <?php foreach ($friends as $friend): ?>
                        <?php
                        $friendUserId = $friend->getUserId() == $userId ? $friend->getFriendId() : $friend->getUserId();
                        $friendUser = User::findById($friendUserId);
                        ?>
                        <article class="friend-card">
                            <div class="friend-avatar">
                                <?= $friendUser ? strtoupper(substr($friendUser->getUsername(), 0, 1)) : '?' ?>
                            </div>
                            <div class="friend-name">
                                <?= $friendUser ? htmlspecialchars($friendUser->getUsername()) : 'Unknown' ?>
                            </div>
                            <div class="friend-status">
                                <i class="fas fa-palette"></i> ASCII Artist
                            </div>
                            <div class="friend-actions">
                                <a href="/profile/<?= $friendUserId ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> View Profile
                                </a>
                                <button class="btn btn-danger" onclick="removeFriend(<?= $friendUserId ?>)">
                                    <i class="fas fa-user-minus"></i> Remove
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section id="requests" class="tab-content" aria-labelledby="requests-tab">
            <?php if (empty($pendingRequests)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No pending requests</h3>
                    <p>You're all caught up!</p>
                </div>
            <?php else: ?>
                <div class="friends-grid">
                    <?php foreach ($pendingRequests as $request): ?>
                        <?php
                        $requestUser = User::findById($request->getUserId());
                        ?>
                        <article class="friend-card">
                            <div class="friend-avatar">
                                <?= $requestUser ? strtoupper(substr($requestUser->getUsername(), 0, 1)) : '?' ?>
                            </div>
                            <div class="friend-name">
                                <?= $requestUser ? htmlspecialchars($requestUser->getUsername()) : 'Unknown' ?>
                            </div>
                            <div class="friend-status">
                                <i class="fas fa-clock"></i> Wants to be friends
                            </div>
                            <div class="friend-actions">
                                <button class="btn btn-success"
                                    onclick="acceptFriend(<?= $requestUser ? $requestUser->getId() : 0 ?>)">
                                    <i class="fas fa-check"></i> Accept
                                </button>
                                <button class="btn btn-danger"
                                    onclick="rejectFriend(<?= $requestUser ? $requestUser->getId() : 0 ?>)">
                                    <i class="fas fa-times"></i> Decline
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section id="search" class="tab-content" aria-labelledby="search-tab">
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search for users..." onkeyup="searchUsers()">
            </div>
            <div id="searchResults" class="friends-grid">
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>Search for friends</h3>
                    <p>Enter a username to find other ASCII art enthusiasts</p>
                </div>
            </div>
        </section>

        <footer style="margin-top: 40px; text-align: center;">
            <a href="/feed" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Feed
            </a>
        </footer>
    </main>
    <script src="/assets/js/friends.js"></script>
    <script>
        window.friendUserIds = <?= json_encode($friendUserIds) ?>;
    </script> <!-- TODO make this to be returned from the controller -->
</body>

</html>

<?php
require_once __DIR__ . '/../layout/layout.php';
?>