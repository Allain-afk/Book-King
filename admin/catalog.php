<?php
// Start session at the very beginning of the file
session_start();

// At the top of the file, after session_start()
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

// Get admin name from session
$adminFirstName = $_SESSION['admin_first_name'] ?? 'Admin';
$adminLastName = $_SESSION['admin_last_name'] ?? '';
$adminName = $adminFirstName . ' ' . $adminLastName;

// Include the database connection
$pdo = require '../database/db_connection.php';

// Include the activity logger
require '../helpers/activity_logger.php';

// Replace the existing confirmation handling code
if (isset($_POST['confirm_return'])) {
    $log_id = $_POST['log_id'];
    $book_id = $_POST['book_id'];
    $user_id = $_POST['user_id'];

    try {
        $pdo->beginTransaction();

        // Update borrowed_books table
        $stmt = $pdo->prepare("
            UPDATE borrowed_books 
            SET return_date = CURRENT_TIMESTAMP 
            WHERE book_id = ? AND user_id = ? AND return_date IS NULL
        ");
        $stmt->execute([$book_id, $user_id]);

        // Update log status
        $stmt = $pdo->prepare("
            UPDATE activity_logs 
            SET status = 'completed' 
            WHERE log_id = ?
        ");
        $stmt->execute([$log_id]);

        $pdo->commit();

        // Set success message in session instead of using alert
        $_SESSION['return_success'] = true;
        header('Location: catalog.php');
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error confirming return: " . $e->getMessage());
        $_SESSION['return_error'] = true;
        header('Location: ../catalog.php');
        exit();
    }
}

// Fetch logs from database outside of the HTML
try {
    $stmt = $pdo->query('
        SELECT 
            l.log_id,
            l.action_type,
            l.description,
            l.performed_by,
            l.timestamp,
            l.status,
            l.related_id,
            CASE 
                WHEN l.action_type IN ("RETURN_REQUEST", "BOOK_RETURN", "BORROW") 
                THEN (
                    SELECT user_id 
                    FROM borrowed_books 
                    WHERE book_id = l.related_id 
                    ORDER BY borrow_date DESC 
                    LIMIT 1
                )
                ELSE NULL
            END as user_id
        FROM activity_logs l
        ORDER BY l.timestamp DESC
        LIMIT 100
    ');
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching logs: " . $e->getMessage());
    $logs = [];
}

// Add this after your existing query
try {
    $debug_stmt = $pdo->query("
        SELECT * FROM activity_logs 
        WHERE action_type = 'RETURN_REQUEST' 
        AND status = 'pending' 
        LIMIT 1
    ");
    $debug_result = $debug_stmt->fetch(PDO::FETCH_ASSOC);
    if ($debug_result) {
        error_log("Found pending return request: " . print_r($debug_result, true));
    } else {
        error_log("No pending return requests found");
    }
} catch (PDOException $e) {
    error_log("Debug query error: " . $e->getMessage());
}

// Get total books count and fetch all books
try {
    $bookCount = $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();

    // Fetch all books - FIXED QUERY to remove categories join
    $booksQuery = $pdo->query('
        SELECT * FROM books ORDER BY book_id DESC
    ');
    $books = $booksQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching books: " . $e->getMessage());
    $bookCount = 0;
    $books = [];
}

// Function to get random cover URL if not provided
function getRandomCover($title, $author)
{
    // List of background colors (pastel colors)
    $colors = [
        'F8B195',
        'F67280',
        'C06C84',
        '6C5B7B',
        '355C7D', // warm to cool
        'A8E6CF',
        'DCEDC1',
        'FFD3B6',
        'FFAAA5',
        'FF8B94', // nature
        'B5EAD7',
        'C7CEEA',
        'E2F0CB',
        'FFDAC1',
        'FFB7B2', // soft
        'E7D3EA',
        'DCD3FF',
        'B5D8EB',
        'BBE1FA',
        'D6E5FA'  // pastel
    ];

    // Get random color
    $bgColor = $colors[array_rand($colors)];

    // Format title and author for URL
    $text = urlencode($title . "\nby " . $author);

    return "https://placehold.co/400x600/${bgColor}/333333/png?text=${text}";
}

// Update books with cover images if needed
foreach ($books as &$book) {
    if (empty($book['cover_image_url'])) {
        $book['cover_image_url'] = getRandomCover($book['title'], $book['author']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Book Catalog - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <link rel="stylesheet" href="../assets/css/catalog-2.css">
</head>

<body>
    <div class="mobile-menu-btn">
        <span></span>
        <span></span>
        <span></span>
    </div>
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
            <a href="../admin/catalog.php" class="nav-item active">
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
            <a href="../admin/borrowers-management.php" class="nav-item">
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
        <a href="../admin/admin-logout.php" class="nav-item">
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
        <div class="catalog-container">
            <div class="catalog-header">
                <h1 class="catalog-title">Book Catalog</h1>
                <div class="search-add-container">
                    <div class="search-box">
                        <input type="text" class="search-input" placeholder="Search books...">
                    </div>
                </div>
            </div>
            <div class="books-grid">
                <?php foreach ($books as $book): ?>
                    <div class="book-card">
                        <img src="<?= htmlspecialchars($book['cover_image_url'] ?? '../images/default-book-cover.jpg') ?>"
                            alt="<?= htmlspecialchars($book['title']) ?>"
                            class="book-cover">
                        <h3 class="book-title"><?= htmlspecialchars($book['title']) ?></h3>
                        <p class="book-author"><?= htmlspecialchars($book['author']) ?></p>
                        <span class="book-category"><?= htmlspecialchars($book['type']) ?></span>
                        <div class="book-actions">
                            <button class="action-btn edit-btn" onclick="editBook(<?= $book['book_id'] ?>)">Edit</button>
                            <button class="action-btn delete-btn" onclick="deleteBook(<?= $book['book_id'] ?>)">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Add Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Edit Book</h2>
            <form id="editBookForm">
                <input type="hidden" id="edit_book_id" name="book_id">
                <div class="form-group">
                    <label for="edit_title">Title</label>
                    <input type="text" id="edit_title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="edit_author">Author</label>
                    <input type="text" id="edit_author" name="author" required>
                </div>
                <div class="form-group">
                    <label for="edit_type">Type</label>
                    <input type="text" id="edit_type" name="type" required>
                </div>
                <div class="form-group">
                    <label for="edit_language">Language</label>
                    <input type="text" id="edit_language" name="language" required>
                </div>
                <div class="form-buttons">
                    <button type="button" class="delete-btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="add-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Delete Book</h2>
                <span class="close-btn" onclick="closeDeleteModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="warning-icon">!</div>
                <p class="delete-message">Are you sure you want to delete this book?</p>
                <div class="button-group">
                    <button class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
                    <button class="delete-confirm-btn" onclick="confirmDelete()">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div id="notificationContainer"></div>

    <script src="./js/catalog.js"></script>
</body>

</html>