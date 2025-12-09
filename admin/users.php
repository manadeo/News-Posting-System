<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireAdmin();

$users = getAllUsers();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $userId = $_POST['user_id'] ?? 0;
    $username = $_POST['username'] ?? '';
    // Optional password update
    $password = !empty($_POST['password']) ? $_POST['password'] : null;
    
    // Validate
    if (!$userId || !$username) {
        $error = 'Username is required';
    } else {
        $result = adminUpdateUser($userId, $username, $password);
        if ($result['success']) {
            $success = 'User updated successfully!';
            $users = getAllUsers(); // Refresh list
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
    <title>User Management - News Posting System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <nav class="navbar">
            <div class="navbar-content">
                <a href="dashboard.php" class="navbar-brand">🗞️ <span>News Posting System - Admin</span></a>
                <div class="navbar-menu">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="all-posts.php">All Posts</a>
                    <a href="users.php" style="font-weight: bold; color: var(--primary);">Users</a>
                    <div class="user-info" onclick="window.location.href='profile.php'" style="cursor: pointer;">
                        <div class="user-avatar">A</div>
                        <span>admin</span>
                    </div>
                    <a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
                </div>
            </div>
        </nav>
        
        <div class="container">
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="section-header">
                <h2>User Management</h2>
                <span class="helper-text">Manage registered users</span>
            </div>
            
            <div class="content-section">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--gray-200);">
                                <th style="padding: 15px;">ID</th>
                                <th style="padding: 15px;">User</th>
                                <th style="padding: 15px;">Email</th>
                                <th style="padding: 15px;">Role</th>
                                <th style="padding: 15px;">Joined</th>
                                <th style="padding: 15px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr style="border-bottom: 1px solid var(--gray-100);">
                                    <td style="padding: 15px;"><?php echo $u['id']; ?></td>
                                    <td style="padding: 15px;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.9rem;">
                                                <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($u['username']); ?></div>
                                                <div style="font-size: 0.8rem; color: var(--gray-500);">
                                                    <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 15px;"><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td style="padding: 15px;">
                                        <span class="<?php echo $u['role'] === 'admin' ? 'status-approved' : 'status-pending'; ?>" 
                                              style="padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; background: var(--light); border: 1px solid var(--gray-300);">
                                            <?php echo ucfirst($u['role']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 15px; font-size: 0.9rem;">
                                        <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                                    </td>
                                    <td style="padding: 15px;">
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($u)); ?>)" 
                                                class="btn btn-secondary btn-sm">
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit User</h3>
                <span class="close-modal" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    <input type="hidden" name="update_user" value="1">
                    
                    <div class="form-group">
                        <label for="edit_username">Username</label>
                        <input type="text" id="edit_username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_password">New Password (Optional)</label>
                        <input type="password" id="edit_password" name="password" placeholder="Leave empty to keep current">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openEditModal(user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('editUserModal').style.display = 'block';
    }

    function closeEditModal() {
        document.getElementById('editUserModal').style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('editUserModal')) {
            closeEditModal();
        }
    }
    </script>
</body>
</html>
