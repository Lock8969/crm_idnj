<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Debugging: Print all form data (Only enable if needed)
    // echo "<pre>";
    // print_r($_POST);
    // echo "</pre>";
    // exit;

    if (!isset($_POST['table']) || !isset($_POST['record_id'])) {
        die("Invalid request: Missing table name or record ID.");
    }

    $table = $_POST['table']; // Get table name dynamically
    $record_id = intval($_POST['record_id']); // Ensure it's an integer

    // Define allowed tables to prevent SQL injection
    $allowed_tables = ['leads', 'client_information', 'vehicle_information'];

    if (!in_array($table, $allowed_tables)) {
        die("Invalid table.");
    }

    // Initialize query parts
    $fields = [];
    $params = [];

    // Fetch column names dynamically for the table
    $stmt = $pdo->query("SHOW COLUMNS FROM $table");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    /// Iterate through posted fields and dynamically build query
    foreach ($columns as $column) {
        if (isset($_POST[$column])) {
            // Convert empty values to NULL for integer and ENUM fields
            if (in_array($column, ['hybrid', 'model_id', 'make_id', 'start_system']) && $_POST[$column] === '') {
                $fields[] = "$column = NULL";
            } else {
                $fields[] = "$column = ?";
                $params[] = $column === 'state' ? strtoupper($_POST[$column]) : $_POST[$column];
            }
        }
    }
    


    // Boolean fields (set to 1 if checked, 0 if unchecked)
    $boolean_fields = ['spam', 'error', 'lost', 'current_client', 'wrong_number', 'not_applicable'];
    foreach ($boolean_fields as $field) {
        if (in_array($field, $columns)) {
            $fields[] = "$field = ?";
            $params[] = isset($_POST[$field]) ? 1 : 0;
        }
    }

    // Ensure at least one field is being updated
    if (empty($fields)) {
        die("No valid fields provided for update.");
    }

    // Add record ID to params
    $params[] = $record_id;

    // Build and execute the SQL query
    if ($table === 'vehicle_information') {
        $sql = "UPDATE $table SET " . implode(', ', $fields) . " WHERE customer_id = ?";
    } else {
        $sql = "UPDATE $table SET " . implode(', ', $fields) . " WHERE id = ?";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Debugging: Check for SQL errors
    if ($stmt->errorCode() !== '00000') {
        echo "<pre>";
        print_r($stmt->errorInfo());
        echo "</pre>";
        exit;
    }

    // Redirect to detail page
    header("Location: " . $_POST['redirect_url'] . "?id=$record_id&updated=true");
    exit;
}
?>
