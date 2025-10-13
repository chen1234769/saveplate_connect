// Dashboard Page Functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard page loaded');
    
    // Load and update statistics
    loadStatistics();
    
    // Add event listeners for action buttons
    setupActionButtons();
});

async function loadStatistics() {
    try {
        // Fetch all items to calculate statistics
        const response = await fetch('browse_items.php?user_id=' + window.CURRENT_USER_ID + '&list=full');
        const items = await response.json();
        
        if (Array.isArray(items)) {
            updateStatistics(items);
        }
    } catch (error) {
        console.error('Failed to load statistics:', error);
    }
}

function updateStatistics(items) {
    const totalItems = items.length;
    const goodItems = items.filter(item => item.status === 'Good').length;
    const expiringSoon = items.filter(item => item.status === 'Expiring Soon').length;
    const expiredItems = items.filter(item => item.status === 'Expired').length;
    
    // Update the DOM elements
    const totalItemsEl = document.getElementById('total-items');
    const goodItemsEl = document.getElementById('good-items');
    const expiringSoonEl = document.getElementById('expiring-soon');
    const expiredItemsEl = document.getElementById('expired-items');
    
    if (totalItemsEl) totalItemsEl.textContent = totalItems;
    if (goodItemsEl) goodItemsEl.textContent = goodItems;
    if (expiringSoonEl) expiringSoonEl.textContent = expiringSoon;
    if (expiredItemsEl) expiredItemsEl.textContent = expiredItems;
}

function setupActionButtons() {
    // Action buttons now use onclick handlers directly in HTML
    // No need for additional event listeners
    console.log('Action buttons are set up with direct navigation');
}
