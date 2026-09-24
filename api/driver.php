<?php
// api/driver.php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn() || getUserRole() !== 'Driver') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$driverId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get assigned deliveries including farmer + consumer location
    $stmt = $pdo->prepare("
        SELECT
            d.DeliveryID, d.PickupTime, d.DeliveryTime,
            o.OrderID, o.Status, o.TotalAmount,
            farmer.Name        AS FarmerName,
            farmer.Address     AS FarmerAddress,
            farmer.Coordinates AS FarmerCoordinates,
            consumer.Name      AS ConsumerName,
            consumer.Address   AS ConsumerAddress,
            consumer.Coordinates AS ConsumerCoordinates
        FROM Deliveries d
        JOIN Orders o       ON d.OrderID = o.OrderID
        JOIN OrderItems oi  ON oi.OrderID = o.OrderID
        JOIN Products p     ON p.ProductID = oi.ProductID
        JOIN Users farmer   ON farmer.UserID = p.FarmerID
        JOIN Users consumer ON consumer.UserID = o.ConsumerID
        WHERE d.DriverID = ?
        GROUP BY d.DeliveryID
        ORDER BY o.OrderDate DESC
    ");
    $stmt->execute([$driverId]);
    echo json_encode($stmt->fetchAll());
} elseif ($method === 'PUT') {
    // Update delivery status
    $data = json_decode(file_get_contents('php://input'), true);
    $deliveryId = (int)$data['deliveryId'];
    $orderId    = (int)$data['orderId'];
    $status     = sanitizeInput($data['status']);

    // Whitelist allowed status transitions
    $allowedStatuses = ['In Transit', 'Delivered'];
    if (!in_array($status, $allowedStatuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status value']);
        exit;
    }
    
    // Verify driver owns this delivery
    $stmt = $pdo->prepare("SELECT 1 FROM Deliveries WHERE DeliveryID = ? AND DriverID = ?");
    $stmt->execute([$deliveryId, $driverId]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Not assigned to this delivery']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE Orders SET Status = ? WHERE OrderID = ?");
        $stmt->execute([$status, $orderId]);
        
        if ($status === 'In Transit') {
            $stmt = $pdo->prepare("UPDATE Deliveries SET PickupTime = NOW() WHERE DeliveryID = ?");
            $stmt->execute([$deliveryId]);
        } elseif ($status === 'Delivered') {
            $stmt = $pdo->prepare("UPDATE Deliveries SET DeliveryTime = NOW() WHERE DeliveryID = ?");
            $stmt->execute([$deliveryId]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['error' => 'Failed to update status']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}
?>
