<?php
// Start the session
session_start();

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Delete the remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    // Clear the token in the database
    include 'db.php';
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = :id");
            $stmt->execute(['id' => $_SESSION['user_id']]);
        } catch (PDOException $e) {
            // Just log the error but continue with logout
            error_log("Error clearing remember token: " . $e->getMessage());
        }
    }
    
    // Clear the cookie
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
?>