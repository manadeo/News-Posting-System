<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireLogin();

$success = '';
$error = '';

// Handle new post creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $scheduledAt = !empty($_POST['scheduled_at']) ? str_replace('T', ' ', $_POST['scheduled_at']) : null;
    $imagePath = null;
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imagePath = uploadImage($_FILES['image']);
        if (!$imagePath) {
            $error = 'Failed to upload image. Only JPG, JPEG, PNG, and GIF are allowed.';
        }
    }
    
    if ($title && isHarmful($title) || $content && isHarmful($content)) {
        $error = 'Your post contains harmful content and cannot be published.';
    } elseif (!$error && createPost(getCurrentUserId(), $title, $content, $imagePath, $scheduledAt)) {
        $success = 'Post created successfully! Waiting for admin approval.';
    } elseif (!$error) {
        $error = 'Failed to create post. Please try again.';
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

// Get approved posts from all users (newsfeed)
$newsfeed = getPosts('approved', null, 20);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - News Posting System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <!-- Navigation Bar -->
        <nav class="navbar">
            <div class="navbar-content">
                <a href="dashboard.php" class="navbar-brand"><span>News Posting System</span></a>
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
                    <h1 class="dashboard-title">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
                    <p class="dashboard-subtitle">Share your news and stay updated with the latest posts</p>
                </div>
                <div class="dashboard-date">
                    <?php echo date('F d, Y'); ?>
                </div>
            </div>
            
            <div class="content-section">
                <div class="section-header">
                    <h2>Create New Post</h2>
                    <span class="helper-text">Share your news with the community</span>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Post Title</label>
                        <input type="text" id="title" name="title" required maxlength="255">
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Caption</label>
                        <textarea id="content" name="content" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="scheduled_at">Schedule Publication (Optional)</label>
                        <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="form-control" min="<?php echo date('Y-m-d\TH:i'); ?>">
                        <p class="helper-text" style="font-size: 0.8rem; margin-top: 5px; color: var(--gray-500);">
                            Leave empty to publish immediately upon approval.
                        </p>
                    </div>
                    
                    <div class="form-group">
                        <label>Image (Optional)</label>
                        <div class="file-upload" onclick="document.getElementById('image').click();">
                            <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                            <div class="file-upload-icon">Upload Image</div>
                            <div class="file-upload-text">Click to browse files</div>
                        </div>
                        <div id="imagePreview" class="file-preview"></div>
                    </div>
                    
                    <button type="submit" name="create_post" class="btn btn-primary">
                        Submit Post
                    </button>
                </form>
            </div>
            
            <div class="content-section">
                <div class="section-header">
                    <h2>📰 Latest News</h2>
                </div>
                
                <?php if (empty($newsfeed)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <h3>No posts yet</h3>
                        <p>Be the first to create a post!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($newsfeed as $post): ?>
                        <div class="post-card">
                            <div class="post-header">
                                <div class="post-author">
                                    <div class="post-author-avatar" style="<?php echo $post['is_announcement'] ? 'background: var(--primary);' : ''; ?>">
                                        <?php echo $post['is_announcement'] ? '📢' : strtoupper(substr($post['username'], 0, 1)); ?>
                                    </div>
                                    <div class="post-author-info">
                                        <h4><?php echo htmlspecialchars($post['username']); ?></h4>
                                        <div class="post-meta">
                                            <span class="post-time">
                                                <?php 
                                                $displayTime = $post['scheduled_at'] && strtotime($post['scheduled_at']) <= time() 
                                                    ? $post['scheduled_at'] 
                                                    : $post['created_at'];
                                                echo timeAgo($displayTime); 
                                                ?>
                                            </span>
                                            <?php if ($post['scheduled_at'] && strtotime($post['scheduled_at']) > time()): ?>
                                                <span class="status-pending" style="margin-left: 10px; font-size: 0.8rem;">Scheduled</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($post['is_announcement']): ?>
                                    <span class="post-status status-approved" style="background: var(--primary); color: white;">Announcement</span>
                                <?php else: ?>
                                    <span class="post-status status-approved">Published</span>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                            <p class="post-content"><?php echo htmlspecialchars($post['content']); ?></p>
                            
                            <?php if ($post['image_path']): ?>
                                <img src="../<?php echo htmlspecialchars($post['image_path']); ?>" 
                                     alt="Post image" class="post-image">
                            <?php endif; ?>
                            
                            <?php 
                            // Include comments section
                            $isAdmin = false;
                            include '../includes/comments.php';
                            ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
    <?php include '../includes/modal.php'; ?>
    <?php include '../includes/toast.php'; ?>
</body>
</html>
