<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

use App\model\User;
use App\model\Post;
use App\model\Friend;

$userId = $_SESSION['user_id'];
$currentUser = User::findById($userId);

$profileUserId = isset($_GET['user']) ? (int) $_GET['user'] : $userId;
$profileUser = User::findById($profileUserId);

if (!$profileUser) {
    header('Location: /feed');
    exit;
}

$isOwnProfile = $profileUserId === $userId;
$userPosts = Post::findByUserId($profileUserId);
$userFriends = Friend::getFriends($profileUserId);
$userLikes = User::getLikes($profileUserId);

$friendshipStatus = null;
if (!$isOwnProfile) {
    $friendship = Friend::getFriendship($userId, $profileUserId);
    $friendshipStatus = $friendship ? $friendship->getStatus() : null;
}

$title = htmlspecialchars($profileUser->getUsername()) . "'s Profile - ASCII Art Social Network";
ob_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($title) ?> - ASCII Art Social</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="/assets/css/profile.css" rel="stylesheet" />
    <link href="/assets/css/zoom.css" rel="stylesheet" />
</head>

<body>
    <main class="main-container">
        <aside class="profile-sidebar">
            <figure class="profile-avatar">
                <?php if ($profileUser->getProfilePicture()): ?>
                    <img src="<?= htmlspecialchars($profileUser->getProfilePicture()) ?>" alt="Profile Picture">
                <?php else: ?>
                    <span aria-label="User Initial"><?= strtoupper(substr($profileUser->getUsername(), 0, 1)) ?></span>
                <?php endif; ?>
                <figcaption class="online-status offline" id="userStatus-<?= $profileUser->getId() ?>"></figcaption>
            </figure>

            <section class="profile-info">
                <header>
                    <h1><?= htmlspecialchars($profileUser->getUsername()) ?></h1>
                    <p class="join-date">
                        <i class="fas fa-calendar-alt"></i>
                        Joined <?= date('F Y', strtotime($profileUser->getCreatedAt())) ?>
                    </p>
                </header>

                <ul class="profile-stats" role="list">
                    <li class="stat">
                        <span class="stat-number" id="postsCount"><?= count($userPosts) ?></span>
                        <span class="stat-label">Posts</span>
                    </li>
                    <li class="stat">
                        <span class="stat-number" id="friendsCount"><?= count($userFriends) ?></span>
                        <span class="stat-label">Friends</span>
                    </li>
                    <li class="stat">
                        <span class="stat-number" id="likesCount"><?= count($userLikes) ?></span>
                        <span class="stat-label">Likes</span>
                    </li>
                </ul>

                <?php if ($profileUser->getBio()): ?>
                    <blockquote class="profile-bio">
                        <i class="fas fa-quote-left"></i>
                        <?= htmlspecialchars($profileUser->getBio()) ?>
                        <i class="fas fa-quote-right"></i>
                    </blockquote>
                <?php endif; ?>

                <nav class="profile-actions" aria-label="Profile actions">
                    <?php if ($isOwnProfile): ?>
                        <a href="/draw" class="action-btn primary">
                            <i class="fas fa-paint-brush"></i>
                            Create Art
                        </a>
                        <a href="/profile/<?= $profileUserId ?>/friends" class="action-btn secondary" id="viewFriendsBtn">
                            <i class="fas fa-users"></i>
                            View Friends
                        </a>
                    <?php else: ?>
                        <button class="action-btn primary">
                            <i class="fas fa-user-plus"></i>
                            Add Friend
                        </button>
                        <button class="action-btn secondary">
                            <i class="fas fa-users"></i>
                            Collaborate
                        </button>
                    <?php endif; ?>
                </nav>
            </section>
        </aside>

        <section class="posts-section">
            <header class="posts-header">
                <h2>
                    <i class="fas fa-images"></i>
                    <?= $isOwnProfile ? 'Your ASCII Art' : $profileUser->getUsername() . "'s ASCII Art" ?>
                </h2>
            </header>

            <div class="posts-container" id="postsContainer">
                <?php if (empty($userPosts)): ?>
                    <section class="empty-state">
                        <i class="fas fa-palette"></i>
                        <h3><?= $isOwnProfile ? "You haven't created any ASCII art yet!" : $profileUser->getUsername() . " hasn't shared any art yet." ?>
                        </h3>
                        <?php if ($isOwnProfile): ?>
                            <p>Start creating amazing ASCII art and share it with the community!</p>
                            <a href="/draw" class="action-btn primary" style="margin-top: 1rem; display: inline-flex;">
                                <i class="fas fa-paint-brush"></i>
                                Create Your First Art
                            </a>
                        <?php endif; ?>
                    </section>
                <?php else: ?>
                    <?php foreach ($userPosts as $post): ?>
                        <?php $postId = $post->getId(); ?>
                        <article class="post" data-post-id="<?= $postId ?>">
                            <header class="post-header">
                                <time class="post-date" datetime="<?= date('c', strtotime($post->getCreatedAt())) ?>">
                                    <i class="fas fa-clock"></i>
                                    <?= date('M j, Y \a\t g:i A', strtotime($post->getCreatedAt())) ?>
                                </time>
                                <?php if ($isOwnProfile): ?>
                                    <button class="interaction-btn" onclick="deletePost(<?= $postId ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </header>

                            <?php if ($post->getTitle()): ?>
                                <h2 class="post-title"><?= htmlspecialchars($post->getTitle()) ?></h2>
                            <?php endif; ?>

                            <div class="zoom-control">
                                <label for="asciiZoom-<?= $postId ?>" class="zoom-label">
                                    <i class="fas fa-search-plus"></i> Zoom:
                                </label>
                                <input type="range" id="asciiZoom-<?= $postId ?>" min="0.5" max="24" value="12" step="0.5">
                                <span id="asciiZoomValue-<?= $postId ?>">12px</span>
                            </div>

                            <pre id="asciiOutput-<?= $postId ?>"
                                class="ascii-output"><?= htmlspecialchars($post->getAsciiContent() ?: $post->getContent()) ?></pre>

                            <footer class="post-interactions">
                                <div class="likes-count">
                                    <i class="fas fa-heart" style="color:#e25555"></i>
                                    <span id="likes-<?= $postId ?>"><?= $post->getLikesCount() ?></span>
                                    <span class="likes-label">likes</span>
                                </div>
                                <button class="interaction-btn" onclick="editInCanvas(<?= $postId ?>)">
                                    <i class="fas fa-paint-brush"></i>
                                    Edit
                                </button>
                            </footer>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <div class="connection-status disconnected" id="connectionStatus">
        <i class="fas fa-circle"></i>
        <span>Connecting...</span>
    </div>

    <script>
        // Pass PHP variables to JS
        window.profileUserId = <?= $userId ?>;
        window.profileProfileUserId = <?= $profileUserId ?>;
        window.profileIsOwnProfile = <?= $isOwnProfile ? 'true' : 'false' ?>;
    </script>
    <script src="/assets/js/profile.js"></script>
    <script src="/assets/js/zoom.js"></script>
</body>

</html>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout/layout.php';
?>