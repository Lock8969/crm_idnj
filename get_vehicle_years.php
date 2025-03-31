<?php
/**
 * This works with Lead convert flow
 * Uses this to get years for years from the database
 */

require_once 'db.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, year FROM vehicle_years ORDER BY year DESC");
    $years = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($years);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch vehicle years']);
}
?> 