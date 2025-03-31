<?php
/**
 * This works with Lead convert flow from vehicles and with the edit part of the vehicle card
 * Uses this to get models based on selected make from the database
 */

require_once 'db.php';

header('Content-Type: application/json');

if (isset($_POST['make_id'])) {
    $make_id = intval($_POST['make_id']); // Ensure it's an integer
    // Fetch models based on the selected make
    $stmt = $pdo->prepare("SELECT id, model FROM vehicle_models WHERE make_id = ? ORDER BY model");
    $stmt->execute([$make_id]);
    
    $options = "<option value=''>Select Model</option>"; // Default option

    while ($row = $stmt->fetch()) {
        $options .= "<option value='{$row['id']}'>{$row['model']}</option>";
    }
    echo $options; // Send response back to AJAX
}
?>
