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
    $install_on = !empty($_POST['install_on']) ? $_POST['install_on'] : null;
    $dl_state = $_POST['dl_state'];
    
    // Handle law_type - only accept 'old law', 'new law', or NULL
    $law_type = $_POST['law_type'];
    if (!in_array($law_type, ['old law', 'new law'])) {
        $law_type = null;
    }
    
    $price_code = $_POST['price_code'];
    $calibration_interval = $_POST['calibration_interval'];
    $arresting_municipality = !empty($_POST['arresting_municipality']) ? $_POST['arresting_municipality'] : null;
    $offense_date = !empty($_POST['offense_date']) ? $_POST['offense_date'] : null;
    $out_of_state = $_POST['out_of_state'];
    $install_comments = $_POST['install_comments'];

    try {
        $stmt = $pdo->prepare("UPDATE client_information SET 
            status = :status,
            offense_number = :offense_number,
            install_on = :install_on,
            dl_state = :dl_state,
            law_type = :law_type,
            price_code = :price_code,
            calibration_interval = :calibration_interval,
            arresting_municipality = :arresting_municipality,
            offense_date = :offense_date,
            out_of_state = :out_of_state,
            install_comments = :install_comments
            WHERE id = :id");
        
        $stmt->execute([
            'status' => $status,
            'offense_number' => $offense_number,
            'install_on' => $install_on,
            'dl_state' => $dl_state,
            'law_type' => $law_type,
            'price_code' => $price_code,
            'calibration_interval' => $calibration_interval,
            'arresting_municipality' => $arresting_municipality,
            'offense_date' => $offense_date,
            'out_of_state' => $out_of_state,
            'install_comments' => $install_comments,
            'id' => $client_id
        ]);
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