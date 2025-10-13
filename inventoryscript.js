// Global variables
let inventoryData = [];
let filteredData = [];

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inventory page loaded');
    loadInventoryData();
    setupEventListeners();
    setupFilters();
});

// Load inventory data from server
async function loadInventoryData() {
    try {
        console.log('Loading inventory data...');
        const response = await fetch('get_inventory.php?user_id=1');
        const data = await response.json();
        
        inventoryData = data;
        filteredData = [...data];
        console.log('Loaded', data.length, 'items');
        
        renderInventoryTable();
        updateStatistics();
    } catch (error) {
        console.error('Error loading inventory data:', error);
        showNotification('Error loading inventory data', 'error');
    }
}

// Render inventory table
function renderInventoryTable() {
    const tbody = document.getElementById('inventoryTableBody');
    tbody.innerHTML = '';
    
    if (filteredData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem; color: #6b7280;">No items found</td></tr>';
        return;
    }
    
    filteredData.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.name}</td>
            <td>${item.quantity}</td>
            <td>${item.expiry}</td>
            <td>${item.category}</td>
            <td>${item.location}</td>
            <td>${getStatusBadge(item.status)}</td>
            <td class="action-buttons">
                <button class="action-btn btn-edit" data-tooltip="Edit" onclick="openEditModal(${item.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="action-btn btn-consume" data-tooltip="Consume" onclick="openConsumeModal(${item.id})">
                    <i class="fas fa-utensils"></i>
                </button>
                <button class="action-btn btn-donate" data-tooltip="Donate" onclick="openDonateModal(${item.id})">
                    <i class="fas fa-heart"></i>
                </button>
                <button class="action-btn btn-delete" data-tooltip="Delete" onclick="deleteItem(${item.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Get status badge HTML
function getStatusBadge(status) {
    switch (status) {
        case 'good':
            return '<span class="status-badge good">✅ Good</span>';
        case 'expiring_soon':
            return '<span class="status-badge expiring_soon">⚠️ Expiring Soon</span>';
        case 'expired':
            return '<span class="status-badge expired">❌ Expired</span>';
        default:
            return '<span class="status-badge good">✅ Good</span>';
    }
}

// Update statistics
function updateStatistics() {
    const totalItems = inventoryData.length;
    const goodItems = inventoryData.filter(item => item.status === 'good').length;
    const expiringItems = inventoryData.filter(item => item.status === 'expiring_soon').length;
    const expiredItems = inventoryData.filter(item => item.status === 'expired').length;
    
    document.getElementById('totalItems').textContent = totalItems;
    document.getElementById('goodItems').textContent = goodItems;
    document.getElementById('expiringItems').textContent = expiringItems;
    document.getElementById('expiredItems').textContent = expiredItems;
}

// Setup filters
function setupFilters() {
    const categoryFilter = document.getElementById('categoryFilter');
    const locationFilter = document.getElementById('locationFilter');
    const statusFilter = document.getElementById('statusFilter');
    const searchFilter = document.getElementById('searchFilter');
    
    categoryFilter.addEventListener('change', applyFilters);
    locationFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    searchFilter.addEventListener('input', applyFilters);
}

// Apply filters
function applyFilters() {
    const categoryFilter = document.getElementById('categoryFilter').value;
    const locationFilter = document.getElementById('locationFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const searchFilter = document.getElementById('searchFilter').value.toLowerCase();
    
    filteredData = inventoryData.filter(item => {
        const matchesCategory = !categoryFilter || item.category.toLowerCase().replace(' ', '_') === categoryFilter;
        const matchesLocation = !locationFilter || item.storage_type === locationFilter;
        const matchesStatus = !statusFilter || item.status === statusFilter;
        const matchesSearch = !searchFilter || item.name.toLowerCase().includes(searchFilter);
        
        return matchesCategory && matchesLocation && matchesStatus && matchesSearch;
    });
    
    renderInventoryTable();
}

// Setup event listeners
function setupEventListeners() {
    // Add form
    document.getElementById('addForm').addEventListener('submit', handleAddItem);
    
    // Edit form
    document.getElementById('editForm').addEventListener('submit', handleEditItem);
    
    // Consume form
    document.getElementById('consumeForm').addEventListener('submit', handleConsumeItem);
    
    // Donate form
    document.getElementById('donateForm').addEventListener('submit', handleDonateItem);
    
    // Close modals when clicking outside
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });
}

// Modal functions
function openAddModal() {
    console.log('Opening add modal');
    document.getElementById('addModal').style.display = 'block';
    
    // Set default date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('addExpiry').value = today;
}

function openEditModal(itemId) {
    console.log('Opening edit modal for item:', itemId);
    const item = inventoryData.find(i => i.id == itemId);
    if (!item) return;
    
    // Populate form
    document.getElementById('editId').value = item.id;
    document.getElementById('editName').value = item.name;
    document.getElementById('editQuantity').value = item.quantity;
    document.getElementById('editExpiry').value = item.expiry_date;
    document.getElementById('editCategory').value = item.category.toLowerCase().replace(' ', '_');
    document.getElementById('editStorage').value = item.storage_type;
    document.getElementById('editRemarks').value = item.remarks || '';
    
    document.getElementById('editModal').style.display = 'block';
}

function openConsumeModal(itemId) {
    console.log('Opening consume modal for item:', itemId);
    const item = inventoryData.find(i => i.id == itemId);
    if (!item) return;
    
    // Check if expired
    if (item.status === 'expired') {
        showNotification('Cannot consume expired items', 'error');
        return;
    }
    
    // Populate form
    document.getElementById('consumeId').value = item.id;
    document.getElementById('consumeQuantity').value = item.quantity;
    document.getElementById('consumeQuantity').max = item.quantity;
    document.getElementById('consumeAvailable').textContent = item.quantity;
    
    document.getElementById('consumeModal').style.display = 'block';
}

function openDonateModal(itemId) {
    console.log('Opening donate modal for item:', itemId);
    const item = inventoryData.find(i => i.id == itemId);
    if (!item) return;
    
    // Check if expired
    if (item.status === 'expired') {
        showNotification('Cannot donate expired items', 'error');
        return;
    }
    
    // Populate form
    document.getElementById('donateId').value = item.id;
    document.getElementById('donateQuantity').value = item.quantity;
    document.getElementById('donateQuantity').max = item.quantity;
    document.getElementById('donateAvailable').textContent = item.quantity;
    
    document.getElementById('donateModal').style.display = 'block';
}

function closeModal(modalId) {
    console.log('Closing modal:', modalId);
    document.getElementById(modalId).style.display = 'none';
    
    // Reset forms
    if (modalId === 'addModal') {
        document.getElementById('addForm').reset();
    } else if (modalId === 'editModal') {
        document.getElementById('editForm').reset();
    } else if (modalId === 'consumeModal') {
        document.getElementById('consumeForm').reset();
    } else if (modalId === 'donateModal') {
        document.getElementById('donateForm').reset();
    }
}

// Form handlers
async function handleAddItem(e) {
    e.preventDefault();
    console.log('Adding new item');
    
    const formData = new FormData(e.target);
    formData.append('user_id', '1');
    
    try {
        const response = await fetch('add_item.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Item added successfully', 'success');
            closeModal('addModal');
            loadInventoryData();
        } else {
            showNotification(result.message || 'Error adding item', 'error');
        }
    } catch (error) {
        console.error('Error adding item:', error);
        showNotification('Error adding item', 'error');
    }
}

async function handleEditItem(e) {
    e.preventDefault();
    console.log('Editing item');
    
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('edit_item.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Item updated successfully', 'success');
            closeModal('editModal');
            loadInventoryData();
        } else {
            showNotification(result.message || 'Error updating item', 'error');
        }
    } catch (error) {
        console.error('Error updating item:', error);
        showNotification('Error updating item', 'error');
    }
}

async function handleConsumeItem(e) {
    e.preventDefault();
    console.log('Consuming item');
    
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('consume_item.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Item consumed successfully', 'success');
            closeModal('consumeModal');
            loadInventoryData();
        } else {
            showNotification(result.message || 'Error consuming item', 'error');
        }
    } catch (error) {
        console.error('Error consuming item:', error);
        showNotification('Error consuming item', 'error');
    }
}

async function handleDonateItem(e) {
    e.preventDefault();
    console.log('Donating item');
    
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('donate_item.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Item donated successfully', 'success');
            closeModal('donateModal');
            loadInventoryData();
        } else {
            showNotification(result.message || 'Error donating item', 'error');
        }
    } catch (error) {
        console.error('Error donating item:', error);
        showNotification('Error donating item', 'error');
    }
}

async function deleteItem(itemId) {
    console.log('Deleting item:', itemId);
    
    if (!confirm('Are you sure you want to delete this item?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('item_id', itemId);
    
    try {
        const response = await fetch('delete_item.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Item deleted successfully', 'success');
            loadInventoryData();
        } else {
            showNotification(result.message || 'Error deleting item', 'error');
        }
    } catch (error) {
        console.error('Error deleting item:', error);
        showNotification('Error deleting item', 'error');
    }
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = `notification ${type} show`;
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);

}
