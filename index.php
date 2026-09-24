<?php
// index.php
require_once 'includes/auth.php';

if (isLoggedIn()) {
    $role = getUserRole();
    if ($role === 'Farmer') {
        header("Location: farmer_dashboard.php");
    } elseif ($role === 'Consumer') {
        header("Location: consumer_dashboard.php");
    } elseif ($role === 'Driver') {
        header("Location: driver_dashboard.php");
    } else {
        header("Location: login.php");
    }
} else {
    header("Location: login.php");
}
exit;
?>
