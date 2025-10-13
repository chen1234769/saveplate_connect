// Browse Page Navigation and Functionality
document.addEventListener('DOMContentLoaded', function() {
    // 移动端菜单切换功能
    const hamburger = document.querySelector(".hamburger");
    const navMenu = document.querySelector(".nav-menu");

    if (hamburger && navMenu) {
        hamburger.addEventListener("click", () => {
            hamburger.classList.toggle("active");
            navMenu.classList.toggle("active");
        });
    }

    // 活动指示器功能
    const navLinks = document.querySelectorAll(".nav-link");
    const activeIndicator = document.querySelector(".active-indicator");

    function moveIndicator(activeLink) {
        if (!activeIndicator) return;
        
        const linkRect = activeLink.getBoundingClientRect();
        const navListRect = activeLink.closest('.nav-list').getBoundingClientRect();
        
        const left = linkRect.left - navListRect.left;
        const width = linkRect.width;
        
        activeIndicator.style.width = `${width}px`;
        activeIndicator.style.left = `${left}px`;
    }

    // 页面加载时设置活动菜单项
    const activeLink = document.querySelector('.nav-link.active');
    if (activeLink && activeIndicator) {
        setTimeout(() => {
            moveIndicator(activeLink);
        }, 100);
    }

    // 点击页面其他区域关闭移动菜单
    document.addEventListener('click', (event) => {
        const isClickInsideNav = event.target.closest('.page-nav');
        if (!isClickInsideNav && hamburger && navMenu) {
            hamburger.classList.remove("active");
            navMenu.classList.remove("active");
        }
    });

    // 窗口大小改变时重新定位指示器
    window.addEventListener('resize', function() {
        const activeLink = document.querySelector('.nav-link.active');
        if (activeLink && activeIndicator) {
            moveIndicator(activeLink);
        }
    });

    // 移除滚动时的背景透明度变化，避免覆盖内容
    // If needed later, we can re-introduce with safer styles.

    // Browse specific functionality
    // List button selection (only one active at a time)
    const listButtons = document.querySelectorAll('.list-btn');
    
    listButtons.forEach(button => {
        button.addEventListener('click', function() {
            console.log('Button clicked:', this.textContent);
            
            // Remove active class from all buttons
            listButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // You can add functionality here to load different lists
            const listType = this.getAttribute('data-list');
            console.log('Selected list:', listType);
            loadAndRender(listType);
        });
    });

    // Date input formatting and validation
    const fromDate = document.getElementById('from-date');
    const toDate = document.getElementById('to-date');
    
    if (fromDate && toDate) {
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        fromDate.min = today;
        toDate.min = today;
        
        // Update min date of "to" when "from" changes
        fromDate.addEventListener('change', function() {
            toDate.min = this.value;
            
            // If toDate is before fromDate, reset it
            if (toDate.value && toDate.value < this.value) {
                toDate.value = this.value;
            }
        });
        
        // Validate that toDate is not before fromDate
        toDate.addEventListener('change', function() {
            if (fromDate.value && this.value < fromDate.value) {
                this.value = fromDate.value;
                alert('End date cannot be before start date');
            }
        });
    }

    // Enhanced dropdown functionality
    const dropdowns = document.querySelectorAll('.dropdown-select');
    
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('change', function() {
            console.log('Selected:', this.value, 'from', this.id);
        });
    });

    // Dynamic data loading + rendering
    const grid = document.getElementById('food-tags-grid');
    const detail = document.getElementById('food-detail');

    function buildQuery(params) {
        const usp = new URLSearchParams();
        Object.entries(params).forEach(([k, v]) => {
            if (v !== undefined && v !== null && v !== '') usp.append(k, v);
        });
        return usp.toString();
    }

    async function fetchItems(filters) {
        const base = 'browse_items.php';
        const query = buildQuery(filters);
        const url = query ? `${base}?${query}` : base;
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('Failed to load items');
        return await res.json();
    }

    function renderItems(items) {
        if (!grid) return;
        grid.innerHTML = '';
        if (!Array.isArray(items) || items.length === 0) {
            grid.innerHTML = '<div class="empty">No items found</div>';
            return;
        }
        items.forEach(item => {
            const card = document.createElement('div');
            card.className = 'food-tag';
            const typeClass = item.item_type === 'donation' ? 'donation' : '';
            const typeLabel = item.item_type === 'donation' ? 'Donation' : 'Inventory';
            card.innerHTML = `
                <div class="food-tag-header">
                    <div class="food-tag-name">${item.name || 'Unnamed'}</div>
                    <div class="food-type-chip ${typeClass}">${typeLabel}</div>
                </div>
            `;
            card.addEventListener('click', () => toggleCard(card, item));
            grid.appendChild(card);
        });
        
        // Apply search filter after rendering
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            const searchValue = searchInput.value.toLowerCase();
            filterItemsBySearch(searchValue);
        }
    }

    function toggleCard(card, item) {
        const isExpanded = card.classList.contains('expanded');
        
        // Close all other expanded cards first
        document.querySelectorAll('.food-tag.expanded').forEach(el => {
            if (el !== card) {
                el.classList.remove('expanded');
                const det = el.querySelector('.food-details');
                if (det) det.remove();
            }
        });

        if (isExpanded) {
            // If this card is already expanded, close it
            card.classList.remove('expanded');
            const det = card.querySelector('.food-details');
            if (det) det.remove();
            return;
        }

        // Expand this card
        card.classList.add('expanded');
        const details = document.createElement('div');
        details.className = 'food-details';
        details.innerHTML = `
            <div class="panel">
                <div><strong>Quantity:</strong> ${item.quantity ?? '-'}</div>
                <div><strong>Expiry Date:</strong> ${item.expiry_date ?? '-'}</div>
                <div><strong>Storage Type:</strong> ${item.storage_type ?? '-'}</div>
                <div><strong>Category:</strong> ${item.category ?? '-'}</div>
                <div><strong>Status:</strong> ${item.status ?? '-'}</div>
            </div>
            <div class="panel">
                <div><strong>Pickup Location:</strong> ${item.pickup_location ?? '-'}</div>
                <div><strong>Contact Method:</strong> ${item.contact_method ?? '-'}</div>
            </div>
            <div class="panel food-notes"><strong>Remarks:</strong> ${item.remarks ?? '-'}</div>
            <div class="food-actions">
                <button class="action-btn used" onclick="handleAction('used', ${item.id})">Used</button>
                <button class="action-btn meal" onclick="handleAction('meal', ${item.id})">Meal</button>
                <button class="action-btn donate" onclick="handleAction('donate', ${item.id})">Donate</button>
            </div>
        `;
        card.appendChild(details);
        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async function loadAndRender(listType) {
        const filters = {
            user_id: window.CURRENT_USER_ID,
            list: listType || (document.querySelector('.list-btn.active')?.dataset.list || 'full'),
            from: document.getElementById('from-date')?.value,
            to: document.getElementById('to-date')?.value,
            storage: document.getElementById('storage-type')?.value,
            category: document.getElementById('categories')?.value
        };
        try {
            const items = await fetchItems(filters);
            renderItems(items);
        } catch (e) {
            console.error(e);
            if (grid) grid.innerHTML = '<div class="error">Failed to load items</div>';
        }
    }

    // Filter change triggers
    ['from-date','to-date','storage-type','categories'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => loadAndRender());
    });

    // Clear functions
    window.clearStorageType = function() {
        document.getElementById('storage-type').value = '';
        loadAndRender();
    };

    window.clearCategories = function() {
        document.getElementById('categories').value = '';
        loadAndRender();
    };

    // Action handlers
    window.handleAction = async function(action, itemId) {
        try {
            const response = await fetch('handle_item_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, itemId, user_id: window.CURRENT_USER_ID })
            });
            if (response.ok) {
                loadAndRender(); // Refresh the list
            } else {
                alert('Action failed');
            }
        } catch (e) {
            console.error('Action error:', e);
            alert('Action failed');
        }
    };

    // Initial load
    loadAndRender('full');

    // Search functionality
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            filterItemsBySearch(searchValue);
        });
    }

    console.log('Browse page loaded');
});

// Search filter function that works with existing system
function filterItemsBySearch(searchValue) {
    const grid = document.getElementById('food-tags-grid');
    if (!grid) return;
    
    const allItems = grid.querySelectorAll('.food-tag');
    
    allItems.forEach(item => {
        const itemName = item.querySelector('.food-tag-name');
        if (itemName) {
            const name = itemName.textContent.toLowerCase();
            
            if (searchValue === '' || name.startsWith(searchValue)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        }
    });
}