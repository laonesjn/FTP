<?php
// api/admin.php - Real-time data endpoint for admin dashboard
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$section      = $_GET['section']      ?? 'all';
$orderFilter  = $_GET['order_filter'] ?? 'all'; // 'day', 'week', 'all'

$response = [];

if ($section === 'all' || $section === 'users') {
    $stmt = $pdo->query("SELECT UserID, Name, Email, Role, Address FROM Users ORDER BY Role, Name");
    $response['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($section === 'all' || $section === 'products') {
    $stmt = $pdo->query("
        SELECT p.ProductID, p.Name AS ProductName, p.Price, p.Stock, u.Name AS FarmerName
        FROM Products p
        JOIN Users u ON p.FarmerID = u.UserID
        ORDER BY p.Name
    ");
    $response['products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($section === 'all' || $section === 'orders') {
    // Build date filter clause
    $dateClause = '';
    $dateParams = [];
    if ($orderFilter === 'day') {
        $dateClause = 'WHERE DATE(o.OrderDate) = CURDATE()';
    } elseif ($orderFilter === 'week') {
        $dateClause = 'WHERE o.OrderDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
    }

    $stmt = $pdo->prepare("
        SELECT o.OrderID, o.TotalAmount, o.OrderDate, o.Status, u.Name AS ConsumerName
        FROM Orders o
        JOIN Users u ON o.ConsumerID = u.UserID
        $dateClause
        ORDER BY o.OrderDate DESC
    ");
    $stmt->execute($dateParams);
    $response['orders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['order_filter'] = $orderFilter;
}

if ($section === 'all' || $section === 'deliveries') {
    $stmt = $pdo->query("
        SELECT d.DeliveryID, d.OrderID, d.PickupTime, d.DeliveryTime, u.Name AS DriverName
        FROM Deliveries d
        JOIN Users u ON d.DriverID = u.UserID
        ORDER BY d.PickupTime DESC
    ");
    $response['deliveries'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Stats summary
$response['stats'] = [
    'total_users'      => $pdo->query("SELECT COUNT(*) FROM Users")->fetchColumn(),
    'total_products'   => $pdo->query("SELECT COUNT(*) FROM Products")->fetchColumn(),
    'total_orders'     => $pdo->query("SELECT COUNT(*) FROM Orders")->fetchColumn(),
    'active_deliveries'=> $pdo->query("SELECT COUNT(*) FROM Orders WHERE Status = 'In Transit'")->fetchColumn(),
    'delivered'        => $pdo->query("SELECT COUNT(*) FROM Orders WHERE Status = 'Delivered'")->fetchColumn(),
];

echo json_encode($response);
