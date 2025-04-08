<?php
// -----
// GET DEVICES API ENDPOINT
// This file handles AJAX requests for device search functionality
// Returns available devices based on search criteria and device type
// -----

// -----
// DATABASE CONNECTION
// Includes database connection file
// -----
include 'db.php';

// -----
// INPUT VALIDATION
// Ensures required parameters are present
// -----
if (!isset($_GET['type']) || !isset($_GET['search'])) {
    die(json_encode(['error' => 'Missing required parameters']));
}

// -----
// PARAMETER EXTRACTION
// Retrieves and sanitizes input parameters
// -----
$type = $_GET['type'];
$search = $_GET['search'];

// -----
// DEVICE TYPE VALIDATION
// Ensures device type is either handset or control_box
// -----
if (!in_array($type, ['handset', 'control_box'])) {
    die(json_encode(['error' => 'Invalid device type']));
}

// -----
// TABLE SELECTION
// Determines which inventory table to query based on device type
// -----
$table = $type === 'handset' ? 'hs_inventory' : 'cb_inventory';

// -----
// DATABASE QUERY
// Searches for available devices matching the search criteria
// Only returns devices not assigned to any customer (customer_id IS NULL)
// -----
$stmt = $pdo->prepare("
    SELECT id, serial_number 
    FROM $table 
    WHERE customer_id IS NULL 
    AND serial_number LIKE :search 
    ORDER BY serial_number 
    LIMIT 10
");

// -----
// SEARCH PARAMETER FORMATTING
// Formats search term for SQL LIKE query
// -----
$searchParam = "%$search%";

// -----
// QUERY EXECUTION
// Executes the prepared statement with search parameter
// -----
$stmt->execute(['search' => $searchParam]);

// -----
// RESULT FETCHING
// Retrieves all matching devices
// -----
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -----
// RESPONSE HEADERS
// Sets appropriate content type for JSON response
// -----
header('Content-Type: application/json');

// -----
// RESPONSE OUTPUT
// Returns devices as JSON array
// -----
echo json_encode($devices); 