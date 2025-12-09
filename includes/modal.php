<!-- Modal Component - Include in pages before closing </body> tag -->
<div id="customModal" class="modal">
    <div class="modal-overlay" onclick="closeModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <span class="modal-icon" id="modalIcon"></span>
            <h3 id="modalTitle">Notification</h3>
        </div>
        <div class="modal-body">
            <p id="modalMessage"></p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="modalCancelBtn" onclick="closeModal()">Cancel</button>
            <button class="btn btn-primary" id="modalConfirmBtn" onclick="confirmModal()">OK</button>
        </div>
    </div>
</div>

<script>
// Modal system for custom alerts and confirmations
let modalCallback = null;

function showModal(title, message, type = 'info', buttons = 'ok') {
    const modal = document.getElementById('customModal');
    const icon = document.getElementById('modalIcon');
    const titleEl = document.getElementById('modalTitle');
    const messageEl = document.getElementById('modalMessage');
    const cancelBtn = document.getElementById('modalCancelBtn');
    const confirmBtn = document.getElementById('modalConfirmBtn');
    
    // Set icon based on type
    const icons = {
        'success': '✅',
        'error': '❌',
        'warning': '⚠️',
        'info': 'ℹ️',
        'question': '❓',
        'delete': '🗑️'
    };
    
    icon.textContent = icons[type] || icons['info'];
    titleEl.textContent = title;
    messageEl.textContent = message;
    
    // Configure buttons
    if (buttons === 'ok') {
        cancelBtn.style.display = 'none';
        confirmBtn.textContent = 'OK';
        confirmBtn.className = 'btn btn-primary';
    } else if (buttons === 'confirm') {
        cancelBtn.style.display = 'inline-block';
        confirmBtn.textContent = 'Confirm';
        confirmBtn.className = 'btn btn-primary';
    } else if (buttons === 'delete') {
        cancelBtn.style.display = 'inline-block';
        confirmBtn.textContent = 'Delete';
        confirmBtn.className = 'btn btn-danger';
    }
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('customModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    
    if (modalCallback) {
        modalCallback(false);
        modalCallback = null;
    }
}

function confirmModal() {
    const modal = document.getElementById('customModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    
    if (modalCallback) {
        modalCallback(true);
        modalCallback = null;
    } else {
        // For simple OK dialogs, just close
        closeModal();
    }
}

// Convenience functions
function showSuccess(message, title = 'Success') {
    showModal(title, message, 'success', 'ok');
}

function showError(message, title = 'Error') {
    showModal(title, message, 'error', 'ok');
}

function showWarning(message, title = 'Warning') {
    showModal(title, message, 'warning', 'ok');
}

function showInfo(message, title = 'Information') {
    showModal(title, message, 'info', 'ok');
}

function showConfirm(message, title = 'Confirm Action', onConfirm) {
    modalCallback = onConfirm;
    showModal(title, message, 'question', 'confirm');
}

function showDeleteConfirm(message, title = 'Delete Confirmation', onConfirm) {
    modalCallback = onConfirm;
    showModal(title, message, 'delete', 'delete');
}

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>
