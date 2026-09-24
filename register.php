<?php
// register.php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = sanitizeInput($_POST['name']);
    $email       = sanitizeInput($_POST['email']);
    $password    = $_POST['password'];
    $role        = $_POST['role'];
    $address     = sanitizeInput($_POST['address']);
    $coordinates = sanitizeInput($_POST['coordinates']);

    $allowedRoles = ['Farmer', 'Consumer', 'Driver'];

    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "Please fill in all required fields.";
    } elseif (!in_array($role, $allowedRoles)) {
        $error = "Invalid role selected.";
    } else {
        $stmt = $pdo->prepare("SELECT UserID FROM Users WHERE Email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email is already registered.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO Users (Name, Email, PasswordHash, Role, Address, Coordinates) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hashedPassword, $role, $address, $coordinates]);
                $success = "Account created! <a href='login.php'>Click here to login</a>.";
            } catch (PDOException $e) {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – Farm to Table</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 520px;">
        <div class="auth-logo"><span>🌱</span></div>
        <h2>Create Account</h2>
        <p class="auth-sub">Join the Farm-to-Table community</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php else: ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" placeholder="John Doe" required
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="you@example.com" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Min. 8 characters" required>
            </div>
            <div class="form-group">
                <label for="role">I am a...</label>
                <select id="role" name="role" required>
                    <option value="">Select your role</option>
                    <option value="Farmer"   <?php if(isset($_POST['role']) && $_POST['role']==='Farmer')   echo 'selected'; ?>>🌾 Farmer</option>
                    <option value="Consumer" <?php if(isset($_POST['role']) && $_POST['role']==='Consumer') echo 'selected'; ?>>🛒 Consumer</option>
                    <option value="Driver"   <?php if(isset($_POST['role']) && $_POST['role']==='Driver')   echo 'selected'; ?>>🚚 Delivery Driver</option>
                </select>
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="2" placeholder="Your full address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
            </div>
            <div class="form-group">
                <label for="coordinates">Your Location <span style="font-weight:400;color:#999;">(for delivery routing)</span></label>
                <input type="text" id="coordinates" name="coordinates"
                       placeholder="e.g. 34.0522,-118.2437" readonly style="background:#f5f5f5;"
                       value="<?php echo isset($_POST['coordinates']) ? htmlspecialchars($_POST['coordinates']) : ''; ?>">
                <button type="button" id="locate-btn" onclick="detectLocation()">📍 Use My Location</button>
                <div id="location-status">Click the button above to auto-detect your location.</div>
                <iframe id="map-preview" src="" frameborder="0" allowfullscreen></iframe>
            </div>
            <button type="submit" class="btn btn-full" style="margin-top: 6px;">Create Account</button>
        </form>

        <?php endif; ?>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Sign in here</a>
        </div>
    </div>
</div>

<script>
function detectLocation() {
    const status    = document.getElementById('location-status');
    const coordsIn  = document.getElementById('coordinates');
    const mapFrame  = document.getElementById('map-preview');
    const btn       = document.getElementById('locate-btn');

    if (!navigator.geolocation) { status.textContent = 'Geolocation not supported by your browser.'; return; }

    btn.textContent = 'Detecting...';
    btn.disabled    = true;
    status.textContent = 'Requesting your location...';

    navigator.geolocation.getCurrentPosition(
        pos => {
            const lat = pos.coords.latitude.toFixed(6);
            const lng = pos.coords.longitude.toFixed(6);
            coordsIn.value = lat + ',' + lng;
            status.innerHTML = '✅ Location detected! Confirm on the map below.';
            status.style.color = '#2e7d32';
            btn.textContent = '📍 Re-detect Location';
            btn.disabled = false;
            mapFrame.src  = `https://maps.google.com/maps?q=${lat},${lng}&z=15&output=embed`;
            mapFrame.style.display = 'block';
        },
        () => {
            status.textContent = 'Could not detect location. Please type coordinates manually.';
            status.style.color = '#c62828';
            btn.textContent = '📍 Try Again';
            btn.disabled = false;
            coordsIn.removeAttribute('readonly');
            coordsIn.style.background = '';
        }
    );
}
</script>
</body>
</html>
