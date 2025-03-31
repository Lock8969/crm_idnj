<?php
/**
 * This works with Lead convert flow
 * When vehicle information is saved, finds customer_id that matches client_id 
 * and updates vehicle information from form
 */

require 'db.php';

header('Content-Type: application/json');

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Log the client_id being used
    error_log("Searching for vehicle record with client_id: " . $data['client_id']);
    
    // Check if vehicle record exists for this customer
    $check_stmt = $pdo->prepare("SELECT id FROM vehicle_information WHERE customer_id = :customer_id");
    $check_stmt->execute(['customer_id' => $data['client_id']]);
    $existing_vehicle = $check_stmt->fetch();

    if (!$existing_vehicle) {
        throw new Exception('No vehicle record found for this customer');
    }
    
    // Map form fields to database fields
    $vehicleData = [
        'customer_id' => $data['client_id'],
        'year_id' => $data['year_id'],
        'make_id' => $data['make_id'],
        'model_id' => $data['model_id'],
        'hybrid' => $data['hybrid'],
        'start_system' => $data['start_system'],
        'start_stop' => $data['start_stop'],
        'notes' => $data['notes'] ?? null
    ];

    // Prepare SQL statement for UPDATE
    $sql = "UPDATE vehicle_information SET 
        year_id = :year_id,
        make_id = :make_id,
        model_id = :model_id,
        hybrid = :hybrid,
        start_system = :start_system,
        start_stop = :start_stop,
        notes = :notes
        WHERE customer_id = :customer_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($vehicleData);

    echo json_encode(['success' => true, 'message' => 'Vehicle information updated successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 