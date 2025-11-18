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
    <title>SavePlate Connect - Analytics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="browsestyle.css">
    <link rel="stylesheet" href="analyticsstyle.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <a href="analytics.php" class="nav-link active">Analytics</a>
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

    <!-- Analytics Content -->
    <main class="main-content">
        <div class="page-content">
            <div class="analytics-layout">
                <!-- Analytics Overview -->
                <div class="analytics-overview">
                    <h1 class="analytics-title">Food Waste Analytics</h1>
                    <p class="analytics-subtitle">Track your food consumption patterns and waste reduction progress</p>
                </div>

                <!-- Analytics Cards -->
                <div class="analytics-grid">
                    <div class="analytics-card">
                        <div class="card-icon">🥗</div>
                        <h3 class="card-title">Total Food Saved from Waste</h3>
                        <div class="card-value" id="food-saved">0 items</div>
                        <div class="card-description">Items saved via donations & meal planning</div>
                    </div>
                    
                    <div class="analytics-card">
                        <div class="card-icon">📊</div>
                        <h3 class="card-title">Waste Reduction</h3>
                        <div class="card-value" id="waste-reduction">0%</div>
                        <div class="card-description">Reduction in food waste compared to risk</div>
                    </div>
                    
                    <div class="analytics-card">
                        <div class="card-icon">♻️</div>
                        <h3 class="card-title">Items Donated</h3>
                        <div class="card-value" id="items-donated">0</div>
                        <div class="card-description">Items donated to others</div>
                    </div>
                    
                    <div class="analytics-card">
                        <div class="card-icon">🍽️</div>
                        <h3 class="card-title">Meals Planned</h3>
                        <div class="card-value" id="meals-planned">0</div>
                        <div class="card-description">Confirmed meals created from inventory</div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="charts-section">
                    <div class="chart-container">
                        <h3 class="chart-title">Food Categories Distribution</h3>
                        <p class="chart-description">See which food groups dominate your inventory.</p>
                        <canvas id="categoryChart"></canvas>
                        <div class="chart-message" id="categoryChartMessage" style="display:none;">No inventory data yet.</div>
                    </div>
                    
                    <div class="chart-container">
                        <div class="chart-header-with-filter">
                            <div>
                                <h3 class="chart-title">Monthly Waste vs. Saved Trend</h3>
                                <p class="chart-description">Track how your saved items compare to expired items over time.</p>
                            </div>
                            <div class="chart-filter">
                                <button class="filter-btn active" data-period="1week">1 Week</button>
                                <button class="filter-btn" data-period="6months">6 Months</button>
                                <button class="filter-btn" data-period="12months">12 Months</button>
                            </div>
                        </div>
                        <canvas id="trendChart"></canvas>
                        <div class="chart-message" id="trendChartMessage" style="display:none;">Trend chart will appear when data is available.</div>
                    </div>
                </div>

                <!-- Insights & Recommendations Section -->
                <div class="insights-section">
                    <div class="insights-header">
                        <h2 class="insights-title">💡 Insights & Recommendations</h2>
                        <p class="insights-subtitle">Personalized tips to improve your food waste reduction habits</p>
                    </div>
                    <div id="insightsContainer" class="insights-container">
                        <!-- Insights will be dynamically generated here -->
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
    <script src="analyticsscript.js"></script>
</body>
</html>
