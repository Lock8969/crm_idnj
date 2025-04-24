<?php
//----------------------------------------
// THIS IS THE API FOR UPDATING A USER
// SENT HERE FROM UPDATE_USER_MODAL.PHP
//-----------------------------------------
// Set headers for JSON response
header('Content-Type: application/json');

// Include required files
require_once 'SystemService.php';

// Initialize SystemService
$systemService = new SystemService();

// Get POST data
$data = [
    'user_id' => $_POST['user_id'] ?? null,
    'username' => $_POST['username'] ?? null,
    'email' => $_POST['email'] ?? null,
    'full_name' => $_POST['full_name'] ?? null,
    'role' => $_POST['role'] ?? null,
    'password' => $_POST['password'] ?? null,
    'action' => $_POST['action'] ?? null // New field to handle delete action
];

// If action is delete, mark user as inactive
if ($data['action'] === 'delete') {
    $data['active'] = 0;
}

// Call updateUser method
$result = $systemService->updateUser($data);

// Return JSON response
echo json_encode($result);
?> 