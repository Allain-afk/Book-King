<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in. Session data: " . print_r($_SESSION, true));
    header('Location: ../index.php');
    exit();
}

// Debug: Log successful dashboard access
error_log("Dashboard accessed by user: " . $_SESSION['username']);

// Include database connection
$pdo = require '../database/db_connection.php';

// Function to get random cover URL
function getRandomCover($title, $author) {
    // List of background colors (pastel colors)
    $colors = [
        'F8B195', 'F67280', 'C06C84', '6C5B7B', '355C7D', // warm to cool
        'A8E6CF', 'DCEDC1', 'FFD3B6', 'FFAAA5', 'FF8B94', // nature
        'B5EAD7', 'C7CEEA', 'E2F0CB', 'FFDAC1', 'FFB7B2', // soft
        'E7D3EA', 'DCD3FF', 'B5D8EB', 'BBE1FA', 'D6E5FA'  // pastel
    ];
    
    // Get random color
    $bgColor = $colors[array_rand($colors)];
    
    // Format title and author for URL
    $text = urlencode($title . "\nby " . $author);
    
    return "https://placehold.co/400x600/${bgColor}/333333/png?text=${text}";
}

// Get the logged-in user's full name with null coalescing operator
$firstName = $_SESSION['first_name'] ?? 'User';
$lastName = $_SESSION['last_name'] ?? '';
$userFullName = trim($firstName . ' ' . $lastName);

// Get user's profile picture from database
try {
    $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profilePicture = $stmt->fetchColumn();
    $_SESSION['profile_picture'] = $profilePicture; // Update session with latest picture
} catch (PDOException $e) {
    error_log("Error fetching profile picture: " . $e->getMessage());
    $profilePicture = 'default.jpg';
}

// Get borrow and return counts
try {
    // Get total borrows count
    $stmtBorrows = $pdo->prepare("SELECT COUNT(*) FROM borrowed_books WHERE user_id = ?");
    $stmtBorrows->execute([$_SESSION['user_id']]);
    $totalBorrows = $stmtBorrows->fetchColumn();

    // Get total returns count
    $stmtReturns = $pdo->prepare("SELECT COUNT(*) FROM borrowed_books WHERE user_id = ? AND return_date IS NOT NULL");
    $stmtReturns->execute([$_SESSION['user_id']]);
    $totalReturns = $stmtReturns->fetchColumn();

    // Get total books count
    $stmtBooks = $pdo->prepare("SELECT COUNT(*) FROM books");
    $stmtBooks->execute();
    $totalBooks = $stmtBooks->fetchColumn();
} catch (PDOException $e) {
    $totalBorrows = 0;
    $totalReturns = 0;
    $totalBooks = 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/user-dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/user-dashboard-2.css?v=<?php echo time();?>">
    <link rel="stylesheet" href="../assets/css/settings-modal.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script>
        function updateTime() {
            const now = new Date();

            // Update time
            const timeString = now.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            document.getElementById('current-time').textContent = timeString;

            // Update date
            const dateString = now.toLocaleDateString('en-US', {
                month: 'short',
                day: '2-digit',
                year: 'numeric'
            });
            document.getElementById('current-date').textContent = dateString;
        }

        // Update time every second
        setInterval(updateTime, 1000);

        // Initial call to display time immediately
        document.addEventListener('DOMContentLoaded', updateTime);
    </script>
</head>

<body>
    <div class="sidebar">
        <div class="logo-container">
            <img src="../images/logo.png" alt="Book King Logo">
        </div>
        <div class="sidebar-item home" onclick="window.location.href='user-dashboard.php'">
            <img src="../images/element-2 2.svg" alt="Home" class="icon-image">
        </div>
        <div class="sidebar-item list" onclick="window.location.href='user-return-books.php'">
            <img src="../images/Vector.svg" alt="List" class="icon-image">
        </div>
        <div class="sidebar-item book" onclick="window.location.href='user-borrow-books.php'">
            <img src="../images/book.png" alt="Book" class="icon-image">
        </div>
        <div class="sidebar-item logout" onclick="handleLogout()">
            <img src="../images/logout 3.png" alt="Logout" class="icon-image">
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="user-info">
                <div class="user-icon" onclick="openSettingsModal()" style="cursor: pointer;">
                    <?php
                    $profilePicturePath = $profilePicture !== 'default.jpg' 
                        ? '../uploads/profile_pictures/' . $profilePicture 
                        : '../images/user.png';
                    ?>
                    <img src="<?php echo htmlspecialchars($profilePicturePath); ?>" alt="User" class="profile-picture">
                </div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($userFullName); ?></div>
                    <div class="user-role">User</div>
                </div>
            </div>
            <div class="quote-container" style="flex: 1; margin: 15px 0 0 20px; max-width: 550px; text-align: center;">
                <div id="quoteCarousel" class="quotes-carousel" style="height: 100%; display: flex; flex-direction: column; justify-content: center;">
                    <div class="quote-slide fade active" style="margin: 0 0 12px 0;">
                        <div class="quote-text" style="font-size: 16px; line-height: 1.4; margin-bottom: 6px; font-weight: 500;">"A book is a gateway to other worlds, a key to unlock imagination's door."</div>
                        <div class="quote-author" style="font-size: 13px; font-style: italic;">~ Neil Gaiman</div>
                    </div>
                    <div class="quote-slide fade" style="margin: 0 0 12px 0;">
                        <div class="quote-text" style="font-size: 16px; line-height: 1.4; margin-bottom: 6px; font-weight: 500;">"The more that you read, the more things you will know. The more that you learn, the more places you'll go."</div>
                        <div class="quote-author" style="font-size: 13px; font-style: italic;">~ Dr. Seuss</div>
                    </div>
                </div>
            </div>
            <div class="time">
                <div id="current-time" class="current-time"></div>
                <div id="current-date" class="current-date"></div>
            </div>
            <div class="settings-icon">
                <img src="../images/Vector.png" alt="Settings" style="cursor: pointer;" onclick="openSettingsModal()">
            </div>
        </div>

        <div class="dashboard-grid">
            <a href="user-return-books.php?tab=borrowed" class="card-link">
                <div class="card">
                    <div class="card-icon">
                        <img src="../images/book-square 2.png" alt="Borrowed Books">
                    </div>
                    <div class="card-content">
                        <h3>Your Borrowed Books</h3>
                        <p>Track and manage your currently borrowed books. Stay updated on due dates and book status.</p>
                        <div class="card-stats">
                            <span class="stat-number"><?php echo $totalBorrows - $totalReturns; ?></span>
                            <span class="stat-label">Currently Borrowed</span>
                        </div>
                    </div>
                </div>
            </a>

            <a href="user-return-books.php?tab=returned" class="card-link">
                <div class="card">
                    <div class="card-icon">
                        <img src="../images/redo 1.png" alt="Returned Books">
                    </div>
                    <div class="card-content">
                        <h3>Your Return History</h3>
                        <p>View your complete book return history and past borrowing activities.</p>
                        <div class="card-stats">
                            <span class="stat-number"><?php echo $totalReturns; ?></span>
                            <span class="stat-label">Books Returned</span>
                        </div>
                    </div>
                </div>
            </a>

            <a href="user-borrow-books.php" class="card-link">
                <div class="card">
                    <div class="card-icon">
                        <img src="../images/browse.png" alt="Browse Books">
                    </div>
                    <div class="card-content">
                        <h3>Browse Available Books</h3>
                        <p>Discover and borrow from our extensive collection of books and resources.</p>
                        <div class="card-stats">
                            <span class="stat-number"><?php echo $totalBooks; ?></span>
                            <span class="stat-label">Total Books</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div style="margin: 25px auto 0; padding: 25px; width: 100%; max-width: 98%; text-align: center; background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(176, 113, 84, 0.1);">
            <h3 style="margin-bottom: 20px; color: #B07154; font-size: 1.4rem; font-weight: 600;">Books You Might Like</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 12px; padding: 0 8px;" id="popular-books-container">
                <?php
                try {
                    // Get 24 random books from the database
                    $stmt = $pdo->prepare("
                        SELECT b.*, 
                            CASE 
                                WHEN EXISTS (
                                    SELECT 1 FROM borrowed_books bb 
                                    WHERE bb.book_id = b.book_id 
                                    AND bb.return_date IS NULL
                                ) THEN 'Unavailable'
                                ELSE 'Available'
                            END as availability
                        FROM books b
                        ORDER BY RAND()
                        LIMIT 24
                    ");
                    $stmt->execute();
                    $recommendedBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($recommendedBooks as $book) {
                        // Use the book's cover image if available, otherwise generate one
                        $coverImage = !empty($book['cover_image_url']) ? 
                            htmlspecialchars($book['cover_image_url']) : 
                            getRandomCover($book['title'], $book['author']);

                        echo '
                        <div style="transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform=\'translateY(-5px)\'" onmouseout="this.style.transform=\'translateY(0)\'">
                            <a href="book-details.php?id=' . htmlspecialchars($book['book_id']) . '" style="text-decoration:none;">
                            <div style="position: relative; width: 100%; padding-bottom: 142%; margin-bottom: 6px;">
                                    <img src="' . $coverImage . '" 
                                         alt="' . htmlspecialchars($book['title']) . '"
                                         style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 15px rgba(176, 113, 84, 0.1);">
                            </div>
                            <div style="padding: 0 2px;">
                                <div style="font-size:11px; font-weight:600; color:#B07154; margin-bottom:2px; line-height:1.3; height:28px; overflow:hidden;">
                                        ' . htmlspecialchars(strlen($book['title']) > 40 ? substr($book['title'], 0, 40) . '...' : $book['title']) . '
                                </div>
                                <div style="font-size:10px; color:#666; line-height:1.2; height:12px; overflow:hidden;">
                                        ' . htmlspecialchars($book['author']) . '
                                    </div>
                                    <div style="font-size:10px; color:' . ($book['availability'] === 'Available' ? '#4CAF50' : '#FF5722') . '; margin-top:4px;">
                                        ' . htmlspecialchars($book['availability']) . '
                                    </div>
                                </div>
                            </a>
                        </div>';
                    }
                } catch (PDOException $e) {
                    echo '<div style="grid-column:1/-1; padding:20px; color:#B07154; font-size:14px;">Unable to load recommendations at the moment. Please try again later.</div>';
                }
                ?>
                            </div>
                </div>

        <style>
            .card-stats {
                margin-top: 12px;
                padding-top: 10px;
                border-top: 1px solid rgba(176, 113, 84, 0.1);
                text-align: center;
            }
            .stat-number {
                display: block;
                font-size: 20px;
                font-weight: 700;
                color: #B07154;
                margin-bottom: 2px;
            }
            .stat-label {
                font-size: 12px;
                color: #666;
            }
            .dashboard-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
                padding: 20px;
                max-width: 98%;
                margin: 0 auto;
            }
            .card {
                background: #FFFFFF;
                border-radius: 12px;
                padding: 15px;
                box-shadow: 0 4px 15px rgba(176, 113, 84, 0.1);
                transition: all 0.3s ease;
                height: 100%;
                max-height: 220px;
            }
            .card-icon {
                width: 40px;
                height: 40px;
                margin-bottom: 12px;
            }
            .card-icon img {
                width: 100%;
                height: 100%;
                object-fit: contain;
            }
            .card-content h3 {
                color: #B07154;
                font-size: 15px;
                font-weight: 600;
                margin-bottom: 8px;
                line-height: 1.3;
            }
            .card-content p {
                color: #666;
                font-size: 12px;
                line-height: 1.4;
                margin-bottom: 5px;
                display: -webkit-box;
                display: box;
                -webkit-line-clamp: 2;
                line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                height: 34px;
            }
            @media screen and (max-width: 1024px) {
                .dashboard-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            @media screen and (max-width: 768px) {
                .dashboard-grid {
                    grid-template-columns: 1fr;
                }
                .card {
                    max-height: 200px;
                }
            }
        </style>

        <script>
            function handleLogout() {
                window.location.href = 'logout.php';
            }
        </script>

        <!-- Settings Modal -->
        <div id="settingsModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Settings</h2>
                    <span class="close" onclick="closeSettingsModal()">×</span>
                </div>
                <div class="settings-form-container">
                    <!-- Profile Picture Section -->
                    <div class="profile-section">
                        <h3>Profile Picture</h3>
                        <div class="profile-picture-container" style="margin-bottom: 20px;">
                            <?php
                            $modalProfilePicture = isset($_SESSION['profile_picture']) && $_SESSION['profile_picture'] !== 'default.jpg' 
                                ? '../uploads/profile_pictures/' . $_SESSION['profile_picture'] 
                                : '../images/user.png';
                            ?>
                            <div class="profile-icon">
                                <img src="<?php echo htmlspecialchars($modalProfilePicture); ?>" alt="Profile Picture" class="profile-picture">
                            </div>
                            <div class="profile-picture-actions">
                                <input type="file" id="settings-profile-picture-input" accept="image/*" style="display: none;">
                                <label for="settings-profile-picture-input" class="action-btn change-btn">Change Picture</label>
                                <button type="button" onclick="removeProfilePicture()" class="action-btn remove-btn">Remove Picture</button>
                            </div>
                        </div>
                    </div>

                    <div style="border-top: 1px solid #eee; margin: 20px 0;"></div>

                    <!-- Password Change Section -->
                    <div class="password-section">
                        <h3>Change Password</h3>

                        <form id="settingsForm" onsubmit="return changePassword(event)">
                            <div class="form-group">
                                <label for="currentPassword">Current Password</label>
                                <div class="input-container">
                                    <input type="password" id="currentPassword" name="currentPassword" required>
                                    <i class="fa-regular fa-eye-slash toggle-password" onclick="togglePassword('currentPassword', this)"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="newPassword">New Password</label>
                                <div class="input-container">
                                    <input type="password" id="newPassword" name="newPassword" required>
                                    <i class="fa-regular fa-eye-slash toggle-password" onclick="togglePassword('newPassword', this)"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirmPassword">Confirm New Password</label>
                                <div class="input-container">
                                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                                    <i class="fa-regular fa-eye-slash toggle-password" onclick="togglePassword('confirmPassword', this)"></i>
                                </div>
                            </div>

                            <div class="button-group">
                                <button type="button" class="cancel-btn" onclick="closeSettingsModal()">Cancel</button>
                                <button type="submit" class="confirm-btn">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Add profile picture upload handler for settings modal
            document.addEventListener('DOMContentLoaded', function() {
                const settingsProfilePictureInput = document.getElementById('settings-profile-picture-input');
                if (settingsProfilePictureInput) {
                    settingsProfilePictureInput.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (file) {
                            const formData = new FormData();
                            formData.append('profilePicture', file);

                            fetch('update_user_settings.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                window.location.reload();
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while uploading the profile picture');
                            });
                        }
                    });
                }
            });

            function openSettingsModal() {
                const modal = document.getElementById('settingsModal');
                if (modal) {
                    modal.classList.add('show');
                    console.log('Modal opened'); // Debug log
                }
            }

            function closeSettingsModal() {
                const modal = document.getElementById('settingsModal');
                if (modal) {
                    modal.classList.remove('show');
                    console.log('Modal closed'); // Debug log
                }
            }

            // Add event listener for clicking outside the modal
            window.onclick = function(event) {
                const modal = document.getElementById('settingsModal');
                if (event.target == modal) {
                    closeSettingsModal();
                }
            }

            function togglePassword(inputId, icon) {
                const input = document.getElementById(inputId);
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            }

            function removeProfilePicture() {
                fetch('update_user_settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'remove_picture=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload(); // Reload the page to show default picture
                    } else {
                        showNotification('Failed to remove profile picture', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while removing the profile picture', 'error');
                });
            }

            function showNotification(message, type = 'success') {
                const notification = document.getElementById('notification');
                const messageElement = document.getElementById('notification-message');
                const icon = notification.querySelector('i');
                
                // Remove existing classes
                notification.classList.remove('success', 'error');
                
                // Add appropriate class and icon
                if (type === 'success') {
                    notification.classList.add('success');
                    icon.className = 'fas fa-check-circle';
                } else {
                    notification.classList.add('error');
                    icon.className = 'fas fa-times-circle';
                }
                
                messageElement.textContent = message;
                notification.classList.add('show');
                
                setTimeout(() => {
                    notification.classList.remove('show');
                }, 3000);
            }

            function changePassword(event) {
                event.preventDefault();
                
                const currentPassword = document.getElementById('currentPassword').value;
                const newPassword = document.getElementById('newPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;

                if (newPassword !== confirmPassword) {
                    showNotification('New passwords do not match!', 'error');
                    return false;
                }

                const formData = new FormData();
                formData.append('currentPassword', currentPassword);
                formData.append('newPassword', newPassword);

                fetch('update_user_settings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Password updated successfully!', 'success');
                        document.getElementById('settingsForm').reset();
                        closeSettingsModal();
                    } else {
                        showNotification(data.error || 'Failed to update password', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while updating the password', 'error');
                });

                return false;
            }
        </script>
    </div>

    <!-- Add this notification div -->
    <div id="notification" class="notification">
        <div class="notification-content">
            <i class="fas fa-check-circle"></i>
            <span id="notification-message"></span>
        </div>
    </div>
</body>

</html>