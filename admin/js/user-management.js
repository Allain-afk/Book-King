document.addEventListener('DOMContentLoaded', function() {
    // Update datetime display
    function updateDateTime() {
        const now = new Date();
        
        // Update time
        const timeDisplay = document.querySelector('.time-display');
        timeDisplay.textContent = now.toLocaleString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });

        // Update date
        const dateDisplay = document.querySelector('.date-display');
        dateDisplay.textContent = now.toLocaleDateString('en-US', {
            month: 'short',
            day: '2-digit',
            year: 'numeric'
        });
    }

    // Update immediately and then every second
    updateDateTime();
    setInterval(updateDateTime, 1000);

    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');
    const content = document.querySelector('.content');
    const body = document.body;

    // Create overlay element
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    body.appendChild(overlay);

    function toggleMenu() {
        mobileMenuBtn.classList.toggle('active');
        sidebar.classList.toggle('active');
        content.classList.toggle('sidebar-active');
        overlay.classList.toggle('active');
        body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    }

    mobileMenuBtn.addEventListener('click', toggleMenu);
    overlay.addEventListener('click', toggleMenu);

    // Close menu when clicking a nav item on mobile
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                toggleMenu();
            }
        });
    });

    // Handle resize events
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            if (window.innerWidth > 768) {
                mobileMenuBtn.classList.remove('active');
                sidebar.classList.remove('active');
                content.classList.remove('sidebar-active');
                overlay.classList.remove('active');
                body.style.overflow = '';
            }
        }, 250);
    });


    // Remove success/error messages after 3 seconds
    setTimeout(function() {
        const messages = document.querySelectorAll('.success-message, .error-message');
        messages.forEach(function(message) {
            message.style.display = 'none';
        });
    }, 3000);
});

let currentUserId = null;
let userToDelete = null;

function editUser(userId) {
    currentUserId = userId;
    document.getElementById('editUserId').value = userId;
    document.getElementById('editUserModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editUserModal').style.display = 'none';
    currentUserId = null;
}

// Handle form submission
document.getElementById('editUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('update_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', 'User updated successfully');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showNotification('error', data.message || 'Error updating user');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'Error updating user');
    })
    .finally(() => {
        closeEditModal();
    });
});

function showNotification(type, message) {
    const container = document.getElementById('notificationContainer');
    const notification = document.createElement('div');
    
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-icon">${type === 'success' ? '✅' : '❌'}</div>
        <div class="notification-text">${message}</div>
        <div class="notification-close">×</div>
    `;
    
    container.appendChild(notification);
    
    notification.offsetHeight;
    notification.classList.add('show');
    
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => {
            container.removeChild(notification);
        }, 400);
    });
    
    setTimeout(() => {
        if (container.contains(notification)) {
            notification.classList.remove('show');
            setTimeout(() => {
                if (container.contains(notification)) {
                    container.removeChild(notification);
                }
            }, 400);
        }
    }, 3000);
}

function deleteUser(userId) {
    userToDelete = userId;
    document.getElementById('deleteConfirmModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteConfirmModal').style.display = 'none';
    userToDelete = null;
}

function confirmDelete() {
    if (userToDelete !== null) {
        window.location.href = `user-management.php?delete=${userToDelete}`;
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editUserModal');
    if (event.target == modal) {
        closeEditModal();
    }
    if (event.target == document.getElementById('deleteConfirmModal')) {
        closeDeleteModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeDeleteModal();
    }
});