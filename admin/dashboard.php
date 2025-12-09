<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireAdmin();

// Handle post approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $postId = $_POST['post_id'] ?? 0;
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        updatePostStatus($postId, 'approved');
        $success = 'Post approved successfully!';
    } elseif ($action === 'reject') {
        updatePostStatus($postId, 'rejected');
        $success = 'Post rejected successfully!';
    } elseif ($action === 'delete') {
        if (deletePost($postId, null, true)) {
            // If AJAX request, return JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => true]);
                exit();
            }
            $success = 'Post deleted successfully!';
        }
    }
}

// Handle post deletion (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $postId = $_POST['post_id'] ?? 0;
    
    if (deletePost($postId, null, true)) {
        // If AJAX request, return JSON
        if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || isset($_POST['ajax'])) {
            echo json_encode(['success' => true]);
            exit();
        }
    } else {
        // If AJAX request, return JSON error
        if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || isset($_POST['ajax'])) {
            echo json_encode(['success' => false, 'error' => 'Failed to delete post']);
            exit();
        }
    }
}

// Handle comment editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_comment'])) {
    $commentId = $_POST['comment_id'] ?? 0;
    $comment = $_POST['comment'] ?? '';
    
    // Check if AJAX request
    if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        
        $result = updateComment($commentId, $_SESSION['user_id'], $comment);
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update comment']);
        }
        exit();
    }
    
    // Non-AJAX fallback
    updateComment($commentId, $_SESSION['user_id'], $comment);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $postId = $_POST['post_id'] ?? 0;
    $comment = $_POST['comment'] ?? '';
    
    // Check if AJAX request
    if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || isset($_POST['ajax'])) {
        
        header('Content-Type: application/json');
        
        if (!$comment || !$postId) {
            echo json_encode(['success' => false, 'error' => 'Comment and post ID are required']);
            exit();
        }
        
        $parentId = $_POST['parent_id'] ?? null;
        $result = createComment($postId, $_SESSION['user_id'], $comment, $parentId);
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save comment']);
        }
        exit();
    }
    
    // Non-AJAX fallback
    if ($comment && $postId) {
        $parentId = $_POST['parent_id'] ?? null;
        createComment($postId, $_SESSION['user_id'], $comment, $parentId);
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle comment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    $commentId = $_POST['comment_id'] ?? 0;
    deleteComment($commentId, $_SESSION['user_id'], true);
    
    // Check if AJAX request
    if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || isset($_POST['ajax'])) {
        echo json_encode(['success' => true]);
        exit();
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle announcement creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_announcement'])) {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $scheduledAt = $_POST['scheduled_at'] ?? null;
    $imagePath = null;
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imagePath = uploadImage($_FILES['image']);
        if (!$imagePath) {
            $error = 'Failed to upload image.';
        }
    }
    
    if (!isset($error)) {
        // Create as announcement (1) and approved status
        if (createPost($_SESSION['user_id'], $title, $content, $imagePath, $scheduledAt, 1, 'approved')) {
            $success = 'Announcement posted successfully!';
        } else {
            $error = 'Failed to post announcement.';
        }
    }
}

// Get statistics
$stats = getStats();

// Get pending posts
$pendingPosts = getPosts('pending');

// Get all approved posts
$approvedPosts = getPosts('approved', null, 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - News Posting System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <!-- Navigation Bar -->
        <nav class="navbar">
            <div class="navbar-content">
                <a href="dashboard.php" class="navbar-brand"><span>News Posting System - Admin</span></a>
                <div class="navbar-menu">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="all-posts.php">All Posts</a>
                    <a href="users.php">Users</a>
                    <div class="user-info" onclick="window.location.href='profile.php'" style="cursor: pointer;">
                        <div class="user-avatar">A</div>
                        <span>admin</span>
                    </div>
                    <a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
                </div>
            </div>
        </nav>
        <div class="container">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Welcome Header -->
            <div class="dashboard-header">
                <div>
                    <h1 class="dashboard-title">👋 Welcome back, Admin!</h1>
                    <p class="dashboard-subtitle">Manage posts and monitor your news posting system</p>
                </div>
                <div class="dashboard-date">
                    📅 <?php echo date('F d, Y'); ?>
                </div>
            </div>
            
            <!-- Post Announcement Section -->
            <div class="content-section" style="margin-bottom: 24px;">
                <div class="section-header">
                    <h2>Post Announcement</h2>
                    <span class="helper-text">Create a public announcement for all users</span>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" required maxlength="255" placeholder="Announcement Title">
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" required placeholder="Write your announcement here..." style="min-height: 100px;"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="scheduled_at">Schedule (Optional)</label>
                        <input type="datetime-local" id="scheduled_at" name="scheduled_at">
                        <span class="helper-text" style="font-size: 0.8rem; margin-top: 4px; display: block;">Leave empty to publish immediately</span>
                    </div>

                    <div class="form-group">
                        <label>Image (Optional)</label>
                        <div class="file-upload" onclick="document.getElementById('announcement_image').click();">
                            <input type="file" id="announcement_image" name="image" accept="image/*" onchange="previewImage(this)">
                            <div class="file-upload-icon" style="font-size: 2rem;">Upload Image</div>
                            <div class="file-upload-text">Click to browse files</div>
                        </div>
                        <div id="imagePreview" class="file-preview"></div>
                    </div>
                    
                    <button type="submit" name="create_announcement" class="btn btn-primary" style="width: auto;">
                        Publish Announcement
                    </button>
                </form>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3>Total Users</h3>
                        <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                        <p class="stat-label">Registered Users</p>
                    </div>
                </div>
                <div class="stat-card stat-info">
                    <div class="stat-icon">📝</div>
                    <div class="stat-info">
                        <h3>Total Posts</h3>
                        <div class="stat-value"><?php echo $stats['total_posts']; ?></div>
                        <p class="stat-label">All Posts</p>
                    </div>
                </div>
                <div class="stat-card stat-warning">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3>Pending Posts</h3>
                        <div class="stat-value"><?php echo $stats['pending_posts']; ?></div>
                        <p class="stat-label">Awaiting Review</p>
                    </div>
                </div>
                <div class="stat-card stat-success">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <h3>Approved Posts</h3>
                        <div class="stat-value"><?php echo $stats['approved_posts']; ?></div>
                        <p class="stat-label">Published</p>
                    </div>
                </div>
            </div>
            
            <!-- Pending Posts Section -->
            <div class="content-section">
                <div class="section-header">
                    <h2>⏳ Pending Approval (<?php echo count($pendingPosts); ?>)</h2>
                </div>
                
                <?php if (empty($pendingPosts)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">✅</div>
                        <h3>No pending posts</h3>
                        <p>All posts have been reviewed</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pendingPosts as $post): ?>
                        <div class="post-card" data-post-id="<?php echo $post['id']; ?>">
                            <div class="post-header">
                                <div class="post-author">
                                    <div class="post-author-avatar">
                                        <?php echo strtoupper(substr($post['username'], 0, 1)); ?>
                                    </div>
                                    <div class="post-author-info">
                                        <h4><?php echo htmlspecialchars($post['username']); ?></h4>
                                        <span class="post-time"><?php echo timeAgo($post['created_at']); ?></span>
                                        <?php if ($post['updated_at'] && strtotime($post['updated_at']) > strtotime($post['created_at']) + 60): ?>
                                            <div class="post-edited-badge" style="font-size: 0.75rem; color: var(--warning); font-weight: 500;">
                                                ✏️ Edited <?php echo timeAgo($post['updated_at']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="post-status status-pending">Pending</span>
                                <?php if (!empty($post['scheduled_at'])): ?>
                                    <div style="margin-top: 5px; font-size: 0.75rem; color: var(--primary); font-weight: 600;">
                                        🕒 Scheduled: <?php echo date('M d, H:i', strtotime($post['scheduled_at'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                            <p class="post-content"><?php echo htmlspecialchars($post['content']); ?></p>
                            
                            <?php if ($post['image_path']): ?>
                                <img src="../<?php echo htmlspecialchars($post['image_path']); ?>" 
                                     alt="Post image" class="post-image">
                            <?php endif; ?>
                            
                            <div class="post-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">
                                        ✓ Approve
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" name="action" value="reject" class="btn btn-secondary btn-sm">
                                        ✗ Reject
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" id="deletePending_<?php echo $post['id']; ?>">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="button" class="btn btn-danger btn-sm"
                                            onclick="deletePostAjax(<?php echo $post['id']; ?>)">
                                        🗑 Delete
                                    </button>
                                </form>
                            </div>
                            
                            <?php 
                            // Include comments section - admin can delete all comments
                            $isAdmin = true;
                            include '../includes/comments.php';
                            ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Recent Approved Posts -->
            <div class="content-section">
                <div class="section-header">
                    <h2>✅ Recent Approved Posts</h2>
                    <a href="all-posts.php" class="btn btn-secondary btn-sm">View All</a>
                </div>
                
                <?php if (empty($approvedPosts)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <h3>No approved posts yet</h3>
                    </div>
                <?php else: ?>
                    <?php foreach ($approvedPosts as $post): ?>
                        <div class="post-card" data-post-id="<?php echo $post['id']; ?>">
                            <div class="post-header">
                                <div class="post-author">
                                    <div class="post-author-avatar">
                                        <?php echo strtoupper(substr($post['username'], 0, 1)); ?>
                                    </div>
                                    <div class="post-author-info">
                                        <h4><?php echo htmlspecialchars($post['username']); ?></h4>
                                        <span class="post-time"><?php echo timeAgo($post['created_at']); ?></span>
                                    </div>
                                </div>
                                <span class="post-status status-approved">Approved</span>
                            </div>
                            
                            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                            <p class="post-content"><?php echo htmlspecialchars($post['content']); ?></p>
                            
                            <?php if ($post['image_path']): ?>
                                <img src="../<?php echo htmlspecialchars($post['image_path']); ?>" 
                                     alt="Post image" class="post-image">
                            <?php endif; ?>
                            
                            <div class="post-actions">
                                <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="btn btn-secondary btn-sm" style="background: var(--primary); color: white; border: none; font-size: 0.8rem; padding: 4px 10px;">✏️ Edit</a>
                                
                                <form method="POST" style="display: inline;" id="deleteApproved_<?php echo $post['id']; ?>">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="button" class="btn btn-danger btn-sm"
                                            onclick="deletePostAjax(<?php echo $post['id']; ?>)">
                                        🗑 Delete
                                    </button>
                                </form>
                            </div>
                            
                            <?php 
                            // Include comments section - admin can delete all comments
                            $isAdmin = true;
                            include '../includes/comments.php';
                            ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include '../includes/modal.php'; ?>
    <?php include '../includes/toast.php'; ?>
    
    <script>
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                preview.appendChild(img);
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</body>
</html>
