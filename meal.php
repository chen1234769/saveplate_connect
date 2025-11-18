<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session_check.php';

// Get current user information from session
$user_id = $current_user_id;
$username = $current_username;

// Calculate current week dates
$today = new DateTime();
$monday = clone $today;
$monday->modify('monday this week');
$sunday = clone $monday;
$sunday->modify('+6 days');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Planning - SavePlate Connect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="browsestyle.css">
    <link rel="stylesheet" href="mealstyle.css">
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
                    <a href="analytics.php" class="nav-link">Analytics</a>
                </li>
                <li class="nav-item">
                    <a href="alert.php" class="nav-link">Alerts</a>
                </li>
                <li class="nav-item">
                    <a href="meal.php" class="nav-link active">Meal</a>
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

    <!-- Meal Planning Content -->
    <main class="main-content">
        <div class="page-content">
            <div class="meal-dashboard">
                <div class="meal-sidebar">
                    <h2>My Inventory</h2>
                    <div class="inventory-list" id="inventory-list">
                        <!-- Inventory items will be loaded here -->
                        <div class="loading">Loading inventory...</div>
                    </div>
                    <button class="btn btn-outline" style="width: 100%; margin-top: 15px;" onclick="window.location.href='inventory.php'">
                        <i class="fas fa-utensils"></i> Manage Inventory
                    </button>
                </div>
                
                <div class="meal-main-content">
                    <h1>Weekly Meal Planner</h1>
                    <p class="meal-description">Plan your meals for the week using ingredients from your inventory to reduce waste.</p>
                    
                    <div class="meal-calendar">
                        <div class="week-navigation">
                            <button class="week-nav-btn" id="prev-week" title="Previous Week">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                            <div class="week-display" id="week-display">
                                This Week: <?php echo $monday->format('M d') . ' - ' . $sunday->format('M d, Y'); ?>
                            </div>
                            <button class="week-nav-btn" id="next-week" title="Next Week">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                        
                        <div class="calendar-header">
                            <div>Monday</div>
                            <div>Tuesday</div>
                            <div>Wednesday</div>
                            <div>Thursday</div>
                            <div>Friday</div>
                            <div>Saturday</div>
                            <div>Sunday</div>
                        </div>
                        
                        <div class="calendar-week" id="calendar-week">
                            <?php
                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                            $dayAbbr = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                            $dayData = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
                            
                            for ($i = 0; $i < 7; $i++) {
                                $currentDay = clone $monday;
                                $currentDay->modify("+$i days");
                                $dayName = $days[$i];
                                $dayAbbrName = $dayAbbr[$i];
                                $dayDataName = $dayData[$i];
                                $dateStr = $currentDay->format('M d');
                                ?>
                                <div class="calendar-day">
                                    <div class="day-header">
                                        <span><?php echo $dateStr; ?></span>
                                    </div>
                                    <div class="meal-slots">
                                        <div class="meal-slot" data-day="<?php echo $dayDataName; ?>" data-meal="breakfast" data-date="<?php echo $currentDay->format('Y-m-d'); ?>">
                                            <strong>Breakfast</strong>
                                            <div class="meal-content">Click to plan</div>
                                        </div>
                                        <div class="meal-slot" data-day="<?php echo $dayDataName; ?>" data-meal="lunch" data-date="<?php echo $currentDay->format('Y-m-d'); ?>">
                                            <strong>Lunch</strong>
                                            <div class="meal-content">Click to plan</div>
                                        </div>
                                        <div class="meal-slot" data-day="<?php echo $dayDataName; ?>" data-meal="dinner" data-date="<?php echo $currentDay->format('Y-m-d'); ?>">
                                            <strong>Dinner</strong>
                                            <div class="meal-content">Click to plan</div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button class="btn btn-outline" id="clear-all">Clear All</button>
                        <button class="btn btn-primary" id="confirm-plan">Confirm Weekly Plan</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Recipe Selection Modal -->
    <div class="modal" id="recipe-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Select a Recipe</h2>
                <button class="close-modal">&times;</button>
            </div>
            <p>Choose from recipes that use your available ingredients</p>
            
            <div class="recipe-grid" id="recipe-grid">
                <!-- Recipes will be loaded dynamically -->
            </div>
            
            <div class="meal-details" id="meal-details" style="display: none;">
                <h3 id="selected-recipe-title">Recipe Title</h3>
                <p id="selected-recipe-description">Recipe description will appear here.</p>
                <h4>Ingredients:</h4>
                <ul class="ingredient-list" id="selected-recipe-ingredients">
                    <!-- Ingredients will be populated here -->
                </ul>
                <div class="action-buttons">
                    <button class="btn btn-danger" id="remove-meal">Clear Meal</button>
                    <button class="btn btn-outline" id="back-to-recipes">Back to Recipes</button>
                    <button class="btn btn-primary" id="add-to-plan">Add to Meal Plan</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Notification -->
    <div class="notification" id="notification"></div>
    
    <script>
        window.CURRENT_USER_ID = <?php echo json_encode($user_id); ?>;
        window.CURRENT_USERNAME = <?php echo json_encode($username); ?>;
    </script>
    <script src="browsescript.js"></script>
    <script src="mealscript.js"></script>
</body>
</html>
