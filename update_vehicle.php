<?php
require_once 'auth_check.php';
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the customer ID
    $customer_id = $_POST['customer_id'];
    
    error_log("Updating vehicle for customer ID: " . $customer_id);

    // Ensure all form fields are present and have valid values
    $year_id = isset($_POST['year_id']) && $_POST['year_id'] !== '' ? $_POST['year_id'] : null;
    $make_id = isset($_POST['make_id']) && $_POST['make_id'] !== '' ? $_POST['make_id'] : null;
    $model_id = isset($_POST['model_id']) && $_POST['model_id'] !== '' ? $_POST['model_id'] : null;
    $hybrid = isset($_POST['hybrid']) && $_POST['hybrid'] !== '' ? $_POST['hybrid'] : null;
    $start_system = isset($_POST['start_system']) && $_POST['start_system'] !== '' ? $_POST['start_system'] : null;
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

    try {
        // Check if a vehicle record already exists for this customer
        $check_stmt = $pdo->prepare("SELECT id FROM vehicle_information WHERE customer_id = :customer_id");
        $check_stmt->execute(['customer_id' => $customer_id]);
        $existing_vehicle = $check_stmt->fetch();

        if ($existing_vehicle) {
            // Update existing record
            $stmt = $pdo->prepare("UPDATE vehicle_information SET year_id = :year_id, make_id = :make_id, model_id = :model_id, hybrid = :hybrid, start_system = :start_system, notes = :notes WHERE customer_id = :customer_id");
        } else {
            // Insert new record
            $stmt = $pdo->prepare("INSERT INTO vehicle_information (customer_id, year_id, make_id, model_id, hybrid, start_system, notes) VALUES (:customer_id, :year_id, :make_id, :model_id, :hybrid, :start_system, :notes)");
        }

        $stmt->execute([
            'customer_id' => $customer_id,
            'year_id' => $year_id,
            'make_id' => $make_id,
            'model_id' => $model_id,
            'hybrid' => $hybrid,
            'start_system' => $start_system,
            'notes' => $notes
        ]);
        
        error_log("Vehicle information updated successfully for customer ID: " . $customer_id);
        
        // Redirect to the client detail page with customer ID
        header("Location: client_detail.php?id=$customer_id");
        exit;
    } catch (PDOException $e) {
        error_log("Error updating vehicle information: " . $e->getMessage());
        die("An error occurred while updating vehicle information: " . $e->getMessage());
    }
}
?>