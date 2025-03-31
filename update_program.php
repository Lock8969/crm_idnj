<?php
/**
 * =============================================
 * UPDATE PROGRAM INFORMATION
 * =============================================
 * This script is called by program_card.php to handle updates
 * to the program-related fields in the client_information table.
 * 
 * Fields updated:
 * - status (preserved, not editable)
 * - offense_number
 * - install_on
 * - dl_state
 * - law_type
 * - price_code
 * - install_comments
 * 
 * After update, redirects back to client_detail.php
 * =============================================
 */

require_once 'auth_check.php';
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['record_id'];
    $redirect_url = $_POST['redirect_url'];
    error_log("Updating program information for client ID: " . $client_id);

    // Get all form fields
    $status = $_POST['status']; // This is preserved from the hidden input
    $offense_number = $_POST['offense_number'];
    $install_on = $_POST['install_on'];
    $dl_state = $_POST['dl_state'];
    $law_type = $_POST['law_type'];
    $price_code = $_POST['price_code'];
    $install_comments = $_POST['install_comments'];

    try {
        $stmt = $pdo->prepare("UPDATE client_information 
                              SET status = :status,
                                  offense_number = :offense_number,
                                  install_on = :install_on,
                                  dl_state = :dl_state,
                                  law_type = :law_type,
                                  price_code = :price_code,
                                  install_comments = :install_comments 
                              WHERE id = :id");
        
        $params = [
            'status' => $status,
            'offense_number' => $offense_number ?: null,
            'install_on' => $install_on ?: null,
            'dl_state' => $dl_state ?: null,
            'law_type' => $law_type ?: null,
            'price_code' => $price_code ?: null,
            'install_comments' => $install_comments,
            'id' => $client_id
        ];
        
        error_log("Executing update with parameters: " . print_r($params, true));
        $stmt->execute($params);
        error_log("Update successful");

        header("Location: $redirect_url");
        exit;
    } catch (PDOException $e) {
        error_log("Error updating program information: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        die("An error occurred while updating program information: " . $e->getMessage());
    }
}
?>