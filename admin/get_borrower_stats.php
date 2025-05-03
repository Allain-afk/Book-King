<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Include the database connection
$pdo = require '../database/db_connection.php';

try {
    // Get total number of borrowed books
    $totalQuery = "SELECT COUNT(*) as total FROM borrowed_books WHERE return_date IS NULL";
    $total = $pdo->query($totalQuery)->fetch(PDO::FETCH_ASSOC)['total'];

    if ($total > 0) {
        // Get number of overdue books
        $overdueQuery = "SELECT COUNT(*) as overdue 
                       FROM borrowed_books 
                       WHERE return_date IS NULL 
                       AND due_date < CURRENT_DATE()";
        $overdue = $pdo->query($overdueQuery)->fetch(PDO::FETCH_ASSOC)['overdue'];

        // Calculate percentages
        $overduePercentage = round(($overdue / $total) * 100);
        $onTimePercentage = 100 - $overduePercentage;
    } else {
        $overduePercentage = 0;
        $onTimePercentage = 100;
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'overdue' => $overduePercentage,
        'onTime' => $onTimePercentage,
        'total' => $total
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'overdue' => 0,
        'onTime' => 100
    ]);
} 