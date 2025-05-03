<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
$pdo = require '../database/db_connection.php';

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['currentPassword']) || !isset($data['newPassword'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$currentPassword = $data['currentPassword'];
$newPassword = $data['newPassword'];
$adminId = $_SESSION['admin_id'];

try {
    // Verify current password
    $stmt = $pdo->prepare('SELECT password FROM admin WHERE admin_id = ?');
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || $admin['password'] !== $currentPassword) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit();
    }

    // Validate new password
    if (strlen($newPassword) < 8 ||
        !preg_match('/[A-Z]/', $newPassword) ||
        !preg_match('/[a-z]/', $newPassword) ||
        !preg_match('/[0-9]/', $newPassword)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'New password does not meet requirements']);
        exit();
    }

    // Update password
    $stmt = $pdo->prepare('UPDATE admin SET password = ? WHERE admin_id = ?');
    $stmt->execute([$newPassword, $adminId]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
} catch (PDOException $e) {
    error_log("Password change error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} 