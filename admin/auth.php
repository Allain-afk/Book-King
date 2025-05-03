<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // For AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit();
    }
    // For regular page loads
    header('Location: admin-login.php');
    exit();
}

// Check if admin is active
if ($_SESSION['admin_status'] !== 'active') {
    // For AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Your account is deactivated']);
        exit();
    }
    // For regular page loads
    session_destroy();
    header('Location: admin-login.php?error=deactivated');
    exit();
}

// Include database connection after authentication
$pdo = require '../database/db_connection.php'; 