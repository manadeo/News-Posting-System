<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireAdmin();

$postId = $_GET['id'] ?? 0;
$post = getPost($postId);

if (!$post) {
    header("Location: dashboard.php");
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_post'])) {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $scheduledAt = $_POST['scheduled_at'] ?? null;
    $isAnnouncement = isset($_POST['is_announcement']) ? 1 : 0;
    $imagePath = null;
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imagePath = uploadImage($_FILES['image']);
        if (!$imagePath) {
            $error = 'Failed to upload image. Only JPG, JPEG, PNG, and GIF are allowed.';
        }
    }
    
    if (!$error) {
        // Admins can update any post. passing true for isAdmin
        if (updatePost($postId, $_SESSION['user_id'], $title, $content, $imagePath, $scheduledAt, $isAnnouncement, true)) {
            $success = 'Post updated successfully!';
            // Refresh post data
            $post = getPost($postId);
        } else {
            $error = 'Failed to update post. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post (Admin) - News Posting System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <nav class="navbar">
            <div class="navbar-content">
                <a href="dashboard.php" class="navbar-brand"><span>News Posting System - Admin</span></a>
                <div class="navbar-menu">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="all-posts.php">All Posts</a>
                    <div class="user-info">
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
            
            <div class="content-section">
                <div class="section-header">
                    <h2>✏️ Edit Post</h2>
                    <a href="dashboard.php" class="btn btn-secondary btn-sm">← Back to Dashboard</a>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Post Title</label>
                        <input type="text" id="title" name="title" required maxlength="255" value="<?php echo htmlspecialchars($post['title']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="scheduled_at">Schedule (Optional)</label>
                        <input type="datetime-local" id="scheduled_at" name="scheduled_at" value="<?php echo $post['scheduled_at'] ? date('Y-m-d\TH:i', strtotime($post['scheduled_at'])) : ''; ?>">
                    </div>

                    <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="is_announcement" name="is_announcement" <?php echo $post['is_announcement'] ? 'checked' : ''; ?> style="width: auto;">
                        <label for="is_announcement" style="margin: 0; cursor: pointer;">Mark as Announcement</label>
                    </div>
                    
                    <div class="form-group">
                        <label>Current Image</label>
                        <?php if ($post['image_path']): ?>
                            <div class="mb-20">
                                <img src="../<?php echo htmlspecialchars($post['image_path']); ?>" alt="Current Image" style="max-height: 200px; border-radius: var(--radius-md);">
                            </div>
                        <?php else: ?>
                            <p class="helper-text mb-20">No image uploaded</p>
                        <?php endif; ?>
                        
                        <label>Update Image (Optional)</label>
                        <div class="file-upload" onclick="document.getElementById('image').click();" style="padding: 20px;">
                            <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                            <div class="file-upload-icon" style="font-size: 2rem;">📷</div>
                            <div class="file-upload-text">Click to change image</div>
                        </div>
                        <div id="imagePreview" class="file-preview"></div>
                    </div>
                    
                    <button type="submit" name="update_post" class="btn btn-primary">
                        💾 Save Changes
                    </button>
                </form>
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
</body>
</html>
