<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: dashboard.php');
    } else {
        header('Location: ../index.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (loginUser($username, $password)) {
        // Check if user is admin
        if (isAdmin()) {
            header('Location: dashboard.php');
            exit;
        } else {
            // Not an admin, logout and show error
            session_destroy();
            $error = 'Access denied. This login is for administrators only.';
        }
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - News Posting System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin-login.css">
</head>
<body class="admin-portal">
    <div class="auth-container">
        <div class="auth-box">
            <h1>🗞️ <span>Admin Portal</span></h1>
            <p class="subtitle">⚠️ Administrator Access Only</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">ADMIN USERNAME</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">ADMIN PASSWORD</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">🔐 Admin Login</button>
            </form>
            
            <div class="auth-toggle">
                <a href="../index.php">← Back to User Login</a>
            </div>
        </div>
    </div>
</body>
</html>
