<?php
session_start();
require_once '../../helpers/activity_logger.php';

// Include the database connection
$pdo = require '../../database/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['books']) || !isset($data['dueDate'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

$userId = $_SESSION['user_id'];
$books = $data['books'];
$dueDate = $data['dueDate'];

try {
    // Begin transaction
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO borrowed_books (user_id, book_id, due_date) 
        VALUES (:user_id, :book_id, :due_date)
    ");

    foreach ($books as $book) {
        $stmt->execute([
            ':user_id' => $userId,
            ':book_id' => $book['id'],
            ':due_date' => $dueDate
        ]);

        // Log each book borrow
        logActivity(
            $pdo,
            'BORROW',
            "Book '{$book['title']}' borrowed by user ID: {$userId}",
            $_SESSION['username'],
            $book['id'],
            'completed'
        );
    }

    // Commit transaction
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Books borrowed successfully']);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error saving borrowed books: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
