<?php
// driver_dashboard.php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireRole('Driver');

$driverId = $_SESSION['user_id'];

// Fetch assigned deliveries - join BOTH farmer and consumer location data
$stmt = $pdo->prepare("
    SELECT 
        d.DeliveryID, d.PickupTime, d.DeliveryTime,
        o.OrderID, o.Status, o.TotalAmount,
        farmer.Name     AS FarmerName,
        farmer.Address  AS FarmerAddress,
        farmer.Coordinates AS FarmerCoordinates,
        consumer.Name   AS ConsumerName,
        consumer.Address AS ConsumerAddress,
        consumer.Coordinates AS ConsumerCoordinates
    FROM Deliveries d
    JOIN Orders o ON d.OrderID = o.OrderID
    JOIN OrderItems oi ON oi.OrderID = o.OrderID
    JOIN Products p ON p.ProductID = oi.ProductID
    JOIN Users farmer ON farmer.UserID = p.FarmerID
    JOIN Users consumer ON consumer.UserID = o.ConsumerID
    WHERE d.DriverID = ?
    GROUP BY d.DeliveryID
    ORDER BY o.OrderDate DESC
");
$stmt->execute([$driverId]);
$deliveries = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard – Farm to Table</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/driver.css">
    <script>
        function openMaps(coords, address) {
            const query = coords && coords.trim() ? coords : encodeURIComponent(address);
            const url = "https://www.google.com/maps/dir/?api=1&destination=" + query;
            window.open(url, '_blank');
        }

        function updateStatus(deliveryId, orderId, status) {
            fetch('api/driver.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ deliveryId, orderId, status })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error updating status: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(() => alert('Network error. Please try again.'));
        }
    </script>
</head>
<body>
<nav class="navbar">
    <h1>🚚 Farm-to-Table</h1>
    <div class="menu-icon" onclick="this.nextElementSibling.classList.toggle('active')">☰</div>
    <div class="nav-links">
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="page-body">
    <div class="page-header">
        <div>
            <h2>Your Deliveries</h2>
            <div class="welcome-sub">Navigate your assigned deliveries step by step.</div>
        </div>
    </div>

        <div class="grid">
            <?php if (empty($deliveries)): ?>
                <p>No deliveries assigned to you right now.</p>
            <?php else: ?>
                <?php foreach ($deliveries as $d): ?>
                    <div class="card">
                        <h3>Order #<?php echo $d['OrderID']; ?></h3>
                        <p><strong>Total:</strong> LKR <?php echo number_format($d['TotalAmount'], 2); ?></p>
                        <p><strong>Status:</strong>
                            <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $d['Status'])); ?>">
                                <?php echo $d['Status']; ?>
                            </span>
                        </p>

                        <!-- Step Progress Bar -->
                        <?php
                            $s = $d['Status'];
                            $step1 = ($s === 'Pending' || $s === 'Processing') ? 'active' : 'done';
                            $step2 = ($s === 'In Transit') ? 'active' : ($s === 'Delivered' ? 'done' : '');
                            $step3 = ($s === 'Delivered') ? 'done' : '';
                        ?>
                        <div class="step-bar">
                            <div class="step <?php echo $step1; ?>">📦 Go to Farmer</div>
                            <span class="step-arrow">→</span>
                            <div class="step <?php echo $step2; ?>">🚚 Go to Consumer</div>
                            <span class="step-arrow">→</span>
                            <div class="step <?php echo $step3; ?>">✅ Delivered</div>
                        </div>

                        <!-- STEP 1: Navigate to Farmer & pick up -->
                        <?php if ($s === 'Pending' || $s === 'Processing'): ?>
                            <div class="location-box">
                                <strong>📍 Step 1 – Pick up from Farmer</strong>
                                <?php echo htmlspecialchars($d['FarmerName']); ?><br>
                                <small><?php echo htmlspecialchars($d['FarmerAddress'] ?? 'No address set'); ?></small>
                            </div>
                            <div class="action-row">
                                <button class="btn-maps" onclick="openMaps('<?php echo htmlspecialchars($d['FarmerCoordinates']); ?>','<?php echo htmlspecialchars($d['FarmerAddress']); ?>')">
                                    🗺️ Navigate to Farmer
                                </button>
                                <button class="btn btn-pickup" onclick="updateStatus(<?php echo $d['DeliveryID']; ?>, <?php echo $d['OrderID']; ?>, 'In Transit')">
                                    ✅ I've Got the Product
                                </button>
                            </div>

                        <!-- STEP 2: Navigate to Consumer & deliver -->
                        <?php elseif ($s === 'In Transit'): ?>
                            <div class="location-box">
                                <strong>📍 Step 2 – Deliver to Consumer</strong>
                                <?php echo htmlspecialchars($d['ConsumerName']); ?><br>
                                <small><?php echo htmlspecialchars($d['ConsumerAddress'] ?? 'No address set'); ?></small>
                            </div>
                            <div class="action-row">
                                <button class="btn-maps" onclick="openMaps('<?php echo htmlspecialchars($d['ConsumerCoordinates']); ?>','<?php echo htmlspecialchars($d['ConsumerAddress']); ?>')">
                                    🗺️ Navigate to Consumer
                                </button>
                                <button class="btn btn-delivered" onclick="updateStatus(<?php echo $d['DeliveryID']; ?>, <?php echo $d['OrderID']; ?>, 'Delivered')">
                                    📬 Mark as Delivered
                                </button>
                            </div>

                        <!-- STEP 3: Done -->
                        <?php elseif ($s === 'Delivered'): ?>
                            <div class="location-box" style="background:#eaffea; border-color:#b2dfb2;">
                                <strong>✅ Delivery Complete</strong>
                                Delivered to <?php echo htmlspecialchars($d['ConsumerName']); ?><br>
                                <small>Delivered at: <?php echo htmlspecialchars($d['DeliveryTime'] ?? 'N/A'); ?></small>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
</div>

    <script src="assets/js/app.js"></script>
</body>
</html>
</body>
</html>
