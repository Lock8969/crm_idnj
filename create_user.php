<?php
require_once 'auth_check.php';
session_start();
include 'db.php'; // Include your database connection file

// Enable error logging
error_log("Create user process started");

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    error_log("Form data received: username=$username, email=$email");

    // Validate form data
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
        header("Location: /sign-up.php?error=" . urlencode($error));
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
        header("Location: /sign-up.php?error=" . urlencode($error));
        exit;
    }

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
        header("Location: /sign-up.php?error=" . urlencode($error));
        exit;
    }

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    try {
        // Check if username or email already exists
        $check_stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
        $check_stmt->execute([
            'username' => $username,
            'email' => $email
        ]);
        
        if ($check_stmt->rowCount() > 0) {
            $user = $check_stmt->fetch();
            if ($user['username'] === $username) {
                $error = "Username already exists.";
            } else {
                $error = "Email address already in use.";
            }
            header("Location: /sign-up.php?error=" . urlencode($error));
            exit;
        }

// Insert the new user
$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, 'Sales Rep')");
$stmt->execute([
    'username' => $username,
    'email' => $email,
    'password_hash' => $password_hash
]);

error_log("User created successfully: $username");

// Set success flag without HTML in the URL parameter
header("Location: /sign-up.php?success=true");
exit;
} catch (PDOException $e) {
error_log("Database error: " . $e->getMessage());

$error = "An error occurred: " . $e->getMessage();
header("Location: /sign-up.php?error=" . urlencode($error));
exit;
}
} else {
error_log("No POST data received");
header("Location: /sign-up.php");
exit;
}
?>