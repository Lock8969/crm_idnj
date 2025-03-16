<?php
/**
 * PaymentService.php
 * Handles payment processing using Authorize.net API
 */
class PaymentService {
    private $apiLoginId;
    private $transactionKey;
    private $sandboxMode;
    private $pdo;
    
    public function __construct($pdo, $apiLoginId = null, $transactionKey = null, $sandboxMode = true) {
        $this->pdo = $pdo;
        
        // Use provided credentials or load from configuration
        $this->apiLoginId = $apiLoginId ?: $this->getConfigValue('auth_net_api_login_id');
        $this->transactionKey = $transactionKey ?: $this->getConfigValue('auth_net_transaction_key');
        $this->sandboxMode = $sandboxMode;
    }
    
    /**
     * Process a credit card payment
     * 
     * @param array $cardData Credit card data (number, expiry, cvv)
     * @param array $customerData Customer information
     * @param float $amount Amount to charge
     * @param string $description Description of the transaction
     * @return array Response with success status and transaction details
     */
    public function processPayment($cardData, $customerData, $amount, $description = 'Installation Payment') {
        // Validate input data
        $this->validateCardData($cardData);
        $this->validateCustomerData($customerData);
        
        if ($amount <= 0) {
            throw new Exception("Invalid payment amount");
        }
        
        // Format amount to two decimal places
        $amount = number_format($amount, 2, '.', '');
        
        // Prepare the API request
        $requestData = $this->buildAuthnetRequest($cardData, $customerData, $amount, $description);
        
        // Send request to Authorize.net
        $response = $this->sendAuthnetRequest($requestData);
        
        // Process the response
        $result = $this->processAuthnetResponse($response);
        
        // Log the transaction (but never log full card details)
        $this->logTransaction($result, $customerData, $amount);
        
        return $result;
    }
    
    /**
     * Process a payment for recurring billing (subscription)
     * 
     * @param array $cardData Credit card data
     * @param array $customerData Customer information
     * @param array $subscriptionData Subscription details
     * @return array Response with success status and subscription details
     */
    public function createSubscription($cardData, $customerData, $subscriptionData) {
        // Validate input data
        $this->validateCardData($cardData);
        $this->validateCustomerData($customerData);
        $this->validateSubscriptionData($subscriptionData);
        
        // Prepare the API request for subscription
        $requestData = $this->buildAuthnetSubscriptionRequest($cardData, $customerData, $subscriptionData);
        
        // Send request to Authorize.net
        $response = $this->sendAuthnetRequest($requestData);
        
        // Process the response
        $result = $this->processAuthnetSubscriptionResponse($response);
        
        // Log the subscription creation
        $this->logSubscription($result, $customerData, $subscriptionData);
        
        return $result;
    }
    
    /**
     * Store a payment method for future use
     * 
     * @param array $cardData Credit card data
     * @param array $customerData Customer information
     * @return array Response with customer profile and payment profile IDs
     */
    public function storePaymentMethod($cardData, $customerData) {
        // Validate input data
        $this->validateCardData($cardData);
        $this->validateCustomerData($customerData);
        
        // Prepare the customer profile request
        $requestData = $this->buildAuthnetCustomerProfileRequest($cardData, $customerData);
        
        // Send request to Authorize.net
        $response = $this->sendAuthnetRequest($requestData);
        
        // Process the response
        $result = $this->processAuthnetCustomerProfileResponse($response);
        
        // If successful, store the profile IDs in our database
        if ($result['success'] && isset($result['profileIds'])) {
            $this->storeCustomerProfileIds(
                $customerData['customer_id'],
                $result['profileIds']['customerProfileId'],
                $result['profileIds']['customerPaymentProfileId']
            );
        }
        
        return $result;
    }
    
    /**
     * Build the main Authorize.net API request
     */
    private function buildAuthnetRequest($cardData, $customerData, $amount, $description) {
        // Create a minimal request with only required fields
        $requestData = [
            'createTransactionRequest' => [
                'merchantAuthentication' => [
                    'name' => $this->apiLoginId,
                    'transactionKey' => $this->transactionKey
                ],
                'refId' => 'REF' . time(),
                'transactionRequest' => [
                    'transactionType' => 'authCaptureTransaction',
                    'amount' => $amount,
                    'payment' => [
                        'creditCard' => [
                            'cardNumber' => $cardData['cardNumber'],
                            'expirationDate' => $cardData['expirationYear'] . '-' . $cardData['expirationMonth'],
                            'cardCode' => $cardData['cvv']
                        ]
                    ]
                ]
            ]
        ];
        
        return $requestData;
    }
    
    /**
     * Build request for creating a subscription
     */
    private function buildAuthnetSubscriptionRequest($cardData, $customerData, $subscriptionData) {
        // Create an array with all required fields for a subscription
        $requestData = [
            'ARBCreateSubscriptionRequest' => [
                'merchantAuthentication' => [
                    'name' => $this->apiLoginId,
                    'transactionKey' => $this->transactionKey
                ],
                'refId' => 'SUB' . time(),
                'subscription' => [
                    'name' => $subscriptionData['name'],
                    'paymentSchedule' => [
                        'interval' => [
                            'length' => $subscriptionData['intervalLength'],
                            'unit' => $subscriptionData['intervalUnit'] // days, months
                        ],
                        'startDate' => $subscriptionData['startDate'],
                        'totalOccurrences' => $subscriptionData['totalOccurrences'] ?? '9999',
                        'trialOccurrences' => $subscriptionData['trialOccurrences'] ?? '0'
                    ],
                    'amount' => $subscriptionData['amount'],
                    'trialAmount' => $subscriptionData['trialAmount'] ?? '0.00',
                    'payment' => [
                        'creditCard' => [
                            'cardNumber' => $cardData['cardNumber'],
                            'expirationDate' => $cardData['expirationYear'] . '-' . $cardData['expirationMonth'],
                            'cardCode' => $cardData['cvv']
                        ]
                    ],
                    'customer' => [
                        'id' => $customerData['customer_id'] ?? '',
                        'email' => $customerData['email'] ?? ''
                    ],
                    'billTo' => [
                        'firstName' => $customerData['firstName'] ?? '',
                        'lastName' => $customerData['lastName'] ?? '',
                        'address' => $customerData['address'] ?? '',
                        'city' => $customerData['city'] ?? '',
                        'state' => $customerData['state'] ?? '',
                        'zip' => $customerData['zip'] ?? '',
                        'country' => 'USA'
                    ]
                ]
            ]
        ];
        
        return $requestData;
    }
    
    /**
     * Build request for storing a customer payment profile
     */
    private function buildAuthnetCustomerProfileRequest($cardData, $customerData) {
        // Create an array with all required fields for creating a customer profile
        $requestData = [
            'createCustomerProfileRequest' => [
                'merchantAuthentication' => [
                    'name' => $this->apiLoginId,
                    'transactionKey' => $this->transactionKey
                ],
                'profile' => [
                    'merchantCustomerId' => 'MC' . $customerData['customer_id'],
                    'description' => 'Profile for ' . $customerData['firstName'] . ' ' . $customerData['lastName'],
                    'email' => $customerData['email'] ?? '',
                    'paymentProfiles' => [
                        'customerType' => 'individual',
                        'payment' => [
                            'creditCard' => [
                                'cardNumber' => $cardData['cardNumber'],
                                'expirationDate' => $cardData['expirationYear'] . '-' . $cardData['expirationMonth'],
                                'cardCode' => $cardData['cvv']
                            ]
                        ],
                        'billTo' => [
                            'firstName' => $customerData['firstName'] ?? '',
                            'lastName' => $customerData['lastName'] ?? '',
                            'address' => $customerData['address'] ?? '',
                            'city' => $customerData['city'] ?? '',
                            'state' => $customerData['state'] ?? '',
                            'zip' => $customerData['zip'] ?? '',
                            'country' => 'USA'
                        ]
                    ]
                ],
                'validationMode' => 'liveMode'
            ]
        ];
        
        return $requestData;
    }
    
    /**
     * Send request to Authorize.net API
     */
    private function sendAuthnetRequest($requestData) {
        // Determine which endpoint to use based on sandbox mode
        $endpoint = $this->sandboxMode 
            ? 'https://apitest.authorize.net/xml/v1/request.api' 
            : 'https://api.authorize.net/xml/v1/request.api';
        
        // Convert request data to JSON
        $jsonRequest = json_encode($requestData);
        
        // Log the exact request being sent
        error_log("Authorize.net request: " . $jsonRequest);
        
        // Initialize cURL session
        $ch = curl_init($endpoint);
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonRequest);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Should be true in production with proper certs
        
        // Execute the request
        $response = curl_exec($ch);
        error_log("Raw Authorize.net response: " . $response);
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        error_log("HTTP response code: " . $httpCode);
        
        // Rest of function remains the same...
    }
    
    /**
     * Process the Authorize.net response for a transaction
     */
    private function processAuthnetResponse($response) {
        $result = [
            'success' => false,
            'message' => 'Unknown error occurred',
            'transactionId' => null,
            'authCode' => null,
            'responseCode' => null,
            'responseText' => null
        ];
        
        // Check if response contains required fields
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
                // Transaction not approved
                $errorText = '';
                
                if (isset($transactionResponse['errors']) && is_array($transactionResponse['errors'])) {
                    foreach ($transactionResponse['errors'] as $error) {
                        $errorText .= $error['errorText'] ?? 'Unknown error';
                    }
                }
                
                $result['message'] = $errorText ?: 'Transaction declined';
            }
            
            $result['responseCode'] = $responseCode;
            $result['responseText'] = $this->getResponseCodeText($responseCode);
        } elseif (isset($response['messages']) && isset($response['messages']['resultCode'])) {
            // Some API calls return a different format
            if ($response['messages']['resultCode'] === 'Ok') {
                $result['success'] = true;
                $result['message'] = 'Request successful';
                
                // Extract any other relevant information from the response
                if (isset($response['customerProfileId'])) {
                    $result['customerProfileId'] = $response['customerProfileId'];
                }
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
        
        return $result;
    }
    
    /**
     * Process the Authorize.net response for a subscription
     */
    private function processAuthnetSubscriptionResponse($response) {
        $result = [
            'success' => false,
            'message' => 'Unknown error occurred',
            'subscriptionId' => null
        ];
        
        if (isset($response['ARBCreateSubscriptionResponse'])) {
            $subscriptionResponse = $response['ARBCreateSubscriptionResponse'];
            
            if (isset($subscriptionResponse['messages']['resultCode']) && 
                $subscriptionResponse['messages']['resultCode'] === 'Ok') {
                $result['success'] = true;
                $result['message'] = 'Subscription created successfully';
                $result['subscriptionId'] = $subscriptionResponse['subscriptionId'] ?? '';
            } else {
                $errorMessage = '';
                if (isset($subscriptionResponse['messages']['message']) && 
                    is_array($subscriptionResponse['messages']['message'])) {
                    foreach ($subscriptionResponse['messages']['message'] as $message) {
                        $errorMessage .= ($message['text'] ?? 'Unknown error') . ' ';
                    }
                }
                $result['message'] = trim($errorMessage) ?: 'Subscription creation failed';
            }
        }
        
        return $result;
    }
    
    /**
     * Process the Authorize.net response for a customer profile
     */
    private function processAuthnetCustomerProfileResponse($response) {
        $result = [
            'success' => false,
            'message' => 'Unknown error occurred',
            'profileIds' => null
        ];
        
        if (isset($response['createCustomerProfileResponse'])) {
            $profileResponse = $response['createCustomerProfileResponse'];
            
            if (isset($profileResponse['messages']['resultCode']) && 
                $profileResponse['messages']['resultCode'] === 'Ok') {
                $result['success'] = true;
                $result['message'] = 'Customer profile created successfully';
                
                // Extract profile IDs
                $result['profileIds'] = [
                    'customerProfileId' => $profileResponse['customerProfileId'] ?? '',
                    'customerPaymentProfileId' => $profileResponse['customerPaymentProfileIdList'][0] ?? ''
                ];
            } else {
                $errorMessage = '';
                if (isset($profileResponse['messages']['message']) && 
                    is_array($profileResponse['messages']['message'])) {
                    foreach ($profileResponse['messages']['message'] as $message) {
                        $errorMessage .= ($message['text'] ?? 'Unknown error') . ' ';
                    }
                }
                $result['message'] = trim($errorMessage) ?: 'Customer profile creation failed';
            }
        }
        
        return $result;
    }
    
    /**
     * Store customer profile IDs in the database for future reference
     */
    private function storeCustomerProfileIds($customerId, $customerProfileId, $customerPaymentProfileId) {
        try {
            // First check if customer already has a profile
            $checkStmt = $this->pdo->prepare("
                SELECT id FROM payment_profiles 
                WHERE customer_id = ?
            ");
            $checkStmt->execute([$customerId]);
            
            if ($checkStmt->rowCount() > 0) {
                // Update existing profile
                $stmt = $this->pdo->prepare("
                    UPDATE payment_profiles 
                    SET authnet_customer_profile_id = ?,
                        authnet_payment_profile_id = ?,
                        updated_at = NOW()
                    WHERE customer_id = ?
                ");
                $stmt->execute([$customerProfileId, $customerPaymentProfileId, $customerId]);
            } else {
                // Insert new profile
                $stmt = $this->pdo->prepare("
                    INSERT INTO payment_profiles 
                    (customer_id, authnet_customer_profile_id, authnet_payment_profile_id)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$customerId, $customerProfileId, $customerPaymentProfileId]);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Error storing customer profile IDs: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log transaction details for audit purposes
     */
    private function logTransaction($result, $customerData, $amount) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO payment_transactions 
                (customer_id, transaction_id, amount, status, auth_code, response_code, response_message)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $customerData['customer_id'] ?? null,
                $result['transactionId'] ?? null,
                $amount,
                $result['success'] ? 'approved' : 'declined',
                $result['authCode'] ?? null,
                $result['responseCode'] ?? null,
                $result['message'] ?? null
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Error logging transaction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log subscription details
     */
    private function logSubscription($result, $customerData, $subscriptionData) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO subscriptions 
                (customer_id, subscription_id, amount, interval_length, interval_unit, status, start_date)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $customerData['customer_id'] ?? null,
                $result['subscriptionId'] ?? null,
                $subscriptionData['amount'],
                $subscriptionData['intervalLength'],
                $subscriptionData['intervalUnit'],
                $result['success'] ? 'active' : 'failed',
                $subscriptionData['startDate']
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Error logging subscription: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get text description for response code
     */
    private function getResponseCodeText($code) {
        $responses = [
            '1' => 'Approved',
            '2' => 'Declined',
            '3' => 'Error',
            '4' => 'Held for Review'
        ];
        
        return $responses[$code] ?? 'Unknown';
    }
    
    /**
     * Validate credit card data
     */
    private function validateCardData($cardData) {
        if (empty($cardData['cardNumber']) || !is_numeric($cardData['cardNumber'])) {
            throw new Exception("Invalid card number");
        }
        
        if (empty($cardData['expirationMonth']) || !is_numeric($cardData['expirationMonth']) || 
            $cardData['expirationMonth'] < 1 || $cardData['expirationMonth'] > 12) {
            throw new Exception("Invalid expiration month");
        }
        
        if (empty($cardData['expirationYear']) || !is_numeric($cardData['expirationYear'])) {
            throw new Exception("Invalid expiration year");
        }
        
        if (empty($cardData['cvv']) || !is_numeric($cardData['cvv'])) {
            throw new Exception("Invalid CVV");
        }
        
        // Check if card is expired
        $currentMonth = date('n');
        $currentYear = date('Y');
        
        if ($cardData['expirationYear'] < $currentYear || 
            ($cardData['expirationYear'] == $currentYear && $cardData['expirationMonth'] < $currentMonth)) {
            throw new Exception("Card has expired");
        }
    }
    
    /**
     * Validate customer data
     */
    private function validateCustomerData($customerData) {
        if (empty($customerData['firstName']) || empty($customerData['lastName'])) {
            throw new Exception("Customer name is required");
        }
        
        if (!empty($customerData['email']) && !filter_var($customerData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
    }
    
    /**
     * Validate subscription data
     */
    private function validateSubscriptionData($subscriptionData) {
        if (empty($subscriptionData['amount']) || !is_numeric($subscriptionData['amount']) || $subscriptionData['amount'] <= 0) {
            throw new Exception("Invalid subscription amount");
        }
        
        if (empty($subscriptionData['intervalLength']) || !is_numeric($subscriptionData['intervalLength']) || $subscriptionData['intervalLength'] <= 0) {
            throw new Exception("Invalid interval length");
        }
        
        if (empty($subscriptionData['intervalUnit']) || !in_array($subscriptionData['intervalUnit'], ['days', 'months'])) {
            throw new Exception("Invalid interval unit");
        }
        
        if (empty($subscriptionData['startDate'])) {
            throw new Exception("Start date is required");
        }
    }
    
    /**
     * Get configuration value from the database
     */
    private function getConfigValue($key) {
        try {
            $stmt = $this->pdo->prepare("SELECT value FROM system_config WHERE name = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['value'] : null;
        } catch (PDOException $e) {
            error_log("Error getting config value: " . $e->getMessage());
            return null;
        }
    }
}
?>