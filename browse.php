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
    <title>SavePlate Connect - Browse</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="browsestyle.css">
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
                    <a href="browse.php" class="nav-link active">Browse</a>
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

    <!-- Browse Content -->
    <main class="main-content">
        <div class="page-content">
            <div class="browse-layout">
                <!-- Left Side - Filter Container -->
                <div class="browse-container">
                    <h1 class="browse-title">Click your list</h1>
                    
                    <!-- List Selection Buttons -->
                    <div class="list-selection">
                        <button class="list-btn active" data-list="full">Full List</button>
                        <button class="list-btn" data-list="inventory">Inventory List</button>
                        <button class="list-btn" data-list="donation">Donation List</button>
                    </div>

                    <!-- Expiry Date Range -->
                    <div class="filter-section">
                        <h2 class="filter-title">Expiry Date Range:</h2>
                        <div class="date-range">
                            <div class="date-input-group">
                                <label for="from-date">From</label>
                                <input type="date" id="from-date" class="date-input">
                            </div>
                            <div class="date-input-group">
                                <label for="to-date">To</label>
                                <input type="date" id="to-date" class="date-input">
                            </div>
                        </div>
                    </div>

                    <!-- Storage Type Dropdown -->
                    <div class="filter-section">
                        <div class="dropdown-group">
                            <label for="storage-type">Storage Type</label>
                            <div class="custom-dropdown">
                                <select id="storage-type" class="dropdown-select">
                                    <option value="" disabled selected>Storage Type</option>
                                    <option value="fridge">Fridge</option>
                                    <option value="pantry">Pantry</option>
                                    <option value="freezer">Freezer</option>
                                    <option value="shelf">Shelf</option>
                                    <option value="counter">Counter</option>
                                    <option value="cupboard">Cupboard</option>
                                    <option value="wine_cellar">Wine Cellar</option>
                                    <option value="root_cellar">Root Cellar</option>
                                    <option value="other">Other</option>
                                </select>
                                <span class="dropdown-arrow">▼</span>
                                <button class="clear-btn" onclick="clearStorageType()">×</button>
                            </div>
                        </div>
                    </div>

                    <!-- Categories Dropdown -->
                    <div class="filter-section">
                        <div class="dropdown-group">
                            <label for="categories">Categories</label>
                            <div class="custom-dropdown">
                                <select id="categories" class="dropdown-select">
                                    <option value="" disabled selected>Categories</option>
                                    <option value="fruits">Fruits</option>
                                    <option value="vegetables">Vegetables</option>
                                    <option value="dairy">Dairy</option>
                                    <option value="meat">Meat</option>
                                    <option value="seafood">Seafood</option>
                                    <option value="canned">Canned</option>
                                    <option value="frozen">Frozen</option>
                                    <option value="snacks">Snacks</option>
                                    <option value="beverages">Beverages</option>
                                    <option value="grains">Grains</option>
                                    <option value="bakery">Bakery</option>
                                    <option value="condiments">Condiments</option>
                                    <option value="herbs_spices">Herbs & Spices</option>
                                    <option value="oils_sauces">Oils & Sauces</option>
                                    <option value="other">Other</option>
                                </select>
                                <span class="dropdown-arrow">▼</span>
                                <button class="clear-btn" onclick="clearCategories()">×</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Search and Food Tags -->
                <div class="right-side-content">
                    <!-- Search Item Section -->
                    <div class="search-section">
                        <div class="search-container">
                            <h2 class="search-title">Search Items</h2>
                            <div class="search-input-group">
                                <input type="text" id="search-input" class="search-input" placeholder="Search for food items...">
                                <span class="search-icon">🔍</span>
                            </div>
                        </div>
                    </div>

                    <!-- Food Tags Section -->
                    <div class="food-tags-section">
                        <h2 class="food-tags-title">Food Items</h2>
                        <div id="food-tags-grid" class="food-tags-grid"></div>
                        <div id="food-detail" class="food-detail" style="display:none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal for Used Action -->
    <div id="usedModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <h3>Use Item</h3>
                <span class="close" onclick="closeModal('usedModal')">&times;</span>
            </div>
            <form id="usedForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="usedQuantity">Quantity to Use</label>
                        <input type="number" id="usedQuantity" name="quantity" min="1" required>
                        <div class="available-info" id="usedAvailable">Available: 0</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('usedModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Use Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal for Meal Action -->
    <div id="mealModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3>Plan for Meal</h3>
                <span class="close" onclick="closeModal('mealModal')">&times;</span>
            </div>
            <form id="mealForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="mealQuantity">Quantity to Plan for Meal</label>
                        <input type="number" id="mealQuantity" name="quantity" min="1" required>
                        <div class="available-info" id="mealAvailable">Available: 0</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('mealModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Plan for Meal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal for Donate Action -->
    <div id="donateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3>Donate Item</h3>
                <span class="close" onclick="closeModal('donateModal')">&times;</span>
            </div>
            <form id="donateForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="donateQuantity">Quantity to Donate</label>
                        <input type="number" id="donateQuantity" name="quantity" min="1" required>
                        <div class="available-info" id="donateAvailable">Available: 0</div>
                    </div>
                    <div class="form-group">
                        <label for="donatePickupLocation">Pickup Location</label>
                        <input type="text" id="donatePickupLocation" name="pickup_location" placeholder="e.g., 123 Main St, City" required>
                    </div>
                    <div class="form-group">
                        <label for="donateContactMethod">Contact Method</label>
                        <input type="text" id="donateContactMethod" name="contact_method" placeholder="e.g., Phone: 123-456-7890" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('donateModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Donate Item</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // expose user info for JS
        window.CURRENT_USER_ID = <?php echo json_encode($user_id); ?>;
        window.CURRENT_USERNAME = <?php echo json_encode($username); ?>;
    </script>
    <script src="browsescript.js"></script>
</body>
</html>