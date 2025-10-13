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
    <title>SavePlate Connect - Dashboard</title>
    <link rel="stylesheet" href="browsestyle.css">
    <link rel="stylesheet" href="dashboardstyle.css">
</head>
<body>
    <!-- Header Section -->
    <header class="header">
        <div class="nav-logo">
            <a href="index.html" class="logo-link">
                <img src="Logo.png" alt="SavePlate Connect Logo" class="logo-img">
            </a>
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
            </a>
        </div>
    </header>

    <!-- Page Navigation Menu -->
    <nav class="page-nav">
        <div class="nav-menu">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link active">Dashboard</a>
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
                    <a href="alert.php" class="nav-link">Alerts</a>
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

    <!-- Dashboard Content -->
    <main class="main-content">
        <div class="page-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-card">
                    <h1 class="welcome-title">Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
                    <p class="welcome-subtitle">Here's your food waste reduction overview.</p>
                </div>
            </div>

            <!-- Statistics Cards Section -->
            <div class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">🍎</div>
                        <div class="stat-number" id="total-items">5</div>
                        <div class="stat-label">Total Items</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-number" id="good-items">3</div>
                        <div class="stat-label">Good Items</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⚠️</div>
                        <div class="stat-number" id="expiring-soon">1</div>
                        <div class="stat-label">Expiring Soon</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">❌</div>
                        <div class="stat-number" id="expired-items">1</div>
                        <div class="stat-label">Expired Items</div>
                    </div>
                </div>
            </div>

            <!-- Action Cards Section -->
            <div class="actions-section">
                <div class="actions-grid">
                    <div class="action-card clickable" onclick="window.location.href='inventory.php'">
                        <div class="action-icon">📦</div>
                        <h3 class="action-title">Add Food Item</h3>
                        <p class="action-description">Quickly add new items to your inventory</p>
                    </div>
                    <div class="action-card clickable" onclick="window.location.href='donate.php'">
                        <div class="action-icon">🎁</div>
                        <h3 class="action-title">Make Donation</h3>
                        <p class="action-description">Share surplus food with your community</p>
                    </div>
                    <div class="action-card clickable" onclick="window.location.href='meal.php'">
                        <div class="action-icon">🍽️</div>
                        <h3 class="action-title">Plan Meals</h3>
                        <p class="action-description">Create meal plans using your inventory</p>
                    </div>
                </div>
            </div>

            <!-- Analysis Section -->
            <div class="analysis-section">
                <div class="analysis-container">
                    <div class="section-header">
                        <h2 class="section-title">Food Analysis</h2>
                        <p class="section-subtitle">Insights and recommendations for your food inventory</p>
                    </div>
                    <div class="analysis-grid">
                        <div class="analysis-card clickable" onclick="window.location.href='analytics.php'">
                            <div class="analysis-icon">📊</div>
                            <h3 class="analysis-title">Waste Reduction</h3>
                            <p class="analysis-description">Track your food waste patterns and get personalized tips to reduce waste</p>
                        </div>
                        <div class="analysis-card clickable" onclick="window.location.href='analytics.php'">
                            <div class="analysis-icon">📈</div>
                            <h3 class="analysis-title">Usage Trends</h3>
                            <p class="analysis-description">Analyze your consumption patterns and optimize your shopping habits</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // expose user info for JS
        window.CURRENT_USER_ID = <?php echo $user_id; ?>;
    </script>
    <script src="browsescript.js"></script>
    <script src="dashboardscript.js"></script>
</body>
</html>
