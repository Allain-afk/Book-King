document.addEventListener('DOMContentLoaded', function() {
    // Update datetime display
    function updateDateTime() {
        const now = new Date();
        
        // Update time
        const timeDisplay = document.querySelector('.time-display');
        timeDisplay.textContent = now.toLocaleString('en-US', {
            hour: 'numeric',
            minute: 'numeric',
            second: 'numeric',
            hour12: true
        });

        // Update date
        const dateDisplay = document.querySelector('.date-display');
        dateDisplay.textContent = now.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    // Update immediately and then every second
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // Check if CSS file is loaded
    let cssLoaded = false;
    for(let i = 0; i < document.styleSheets.length; i++) {
        if(document.styleSheets[i].href && document.styleSheets[i].href.includes('branch-management.css')) {
            cssLoaded = true;
            break;
        }
    }
    
    if(!cssLoaded) {
        console.error('Branch management CSS file failed to load');
    }

    // Test JS functionality
    if(typeof openModal === 'undefined') {
        console.error('Branch management JS file failed to load');
    }
});

function openModal() {
    document.getElementById('modalTitle').textContent = 'Add New Branch';
    document.getElementById('branchForm').reset();
    document.getElementById('branchId').value = '';
    document.getElementById('branchForm').action = 'add_branch.php';
    document.getElementById('branchModal').style.display = 'block';
}

function editBranch(branch) {
    document.getElementById('modalTitle').textContent = 'Edit Branch';
    document.getElementById('branchId').value = branch.branch_id;
    document.getElementById('branchName').value = branch.branch_name;
    document.getElementById('branchLocation').value = branch.branch_location;
    document.getElementById('contactNumber').value = branch.contact_number || '';
    document.getElementById('branchForm').action = 'update_branch.php';
    document.getElementById('branchModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('branchModal').style.display = 'none';
}

let branchToDelete = null;

function deleteBranch(branchId) {
    branchToDelete = branchId;
    document.getElementById('deleteConfirmModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteConfirmModal').style.display = 'none';
    branchToDelete = null;
}

function confirmDelete() {
    if (branchToDelete !== null) {
        window.location.href = `branch-management.php?delete=${branchToDelete}`;
    }
}

// Update window click handler to include delete modal
window.onclick = function(event) {
    if (event.target == document.getElementById('branchModal')) {
        closeModal();
    }
    if (event.target == document.getElementById('deleteConfirmModal')) {
        closeDeleteModal();
    }
}

// Update escape key handler to include delete modal
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
        closeDeleteModal();
    }
});