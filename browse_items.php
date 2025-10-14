<?php
header('Content-Type: application/json');
require_once __DIR__ . '/database.php';

// Read inputs
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$list    = isset($_GET['list']) ? strtolower(trim($_GET['list'])) : 'full';
$from    = isset($_GET['from']) ? trim($_GET['from']) : '';
$to      = isset($_GET['to']) ? trim($_GET['to']) : '';
$storage = isset($_GET['storage']) ? trim($_GET['storage']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// Fallback user_id during early development
if ($user_id === 0) {
    $user_id = 1;
}

// We support two tables: `inventory` (all user items not yet donated)
// and `donation` (items already listed to donate).
// We normalize columns via aliases and include a literal `item_type`.

// Helper to build WHERE fragment and params for common filters
function build_where($alias, $from, $to, $storage, $category) {
    $where = '';
    $params = [];
    $types = '';
    if ($from !== '') { $where .= " AND {$alias}.expiry_date >= ?"; $params[] = $from; $types .= 's'; }
    if ($to !== '')   { $where .= " AND {$alias}.expiry_date <= ?"; $params[] = $to;   $types .= 's'; }
    if ($storage !== '') { $where .= " AND {$alias}.storage_type = ?"; $params[] = $storage; $types .= 's'; }
    if ($category !== '') { $where .= " AND {$alias}.category = ?"; $params[] = $category; $types .= 's'; }
    return [$where, $params, $types];
}

$args = [];
$types = '';

if ($list === 'inventory') {
    list($extraWhere, $extraParams, $extraTypes) = build_where('i', $from, $to, $storage, $category);
    $sql = "SELECT i.id, i.user_id, i.name, 'inventory' AS item_type, i.quantity, i.expiry_date, i.storage_type, i.category, i.status, '-' AS pickup_location, '-' AS contact_method, i.remarks
            FROM inventory i WHERE i.user_id = ?{$extraWhere}";
    $args = array_merge([$user_id], $extraParams);
    $types = 'i' . $extraTypes;
} elseif ($list === 'donation') {
    list($extraWhere, $extraParams, $extraTypes) = build_where('d', $from, $to, $storage, $category);
    $sql = "SELECT d.id, d.user_id, d.name, 'donation' AS item_type, d.quantity, d.expiry_date, d.storage_type, d.category, d.status, d.pickup_location, d.contact_method, d.remarks
            FROM donation d WHERE d.user_id = ?{$extraWhere}";
    $args = array_merge([$user_id], $extraParams);
    $types = 'i' . $extraTypes;
} else { // full
    list($wInv, $pInv, $tInv) = build_where('i', $from, $to, $storage, $category);
    list($wDon, $pDon, $tDon) = build_where('d', $from, $to, $storage, $category);
    $sql = "(
                SELECT i.id, i.user_id, i.name, 'inventory' AS item_type, i.quantity, i.expiry_date, i.storage_type, i.category, i.status, '-' AS pickup_location, '-' AS contact_method, i.remarks
                FROM inventory i WHERE i.user_id = ?{$wInv}
            )
            UNION ALL
            (
                SELECT d.id, d.user_id, d.name, 'donation' AS item_type, d.quantity, d.expiry_date, d.storage_type, d.category, d.status, d.pickup_location, d.contact_method, d.remarks
                FROM donation d WHERE d.user_id = ?{$wDon}
            )
            ORDER BY COALESCE(expiry_date, '9999-12-31') ASC, name ASC";
    $args = array_merge([$user_id], $pInv, [$user_id], $pDon);
    $types = 'i' . $tInv . 'i' . $tDon;
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => $conn->error]);
    exit;
}

if ($types !== '') {
    $stmt->bind_param($types, ...$args);
}

$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode($items);
?>


