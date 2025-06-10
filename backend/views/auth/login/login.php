<?php
$title = "Login to ASCII Art Social";
$error = $error ?? '';
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - ASCII Art Social Network</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="/assets/css/login.css" rel="stylesheet" />
</head>

<body>
    <main class="auth-container">
        <section class="card">
            <header class="auth-header">
                <h1><i class="fas fa-palette"></i> Welcome Back</h1>
                <p>Sign in to your ASCII Art Social account</p>
            </header>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/login" id="loginForm">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email"
                        required />
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" class="form-control"
                        placeholder="Enter your password" required />
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <footer class="auth-links">
                <p>Don't have an account?
                    <a href="/register">Create one here</a>
                </p>
            </footer>
        </section>

        <pre class="ascii-art">
░█████╗░░██████╗░█████╗░██╗██╗
██╔══██╗██╔════╝██╔══██╗██║██║
███████║╚█████╗░██║░░╚═╝██║██║
██╔══██║░╚═══██╗██║░░██╗██║██║
██║░░██║██████╔╝╚█████╔╝██║██║
╚═╝░░╚═╝╚═════╝░░╚════╝░╚═╝╚═╝
        </pre>
    </main>

    <script src="/assets/js/login.js"></script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layout/layout.php';
?>