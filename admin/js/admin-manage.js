function showAddModal() {
    document.getElementById('addModal').style.display = 'block';
}

function showEditModal(admin) {
    document.getElementById('edit_admin_id').value = admin.admin_id;
    document.getElementById('edit_username').value = admin.username;
    document.getElementById('edit_firstname').value = admin.FirstName;
    document.getElementById('edit_lastname').value = admin.LastName;
    document.getElementById('edit_email').value = admin.email;
    document.getElementById('edit_status').value = admin.Status;
    document.getElementById('editModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

let adminToDelete = null;

function deleteAdmin(adminId) {
    adminToDelete = adminId;
    document.getElementById('deleteConfirmModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteConfirmModal').style.display = 'none';
    adminToDelete = null;
}

function confirmDelete() {
    if (!adminToDelete) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="admin_id" value="${adminToDelete}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.id === 'deleteConfirmModal') {
        closeDeleteModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeDeleteModal();
    }
});

function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}