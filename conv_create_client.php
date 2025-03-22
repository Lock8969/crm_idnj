<?php
require_once 'auth_check.php';
include 'db.php';

//=============================================
// Data Structure Documentation
//=============================================
/*
Main Fields:
lead_id, first_name, dl_state, email, install_comments, last_name
law_type, offense_number, phone_number

Fee Fields:
pricing_code, certification_fee, admin_fee, applied_credit, sales_tax

Transaction Object:
transaction.id, transaction.amount, transaction.status, transaction.timestamp
transaction.response.responseCode, transaction.response.authCode
transaction.response.messages[0].code, transaction.response.messages[0].description
*/

//=============================================
// Client Creation Function
//=============================================
function createClientFromPaymentData($data) {
    global $pdo;
    
    try {
        // Prepare the SQL statement for inserting a new client
        $stmt = $pdo->prepare("INSERT INTO client_information (
            first_name, last_name, email, phone_number,  
            dl_state, lead_id, price_code, 
            install_comments, offense_number
        ) VALUES (
            :first_name, :last_name, :email, :phone_number,
            :dl_state, :lead_id, :price_code,
            :install_comments, :offense_number
        )");

        // Execute the statement with the provided data
        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'dl_state' => $data['dl_state'],
            'lead_id' => $data['lead_id'],
            'price_code' => $data['pricing_code'],
            'install_comments' => $data['install_comments'] ?? null,
            'offense_number' => $data['offense_number'] ?? null
        ]);

        // Get the ID of the newly created client
        $client_id = $pdo->lastInsertId();
        
        error_log("New client created successfully with ID: " . $client_id);
        
        return [
            'success' => true,
            'client_id' => $client_id
        ];
        
    } catch (PDOException $e) {
        error_log("Error creating new client: " . $e->getMessage());
        return [
            'success' => false,
            'error' => "An error occurred while creating the client: " . $e->getMessage()
        ];
    }
}

//=============================================
// Lead Update Function
//=============================================
function updateLeadWithClientId($lead_id, $client_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE leads SET converted_client_id = :client_id WHERE id = :lead_id");
        
        $stmt->execute([
            'client_id' => $client_id,
            'lead_id' => $lead_id
        ]);
        
        error_log("Lead updated successfully with client ID: " . $client_id);
        
        return [
            'success' => true
        ];
        
    } catch (PDOException $e) {
        error_log("Error updating lead: " . $e->getMessage());
        return [
            'success' => false,
            'error' => "An error occurred while updating the lead: " . $e->getMessage()
        ];
    }
}

//=============================================
// Payment Transaction Creation Function
//=============================================
function createPaymentTransaction($client_id, $transaction_data) {
    global $pdo;
    
    try {
        // Debug logging
        error_log("Attempting to create payment transaction for client ID: " . $client_id);
        error_log("Transaction data received: " . print_r($transaction_data, true));
        
        // Map response code to status
        $response_code = $transaction_data['response']['messages'][0]['code'];
        $status = 'error'; // default status
        
        switch($response_code) {
            case '1':
                $status = 'approved';
                break;
            case '2':
                $status = 'declined';
                break;
            default:
                $status = 'error';
        }
        
        $stmt = $pdo->prepare("INSERT INTO payment_transactions (
            customer_id, transaction_id, amount, status,
            auth_code, response_code, response_message,
            payment_type, description, certification_fee,
            admin_fee, applied_credit, sales_tax
        ) VALUES (
            :customer_id, :transaction_id, :amount, :status,
            :auth_code, :response_code, :response_message,
            :payment_type, :description, :certification_fee,
            :admin_fee, :applied_credit, :sales_tax
        )");

        // Map the transaction data to the table fields
        $params = [
            'customer_id' => $client_id,
            'transaction_id' => $transaction_data['id'],
            'amount' => $transaction_data['amount'],
            'status' => $status,
            'auth_code' => $transaction_data['response']['authCode'],
            'response_code' => $transaction_data['response']['messages'][0]['code'],
            'response_message' => $transaction_data['response']['messages'][0]['description'] ?? null,
            'payment_type' => 'CREDIT_CARD',
            'description' => 'Installation Payment',
            'certification_fee' => $transaction_data['certification_fee'] ?? 0.00,
            'admin_fee' => $transaction_data['admin_fee'] ?? 0.00,
            'applied_credit' => $transaction_data['applied_credit'] ?? 0.00,
            'sales_tax' => $transaction_data['sales_tax'] ?? 0.00
        ];
        
        // Debug logging
        error_log("Parameters for insert: " . print_r($params, true));
        
        $stmt->execute($params);
        
        error_log("Payment transaction created successfully for client ID: " . $client_id);
        
        return [
            'success' => true
        ];
        
    } catch (PDOException $e) {
        error_log("Error creating payment transaction: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        return [
            'success' => false,
            'error' => "An error occurred while creating the payment transaction: " . $e->getMessage()
        ];
    }
}

//=============================================
// Inventory Records Creation Function
//=============================================
function createInventoryRecords($client_id) {
    global $pdo;
    
    try {
        // Create vehicle_information record
        $stmt = $pdo->prepare("INSERT INTO vehicle_information (customer_id) VALUES (:customer_id)");
        $stmt->execute(['customer_id' => $client_id]);
        
        error_log("Vehicle information record created successfully for client ID: " . $client_id);
        
        return [
            'success' => true
        ];
        
    } catch (PDOException $e) {
        error_log("Error creating vehicle information record: " . $e->getMessage());
        return [
            'success' => false,
            'error' => "An error occurred while creating vehicle information record: " . $e->getMessage()
        ];
    }
}

//=============================================
// API Endpoint Handler
//=============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON data from the request body
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        exit;
    }
    
    // Create the client
    $result = createClientFromPaymentData($data);
    
    // If client creation was successful, update the lead and create payment transaction
    if ($result['success']) {
        // Update lead
        $updateResult = updateLeadWithClientId($data['lead_id'], $result['client_id']);
        if (!$updateResult['success']) {
            error_log("Warning: Lead update failed after client creation");
        }
        
        // Create payment transaction
        $transactionResult = createPaymentTransaction($result['client_id'], $data['transaction']);
        if (!$transactionResult['success']) {
            error_log("Warning: Payment transaction creation failed after client creation");
        }
        
        // Create inventory records
        $inventoryResult = createInventoryRecords($result['client_id']);
        if (!$inventoryResult['success']) {
            error_log("Warning: Inventory records creation failed after client creation");
        }
    }
    
    // Send the response
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}
?> 