<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

use App\model\Post;
use App\model\User;
use App\model\Like;

$userId = $_SESSION['user_id'];
$posts = Post::getFeedForUser($userId, 20);
$currentUser = User::findById($userId);

$title = "Feed - ASCII Art Social Network";
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/feed.css" rel="stylesheet">
    <link href="/assets/css/zoom.css" rel="stylesheet">
</head>

<body>
    <main class="main-container">
        <section class="posts-container" id="postsContainer">
            <?php foreach ($posts as $post): ?>
                <?php
                $postId = $post->getId();
                $postUser = User::findById($post->getUserId());
                $username = $postUser ? $postUser->getUsername() : 'Unknown User';
                $liked = Like::isPostLikedByUser($userId, $postId);
                ?>
                <article class="post" data-post-id="<?= $postId ?>">
                    <div class="post-header">
                        <div class="user-info">
                            <div class="user-avatar">
                                <?= strtoupper(substr($username, 0, 1)) ?>
                            </div>
                            <div class="user-details">
                                <h4><?= htmlspecialchars($username) ?></h4>
                                <div class="timestamp" data-timestamp="<?= $post->getCreatedAt() ?>">
                                    <?= date('M j, Y \a\t g:i A', strtotime($post->getCreatedAt())) ?>
                                </div>
                            </div>
                        </div>
                        <div class="post-menu">
                            <button class="interaction-btn" onclick="togglePostMenu(<?= $postId ?>)">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                        </div>
                    </div>

                    <?php if ($post->getTitle()): ?>
                        <h2 class="post-title"><?= htmlspecialchars($post->getTitle()) ?></h2>
                    <?php endif; ?>

                    <div class="zoom-control" style="margin-bottom:0.5rem;">
                        <label for="asciiZoom-<?= $postId ?>" class="zoom-label">
                            <i class="fas fa-search-plus"></i> Zoom:
                        </label>
                        <input type="range" id="asciiZoom-<?= $postId ?>" min="0.5" max="24" value="12" step="0.5">
                        <span id="asciiZoomValue-<?= $postId ?>">12px</span>
                    </div>

                    <pre id="asciiOutput-<?= $postId ?>" class="ascii-output"><?= htmlspecialchars($post->getAsciiContent() ?: $post->getContent()) ?></pre>

                    <div class="post-interactions">
                        <div class="interaction-buttons">
                            <button class="interaction-btn like-btn<?= $liked ? ' liked' : '' ?>" 
                                    id="like-btn-<?= $postId ?>"
                                    onclick="likePost(<?= $postId ?>)">
                                <i class="fas fa-heart"></i>
                                <span id="likes-<?= $postId ?>"><?= $post->getLikesCount() ?></span>
                            </button>
                            <button class="interaction-btn" onclick="sharePost(<?= $postId ?>)">
                                <i class="fas fa-share"></i>
                                Share
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if (empty($posts)): ?>
                <div class="loading">
                    <i class="fas fa-palette" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                    <p>No posts yet! Be the first to share some ASCII art.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <div class="connection-status disconnected" id="connectionStatus">
        <i class="fas fa-circle"></i>
        <span>Connecting...</span>
    </div>

    <script>
        const userId = <?= $userId ?>;
        const username = '<?= htmlspecialchars($currentUser->getUsername()) ?>';
    </script>
    <script src="/assets/js/feed.js"></script>
    <script src="/assets/js/zoom.js"></script>
</body>

</html>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout/layout.php';
?>