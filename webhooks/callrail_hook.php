<?php
/**
 * CALLRAIL WEBHOOK HANDLER
 * 
 * This script handles incoming webhook calls from CallRail when a call is received.
 * It processes the call data, logs it, and stores it in the database.
 * 
 * Key Functions:
 * 1. Receives and validates incoming call data from CallRail
 * 2. Formats phone numbers to standard (XXX) XXX-XXXX format
 * 3. Determines the source of the call based on tracking numbers
 * 4. Processes CallRail-specific fields (first_call, prior_calls, resource_id)
 * 5. Logs the call data for debugging and auditing
 * 6. Stores the call information in the database
 */

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/webhook_errors.log');

/**
 * Phone Number Formatter
 * 
 * Converts phone numbers to a standard format: (XXX) XXX-XXXX
 * Handles various input formats and removes non-digit characters
 * 
 * @param string $phone The phone number to format
 * @return string Formatted phone number
 */
function formatPhoneNumber($phone) {
    // Remove any non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // If number starts with 1, remove it (US country code)
    if (strlen($phone) === 11 && $phone[0] === '1') {
        $phone = substr($phone, 1);
    }
    
    // Format as (XXX) XXX-XXXX
    if (strlen($phone) === 10) {
        return '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6);
    }
    
    // Return original if not 10 digits
    return $phone;
}

try {
    // Get the raw input data from CallRail
    $raw_data = file_get_contents('php://input');
    
    // Log the raw input for debugging purposes
    error_log("CallRail Raw Input: " . $raw_data);
    
    // Parse the incoming data (handles both JSON and URL-encoded formats)
    $data = [];
    if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $data = json_decode($raw_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON decode error: ' . json_last_error_msg());
        }
    } else {
        parse_str($raw_data, $data);
    }
    
    // Create a structured log entry
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'source' => 'CallRail',
        'data' => $data
    ];
    
    // Convert to pretty JSON for readable logging
    $log_json = json_encode($log_entry, JSON_PRETTY_PRINT);
    
    // Format the log entry with separators
    $log_content = "=== CallRail Webhook Log Start ===\n" . $log_json . "\n=== CallRail Webhook Log End ===\n\n";
    
    // Read existing logs and prepend new entry
    $existing_logs = file_exists('webhook_call_log.log') ? file_get_contents('webhook_call_log.log') : '';
    if (file_put_contents('webhook_call_log.log', $log_content . $existing_logs) === false) {
        throw new Exception('Failed to write to log file');
    }

    // Establish database connection
    $db = new mysqli('localhost', 'xpjjbrbbmv', 'Fs8YyHyejv', 'xpjjbrbbmv');
    
    if ($db->connect_error) {
        throw new Exception('Database connection failed: ' . $db->connect_error);
    }

    /**
     * CallRail Data Mapping
     * 
     * Maps CallRail's webhook data to our database fields
     * CallRail provides first_call and prior_calls directly,
     * unlike Twilio where we calculate these values
     */
    $name = $data['customer_name'] ?? '';
    $from_number = formatPhoneNumber($data['customer_phone_number'] ?? '');
    $to_number = formatPhoneNumber($data['tracking_phone_number'] ?? '');
    $first_call = $data['first_call'] ?? false;
    $prior_calls = $data['prior_calls'] ?? 0;
    $resource_id = $data['resource_id'] ?? null;

    // Determine the source of the call based on the tracking number
    $source = 'Unknown';
    $tracking_number = preg_replace('/[^0-9]/', '', $to_number);

    /**
     * Marketing Source Mapping
     * 
     * Maps CallRail tracking numbers to their marketing sources
     * Different from Twilio's mapping as CallRail uses different numbers
     */
    if (in_array($tracking_number, ['8565531087', '8568884176', '8568306766', '8566844075'])) {
        $source = 'Google ads';
    } elseif ($tracking_number === '8662714069') {
        $source = 'GMB ads';
    } elseif ($tracking_number === '8567407948') {
        $source = 'Google ads extension';
    }

    // Prepare the SQL statement for inserting the call log
    $stmt = $db->prepare("INSERT INTO call_log (name, from_number, to_number, first_call, prior_calls, source, resource_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $db->error);
    }

    // Bind parameters to the prepared statement
    $stmt->bind_param("sssiss", 
        $name,          // customer_name from CallRail
        $from_number,   // formatted customer_phone_number
        $to_number,     // formatted tracking_phone_number
        $first_call,    // first_call flag from CallRail
        $prior_calls,   // prior_calls count from CallRail
        $source,        // determined marketing source
        $resource_id    // CallRail's unique resource identifier
    );

    // Execute the insert statement
    if (!$stmt->execute()) {
        throw new Exception('Failed to insert into database: ' . $stmt->error);
    }

    // Clean up database connections
    $stmt->close();
    $db->close();
    
    // Send success response to CallRail
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    // Log any errors and send error response
    error_log("CallRail Webhook Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
