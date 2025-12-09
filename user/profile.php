<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireLogin();

$user = getUser($_SESSION['user_id']);
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $contactNumber = $_POST['contact_number'] ?? '';
    
    if (updateUserProfile($_SESSION['user_id'], $firstName, $lastName, $contactNumber)) {
        $success = 'Profile updated successfully!';
        // Refresh user data
        $user = getUser($_SESSION['user_id']);
    } else {
        $error = 'Failed to update profile. Please try again.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_credentials'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newUsername = $_POST['username'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate
    if ($newPassword && $newPassword !== $confirmPassword) {
        $error = 'New passwords do not match';
    } else {
        $result = updateUserCredentials($_SESSION['user_id'], $currentPassword, $newUsername, $newPassword ?: null);
        if ($result['success']) {
            $success = 'Account credentials updated successfully!';
            // Refresh user data
            $user = getUser($_SESSION['user_id']);
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - News Posting System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .profile-header-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 40px;
            border-radius: var(--radius-xl);
            text-align: center;
            margin-bottom: 30px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }
        
        .profile-header-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        .profile-avatar-large {
            width: 100px;
            height: 100px;
            background: var(--white);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 800;
            margin: 0 auto 20px;
            box-shadow: var(--shadow-md);
        }
        
        .profile-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 10px;
        }
        
        .profile-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .profile-form-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-xl);
            padding: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .readonly-field {
            background: var(--gray-100);
            cursor: not-allowed;
            color: var(--gray-600);
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <nav class="navbar">
            <div class="navbar-content">
                <a href="dashboard.php" class="navbar-brand">🗞️ <span>News Posting System</span></a>
                <div class="navbar-menu">
                    <a href="dashboard.php">Newsfeed</a>
                    <a href="my-posts.php">My Posts</a>
                    <div class="user-info" onclick="window.location.href='profile.php'" style="cursor: pointer;">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                        </div>
                        <span><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
                </div>
            </div>
        </nav>
        
        <div class="container profile-container">
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="profile-header-card">
                <div class="profile-avatar-large">
                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                </div>
                <h1 class="profile-title"><?php echo htmlspecialchars($user['username']); ?></h1>
                <p class="profile-subtitle">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
            </div>
            
            <div class="profile-form-section">
                <div class="section-header">
                    <h2>👤 Personal Information</h2>
                    <span class="helper-text">Update your personal details</span>
                </div>
                
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" class="readonly-field" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="readonly-field" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" placeholder="Enter your first name">
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" placeholder="Enter your last name">
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_number">Contact Number</label>
                            <input type="tel" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>" placeholder="Enter your contact number">
                        </div>
                        
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" value="<?php echo ucfirst($user['role']); ?>" class="readonly-field" readonly>
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            💾 Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Account Security Section -->
            <div class="profile-form-section" style="margin-top: 30px;">
                <div class="section-header">
                    <h2>🔒 Account Security</h2>
                    <span class="helper-text">Update your login credentials</span>
                </div>
                
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password (leave empty to keep current)</label>
                            <input type="password" id="new_password" name="new_password" placeholder="New password">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
                        </div>
                        
                        <div class="form-group">
                            <label for="current_password">Current Password (Required)</label>
                            <input type="password" id="current_password" name="current_password" required placeholder="To verify it's you">
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" name="update_credentials" class="btn btn-warning">
                            🔑 Update Credentials
                        </button>
                    </div>
                </form>
            </div>
            
        </div>
    </div>
</body>
</html>
