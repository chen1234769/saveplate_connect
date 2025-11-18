// Meal Planning Page Functionality
let currentMealSlot = null;
let inventoryItems = [];
let reservedQuantities = {};
let currentRecipe = null;
let currentIngredients = [];
let currentWeekStart = null; // Monday of current week

function formatDateToYMD(date) {
    const copy = new Date(date);
    copy.setHours(0, 0, 0, 0);
    const offsetMs = copy.getTimezoneOffset() * 60000;
    const localISO = new Date(copy.getTime() - offsetMs).toISOString();
    return localISO.split('T')[0];
}

function getWeekRangeDates() {
    const monday = new Date(currentWeekStart);
    const sunday = new Date(monday);
    sunday.setDate(sunday.getDate() + 6);
    return {
        monday,
        sunday,
        mondayStr: formatDateToYMD(monday),
        sundayStr: formatDateToYMD(sunday)
    };
}

function isDateBeforeToday(dateStr) {
    const slotDate = new Date(dateStr + 'T00:00:00');
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return slotDate < today;
}

function formatFriendlyDate(date) {
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Meal planning page loaded');
    
    // Initialize current week (Monday of this week)
    const today = new Date();
    const monday = new Date(today);
    const day = monday.getDay();
    const diff = monday.getDate() - day + (day === 0 ? -6 : 1); // Adjust when day is Sunday
    monday.setDate(diff);
    monday.setHours(0, 0, 0, 0);
    currentWeekStart = monday;
    
    // Mobile menu toggle
    const hamburger = document.querySelector(".hamburger");
    const navMenu = document.querySelector(".nav-menu");

    if (hamburger && navMenu) {
        hamburger.addEventListener("click", () => {
            hamburger.classList.toggle("active");
            navMenu.classList.toggle("active");
        });
    }

    // Close menu when clicking outside
    document.addEventListener('click', (event) => {
        const isClickInsideNav = event.target.closest('.page-nav');
        if (!isClickInsideNav && hamburger && navMenu) {
            hamburger.classList.remove("active");
            navMenu.classList.remove("active");
        }
    });
    
    // Setup week navigation
    setupWeekNavigation();
    updateCalendarDisplay();
    
    // Load data
    (async () => {
        await loadReservedQuantities();
        await loadInventoryItems();
        await loadSavedMealPlans();
    })();
    
    // Setup modal handlers
    setupModalHandlers();
    
    // Setup action buttons
    setupActionButtons();
});

// Load reserved quantities
async function loadReservedQuantities() {
    try {
        const response = await fetch('get_reserved_quantities.php');
        if (response.ok) {
            reservedQuantities = await response.json();
        }
    } catch (error) {
        console.error('Failed to load reserved quantities:', error);
    }
}

// Load inventory items for sidebar and recipe matching
async function loadInventoryItems() {
    try {
        const response = await fetch(`browse_items.php?user_id=${window.CURRENT_USER_ID}&list=inventory`);
        const items = await response.json();
        
        // Filter out expired and donation items
        inventoryItems = items.filter(item => 
            item.status !== 'expired' && 
            item.item_type !== 'donation'
        );
        
        const container = document.getElementById('inventory-list');
        if (!container) return;
        
        if (!Array.isArray(items) || items.length === 0) {
            container.innerHTML = '<div class="loading">No items in inventory</div>';
            return;
        }
        
        // Filter out expired items and limit to 10 most relevant
        const validItems = inventoryItems
            .sort((a, b) => {
                if (a.status === 'expiring_soon' && b.status !== 'expiring_soon') return -1;
                if (a.status !== 'expiring_soon' && b.status === 'expiring_soon') return 1;
                return 0;
            })
            .slice(0, 10);
        
        container.innerHTML = validItems.map(item => {
            const reserved = reservedQuantities[item.id] || 0;
            const available = item.quantity - reserved;
            const statusClass = item.status === 'expiring_soon' ? 'expiring-soon' : '';
            const statusText = item.status === 'expiring_soon' ? ' (expiring soon)' : '';
            const reservedText = reserved > 0 ? ` [${reserved} reserved]` : '';
            return `
                <div class="inventory-item ${statusClass}">
                    <span>${item.name}</span>
                    <span>${available} available${statusText}${reservedText}</span>
                </div>
            `;
        }).join('');
        
        return inventoryItems;
    } catch (error) {
        console.error('Failed to load inventory:', error);
        document.getElementById('inventory-list').innerHTML = '<div class="loading">Error loading inventory</div>';
        return [];
    }
}

// Load saved meal plans for current week
async function loadSavedMealPlans() {
    try {
        const { mondayStr, sundayStr } = getWeekRangeDates();
        
        const response = await fetch(`get_meal_plan.php?from=${mondayStr}&to=${sundayStr}`);
        if (!response.ok) return;
        
        const meals = await response.json();
        
        // Clear all planned meals first
        document.querySelectorAll('.meal-slot.planned').forEach(slot => {
            resetMealSlot(slot);
        });
        
        // Load meals for current week
        meals.forEach(meal => {
            const mealSlot = document.querySelector(`.meal-slot[data-date="${meal.date}"][data-meal="${meal.meal_type}"]`);
            
            if (mealSlot) {
                const mealContent = mealSlot.querySelector('.meal-content');
                mealContent.textContent = meal.meal_name;
                mealSlot.classList.add('planned');
                mealSlot.setAttribute('data-meal-id', meal.id);
                mealSlot.setAttribute('data-meal-name', meal.meal_name);
                mealSlot.setAttribute('data-ingredients', JSON.stringify(meal.ingredients));
            }
        });
    } catch (error) {
        console.error('Failed to load saved meal plans:', error);
    }
}

// Setup meal slot click handlers
function setupMealSlots() {
    const mealSlots = document.querySelectorAll('.meal-slot');
    mealSlots.forEach(slot => {
        slot.addEventListener('click', (event) => {
            const slotDate = slot.getAttribute('data-date');
            if (slotDate && isDateBeforeToday(slotDate)) {
                showNotification(`Cannot plan meals before today (${formatFriendlyDate(new Date())})`, 'error');
                currentMealSlot = null;
                updateRemoveMealButtonVisibility();
                return;
            }
            
            currentMealSlot = slot;
            updateRemoveMealButtonVisibility();
            
            // If meal is already planned, load it for editing
            if (slot.classList.contains('planned')) {
                const mealName = slot.getAttribute('data-meal-name') || slot.querySelector('.meal-content').textContent;
                const ingredientsJson = slot.getAttribute('data-ingredients');
                
                if (ingredientsJson) {
                    currentIngredients = JSON.parse(ingredientsJson);
                    currentRecipe = {
                        title: 'Custom Meal',
                        description: 'Edit your meal plan',
                        ingredients: []
                    };
                    openRecipeModal();
                    // Show edit view with existing data
                    setTimeout(() => {
                        showCustomMealFormEdit(mealName, currentIngredients);
                    }, 100);
                } else {
                    openRecipeModal();
                }
            } else {
                openRecipeModal();
            }
        });
    });
}

// Recipe data
const recipes = {
    pasta: {
        title: "Pasta Primavera",
        description: "A delicious pasta dish with fresh vegetables from your inventory.",
        ingredients: ["Pasta", "Tomatoes", "Broccoli", "Spinach"],
        usesInventory: 4
    },
    'stir-fry': {
        title: "Chicken Stir Fry",
        description: "Quick and healthy stir fry using chicken and vegetables.",
        ingredients: ["Chicken", "Broccoli", "Rice"],
        usesInventory: 3
    },
    smoothie: {
        title: "Berry Smoothie",
        description: "Refreshing smoothie using dairy products from your inventory.",
        ingredients: ["Yogurt", "Milk", "Berries"],
        usesInventory: 2
    },
    salad: {
        title: "Spinach Salad",
        description: "Fresh salad with protein and greens.",
        ingredients: ["Spinach", "Tomatoes", "Chicken"],
        usesInventory: 3
    },
    'rice-bowl': {
        title: "Rice Bowl",
        description: "Hearty rice bowl with protein and vegetables.",
        ingredients: ["Rice", "Chicken", "Broccoli"],
        usesInventory: 3
    },
    'chicken-rice': {
        title: "Chicken and Rice",
        description: "Simple and satisfying meal with chicken and rice.",
        ingredients: ["Chicken", "Rice"],
        usesInventory: 2
    },
    'vegetable-stir': {
        title: "Vegetable Stir Fry",
        description: "Colorful mix of vegetables in a savory sauce.",
        ingredients: ["Broccoli", "Spinach", "Tomatoes"],
        usesInventory: 3
    }
};

// Load recipes based on inventory
async function loadRecipes() {
    const recipeGrid = document.getElementById('recipe-grid');
    if (!recipeGrid) return;
    
    const recipeCards = [];
    
    // Check which recipes can be made
    Object.keys(recipes).forEach(recipeId => {
        const recipe = recipes[recipeId];
        const matchCount = countIngredientMatches(recipe.ingredients, inventoryItems);
        
        if (matchCount > 0) {
            recipeCards.push(createRecipeCard(recipeId, recipe, matchCount));
        }
    });
    
    // Add custom meal option
    recipeCards.push(createCustomMealCard());
    
    recipeGrid.innerHTML = recipeCards.join('');
    
    // Re-attach event listeners
    document.querySelectorAll('.recipe-card').forEach(card => {
        card.addEventListener('click', () => {
            const recipeId = card.getAttribute('data-recipe');
            if (recipeId === 'custom') {
                showCustomMealForm();
            } else {
                showRecipeDetails(recipeId);
            }
        });
    });
}

// Count how many recipe ingredients match inventory
function countIngredientMatches(recipeIngredients, inventoryItems) {
    const inventoryNames = inventoryItems.map(item => item.name.toLowerCase());
    let matches = 0;
    
    recipeIngredients.forEach(ingredient => {
        const ingredientLower = ingredient.toLowerCase();
        if (inventoryNames.some(name => name.includes(ingredientLower) || ingredientLower.includes(name))) {
            matches++;
        }
    });
    
    return matches;
}

// Create recipe card HTML
function createRecipeCard(recipeId, recipe, matchCount) {
    const matchClass = matchCount >= 3 ? 'match-high' : matchCount >= 2 ? 'match-medium' : 'match-low';
    const matchText = `Uses ${matchCount} ingredient${matchCount > 1 ? 's' : ''} from your inventory`;
    
    return `
        <div class="recipe-card" data-recipe="${recipeId}">
            <div class="recipe-image">🍽️</div>
            <div class="recipe-info">
                <div class="recipe-title">${recipe.title}</div>
                <div class="recipe-ingredients">${recipe.ingredients.join(', ')}</div>
                <div class="recipe-match ${matchClass}">${matchText}</div>
            </div>
        </div>
    `;
}

// Create custom meal card
function createCustomMealCard() {
    return `
        <div class="recipe-card" data-recipe="custom">
            <div class="recipe-image" style="display: flex; align-items: center; justify-content: center; font-size: 48px;">+</div>
            <div class="recipe-info">
                <div class="recipe-title">Create Custom Meal</div>
                <div class="recipe-ingredients">Add your own meal plan</div>
            </div>
        </div>
    `;
}

// Show recipe details with quantity inputs
function showRecipeDetails(recipeId) {
    currentRecipe = recipes[recipeId];
    if (!currentRecipe) return;
    
    document.getElementById('selected-recipe-title').textContent = currentRecipe.title;
    document.getElementById('selected-recipe-description').textContent = currentRecipe.description;
    
    // Find matching inventory items for each ingredient
    const ingredientsList = document.getElementById('selected-recipe-ingredients');
    ingredientsList.innerHTML = '';
    currentIngredients = [];
    
    currentRecipe.ingredients.forEach(ingredientName => {
        const matchingItems = findMatchingInventoryItems(ingredientName);
        
        if (matchingItems.length > 0) {
            // Use first match
            const item = matchingItems[0];
            const reserved = reservedQuantities[item.id] || 0;
            const available = item.quantity - reserved;
            
            const li = document.createElement('li');
            li.className = 'ingredient-input-item';
            li.innerHTML = `
                <div class="ingredient-input-row">
                    <span class="ingredient-name">${item.name}</span>
                    <div class="ingredient-quantity-input">
                        <input type="number" 
                               class="ingredient-qty" 
                               data-inventory-id="${item.id}"
                               data-inventory-name="${item.name}"
                               min="1" 
                               max="${available}" 
                               value="1"
                               style="width: 60px; padding: 4px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.15); color: #fff; font-weight: 500; text-align: center;">
                        <span class="available-text">/ ${available} available</span>
                    </div>
                </div>
            `;
            ingredientsList.appendChild(li);
            
            currentIngredients.push({
                inventory_id: item.id,
                name: item.name,
                quantity: 1
            });
        } else {
            // No match found
            const li = document.createElement('li');
            li.innerHTML = `<span style="color: rgba(255,255,255,0.5);">${ingredientName} (not in inventory)</span>`;
            ingredientsList.appendChild(li);
        }
    });
    
    // Add event listeners to quantity inputs
    document.querySelectorAll('.ingredient-qty').forEach(input => {
        input.addEventListener('change', updateIngredientQuantity);
    });
    
    document.getElementById('recipe-grid').style.display = 'none';
    document.getElementById('meal-details').style.display = 'block';
}

// Find matching inventory items for an ingredient name
function findMatchingInventoryItems(ingredientName) {
    const ingredientLower = ingredientName.toLowerCase();
    return inventoryItems.filter(item => {
        const itemNameLower = item.name.toLowerCase();
        return itemNameLower.includes(ingredientLower) || ingredientLower.includes(itemNameLower);
    });
}

// Update ingredient quantity
function updateIngredientQuantity(e) {
    const inventoryId = parseInt(e.target.getAttribute('data-inventory-id'));
    const quantity = parseInt(e.target.value) || 0;
    
    const ingredient = currentIngredients.find(ing => ing.inventory_id === inventoryId);
    if (ingredient) {
        ingredient.quantity = quantity;
    }
}

// Show custom meal form (for editing existing meal)
function showCustomMealFormEdit(mealName, existingIngredients) {
    currentRecipe = {
        title: 'Custom Meal',
        description: 'Edit your meal plan',
        ingredients: []
    };
    
    currentIngredients = existingIngredients || [];
    
    document.getElementById('selected-recipe-title').textContent = 'Edit Custom Meal';
    document.getElementById('selected-recipe-description').textContent = 'Modify your meal ingredients';
    
    const ingredientsList = document.getElementById('selected-recipe-ingredients');
    ingredientsList.innerHTML = '';
    
    // Add meal name input
    const nameLi = document.createElement('li');
    nameLi.className = 'ingredient-input-item';
    nameLi.innerHTML = `
        <div class="ingredient-input-row">
            <label style="color: #fff; font-weight: 600; text-shadow: 0 1px 2px rgba(0,0,0,0.2);">Meal Name:</label>
            <input type="text" 
                   id="custom-meal-name" 
                   value="${mealName || ''}"
                   placeholder="Enter meal name"
                   style="flex: 1; padding: 6px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.15); color: #fff; font-weight: 500; margin-left: 10px;">
        </div>
    `;
    ingredientsList.appendChild(nameLi);
    
    // Add ingredient selector
    const selectLi = document.createElement('li');
    selectLi.className = 'ingredient-input-item';
    selectLi.innerHTML = `
        <div class="ingredient-input-row">
            <select id="ingredient-selector" 
                    style="flex: 1; padding: 6px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.15); color: #fff; font-weight: 500;">
                <option value="" style="background: rgba(76, 175, 80, 0.8); color: #fff;">Select an ingredient...</option>
                ${inventoryItems.map(item => {
                    const reserved = reservedQuantities[item.id] || 0;
                    const available = item.quantity - reserved;
                    // Check if already in current ingredients
                    const alreadyAdded = currentIngredients.find(ing => ing.inventory_id === item.id);
                    const currentQty = alreadyAdded ? alreadyAdded.quantity : 0;
                    const maxAvailable = alreadyAdded ? available + currentQty : available;
                    return `<option value="${item.id}" data-name="${item.name}" data-available="${maxAvailable}" style="background: rgba(76, 175, 80, 0.8); color: #fff;">${item.name} (${maxAvailable} available)</option>`;
                }).join('')}
            </select>
            <button class="btn btn-outline" id="add-ingredient-btn" style="margin-left: 10px; padding: 6px 12px;">Add</button>
        </div>
    `;
    ingredientsList.appendChild(selectLi);
    
    // Add selected ingredients list
    const listLi = document.createElement('li');
    listLi.className = 'ingredient-input-item';
    listLi.id = 'selected-ingredients-list';
    listLi.innerHTML = '<div style="margin-top: 10px;"><strong style="color: rgba(255,255,255,0.9);">Selected Ingredients:</strong></div>';
    ingredientsList.appendChild(listLi);
    
    // Add existing ingredients
    currentIngredients.forEach(ingredient => {
        const item = inventoryItems.find(item => item.id === ingredient.inventory_id);
        if (item) {
            const reserved = reservedQuantities[item.id] || 0;
            const available = item.quantity - reserved;
            const currentQty = ingredient.quantity;
            const maxAvailable = available + currentQty; // Can keep current quantity
            
            const listContainer = document.getElementById('selected-ingredients-list');
            const ingredientDiv = document.createElement('div');
            ingredientDiv.className = 'selected-ingredient';
            ingredientDiv.innerHTML = `
                <div class="ingredient-input-row" style="margin-top: 8px;">
                    <span class="ingredient-name">${ingredient.name}</span>
                    <div class="ingredient-quantity-input">
                        <input type="number" 
                               class="ingredient-qty" 
                               data-inventory-id="${ingredient.inventory_id}"
                               data-inventory-name="${ingredient.name}"
                               min="1" 
                               max="${maxAvailable}" 
                               value="${currentQty}"
                               style="width: 60px; padding: 4px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.15); color: #fff; font-weight: 500; text-align: center;">
                        <span class="available-text">/ ${maxAvailable} max</span>
                        <button class="remove-ingredient" data-inventory-id="${ingredient.inventory_id}" style="margin-left: 8px; padding: 4px 8px; background: rgba(244,67,54,0.8); color: #fff; border: none; border-radius: 4px; cursor: pointer;">Remove</button>
                    </div>
                </div>
            `;
            listContainer.appendChild(ingredientDiv);
            
            // Add event listeners
            ingredientDiv.querySelector('.ingredient-qty').addEventListener('change', updateIngredientQuantity);
            ingredientDiv.querySelector('.remove-ingredient').addEventListener('click', (e) => {
                const id = parseInt(e.target.getAttribute('data-inventory-id'));
                currentIngredients = currentIngredients.filter(ing => ing.inventory_id !== id);
                ingredientDiv.remove();
            });
        }
    });
    
    document.getElementById('recipe-grid').style.display = 'none';
    document.getElementById('meal-details').style.display = 'block';
    
    // Add ingredient button handler
    document.getElementById('add-ingredient-btn').addEventListener('click', addCustomIngredient);
}

// Show custom meal form
function showCustomMealForm() {
    currentRecipe = {
        title: "Custom Meal",
        description: "Your own meal creation.",
        ingredients: []
    };
    
    document.getElementById('selected-recipe-title').textContent = 'Create Custom Meal';
    document.getElementById('selected-recipe-description').textContent = 'Select ingredients from your inventory';
    
    const ingredientsList = document.getElementById('selected-recipe-ingredients');
    ingredientsList.innerHTML = '';
    currentIngredients = [];
    
    // Add meal name input
    const nameLi = document.createElement('li');
    nameLi.className = 'ingredient-input-item';
    nameLi.innerHTML = `
        <div class="ingredient-input-row">
            <label style="color: #fff; font-weight: 600; text-shadow: 0 1px 2px rgba(0,0,0,0.2);">Meal Name:</label>
            <input type="text" 
                   id="custom-meal-name" 
                   placeholder="Enter meal name"
                   style="flex: 1; padding: 6px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.15); color: #fff; font-weight: 500; margin-left: 10px;">
        </div>
    `;
    ingredientsList.appendChild(nameLi);
    
    // Add ingredient selector
    const selectLi = document.createElement('li');
    selectLi.className = 'ingredient-input-item';
    selectLi.innerHTML = `
        <div class="ingredient-input-row">
            <select id="ingredient-selector" 
                    style="flex: 1; padding: 6px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.15); color: #fff; font-weight: 500;">
                <option value="" style="background: rgba(76, 175, 80, 0.8); color: #fff;">Select an ingredient...</option>
                ${inventoryItems.map(item => {
                    const reserved = reservedQuantities[item.id] || 0;
                    const available = item.quantity - reserved;
                    return `<option value="${item.id}" data-name="${item.name}" data-available="${available}" style="background: rgba(76, 175, 80, 0.8); color: #fff;">${item.name} (${available} available)</option>`;
                }).join('')}
            </select>
            <button class="btn btn-outline" id="add-ingredient-btn" style="margin-left: 10px; padding: 6px 12px;">Add</button>
        </div>
    `;
    ingredientsList.appendChild(selectLi);
    
    // Add selected ingredients list
    const listLi = document.createElement('li');
    listLi.className = 'ingredient-input-item';
    listLi.id = 'selected-ingredients-list';
    listLi.innerHTML = '<div style="margin-top: 10px;"><strong style="color: rgba(255,255,255,0.9);">Selected Ingredients:</strong></div>';
    ingredientsList.appendChild(listLi);
    
    document.getElementById('recipe-grid').style.display = 'none';
    document.getElementById('meal-details').style.display = 'block';
    
    // Add ingredient button handler
    document.getElementById('add-ingredient-btn').addEventListener('click', addCustomIngredient);
}

// Add ingredient to custom meal
function addCustomIngredient() {
    const selector = document.getElementById('ingredient-selector');
    const selectedOption = selector.options[selector.selectedIndex];
    
    if (!selectedOption.value) return;
    
    const inventoryId = parseInt(selectedOption.value);
    const name = selectedOption.getAttribute('data-name');
    const available = parseInt(selectedOption.getAttribute('data-available'));
    
    // Check if already added
    if (currentIngredients.find(ing => ing.inventory_id === inventoryId)) {
        showNotification('Ingredient already added', 'error');
        return;
    }
    
    const ingredient = {
        inventory_id: inventoryId,
        name: name,
        quantity: 1
    };
    
    currentIngredients.push(ingredient);
    
    // Add to UI
    const listContainer = document.getElementById('selected-ingredients-list');
    const ingredientDiv = document.createElement('div');
    ingredientDiv.className = 'selected-ingredient';
    ingredientDiv.innerHTML = `
        <div class="ingredient-input-row" style="margin-top: 8px;">
            <span class="ingredient-name">${name}</span>
            <div class="ingredient-quantity-input">
                <input type="number" 
                       class="ingredient-qty" 
                       data-inventory-id="${inventoryId}"
                       data-inventory-name="${name}"
                       min="1" 
                       max="${available}" 
                       value="1"
                       style="width: 60px; padding: 4px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: #fff;">
                <span class="available-text">/ ${available} available</span>
                <button class="remove-ingredient" data-inventory-id="${inventoryId}" style="margin-left: 8px; padding: 4px 8px; background: rgba(244,67,54,0.8); color: #fff; border: none; border-radius: 4px; cursor: pointer;">Remove</button>
            </div>
        </div>
    `;
    listContainer.appendChild(ingredientDiv);
    
    // Add event listeners
    ingredientDiv.querySelector('.ingredient-qty').addEventListener('change', updateIngredientQuantity);
    ingredientDiv.querySelector('.remove-ingredient').addEventListener('click', (e) => {
        const id = parseInt(e.target.getAttribute('data-inventory-id'));
        currentIngredients = currentIngredients.filter(ing => ing.inventory_id !== id);
        ingredientDiv.remove();
    });
    
    selector.value = '';
}

// Setup modal handlers
function setupModalHandlers() {
    const recipeModal = document.getElementById('recipe-modal');
    const closeModal = document.querySelector('.close-modal');
    const backToRecipes = document.getElementById('back-to-recipes');
    const addToPlan = document.getElementById('add-to-plan');
    const removeMealBtn = document.getElementById('remove-meal');
    
    if (closeModal) {
        closeModal.addEventListener('click', closeRecipeModal);
    }
    
    if (backToRecipes) {
        backToRecipes.addEventListener('click', () => {
            document.getElementById('meal-details').style.display = 'none';
            document.getElementById('recipe-grid').style.display = 'grid';
            currentRecipe = null;
            currentIngredients = [];
        });
    }
    
    if (addToPlan) {
        addToPlan.addEventListener('click', () => {
            addMealToPlan();
        });
    }
    
    if (removeMealBtn) {
        removeMealBtn.addEventListener('click', async () => {
            if (!currentMealSlot) {
                return;
            }
            if (!confirm('Clear this meal plan?')) {
                return;
            }
            await clearMealSlot(currentMealSlot);
            closeRecipeModal();
        });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === recipeModal) {
            closeRecipeModal();
        }
    });
}

// Setup week navigation
function setupWeekNavigation() {
    const prevWeekBtn = document.getElementById('prev-week');
    const nextWeekBtn = document.getElementById('next-week');
    
    if (prevWeekBtn) {
        prevWeekBtn.addEventListener('click', () => {
            navigateWeek(-1);
        });
    }
    
    if (nextWeekBtn) {
        nextWeekBtn.addEventListener('click', () => {
            navigateWeek(1);
        });
    }
}

// Navigate to previous or next week
function navigateWeek(direction) {
    // Move week by 7 days
    const newWeekStart = new Date(currentWeekStart);
    newWeekStart.setDate(newWeekStart.getDate() + (direction * 7));
    currentWeekStart = newWeekStart;
    
    // Update calendar display
    updateCalendarDisplay();
    
    // Reload meals for the new week
    loadSavedMealPlans();
}

// Update calendar display for current week
function updateCalendarDisplay() {
    const { monday, sunday } = getWeekRangeDates();
    currentMealSlot = null;
    updateRemoveMealButtonVisibility();
    
    // Update week display
    const weekDisplay = document.getElementById('week-display');
    if (weekDisplay) {
        const options = { month: 'short', day: 'numeric' };
        const mondayLabel = monday.toLocaleDateString('en-US', options);
        const sundayLabel = sunday.toLocaleDateString('en-US', { ...options, year: 'numeric' });
        
        const today = new Date();
        const todayMonday = new Date(today);
        const dayOffset = (todayMonday.getDay() + 6) % 7;
        todayMonday.setDate(todayMonday.getDate() - dayOffset);
        todayMonday.setHours(0, 0, 0, 0);
        const isCurrentWeek = monday.toDateString() === todayMonday.toDateString();
        const prefix = isCurrentWeek ? 'This Week: ' : 'Week: ';
        
        weekDisplay.textContent = `${prefix}${mondayLabel} - ${sundayLabel}`;
    }
    
    // Update calendar days
    const calendarWeek = document.getElementById('calendar-week');
    if (!calendarWeek) return;
    
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    const dayAbbr = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const dayData = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
    
    calendarWeek.innerHTML = '';
    
    for (let i = 0; i < 7; i++) {
        const currentDay = new Date(monday);
        currentDay.setDate(currentDay.getDate() + i);
        
        const dateStr = currentDay.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        const dateYMD = formatDateToYMD(currentDay);
        const dayAbbrName = dayAbbr[i];
        const dayDataName = dayData[i];
        
        const dayDiv = document.createElement('div');
        dayDiv.className = 'calendar-day';
        dayDiv.innerHTML = `
            <div class="day-header">
                <span>${dateStr}</span>
            </div>
            <div class="meal-slots">
                <div class="meal-slot" data-day="${dayDataName}" data-meal="breakfast" data-date="${dateYMD}">
                    <strong>Breakfast</strong>
                    <div class="meal-content">Click to plan</div>
                </div>
                <div class="meal-slot" data-day="${dayDataName}" data-meal="lunch" data-date="${dateYMD}">
                    <strong>Lunch</strong>
                    <div class="meal-content">Click to plan</div>
                </div>
                <div class="meal-slot" data-day="${dayDataName}" data-meal="dinner" data-date="${dateYMD}">
                    <strong>Dinner</strong>
                    <div class="meal-content">Click to plan</div>
                </div>
            </div>
        `;
        calendarWeek.appendChild(dayDiv);
    }
    
    // Re-setup meal slot handlers for new calendar
    setupMealSlots();
}

// Setup action buttons
function setupActionButtons() {
    const clearAllBtn = document.getElementById('clear-all');
    const confirmPlanBtn = document.getElementById('confirm-plan');
    
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', () => {
            clearAllMeals();
        });
    }
    
    if (confirmPlanBtn) {
        confirmPlanBtn.addEventListener('click', () => {
            confirmWeeklyPlan();
        });
    }
}

// Open recipe modal
function openRecipeModal() {
    const recipeModal = document.getElementById('recipe-modal');
    if (!recipeModal) return;
    
    recipeModal.style.display = 'flex';
    document.getElementById('meal-details').style.display = 'none';
    document.getElementById('recipe-grid').style.display = 'grid';
    updateRemoveMealButtonVisibility();
    
    // Load recipes
    loadRecipes();
}

// Close recipe modal
function closeRecipeModal() {
    const recipeModal = document.getElementById('recipe-modal');
    if (recipeModal) {
        recipeModal.style.display = 'none';
    }
    currentMealSlot = null;
    currentRecipe = null;
    currentIngredients = [];
    updateRemoveMealButtonVisibility();
}

// Add meal to plan
function addMealToPlan() {
    if (!currentMealSlot || !currentRecipe) return;
    
    let mealName = '';
    if (currentRecipe.title === 'Custom Meal') {
        mealName = document.getElementById('custom-meal-name')?.value.trim();
        if (!mealName) {
            showNotification('Please enter a meal name', 'error');
            return;
        }
    } else {
        mealName = currentRecipe.title;
    }
    
    // Validate ingredients
    if (currentIngredients.length === 0) {
        showNotification('Please add at least one ingredient', 'error');
        return;
    }
    
    // Validate quantities
    for (const ing of currentIngredients) {
        const input = document.querySelector(`.ingredient-qty[data-inventory-id="${ing.inventory_id}"]`);
        if (input) {
            const quantity = parseInt(input.value) || 0;
            const max = parseInt(input.max);
            if (quantity <= 0 || quantity > max) {
                showNotification(`Invalid quantity for ${ing.name}`, 'error');
                return;
            }
            ing.quantity = quantity;
        }
    }
    
    const mealContent = currentMealSlot.querySelector('.meal-content');
    mealContent.textContent = mealName;
    currentMealSlot.classList.add('planned');
    
    // Store ingredients data
    currentMealSlot.setAttribute('data-meal-name', mealName);
    currentMealSlot.setAttribute('data-ingredients', JSON.stringify(currentIngredients));
    
    showNotification('Meal added to your plan!');
    closeRecipeModal();
}

// Clear all meals for current week
async function clearAllMeals() {
    const { mondayStr, sundayStr } = getWeekRangeDates();
    
    if (!confirm('Clear all meals for this week?')) {
        return;
    }
    
    try {
        const response = await fetch('clear_meals.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ week_start: mondayStr, week_end: sundayStr })
        });
        
        const result = await response.json();
        
        if (result.success) {
            updateCalendarDisplay();
            currentMealSlot = null;
            updateRemoveMealButtonVisibility();
            await loadSavedMealPlans();
            await loadReservedQuantities();
            await loadInventoryItems();
            showNotification('Week cleared successfully');
        } else {
            showNotification(result.message || 'Error clearing meals', 'error');
        }
    } catch (error) {
        console.error('Error clearing meals:', error);
        showNotification('Error clearing meals', 'error');
    }
}

// Confirm weekly plan
async function confirmWeeklyPlan() {
    const plannedMeals = [];
    const mealSlots = document.querySelectorAll('.meal-slot.planned');
    
    mealSlots.forEach(slot => {
        const day = slot.getAttribute('data-day');
        const meal = slot.getAttribute('data-meal');
        const date = slot.getAttribute('data-date');
        const mealName = slot.getAttribute('data-meal-name') || slot.querySelector('.meal-content').textContent;
        const ingredientsJson = slot.getAttribute('data-ingredients');
        
        if (mealName && mealName !== 'Click to plan') {
            const mealData = {
                date: date,
                meal_type: meal,
                meal_name: mealName,
                ingredients: ingredientsJson ? JSON.parse(ingredientsJson) : []
            };
            plannedMeals.push(mealData);
        }
    });
    
    if (plannedMeals.length === 0) {
        showNotification('Please plan at least one meal before confirming!', 'error');
        return;
    }
    
    const { mondayStr, sundayStr } = getWeekRangeDates();
    
    try {
        const response = await fetch('save_meal_plan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ week_start: mondayStr, week_end: sundayStr, meals: plannedMeals })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(`Weekly meal plan confirmed! ${plannedMeals.length} meal(s) saved.`);
            currentMealSlot = null;
            updateRemoveMealButtonVisibility();
            await loadSavedMealPlans();
            await loadReservedQuantities();
            await loadInventoryItems();
        } else {
            showNotification(result.error || 'Error saving meal plan', 'error');
        }
    } catch (error) {
        console.error('Error saving meal plan:', error);
        showNotification('Error saving meal plan', 'error');
    }
}

function resetMealSlot(slot) {
    if (!slot) return;
    slot.classList.remove('planned');
    const mealContent = slot.querySelector('.meal-content');
    if (mealContent) {
        mealContent.textContent = 'Click to plan';
    }
    slot.removeAttribute('data-meal-id');
    slot.removeAttribute('data-meal-name');
    slot.removeAttribute('data-ingredients');
}

function updateRemoveMealButtonVisibility() {
    const removeBtn = document.getElementById('remove-meal');
    if (!removeBtn) return;
    if (currentMealSlot && currentMealSlot.classList.contains('planned')) {
        removeBtn.style.display = 'inline-flex';
    } else {
        removeBtn.style.display = 'none';
    }
}

async function clearMealSlot(slot, showMessage = true) {
    if (!slot) return;
    const mealId = slot.getAttribute('data-meal-id');
    const isPlanned = slot.classList.contains('planned');
    
    if (!mealId) {
        resetMealSlot(slot);
        updateRemoveMealButtonVisibility();
        if (showMessage && isPlanned) {
            showNotification('Meal cleared', 'success');
        }
        return;
    }
    
    if (mealId) {
        try {
            const response = await fetch('delete_meal.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ meal_id: mealId })
            });
            const result = await response.json();
            if (!result.success) {
                showNotification(result.message || 'Error clearing meal', 'error');
                return;
            }
        } catch (error) {
            console.error('Error clearing meal:', error);
            showNotification('Error clearing meal', 'error');
            return;
        }
    } else if (!isPlanned) {
        resetMealSlot(slot);
        updateRemoveMealButtonVisibility();
        return;
    }
    
    resetMealSlot(slot);
    updateRemoveMealButtonVisibility();
    await loadReservedQuantities();
    await loadInventoryItems();
    await loadSavedMealPlans();
    if (showMessage) {
        showNotification('Meal cleared', 'success');
    }
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    if (!notification) return;
    
    notification.textContent = message;
    notification.className = `notification ${type} show`;
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}
