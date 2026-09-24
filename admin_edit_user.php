<?php
// admin_edit_user.php
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
    $email = sanitizeInput($_POST['email']);
    $role = sanitizeInput($_POST['role']);
    $address = sanitizeInput($_POST['address']);
    $coordinates = sanitizeInput($_POST['coordinates']);

    if (empty($name) || empty($email)) {
        $error = "Name and Email are required.";
    } else {
        $stmt = $pdo->prepare("UPDATE Users SET Name=?, Email=?, Role=?, Address=?, Coordinates=? WHERE UserID=?");
        if ($stmt->execute([$name, $email, $role, $address, $coordinates, $id])) {
            $message = "User updated successfully!";
        } else {
            $error = "Failed to update user.";
        }
    }
}

// Fetch current data
$stmt = $pdo->prepare("SELECT * FROM Users WHERE UserID = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: admin_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold;}
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .message { background-color: #dff0d8; padding: 10px; border: 1px solid #d0e9c6; color: #3c763d; margin-bottom: 20px;}
        .error { background-color: #f2dede; padding: 10px; border: 1px solid #ebccd1; color: #a94442; margin-bottom: 20px;}
        .back-link { display: inline-block; margin-bottom: 20px; color: #0066cc; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 600px;">
        <a href="admin_dashboard.php" class="back-link">&larr; Back to Dashboard</a>
        <h2>Edit User: <?php echo htmlspecialchars($user['Name']); ?></h2>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['Name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['Email']); ?>" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role">
                    <option value="Farmer" <?php if($user['Role'] == 'Farmer') echo 'selected'; ?>>Farmer</option>
                    <option value="Consumer" <?php if($user['Role'] == 'Consumer') echo 'selected'; ?>>Consumer</option>
                    <option value="Driver" <?php if($user['Role'] == 'Driver') echo 'selected'; ?>>Driver</option>
                    <option value="Admin" <?php if($user['Role'] == 'Admin') echo 'selected'; ?>>Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="3"><?php echo htmlspecialchars($user['Address'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Coordinates</label>
                <input type="text" name="coordinates" value="<?php echo htmlspecialchars($user['Coordinates'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn">Update User</button>
        </form>
    </div>
</body>
</html>
