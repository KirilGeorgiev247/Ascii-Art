<?php
$title = $title ?? 'ASCII Art Social Network';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($title) ?> - ASCII Art Social</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Fira+Code:wght@300;400;500&display=swap" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

  <link rel="stylesheet" href="/assets/css/layout.css" />
</head>
<body>
  <?php if (isset($_SESSION['user_id'])): ?>
 <header class="header">
    <div class="header-content">
      <a href="/feed" class="logo"><i class="fas fa-palette"></i> ASCII Art Social - Layout</a>
      <nav class="nav-links">
        <a href="/feed" class="<?= basename($_SERVER['REQUEST_URI']) === 'feed' ? 'active' : '' ?>"><i class="fas fa-home"></i> Feed</a>
        <a href="/draw" class="<?= basename($_SERVER['REQUEST_URI']) === 'draw' ? 'active' : '' ?>"><i class="fas fa-paint-brush"></i> Draw</a>
        <a href="/image" class="<?= basename($_SERVER['REQUEST_URI']) === 'image' ? 'active' : '' ?>"><i class="fas fa-image"></i> Convert</a>
        <a href="/profile" class="<?= basename($_SERVER['REQUEST_URI']) === 'profile' ? 'active' : '' ?>"><i class="fas fa-user"></i> Profile</a>
        <a href="/logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </nav>
    </div>
  </header>
  <?php endif; ?>

  <main class="main-content">
    <div class="container">
      <?= $content ?>
    </div>
  </main>

  <?php if (!isset($_SESSION['user_id'])): ?>
  <footer class="footer">
    <div class="container">
      <p>&copy; <?= date('Y') ?> ASCII Art Social Network. Transform your images into beautiful ASCII art and share with friends.</p>
    </div>
  </footer>
  <?php endif; ?>

  <script src="/assets/js/layout.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>