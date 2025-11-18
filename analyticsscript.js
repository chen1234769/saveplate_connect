let currentPeriod = '12months'; // Default period

document.addEventListener('DOMContentLoaded', () => {
    // Ensure Chart text uses white by default to match dark background
    if (window.Chart && Chart.defaults && Chart.defaults.color !== '#fff') {
        Chart.defaults.color = '#fff';
        Chart.defaults.font.weight = '500';
    }
    setupPeriodFilters();
    loadAnalyticsData();
});

function setupPeriodFilters() {
    const filterBtns = document.querySelectorAll('.chart-filter .filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active class from all buttons
            filterBtns.forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            btn.classList.add('active');
            // Update current period and reload data
            currentPeriod = btn.dataset.period;
            loadAnalyticsData();
        });
    });
}

async function loadAnalyticsData() {
    try {
        const url = `get_analytics_data.php?period=${currentPeriod}`;
        const response = await fetch(url);
        if (!response.ok) throw new Error('Failed to fetch analytics data');
        const data = await response.json();
        updateCards(data.cards || {});
        renderCategoryChart(data.category_distribution || []);
        renderTrendChart(data.monthly_trend || {});
        generateInsights(data.insights_data || {});
    } catch (error) {
        console.error('Failed to load analytics data:', error);
    }
}

function updateCards(cards) {
    setCardValue('food-saved', `${cards.food_saved || 0} item${cards.food_saved === 1 ? '' : 's'}`);
    setCardValue('waste-reduction', `${cards.waste_reduction || 0}%`);
    setCardValue('items-donated', cards.items_donated || 0);
    setCardValue('meals-planned', cards.meals_planned || 0);
}

function setCardValue(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}

let categoryChartInstance = null;
function renderCategoryChart(dataset) {
    const canvas = document.getElementById('categoryChart');
    const message = document.getElementById('categoryChartMessage');
    if (!canvas) return;
    
    if (categoryChartInstance) categoryChartInstance.destroy();
    
    if (!dataset.length) {
        canvas.style.display = 'none';
        if (message) message.style.display = 'block';
        return;
    }
    
    canvas.style.display = 'block';
    if (message) message.style.display = 'none';
    
    categoryChartInstance = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: dataset.map(d => d.label),
            datasets: [{
                data: dataset.map(d => d.value),
                backgroundColor: ['#34d399', '#60a5fa', '#fbbf24', '#f87171', '#c084fc', '#f472b6']
            }]
        },
        options: {
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#fff' }
                }
            }
        }
    });
}

let trendChartInstance = null;
function renderTrendChart(trend) {
    const canvas = document.getElementById('trendChart');
    const message = document.getElementById('trendChartMessage');
    if (!canvas) return;
    
    if (trendChartInstance) trendChartInstance.destroy();
    
    if (!trend || !trend.labels || !trend.labels.length) {
        canvas.style.display = 'none';
        if (message) message.style.display = 'block';
        return;
    }
    
    canvas.style.display = 'block';
    if (message) message.style.display = 'none';
    
    trendChartInstance = new Chart(canvas, {
        type: 'line',
        data: {
            labels: trend.labels,
            datasets: [
                {
                    label: 'Items Saved',
                    data: trend.saved || [],
                    borderColor: '#34d399',
                    backgroundColor: 'rgba(52, 211, 153, 0.2)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointHitRadius: 12
                },
                {
                    label: 'Items Wasted',
                    data: trend.wasted || [],
                    borderColor: '#f87171',
                    backgroundColor: 'rgba(248, 113, 113, 0.2)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointHitRadius: 12
                }
            ]
        },
        options: {
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#fff' }
                },
                tooltip: {
                    enabled: true,
                    mode: 'nearest',
                    intersect: false,
                    backgroundColor: 'rgba(0,0,0,0.85)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255,255,255,0.3)',
                    borderWidth: 1,
                    padding: 10
                }
            },
            interaction: {
                mode: 'nearest',
                intersect: false
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        color: '#fff'
                    },
                    grid: {
                        color: 'rgba(255,255,255,0.1)'
                    }
                },
                x: {
                    ticks: { color: '#fff' },
                    grid: {
                        color: 'rgba(255,255,255,0.05)'
                    }
                }
            }
        }
    });
}

// Generate insights and recommendations
function generateInsights(data) {
    const container = document.getElementById('insightsContainer');
    if (!container) return;
    
    const insights = [];
    
    // Insight 1: Waste Reduction Performance
    if (data.waste_reduction >= 80) {
        insights.push({
            type: 'positive',
            icon: '🎉',
            title: 'Excellent Waste Reduction!',
            text: `You're achieving ${data.waste_reduction}% waste reduction! You're doing an amazing job saving food from waste.`,
            action: 'Keep up the great work and continue planning meals and donating items.'
        });
    } else if (data.waste_reduction >= 50) {
        insights.push({
            type: 'info',
            icon: '📈',
            title: 'Good Progress on Waste Reduction',
            text: `Your waste reduction is at ${data.waste_reduction}%. There's room for improvement to reach even higher levels.`,
            action: 'Try planning more meals or donating items that are expiring soon to boost your score.'
        });
    } else if (data.waste_reduction > 0) {
        insights.push({
            type: 'warning',
            icon: '⚠️',
            title: 'Waste Reduction Needs Improvement',
            text: `Your current waste reduction is ${data.waste_reduction}%. More items are expiring than being saved.`,
            action: 'Focus on meal planning and donating items before they expire to improve your waste reduction.'
        });
    } else {
        insights.push({
            type: 'warning',
            icon: '🚨',
            title: 'Start Reducing Food Waste',
            text: 'You haven\'t saved any items yet. Begin by planning meals or donating items to reduce waste.',
            action: 'Visit the Meal page to plan your weekly meals or the Donate page to share items with others.'
        });
    }
    
    // Insight 2: Expiring Soon Alert
    if (data.expiring_soon_qty > 0) {
        insights.push({
            type: 'warning',
            icon: '⏰',
            title: 'Items Expiring Soon',
            text: `You have ${data.expiring_soon_qty} item${data.expiring_soon_qty === 1 ? '' : 's'} expiring soon in your inventory.`,
            action: 'Plan meals with these items or donate them quickly to prevent waste. Check your Inventory page for details.'
        });
    }
    
    // Insight 3: Expired Items Warning
    if (data.expired_qty > 0) {
        insights.push({
            type: 'warning',
            icon: '❌',
            title: 'Expired Items Detected',
            text: `You currently have ${data.expired_qty} expired item${data.expired_qty === 1 ? '' : 's'} in your inventory.`,
            action: 'Remove expired items and focus on better inventory management. Set reminders for items approaching expiry.'
        });
    }
    
    // Insight 4: Meal Planning Recommendation
    if (data.meals_planned === 0) {
        insights.push({
            type: 'info',
            icon: '🍽️',
            title: 'Start Meal Planning',
            text: 'You haven\'t planned any meals yet. Meal planning helps you use inventory items before they expire.',
            action: 'Visit the Meal page to create weekly meal plans and automatically reserve ingredients from your inventory.'
        });
    } else if (data.meals_planned < 5) {
        insights.push({
            type: 'info',
            icon: '📅',
            title: 'Increase Meal Planning',
            text: `You've planned ${data.meals_planned} meal${data.meals_planned === 1 ? '' : 's'}. Planning more meals can help reduce waste.`,
            action: 'Try planning meals for the entire week to maximize your inventory usage and reduce waste.'
        });
    } else {
        insights.push({
            type: 'positive',
            icon: '✅',
            title: 'Great Meal Planning!',
            text: `You've planned ${data.meals_planned} meals! This is helping you use your inventory effectively.`,
            action: 'Continue planning weekly meals to maintain your waste reduction momentum.'
        });
    }
    
    // Insight 5: Donation Activity
    if (data.items_donated === 0) {
        insights.push({
            type: 'info',
            icon: '♻️',
            title: 'Consider Donating Items',
            text: 'Donating items you won\'t use helps others and reduces waste. You haven\'t donated any items yet.',
            action: 'Check your inventory for items you won\'t use and share them on the Donate page before they expire.'
        });
    } else if (data.items_donated < 3) {
        insights.push({
            type: 'info',
            icon: '🤝',
            title: 'Keep Donating!',
            text: `You've donated ${data.items_donated} item${data.items_donated === 1 ? '' : 's'}. Donating more can help reduce waste.`,
            action: 'Continue donating items you won\'t use to help others and improve your waste reduction score.'
        });
    } else {
        insights.push({
            type: 'positive',
            icon: '🌟',
            title: 'Excellent Donation Activity!',
            text: `You've donated ${data.items_donated} items! Your generosity is helping reduce food waste.`,
            action: 'Keep up the great work! Your donations are making a positive impact.'
        });
    }
    
    // Insight 6: Monthly Trend Analysis
    if (data.recent_wasted > data.recent_saved && data.recent_wasted > 0) {
        insights.push({
            type: 'warning',
            icon: '📉',
            title: 'Waste Trend Alert',
            text: `This month, you've wasted ${data.recent_wasted} items while saving ${data.recent_saved}. Waste is outpacing savings.`,
            action: 'Focus on proactive meal planning and early donations to reverse this trend next month.'
        });
    } else if (data.recent_saved > data.recent_wasted && data.recent_saved > 0) {
        insights.push({
            type: 'positive',
            icon: '📊',
            title: 'Positive Trend This Month',
            text: `Great news! This month you saved ${data.recent_saved} items vs ${data.recent_wasted} wasted. Keep it up!`,
            action: 'Maintain this positive momentum by continuing your meal planning and donation habits.'
        });
    }
    
    // Render insights
    container.innerHTML = '';
    if (insights.length === 0) {
        container.innerHTML = '<div class="insight-card info"><div class="insight-icon">ℹ️</div><div class="insight-title">No Insights Available</div><div class="insight-text">Start using the system to receive personalized recommendations.</div></div>';
        return;
    }
    
    insights.forEach(insight => {
        const card = document.createElement('div');
        card.className = `insight-card ${insight.type}`;
        card.innerHTML = `
            <div class="insight-icon">${insight.icon}</div>
            <div class="insight-title">${insight.title}</div>
            <div class="insight-text">${insight.text}</div>
            <div class="insight-action">💡 ${insight.action}</div>
        `;
        container.appendChild(card);
    });
}
