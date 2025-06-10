<?php
$query = $_GET['q'] ?? '';
$posts = $posts ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - ASCII Art Social Network</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .ascii-art {
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            font-size: 0.7rem;
            line-height: 1.1;
            max-height: 200px;
            overflow: hidden;
        }
        .post-meta {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .navbar-brand {
            font-weight: bold;
            color: #495057 !important;
        }
        .search-result {
            transition: transform 0.2s;
        }
        .search-result:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-paint-brush me-2"></i>ASCII Art Social
            </a>
            <div class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="nav-link" href="/feed">Feed</a>
                    <a class="nav-link" href="/profile">Profile</a>
                    <a class="nav-link" href="/draw">Draw</a>
                    <a class="nav-link" href="/logout">Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="/login">Login</a>
                    <a class="nav-link" href="/register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- Search Header -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="mb-1">
                                    <i class="fas fa-search me-2"></i>Search Results
                                </h4>
                                <p class="text-muted mb-0">
                                    <?php if ($query): ?>
                                        Showing results for "<strong><?= htmlspecialchars($query) ?></strong>"
                                    <?php else: ?>
                                        Enter a search term to find posts
                                    <?php endif; ?>
                                </p>
                            </div>
                            <a href="/feed" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Feed
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Search Form -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" action="/search">
                            <div class="input-group">
                                <input type="text" class="form-control" name="q" 
                                       placeholder="Search for ASCII art, titles, or content..." 
                                       value="<?= htmlspecialchars($query) ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Results -->
                <?php if (empty($posts) && $query): ?>
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No results found</h5>
                            <p class="text-muted">Try different keywords or browse the <a href="/feed">main feed</a>.</p>
                        </div>
                    </div>
                <?php elseif (empty($posts)): ?>
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Start your search</h5>
                            <p class="text-muted">Enter keywords above to find amazing ASCII art!</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <small class="text-muted">Found <?= count($posts) ?> result(s)</small>
                    </div>

                    <?php foreach ($posts as $post): ?>
                        <div class="card shadow-sm mb-3 search-result">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3">
                                        <?php if (isset($post->profile_picture) && $post->profile_picture): ?>
                                            <img src="<?= htmlspecialchars($post->profile_picture) ?>" 
                                                 alt="Profile" class="rounded-circle" width="40" height="40">
                                        <?php else: ?>                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                                 style="width: 40px; height: 40px;">
                                                <?= strtoupper(substr($post->getUsername() ?? 'U', 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">
                                            <a href="/post/<?= $post->getId() ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($post->getTitle()) ?>
                                            </a>
                                        </h6>
                                        <div class="post-meta">
                                            By <?= htmlspecialchars($post->getUsername() ?? 'Unknown') ?> • 
                                            <?= date('M j, Y', strtotime($post->getCreatedAt())) ?>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($post->getContent()): ?>
                                    <p class="card-text">
                                        <?= nl2br(htmlspecialchars(substr($post->getContent(), 0, 200))) ?>
                                        <?php if (strlen($post->getContent()) > 200): ?>...<?php endif; ?>
                                    </p>
                                <?php endif; ?>

                                <?php if ($post->getAsciiContent()): ?>
                                    <div class="ascii-art mb-3">
<?= htmlspecialchars(substr($post->getAsciiContent(), 0, 500)) ?>
<?php if (strlen($post->getAsciiContent()) > 500): ?>
...
<?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <small class="text-muted me-3">
                                            <i class="fas fa-heart me-1"></i><?= $post->getLikesCount() ?>
                                        </small>
                                        <small class="text-muted">
                                            Type: <?= ucfirst(str_replace('_', ' ', $post->getType())) ?>
                                        </small>
                                    </div>
                                    <a href="/post/<?= $post->getId() ?>" class="btn btn-sm btn-outline-primary">
                                        View Post <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>