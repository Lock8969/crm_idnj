<?php
// webhook.php - Endpoint to receive lead data from external systems

// Include database connection
include 'db.php';

// Define a secret key for basic authentication (should be kept secret and shared with the sender)
define('WEBHOOK_SECRET', 'eP8jQ5tX2kLzF7vA3cN6bR9yW4mD1hG0'); // Change this to a strong random string

// Log incoming webhook requests
function logWebhook($status, $message, $data = null) {
    $log_file = 'webhooks.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] STATUS: $status - $message";
    
    if ($data !== null) {
        $log_entry .= " - DATA: " . json_encode($data);
    }
    
    file_put_contents($log_file, $log_entry . PHP_EOL, FILE_APPEND);
}

// Get the incoming request data
$request_method = $_SERVER['REQUEST_METHOD'];
$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

// Only accept POST requests
if ($request_method !== 'POST') {
    logWebhook('ERROR', 'Invalid request method', ['method' => $request_method]);
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Verify the webhook secret if provided in the header
$headers = getallheaders();
$provided_secret = isset($headers['X-Webhook-Secret']) ? $headers['X-Webhook-Secret'] : '';

if ($provided_secret !== WEBHOOK_SECRET) {
    logWebhook('ERROR', 'Invalid webhook secret');
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get the request body
$input = file_get_contents('php://input');

// Check content type and parse data accordingly
if (strpos($content_type, 'application/json') !== false) {
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logWebhook('ERROR', 'Invalid JSON data', ['error' => json_last_error_msg()]);
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
} elseif (strpos($content_type, 'application/x-www-form-urlencoded') !== false) {
    parse_str($input, $data);
} else {
    logWebhook('ERROR', 'Unsupported content type', ['content_type' => $content_type]);
    http_response_code(415); // Unsupported Media Type
    echo json_encode(['error' => 'Unsupported content type']);
    exit;
}

// Validate required fields
$required_fields = ['phone_number']; // Add other required fields as needed
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        logWebhook('ERROR', "Missing required field: $field", $data);
        http_response_code(400); // Bad Request
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

// Prepare data for insertion
// Map incoming data to table fields - adjust these mappings as needed
$lead_data = [
    'phone_number' => $data['phone_number'] ?? null,
    'first_name' => $data['first_name'] ?? null,
    'last_name' => $data['last_name'] ?? null,
    'email' => $data['email'] ?? null,
    'status' => $data['status'] ?? 'New Lead',
    'lead_source' => $data['lead_source'] ?? 'Unknown',
    'caller_id_number' => $data['caller_id_number'] ?? null,
    'caller_id_name' => $data['caller_id_name'] ?? null,
    'created_by' => $data['created_by'] ?? 'Webhook',
    'collaborators' => $data['collaborators'] ?? null,
    'gmb_location' => $data['gmb_location'] ?? null,
    'gclid' => $data['gclid'] ?? null,
    'address1' => $data['address1'] ?? $data['address'] ?? null, // Support both formats
    'address2' => $data['address2'] ?? null,
    'city' => $data['city'] ?? null,
    'state' => $data['state'] ?? null,
    'zip' => $data['zip'] ?? null,
    'notes' => $data['notes'] ?? null,
    'hybrid' => isset($data['hybrid']) ? (int)$data['hybrid'] : null,
    'start_system' => $data['start_system'] ?? null,
    'vehicle_notes' => $data['vehicle_notes'] ?? null,
    'year' => $data['year'] ?? null, // New field
    'make' => $data['make'] ?? null, // New field
    'model' => $data['model'] ?? null, // New field
];

// Build the SQL statement dynamically
$fields = [];
$placeholders = [];
$values = [];

foreach ($lead_data as $field => $value) {
    if ($value !== null) {
        $fields[] = $field;
        $placeholders[] = '?';
        $values[] = $value;
    }
}

$sql = "INSERT INTO leads (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";

try {
    // Insert into database
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($values);
    
    if ($result) {
        $lead_id = $pdo->lastInsertId();
        logWebhook('SUCCESS', "Lead created successfully", ['lead_id' => $lead_id, 'data' => $lead_data]);
        
        // Return success response
        http_response_code(201); // Created
        echo json_encode([
            'success' => true,
            'message' => 'Lead created successfully',
            'lead_id' => $lead_id
        ]);
    } else {
        throw new Exception("Database insert failed");
    }
} catch (PDOException $e) {
    logWebhook('ERROR', "Database error: " . $e->getMessage(), $lead_data);
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    exit;
} catch (Exception $e) {
    logWebhook('ERROR', $e->getMessage(), $lead_data);
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Error creating lead', 'message' => $e->getMessage()]);
    exit;
}
?>