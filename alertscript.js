const alertTypeMap = {
    expiring_soon: { icon: '⚠️', style: 'urgent' },
    donation_success: { icon: '✅', style: 'success' },
    donation_available: { icon: '🎁', style: 'success' },
    donation_removed: { icon: '↩️', style: 'info' },
    meal_plan: { icon: '🍽️', style: 'info' },
    meal_reminder: { icon: '🔔', style: 'info' },
    default: { icon: 'ℹ️', style: 'info' }
};

let currentFilter = 'all';

document.addEventListener('DOMContentLoaded', () => {
    loadAlerts();
    setupFilters();
    setupActionButtons();
    // Update relative "time ago" text periodically
    setInterval(updateAlertTimes, 60000); // every 1 min
    // Auto-refresh alerts every 30 seconds for real-time updates
    setInterval(() => {
        loadAlerts();
        // Check for expiring items and meal reminders
        fetch('check_expiring_items.php').catch(() => {});
        fetch('check_meal_reminders.php').catch(() => {});
    }, 30000); // every 30 sec
});

function setupFilters() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilter = btn.dataset.filter;
    loadAlerts();
});
    });
}

function setupActionButtons() {
    const refreshBtn = document.getElementById('refreshBtn');
    const markAllBtn = document.getElementById('markAllReadBtn');
    const clearAllBtn = document.getElementById('clearAllBtn');
    
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            loadAlerts();
            refreshBtn.querySelector('i').classList.add('fa-spin');
            setTimeout(() => {
                refreshBtn.querySelector('i').classList.remove('fa-spin');
            }, 1000);
        });
    }
    
    if (markAllBtn) {
        markAllBtn.addEventListener('click', markAllAlertsRead);
    }
    
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', clearAllAlerts);
    }
}

async function loadAlerts() {
    const alertsGrid = document.getElementById('alertsGrid');
    const emptyState = document.getElementById('alertsEmpty');
    if (!alertsGrid) return;
    
    try {
        const url = `get_alerts.php?filter=${currentFilter}`;
        const response = await fetch(url);
        if (!response.ok) throw new Error('Failed to fetch alerts');
        const data = await response.json();
        const alerts = Array.isArray(data.alerts) ? data.alerts : [];
        
        // Update counts
        if (data.counts) {
            document.getElementById('totalAlerts').textContent = data.counts.total || 0;
            document.getElementById('unreadAlerts').textContent = data.counts.unread || 0;
        }
        
        alertsGrid.innerHTML = '';
        if (alerts.length === 0) {
            emptyState.style.display = 'block';
            return;
        }
        emptyState.style.display = 'none';
        
        alerts.forEach(alert => {
            const card = createAlertCard(alert);
            alertsGrid.appendChild(card);
        });
        
        if (typeof window.refreshAlertBadge === 'function') {
            window.refreshAlertBadge();
        }
    } catch (error) {
        console.error('Failed to load alerts:', error);
        if (emptyState) {
            emptyState.style.display = 'block';
            emptyState.textContent = 'Unable to load alerts right now.';
        }
    }
}

function formatRelativeTimeJS(dateStr) {
    if (!dateStr) return '';
    const created = new Date(dateStr);
    if (isNaN(created.getTime())) return '';
    const diffMs = Date.now() - created.getTime();
    const diffSec = Math.floor(diffMs / 1000);
    if (diffSec < 60) return 'Just now';
    const diffMin = Math.floor(diffSec / 60);
    if (diffMin < 60) return `${diffMin} min ago`;
    const diffHrs = Math.floor(diffMin / 60);
    if (diffHrs < 24) return `${diffHrs} hrs ago`;
    return created.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

function createAlertCard(alert) {
    const map = alertTypeMap[alert.type] || alertTypeMap.default;
    const card = document.createElement('div');
    card.className = `alert-card ${map.style}`;
    card.dataset.alertId = alert.id;
    card.dataset.isRead = alert.is_read ? 'true' : 'false';
    // store raw timestamp so we can keep updating text
    card.dataset.createdAt = alert.created_at || '';
    const timeText = alert.created_at ? formatRelativeTimeJS(alert.created_at) : (alert.time || '');
    const markReadBtn = !alert.is_read ? `
        <button class="alert-mark-read-btn" onclick="event.stopPropagation(); markAlertRead(${alert.id})" title="Mark as Read">
            <i class="fas fa-check"></i>
        </button>
    ` : '';
    card.innerHTML = `
        <div class="alert-icon">${map.icon}</div>
        <div class="alert-content">
            <h3 class="alert-title">${alert.title}</h3>
            <p class="alert-message">${alert.message}</p>
            <div class="alert-time">${timeText}</div>
        </div>
        <div class="alert-card-actions">
            ${markReadBtn}
            <button class="alert-delete-btn" onclick="event.stopPropagation(); deleteAlert(${alert.id})" title="Delete">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Click to open modal (but not on action buttons)
    card.addEventListener('click', (e) => {
        if (!e.target.closest('.alert-card-actions')) {
            if (alert.link) {
                window.location.href = alert.link;
            } else {
                openAlertModal(alert);
            }
        }
    });
    
    return card;
}

const alertModal = document.getElementById('alertModal');
const closeAlertModalBtn = document.getElementById('closeAlertModal');
const modalTitle = document.getElementById('modalTitle');
const modalMessage = document.getElementById('modalMessage');
const modalTime = document.getElementById('modalTime');
const modalLinkBtn = document.getElementById('modalLinkBtn');

function openAlertModal(alert) {
    if (!alertModal) return;
    modalTitle.textContent = alert.title;
    modalMessage.textContent = alert.message;
    modalTime.textContent = alert.created_at ? formatRelativeTimeJS(alert.created_at) : (alert.time || '');
    
    if (alert.link) {
        modalLinkBtn.style.display = 'inline-flex';
        modalLinkBtn.onclick = () => {
            window.location.href = alert.link;
        };
    } else {
        modalLinkBtn.style.display = 'none';
        modalLinkBtn.onclick = null;
    }
    
    alertModal.style.display = 'flex';
}

function closeAlertModal() {
    if (alertModal) {
        alertModal.style.display = 'none';
    }
}

if (closeAlertModalBtn) {
    closeAlertModalBtn.addEventListener('click', closeAlertModal);
}

if (alertModal) {
    alertModal.addEventListener('click', (e) => {
        if (e.target === alertModal) {
            closeAlertModal();
        }
    });
}

// Refresh "x min ago" text for all cards without reloading alerts
function updateAlertTimes() {
    const cards = document.querySelectorAll('.alert-card');
    cards.forEach(card => {
        const createdAt = card.dataset.createdAt;
        if (!createdAt) return;
        const timeEl = card.querySelector('.alert-time');
        if (!timeEl) return;
        timeEl.textContent = formatRelativeTimeJS(createdAt);
    });
}

// Mark all alerts as read
async function markAllAlertsRead() {
    if (!confirm('Mark all alerts as read?')) return;
    
    try {
        const response = await fetch('mark_all_alerts_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        const result = await response.json();
        if (result.success) {
            await loadAlerts();
            if (typeof window.refreshAlertBadge === 'function') {
                window.refreshAlertBadge();
            }
        }
    } catch (error) {
        console.error('Error marking all alerts as read:', error);
    }
}

// Clear all alerts
async function clearAllAlerts() {
    if (!confirm('Are you sure you want to delete all alerts? This action cannot be undone.')) return;
    
    try {
        const response = await fetch('clear_all_alerts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        const result = await response.json();
        if (result.success) {
            await loadAlerts();
            if (typeof window.refreshAlertBadge === 'function') {
                window.refreshAlertBadge();
            }
        }
    } catch (error) {
        console.error('Error clearing all alerts:', error);
    }
}

// Mark single alert as read
async function markAlertRead(alertId) {
    try {
        const response = await fetch('mark_alert_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ alert_id: alertId })
        });
        const result = await response.json();
        if (result.success) {
            // Hide mark as read button
            const card = document.querySelector(`[data-alert-id="${alertId}"]`);
            if (card) {
                const markReadBtn = card.querySelector('.alert-mark-read-btn');
                if (markReadBtn) {
                    markReadBtn.style.display = 'none';
                }
                card.dataset.isRead = 'true';
            }
            await loadAlerts(); // Reload to update counts
            if (typeof window.refreshAlertBadge === 'function') {
                window.refreshAlertBadge();
            }
        }
    } catch (error) {
        console.error('Error marking alert as read:', error);
    }
}

// Delete single alert
async function deleteAlert(alertId) {
    try {
        const response = await fetch('delete_alert.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ alert_id: alertId })
        });
        const result = await response.json();
        if (result.success) {
            // Remove card with animation
            const card = document.querySelector(`[data-alert-id="${alertId}"]`);
            if (card) {
                card.style.opacity = '0';
                card.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    card.remove();
                    loadAlerts(); // Reload to update counts
                }, 300);
            }
            if (typeof window.refreshAlertBadge === 'function') {
                window.refreshAlertBadge();
            }
        }
    } catch (error) {
        console.error('Error deleting alert:', error);
    }
}
