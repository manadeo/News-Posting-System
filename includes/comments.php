<?php
// Comments component - Include this in post displays
// Required variables: $post (post array), $isAdmin (boolean)
// NOTE: Comment submission and deletion are handled at the page level to avoid header errors

// Get comments for this post
$comments = getComments($post['id']);
$commentCount = count($comments);
?>

<div class="comments-section" data-post-id="<?php echo $post['id']; ?>">
    <div class="comments-header">
        <h4>💬 Comments <span class="comment-count"><?php echo $commentCount; ?></span></h4>
    </div>
    
    <!-- Comment Form -->
    <form method="POST" class="comment-form" onsubmit="submitComment(<?php echo $post['id']; ?>, this); return false;">
        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
        <textarea 
            name="comment" 
            class="comment-input" 
            placeholder="Write a comment..." 
            required
        ></textarea>
        <button type="submit" name="add_comment" class="comment-submit">
            💬 Comment
        </button>
    </form>
    
    <!-- Comments List -->
    <div class="comments-list">
        <?php if (empty($comments)): ?>
            <div class="no-comments">
                No comments yet. Be the first to comment!
            </div>
        <?php else: ?>
            <?php 
            // Use recursive function to render comments
            renderCommentTree($comments, null, $isAdmin, $_SESSION['user_id'], $post['id']);
            ?>
        <?php endif; ?>
    </div>
</div>
