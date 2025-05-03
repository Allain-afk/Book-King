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

// Get modal elements
const modal = document.getElementById('addBorrowerModal');
const addBorrowerBtn = document.querySelector('.add-book-btn');
const closeBtn = document.querySelector('.close');
const cancelBtn = document.querySelector('.cancel-btn');
const form = document.getElementById('addBorrowerForm');

// View modal elements
const viewModal = document.getElementById('viewBorrowerModal');
const closeViewBtn = document.querySelector('.close-view');
const closeModalBtn = document.querySelector('.close-btn');

// Open modal
addBorrowerBtn.onclick = function() {
    modal.style.display = 'block';
}

// Close modal
closeBtn.onclick = function() {
    modal.style.display = 'none';
}

cancelBtn.onclick = function() {
    modal.style.display = 'none';
}

// Close view modal
closeViewBtn.onclick = function() {
    viewModal.style.display = 'none';
}

closeModalBtn.onclick = function() {
    viewModal.style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = 'none';
    }
    if (event.target == returnModal) {
        returnModal.style.display = 'none';
    }
    if (event.target == viewModal) {
        viewModal.style.display = 'none';
    }
}

// View button functionality
document.querySelectorAll('.view-btn').forEach(button => {
    button.onclick = async function() {
        const borrowId = this.dataset.borrowId;

        try {
            const response = await fetch(`get-borrower.php?id=${borrowId}`);
            const borrower = await response.json();

            if (borrower) {
                // Populate the modal with borrower details
                document.getElementById('view-borrow-id').textContent = borrower.id;
                document.getElementById('view-borrower-name').textContent = borrower.borrower_name;
                document.getElementById('view-book-title').textContent = borrower.book_title;
                document.getElementById('view-borrow-date').textContent = borrower.borrow_date;
                document.getElementById('view-due-date').textContent = borrower.due_date;

                // Set status with appropriate class
                const statusElement = document.getElementById('view-status');
                statusElement.textContent = borrower.status;
                statusElement.className = 'detail-value ' + borrower.status_class;

                // Handle return date display
                const returnDateRow = document.getElementById('return-date-row');
                const returnDateValue = document.getElementById('view-return-date');

                if (borrower.return_date) {
                    returnDateRow.style.display = '';
                    returnDateValue.textContent = borrower.return_date;
                } else {
                    returnDateRow.style.display = 'none';
                }

                // Show the modal
                viewModal.style.display = 'block';
            } else {
                alert('Error: Borrower details not found');
            }
        } catch (error) {
            alert('Error fetching borrower details: ' + error.message);
        }
    };
});

// Handle form submission
form.onsubmit = async function(e) {
    e.preventDefault();

    const formData = new FormData(form);
    try {
        const response = await fetch('add-borrower.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Show success popup
            const successPopup = document.createElement('div');
            successPopup.className = 'modal success-modal';
            successPopup.innerHTML = `
                <div class="modal-content success-content">
                    <div class="success-header">
                        <img src="images/image 1.png" alt="Logo" class="success-logo">
                        <h2>Success!</h2>
                    </div>
                    <p>Borrower added successfully!</p>
                    <div class="success-footer">
                        <img src="images/logo3.png" alt="Footer Logo" class="footer-logo">
                    </div>
                </div>
            `;
            document.body.appendChild(successPopup);

            // Close add borrower modal
            modal.style.display = 'none';

            // Clear form
            form.reset();

            // Remove success popup and refresh page after delay
            setTimeout(() => {
                successPopup.remove();
                window.location.reload();
            }, 2000);
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error adding borrower: ' + error.message);
    }
}

// Return book functionality
const returnModal = document.getElementById('returnBookModal');
const returnForm = document.getElementById('returnBookForm');
const closeReturnBtns = document.querySelectorAll('.close-return');

// Return button functionality
document.querySelectorAll('.return-btn').forEach(button => {
    button.onclick = function() {
        const borrowId = this.dataset.borrowId;
        document.getElementById('return_borrow_id').value = borrowId;
        returnModal.style.display = 'block';
    };
});

// Close return modal
closeReturnBtns.forEach(btn => {
    btn.onclick = function() {
        returnModal.style.display = 'none';
    };
});

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target == returnModal) {
        returnModal.style.display = 'none';
    }
};

// Handle return form submission
returnForm.onsubmit = async function(e) {
    e.preventDefault();
    const formData = new FormData(returnForm);

    try {
        const response = await fetch('../return-book.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const result = await response.json();
        if (result.success) {
            // Show success message
            const successPopup = document.createElement('div');
            successPopup.className = 'modal success-modal';
            successPopup.innerHTML = `
                <div class="modal-content success-content">
                    <div class="success-header">
                        <img src="../images/image 1.png" alt="Logo" class="success-logo">
                        <h2>Success!</h2>
                    </div>
                    <p>Book returned successfully!</p>
                    <div class="success-footer">
                        <img src="../images/logo3.png" alt="Footer Logo" class="footer-logo">
                    </div>
                </div>
            `;
            document.body.appendChild(successPopup);

            // Close return modal
            returnModal.style.display = 'none';

            // Remove success popup and refresh page after delay
            setTimeout(() => {
                successPopup.remove();
                window.location.reload();
            }, 2000);
        } else {
            alert('Error: ' + (result.message || 'Failed to return book'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error returning book: ' + error.message);
    }
};

// Search functionality
const searchInput = document.getElementById('searchInput');
const tableBody = document.querySelector('tbody');

searchInput.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = tableBody.getElementsByTagName('tr');
    let hasVisibleRows = false;

    // Remove existing "no results" row if it exists
    const existingNoResults = document.querySelector('.no-results');
    if (existingNoResults) {
        existingNoResults.remove();
    }

    // Filter rows
    Array.from(rows).forEach(row => {
        // Skip the "no results" row if it exists
        if (row.classList.contains('no-results')) return;

        const cells = row.getElementsByTagName('td');
        const rowText = Array.from(cells).reduce((text, cell) => {
            return text + ' ' + cell.textContent.toLowerCase();
        }, '');

        if (rowText.includes(searchTerm)) {
            row.style.display = '';
            hasVisibleRows = true;
        } else {
            row.style.display = 'none';
        }
    });

    // Show "No results" message if no matches
    if (!hasVisibleRows) {
        const noResultsRow = document.createElement('tr');
        noResultsRow.className = 'no-results';
        noResultsRow.innerHTML = '<td colspan="7" style="text-align: center;">No matching borrowers found</td>';
        tableBody.appendChild(noResultsRow);
    }
});