<?php
// Feed view with real-time WebSocket integration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

use App\model\Post;
use App\model\User;

$userId = $_SESSION['user_id'];
$posts = Post::fetchRecent(20);
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
</head>

<body>
    <main class="main-container">
        <section class="create-post">
            <h3><i class="fas fa-pencil-alt"></i> Share Your ASCII Art</h3>
            <textarea id="postContent" placeholder="Create some ASCII art...
    
    ╔══════════════════╗
    ║   Hello World!   ║
    ╚══════════════════╝
    
Try using tools below or visit the Draw page for collaborative editing!"></textarea>
            <div class="post-actions">
                <div class="ascii-tools">
                    <button class="tool-btn" onclick="insertTemplate('box')" title="Box Template">
                        <i class="fas fa-square"></i>
                    </button>
                    <button class="tool-btn" onclick="insertTemplate('heart')" title="Heart Template">
                        <i class="fas fa-heart"></i>
                    </button>
                    <button class="tool-btn" onclick="insertTemplate('star')" title="Star Template">
                        <i class="fas fa-star"></i>
                    </button>
                    <button class="tool-btn" onclick="showEmojiPicker()" title="ASCII Emojis">
                        <i class="fas fa-smile"></i>
                    </button>
                </div>
                <button class="post-btn" onclick="createPost()">
                    <i class="fas fa-paper-plane"></i>
                    Share Art
                </button>
            </div>
            <div class="emoji-picker" id="emojiPicker">
                <div class="emoji-grid">
                    <button class="emoji-btn" onclick="insertEmoji(':)')">:)</button>
                    <button class="emoji-btn" onclick="insertEmoji(':(')">:(</button>
                    <button class="emoji-btn" onclick="insertEmoji(':D')">:D</button>
                    <button class="emoji-btn" onclick="insertEmoji(':P')">:P</button>
                    <button class="emoji-btn" onclick="insertEmoji('&lt;3')">&lt;3</button>
                    <button class="emoji-btn" onclick="insertEmoji('(*)')">(*)</button>
                    <button class="emoji-btn" onclick="insertEmoji('(+)')">(+)</button>
                    <button class="emoji-btn" onclick="insertEmoji('(-)')">(-)</button>
                </div>
            </div>
        </section>

        <section class="posts-container" id="postsContainer">
            <?php foreach ($posts as $post): ?>
                <?php
                $postUser = User::findById($post->getUserId());
                $username = $postUser ? $postUser->getUsername() : 'Unknown User';
                ?>
                <article class="post" data-post-id="<?= $post->getId() ?>">
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
                            <button class="interaction-btn" onclick="togglePostMenu(<?= $post->getId() ?>)">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                        </div>
                    </div>

                    <?php if ($post->getTitle()): ?>
                        <h2 class="post-title"><?= htmlspecialchars($post->getTitle()) ?></h2>
                    <?php endif; ?>

                    <div class="post-content"><?= htmlspecialchars($post->getAsciiContent() ?: $post->getContent()) ?></div>

                    <div class="post-interactions">
                        <div class="interaction-buttons">
                            <button class="interaction-btn" onclick="likePost(<?= $post->getId() ?>)">
                                <i class="fas fa-heart"></i>
                                <span id="likes-<?= $post->getId() ?>">0</span>
                            </button>
                            <button class="interaction-btn" onclick="sharePost(<?= $post->getId() ?>)">
                                <i class="fas fa-share"></i>
                                Share
                            </button>
                            <button class="interaction-btn" onclick="copyToCanvas(<?= $post->getId() ?>)">
                                <i class="fas fa-paint-brush"></i>
                                Edit
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

    <aside class="online-users" id="onlineUsers">
        <h4><i class="fas fa-users"></i> Online Now</h4>
        <div id="usersList"></div>
    </aside>

    <div class="connection-status disconnected" id="connectionStatus">
        <i class="fas fa-circle"></i>
        <span>Connecting...</span>
    </div>

    <script>
        // Pass PHP variables to JavaScript
        const userId = <?= $userId ?>;
        const username = '<?= htmlspecialchars($currentUser->getUsername()) ?>';
    </script>
    <script src="/assets/js/feed.js"></script>
</body>

</html>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout/layout.php';
?>