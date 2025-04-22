<?php
require_once 'auth_check.php';
include 'db.php';
require_once 'AppointmentService.php';

// Set response header to JSON
header('Content-Type: application/json');

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Log the received data
    $logFile = 'appointment_update_logs.json';
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'received_data' => $data
    ];

    // Read existing logs if file exists
    $existingLogs = [];
    if (file_exists($logFile)) {
        $existingLogs = json_decode(file_get_contents($logFile), true) ?? [];
    }

    // Add new log to beginning of array
    array_unshift($existingLogs, $logData);

    // Write updated logs back to file
    file_put_contents($logFile, json_encode($existingLogs, JSON_PRETTY_PRINT));

    // Initialize appointment service
    $appointmentService = new AppointmentService($pdo);

    // Call updateAppointment method
    $result = $appointmentService->updateAppointment($data['appointment_id'], [
        'user_id' => $_SESSION['user_id'],
        'location_id' => $data['location_id'],
        'appointment_type' => $data['type'],
        'date' => $data['date'],
        'time' => $data['time']
    ]);

    // Return the result
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 