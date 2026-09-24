<?php
// includes/auth.php
session_start();

// Utility function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Utility function to get the current logged in user's role
function getUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

// Utility function to require login for a page
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

// Utility function to require a specific role for a page
function requireRole($role) {
    requireLogin();
    if (getUserRole() !== $role) {
        header("Location: index.php"); // Redirect to home if unauthorized
        exit;
    }
}

// Helper to sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
?>
