<?php
require_once __DIR__ . '/../config/database.php';

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Login user
function loginUser($username, $password) {
    $conn = getDBConnection();
    $username = sanitize($username);
    
    $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    
    return false;
}

// Register user
function registerUser($username, $email, $password) {
    $conn = getDBConnection();
    $username = sanitize($username);
    $email = sanitize($email);
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$username, $email, $hashedPassword]);
        return true;
    } catch (PDOException $e) {
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

// Get user by ID
function getUser($userId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, username, email, role, first_name, last_name, contact_number, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

// Update user profile
function updateUserProfile($userId, $firstName, $lastName, $contactNumber) {
    $conn = getDBConnection();
    $firstName = sanitize($firstName);
    $lastName = sanitize($lastName);
    $contactNumber = sanitize($contactNumber);
    
    $sql = "UPDATE users SET first_name = ?, last_name = ?, contact_number = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$firstName, $lastName, $contactNumber, $userId]);
    
    return $result;
}

// Update user credentials (username/password)
function updateUserCredentials($userId, $currentPassword, $newUsername, $newPassword = null) {
    $conn = getDBConnection();
    
    // 1. Verify current password
    $stmt = $conn->prepare("SELECT password, username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($currentPassword, $user['password'])) {
        return ['success' => false, 'error' => 'Incorrect current password'];
    }
    
    // 2. Check if username is changing and is unique
    if ($newUsername !== $user['username']) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$newUsername]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Username already taken'];
        }
    }
    
    // 3. Update details
    try {
        if ($newPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
            $stmt->execute([$newUsername, $hashedPassword, $userId]);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->execute([$newUsername, $userId]);
        }
        
        // Update session if username changed
        $_SESSION['username'] = $newUsername;
        
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database error'];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database error'];
    }
}

// Get all users (Admin only)
function getAllUsers() {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT id, username, email, role, first_name, last_name, created_at FROM users ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

// Admin Update User (Bypass current password check)
function adminUpdateUser($userId, $username, $password = null, $role = null) {
    $conn = getDBConnection();
    
    // Check username uniqueness if changing
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentUser = $stmt->fetch();
    
    if (!$currentUser) return ['success' => false, 'error' => 'User not found'];
    
    if ($username !== $currentUser['username']) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Username already taken'];
        }
    }
    
    try {
        $params = [$username];
        $sql = "UPDATE users SET username = ?";
        
        if ($password) {
            $sql .= ", password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        if ($role) {
            $sql .= ", role = ?";
            $params[] = $role;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $userId;
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database error'];
    }
}

// Check for harmful content
function isHarmful($text) {
    // Basic list of harmful/offensive keywords for demonstration
    $harmfulWords = [
        'violence', 'attack', 'kill', 'hate', 'racist', 
        'stupid', 'idiot', 'scam', 'fraud', 'harmful_test_word',
        'sex', 'porn', 'nude', 'naked', 'xxx', 'adult'
    ];
    
    $lowercaseText = strtolower($text);
    
    foreach ($harmfulWords as $word) {
        // Simple containment check. regex could be better but this suffices for basic reqs.
        if (strpos($lowercaseText, $word) !== false) {
            return true;
        }
    }
    
    return false;
}

// Get all posts (with filters)
function getPosts($status = null, $userId = null, $limit = null) {
    $conn = getDBConnection();
    
    $sql = "SELECT p.*, u.username FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE 1=1";
    
    $params = [];
    
    if ($status) {
        $sql .= " AND p.status = ?";
        $params[] = $status;
        
        // If getting approved posts, check for schedule
        // Admins viewing all posts vs public feed:
        // For simplicity, we assume if filtering by 'approved', we only show published unless specifically requested otherwise.
        // But wait, the previous logic was simple.
        // Let's ensure 'approved' means 'approved AND (scheduled_at IS NULL OR scheduled_at <= NOW())'
        // UNLESS we are in admin dashboard. 
        // But getPosts is general.
        // Let's assume for now this function is primarily for the feed.
        if ($status === 'approved') {
            $sql .= " AND (p.scheduled_at IS NULL OR p.scheduled_at <= NOW())";
        }
    }
    
    if ($userId) {
        $sql .= " AND p.user_id = ?";
        $params[] = $userId;
    }
    
    // Sort logic: 
    // Prioritize announcements, then created_at
    $sql .= " ORDER BY p.is_announcement DESC, p.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = (int)$limit;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Get single post
function getPost($postId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT p.*, u.username FROM posts p 
                           JOIN users u ON p.user_id = u.id 
                           WHERE p.id = ?");
    $stmt->execute([$postId]);
    return $stmt->fetch();
}

// Create post
// Create post
function createPost($userId, $title, $content, $imagePath = null, $scheduledAt = null, $isAnnouncement = 0, $status = 'pending') {
    $conn = getDBConnection();
    $title = sanitize($title);
    $content = sanitize($content);
    
    $sql = "INSERT INTO posts (user_id, title, content, image_path, status, scheduled_at, is_announcement) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    // Handle empty string for scheduledAt
    if (empty($scheduledAt)) {
        $scheduledAt = null;
    }
    
    $stmt->execute([$userId, $title, $content, $imagePath, $status, $scheduledAt, $isAnnouncement]);
    
    return $stmt->rowCount() > 0;
}

// Update post
function updatePost($postId, $userId, $title, $content, $imagePath = null, $scheduledAt = null, $isAnnouncement = 0, $isAdmin = false) {
    $conn = getDBConnection();
    $title = sanitize($title);
    $content = sanitize($content);
    
    // Check ownership
    $stmt = $conn->prepare("SELECT user_id, image_path FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    if (!$post) {
        return false;
    }

    // Check permissions: Owner OR Admin
    if (!$isAdmin && $post['user_id'] != $userId) {
        return false;
    }
    
    $params = [$title, $content];
    $sql = "UPDATE posts SET title = ?, content = ?";
    
    if ($imagePath) {
        $sql .= ", image_path = ?";
        $params[] = $imagePath;
        
        // Delete old image if it exists and is different
        if ($post['image_path'] && file_exists(__DIR__ . '/../' . $post['image_path'])) {
            unlink(__DIR__ . '/../' . $post['image_path']);
        }
    }

    // Update scheduled_at
    if (empty($scheduledAt)) {
        $scheduledAt = null;
    }
    $sql .= ", scheduled_at = ?";
    $params[] = $scheduledAt;

    // Update is_announcement (only if admin, but effectively we pass based on role/context in caller)
    // Actually, isAnnouncement should be updated too.
    $sql .= ", is_announcement = ?";
    $params[] = $isAnnouncement;
    
    // Status logic: 
    // If Admin, keep approved (or approved if it was pending? Admin edit effectively approves it usually).
    // Let's say if Admin, status = 'approved'. 
    // If User, content change -> 'pending'.
    
    if ($isAdmin) {
        $sql .= ", status = 'approved'";
    } else {
        $sql .= ", status = 'pending'";
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $postId;
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    return true; // Return true on success execution
}

// Update post status
function updatePostStatus($postId, $status) {
    $conn = getDBConnection();
    
    // If approving, also update the timestamp so it shows as new
    if ($status === 'approved') {
        $stmt = $conn->prepare("UPDATE posts SET status = ?, created_at = NOW() WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE posts SET status = ? WHERE id = ?");
    }
    
    $stmt->execute([$status, $postId]);
    return $stmt->rowCount() > 0;
}

// Delete post
function deletePost($postId, $userId = null, $isAdmin = false) {
    $conn = getDBConnection();
    
    // Get post to check ownership and delete image
    $post = getPost($postId);
    
    if (!$post) {
        return false;
    }
    
    // Check permissions
    if (!$isAdmin && $post['user_id'] != $userId) {
        return false;
    }
    
    // Delete image file if exists
    if ($post['image_path'] && file_exists(__DIR__ . '/../' . $post['image_path'])) {
        unlink(__DIR__ . '/../' . $post['image_path']);
    }
    
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    return $stmt->rowCount() > 0;
}

// Upload image
function uploadImage($file) {
    $targetDir = __DIR__ . "/../assets/uploads/";
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileExtension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowedExtensions = array("jpg", "jpeg", "png", "gif");
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        return false;
    }
    
    // Generate unique filename
    $newFileName = uniqid() . '_' . time() . '.' . $fileExtension;
    $targetFile = $targetDir . $newFileName;
    
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return "assets/uploads/" . $newFileName;
    }
    
    return false;
}

// Get stats for admin dashboard
function getStats() {
    $conn = getDBConnection();
    
    $stats = array();
    
    // Total users
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
    $result = $stmt->fetch();
    $stats['total_users'] = $result['count'];
    
    // Total posts
    $stmt = $conn->query("SELECT COUNT(*) as count FROM posts");
    $result = $stmt->fetch();
    $stats['total_posts'] = $result['count'];
    
    // Pending posts
    $stmt = $conn->query("SELECT COUNT(*) as count FROM posts WHERE status = 'pending'");
    $result = $stmt->fetch();
    $stats['pending_posts'] = $result['count'];
    
    // Approved posts
    $stmt = $conn->query("SELECT COUNT(*) as count FROM posts WHERE status = 'approved'");
    $result = $stmt->fetch();
    $stats['approved_posts'] = $result['count'];
    
    return $stats;
}

// Format time ago  
function timeAgo($datetime) {
    //Set timezone to Philippines
    date_default_timezone_set('Asia/Manila');
    
    $timestamp = strtotime($datetime);
    $current = time();
    $diff = $current - $timestamp;
    
    // Handle negative time differences (future dates due to timezone issues)
    if ($diff < 0) {
        $diff = abs($diff);
        // If it's a small negative difference, just show "Just now"
        if ($diff < 300) { // Less than 5 minutes in the future
            return "Just now";
        }
    }
    
    // Show "Just now" for very recent posts
    if ($diff < 10) {
        return "Just now";
    } elseif ($diff < 60) {
        return $diff . " seconds ago";
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ($minutes == 1 ? " minute ago" : " minutes ago");
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ($hours == 1 ? " hour ago" : " hours ago");
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ($days == 1 ? " day ago" : " days ago");
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ($weeks == 1 ? " week ago" : " weeks ago");
    } else {
        return date('M d, Y', $timestamp);
    }
}

// ========== COMMENT FUNCTIONS ==========

// Create comment
// Create comment
function createComment($postId, $userId, $comment, $parentId = null) {
    $conn = getDBConnection();
    $comment = sanitize($comment);
    
    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment, parent_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$postId, $userId, $comment, !empty($parentId) ? $parentId : null]);
    
    return $stmt->rowCount() > 0;
}

// Get comments for a post
function getComments($postId) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT c.*, u.username FROM comments c 
                           JOIN users u ON c.user_id = u.id 
                           WHERE c.post_id = ? 
                           ORDER BY c.created_at DESC");
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

// Delete comment
function deleteComment($commentId, $userId = null, $isAdmin = false) {
    $conn = getDBConnection();
    
    // Get comment to check ownership
    $stmt = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();
    
    if (!$comment) {
        return false;
    }
    
    // Check permissions - admin can delete any, user can only delete own
    if (!$isAdmin && $comment['user_id'] != $userId) {
        return false;
    }
    
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$commentId]);
    return $stmt->rowCount() > 0;
}

// Update comment
function updateComment($commentId, $userId, $newComment) {
    $conn = getDBConnection();
    $newComment = sanitize($newComment);
    
    // Get comment to check ownership (only owner can edit)
    $stmt = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();
    
    if (!$comment) {
        return false;
    }
    
    // Only the author can edit their comment
    if ($comment['user_id'] != $userId) {
        return false;
    }
    
    $stmt = $conn->prepare("UPDATE comments SET comment = ?, created_at = NOW() WHERE id = ?");
    $stmt->execute([$newComment, $commentId]);
    return $stmt->rowCount() > 0;
}

// Get comment count for a post
function getCommentCount($postId) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM comments WHERE post_id = ?");
    $stmt->execute([$postId]);
    $result = $stmt->fetch();
    return $result['count'];
}

// Render comments recursively
function renderCommentTree($comments, $parentId = null, $isAdmin = false, $currentUserId = null, $postId = null) {
    $branch = [];
    foreach ($comments as $comment) {
        if ($comment['parent_id'] == $parentId) {
            $branch[] = $comment;
        }
    }

    if (empty($branch)) return;

    foreach ($branch as $comment) {
        $isReply = !empty($parentId);
        $itemClass = $isReply ? 'comment-item reply-item' : 'comment-item';
        
        echo '<div class="' . $itemClass . '">';
        
        // Header
        echo '<div class="comment-header">';
            echo '<div class="comment-author">';
                echo '<div class="comment-avatar" style="' . ($isReply ? 'width: 24px; height: 24px; font-size: 0.7rem;' : '') . '">';
                    echo strtoupper(substr($comment['username'], 0, 1));
                echo '</div>';
                echo '<div>';
                    echo '<div class="comment-author-name" style="' . ($isReply ? 'font-size: 0.85rem;' : '') . '">';
                        echo htmlspecialchars($comment['username']);
                    echo '</div>';
                    echo '<div class="comment-time" style="' . ($isReply ? 'font-size: 0.7rem;' : '') . '">';
                        echo timeAgo($comment['created_at']);
                    echo '</div>';
                echo '</div>';
            echo '</div>';

            // Actions
            if ($isAdmin || $comment['user_id'] == $currentUserId) {
                echo '<div class="comment-actions">';
                    // Reply button (For both)
                    echo '<button type="button" class="comment-reply-btn" onclick="toggleReplyForm(' . $comment['id'] . ')">Reply</button>';
                    
                    if ($comment['user_id'] == $currentUserId) {
                        echo '<button type="button" class="comment-edit-btn" onclick="toggleEditComment(' . $comment['id'] . ')">Edit</button>';
                    }
                    echo '<button type="button" class="comment-delete" onclick="deleteCommentAjax(' . $comment['id'] . ', ' . $postId . ')">Delete</button>';
                echo '</div>';
            } else {
                 // Reply button for others
                 echo '<div class="comment-actions">';
                    echo '<button type="button" class="comment-reply-btn" onclick="toggleReplyForm(' . $comment['id'] . ')">Reply</button>';
                 echo '</div>';
            }
        echo '</div>'; // End header

        // Body
        echo '<div class="comment-text" id="comment-text-' . $comment['id'] . '" style="' . ($isReply ? 'font-size: 0.9rem;' : '') . '">';
            echo nl2br(htmlspecialchars($comment['comment']));
        echo '</div>';

        // Edit Form
        if ($comment['user_id'] == $currentUserId) {
            echo '<div class="comment-edit-form" id="comment-edit-form-' . $comment['id'] . '" style="display: none;">';
                echo '<form method="POST" onsubmit="submitEditComment(' . $comment['id'] . ', ' . $postId . ', this); return false;">';
                    echo '<textarea name="comment" class="comment-input" required>' . htmlspecialchars($comment['comment']) . '</textarea>';
                    echo '<div class="comment-edit-actions">';
                        echo '<button type="submit" class="comment-submit btn-sm">Save</button>';
                        echo '<button type="button" class="btn btn-secondary btn-sm" onclick="toggleEditComment(' . $comment['id'] . ')">Cancel</button>';
                    echo '</div>';
                echo '</form>';
            echo '</div>';
        }

        // Reply Form
        echo '<div class="comment-reply-form" id="reply-form-' . $comment['id'] . '" style="display: none;">';
            echo '<form method="POST" onsubmit="submitComment(' . $postId . ', this); return false;">';
                echo '<input type="hidden" name="post_id" value="' . $postId . '">';
                echo '<input type="hidden" name="parent_id" value="' . $comment['id'] . '">';
                echo '<textarea name="comment" class="comment-input" placeholder="Write a reply..." required style="min-height: 60px;"></textarea>';
                echo '<div class="comment-edit-actions">';
                    echo '<button type="submit" name="add_comment" class="comment-submit btn-sm">Reply</button>';
                    echo '<button type="button" class="btn btn-secondary btn-sm" onclick="toggleReplyForm(' . $comment['id'] . ')">Cancel</button>';
                echo '</div>';
            echo '</form>';
        echo '</div>';

        echo '</div>'; // End comment item

        // Render children
        $hasChildren = false;
        foreach ($comments as $c) {
            if ($c['parent_id'] == $comment['id']) {
                $hasChildren = true;
                break;
            }
        }

        if ($hasChildren) {
             echo '<div class="replies-list">';
             renderCommentTree($comments, $comment['id'], $isAdmin, $currentUserId, $postId);
             echo '</div>';
        }
    }
}
