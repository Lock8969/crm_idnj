<?php
require_once 'db.php';

function getLocations() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, location_name FROM locations ORDER BY location_name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching locations: " . $e->getMessage());
        return [];
    }
}

// If called directly, return JSON response
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'locations' => getLocations()]);
}
?> 