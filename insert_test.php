<?php
include 'db.php';

try {
    $sql = "INSERT INTO leads (phone_number, first_name, last_name, email, status) 
            VALUES ('1234567890', 'John', 'Doe', 'john.doe@example.com', 'New')";
    
    $pdo->exec($sql);
    echo "Test lead inserted successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
