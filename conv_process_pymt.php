<?php
// Enable error reporting but don't display errors
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set header to JSON
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendJsonResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// Include database connection
try {
    include_once 'db.php';
} catch (Exception $e) {
    sendJsonResponse(false, 'Database connection error: ' . $e->getMessage());
}

// Function to process payment
function processPayment($data) {
    global $pdo;
    
    // Get Authorize.net credentials from system_config
    try {
        $stmt = $pdo->prepare("SELECT value FROM system_config WHERE name IN ('auth_net_api_login_id', 'auth_net_transaction_key')");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $credentials = [];
        foreach ($results as $row) {
            $credentials[$row['name']] = $row['value'];
        }
        
        if (empty($credentials['auth_net_api_login_id']) || empty($credentials['auth_net_transaction_key'])) {
            throw new Exception('Missing Authorize.net credentials');
        }
    } catch (PDOException $e) {
        throw new Exception('Failed to get Authorize.net credentials: ' . $e->getMessage());
    }
    
    // Prepare the JSON request
    $requestData = [
        'createTransactionRequest' => [
            'merchantAuthentication' => [
                'name' => $credentials['auth_net_api_login_id'],
                'transactionKey' => $credentials['auth_net_transaction_key']
            ],
            'refId' => 'REF' . time(),
            'transactionRequest' => [
                'transactionType' => 'authCaptureTransaction',
                'amount' => number_format($data['amount'], 2, '.', ''),
                'payment' => [
                    'creditCard' => [
                        'cardNumber' => str_replace(' ', '', $data['card_number']),
                        'expirationDate' => $data['expiration_date'],
                        'cardCode' => $data['cvv']
                    ]
                ],
                'billTo' => [
                    'firstName' => $data['first_name'],
                    'lastName' => $data['last_name']
                ]
            ]
        ]
    ];
    
    // Convert to JSON
    $jsonRequest = json_encode($requestData);
    
    // Make the API call
    $url = "https://api.authorize.net/xml/v1/request.api";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonRequest);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Log the request and response for debugging
    error_log("Authorize.net Request: " . $jsonRequest);
    error_log("Authorize.net Response: " . $response);
    error_log("HTTP Code: " . $http_code);
    if ($curl_error) {
        error_log("Curl Error: " . $curl_error);
    }
    
    // Parse the response
    $responseData = json_decode($response, true);
    if (!$responseData) {
        throw new Exception('Invalid response from payment processor');
    }
    
    // Check for transaction response
    if (isset($responseData['transactionResponse'])) {
        $transactionResponse = $responseData['transactionResponse'];
        
        if ($transactionResponse['responseCode'] === '1') {
            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'transaction_id' => $transactionResponse['transId']
            ];
        } else {
            $errorMessage = isset($transactionResponse['errors'][0]['errorText']) 
                ? $transactionResponse['errors'][0]['errorText'] 
                : 'Transaction declined';
            return [
                'success' => false,
                'message' => $errorMessage
            ];
        }
    } else {
        // Check for general API errors
        if (isset($responseData['messages']['message'][0]['text'])) {
            return [
                'success' => false,
                'message' => $responseData['messages']['message'][0]['text']
            ];
        }
        return [
            'success' => false,
            'message' => 'Error processing payment'
        ];
    }
}

// Handle the POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get POST data
        $data = [
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'card_number' => $_POST['card_number'] ?? '',
            'expiration_date' => $_POST['expiration_date'] ?? '',
            'cvv' => $_POST['cvv'] ?? '',
            'amount' => $_POST['amount'] ?? 0
        ];
        
        // Validate required fields
        $required_fields = ['first_name', 'last_name', 'card_number', 'expiration_date', 'cvv', 'amount'];
        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            sendJsonResponse(false, 'Missing required fields: ' . implode(', ', $missing_fields));
        }
        
        // Process the payment
        $result = processPayment($data);
        sendJsonResponse($result['success'], $result['message'], $result);
        
    } catch (Exception $e) {
        sendJsonResponse(false, $e->getMessage());
    }
} else {
    sendJsonResponse(false, 'Invalid request method');
}
