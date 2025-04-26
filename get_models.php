<?php
/**
 * This works with Lead convert flow from vehicles and with the edit part of the vehicle card
 * Uses this to get models based on selected make from the database
 */

require_once 'db.php';

header('Content-Type: application/json');

if (isset($_POST['make_id'])) {
    $make_id = intval($_POST['make_id']); // Ensure it's an integer
    try {
        // Fetch models based on the selected make
        $stmt = $pdo->prepare("SELECT id, model FROM vehicle_models WHERE make_id = ? ORDER BY model");
        $stmt->execute([$make_id]);
        
        $models = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $models[] = $row;
        }
        
        echo json_encode($models);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'No make_id provided']);
}
?>
