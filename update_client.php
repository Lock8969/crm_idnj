<?php
require_once 'auth_check.php';
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['record_id'];
    $redirect_url = $_POST['redirect_url'];
    error_log("Updating client ID: " . $client_id);
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone_number = $_POST['phone_number'];
    $email = $_POST['email'];
    $address1 = $_POST['address1'];
    $address2 = $_POST['address2'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $zip = $_POST['zip'];
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;

    try {
        $stmt = $pdo->prepare("UPDATE client_information SET first_name = :first_name, last_name = :last_name, phone_number = :phone_number, email = :email, address1 = :address1, address2 = :address2, city = :city, state = :state, zip = :zip, dob = :dob WHERE id = :id");
        
        // Log the data being sent
        error_log("Attempting to update client with data: " . print_r([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone_number' => $phone_number,
            'email' => $email,
            'address1' => $address1,
            'address2' => $address2,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'dob' => $dob,
            'id' => $client_id
        ], true));
        
        $stmt->execute([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone_number' => $phone_number,
            'email' => $email,
            'address1' => $address1,
            'address2' => $address2,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'dob' => $dob,
            'id' => $client_id
        ]);  
        
        // Check if any rows were actually updated
        if ($stmt->rowCount() === 0) {
            error_log("No rows were updated for client ID: " . $client_id);
            die("No changes were made. The client information may not exist or the data is identical.");
        }
        
        header("Location: $redirect_url");
        exit;
    } catch (PDOException $e) {
        // Log detailed error information
        error_log("Error updating client information: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        error_log("Client ID: " . $client_id);
        error_log("SQL Query: UPDATE client_information SET first_name = :first_name, last_name = :last_name, phone_number = :phone_number, email = :email, address1 = :address1, address2 = :address2, city = :city, state = :state, zip = :zip, dob = :dob WHERE id = :id");
        
        // Show the actual error message to the user
        die("Database Error: " . $e->getMessage() . ". Please try again or contact support if the problem persists.");
    }
}
?>