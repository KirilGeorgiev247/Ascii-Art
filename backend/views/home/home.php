<?php
$title = "Welcome to ASCII Art Social Network";
ob_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($title) ?> - ASCII Art Social</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="/assets/css/home.css" rel="stylesheet" />
</head>

<body>
    <main>
        <section class="hero-section">
            <div class="container">
                <h1>
                    <i class="fas fa-palette"></i> ASCII Art Social
                </h1>
                <p class="hero-subtitle">
                    Transform your images into beautiful ASCII art and share with friends
                </p>
                <div class="hero-buttons">
                    <a href="/register" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Get Started
                    </a>
                    <a href="/login" class="btn btn-outline">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                </div>
            </div>
        </section>

        <section class="features-section">
            <h2 class="section-title">Powerful Features</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-image"></i></div>
                    <h3>Image Conversion</h3>
                    <p>Upload any image and convert it to stunning ASCII art using advanced algorithms including edge
                        detection and color reduction.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-paint-brush"></i></div>
                    <h3>Creative Tools</h3>
                    <p>Use advanced algorithms like flood fill, color changing, and edge detection to create unique
                        ASCII masterpieces.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-users"></i></div>
                    <h3>Social Network</h3>
                    <p>Share your ASCII art with friends, like and comment on posts, and discover amazing creations from
                        the community.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-download"></i></div>
                    <h3>Import & Export</h3>
                    <p>Export your ASCII art in multiple formats including TXT, HTML, JSON, and SVG for use anywhere.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-cogs"></i></div>
                    <h3>Smart Algorithms</h3>
                    <p>Powered by Sobel, Prewitt, Roberts, and Laplacian edge detection with customizable symbol sets.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-user-circle"></i></div>
                    <h3>Personal Profiles</h3>
                    <p>Create your profile, add friends, manage your ASCII art gallery, and build your creative
                        community.</p>
                </div>
            </div>
        </section>

        <section class="demo-section">
            <div class="container">
                <h2 class="section-title">See ASCII Art in Action</h2>
                <pre class="ascii-art-demo">
⠀⠀⠀⠀⠀⢀⣀⣤⣴⣶⣶⣿⣿⣿⣿⣿⣿⣿⣿⣶⣶⣤⣀⡀⠀⠀⠀⠀⠀
⠀⠀⢀⣤⣾⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣷⣤⡀⠀⠀
⠀⣴⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣦⠀
⣸⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣇
⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡿⠟⠛⠉⠉⠛⠻⢿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿
⣿⣿⣿⣿⣿⣿⣿⣿⠟⠉⠀⠀⠀⠀⠀⠀⠀⠀⠉⠻⣿⣿⣿⣿⣿⣿⣿⣿⣿
⣿⣿⣿⣿⣿⣿⡿⠃⠀⠀⢀⣀⣀⣀⣀⣀⣀⡀⠀⠀⠘⢿⣿⣿⣿⣿⣿⣿⣿
⣿⣿⣿⣿⣿⡟⠀⠀⣠⣾⣿⣿⣿⣿⣿⣿⣿⣿⣷⣄⠀⠀⢻⣿⣿⣿⣿⣿⣿
⣿⣿⣿⣿⣿⠃⠀⢰⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡆⠀⠘⣿⣿⣿⣿⣿⣿
⣿⣿⣿⣿⡏⠀⠀⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⠀⠀⢹⣿⣿⣿⣿⣿
⣿⣿⣿⣿⡇⠀⠀⢻⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡟⠀⠀⢸⣿⣿⣿⣿⣿
⣿⣿⣿⣿⣷⠀⠀⠈⢿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡿⠁⠀⠀⣾⣿⣿⣿⣿⣿
⣿⣿⣿⣿⣿⣷⡀⠀⠀⠙⠻⣿⣿⣿⣿⣿⣿⠟⠋⠀⠀⢀⣾⣿⣿⣿⣿⣿⣿
⠈⢿⣿⣿⣿⣿⣿⣷⣄⡀⠀⠀⠉⠛⠛⠉⠀⠀⢀⣠⣾⣿⣿⣿⣿⣿⣿⡿⠁
⠀⠀⠙⢿⣿⣿⣿⣿⣿⣿⣶⣤⣄⣀⣀⣠⣤⣶⣿⣿⣿⣿⣿⣿⣿⡿⠋⠀⠀
⠀⠀⠀⠀⠙⠻⢿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡿⠟⠋⠀⠀⠀⠀
                </pre>
                <p class="demo-caption">
                    This is just a preview! Create your own ASCII masterpieces and share them with the world.
                </p>
            </div>
        </section>

        <section class="cta-section">
            <h2>Ready to Get Creative?</h2>
            <p>Join our community of ASCII artists and start creating today!</p>
            <a href="/register" class="btn btn-primary">
                <i class="fas fa-rocket"></i> Join Now - It's Free!
            </a>
        </section>
    </main>
    <script src="/assets/js/home.js"></script>
</body>

</html>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout/layout.php';
?>