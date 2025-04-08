<?php
require_once 'auth_check.php';
include 'db.php';

// Validate input
if (!isset($_POST['record_id']) || !filter_var($_POST['record_id'], FILTER_VALIDATE_INT)) {
    die("Invalid client ID.");
}

$client_id = $_POST['record_id'];
$handset_id = $_POST['handset_id'] ?? null;
$control_box_id = $_POST['control_box_id'] ?? null;

try {
    // Start transaction
    $pdo->beginTransaction();

    // First, clear any existing assignments for this client
    $stmt = $pdo->prepare("UPDATE hs_inventory SET customer_id = NULL WHERE customer_id = :customer_id");
    $stmt->execute(['customer_id' => $client_id]);

    $stmt = $pdo->prepare("UPDATE cb_inventory SET customer_id = NULL WHERE customer_id = :customer_id");
    $stmt->execute(['customer_id' => $client_id]);

    // Assign new handset if selected
    if ($handset_id) {
        $stmt = $pdo->prepare("UPDATE hs_inventory SET customer_id = :customer_id WHERE id = :id AND customer_id IS NULL");
        $stmt->execute([
            'customer_id' => $client_id,
            'id' => $handset_id
        ]);
    }

    // Assign new control box if selected
    if ($control_box_id) {
        $stmt = $pdo->prepare("UPDATE cb_inventory SET customer_id = :customer_id WHERE id = :id AND customer_id IS NULL");
        $stmt->execute([
            'customer_id' => $client_id,
            'id' => $control_box_id
        ]);
    }

    // Commit transaction
    $pdo->commit();

    // Redirect back to client detail page
    $redirect_url = $_POST['redirect_url'] ?? "client_detail.php?id=" . $client_id;
    header("Location: " . $redirect_url);
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    error_log("Error updating device assignments: " . $e->getMessage());
    die("An error occurred while updating device assignments.");
} 