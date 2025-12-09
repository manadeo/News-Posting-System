<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireLogin();

$success = '';
$error = '';

// Handle post deletion (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $postId = $_POST['post_id'] ?? 0;
    
    if (deletePost($postId, getCurrentUserId(), false)) {
        // If AJAX request, return JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => true]);
            exit();
        }
        $success = 'Post deleted successfully!';
    } else {
        // If AJAX request, return JSON error
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => false, 'error' => 'Failed to delete post']);
            exit();
        }
        $error = 'Failed to delete post.';
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
    $parentId = $_POST['parent_id'] ?? null;
    
    // Check if AJAX request
    if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || isset($_POST['ajax'])) {
        
        header('Content-Type: application/json');
        
        if (!$comment || !$postId) {
            echo json_encode(['success' => false, 'error' => 'Comment and post ID are required']);
            exit();
        }
        
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
        createComment($postId, $_SESSION['user_id'], $comment, $parentId);
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle comment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    $commentId = $_POST['comment_id'] ?? 0;
    deleteComment($commentId, $_SESSION['user_id'], false);
    
    // Check if AJAX request
    if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || isset($_POST['ajax'])) {
        echo json_encode(['success' => true]);
        exit();
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get user's posts
$myPosts = getPosts(null, getCurrentUserId());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Posts - News Posting System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                        </div>
                        <span><?php echo $_SESSION['username']; ?></span>
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
            
            <!-- Welcome Header -->
            <div class="dashboard-header">
                <div>
                    <h1 class="dashboard-title">📝 My Posts</h1>
                    <p class="dashboard-subtitle">Manage and track your submitted posts</p>
                </div>
                <div class="dashboard-stats-mini">
                    <span class="mini-stat">
                        <strong><?php echo count($myPosts); ?></strong> Total Posts
                    </span>
                </div>
            </div>
            
            <div class="content-section">
                <div class="section-header">
                    <h2>📋 My Posts (<?php echo count($myPosts); ?>)</h2>
                    <a href="dashboard.php" class="btn btn-primary btn-sm">+ Create Post</a>
                </div>
                
                <?php if (empty($myPosts)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📝</div>
                        <h3>You haven't created any posts yet</h3>
                        <p>Start sharing your news with the community!</p>
                        <a href="dashboard.php" class="btn btn-primary mt-20">Create Your First Post</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($myPosts as $post): ?>
                        <div class="post-card" data-post-id="<?php echo $post['id']; ?>">
                            <div class="post-header">
                                <div class="post-author">
                                    <div class="post-author-avatar">
                                        <?php echo strtoupper(substr($post['username'], 0, 1)); ?>
                                    </div>
                                    <div class="post-author-info">
                                        <h4><?php echo htmlspecialchars($post['username']); ?> (You)</h4>
                                        <span class="post-time"><?php echo timeAgo($post['created_at']); ?></span>
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
                                <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="btn btn-secondary btn-sm" style="background: var(--primary); color: white;">
                                    ✏️ Edit Post
                                </a>
                                <button type="button" class="btn btn-danger btn-sm"
                                        onclick="deletePostAjax(<?php echo $post['id']; ?>)">
                                    🗑 Delete Post
                                </button>
                            </div>
                            
                            <?php 
                            // Include comments section - users can delete their own comments
                            $isAdmin = false;
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
