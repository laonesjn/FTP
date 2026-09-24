<?php
// admin_dashboard.php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// Handle Delete Actions (still server-side for security)
$message = '';
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'delete_user' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("DELETE FROM Users WHERE UserID = ?");
        if ($stmt->execute([$id])) $message = "User deleted successfully.";
    } elseif ($_GET['action'] == 'delete_product' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("DELETE FROM Products WHERE ProductID = ?");
        if ($stmt->execute([$id])) $message = "Product deleted successfully.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – Farm to Table</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">

</head>
<body>

<div class="admin-header">
    <h1>🌿 Admin Control Panel</h1>
    <a href="logout.php" class="btn-logout">Logout</a>
</div>

<div class="admin-body">

    <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Live Indicator -->
    <div class="live-bar" id="live-bar">
        <div class="pulse" id="pulse-dot"></div>
        <span id="live-status">Connecting...</span>
        <span style="margin-left:auto; color:#aaa;" id="last-updated"></span>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card"><div class="num" id="stat-users">—</div><div class="label">Total Users</div></div>
        <div class="stat-card"><div class="num" id="stat-products">—</div><div class="label">Products</div></div>
        <div class="stat-card"><div class="num" id="stat-orders">—</div><div class="label">Total Orders</div></div>
        <div class="stat-card"><div class="num" id="stat-transit" style="color:#7b1fa2;">—</div><div class="label">In Transit</div></div>
        <div class="stat-card"><div class="num" id="stat-delivered">—</div><div class="label">Delivered</div></div>
    </div>

    <!-- Users Table -->
    <div class="section">
        <div class="section-header">
            <h2>👤 Users</h2>
            <span class="count-badge" id="users-count">—</span>
        </div>
        <table>
            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead>
            <tbody id="users-body"><tr class="loading-row"><td colspan="5">Loading...</td></tr></tbody>
        </table>
    </div>

    <!-- Products Table -->
    <div class="section">
        <div class="section-header">
            <h2>🛒 Products</h2>
            <span class="count-badge" id="products-count">—</span>
        </div>
        <table>
            <thead><tr><th>ID</th><th>Product</th><th>Price</th><th>Stock</th><th>Farmer</th><th>Actions</th></tr></thead>
            <tbody id="products-body"><tr class="loading-row"><td colspan="6">Loading...</td></tr></tbody>
        </table>
    </div>

    <!-- Orders Table -->
    <div class="section">
        <div class="section-header">
            <h2>📦 Orders / Transactions</h2>
            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <div class="filter-group">
                    <button class="filter-btn active" id="filter-all" onclick="setOrderFilter('all')">All Time</button>
                    <button class="filter-btn" id="filter-week" onclick="setOrderFilter('week')">This Week</button>
                    <button class="filter-btn" id="filter-day" onclick="setOrderFilter('day')">Today</button>
                </div>
                <span class="count-badge" id="orders-count">—</span>
            </div>
        </div>
        <table>
            <thead><tr><th>Order ID</th><th>Consumer</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
            <tbody id="orders-body"><tr class="loading-row"><td colspan="5">Loading...</td></tr></tbody>
        </table>
    </div>

    <!-- Deliveries Table -->
    <div class="section">
        <div class="section-header">
            <h2>🚚 Deliveries</h2>
            <span class="count-badge" id="deliveries-count">—</span>
        </div>
        <table>
            <thead><tr><th>ID</th><th>Order ID</th><th>Driver</th><th>Pickup Time</th><th>Delivery Time</th></tr></thead>
            <tbody id="deliveries-body"><tr class="loading-row"><td colspan="5">Loading...</td></tr></tbody>
        </table>
    </div>

</div>

<script>
const POLL_INTERVAL = 5000; // 5 seconds
let previousData = {};
let activeOrderFilter = 'all'; // 'all' | 'week' | 'day'

function setOrderFilter(filter) {
    activeOrderFilter = filter;
    // Update button active states
    ['all', 'week', 'day'].forEach(f => {
        document.getElementById('filter-' + f).classList.toggle('active', f === filter);
    });
    // Fetch immediately with new filter
    fetchData();
}

function escHtml(str) {
    if (str === null || str === undefined) return '—';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function statusBadge(status) {
    const cls = (status || '').replace(' ', '');
    return `<span class="status-badge status-${cls}">${escHtml(status)}</span>`;
}

function roleBadge(role) {
    return `<span class="role-badge role-${escHtml(role)}">${escHtml(role)}</span>`;
}

function buildUsers(users) {
    if (!users || !users.length) return '<tr><td colspan="5" style="text-align:center;color:#aaa;">No users found.</td></tr>';
    return users.map(u => `
        <tr>
            <td>${escHtml(u.UserID)}</td>
            <td>${escHtml(u.Name)}</td>
            <td>${escHtml(u.Email)}</td>
            <td>${roleBadge(u.Role)}</td>
            <td class="action-links">
                <a class="edit-link" href="admin_edit_user.php?id=${u.UserID}">✏️ Edit</a>
                <a class="delete-link" href="admin_dashboard.php?action=delete_user&id=${u.UserID}"
                   onclick="return confirm('Delete ${escHtml(u.Name)}?')">🗑️ Delete</a>
            </td>
        </tr>`).join('');
}

function buildProducts(products) {
    if (!products || !products.length) return '<tr><td colspan="6" style="text-align:center;color:#aaa;">No products found.</td></tr>';
    return products.map(p => `
        <tr>
            <td>${escHtml(p.ProductID)}</td>
            <td>${escHtml(p.ProductName)}</td>
            <td>LKR ${parseFloat(p.Price).toFixed(2)}</td>
            <td>${escHtml(p.Stock)}</td>
            <td>${escHtml(p.FarmerName)}</td>
            <td class="action-links">
                <a class="edit-link" href="admin_edit_product.php?id=${p.ProductID}">✏️ Edit</a>
                <a class="delete-link" href="admin_dashboard.php?action=delete_product&id=${p.ProductID}"
                   onclick="return confirm('Delete ${escHtml(p.ProductName)}?')">🗑️ Delete</a>
            </td>
        </tr>`).join('');
}

function buildOrders(orders) {
    if (!orders || !orders.length) return '<tr><td colspan="5" style="text-align:center;color:#aaa;">No orders found.</td></tr>';
    return orders.map(o => `
        <tr>
            <td>#${escHtml(o.OrderID)}</td>
            <td>${escHtml(o.ConsumerName)}</td>
            <td>LKR ${parseFloat(o.TotalAmount).toFixed(2)}</td>
            <td>${statusBadge(o.Status)}</td>
            <td>${escHtml(o.OrderDate)}</td>
        </tr>`).join('');
}

function buildDeliveries(deliveries) {
    if (!deliveries || !deliveries.length) return '<tr><td colspan="5" style="text-align:center;color:#aaa;">No deliveries found.</td></tr>';
    return deliveries.map(d => `
        <tr>
            <td>${escHtml(d.DeliveryID)}</td>
            <td>#${escHtml(d.OrderID)}</td>
            <td>${escHtml(d.DriverName)}</td>
            <td>${d.PickupTime ? escHtml(d.PickupTime) : '—'}</td>
            <td>${d.DeliveryTime ? escHtml(d.DeliveryTime) : '<span style="color:#e65100;">Pending</span>'}</td>
        </tr>`).join('');
}

function setOnline(online) {
    const bar = document.getElementById('live-bar');
    const dot = document.getElementById('pulse-dot');
    const status = document.getElementById('live-status');
    if (online) {
        bar.classList.remove('offline');
        dot.style.background = '#4CAF50';
        status.textContent = 'Live — updating every 5s';
        status.style.color = '#2e7d32';
    } else {
        bar.classList.add('offline');
        dot.style.background = '#e53935';
        status.textContent = 'Disconnected — retrying...';
        status.style.color = '#c62828';
    }
}

function flashNewRows(tbodyId, newHtml) {
    const tbody = document.getElementById(tbodyId);
    if (tbody.innerHTML !== newHtml) {
        tbody.innerHTML = newHtml;
        // Flash each row briefly
        Array.from(tbody.querySelectorAll('tr')).forEach(tr => {
            tr.classList.add('flash');
            tr.addEventListener('animationend', () => tr.classList.remove('flash'), { once: true });
        });
    }
}

function updateStats(stats) {
    document.getElementById('stat-users').textContent     = stats.total_users;
    document.getElementById('stat-products').textContent  = stats.total_products;
    document.getElementById('stat-orders').textContent    = stats.total_orders;
    document.getElementById('stat-transit').textContent   = stats.active_deliveries;
    document.getElementById('stat-delivered').textContent = stats.delivered;
}

async function fetchData() {
    try {
        const url = `api/admin.php?order_filter=${activeOrderFilter}`;
        const res = await fetch(url, { cache: 'no-store' });
        if (!res.ok) throw new Error('Server error');
        const data = await res.json();

        setOnline(true);
        document.getElementById('last-updated').textContent =
            'Last updated: ' + new Date().toLocaleTimeString();

        // Update stats
        if (data.stats) updateStats(data.stats);

        // Update tables (only re-render if data changed)
        const usersHtml = buildUsers(data.users);
        flashNewRows('users-body', usersHtml);
        document.getElementById('users-count').textContent = (data.users || []).length + ' users';

        const productsHtml = buildProducts(data.products);
        flashNewRows('products-body', productsHtml);
        document.getElementById('products-count').textContent = (data.products || []).length + ' products';

        const ordersHtml = buildOrders(data.orders);
        flashNewRows('orders-body', ordersHtml);
        document.getElementById('orders-count').textContent = (data.orders || []).length + ' orders';

        const deliveriesHtml = buildDeliveries(data.deliveries);
        flashNewRows('deliveries-body', deliveriesHtml);
        document.getElementById('deliveries-count').textContent = (data.deliveries || []).length + ' deliveries';

    } catch (err) {
        setOnline(false);
        console.error('Poll error:', err);
    }
}

// Initial load + start polling
fetchData();
setInterval(fetchData, POLL_INTERVAL);
</script>
</body>
</html>
