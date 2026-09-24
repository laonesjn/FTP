<?php
// api/orders.php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$role = getUserRole();
$userId = $_SESSION['user_id'];

if ($role === 'Consumer' && $method === 'POST') {
    // Process checkout
    $data = json_decode(file_get_contents('php://input'), true);
    $cart = $data['cart']; // Array of {id, quantity, price}
    $totalAmount = 0;
    
    try {
        $pdo->beginTransaction();
        
        // 1. Verify stock and calculate total
        foreach ($cart as $item) {
            $stmt = $pdo->prepare("SELECT Stock FROM Products WHERE ProductID = ? FOR UPDATE");
            $stmt->execute([$item['id']]);
            $product = $stmt->fetch();
            
            if (!$product || $product['Stock'] < $item['quantity']) {
                throw new Exception("Insufficient stock for product ID: " . $item['id']);
            }
            $totalAmount += $item['price'] * $item['quantity'];
        }
        
        // 2. Create Order
        $stmt = $pdo->prepare("INSERT INTO Orders (ConsumerID, TotalAmount) VALUES (?, ?)");
        $stmt->execute([$userId, $totalAmount]);
        $orderId = $pdo->lastInsertId();
        
        // 3. Create OrderItems and deduct stock
        foreach ($cart as $item) {
            $stmt = $pdo->prepare("INSERT INTO OrderItems (OrderID, ProductID, Quantity, PriceAtTime) VALUES (?, ?, ?, ?)");
            $stmt->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);
            
            $stmt = $pdo->prepare("UPDATE Products SET Stock = Stock - ? WHERE ProductID = ?");
            $stmt->execute([$item['quantity'], $item['id']]);
        }
        
        // 4. Assign to a random driver for v1.0
        $stmt = $pdo->query("SELECT UserID FROM Users WHERE Role = 'Driver' ORDER BY RAND() LIMIT 1");
        $driver = $stmt->fetch();
        if ($driver) {
            $stmt = $pdo->prepare("INSERT INTO Deliveries (OrderID, DriverID) VALUES (?, ?)");
            $stmt->execute([$orderId, $driver['UserID']]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'order_id' => $orderId]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($role === 'Farmer' && $method === 'GET') {
    // Get orders for farmer's products
    $stmt = $pdo->prepare("
        SELECT o.OrderID, o.OrderDate, o.Status, oi.Quantity, p.Name as ProductName, u.Name as ConsumerName
        FROM Orders o
        JOIN OrderItems oi ON o.OrderID = oi.OrderID
        JOIN Products p ON oi.ProductID = p.ProductID
        JOIN Users u ON o.ConsumerID = u.UserID
        WHERE p.FarmerID = ?
        ORDER BY o.OrderDate DESC
    ");
    $stmt->execute([$userId]);
    echo json_encode($stmt->fetchAll());
} elseif ($role === 'Consumer' && $method === 'GET') {
    // Get consumer's past orders
    $stmt = $pdo->prepare("SELECT * FROM Orders WHERE ConsumerID = ? ORDER BY OrderDate DESC");
    $stmt->execute([$userId]);
    echo json_encode($stmt->fetchAll());
} else {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
}
?>
