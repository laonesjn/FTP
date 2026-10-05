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
                    <span id="available-count" style="font-size:0.82rem; color:#999;"><?php echo count($products); ?> available</span>
                </div>
                
                <!-- Search & Filter Controls -->
                <div class="filter-search-bar" style="padding: 14px 20px; background: #f8faf8; border-bottom: 1px solid #e8e8e8; display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                    <div style="flex: 1; min-width: 220px; position: relative;">
                        <input type="text" id="product-search" placeholder="Search by product name, description or farmer..." 
                            oninput="filterProducts()" 
                            style="width: 100%; padding: 9px 12px 9px 34px; border: 1px solid #ccc; border-radius: 6px; font-size: 0.9rem; font-family: var(--font);">
                        <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #888; font-size: 0.9rem;">🔍</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <label for="item-type-filter" style="font-weight: 600; font-size: 0.88rem; color: #444; white-space: nowrap;">Item Type:</label>
                        <select id="item-type-filter" onchange="filterProducts()" style="padding: 9px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 0.9rem; background: #fff; font-family: var(--font); cursor: pointer;">
                            <option value="all">All Items</option>
                            <option value="Plant based">🌱 Plant based</option>
                            <option value="Animal based">🐄 Animal based</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-pills" style="padding: 12px 20px 4px 20px; display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" class="type-pill active" onclick="selectTypeFilter('all', this)" style="padding: 6px 14px; border-radius: 18px; border: 1px solid #2e7d32; background: #2e7d32; color: #fff; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">All Items</button>
                    <button type="button" class="type-pill" onclick="selectTypeFilter('Plant based', this)" style="padding: 6px 14px; border-radius: 18px; border: 1px solid #ccc; background: #fff; color: #444; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">🌱 Plant based</button>
                    <button type="button" class="type-pill" onclick="selectTypeFilter('Animal based', this)" style="padding: 6px 14px; border-radius: 18px; border: 1px solid #ccc; background: #fff; color: #444; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s;">🐄 Animal based</button>
                </div>

                <div class="section-body">
                    <?php if (empty($products)): ?>
                        <p class="text-muted text-center" style="padding: 24px 0;">No products available right now. Check back soon!</p>
                    <?php else: ?>
                        <div class="grid" id="products-grid">
                            <?php foreach ($products as $p): 
                                $itemType = $p['ItemType'] ?? 'Plant based';
                                $isPlant = ($itemType === 'Plant based');
                                $badgeBg = $isPlant ? '#e8f5e9' : '#fff3e0';
                                $badgeColor = $isPlant ? '#2e7d32' : '#e65100';
                                $badgeIcon = $isPlant ? '🌱' : '🐄';
                            ?>
                                <div class="card product-card-item"
                                     data-name="<?php echo htmlspecialchars(strtolower($p['Name'])); ?>"
                                     data-farmer="<?php echo htmlspecialchars(strtolower($p['FarmerName'])); ?>"
                                     data-desc="<?php echo htmlspecialchars(strtolower($p['Description'] ?: '')); ?>"
                                     data-type="<?php echo htmlspecialchars($itemType); ?>">
                                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 6px; gap: 8px;">
                                        <h3 style="margin-bottom:0; font-size:1.02rem;"><?php echo htmlspecialchars($p['Name']); ?></h3>
                                        <span class="badge" style="background: <?php echo $badgeBg; ?>; color: <?php echo $badgeColor; ?>; font-size: 0.75rem; white-space: nowrap; flex-shrink: 0;">
                                            <?php echo $badgeIcon . ' ' . htmlspecialchars($itemType); ?>
                                        </span>
                                    </div>
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
                        <p id="no-products-msg" class="text-muted text-center" style="display: none; padding: 24px 0;">No products match your search or filter criteria.</p>
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
// Filter products based on search input and type filter dropdown
function selectTypeFilter(typeVal, btnEl) {
    document.getElementById('item-type-filter').value = typeVal;
    document.querySelectorAll('.type-pill').forEach(btn => {
        btn.style.background = '#fff';
        btn.style.color = '#444';
        btn.style.borderColor = '#ccc';
        btn.classList.remove('active');
    });
    btnEl.style.background = '#2e7d32';
    btnEl.style.color = '#fff';
    btnEl.style.borderColor = '#2e7d32';
    btnEl.classList.add('active');
    filterProducts();
}

function filterProducts() {
    const searchVal = (document.getElementById('product-search').value || '').toLowerCase().trim();
    const typeVal = document.getElementById('item-type-filter').value;

    const cards = document.querySelectorAll('.product-card-item');
    let visibleCount = 0;

    cards.forEach(card => {
        const name = card.dataset.name || '';
        const farmer = card.dataset.farmer || '';
        const desc = card.dataset.desc || '';
        const type = card.dataset.type || '';

        const matchesSearch = !searchVal || name.includes(searchVal) || farmer.includes(searchVal) || desc.includes(searchVal);
        const matchesType = (typeVal === 'all') || (type.toLowerCase() === typeVal.toLowerCase());

        if (matchesSearch && matchesType) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    const noResultsEl = document.getElementById('no-products-msg');
    if (noResultsEl) {
        noResultsEl.style.display = (visibleCount === 0 && cards.length > 0) ? 'block' : 'none';
    }

    const countEl = document.getElementById('available-count');
    if (countEl) {
        countEl.textContent = visibleCount + ' available';
    }

    // Sync pills with select box
    document.querySelectorAll('.type-pill').forEach(btn => {
        const onclickAttr = btn.getAttribute('onclick') || '';
        if (onclickAttr.includes("'" + typeVal + "'")) {
            btn.style.background = '#2e7d32';
            btn.style.color = '#fff';
            btn.style.borderColor = '#2e7d32';
            btn.classList.add('active');
        } else {
            btn.style.background = '#fff';
            btn.style.color = '#444';
            btn.style.borderColor = '#ccc';
            btn.classList.remove('active');
        }
    });
}

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
