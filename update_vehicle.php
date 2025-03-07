<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = $_POST['record_id'];
    error_log("Updating vehicle ID: " . $vehicle_id);

    // Ensure all form fields are present and have valid values
    $year_id = isset($_POST['year_id']) && $_POST['year_id'] !== '' ? $_POST['year_id'] : null;
    $make_id = isset($_POST['make_id']) && $_POST['make_id'] !== '' ? $_POST['make_id'] : null;
    $model_id = isset($_POST['model_id']) && $_POST['model_id'] !== '' ? $_POST['model_id'] : null;
    $hybrid = isset($_POST['hybrid']) && $_POST['hybrid'] !== '' ? $_POST['hybrid'] : null;
    $start_system = isset($_POST['start_system']) && $_POST['start_system'] !== '' ? $_POST['start_system'] : null;
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

    try {
        $stmt = $pdo->prepare("UPDATE vehicle_information SET year_id = :year_id, make_id = :make_id, model_id = :model_id, hybrid = :hybrid, start_system = :start_system, notes = :notes WHERE id = :id");
        $stmt->execute([
            'year_id' => $year_id,
            'make_id' => $make_id,
            'model_id' => $model_id,
            'hybrid' => $hybrid,
            'start_system' => $start_system,
            'notes' => $notes,
            'id' => $vehicle_id
        ]);
        error_log("Vehicle information updated successfully for ID: " . $vehicle_id);
        header("Location: client_details.php?id=$vehicle_id");
        exit;
    } catch (PDOException $e) {
        error_log("Error updating vehicle information: " . $e->getMessage());
        die("An error occurred while updating vehicle information.");
    }
}
?>