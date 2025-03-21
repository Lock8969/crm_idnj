<?php
// Set to show all errors to help with debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include your database connection
include 'db.php';

// Function to return error in JSON format
function returnError($message) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

// Function to get Authorize.net credentials
function getCredentials() {
    try {
        // Re-include to ensure it's in function scope
        include 'db.php';
        global $pdo;
        
        // Get login ID
        $stmt = $pdo->prepare("SELECT value FROM system_config WHERE id = 4 AND name = 'sb_auth_net_api_login_id'");
        $stmt->execute();
        $login_id_row = $stmt->fetch();
        $login_id = $login_id_row ? $login_id_row['value'] : '';
        
        // Get transaction key
        $stmt = $pdo->prepare("SELECT value FROM system_config WHERE id = 5 AND name = 'sb_auth_net_transaction_key'");
        $stmt->execute();
        $transaction_key_row = $stmt->fetch();
        $transaction_key = $transaction_key_row ? $transaction_key_row['value'] : '';
        
        if (empty($login_id) || empty($transaction_key)) {
            returnError("Could not retrieve Authorize.net credentials");
        }
        
        return [
            'login_id' => $login_id,
            'transaction_key' => $transaction_key
        ];
    } catch (Exception $e) {
        returnError("Error getting credentials: " . $e->getMessage());
    }
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnError('Invalid request method');
}

// Get form data from the POST request
$firstName = isset($_POST['first_name']) ? $_POST['first_name'] : '';
$lastName = isset($_POST['last_name']) ? $_POST['last_name'] : '';
$cardNumber = isset($_POST['card_number']) ? $_POST['card_number'] : '';
$expirationDate = isset($_POST['expiration_date']) ? $_POST['expiration_date'] : '';
$cvv = isset($_POST['cvv']) ? $_POST['cvv'] : '';
$amount = isset($_POST['amount']) ? $_POST['amount'] : '';

// Validate input
if (empty($firstName) || empty($lastName) || empty($cardNumber) || 
    empty($expirationDate) || empty($cvv) || empty($amount)) {
    returnError('Missing required payment information');
}

// Basic validation
if (!is_numeric(str_replace(['$', ','], '', $amount))) {
    returnError('Invalid amount format');
}

// Clean amount (remove $ and commas)
$amount = str_replace(['$', ','], '', $amount);

// Format amount to 2 decimal places
$amount = number_format((float)$amount, 2, '.', '');

// Get credentials
$credentials = getCredentials();

// Create JSON request for Authorize.net
$randomNum = mt_rand(2000, 9999);
$timestamp = time();
$refId = $timestamp . $randomNum;

$jsonRequest = json_encode([
    'createTransactionRequest' => [
        'merchantAuthentication' => [
            'name' => $credentials['login_id'],
            'transactionKey' => $credentials['transaction_key']
        ],
        'refId' => $refId,
        'transactionRequest' => [
            'transactionType' => 'authCaptureTransaction',
            'amount' => $amount,
            'payment' => [
                'creditCard' => [
                    'cardNumber' => $cardNumber,
                    'expirationDate' => $expirationDate,
                    'cardCode' => $cvv
                ]
            ],
            'billTo' => [
                'firstName' => $firstName,
                'lastName' => $lastName
            ]
        ]
    ]
]);

// Set endpoint
$endpointUrl = 'https://apitest.authorize.net/xml/v1/request.api';

// Initialize cURL
$ch = curl_init($endpointUrl);

// Basic cURL options
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonRequest);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Execute request
$response = curl_exec($ch);

// Check for curl errors
if (curl_errno($ch)) {
    curl_close($ch);
    returnError("cURL Error: " . curl_error($ch));
}

curl_close($ch);

//LOGGING TEMORARY!
$logFile = 'authorize_log.txt';

// FILE_APPEND flag preserves existing content
// The file will be created automatically if it doesn't exist
file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Response: " . $response . "\n\n", FILE_APPEND);

// Log the request information
file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Request: " . $jsonRequest . "\n\n", FILE_APPEND);
// Simply return the raw response without interpreting it
header('Content-Type: application/json');
echo $response;
exit;
?>