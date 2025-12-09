<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit();
}

$error = '';
$success = '';
$showRegister = isset($_GET['register']);

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (loginUser($username, $password)) {
        if (isAdmin()) {
            // Admin trying to login on user portal - don't reveal this
            session_destroy();
            $error = 'Invalid username or password';
        } else {
            header('Location: user/dashboard.php');
            exit();
        }
    } else {
        $error = 'Invalid username or password';
    }
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif (registerUser($username, $email, $password)) {
        $success = 'Registration successful! You can now login.';
        $showRegister = false;
    } else {
        $error = 'Username or email already exists';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Posting System - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <?php if (!$showRegister): ?>
                <!-- Login Form -->
                <h1>🗞️ <span>News Posting System</span></h1>
                <p class="subtitle">Sign in to continue</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username or Email</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" name="login" class="btn btn-primary">Login</button>
                </form>
                
                <div class="auth-toggle">
                    Don't have an account? <a href="?register=1">Register here</a>
                </div>
                
                <div class="auth-toggle" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--gray-200);">
                    <small style="color: var(--gray-500);">Administrator?</small>
                    <a href="admin/login.php">Admin Login →</a>
                </div>
            <?php else: ?>
                <!-- Register Form -->
                <h1>🗞️ <span>News Posting System</span></h1>
                <p class="subtitle">Create your account</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <button type="submit" name="register" class="btn btn-primary">Register</button>
                </form>
                
                <div class="alert alert-warning mt-20">
                    <strong>Note:</strong> Registration creates a user account. You can create posts that will be reviewed by the admin before being published.
                </div>
                
                <div class="auth-toggle">
                    Already have an account? <a href="index.php">Login here</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
