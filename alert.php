<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session_check.php';

// Get current user information from session
$user_id = $current_user_id;
$username = $current_username;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SavePlate Connect - Alerts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="browsestyle.css">
    <link rel="stylesheet" href="alertstyle.css">
</head>
<body>
    <!-- Header Section -->
    <header class="header">
        <div class="nav-logo">
            <img src="Logo.png" alt="SavePlate Connect Logo" class="logo-img">
        </div>
        
        <div class="user-date-info">
            <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
            <span class="date-info">
                <?php
                date_default_timezone_set('UTC');
                $currentDate = date('d/m/Y');
                $currentDay = date('D');
                echo $currentDay . " | " . $currentDate;
                ?>
            </span>
            <a href="logout.php" class="logout-btn" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </header>

    <!-- Page Navigation Menu -->
    <nav class="page-nav">
        <div class="nav-menu">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="inventory.php" class="nav-link">Inventory</a>
                </li>
                <li class="nav-item">
                    <a href="browse.php" class="nav-link">Browse</a>
                </li>
                <li class="nav-item">
                    <a href="analytics.php" class="nav-link">Analytics</a>
                </li>
                <li class="nav-item">
                    <a href="alert.php" class="nav-link active">Alerts</a>
                </li>
                <li class="nav-item">
                    <a href="meal.php" class="nav-link">Meal</a>
                </li>
                <li class="nav-item">
                    <a href="donate.php" class="nav-link">Donate</a>
                </li>
            </ul>
            <div class="active-indicator"></div>
        </div>
        
        <div class="hamburger">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </div>
    </nav>

    <!-- Alerts Content -->
    <main class="main-content">
        <div class="page-content">
            <div class="alerts-layout">
                <div class="alerts-header">
                    <h1 class="alerts-title">Food Alerts & Notifications</h1>
                    <p class="alerts-subtitle">Stay informed about your food items and important updates</p>
                </div>

                <!-- Alert Summary Section -->
                <div class="alert-summary-card">
                    <div class="alert-counts">
                        <div class="count-item">
                            <div class="count-value" id="totalAlerts">0</div>
                            <div class="count-label">TOTAL ALERTS</div>
                        </div>
                        <div class="count-item">
                            <div class="count-value unread" id="unreadAlerts">0</div>
                            <div class="count-label">UNREAD</div>
                        </div>
                    </div>
                    <div class="alert-actions">
                        <button class="action-btn btn-refresh" id="refreshBtn" title="Refresh">
                            <i class="fas fa-sync-alt"></i>
                            <span>Refresh</span>
                        </button>
                        <button class="action-btn btn-mark-all" id="markAllReadBtn" title="Mark All as Read">
                            <i class="fas fa-check-double"></i>
                            <span>Mark All as Read</span>
                        </button>
                        <button class="action-btn btn-clear-all" id="clearAllBtn" title="Clear All">
                            <i class="fas fa-trash"></i>
                            <span>Clear All</span>
                        </button>
                    </div>
                </div>

                <!-- Filter Buttons -->
                <div class="alert-filters">
                    <button class="filter-btn active" data-filter="all" id="filterAll">
                        <i class="fas fa-list"></i>
                        <span>All Alerts</span>
                    </button>
                    <button class="filter-btn" data-filter="unread" id="filterUnread">
                        <i class="fas fa-envelope"></i>
                        <span>Unread</span>
                    </button>
                    <button class="filter-btn" data-filter="inventory" id="filterInventory">
                        <i class="fas fa-box"></i>
                        <span>Inventory</span>
                    </button>
                    <button class="filter-btn" data-filter="donations" id="filterDonations">
                        <i class="fas fa-hand-holding-heart"></i>
                        <span>Donations</span>
                    </button>
                    <button class="filter-btn" data-filter="meal_plans" id="filterMealPlans">
                        <i class="fas fa-utensils"></i>
                        <span>Meal Plans</span>
                    </button>
                </div>

                <div class="alerts-grid" id="alertsGrid"></div>
                <div class="alert-empty" id="alertsEmpty" style="display:none;">
                    <p>No alerts yet. You're all caught up!</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Alert Detail Modal -->
    <div class="alert-modal" id="alertModal">
        <div class="alert-modal-content">
            <div class="alert-modal-header">
                <h3 id="modalTitle">Alert Detail</h3>
                <button class="alert-modal-close" id="closeAlertModal">&times;</button>
            </div>
            <p class="alert-modal-message" id="modalMessage"></p>
            <div class="alert-modal-time" id="modalTime"></div>
            <button class="btn btn-outline" id="modalLinkBtn" style="display:none;">Open Page</button>
        </div>
    </div>

    <script>
        window.CURRENT_USER_ID = <?php echo $user_id; ?>;
    </script>
    <script src="browsescript.js"></script>
    <script src="alertscript.js"></script>
</body>
</html>
