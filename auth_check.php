<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Override PHP's session garbage collection settings
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100);
    session_start();
}

// Set session timeout to 1 hour (3600 seconds)
$session_timeout = 3600;

// Check if session has timed out
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    // Session expired - clear all session data
    session_unset();
    session_destroy();
    
    // Redirect to login page with timeout parameter
    header("Location: login.php?timeout=1");
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header("Location: login.php");
    exit;
}

// Check if user has a location set
if (!isset($_SESSION['location_id'])) {
    // User doesn't have a location, redirect to login page
    header("Location: login.php?error=nolocation");
    exit;
}

// Optional: Function to check user role for specific page access
function requireRole($role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] != $role) {
        // User doesn't have required role
        header("Location: unauthorized.php");
        exit;
    }
}

// Function to get the current user's location information
function getCurrentLocation() {
    return [
        'id' => $_SESSION['location_id'],
        'name' => $_SESSION['location_name'] ?? 'Unknown Location'
    ];
}
?>