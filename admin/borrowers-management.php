<?php
// Start session at the very beginning of the file
session_start();

// At the top of the file, after session_start()
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin/admin-login.php');
    exit();
}

// Get admin name from session
$adminFirstName = $_SESSION['admin_first_name'] ?? 'Admin';
$adminLastName = $_SESSION['admin_last_name'] ?? '';

// Include the database connection
$pdo = require '../database/db_connection.php';

// Fetch borrowed books from database outside of the HTML
try {
    $stmt = $pdo->query('
        SELECT 
            bb.id, 
            bb.user_id, 
            bb.book_id, 
            bb.borrow_date, 
            bb.due_date, 
            bb.return_date,
            u.FirstName, 
            u.LastName, 
            b.title
        FROM 
            borrowed_books bb
        JOIN 
            users u ON bb.user_id = u.user_id
        JOIN 
            books b ON bb.book_id = b.book_id
        ORDER BY 
            bb.due_date ASC
    ');
    $borrowers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching borrowers: " . $e->getMessage());
    $borrowers = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowers Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/book-management.css">
    <link rel="stylesheet" href="../assets/css/borrowers-management-2.css">
</head>

<body>
    <div class="sidebar">
        <div class="logo">
            <img src="../images/logo.png" alt="Book King Logo">
        </div>
        <div class="nav-group">
            <a href="../admin/admin-dashboard.php" class="nav-item">
                <div class="icon">
                    <img src="../images/element-2 2.svg" alt="Dashboard" width="24" height="24">
                </div>
                <div class="text">Dashboard</div>
            </a>
            <a href="../admin/catalog.php" class="nav-item">
                <div class="icon">
                    <img src="../images/Vector.svg" alt="Catalog" width="20" height="20">
                </div>
                <div class="text">Catalog</div>
            </a>
            <a href="../admin/book-management.php" class="nav-item">
                <div class="icon">
                    <img src="../images/book.png" alt="Books" width="24" height="24">
                </div>
                <div class="text">Books</div>
            </a>
            <a href="../admin/user-management.php" class="nav-item">
                <div class="icon">
                    <img src="../images/people 3.png" alt="Users" width="24" height="24">
                </div>
                <div class="text">Users</div>
            </a>
            <a href="../admin/branch-management.php" class="nav-item">
                <div class="icon">
                    <img src="../images/buildings-2 1.png" alt="Branches" width="24" height="24">
                </div>
                <div class="text">Branches</div>
            </a>
            <a href="../admin/borrowers-management.php" class="nav-item active">
                <div class="icon">
                    <img src="../images/user.png" alt="Borrowers" width="24" height="24">
                </div>
                <div class="text">Borrowers</div>
            </a>
            <a href="../admin/admin-manage.php" class="nav-item">
                <div class="icon">
                    <img src="../images/security-user 1.png" alt="Manage Admins" width="24" height="24">
                </div>
                <div class="text">Manage Admins</div>
            </a>
        </div>
        <a href="../admin/admin-logout.php" class="nav-item logout">
            <div class="icon">
                <img src="../images/logout 3.png" alt="Log Out" width="24" height="24">
            </div>
            <div class="text">Log Out</div>
        </a>
    </div>

    <div class="content">
        <div class="header">
            <div class="admin-profile">
                <div class="admin-info">
                    <span class="admin-name-1">Welcome, <?= htmlspecialchars($adminFirstName . ' ' . $adminLastName) ?></span>
                </div>
            </div>
            <div class="datetime-display">
                <div class="time-section">
                    <svg class="time-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path fill="#B07154" d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.59-8 8-8 8 3.589 8 8-3.589 8-8 8z" />
                        <path fill="#B07154" d="M13 7h-2v6l4.5 2.7.7-1.2-3.2-1.9z" />
                    </svg>
                    <span class="time-display">--:--:-- --</span>
                </div>
                <div class="date-section">
                    <svg class="date-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path fill="#B07154" d="M19 4h-2V3a1 1 0 0 0-2 0v1H9V3a1 1 0 0 0-2 0v1H5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3zm1 15a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-7h16v7zm0-9H4V7a1 1 0 0 1 1-1h2v1a1 1 0 0 0 2 0V6h6v1a1 1 0 0 0 2 0V6h2a1 1 0 0 1 1 1v3z" />
                    </svg>
                    <span class="date-display">--- --, ----</span>
                </div>
            </div>
        </div>
        <div class="main-content">
            <div class="page-title">Borrowers Management</div>

            <div class="actions-container">
                <button class="add-book-btn">
                    <div class="add-book-icon"></div>
                    <div class="add-book-text">Add Borrower</div>
                </button>
                <div class="search-container">
                    <div class="search-wrapper">
                        <input type="text" id="searchInput" class="search-input" placeholder="Search by ID or Name">
                    </div>
                </div>
            </div>

            <div class="content-table">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Borrow ID</th>
                                <th>Borrower Name</th>
                                <th>Book Title</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th style="text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($borrowers as $borrower):
                                // Check if book is overdue
                                $dueDate = new DateTime($borrower['due_date']);
                                $today = new DateTime();
                                $isOverdue = $dueDate < $today && $borrower['return_date'] === null;

                                // Determine status
                                $status = 'Active';
                                $statusClass = 'status-active';

                                if ($isOverdue) {
                                    $status = 'Overdue';
                                    $statusClass = 'status-overdue';
                                } elseif ($borrower['return_date'] !== null) {
                                    $status = 'Returned';
                                    $statusClass = 'status-returned';
                                }
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($borrower['id']) ?></td>
                                    <td><?= htmlspecialchars($borrower['FirstName'] . ' ' . $borrower['LastName']) ?></td>
                                    <td><?= htmlspecialchars($borrower['title']) ?></td>
                                    <td><?= htmlspecialchars(date('M d, Y', strtotime($borrower['borrow_date']))) ?></td>
                                    <td><?= htmlspecialchars(date('M d, Y', strtotime($borrower['due_date']))) ?></td>
                                    <td class="<?= $statusClass ?>"><?= $status ?></td>
                                    <td class="action-cell">
                                        <button class="action-btn view-btn" data-borrow-id="<?= $borrower['id'] ?>">
                                            <img src="../images/btn view.svg" alt="View">
                                        </button>
                                        <?php if ($borrower['return_date'] === null): ?>
                                            <button class="action-btn return-btn" data-borrow-id="<?= $borrower['id'] ?>">
                                                <img src="../images/btn edit.png" alt="Return">
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($borrowers)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">No borrowers found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Borrower Modal -->
    <div id="addBorrowerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Borrower</h2>
                <span class="close">&times;</span>
            </div>
            <form id="addBorrowerForm" method="POST">
                <div class="form-group">
                    <label for="user_id">Select User</label>
                    <select id="user_id" name="user_id" required>
                        <option value="">Select User</option>
                        <?php
                        try {
                            $userStmt = $pdo->query('SELECT user_id, FirstName, LastName FROM users ORDER BY FirstName');
                            $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($users as $user) {
                                echo '<option value="' . $user['user_id'] . '">' .
                                    htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) . '</option>';
                            }
                        } catch (PDOException $e) {
                            error_log("Error fetching users: " . $e->getMessage());
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="book_id">Select Book</label>
                    <select id="book_id" name="book_id" required>
                        <option value="">Select Book</option>
                        <?php
                        try {
                            $bookStmt = $pdo->query('SELECT book_id, title FROM books WHERE availability = "Available" ORDER BY title');
                            $books = $bookStmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($books as $book) {
                                echo '<option value="' . $book['book_id'] . '">' .
                                    htmlspecialchars($book['title']) . '</option>';
                            }
                        } catch (PDOException $e) {
                            error_log("Error fetching books: " . $e->getMessage());
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="borrow_date">Borrow Date</label>
                    <input type="date" id="borrow_date" name="borrow_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label for="due_date">Due Date</label>
                    <input type="date" id="due_date" name="due_date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="cancel-btn">Cancel</button>
                    <button type="submit" class="submit-btn">Add Borrower</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Return Book Modal -->
    <div id="returnBookModal" class="modal">
        <div class="modal-content">
            <span class="close close-return">&times;</span>
            <h2>Return Book</h2>
            <form id="returnBookForm">
                <input type="hidden" id="return_borrow_id" name="borrow_id">
                <input type="hidden" name="return_date" value="<?php echo date('Y-m-d'); ?>">
                <div class="form-actions">
                    <button type="submit" class="submit-btn">Confirm Return</button>
                    <button type="button" class="cancel-btn close-return">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Borrower Modal -->
    <div id="viewBorrowerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Borrower Details</h2>
                <span class="close-view">&times;</span>
            </div>
            <div class="borrower-details">
                <div class="detail-row">
                    <div class="detail-label">Borrow ID:</div>
                    <div class="detail-value" id="view-borrow-id"></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Borrower Name:</div>
                    <div class="detail-value" id="view-borrower-name"></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Book Title:</div>
                    <div class="detail-value" id="view-book-title"></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Borrow Date:</div>
                    <div class="detail-value" id="view-borrow-date"></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Due Date:</div>
                    <div class="detail-value" id="view-due-date"></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value" id="view-status"></div>
                </div>
                <div class="detail-row" id="return-date-row">
                    <div class="detail-label">Return Date:</div>
                    <div class="detail-value" id="view-return-date"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="close-btn">Close</button>
            </div>
        </div>
    </div>
    <script src="./js/borrowers-management.js"></script>
</body>

</html>