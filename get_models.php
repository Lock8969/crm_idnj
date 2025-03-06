<?php
require 'db.php'; // Include database connection

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
