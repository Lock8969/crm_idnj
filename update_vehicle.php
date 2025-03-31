<?php
/**
 * =============================================
 * UPDATE VEHICLE
 * =============================================
 * This script is called by vehicle_card.php to handle vehicle information updates
 * from the edit form in the vehicle card.
 * 
 * =============================================
 * HOW IT WORKS
 * =============================================
 * 1. Receives POST data from vehicle_card.php edit form
 * 2. Validates all form fields
 * 3. Checks if vehicle record exists for customer
 * 4. Updates existing record or creates new one
 * 5. Redirects back to client_detail.php
 * 
 * =============================================
 * REQUIRED FIELDS
 * =============================================
 * - customer_id: The ID of the client
 * 
 * =============================================
 * OPTIONAL FIELDS
 * =============================================
 * - year_id: Vehicle year
 * - make_id: Vehicle make
 * - model_id: Vehicle model
 * - hybrid: Whether vehicle is hybrid
 * - start_system: Type of start system
 * - start_stop: Whether vehicle has start/stop
 * - notes: Additional vehicle notes
 * 
 * =============================================
 * REDIRECTS TO
 * =============================================
 * client_detail.php?id={customer_id}
 * 
 * =============================================
 */

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
    $start_stop = isset($_POST['start_stop']) && $_POST['start_stop'] !== '' ? $_POST['start_stop'] : null;
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

    try {
        // Check if a vehicle record already exists for this customer
        $check_stmt = $pdo->prepare("SELECT id FROM vehicle_information WHERE customer_id = :customer_id");
        $check_stmt->execute(['customer_id' => $customer_id]);
        $existing_vehicle = $check_stmt->fetch();

        if ($existing_vehicle) {
            // Update existing record
            $stmt = $pdo->prepare("UPDATE vehicle_information SET year_id = :year_id, make_id = :make_id, model_id = :model_id, hybrid = :hybrid, start_system = :start_system, start_stop = :start_stop, notes = :notes WHERE customer_id = :customer_id");
        } else {
            // Insert new record
            $stmt = $pdo->prepare("INSERT INTO vehicle_information (customer_id, year_id, make_id, model_id, hybrid, start_system, start_stop, notes) VALUES (:customer_id, :year_id, :make_id, :model_id, :hybrid, :start_system, :start_stop, :notes)");
        }

        $stmt->execute([
            'customer_id' => $customer_id,
            'year_id' => $year_id,
            'make_id' => $make_id,
            'model_id' => $model_id,
            'hybrid' => $hybrid,
            'start_system' => $start_system,
            'start_stop' => $start_stop,
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