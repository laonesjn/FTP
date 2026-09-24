<?php
// consumer_dashboard.php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireRole('Consumer');

$consumerId = $_SESSION['user_id'];

// Fetch consumer name
$stmt = $pdo->prepare("SELECT Name FROM Users WHERE UserID = ?");
$stmt->execute([$consumerId]);
$consumer = $stmt->fetch();

// Fetch available products
$stmt = $pdo->query("
    SELECT p.*, u.Name as FarmerName
    FROM Products p
    JOIN Users u ON p.FarmerID = u.UserID
    WHERE p.Stock > 0
    ORDER BY p.Name
");
$products = $stmt->fetchAll();

// Fetch consumer's past orders
$stmt = $pdo->prepare("
    SELECT o.OrderID, o.TotalAmount, o.OrderDate, o.Status
    FROM Orders o
    WHERE o.ConsumerID = ?
    ORDER BY o.OrderDate DESC
    LIMIT 10
");
$stmt->execute([$consumerId]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumer Dashboard – Farm to Table</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/consumer.css">
</head>
<body>

<nav class="navbar">
    <h1>🛒 Farm-to-Table</h1>
    <div class="menu-icon" onclick="this.nextElementSibling.classList.toggle('active')">☰</div>
    <div class="nav-links">
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="page-body">
    <div class="page-header">
        <div>
            <h2>Fresh Produce</h2>
            <div class="welcome-sub">Hello, <?php echo htmlspecialchars($consumer['Name']); ?>! Shop directly from local farmers.</div>
        </div>
        <span id="cart-badge" style="display:none; background:var(--primary); color:#fff; border-radius:20px; padding: 5px 14px; font-size: 0.85rem; font-weight: 600;">
            🛒 <span id="cart-count">0</span> item(s)
        </span>
    </div>

    <div class="consumer-layout">
        <!-- Products -->
        <div>
            <div class="section">
                <div class="section-header">
                    <h3>🌽 Available Products</h3>
                    <span style="font-size:0.82rem; color:#999;"><?php echo count($products); ?> available</span>
                </div>
                <div class="section-body">
                    <?php if (empty($products)): ?>
                        <p class="text-muted text-center" style="padding: 24px 0;">No products available right now. Check back soon!</p>
                    <?php else: ?>
                        <div class="grid">
                            <?php foreach ($products as $p): ?>
                                <div class="card">
                                    <h3><?php echo htmlspecialchars($p['Name']); ?></h3>
                                    <p><?php echo htmlspecialchars($p['Description'] ?: 'Fresh from the farm.'); ?></p>
                                    <div style="font-size:0.8rem; color:#888; margin-bottom: 10px;">
                                        🌾 <?php echo htmlspecialchars($p['FarmerName']); ?> &nbsp;·&nbsp;
                                        📦 <?php echo $p['Stock']; ?> in stock
                                    </div>
                                    <div class="price">LKR <?php echo number_format($p['Price'], 2); ?></div>
                                    <button class="btn btn-secondary"
                                        onclick="addToCart(<?php echo $p['ProductID']; ?>, '<?php echo addslashes($p['Name']); ?>', <?php echo $p['Price']; ?>, <?php echo $p['Stock']; ?>)">
                                        Add to Cart
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Past Orders -->
            <?php if (!empty($orders)): ?>
            <div class="section mt-2">
                <div class="section-header">
                    <h3>📋 My Orders</h3>
                </div>
                <table>
                    <thead>
                        <tr><th>Order #</th><th>Amount</th><th>Status</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>#<?php echo $o['OrderID']; ?></td>
                                <td>LKR <?php echo number_format($o['TotalAmount'], 2); ?></td>
                                <td>
                                    <?php $cls = strtolower(str_replace(' ', '', $o['Status'])); ?>
                                    <span class="badge badge-<?php echo $cls; ?>"><?php echo $o['Status']; ?></span>
                                </td>
                                <td class="text-muted"><?php echo htmlspecialchars($o['OrderDate']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Cart Sidebar -->
        <div>
            <div class="cart-sidebar">
                <h3>🛒 Your Cart</h3>
                <div id="cart-items">
                    <p style="color:#aaa; font-style:italic; font-size:0.88rem;">Your cart is empty.</p>
                </div>
                <div class="cart-total">Total: LKR <span id="cart-total">0.00</span></div>
                <button id="checkout-btn" class="btn btn-full" onclick="checkout()">Checkout →</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
<script>
// Update cart badge in navbar
const origAddToCart = window.addToCart;
document.addEventListener('DOMContentLoaded', () => {
    const badge = document.getElementById('cart-badge');
    const countEl = document.getElementById('cart-count');
    // Patch cart updates to show badge
    const observer = new MutationObserver(() => {
        const items = document.querySelectorAll('.cart-item');
        if (items.length > 0) {
            badge.style.display = 'inline-block';
            countEl.textContent = items.length;
        } else {
            badge.style.display = 'none';
        }
    });
    observer.observe(document.getElementById('cart-items'), { childList: true });
});
</script>
</body>
</html>
