<?php
/**
 * This works with Lead convert flow
 * Uses this to get makes for makes from the database
 */
require_once 'db.php'; // Include database connection

header('Content-Type: application/json');

try {
    // Fetch all makes ordered alphabetically
    $stmt = $pdo->query("SELECT id, make FROM vehicle_makes ORDER BY make");
    $makes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($makes);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 