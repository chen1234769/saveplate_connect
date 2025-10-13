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
    <title>Inventory - SavePlate Connect</title>
    <link rel="stylesheet" href="browsestyle.css">
    <link rel="stylesheet" href="inventorystyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="inventory.php" class="nav-link active">Inventory</a>
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

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon">🍎</div>
                    <h1 class="page-title">Food Inventory</h1>
                </div>
                <button class="add-food-btn" onclick="openAddModal()">+ Add Food Item</button>
            </div>
        </div>

        <!-- Action Guide Section -->
        <div class="action-guide-section">
            <div class="action-guide-header">
                <h2>Action Guide</h2>
            </div>
            <div class="action-guide-grid">
                <div class="action-guide-item">
                    <div class="action-icon edit">
                        <i class="fas fa-edit"></i>
                    </div>
                    <span>Edit item details</span>
                </div>
                <div class="action-guide-item">
                    <div class="action-icon consume">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <span>Mark as used</span>
                </div>
                <div class="action-guide-item">
                    <div class="action-icon donate">
                        <i class="fas fa-heart"></i>
                    </div>
                    <span>Donate item</span>
                </div>
                <div class="action-guide-item">
                    <div class="action-icon delete">
                        <i class="fas fa-trash"></i>
                    </div>
                    <span>Delete item</span>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🍎</div>
                    <div class="stat-content">
                        <div class="stat-number" id="totalItems">0</div>
                        <div class="stat-label">TOTAL ITEMS</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <div class="stat-number" id="goodItems">0</div>
                        <div class="stat-label">GOOD ITEMS</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-content">
                        <div class="stat-number" id="expiringItems">0</div>
                        <div class="stat-label">EXPIRING SOON</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">❌</div>
                    <div class="stat-content">
                        <div class="stat-number" id="expiredItems">0</div>
                        <div class="stat-label">EXPIRED ITEMS</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-header">
                <h2>Filter Inventory</h2>
            </div>
            <div class="filter-grid">
                <div class="filter-group">
                    <div class="filter-label">
                        <i class="fas fa-tag"></i>
                        <span>Category</span>
                    </div>
                    <select id="categoryFilter" class="filter-select">
                        <option value="">All Categories</option>
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
                </div>
                <div class="filter-group">
                    <div class="filter-label">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Location</span>
                    </div>
                    <select id="locationFilter" class="filter-select">
                        <option value="">All Locations</option>
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
                </div>
                <div class="filter-group">
                    <div class="filter-label">
                        <i class="fas fa-info-circle"></i>
                        <span>Status</span>
                    </div>
                    <select id="statusFilter" class="filter-select">
                        <option value="">All Status</option>
                        <option value="good">Good</option>
                        <option value="expiring_soon">Expiring Soon</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
                <div class="filter-group">
                    <div class="filter-label">
                        <i class="fas fa-search"></i>
                        <span>Search</span>
                    </div>
                    <input type="text" id="searchFilter" class="filter-input" placeholder="Search items...">
                </div>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="table-section">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Qty</th>
                        <th>Expiry</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="inventoryTableBody">
                    <!-- Items will be loaded here -->
                </tbody>
            </table>
        </div>
    </main>

    <!-- Add Item Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">
                    <div class="modal-icon">➕</div>
                    <h2>Add Food Item</h2>
                </div>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            
            <form id="addForm" class="modal-form">
                <div class="form-group">
                    <label for="addName">Item Name</label>
                    <input type="text" id="addName" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="addQuantity">Quantity</label>
                    <input type="number" id="addQuantity" name="quantity" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="addExpiry">Expiry Date</label>
                    <input type="date" id="addExpiry" name="expiry_date" required>
                </div>
                
                <div class="form-group">
                    <label for="addCategory">Category</label>
                    <select id="addCategory" name="category" required>
                        <option value="">Select Category</option>
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
                </div>
                
                <div class="form-group">
                    <label for="addStorage">Storage Location</label>
                    <select id="addStorage" name="storage_type" required>
                        <option value="">Select Storage</option>
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
                </div>
                
                <div class="form-group">
                    <label for="addRemarks">Remarks (Optional)</label>
                    <textarea id="addRemarks" name="remarks" rows="3"></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn-save">Save Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">
                    <div class="modal-icon">✏️</div>
                    <h2>Edit Food Item</h2>
                </div>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            
            <form id="editForm" class="modal-form">
                <input type="hidden" id="editId" name="item_id">
                
                <div class="form-group">
                    <label for="editName">Item Name</label>
                    <input type="text" id="editName" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="editQuantity">Quantity</label>
                    <input type="number" id="editQuantity" name="quantity" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="editExpiry">Expiry Date</label>
                    <input type="date" id="editExpiry" name="expiry_date" required>
                </div>
                
                <div class="form-group">
                    <label for="editCategory">Category</label>
                    <select id="editCategory" name="category" required>
                        <option value="">Select Category</option>
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
                </div>
                
                <div class="form-group">
                    <label for="editStorage">Storage Location</label>
                    <select id="editStorage" name="storage_type" required>
                        <option value="">Select Storage</option>
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
                </div>
                
                <div class="form-group">
                    <label for="editRemarks">Remarks (Optional)</label>
                    <textarea id="editRemarks" name="remarks" rows="3"></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn-save">Update Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Consume Item Modal -->
    <div id="consumeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">
                    <div class="modal-icon">🍴</div>
                    <h2>Consume Item</h2>
                </div>
                <button class="modal-close" onclick="closeModal('consumeModal')">&times;</button>
            </div>
            
            <form id="consumeForm" class="modal-form">
                <input type="hidden" id="consumeId" name="item_id">
                
                <div class="form-group">
                    <label for="consumeQuantity">Quantity to Consume</label>
                    <input type="number" id="consumeQuantity" name="quantity" min="1" required>
                    <small class="form-help">Available: <span id="consumeAvailable"></span></small>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal('consumeModal')">Cancel</button>
                    <button type="submit" class="btn-save">Consume Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Donate Item Modal -->
    <div id="donateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">
                    <div class="modal-icon">❤️</div>
                    <h2>Donate Item</h2>
                </div>
                <button class="modal-close" onclick="closeModal('donateModal')">&times;</button>
            </div>
            
            <form id="donateForm" class="modal-form">
                <input type="hidden" id="donateId" name="item_id">
                
                <div class="form-group">
                    <label for="donateQuantity">Quantity to Donate</label>
                    <input type="number" id="donateQuantity" name="quantity" min="1" required>
                    <small class="form-help">Available: <span id="donateAvailable"></span></small>
                </div>
                
                <div class="form-group">
                    <label for="pickupLocation">Pickup Location</label>
                    <input type="text" id="pickupLocation" name="pickup_location" placeholder="e.g., 123 Main St, City" required>
                </div>
                
                <div class="form-group">
                    <label for="contactMethod">Contact Method</label>
                    <input type="text" id="contactMethod" name="contact_method" placeholder="e.g., Phone: 123-456-7890" required>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal('donateModal')">Cancel</button>
                    <button type="submit" class="btn-save">Donate Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notification -->
    <div id="notification" class="notification"></div>

    <script src="inventoryscript.js"></script>
</body>
</html>