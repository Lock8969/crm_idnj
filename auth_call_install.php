<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set up error logging
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/payment_error.log'); // Log to a file

// Set Content-Type header immediately
header('Content-Type: application/json');

error_log("auth_call_install.php was called at " . date('Y-m-d H:i:s'));

// Create a function for logging
function logPaymentActivity($message, $data = null) {
    $logEntry = date('[Y-m-d H:i:s]') . ' ' . $message;
    
    if ($data !== null) {
        // Remove sensitive data before logging
        if (is_array($data)) {
            if (isset($data['cardNumber'])) {
                $data['cardNumber'] = substr($data['cardNumber'], 0, 4) . '********' . substr($data['cardNumber'], -4);
            }
            if (isset($data['cvv'])) {
                $data['cvv'] = '***';
            }
            $logEntry .= ' - ' . json_encode($data);
        } else {
            $logEntry .= ' - ' . $data;
        }
    }
    
    // Log to file
    error_log($logEntry);
}

// Log the incoming request
$request_body = file_get_contents('php://input');
logPaymentActivity('Payment request received', json_decode($request_body, true));

try {
    // Your existing payment processing code will go here
    
    // Log successful processing
    logPaymentActivity("Payment processing completed successfully");
    
    // Ensure proper JSON response is sent
    $response = [
        'success' => true,
        'data' => [
            'message' => 'Payment processed successfully',
            'transactionId' => 'test123' // Replace with actual transaction ID from your payment process
        ]
    ];
    
    // Log the response being sent
    logPaymentActivity("Sending response", $response);
    
    // Output the JSON response
    echo json_encode($response);
    exit;
} catch (Exception $e) {
    // Log any exceptions
    logPaymentActivity("Payment processing error: " . $e->getMessage());
    
    // Return error response
    $error_response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    echo json_encode($error_response);
    exit;
}

/**
 * simple_payment_processor.php
 * A streamlined API for processing credit card payments through Authorize.net
 * Path: auth_apis/auth_call_install.php
 */

// Log the start of the request
logPaymentActivity('Payment request started');

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Log the request method
    logPaymentActivity('Request method', $_SERVER['REQUEST_METHOD']);
    
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    // Get the request data
    $rawInput = file_get_contents('php://input');
    logPaymentActivity('Raw input received', strlen($rawInput) . ' bytes');
    
    // Parse the JSON input
    $data = json_decode($rawInput, true);
    
    // Check if JSON parsing failed
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }
    
    logPaymentActivity('Input parsed successfully', ['fields' => array_keys($data)]);
    
    // Validate required fields
    $requiredFields = ['firstName', 'lastName', 'cardNumber', 'expirationMonth', 
                      'expirationYear', 'cvv', 'amount'];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    logPaymentActivity('All required fields present');
    
    // Try to get database connection
    try {
        require_once '../db.php';
        logPaymentActivity('Database connection established');
    } catch (Exception $e) {
        throw new Exception('Database connection failed: ' . $e->getMessage());
    }
    
    // Get Authorize.net credentials from the database
    try {
        $stmt = $pdo->prepare("SELECT name, value FROM system_config WHERE name IN ('auth_net_api_login_id', 'auth_net_transaction_key')");
        $stmt->execute();
        $config = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['name']] = $row['value'];
        }
        
        if (empty($config['auth_net_api_login_id']) || empty($config['auth_net_transaction_key'])) {
            throw new Exception("Authorize.net credentials not found in system_config");
        }
        
        logPaymentActivity('Authorize.net credentials retrieved');
    } catch (Exception $e) {
        throw new Exception('Error getting Authorize.net credentials: ' . $e->getMessage());
    }
    
    // Set API credentials and endpoint
    $apiLoginId = $config['auth_net_api_login_id'];
    $transactionKey = $config['auth_net_transaction_key'];
    
    // Determine if we're in sandbox or production
    $sandboxMode = true; // Set to false for production
    $endpoint = $sandboxMode 
        ? 'https://apitest.authorize.net/xml/v1/request.api' 
        : 'https://api.authorize.net/xml/v1/request.api';
    
    logPaymentActivity('Using endpoint', $endpoint);
    
    // Format request data
    $amount = number_format((float)$data['amount'], 2, '.', '');
    $expirationDate = $data['expirationYear'] . '-' . sprintf('%02d', $data['expirationMonth']);
    
    // Create simplified request payload for Authorize.net
    $payload = [
        'createTransactionRequest' => [
            'merchantAuthentication' => [
                'name' => $apiLoginId,
                'transactionKey' => $transactionKey
            ],
            'refId' => 'REF' . time(),
            'transactionRequest' => [
                'transactionType' => 'authCaptureTransaction',
                'amount' => $amount,
                'payment' => [
                    'creditCard' => [
                        'cardNumber' => preg_replace('/\D/', '', $data['cardNumber']),
                        'expirationDate' => $expirationDate,
                        'cardCode' => $data['cvv']
                    ]
                ],
                'billTo' => [
                    'firstName' => $data['firstName'],
                    'lastName' => $data['lastName']
                ]
            ]
        ]
    ];
    
    logPaymentActivity('Request payload created');
    
    // Convert to JSON
    $jsonPayload = json_encode($payload);
    
    if ($jsonPayload === false) {
        throw new Exception('JSON encoding failed: ' . json_last_error_msg());
    }
    
    logPaymentActivity('Payload JSON encoded successfully', strlen($jsonPayload) . ' bytes');
    
    // Initialize cURL
    $ch = curl_init($endpoint);
    if ($ch === false) {
        throw new Exception('Failed to initialize cURL');
    }
    
    logPaymentActivity('cURL initialized');
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sandboxMode ? false : true);
    
    // Execute request
    logPaymentActivity('Sending request to Authorize.net');
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    logPaymentActivity('Response received', 'HTTP code: ' . $httpCode);
    
    // Check for cURL errors
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        logPaymentActivity('cURL error', $error);
        throw new Exception("cURL Error: " . $error);
    }
    
    curl_close($ch);
    logPaymentActivity('cURL closed');
    
    // Log the raw response (truncated if very large)
    $logResponse = (strlen($response) > 1000) ? substr($response, 0, 1000) . '...[truncated]' : $response;
    logPaymentActivity('Raw response', $logResponse);
    
    // Parse response
    $responseData = json_decode($response, true);
    
    // If response cannot be parsed
    if (json_last_error() !== JSON_ERROR_NONE) {
        logPaymentActivity('JSON parsing error', json_last_error_msg());
        throw new Exception('Error parsing response: ' . json_last_error_msg());
    }
    
    logPaymentActivity('Response JSON parsed successfully');
    
    // Process the response
    $result = processAuthNetResponse($responseData);
    logPaymentActivity('Response processed', $result);
    
    // Log transaction
    logTransaction($pdo, $result, $data);
    logPaymentActivity('Transaction logged to database');
    
    // Return result
    echo json_encode($result);
    logPaymentActivity('Response sent to client');
    
} catch (Exception $e) {
    // Log the error
    logPaymentActivity('Error caught', $e->getMessage());
    
    // Return error response
    $errorResponse = ['success' => false, 'message' => $e->getMessage()];
    echo json_encode($errorResponse);
    logPaymentActivity('Error response sent');
}

/**
 * Process Authorize.net API response
 */
function processAuthNetResponse($response) {
    logPaymentActivity('Processing Authorize.net response');
    
    $result = [
        'success' => false,
        'message' => 'Unknown error occurred',
        'transactionId' => null,
        'authCode' => null,
        'responseCode' => null
    ];
    
    // Extract relevant data
    if (isset($response['transactionResponse'])) {
        $transactionResponse = $response['transactionResponse'];
        
        // Get response code (1 = Approved, 2 = Declined, 3 = Error, 4 = Held for Review)
        $responseCode = $transactionResponse['responseCode'] ?? '';
        
        if ($responseCode == '1') { // Approved
            $result['success'] = true;
            $result['message'] = 'Transaction approved';
            $result['transactionId'] = $transactionResponse['transId'] ?? '';
            $result['authCode'] = $transactionResponse['authCode'] ?? '';
            $result['avsResultCode'] = $transactionResponse['avsResultCode'] ?? '';
            $result['cvvResultCode'] = $transactionResponse['cvvResultCode'] ?? '';
        } else {
            // Transaction not approved - get error details
            $errorText = '';
            
            if (isset($transactionResponse['errors']) && is_array($transactionResponse['errors'])) {
                foreach ($transactionResponse['errors'] as $error) {
                    $errorText .= ($error['errorText'] ?? 'Unknown error') . ' ';
                }
            }
            
            $result['message'] = trim($errorText) ?: 'Transaction declined';
        }
        
        $result['responseCode'] = $responseCode;
        $result['responseText'] = getResponseCodeText($responseCode);
    } else if (isset($response['messages']['resultCode'])) {
        // Alternative response format
        if ($response['messages']['resultCode'] === 'Ok') {
            $result['success'] = true;
            $result['message'] = 'Request successful';
        } else {
            $errorMessage = '';
            if (isset($response['messages']['message']) && is_array($response['messages']['message'])) {
                foreach ($response['messages']['message'] as $message) {
                    $errorMessage .= ($message['text'] ?? 'Unknown error') . ' ';
                }
            }
            $result['message'] = trim($errorMessage) ?: 'Request failed';
        }
    }
    
    logPaymentActivity('Response processing complete', ['success' => $result['success']]);
    return $result;
}

/**
 * Get text description for response code
 */
function getResponseCodeText($code) {
    $responses = [
        '1' => 'Approved',
        '2' => 'Declined',
        '3' => 'Error',
        '4' => 'Held for Review'
    ];
    
    return $responses[$code] ?? 'Unknown';
}

/**
 * Log transaction to database
 */
function logTransaction($pdo, $result, $data) {
    try {
        logPaymentActivity('Logging transaction to database');
        
        $stmt = $pdo->prepare("
            INSERT INTO payment_transactions 
            (customer_id, transaction_id, amount, status, auth_code, response_code, response_message, created_at, payment_type, description)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'CREDIT_CARD', ?)
        ");
        
        $stmt->execute([
            $data['customerId'] ?? null,
            $result['transactionId'] ?? null,
            $data['amount'],
            $result['success'] ? 'approved' : 'declined',
            $result['authCode'] ?? null,
            $result['responseCode'] ?? null,
            $result['message'] ?? null,
            $data['description'] ?? 'Credit Card Payment'
        ]);
        
        logPaymentActivity('Transaction logged successfully');
        return true;
    } catch (PDOException $e) {
        // Log error but don't fail the response
        logPaymentActivity('Error logging transaction to database', $e->getMessage());
        return false;
    }
}
?>