<?php
//----------------------------------------
// THIS IS THE API FOR ADDING A NEW USER
// SENT HERE FROM ADD_USER_MODAL.PHP
//-----------------------------------------
require_once 'SystemService.php';

header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get form data
$username = $_POST['username'] ?? '';
$full_name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$role = $_POST['role'] ?? '';
$password = $_POST['password'] ?? '';

// Validate required fields
if (empty($username) || empty($full_name) || empty($email) || empty($role) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    // Initialize SystemService
    $systemService = new SystemService();
    
    // Call createUser method
    $result = $systemService->createUser($username, $email, $password, $full_name, $role);
    
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} 