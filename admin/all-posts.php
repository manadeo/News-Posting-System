<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireAdmin();

// Handle post deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['delete']) || isset($_POST['delete_post']))) {
    $postId = $_POST['post_id'] ?? 0;
    if (deletePost($postId, null, true)) {
        // If AJAX request, return JSON
        if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || isset($_POST['ajax'])) {
            echo json_encode(['success' => true]);
            exit();
        }
        $success = 'Post deleted successfully!';
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
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
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
        
        $result = createComment($postId, $_SESSION['user_id'], $comment);
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save comment']);
        }
        exit();
    }
    
    // Non-AJAX fallback
    if ($comment && $postId) {
        createComment($postId, $_SESSION['user_id'], $comment);
    }
    
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
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
    
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
    exit();
}

// Get filter
$filter = $_GET['filter'] ?? 'all';

// Get posts based on filter
if ($filter === 'all') {
    $posts = getPosts();
} else {
    $posts = getPosts($filter);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Posts - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <!-- Navigation Bar -->
        <nav class="navbar">
            <div class="navbar-content">
                <a href="dashboard.php" class="navbar-brand">🗞️ <span>News Posting System - Admin</span></a>
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
            
            <div class="content-section">
                <div class="section-header">
                    <h2>📋 All Posts (<?php echo count($posts); ?>)</h2>
                    <div class="filter-tabs">
                        <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                            📊 All
                        </a>
                        <a href="?filter=pending" class="filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                            ⏳ Pending
                        </a>
                        <a href="?filter=approved" class="filter-tab <?php echo $filter === 'approved' ? 'active' : ''; ?>">
                            ✅ Approved
                        </a>
                        <a href="?filter=rejected" class="filter-tab <?php echo $filter === 'rejected' ? 'active' : ''; ?>">
                            ❌ Rejected
                        </a>
                    </div>
                </div>
                
                <?php if (empty($posts)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <h3>No posts found</h3>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
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
                                <span class="post-status status-<?php echo $post['status']; ?>">
                                    <?php echo ucfirst($post['status']); ?>
                                </span>
                            </div>
                            
                            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                            <p class="post-content"><?php echo htmlspecialchars($post['content']); ?></p>
                            
                            <?php if ($post['image_path']): ?>
                                <img src="../<?php echo htmlspecialchars($post['image_path']); ?>" 
                                     alt="Post image" class="post-image">
                            <?php endif; ?>
                            
                            <div class="post-actions">
                                <form method="POST" style="display: inline;" id="deleteForm_<?php echo $post['id']; ?>">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <input type="hidden" name="delete" value="1">
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
</body>
</html>
