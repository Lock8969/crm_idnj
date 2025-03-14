<?php
/**
 * create_client_record.php
 * Creates or updates client records after successful payment
 */
require_once 'auth_check.php';
include 'db.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get the input data
$data = json_decode(file_get_contents('php://input'), true);

// If JSON parsing fails, try to get from POST
if (!$data) {
    $data = $_POST;
}

try {
    // Start a transaction
    $pdo->beginTransaction();
    
    $clientId = null;
    $isNewClient = false;
    
    // Check if we're updating an existing client or creating a new one
    if (!empty($data['customer_id'])) {
        // Existing client - update record
        $clientId = $data['customer_id'];
        
        $updateStmt = $pdo->prepare("
            UPDATE client_information SET
                price_code = :price_code,
                updated_at = NOW()
            WHERE id = :client_id
        ");
        
        $updateStmt->execute([
            'price_code' => $data['price_code'],
            'client_id' => $clientId
        ]);
    } else {
        // New client - insert record
        $isNewClient = true;
        
        $insertStmt = $pdo->prepare("
            INSERT INTO client_information (
                phone_number, first_name, last_name, email, 
                dl_state, law_type, offense_number, price_code,
                created_at, created_by, install_comments
            ) VALUES (
                :phone_number, :first_name, :last_name, :email,
                :dl_state, :law_type, :offense_number, :price_code,
                NOW(), :created_by, :install_comments
            )
        ");
        
        $insertStmt->execute([
            'phone_number' => $data['phone_number'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'dl_state' => $data['dl_state'],
            'law_type' => strtolower($data['law_type']),
            'offense_number' => $data['offense_number'],
            'price_code' => $data['price_code'],
            'created_by' => $_SESSION['username'] ?? 'system',
            'install_comments' => $data['install_comments']
        ]);
        
        $clientId = $pdo->lastInsertId();
    }
    
    // Record payment transaction
    if (!empty($data['transaction_id'])) {
        $transStmt = $pdo->prepare("
            INSERT INTO payment_transactions (
                customer_id, transaction_id, amount, status, response_message,
                created_at, payment_type, description
            ) VALUES (
                :customer_id, :transaction_id, :amount, 'approved', 'Payment processed successfully',
                NOW(), 'CREDIT_CARD', 'Installation Payment'
            )
        ");
        
        $transStmt->execute([
            'customer_id' => $clientId,
            'transaction_id' => $data['transaction_id'],
            'amount' => $data['transaction_amount']
        ]);
    }
    
    // If new client, create related records
    if ($isNewClient) {
        // Create appointments record (empty placeholder)
        $apptStmt = $pdo->prepare("
            INSERT INTO appointments (
                customer_id, created_at, status
            ) VALUES (
                :customer_id, NOW(), 'Pending'
            )
        ");
        $apptStmt->execute(['customer_id' => $clientId]);
        
        // Create cb_inventory record (placeholder)
        $cbStmt = $pdo->prepare("
            INSERT INTO cb_inventory (
                customer_id, status, created_at
            ) VALUES (
                :customer_id, 'PENDING_ASSIGNMENT', NOW()
            )
        ");
        $cbStmt->execute(['customer_id' => $clientId]);
        
        // Create hs_inventory record (placeholder)
        $hsStmt = $pdo->prepare("
            INSERT INTO hs_inventory (
                customer_id, status, created_at
            ) VALUES (
                :customer_id, 'PENDING_ASSIGNMENT', NOW()
            )
        ");
        $hsStmt->execute(['customer_id' => $clientId]);
        
        // Create vehicle_information record (empty placeholder)
        $vehicleStmt = $pdo->prepare("
            INSERT INTO vehicle_information (
                customer_id, created_at
            ) VALUES (
                :customer_id, NOW()
            )
        ");
        $vehicleStmt->execute(['customer_id' => $clientId]);
    }
    
    // If this came from a lead, update the lead record
    if (!empty($data['lead_id'])) {
        $leadStmt = $pdo->prepare("
            UPDATE leads SET
                status = 'Converted',
                converted_client_id = :client_id,
                updated_at = NOW()
            WHERE id = :lead_id
        ");
        
        $leadStmt->execute([
            'client_id' => $clientId,
            'lead_id' => $data['lead_id']
        ]);
    }
    
    // Commit the transaction
    $pdo->commit();
    
    // Return success response with client ID
    echo json_encode([
        'success' => true,
        'message' => 'Client record created successfully',
        'client_id' => $clientId
    ]);
    
} catch (Exception $e) {
    // Roll back the transaction on error
    $pdo->rollBack();
    
    // Log the error
    error_log("Error creating client record: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>