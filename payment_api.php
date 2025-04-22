<?php
/**
 * =============================================
 * PAYMENT API
 * =============================================
 * 
 * Handles payment processing requests including:
 * - Credit card payments
 * - Payment method storage
 * - Subscription creation
 * - Invoice payment processing
 * - Create an invoice with a payment
 * 
 * Endpoints:
 * - process_payment: Process a credit card payment
 * - store_payment_method: Store a payment method for future use
 * - create_subscription: Create a recurring subscription
 * - create_invoice_payment: Process an invoice payment (cash, credit, check, other)
 * - create_invoice_with_payment: Create an invoice with a payment
 * 
 * All endpoints require authentication via auth_check.php
 * =============================================
 */
require_once 'auth_check.php';
include 'db.php';
require_once 'PaymentService.php';

// Log function for payment_api
function logPaymentApi($message, $data = null) {
    $logFile = 'payment_api_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    
    // Create the log message with clear markers
    $logMessage = "\n" . str_repeat("=", 80) . "\n";
    $logMessage .= "BEGIN PAYMENT API LOG - $timestamp\n";
    $logMessage .= str_repeat("=", 80) . "\n";
    $logMessage .= "SOURCE: Frontend (invoice_appointment_modal.php)\n";
    $logMessage .= "RECEIVED DATA (JSON):\n";
    
    // Ensure we log all data fields, including payments
    $logData = [
        'request_type' => $data['request_type'] ?? 'N/A',
        'method' => $_SERVER['REQUEST_METHOD'],
        'customer_id' => $data['customer_id'] ?? 'N/A',
        'created_by' => $data['created_by'] ?? 'N/A',
        'location_id' => $data['location_id'] ?? 'N/A',
        'payment_type' => $data['payment_type'] ?? 'N/A',
        'amount' => $data['amount'] ?? 0,
        'services' => array_map(function($service) {
            return [
                'service_id' => $service['service_id'] ?? 'N/A',
                'name' => $service['name'] ?? 'N/A',
                'price' => $service['price'] ?? 0
            ];
        }, $data['services'] ?? []),
        'payments' => $data['payments'] ?? [],
        'rent_total' => $data['rent_total'] ?? 0,
        'service_total' => $data['service_total'] ?? 0,
        'sub_total' => $data['sub_total'] ?? 0,
        'tax_amount' => $data['tax_amount'] ?? 0,
        'invoice_total' => $data['invoice_total'] ?? 0,
        'total_collected' => $data['total_collected'] ?? 0,
        'remaining_balance' => $data['remaining_balance'] ?? 0
    ];
    
    $logMessage .= json_encode($logData, JSON_PRETTY_PRINT) . "\n";
    $logMessage .= str_repeat("=", 80) . "\n";
    $logMessage .= "END PAYMENT API LOG\n";
    $logMessage .= str_repeat("=", 80) . "\n";
    
    // Get existing content and prepend new log
    $existingContent = file_exists($logFile) ? file_get_contents($logFile) : '';
    file_put_contents($logFile, $logMessage . $existingContent);
}

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    logPaymentApi("Error: Method not allowed", ["method" => $_SERVER['REQUEST_METHOD']]);
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
                    $error = "Missing required field: $field";
                    logPaymentApi("Validation error", ["error" => $error]);
                    throw new Exception($error);
                }
            }
            
            // Process the payment
            $result = $paymentService->processPayment(
                $data['cardData'],
                $data['customerData'],
                $data['amount'],
                $data['description'] ?? 'Installation Payment'
            );
            
            logPaymentApi("Process payment result", $result);
            echo json_encode(['success' => $result['success'], 'data' => $result]);
            break;
            
        case 'store_payment_method':
            // Required fields
            $requiredFields = ['cardData', 'customerData'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $error = "Missing required field: $field";
                    logPaymentApi("Validation error", ["error" => $error]);
                    throw new Exception($error);
                }
            }
            
            // Store the payment method
            $result = $paymentService->storePaymentMethod(
                $data['cardData'],
                $data['customerData']
            );
            
            logPaymentApi("Store payment method result", $result);
            echo json_encode(['success' => $result['success'], 'data' => $result]);
            break;
            
        case 'create_subscription':
            // Required fields
            $requiredFields = ['cardData', 'customerData', 'subscriptionData'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $error = "Missing required field: $field";
                    logPaymentApi("Validation error", ["error" => $error]);
                    throw new Exception($error);
                }
            }
            
            // Create subscription
            $result = $paymentService->createSubscription(
                $data['cardData'],
                $data['customerData'],
                $data['subscriptionData']
            );
            
            logPaymentApi("Create subscription result", $result);
            echo json_encode(['success' => $result['success'], 'data' => $result]);
            break;

        case 'create_invoice_payment':
            // Required fields for invoice payment
            $requiredFields = [
                'invoice_id' => 'Invoice ID',
                'customer_id' => 'Customer ID',
                'amount' => 'Amount',
                'payment_type' => 'Payment Type',
                'created_by' => 'Created By',
                'location_id' => 'Location ID'
            ];

            // Validate required fields
            foreach ($requiredFields as $field => $label) {
                if (!isset($data[$field]) || $data[$field] === null) {
                    $error = "Missing required field: {$label}";
                    logPaymentApi("Validation failed", ["error" => $error, "field" => $field]);
                    throw new Exception($error);
                }
            }

            // Validate payment type
            $validPaymentTypes = ['cash', 'credit', 'check', 'other'];
            if (!in_array(strtolower($data['payment_type']), $validPaymentTypes)) {
                $error = "Invalid payment type. Must be one of: " . implode(', ', $validPaymentTypes);
                logPaymentApi("Validation error", ["error" => $error, "payment_type" => $data['payment_type']]);
                throw new Exception($error);
            }

            // If it's a credit card payment, process it first
            if (strtolower($data['payment_type']) === 'credit') {
                if (empty($data['cardData'])) {
                    $error = "Card data is required for credit card payments";
                    logPaymentApi("Validation error", ["error" => $error]);
                    throw new Exception($error);
                }
                
                // Process the credit card payment
                $paymentResult = $paymentService->processPayment(
                    $data['cardData'],
                    ['customer_id' => $data['customer_id']],
                    $data['amount'],
                    $data['description'] ?? 'Invoice Payment'
                );

                if (!$paymentResult['success']) {
                    $error = "Payment processing failed: " . $paymentResult['message'];
                    logPaymentApi("Payment processing error", ["error" => $error, "result" => $paymentResult]);
                    throw new Exception($error);
                }

                // Add payment processing details to the transaction data
                $data['auth_code'] = $paymentResult['authCode'] ?? null;
                $data['response_code'] = $paymentResult['responseCode'] ?? null;
                $data['response_message'] = $paymentResult['message'] ?? null;
            }

            // Create the payment transaction
            $result = $paymentService->createPaymentTransaction($data);
            
            logPaymentApi("Create invoice payment result", $result);
            echo json_encode(['success' => $result['success'], 'data' => $result]);
            break;

        case 'create_invoice_with_payment':
            // Required fields for invoice with payment
            $requiredFields = [
                'customer_id' => 'Customer ID',
                'created_by' => 'Created By',
                'location_id' => 'Location ID',
                'payment_type' => 'Payment Type',
                'amount' => 'Amount',
                'payments' => 'Payments Object'
            ];

            // Validate required fields
            foreach ($requiredFields as $field => $label) {
                if (!isset($data[$field]) || $data[$field] === null) {
                    $error = "Missing required field: {$label}";
                    logPaymentApi("Validation failed", ["error" => $error, "field" => $field]);
                    throw new Exception($error);
                }
            }

            // Validate payments object structure
            if (!is_array($data['payments']) || empty($data['payments'])) {
                $error = "Payments object must be a non-empty array";
                logPaymentApi("Validation failed", ["error" => $error, "payments" => $data['payments']]);
                throw new Exception($error);
            }

            // If it's a credit card payment, validate card data
            if (strtolower($data['payment_type']) === 'credit' && $data['amount'] > 0 && empty($data['cardData'])) {
                $error = "Card data is required for credit card payments";
                logPaymentApi("Validation failed", ["error" => $error]);
                throw new Exception($error);
            }

            // Ensure services is at least an empty array if not provided
            if (!isset($data['services'])) {
                $data['services'] = [];
            }

            // Log the complete request data
            logPaymentApi("Invoice Payment Request", [
                'request_type' => $data['request_type'] ?? 'unknown',
                'method' => $_SERVER['REQUEST_METHOD'],
                'customer_id' => $data['customer_id'] ?? 'N/A',
                'created_by' => $data['created_by'] ?? 'N/A',
                'location_id' => $data['location_id'] ?? 'N/A',
                'payment_type' => $data['payment_type'] ?? 'N/A',
                'amount' => $data['amount'] ?? 0,
                'services' => $data['services'] ?? [],
                'payments' => $data['payments'] ?? [],
                'rent_total' => $data['rent_total'] ?? 0,
                'service_total' => $data['service_total'] ?? 0,
                'sub_total' => $data['sub_total'] ?? 0,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'invoice_total' => $data['invoice_total'] ?? 0,
                'total_collected' => $data['total_collected'] ?? 0,
                'remaining_balance' => $data['remaining_balance'] ?? 0
            ]);

            // Create invoice with payment
            $result = $paymentService->createInvoiceWithPayment($data);
            
            echo json_encode(['success' => $result['success'], 'data' => $result]);
            break;
            
        default:
            $error = "Invalid request type: {$requestType}";
            logPaymentApi("Error", ["error" => $error]);
            throw new Exception($error);
    }
} catch (Exception $e) {
    http_response_code(400);
    $errorResponse = ['success' => false, 'message' => $e->getMessage()];
    logPaymentApi("Error occurred", ["error" => $e->getMessage(), "trace" => $e->getTraceAsString()]);
    echo json_encode($errorResponse);
}
?>