<?php
// Start the session
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle logout
if (isset($_GET['logout'])) {
    // Destroy the session
    session_destroy();
    // Redirect to login page
    header('Location: admin-login.php');
    exit();
}

// Initialize variables for error messages
$error = '';

try {
    // Include the database connection
    $pdo = require '../database/db_connection.php';
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    $error = "Database connection error. Please check your configuration.";
}

// Add this function after session_start()
function getClientIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

// Function to get location info
function getLocationInfo($ip) {
    try {
        $url = "http://ip-api.com/json/" . $ip;
        $response = @file_get_contents($url);
        
        if ($response) {
            $data = json_decode($response, true);
            if ($data && $data['status'] === 'success') {
                return [
                    'city' => $data['city'] ?? 'Unknown City',
                    'region' => $data['regionName'] ?? 'Unknown Region',
                    'country' => $data['country'] ?? 'Unknown Country',
                    'ip' => $ip
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching location data: " . $e->getMessage());
    }
    
    return [
        'city' => 'Local',
        'region' => 'Network',
        'country' => '',
        'ip' => $ip
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the username and password from the form
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Debug login attempt
    error_log("Login attempt - Username: " . $username);

    // Check if username and password are provided
    if (!empty($username) && !empty($password)) {
        try {
            if (!isset($pdo)) {
                throw new Exception("Database connection is not available");
            }
            
            // Change the query to check the admin table
            $stmt = $pdo->prepare('SELECT * FROM admin WHERE username = ? AND password = ? AND Status = ?');
            $stmt->execute([$username, $password, 'active']);
            $admin = $stmt->fetch();

            if ($admin) {
                // Debug successful login
                error_log("Login successful for username: " . $username);
                
                // Get location information
                $ip = getClientIP();
                $locationInfo = getLocationInfo($ip);
                
                // Add login success log
                error_log("Admin login successful: " . $username . " from " . $ip);
                
                // Format location string
                $location = trim(implode(', ', array_filter([
                    $locationInfo['city'],
                    $locationInfo['region'],
                    $locationInfo['country']
                ])));
                
                // Add IP address if not local
                if ($locationInfo['city'] !== 'Local') {
                    $location .= " ({$locationInfo['ip']})";
                }

                // Update last login time and location
                $updateStmt = $pdo->prepare("
                    UPDATE admin 
                    SET last_login = NOW(),
                        login_location = ?
                    WHERE admin_id = ?
                ");
                $updateStmt->execute([$location, $admin['admin_id']]);

                // Set session variables
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_first_name'] = $admin['FirstName'];
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_status'] = $admin['Status'];

                // Debug session data
                error_log("Session data after login: " . print_r($_SESSION, true));

                // Redirect to the admin dashboard
                header('Location: admin-dashboard.php');
                exit();
            } else {
                error_log("Login failed - Invalid credentials for username: " . $username);
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'A system error occurred. Please try again later.';
        }
    } else {
        $error = 'Please fill in both fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-login.css">
    </head>

<body>
    <div class="split-container">
        <div class="login-side">
            <div class="login-container">
                <div class="login-logo">
                    <img src="../images/logo2.png" alt="Book King Logo">
                </div>
                <h1 class="welcome-text">Welcome Back!</h1>
                <p class="login-subtitle">Sign in to access your admin dashboard</p>

                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="input-container">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="username" class="input-field" placeholder="Enter your username" required autocomplete="off">
                    </div>
                    <div class="input-container">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="password" class="input-field" placeholder="Enter your password" required>
                        <i class="fas fa-eye-slash toggle-password" onclick="togglePassword()"></i>
                    </div>
                    <button type="submit" class="signin-btn">
                        <span class="signin-btn-text">Sign In</span>
                    </button>
                </form>

            </div>
        </div>

        <div class="brand-side">
            <div class="brand-content">
                <img src="../images/logo2.png" alt="Logo" class="brand-logo">
                <p class="brand-description">
                    Welcome to the Library Management System. Manage users, books, and resources efficiently.
                    <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                    <div class="admin-actions" style="margin-top: 20px;">
                        <a href="admin-manage.php" style="color: white; text-decoration: none; display: inline-block; padding: 10px 20px; background: rgba(255,255,255,0.2); border-radius: 8px; margin: 5px;">
                            <i class="fas fa-users-cog"></i> Manage Administrators
                        </a>
                        <a href="admin-dashboard.php" style="color: white; text-decoration: none; display: inline-block; padding: 10px 20px; background: rgba(255,255,255,0.2); border-radius: 8px; margin: 5px;">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </div>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }
    </script>
</body>

</html>