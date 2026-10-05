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

$message = '';

// Handle product addition, edit, delete, stock adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_product') {
        $name        = sanitizeInput($_POST['name']);
        $price       = (float)$_POST['price'];
        $stock       = (int)$_POST['stock'];
        $description = sanitizeInput($_POST['description']);
        $itemType    = isset($_POST['item_type']) && in_array($_POST['item_type'], ['Plant based', 'Animal based']) ? $_POST['item_type'] : 'Plant based';

        $stmt = $pdo->prepare("INSERT INTO Products (FarmerID, Name, Description, Price, Stock, ItemType) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$farmerId, $name, $description, $price, $stock, $itemType])) {
            $message = "<div class='alert alert-success'>✅ Product listed successfully!</div>";
        } else {
            $message = "<div class='alert alert-error'>❌ Failed to add product. Please try again.</div>";
        }
    } elseif ($action === 'edit_product') {
        $productId   = (int)$_POST['product_id'];
        $name        = sanitizeInput($_POST['name']);
        $price       = (float)$_POST['price'];
        $stock       = (int)$_POST['stock'];
        $description = sanitizeInput($_POST['description']);
        $itemType    = isset($_POST['item_type']) && in_array($_POST['item_type'], ['Plant based', 'Animal based']) ? $_POST['item_type'] : 'Plant based';

        $stmt = $pdo->prepare("UPDATE Products SET Name = ?, Description = ?, Price = ?, Stock = ?, ItemType = ? WHERE ProductID = ? AND FarmerID = ?");
        if ($stmt->execute([$name, $description, $price, $stock, $itemType, $productId, $farmerId])) {
            $message = "<div class='alert alert-success'>✅ Product updated successfully!</div>";
        } else {
            $message = "<div class='alert alert-error'>❌ Failed to update product.</div>";
        }
    } elseif ($action === 'delete_product') {
        $productId = (int)$_POST['product_id'];
        $stmt = $pdo->prepare("DELETE FROM Products WHERE ProductID = ? AND FarmerID = ?");
        if ($stmt->execute([$productId, $farmerId])) {
            $message = "<div class='alert alert-success'>✅ Product deleted successfully!</div>";
        } else {
            $message = "<div class='alert alert-error'>❌ Failed to delete product.</div>";
        }
    } elseif ($action === 'update_stock') {
        $productId = (int)$_POST['product_id'];
        $change    = (int)$_POST['change'];

        $stmt = $pdo->prepare("SELECT Stock FROM Products WHERE ProductID = ? AND FarmerID = ?");
        $stmt->execute([$productId, $farmerId]);
        $prod = $stmt->fetch();

        if ($prod) {
            $newStock = max(0, $prod['Stock'] + $change);
            $stmt = $pdo->prepare("UPDATE Products SET Stock = ? WHERE ProductID = ? AND FarmerID = ?");
            if ($stmt->execute([$newStock, $productId, $farmerId])) {
                $message = "<div class='alert alert-success'>✅ Stock updated to {$newStock}!</div>";
            }
        }
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
                            <?php foreach ($products as $p): 
                                $itemType = $p['ItemType'] ?? 'Plant based';
                                $isPlant = ($itemType === 'Plant based');
                                $badgeBg = $isPlant ? '#e8f5e9' : '#fff3e0';
                                $badgeColor = $isPlant ? '#2e7d32' : '#e65100';
                                $badgeIcon = $isPlant ? '🌱' : '🐄';
                            ?>
                                <div class="card" style="display:flex; flex-direction:column; justify-content:space-between;">
                                    <div>
                                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 6px; gap: 8px;">
                                            <h3 style="margin-bottom:0; font-size:1.02rem;"><?php echo htmlspecialchars($p['Name']); ?></h3>
                                            <span class="badge" style="background: <?php echo $badgeBg; ?>; color: <?php echo $badgeColor; ?>; font-size: 0.75rem; white-space: nowrap;">
                                                <?php echo $badgeIcon . ' ' . htmlspecialchars($itemType); ?>
                                            </span>
                                        </div>
                                        <p><?php echo htmlspecialchars($p['Description'] ?: 'No description.'); ?></p>
                                        <div class="price">LKR <?php echo number_format($p['Price'], 2); ?></div>
                                    </div>

                                    <div style="margin-top:12px;">
                                        <!-- Stock Controls (AJAX enabled - No page jump!) -->
                                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; background:#f4f8f4; padding:8px 12px; border-radius:6px; border:1px solid #e0eee0;">
                                            <span style="font-size:0.85rem; font-weight:600; color:#444;">Stock:</span>
                                            <div style="display:flex; align-items:center; gap:8px;">
                                                <button type="button" onclick="adjustStock(<?php echo $p['ProductID']; ?>, -1)" title="Decrease Stock" style="width:28px; height:28px; border-radius:50%; border:1px solid #ccc; background:#fff; cursor:pointer; font-weight:bold; font-size:1rem; line-height:1; display:flex; align-items:center; justify-content:center;">-</button>
                                                <span id="stock-val-<?php echo $p['ProductID']; ?>" style="font-weight:700; font-size:1rem; min-width:30px; text-align:center; color:#2e7d32;"><?php echo $p['Stock']; ?></span>
                                                <button type="button" onclick="adjustStock(<?php echo $p['ProductID']; ?>, 1)" title="Increase Stock" style="width:28px; height:28px; border-radius:50%; border:1px solid #2e7d32; background:#e8f5e9; color:#2e7d32; cursor:pointer; font-weight:bold; font-size:1rem; line-height:1; display:flex; align-items:center; justify-content:center;">+</button>
                                            </div>
                                        </div>

                                        <!-- Edit / Delete Action buttons -->
                                        <div style="display:flex; gap:8px;">
                                            <button type="button" class="btn btn-outline" style="flex:1; padding:7px; font-size:0.82rem;"
                                                onclick="openEditModal(<?php echo $p['ProductID']; ?>, '<?php echo addslashes(htmlspecialchars($p['Name'])); ?>', <?php echo $p['Price']; ?>, <?php echo $p['Stock']; ?>, '<?php echo addslashes(htmlspecialchars($p['Description'] ?: '')); ?>', '<?php echo addslashes($itemType); ?>')">
                                                ✏️ Edit
                                            </button>
                                            <form method="POST" style="flex:1; margin:0;" onsubmit="return confirm('Are you sure you want to delete <?php echo addslashes(htmlspecialchars($p['Name'])); ?>?');">
                                                <input type="hidden" name="action" value="delete_product">
                                                <input type="hidden" name="product_id" value="<?php echo $p['ProductID']; ?>">
                                                <button type="submit" class="btn btn-danger" style="width:100%; padding:7px; font-size:0.82rem;">
                                                    🗑️ Delete
                                                </button>
                                            </form>
                                        </div>
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
                            <label for="item_type">Item Type</label>
                            <select name="item_type" id="item_type" required>
                                <option value="Plant based">🌱 Plant based</option>
                                <option value="Animal based">🐄 Animal based</option>
                            </select>
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

<!-- Edit Product Modal -->
<div id="edit-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; border-radius:10px; max-width:480px; width:100%; padding:24px; box-shadow:0 4px 20px rgba(0,0,0,0.2); position:relative;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="margin:0; font-size:1.1rem; color:var(--primary);">✏️ Edit Product</h3>
            <button onclick="closeEditModal()" style="background:none; border:none; font-size:1.4rem; cursor:pointer; color:#888;">&times;</button>
        </div>
        <form method="POST" action="farmer_dashboard.php">
            <input type="hidden" name="action" value="edit_product">
            <input type="hidden" name="product_id" id="edit-product-id">
            <div class="form-group">
                <label>Product Name</label>
                <input type="text" name="name" id="edit-name" required>
            </div>
            <div class="form-group">
                <label for="edit-item-type">Item Type</label>
                <select name="item_type" id="edit-item-type" required>
                    <option value="Plant based">🌱 Plant based</option>
                    <option value="Animal based">🐄 Animal based</option>
                </select>
            </div>
            <div class="form-group">
                <label>Price (LKR)</label>
                <input type="number" step="0.01" name="price" id="edit-price" required>
            </div>
            <div class="form-group">
                <label>Stock (units)</label>
                <input type="number" name="stock" id="edit-stock" min="0" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="edit-description" rows="3"></textarea>
            </div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="btn btn-full">Save Changes</button>
                <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/app.js"></script>
<script>
// Stock adjustment via async API call (No page jump / reload!)
async function adjustStock(productId, delta) {
    const stockEl = document.getElementById('stock-val-' + productId);
    if (!stockEl) return;

    let currentStock = parseInt(stockEl.textContent, 10) || 0;
    let newStock = Math.max(0, currentStock + delta);
    if (newStock === currentStock) return;

    // Immediate UI update
    stockEl.textContent = newStock;

    try {
        const response = await fetch('api/products.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: productId, stock: newStock })
        });
        const result = await response.json();
        if (!result.success) {
            // Rollback on failure
            stockEl.textContent = currentStock;
            alert('Failed to update stock: ' + (result.error || 'Server error'));
        }
    } catch (err) {
        stockEl.textContent = currentStock;
        console.error('Error updating stock:', err);
    }
}

// Preserve scroll position across page reloads for form submissions
window.addEventListener('beforeunload', () => {
    sessionStorage.setItem('farmer_scroll', window.scrollY);
});

document.addEventListener('DOMContentLoaded', () => {
    const scrollPos = sessionStorage.getItem('farmer_scroll');
    if (scrollPos) {
        window.scrollTo(0, parseInt(scrollPos, 10));
        sessionStorage.removeItem('farmer_scroll');
    }
});

function openEditModal(id, name, price, stock, description, itemType) {
    document.getElementById('edit-product-id').value = id;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-price').value = price;
    document.getElementById('edit-stock').value = stock;
    document.getElementById('edit-description').value = description;
    document.getElementById('edit-item-type').value = itemType || 'Plant based';
    
    document.getElementById('edit-modal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
}
</script>
</body>
</html>
