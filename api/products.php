<?php
// api/products.php
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

if ($role === 'Farmer') {
    $farmerId = $_SESSION['user_id'];
    
    switch ($method) {
        case 'GET':
            $stmt = $pdo->prepare("SELECT * FROM Products WHERE FarmerID = ?");
            $stmt->execute([$farmerId]);
            $products = $stmt->fetchAll();
            echo json_encode($products);
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $name = sanitizeInput($data['name']);
            $description = sanitizeInput($data['description']);
            $price = (float)$data['price'];
            $stock = (int)$data['stock'];
            $imageURL = sanitizeInput($data['imageURL'] ?? '');
            
            $stmt = $pdo->prepare("INSERT INTO Products (FarmerID, Name, Description, Price, Stock, ImageURL) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$farmerId, $name, $description, $price, $stock, $imageURL])) {
                echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            } else {
                echo json_encode(['error' => 'Failed to add product']);
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            $productId = (int)$data['id'];
            $stock = (int)$data['stock'];
            
            $stmt = $pdo->prepare("UPDATE Products SET Stock = ? WHERE ProductID = ? AND FarmerID = ?");
            if ($stmt->execute([$stock, $productId, $farmerId])) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Failed to update product']);
            }
            break;
            
        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            $productId = (int)$data['id'];
            
            $stmt = $pdo->prepare("DELETE FROM Products WHERE ProductID = ? AND FarmerID = ?");
            if ($stmt->execute([$productId, $farmerId])) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Failed to delete product']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
    }
} elseif ($role === 'Consumer' && $method === 'GET') {
    // Consumers can fetch all available products
    $stmt = $pdo->query("SELECT p.*, u.Name as FarmerName FROM Products p JOIN Users u ON p.FarmerID = u.UserID WHERE p.Stock > 0");
    $products = $stmt->fetchAll();
    echo json_encode($products);
} else {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
}
?>
