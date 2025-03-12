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

    try {
        $stmt = $pdo->prepare("UPDATE client_information SET first_name = :first_name, last_name = :last_name, phone_number = :phone_number, email = :email, address1 = :address1, address2 = :address2, city = :city, state = :state, zip = :zip WHERE id = :id");
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
            'id' => $client_id
        ]);  
        header("Location: $redirect_url"); //Rediect to whichever page called for the Update
        exit;
    } catch (PDOException $e) {
        error_log("Error updating client information: " . $e->getMessage());
        die("An error occurred while updating client information.");
    }
}
?>