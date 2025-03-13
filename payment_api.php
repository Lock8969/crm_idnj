<?php
/**
 * payment_api.php
 * Handles payment processing requests
 */
require_once 'auth_check.php';
include 'db.php';
require_once 'PaymentService.php';

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

// Check for request type
$requestType = $data['request_type'] ?? '';

try {
    // Create payment service
    $paymentService = new PaymentService($pdo);
    
    switch ($requestType) {
        case 'process_payment':
            // Required fields
            $requiredFields = ['cardData', 'customerData', 'amount'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Process the payment
            $result = $paymentService->processPayment(
                $data['cardData'],
                $data['customerData'],
                $data['amount'],
                $data['description'] ?? 'Installation Payment'
            );
            
            echo json_encode(['success' => $result['success'], 'data' => $result]);
            break;
            
        case 'store_payment_method':
            // Required fields
            $requiredFields = ['cardData', 'customerData'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Store the payment method
            $result = $paymentService->storePaymentMethod(
                $data['cardData'],
                $data['customerData']
            );
            
            echo json_encode(['success' => $result['success'], 'data' => $result]);
            break;
            
        case 'create_subscription':
            // Required fields
            $requiredFields = ['cardData', 'customerData', 'subscriptionData'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Create subscription
            $result = $paymentService->createSubscription(
                $data['cardData'],
                $data['customerData'],
                $data['subscriptionData']
            );
            
            echo json_encode(['success' => $result['success'], 'data' => $result]);
            break;
            
        default:
            throw new Exception("Invalid request type");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>