<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lead_id = $_POST['lead_id'];

    // Initialize arrays to hold the fields and values 
    //This iterates IF over below fields so i don't have to fill everything out
    $fields = [];
    $params = [];

    // List of all possible fields
    $possible_fields = [
        'first_name', 'last_name', 'phone_number', 'email', 'status', 'lead_source', 'caller_id_number', 
        'created_by', 'collaborators', 'gmb_location', 'gclid', 'caller_id_name', 'client_id', 
        'callback_sequence', 'follow_up_date', 'court_date', 'offense_number', 'start_stop', 
        'hybrid', 'start_system', 'vehicle_notes', 'location', 'notes', 'loss_reason_description', 
        'error_description', 'attorney_contact', 'how_long_lead_form', 'scheduled_by', 
        'last_modified_gclid', 'status_modified', 'install_on', 'removed_on',
        'address1', 'address2', 'city', 'state', 'zip'
    ];

    // Iterate over possible fields and add to query if set
    foreach ($possible_fields as $field) {
        if (isset($_POST[$field])) {
            $fields[] = "$field = ?";
            $params[] = $field === 'state' ? strtoupper($_POST[$field]) : $_POST[$field];
        }
    }

    // Boolean fields
    $boolean_fields = ['spam', 'error', 'lost', 'current_client', 'wrong_number', 'not_applicable'];
    foreach ($boolean_fields as $field) {
        $fields[] = "$field = ?";
        $params[] = isset($_POST[$field]) ? 1 : 0;
    }

    // Add lead_id to params
    $params[] = $lead_id;

    // Build the SQL query
    $sql = "UPDATE leads SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->errorCode() !== '00000') {
        echo "<pre>";
        print_r($stmt->errorInfo());
        echo "</pre>";
        exit;
    }

    header("Location: lead_detail.php?id=$lead_id&updated=true");
    exit;
}
?>
