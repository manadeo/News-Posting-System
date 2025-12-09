<!-- Toast Notification Container -->
<div id="toastContainer" class="toast-container"></div>

<script>
// Toast Notification System
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    // Icon based on type
    const icons = {
        'success': '✅',
        'error': '❌',
        'info': 'ℹ️',
        'warning': '⚠️'
    };
    
    toast.innerHTML = `
        <div class="toast-icon">${icons[type] || icons.info}</div>
        <div class="toast-message">${message}</div>
        <button class="toast-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// AJAX Comment Functions
function submitComment(postId, formElement) {
    const formData = new FormData(formElement);
    formData.append('ajax', '1');
    formData.append('add_comment', '1'); // Required for backend check
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Clear the form
            formElement.querySelector('textarea').value = '';
            
            showToast('Comment added successfully!', 'success');
            
            // Reload page after short delay to show new comment
            setTimeout(() => location.reload(), 500);
        } else {
            // Handle backend error
            showToast(data.error || 'Failed to add comment', 'error');
        }
    })
    .catch(error => {
        showToast('Failed to add comment', 'error');
        console.error('Error:', error);
    });
    
    return false; // Prevent default form submission
}

// Edit Comment Functions
function toggleEditComment(commentId) {
    const textDiv = document.getElementById(`comment-text-${commentId}`);
    const formDiv = document.getElementById(`comment-edit-form-${commentId}`);
    
    if (textDiv.style.display === 'none') {
        textDiv.style.display = 'block';
        formDiv.style.display = 'none';
    } else {
        textDiv.style.display = 'none';
        formDiv.style.display = 'block';
    }
}

function toggleReplyForm(commentId) {
    const formDiv = document.getElementById(`reply-form-${commentId}`);
    if (formDiv.style.display === 'none') {
        formDiv.style.display = 'block';
        // Focus textarea
        const textarea = formDiv.querySelector('textarea');
        if (textarea) textarea.focus();
    } else {
        formDiv.style.display = 'none';
    }
}

function submitEditComment(commentId, postId, formElement) {
    const formData = new FormData(formElement);
    formData.append('ajax', '1');
    formData.append('edit_comment', '1');
    formData.append('comment_id', commentId);
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showToast('Comment updated successfully!', 'success');
            setTimeout(() => location.reload(), 500);
        } else {
            showToast(data.error || 'Failed to update comment', 'error');
        }
    })
    .catch(error => {
        showToast('Failed to update comment', 'error');
        console.error('Error:', error);
    });
    
    return false;
}

function deleteCommentAjax(commentId, postId) {
    showDeleteConfirm('Delete this comment?', 'Delete Comment', function(confirmed) {
        if (confirmed) {
            const formData = new FormData();
            formData.append('delete_comment', '1'); // Required for backend check
            formData.append('comment_id', commentId);
            formData.append('ajax', '1');
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast('Comment deleted successfully!', 'success');
                    
                    // Reload page after short delay
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast(data.error || 'Failed to delete comment', 'error');
                }
            })
            .catch(error => {
                showToast('Failed to delete comment', 'error');
                console.error('Error:', error);
            });
        }
    });
}

function reloadComments(postId) {
    // This function is no longer needed but kept for compatibility
    location.reload();
}

// AJAX Delete Post
function deletePostAjax(postId, postType = 'post') {
    const message = postType === 'post' 
        ? 'Are you sure you want to delete this post? This action cannot be undone.' 
        : 'Delete this item?';
    
    showDeleteConfirm(message, 'Delete Post', function(confirmed) {
        if (confirmed) {
            const formData = new FormData();
            formData.append('delete_post', '1'); // Required for backend check
            formData.append('post_id', postId);
            formData.append('ajax', '1');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                showToast('Post deleted successfully!', 'success');
                
                // Remove the post card from DOM
                const postCard = document.querySelector(`[data-post-id="${postId}"]`);
                if (postCard) {
                    postCard.style.transition = 'all 0.3s ease';
                    postCard.style.opacity = '0';
                    postCard.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        postCard.remove();
                        
                        // Check if there are no more posts
                        const postsContainer = document.querySelector('.content-section');
                        const remainingPosts = postsContainer.querySelectorAll('.post-card');
                        if (remainingPosts.length === 0) {
                            location.reload(); // Reload to show empty state
                        }
                    }, 300);
                }
            })
            .catch(error => {
                showToast('Failed to delete post', 'error');
                console.error('Error:', error);
            });
        }
    });
}
</script>
