<?php
$error = $error ?? '';
$success = $success ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Register - ASCII Art Social Network</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
    <link href="/assets/css/register.css" rel="stylesheet"/>
</head>
<body>
    <main class="register-container">
        <header class="logo">
            <i class="fas fa-palette"></i>
            <h1>Join ASCII Art Network</h1>
        </header>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/register" id="registerForm">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <input type="text" id="username" name="username" required 
                    pattern="[a-zA-Z0-9_]{3,20}"
                    title="Username must be 3-20 characters long and contain only letters, numbers, and underscores"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"/>
            </div>

            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" id="email" name="email" required 
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" required minlength="8"/>
                <div class="strength-meter">
                    <div class="strength-fill" id="strengthFill"></div>
                </div>
                <div class="password-requirements" id="requirements">
                    <div class="requirement" id="req-length"><i class="fas fa-times"></i> At least 8 characters</div>
                    <div class="requirement" id="req-upper"><i class="fas fa-times"></i> One uppercase letter</div>
                    <div class="requirement" id="req-lower"><i class="fas fa-times"></i> One lowercase letter</div>
                    <div class="requirement" id="req-number"><i class="fas fa-times"></i> One number</div>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm"><i class="fas fa-lock"></i> Confirm Password</label>
                <input type="password" id="confirm" name="confirm" required/>
                <div id="confirmMessage" class="confirm-message"></div>
            </div>

            <button type="submit" class="register-btn" id="submitBtn">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <footer class="login-link">
            Already have an account? <a href="/login">Sign In</a>
        </footer>
    </main>

    <script src="/assets/js/register.js"></script>
</body>
</html>