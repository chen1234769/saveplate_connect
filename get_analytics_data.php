<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/database.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

function fetchScalar($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $value = 0;
    if ($result && $row = $result->fetch_row()) {
        $value = (float)$row[0];
    }
    $stmt->close();
    return $value;
}

// Items currently in donation table (still pending pickup)
$stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) FROM donation WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$donatedItems = fetchScalar($stmt);

// Meals planned count
$stmt = $conn->prepare("SELECT COUNT(*) FROM meal WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$mealsPlanned = fetchScalar($stmt);

// Quantity reserved for meals
$stmt = $conn->prepare("SELECT COALESCE(SUM(r.reserved_quantity),0)
    FROM meal_reservations r
    JOIN meal m ON r.meal_id = m.id
    WHERE m.user_id = ?");
$stmt->bind_param('i', $user_id);
$mealSavedQty = fetchScalar($stmt);

$totalSavedQty = $donatedItems + $mealSavedQty;

// Expired quantity
$stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) FROM inventory WHERE user_id = ? AND status = 'expired'");
$stmt->bind_param('i', $user_id);
$expiredQty = fetchScalar($stmt);

// Expiring soon count (for insights)
$stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) FROM inventory WHERE user_id = ? AND status = 'expiring_soon'");
$stmt->bind_param('i', $user_id);
$expiringSoonQty = fetchScalar($stmt);

// Total inventory items (for insights)
$stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) FROM inventory WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$totalInventoryQty = fetchScalar($stmt);

$riskTotal = $totalSavedQty + $expiredQty;
$wasteReduction = $riskTotal > 0 ? round(($totalSavedQty / $riskTotal) * 100) : 0;

// Category distribution (current inventory)
$categoryData = [];
$stmt = $conn->prepare("SELECT category, COALESCE(SUM(quantity),0) as total
    FROM inventory WHERE user_id = ?
    GROUP BY category ORDER BY total DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $categoryData[] = [
        'label' => ucfirst(str_replace('_', ' ', $row['category'])),
        'value' => (int)$row['total']
    ];
}
$stmt->close();

// Get period parameter (default: 12months)
$period = isset($_GET['period']) ? $_GET['period'] : '12months';

$trendLabels = [];
$trendSaved = [];
$trendWasted = [];

if ($period === '1week') {
    // Daily trend for last 7 days
    for ($i = 6; $i >= 0; $i--) {
        $date = new DateTime("-$i days");
        $date->setTime(0, 0, 0);
        $nextDate = clone $date;
        $nextDate->modify('+1 day');
        
        $trendLabels[] = $date->format('M d');
        
        $startDate = $date->format('Y-m-d 00:00:00');
        $endDate = $nextDate->format('Y-m-d 00:00:00');
        $startDay = $date->format('Y-m-d');
        $endDay = $date->format('Y-m-d');
        
        // Donations initiated during this day
        $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) FROM donation WHERE user_id = ? AND listed_at >= ? AND listed_at < ?");
        $stmt->bind_param('iss', $user_id, $startDate, $endDate);
        $saved = fetchScalar($stmt);
        
        // Items that expired on this day
        $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) FROM inventory WHERE user_id = ? AND status = 'expired' AND expiry_date = ?");
        $stmt->bind_param('is', $user_id, $startDay);
        $wasted = fetchScalar($stmt);
        
        $trendSaved[] = $saved;
        $trendWasted[] = $wasted;
    }
} elseif ($period === '6months') {
    // Monthly trend for last 6 months
    for ($i = 5; $i >= 0; $i--) {
        $start = new DateTime("first day of -$i month");
        $end = clone $start;
        $end->modify('last day of this month');
        
        $trendLabels[] = $start->format('M Y');
        
        $startDate = $start->format('Y-m-d 00:00:00');
        $endDate = $end->format('Y-m-d 23:59:59');
        $startDay = $start->format('Y-m-d');
        $endDay = $end->format('Y-m-d');
        
        // Donations initiated during this month
        $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) FROM donation WHERE user_id = ? AND listed_at BETWEEN ? AND ?");
        $stmt->bind_param('iss', $user_id, $startDate, $endDate);
        $saved = fetchScalar($stmt);
        
        // Items that expired in this month
        $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) FROM inventory WHERE user_id = ? AND status = 'expired' AND expiry_date BETWEEN ? AND ?");
        $stmt->bind_param('iss', $user_id, $startDay, $endDay);
        $wasted = fetchScalar($stmt);
        
        $trendSaved[] = $saved;
        $trendWasted[] = $wasted;
    }
} else {
    // Monthly trend for last 12 months (default)
    for ($i = 11; $i >= 0; $i--) {
        $start = new DateTime("first day of -$i month");
        $end = clone $start;
        $end->modify('last day of this month');
        
        $trendLabels[] = $start->format('M Y');
        
        $startDate = $start->format('Y-m-d 00:00:00');
        $endDate = $end->format('Y-m-d 23:59:59');
        $startDay = $start->format('Y-m-d');
        $endDay = $end->format('Y-m-d');
        
        // Donations initiated during this month
        $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) FROM donation WHERE user_id = ? AND listed_at BETWEEN ? AND ?");
        $stmt->bind_param('iss', $user_id, $startDate, $endDate);
        $saved = fetchScalar($stmt);
        
        // Items that expired in this month
        $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) FROM inventory WHERE user_id = ? AND status = 'expired' AND expiry_date BETWEEN ? AND ?");
        $stmt->bind_param('iss', $user_id, $startDay, $endDay);
        $wasted = fetchScalar($stmt);
        
        $trendSaved[] = $saved;
        $trendWasted[] = $wasted;
    }
}

echo json_encode([
    'cards' => [
        'food_saved' => (int)$totalSavedQty,
        'waste_reduction' => $wasteReduction,
        'items_donated' => (int)$donatedItems,
        'meals_planned' => (int)$mealsPlanned
    ],
    'category_distribution' => $categoryData,
    'monthly_trend' => [
        'labels' => $trendLabels,
        'saved' => $trendSaved,
        'wasted' => $trendWasted
    ],
    'insights_data' => [
        'expired_qty' => (int)$expiredQty,
        'expiring_soon_qty' => (int)$expiringSoonQty,
        'total_inventory_qty' => (int)$totalInventoryQty,
        'total_saved_qty' => (int)$totalSavedQty,
        'waste_reduction' => $wasteReduction,
        'items_donated' => (int)$donatedItems,
        'meals_planned' => (int)$mealsPlanned,
        'recent_saved' => end($trendSaved) || 0,
        'recent_wasted' => end($trendWasted) || 0
    ]
]);

$conn->close();

