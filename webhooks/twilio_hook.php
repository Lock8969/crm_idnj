<?php
/**
 * TWILIO WEBHOOK HANDLER
 * 
 * This script handles incoming webhook calls from Twilio when a call is received.
 * It processes the call data, logs it, and stores it in the database.
 * 
 * Key Functions:
 * 1. Receives and validates incoming call data from Twilio
 * 2. Formats phone numbers to standard (XXX) XXX-XXXX format
 * 3. Determines the source of the call based on tracking numbers
 * 4. Counts prior calls from the same number
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
    // Get the raw input data from Twilio
    $raw_data = file_get_contents('php://input');
    
    // Log the raw input for debugging purposes
    error_log("Twilio Raw Input: " . $raw_data);
    
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
        'source' => 'Twilio',
        'data' => $data
    ];
    
    // Convert to pretty JSON for readable logging
    $log_json = json_encode($log_entry, JSON_PRETTY_PRINT);
    
    // Format the log entry with separators
    $log_content = "=== Twilio Webhook Log Start ===\n" . $log_json . "\n=== Twilio Webhook Log End ===\n\n";
    
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

    // Format phone numbers to standard format
    $to_number = formatPhoneNumber($data['to']);
    $from_number = formatPhoneNumber($data['From']);

    // Count prior calls for this number using the index
    // This uses the idx_from_number index for faster lookups
    $count_stmt = $db->prepare("SELECT COUNT(*) as prior_count FROM call_log USE INDEX (idx_from_number) WHERE from_number = ?");
    if (!$count_stmt) {
        throw new Exception('Failed to prepare count statement: ' . $db->error);
    }
    
    $count_stmt->bind_param("s", $from_number);
    if (!$count_stmt->execute()) {
        throw new Exception('Failed to count prior calls: ' . $count_stmt->error);
    }
    
    $result = $count_stmt->get_result();
    $row = $result->fetch_assoc();
    $prior_calls = $row['prior_count'];
    $is_first_call = ($prior_calls === 0);
    
    $count_stmt->close();

    /**
     * Tracking Number to Source Mapping
     * 
     * Maps different phone numbers to their marketing sources
     * Used to track which marketing channel generated the call
     */
    $tracking_sources = [
        '8567539700' => 'Main',
        '8568561656' => 'Brochure',
        '8668705999' => 'Mailer',
        '6092417731' => 'GMB Pleasantville',
        '8563065038' => 'GMB Berlin',
        '6099007352' => 'GMB Hamilton',
        '7322288479' => 'GMB Toms River',
        '8488002989' => 'GMB North Brunswick',
        '8568305042' => 'GMB Cherry Hill',
        '2016934474' => 'GMB Saddle Brook',
        '8562120533' => 'GMB Vineland',
        '9089873524' => 'GMB Kenilworth',
        '2018856967' => 'GMB North Bergen',
        '8009701002' => '800#'
    ];

    // Determine the source of the call based on the tracking number
    $source = 'Unknown';
    $tracking_number = preg_replace('/[^0-9]/', '', $to_number);
    if (isset($tracking_sources[$tracking_number])) {
        $source = $tracking_sources[$tracking_number];
    }

    // Prepare the SQL statement for inserting the call log
    $stmt = $db->prepare("INSERT INTO call_log (to_number, from_number, name, sid, source, prior_calls, first_call) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $db->error);
    }

    // Convert boolean to tinyint for first_call field
    $first_call_tinyint = $is_first_call ? 1 : 0;

    // Bind parameters to the prepared statement
    $stmt->bind_param("sssssii", 
        $to_number,     // formatted to_number
        $from_number,   // formatted from_number
        $data['name'],  // caller ID name
        $data['SID'],   // Twilio SID
        $source,        // marketing source
        $prior_calls,   // count of prior calls
        $first_call_tinyint  // is this the first call?
    );

    // Execute the insert statement
    if (!$stmt->execute()) {
        throw new Exception('Failed to insert into database: ' . $stmt->error);
    }

    // Clean up database connections
    $stmt->close();
    $db->close();
    
    // Send success response to Twilio
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    // Log any errors and send error response
    error_log("Twilio Webhook Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
