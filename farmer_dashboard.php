<?php
// farmer_dashboard.php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireRole('Farmer');

$farmerId = $_SESSION['user_id'];

// Fetch farmer name
$stmt = $pdo->prepare("SELECT Name FROM Users WHERE UserID = ?");
$stmt->execute([$farmerId]);
$farmer = $stmt->fetch();

// Handle product addition
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_product') {
    $name        = sanitizeInput($_POST['name']);
    $price       = (float)$_POST['price'];
    $stock       = (int)$_POST['stock'];
    $description = sanitizeInput($_POST['description']);

    $stmt = $pdo->prepare("INSERT INTO Products (FarmerID, Name, Description, Price, Stock) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$farmerId, $name, $description, $price, $stock])) {
        $message = "<div class='alert alert-success'>✅ Product listed successfully!</div>";
    } else {
        $message = "<div class='alert alert-error'>❌ Failed to add product. Please try again.</div>";
    }
}

// Fetch farmer's products
$stmt = $pdo->prepare("SELECT * FROM Products WHERE FarmerID = ? ORDER BY ProductID DESC");
$stmt->execute([$farmerId]);
$products = $stmt->fetchAll();

// Fetch farmer's related orders
$stmt = $pdo->prepare("
    SELECT o.OrderID, o.OrderDate, o.Status, oi.Quantity, p.Name as ProductName, u.Name as ConsumerName
    FROM Orders o
    JOIN OrderItems oi ON o.OrderID = oi.OrderID
    JOIN Products p    ON oi.ProductID = p.ProductID
    JOIN Users u       ON o.ConsumerID = u.UserID
    WHERE p.FarmerID = ?
    ORDER BY o.OrderDate DESC
    LIMIT 20
");
$stmt->execute([$farmerId]);
$orders = $stmt->fetchAll();

$totalRevenue = array_reduce($products, fn($carry, $p) => $carry + ($p['Price'] * $p['Stock']), 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard – Farm to Table</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/farmer.css">
</head>
<body>

<nav class="navbar">
    <h1>🌾 Farm-to-Table</h1>
    <div class="menu-icon" onclick="this.nextElementSibling.classList.toggle('active')">☰</div>
    <div class="nav-links">
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="page-body">
    <div class="page-header">
        <div>
            <h2>Farmer Dashboard</h2>
            <div class="welcome-sub">Welcome back, <?php echo htmlspecialchars($farmer['Name']); ?>!</div>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="num"><?php echo count($products); ?></div>
            <div class="lbl">Products Listed</div>
        </div>
        <div class="stat-card">
            <div class="num"><?php echo count($orders); ?></div>
            <div class="lbl">Total Orders</div>
        </div>
        <div class="stat-card">
            <div class="num">LKR <?php echo number_format($totalRevenue, 2); ?></div>
            <div class="lbl">Stock Value</div>
        </div>
    </div>

    <?php echo $message; ?>

    <div class="consumer-layout">
        <!-- Left: Products + Orders -->
        <div>
            <!-- Products Section -->
            <div class="section">
                <div class="section-header">
                    <h3>🛒 Your Products</h3>
                    <span style="font-size:0.8rem;color:#aaa;"><?php echo count($products); ?> listed</span>
                </div>
                <div class="section-body">
                    <?php if (empty($products)): ?>
                        <div class="empty-state"><div class="icon">📦</div><p>No products listed yet. Add your first product →</p></div>
                    <?php else: ?>
                        <div class="product-grid">
                            <?php foreach ($products as $p): ?>
                                <div class="card">
                                    <h3><?php echo htmlspecialchars($p['Name']); ?></h3>
                                    <p><?php echo htmlspecialchars($p['Description'] ?: 'No description.'); ?></p>
                                    <div class="price">LKR <?php echo number_format($p['Price'], 2); ?></div>
                                    <div style="display:flex;justify-content:space-between;align-items:center;">
                                        <span class="badge badge-Consumer">Stock: <?php echo $p['Stock']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Orders Section -->
            <div class="section">
                <div class="section-header">
                    <h3>📦 Recent Orders</h3>
                </div>
                <?php if (empty($orders)): ?>
                    <div class="section-body">
                        <p class="text-muted text-center" style="padding: 16px 0;">No orders yet.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr><th>Order #</th><th>Product</th><th>Qty</th><th>Consumer</th><th>Status</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td>#<?php echo $o['OrderID']; ?></td>
                                    <td><?php echo htmlspecialchars($o['ProductName']); ?></td>
                                    <td>×<?php echo $o['Quantity']; ?></td>
                                    <td><?php echo htmlspecialchars($o['ConsumerName']); ?></td>
                                    <td>
                                        <?php
                                        $cls = strtolower(str_replace(' ', '', $o['Status']));
                                        ?>
                                        <span class="badge badge-<?php echo $cls; ?>"><?php echo $o['Status']; ?></span>
                                    </td>
                                    <td class="text-muted"><?php echo htmlspecialchars($o['OrderDate']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Add Product Panel -->
        <div>
            <div class="add-product-panel">
                <div class="panel-header">➕ Add New Product</div>
                <div class="panel-body">
                    <form method="POST" action="farmer_dashboard.php">
                        <input type="hidden" name="action" value="add_product">
                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" name="name" placeholder="e.g. Organic Tomatoes" required>
                        </div>
                        <div class="form-group">
                            <label>Price (LKR)</label>
                            <input type="number" step="0.01" name="price" placeholder="0.00" required>
                        </div>
                        <div class="form-group">
                            <label>Initial Stock (units)</label>
                            <input type="number" name="stock" placeholder="0" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="3" placeholder="Describe your product..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-full">List Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
