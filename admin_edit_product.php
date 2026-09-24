<?php
// admin_edit_product.php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$id = (int)$_GET['id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $imageURL = sanitizeInput($_POST['image_url']);

    if (empty($name) || $price <= 0 || $stock < 0) {
        $error = "Valid Name, Price, and Stock are required.";
    } else {
        $stmt = $pdo->prepare("UPDATE Products SET Name=?, Description=?, Price=?, Stock=?, ImageURL=? WHERE ProductID=?");
        if ($stmt->execute([$name, $description, $price, $stock, $imageURL, $id])) {
            $message = "Product updated successfully!";
        } else {
            $error = "Failed to update product.";
        }
    }
}

// Fetch current data
$stmt = $pdo->prepare("SELECT * FROM Products WHERE ProductID = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: admin_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold;}
        .form-group input, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .message { background-color: #dff0d8; padding: 10px; border: 1px solid #d0e9c6; color: #3c763d; margin-bottom: 20px;}
        .error { background-color: #f2dede; padding: 10px; border: 1px solid #ebccd1; color: #a94442; margin-bottom: 20px;}
        .back-link { display: inline-block; margin-bottom: 20px; color: #0066cc; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 600px;">
        <a href="admin_dashboard.php" class="back-link">&larr; Back to Dashboard</a>
        <h2>Edit Product: <?php echo htmlspecialchars($product['Name']); ?></h2>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Product Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($product['Name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"><?php echo htmlspecialchars($product['Description'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Price (LKR)</label>
                <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['Price']); ?>" required>
            </div>
            <div class="form-group">
                <label>Stock</label>
                <input type="number" name="stock" value="<?php echo htmlspecialchars($product['Stock']); ?>" required>
            </div>
            <div class="form-group">
                <label>Image URL</label>
                <input type="text" name="image_url" value="<?php echo htmlspecialchars($product['ImageURL'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn">Update Product</button>
        </form>
    </div>
</body>
</html>
