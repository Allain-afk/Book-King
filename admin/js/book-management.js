// Mobile menu functionality
document.addEventListener('DOMContentLoaded', function() {
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

    // Search and filter functionality
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const bookElements = document.querySelectorAll('.books-table tr:not(:first-child), .mobile-card');

    function filterBooks() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value.toLowerCase();

        bookElements.forEach(element => {
            const isTableRow = element.tagName === 'TR';
            const title = isTableRow ?
                element.querySelector('.book-title').textContent.toLowerCase() :
                element.querySelector('.mobile-card-info .book-title').textContent.toLowerCase();
            const status = isTableRow ?
                element.querySelector('.status-badge').textContent.toLowerCase() :
                element.querySelector('.status-badge').textContent.toLowerCase();

            const matchesSearch = title.includes(searchTerm);
            const matchesStatus = !statusValue || status === statusValue;

            element.style.display =
                matchesSearch && matchesStatus ? '' : 'none';
        });
    }

    searchInput.addEventListener('input', filterBooks);
    statusFilter.addEventListener('change', filterBooks);
});

let currentBookId = null;

function editBook(bookId) {
    currentBookId = bookId;
    // Fetch book details
    fetch(`get_book.php?id=${bookId}`)
        .then(response => response.json())
        .then(book => {
            document.getElementById('edit_book_id').value = book.book_id;
            document.getElementById('edit_title').value = book.title;
            document.getElementById('edit_author').value = book.author;
            document.getElementById('edit_type').value = book.type;
            document.getElementById('edit_language').value = book.language;
            document.getElementById('editModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error fetching book details');
        });
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
    currentBookId = null;
}

function deleteBook(bookId) {
    currentBookId = bookId;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    currentBookId = null;
}

function confirmDelete() {
    if (!currentBookId) return;

    const formData = new FormData();
    formData.append('book_id', currentBookId);

    fetch('delete_book.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', 'Book deleted successfully', '📚');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification('error', data.message || 'Error deleting book', '❌');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Error deleting book', '❌');
        })
        .finally(() => {
            closeDeleteModal();
        });
}

// Handle edit form submission
document.getElementById('editBookForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('edit_book.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', 'Book updated successfully', '✅');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification('error', data.message || 'Error updating book', '❌');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Error updating book', '❌');
        })
        .finally(() => {
            closeModal();
        });
});

// Close modals when clicking outside
window.onclick = function(event) {
    const editModal = document.getElementById('editModal');
    const deleteModal = document.getElementById('deleteModal');
    if (event.target == editModal) {
        closeModal();
    }
    if (event.target == deleteModal) {
        closeDeleteModal();
    }
}

function showNotification(type, message, icon) {
    const container = document.getElementById('notificationContainer');
    const notification = document.createElement('div');

    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-icon">📚</div>
        <div class="notification-text">Book deleted successfully</div>
        <div class="notification-close">×</div>
    `;

    container.appendChild(notification);

    // Trigger reflow for animation
    notification.offsetHeight;
    notification.classList.add('show');

    // Close button handler
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => {
            container.removeChild(notification);
        }, 400);
    });

    // Auto close after 3 seconds
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
});