<?php
// Start the session
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug session information
error_log("Session data: " . print_r($_SESSION, true));

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    error_log("Admin not logged in. Redirecting to login page.");
    header('Location: admin-login.php');
    exit();
}

// Include the database connection
try {
    $pdo = require '../database/db_connection.php';
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed: " . $e->getMessage());
}

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Add new admin
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO admin (username, password, FirstName, LastName, email, Status)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['username'],
                        $_POST['password'],  // Store password directly without hashing
                        $_POST['firstname'],
                        $_POST['lastname'],
                        $_POST['email'],
                        $_POST['status']
                    ]);
                    $success_message = "Admin added successfully!";
                } catch (PDOException $e) {
                    // Check for duplicate username
                    if ($e->getCode() == 23000) {
                        $error_message = "Username already exists. Please choose a different username.";
                    } else {
                        $error_message = "Error adding admin: " . $e->getMessage();
                    }
                    error_log("Error adding admin: " . $e->getMessage());
                }
                break;

            case 'edit':
                // Edit existing admin
                try {
                    if (!empty($_POST['password'])) {
                        // If password is provided, update with plain password (no hashing)
                        $stmt = $pdo->prepare("
                            UPDATE admin 
                            SET username = ?, 
                                password = ?,
                                FirstName = ?,
                                LastName = ?,
                                email = ?,
                                Status = ?
                            WHERE admin_id = ?
                        ");
                        $params = [
                            $_POST['username'],
                            $_POST['password'],  // Store password directly without hashing
                            $_POST['firstname'],
                            $_POST['lastname'],
                            $_POST['email'],
                            $_POST['status'],
                            $_POST['admin_id']
                        ];
                    } else {
                        // If no password provided, update without changing password
                        $stmt = $pdo->prepare("
                            UPDATE admin 
                            SET username = ?, 
                                FirstName = ?,
                                LastName = ?,
                                email = ?,
                                Status = ?
                            WHERE admin_id = ?
                        ");
                        $params = [
                            $_POST['username'],
                            $_POST['firstname'],
                            $_POST['lastname'],
                            $_POST['email'],
                            $_POST['status'],
                            $_POST['admin_id']
                        ];
                    }
                    $stmt->execute($params);
                    $success_message = "Admin updated successfully!";
                } catch (PDOException $e) {
                    // Check for duplicate username
                    if ($e->getCode() == 23000) {
                        $error_message = "Username already exists. Please choose a different username.";
                    } else {
                        $error_message = "Error updating admin: " . $e->getMessage();
                    }
                    error_log("Error updating admin: " . $e->getMessage());
                }
                break;

            case 'delete':
                // Delete admin
                try {
                    $stmt = $pdo->prepare("DELETE FROM admin WHERE admin_id = ?");
                    $stmt->execute([$_POST['admin_id']]);
                    $success_message = "Admin deleted successfully!";
                } catch (PDOException $e) {
                    $error_message = "Error deleting admin: " . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch all admins
$admins = $pdo->query("SELECT * FROM admin ORDER BY username")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - Book King</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-manage.css">
</head>
<body>
    <div class="container">
        <div class="header-actions">
            <a href="../admin/admin-dashboard.php" class="back-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                </svg>
                Back to Dashboard
            </a>
        </div>
        <h1>Manage Administrators</h1>

        <?php if ($success_message): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <button class="btn btn-primary" onclick="showAddModal()">Add New Admin</button>

        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                        <td><?php echo htmlspecialchars($admin['FirstName'] . ' ' . $admin['LastName']); ?></td>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                        <td>
                            <span class="status-badge <?php echo strtolower($admin['Status']); ?>">
                                <?php echo htmlspecialchars($admin['Status']); ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <button class="btn-edit" onclick="showEditModal(<?php echo htmlspecialchars(json_encode($admin)); ?>)">Edit</button>
                            <button class="btn-delete" onclick="deleteAdmin(<?php echo $admin['admin_id']; ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Admin Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addModal')">&times;</span>
            <h2>Add New Admin</h2>
            <form method="POST" class="edit-form">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" placeholder="Enter password" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePasswordVisibility('password')"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="firstname">First Name</label>
                    <input type="text" id="firstname" name="firstname" placeholder="Enter first name" required>
                </div>
                
                <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input type="text" id="lastname" name="lastname" placeholder="Enter last name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter email address" required>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required class="status-select">
                        <option value="active" class="status-option">Active</option>
                        <option value="deactivate" class="status-option">Deactivate</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-update">Add Admin</button>
            </form>
        </div>
    </div>

    <!-- Edit Admin Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h2>Edit Admin</h2>
            <form method="POST" class="edit-form">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="admin_id" id="edit_admin_id">
                
                <div class="form-group">
                    <label for="edit_username">Username</label>
                    <input type="text" id="edit_username" name="username" value="test" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_password">Password (leave blank to keep current)</label>
                    <div class="password-field">
                        <input type="password" id="edit_password" name="password">
                        <i class="fas fa-eye password-toggle" onclick="togglePasswordVisibility('edit_password')"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_firstname">First Name</label>
                    <input type="text" id="edit_firstname" name="firstname" value="test" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_lastname">Last Name</label>
                    <input type="text" id="edit_lastname" name="lastname" value="test" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" value="test@gmail.com" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status" required class="status-select">
                        <option value="active" class="status-option">Active</option>
                        <option value="deactivate" class="status-option">Deactivate</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-update">Update Admin</button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal">
        <div class="delete-modal-content">
            <h3 class="delete-modal-title">Delete Administrator</h3>
            <p class="delete-modal-message">Are you sure you want to delete this administrator? This action cannot be undone.</p>
            <div class="delete-modal-actions">
                <button class="btn-cancel" onclick="closeDeleteModal()">
                    Cancel
                </button>
                <button class="btn-confirm-delete" onclick="confirmDelete()">
                    Delete
                </button>
            </div>
        </div>
    </div>

    <script src="./js/admin-manage.js"></script>
</body>
</html> 