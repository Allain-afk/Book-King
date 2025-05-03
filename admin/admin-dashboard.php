<?php
// Start the session
session_start();

// Include the database connection
$pdo = require '../database/db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin-login.php');
    exit();
}

// Fetch admin details
$adminFirstName = $_SESSION['admin_first_name'] ?? 'Admin';

error_log('Admin First Name in Dashboard: ' . ($_SESSION['admin_first_name'] ?? 'Not Set'));

// Get counts from database
try {
    $counts = [
        'users' => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'books' => $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn(),
        'branches' => $pdo->query('SELECT COUNT(*) FROM branches')->fetchColumn()
    ];
    
    // Debug book information
    $debugBooks = $pdo->query('SELECT book_id, title FROM books ORDER BY book_id')->fetchAll(PDO::FETCH_ASSOC);
    error_log("Book details:");
    foreach ($debugBooks as $book) {
        error_log("ID: {$book['book_id']} - Title: {$book['title']}");
    }
    
} catch (PDOException $e) {
    error_log("Error fetching counts: " . $e->getMessage());
    $counts = ['users' => 0, 'books' => 0, 'branches' => 0];
}

// Function to get admins
function getAdmins($pdo) {
    try {
        $stmt = $pdo->query('SELECT * FROM admin');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching admins: " . $e->getMessage());
        return [];
    }
}

// Function to get branches
function getBranches($pdo) {
    try {
        $stmt = $pdo->query('SELECT branch_id, branch_name, branch_location FROM branches');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching branches: " . $e->getMessage());
        return [];
    }
}

// Function to get borrowers
function getBorrowers($pdo) {
    try {
        $borrowersQuery = $pdo->query("
            SELECT bb.id, bb.user_id, bb.book_id, bb.borrow_date, bb.due_date, 
                   u.FirstName, u.LastName, b.title
            FROM borrowed_books bb
            JOIN users u ON bb.user_id = u.user_id
            JOIN books b ON bb.book_id = b.book_id
            WHERE bb.return_date IS NULL
            ORDER BY bb.due_date ASC
            LIMIT 5
        ");
        return $borrowersQuery->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching borrowers: " . $e->getMessage());
        return [];
    }
}

// Add this function after other database functions
function getBorrowerStats($pdo) {
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

            return [
                'overdue' => $overduePercentage,
                'onTime' => $onTimePercentage
            ];
        }
        return ['overdue' => 0, 'onTime' => 100];
    } catch (PDOException $e) {
        error_log("Error fetching borrower stats: " . $e->getMessage());
        return ['overdue' => 0, 'onTime' => 100];
    }
}

$admins = getAdmins($pdo);
$branches = getBranches($pdo);
$borrowers = getBorrowers($pdo);
$borrowerStats = getBorrowerStats($pdo);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/admin-dashboard-2.css">
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
            <a href="../admin/admin-dashboard.php" class="nav-item active">
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
                    <div class="admin-name-1">Welcome, <?php echo htmlspecialchars($adminFirstName); ?>!</div>
                </div>
            </div>
            <div class="datetime-display">
                <div class="time-section">
                    <svg class="time-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path fill="#B07154" d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.59-8 8-8 8 3.589 8 8-3.589 8-8 8z"/>
                        <path fill="#B07154" d="M13 7h-2v6l4.5 2.7.7-1.2-3.2-1.9z"/>
                    </svg>
                    <span class="time-display">--:--:-- --</span>
                </div>
                <div class="date-section">
                    <svg class="date-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path fill="#B07154" d="M19 4h-2V3a1 1 0 0 0-2 0v1H9V3a1 1 0 0 0-2 0v1H5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3zm1 15a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-7h16v7zm0-9H4V7a1 1 0 0 1 1-1h2v1a1 1 0 0 0 2 0V6h6v1a1 1 0 0 0 2 0V6h2a1 1 0 0 1 1 1v3z"/>
                    </svg>
                    <span class="date-display">--- --, ----</span>
                </div>
            </div>
        </div>
        <div class="main-content">
            <div class="chart-section">
                <h2 class="chart-title">Borrower Status Distribution</h2>
                <div class="chart-container">
                    <canvas id="borrowerChart"></canvas>
                </div>
                <div class="chart-legend">
                    <div class="legend-item">
                        <div class="legend-color borrowed"></div>
                        <span class="legend-text">Overdue</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color returned"></div>
                        <span class="legend-text">On Time</span>
                    </div>
                </div>
            </div>

            <div class="stats-container">
                <a href="../admin/user-management.php" class="stats-card" style="text-decoration: none; cursor: pointer; transition: transform 0.2s ease-in-out;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                    <div class="stats-icon-container">
                        <img src="../images/user.png" alt="Total Users" class="stats-icon">
                    </div>
                    <div class="stats-value"><?= htmlspecialchars($counts['users']) ?></div>
                    <div class="stats-label">Total Users</div>
                </a>
                <a href="../admin/book-management.php" class="stats-card" style="text-decoration: none; cursor: pointer; transition: transform 0.2s ease-in-out;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                    <div class="stats-icon-container">
                        <img src="../images/book.png" alt="Total Books" class="stats-icon">
                    </div>
                    <div class="stats-value"><?= htmlspecialchars($counts['books']) ?></div>
                    <div class="stats-label">Total Book Count</div>
                </a>
                <a href="../admin/branch-management.php" class="stats-card" style="text-decoration: none; cursor: pointer; transition: transform 0.2s ease-in-out;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                    <div class="stats-icon-container">
                        <img src="../images/buildings-2 1.png" alt="Branch Count" class="stats-icon">
                    </div>
                    <div class="stats-value"><?= htmlspecialchars($counts['branches']) ?></div>
                    <div class="stats-label">Branch Count</div>
                </a>
            </div>
            <div class="cards-column">
                <div class="card">
                    <h2 class="card-title">Overdue Borrowers</h2>
                    <div class="borrower-list">
                        <?php if (!empty($borrowers)): ?>
                            <?php foreach ($borrowers as $borrower): ?>
                                <?php
                                $dueDate = new DateTime($borrower['due_date']);
                                $today = new DateTime();
                                $isOverdue = $dueDate < $today;
                                ?>
                                <a href="../admin/borrowers-management.php" class="borrower-item">
                                    <div class="borrower-icon">
                                        <img src="../images/user.png" alt="User">
                                    </div>
                                    <div class="borrower-info">
                                        <div class="borrower-name"><?= htmlspecialchars($borrower['FirstName'] . ' ' . $borrower['LastName']) ?></div>
                                        <div class="borrower-id">Borrowed ID: <?= htmlspecialchars($borrower['id']) ?></div>
                                        <?php if ($isOverdue): ?>
                                            <div class="overdue-status">Overdue</div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-borrowers-message">
                                <p>No Books Borrowed</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="bottom-row">
                    <div class="admins-section">
                        <div class="admins-title">Book King Admins</div>
                        <div class="admin-list">
                            <?php foreach ($admins as $admin): ?>
                                <a href="../admin/admin-profile.php?id=<?= htmlspecialchars($admin['admin_id']) ?>" 
                                   class="admin-card" style="min-height: 72px;">
                                    <div class="admin-icon">
                                        <img src="../images/security-user 1.png" alt="Admin">
                                    </div>
                                    <div style="flex: 1; display: flex; flex-direction: column; justify-content: center;">
                                        <div class="admin-name" style="line-height: 1.2;"><?= htmlspecialchars($admin['FirstName'] . ' ' . $admin['LastName']) ?></div>
                                        <div class="admin-id" style="line-height: 1.2;"><?= htmlspecialchars($admin['admin_id']) ?></div>
                                    </div>
                                    <div class="status-text <?= $admin['Status'] === 'active' ? 'active' : 'deactive' ?>" style="align-self: center;">
                                        <?= ucfirst($admin['Status'] ?? 'active') ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="branch-card">
                        <h2 class="branch-title">Branch Network</h2>
                        <div class="branch-list">
                            <?php if (!empty($branches)): ?>
                                <?php foreach ($branches as $branch): ?>
                                    <a href="../admin/branch-management.php" class="branch-item">
                                        <div class="branch-icon">
                                            <img src="../images/buildings-2 1.png" alt="Branch Building">
                                        </div>
                                        <div class="branch-info">
                                            <div class="branch-name"><?= htmlspecialchars($branch['branch_name']) ?></div>
                                            <div class="branch-id"><?= htmlspecialchars($branch['branch_location']) ?></div>
                                        </div>
                                        <div class="maximize-icon">
                                            <img src="../images/maximize-circle 1 (1).png" alt="View Details">
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-branches">
                                    <p>No branches registered</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
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

            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            const sidebar = document.querySelector('.sidebar');
            const content = document.querySelector('.content');
            const body = document.body;

            // Create overlay element
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            body.appendChild(overlay);

            function toggleMenu() {
                mobileMenuBtn.classList.toggle('active');
                sidebar.classList.toggle('active');
                content.classList.toggle('sidebar-active');
                overlay.classList.toggle('active');
                body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            }

            mobileMenuBtn.addEventListener('click', toggleMenu);
            overlay.addEventListener('click', toggleMenu);

            // Close menu when clicking a nav item on mobile
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.addEventListener('click', () => {
                    if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                        toggleMenu();
                    }
                });
            });

            // Handle resize events
            let resizeTimer;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    if (window.innerWidth > 768) {
                        mobileMenuBtn.classList.remove('active');
                        sidebar.classList.remove('active');
                        content.classList.remove('sidebar-active');
                        overlay.classList.remove('active');
                        body.style.overflow = '';
                    }
                }, 250);
            });

            // Chart initialization
            const ctx = document.getElementById('borrowerChart').getContext('2d');
            
            // Create gradients
            const overdueGradient = ctx.createLinearGradient(0, 0, 0, 400);
            overdueGradient.addColorStop(0, '#B07154');
            overdueGradient.addColorStop(1, '#8B5B43');

            const onTimeGradient = ctx.createLinearGradient(0, 0, 0, 400);
            onTimeGradient.addColorStop(0, '#F4DECB');
            onTimeGradient.addColorStop(1, '#E4C4A9');

            const data = {
                labels: ['Overdue', 'On Time'],
                datasets: [{
                    data: [<?= $borrowerStats['overdue'] ?>, <?= $borrowerStats['onTime'] ?>],
                    backgroundColor: [overdueGradient, onTimeGradient],
                    borderWidth: 0,
                    borderRadius: 10,
                    hoverOffset: 20
                }]
            };

            const config = {
                type: 'doughnut',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    layout: {
                        padding: {
                            top: 20,
                            bottom: 20,
                            left: 20,
                            right: 20
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'white',
                            titleColor: '#2C3E50',
                            bodyColor: '#2C3E50',
                            bodyFont: {
                                family: 'Montserrat',
                                size: 14
                            },
                            titleFont: {
                                family: 'Montserrat',
                                size: 16,
                                weight: 'bold'
                            },
                            padding: 12,
                            boxWidth: 10,
                            boxHeight: 10,
                            boxPadding: 3,
                            usePointStyle: true,
                            callbacks: {
                                label: function(context) {
                                    const value = context.raw;
                                    return `${value}% of Total Borrowers`;
                                }
                            }
                        }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true,
                        duration: 2000,
                        easing: 'easeInOutQuart'
                    }
                }
            };

            const borrowerChart = new Chart(ctx, config);

            // Update the updateChartData function to fetch real-time data
            function updateChartData() {
                fetch('get_borrower_stats.php')
                    .then(response => response.json())
                    .then(data => {
                        borrowerChart.data.datasets[0].data = [data.overdue, data.onTime];
                        borrowerChart.update('none');
                    })
                    .catch(error => console.error('Error updating chart:', error));
            }

            // Update chart every 5 seconds
            setInterval(updateChartData, 5000);

            // Add hover effects to legend items
            const legendItems = document.querySelectorAll('.legend-item');
            legendItems.forEach((item, index) => {
                item.addEventListener('mouseenter', () => {
                    borrowerChart.setActiveElements([{ datasetIndex: 0, index: index }]);
                    borrowerChart.update();
                });
                
                item.addEventListener('mouseleave', () => {
                    borrowerChart.setActiveElements([]);
                    borrowerChart.update();
                });
            });
        });
    </script>
</body>

</html>