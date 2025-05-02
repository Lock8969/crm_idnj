<?php
//----------------------------------------
// TIME CLOCK API
// Handles clock in/out requests
//----------------------------------------
require_once 'SystemService.php';
require_once 'auth_check.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Logging function
function logTimeClockAction($message, $data = null, $type = 'incoming') {
    static $logBuffer = [];
    static $timestamp = null;
    
    // If this is the start of a new request
    if ($type === 'incoming') {
        $timestamp = date('Y-m-d H:i:s');
        $logBuffer = [];
    }
    
    // Add the log entry to buffer
    $logBuffer[] = [
        'type' => $type,
        'message' => $message,
        'data' => $data
    ];
    
    // If this is the final response, write everything to file
    if ($type === 'response') {
        $logFile = 'time_clock_api_log.txt';
        $logEntry = "\n" . str_repeat("=", 80) . "\n";
        $logEntry .= "START TIME CLOCK API LOG - $timestamp\n";
        $logEntry .= str_repeat("=", 80) . "\n";
        
        // Write all buffered entries
        foreach ($logBuffer as $entry) {
            $logEntry .= "MESSAGE: {$entry['message']}\n";
            if ($entry['data'] !== null) {
                $logEntry .= "DATA: " . json_encode($entry['data'], JSON_PRETTY_PRINT) . "\n";
            }
            if ($entry['type'] === 'incoming') {
                $logEntry .= "OUTGOING\n";
            } elseif ($entry['type'] === 'outgoing') {
                $logEntry .= "RESPONSE\n";
            }
            // Add log_id to the response if it exists
            if ($entry['type'] === 'response' && isset($entry['data']['log_id'])) {
                $logEntry .= "LOG ID: {$entry['data']['log_id']}\n";
            }
        }
        
        $logEntry .= str_repeat("=", 80) . "\n";
        
        // Read existing content
        $existingContent = file_exists($logFile) ? file_get_contents($logFile) : '';
        
        // Write new log entry at the top
        file_put_contents($logFile, $logEntry . $existingContent);
        
        // Clear the buffer
        $logBuffer = [];
        $timestamp = null;
    }
}

// Log the incoming request
logTimeClockAction("Incoming Request", [
    'method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
    'session_data' => [
        'user_id' => $_SESSION['user_id'] ?? null,
        'location_id' => $_SESSION['location_id'] ?? null
    ]
], 'incoming');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response = ['success' => false, 'message' => 'Invalid request method'];
    logTimeClockAction("Invalid Request Method", $response, 'response');
    echo json_encode($response);
    exit;
}

// Initialize SystemService
$systemService = new SystemService();

// Get the action type (clock_in or clock_out)
$action = $_POST['action'] ?? '';

// Prepare data array with required fields
$data = [
    'user_id' => $_SESSION['user_id'] ?? null,
    'location_id' => $_SESSION['location_id'] ?? null,
    'notes' => $_POST['notes'] ?? null,
    'device_type' => $_POST['device_type'] ?? 'other'
];

// Validate required session data
if (empty($data['user_id']) || empty($data['location_id'])) {
    $response = ['success' => false, 'message' => 'User ID and Location ID are required'];
    logTimeClockAction("Missing Required Session Data", $response, 'response');
    echo json_encode($response);
    exit;
}

// Handle clock in
if ($action === 'clock_in') {
    $data['clock_in_time'] = date('Y-m-d H:i:s');
    logTimeClockAction("Sending Clock In Request to handleTimeClock", $data, 'outgoing');
    $result = $systemService->handleTimeClock($data);
    logTimeClockAction("Received Clock In Response", $result, 'response');
    echo json_encode($result);
    exit;
}

// Handle clock out
if ($action === 'clock_out') {
    $data['clock_out_time'] = date('Y-m-d H:i:s');
    $data['additional_time'] = isset($_POST['additional_time']) ? (int)$_POST['additional_time'] : 0;
    logTimeClockAction("Sending Clock Out Request to handleTimeClock", $data, 'outgoing');
    $result = $systemService->handleTimeClock($data);
    logTimeClockAction("Received Clock Out Response", $result, 'response');
    echo json_encode($result);
    exit;
}

// If we get here, the action was invalid
$response = ['success' => false, 'message' => 'Invalid action specified'];
logTimeClockAction("Invalid Action Specified", $response, 'response');
echo json_encode($response);
?> 