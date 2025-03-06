<?php

// THIS PAGE yearly on November 1 at midnight should add the next year
// First time will be November 1 2026

// Include database connection
require 'db.php'; // Ensure this file correctly connects to your database

try {
    // Get the current highest year in the vehicle_years table
    $stmt = $pdo->query("SELECT MAX(year) AS max_year FROM vehicle_years");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $max_year = $row['max_year'] ?? 0;

    // Determine the next year
    $next_year = date('Y') + 1;

    // Check if the next year already exists in the table
    if ($max_year < $next_year) {
        $insert_stmt = $pdo->prepare("INSERT INTO vehicle_years (year) VALUES (:year)");
        $insert_stmt->execute(['year' => $next_year]);
        echo "Successfully added vehicle year: $next_year\n";
    } else {
        echo "Year $next_year already exists. No action taken.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
