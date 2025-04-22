<?php
/**
 * Appointment API
 * 
 * Required Fields for POST/PUT:
 * - customer_id: The ID of the customer
 * - title: The appointment title
 * - appointment_type: Must be one of: 'Install', 'Recalibration', 'Removal', 'Final_download', 'Service', 'Paper_Swap'
 * - start_time: Appointment start time (MySQL datetime format)
 * - end_time: Appointment end time (MySQL datetime format)
 * - location_id: The ID of the appointment location
 * 
 * Optional Fields:
 * - status: Appointment status (defaults to 'scheduled')
 * - service_note: Any service-related notes
 * - description: General appointment description
 * - reason: Required only for DELETE requests
 * 
 * GET Request Parameters:
 * - id: Get specific appointment
 * - start_date: Start date for date range (defaults to current date)
 * - end_date: End date for date range (defaults to current date + 30 days)
 * - location_id: Filter by location
 * - status: Filter by status
 * - appointment_type: Filter by appointment type
 * - customer_id: Filter by customer
 */

require_once 'auth_check.php';
include 'db.php';
require_once 'AppointmentService.php';

// Add initial logging
$logFile = 'appointment_api_log.txt';
$timestamp = date('Y-m-d H:i:s');
$logMessage = "\n" . str_repeat("=", 80) . "\n";
$logMessage .= "APPOINTMENT API ENTRY - $timestamp\n";
$logMessage .= str_repeat("=", 80) . "\n";
$logMessage .= "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
$logMessage .= "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
$existingContent = file_exists($logFile) ? file_get_contents($logFile) : '';
file_put_contents($logFile, $logMessage . $existingContent);

header('Content-Type: application/json');

// Create appointment service
$appointmentService = new AppointmentService($pdo);

// Determine request type
$requestMethod = $_SERVER['REQUEST_METHOD'];

try {
    switch ($requestMethod) {
        case 'GET':
            // Get appointment(s)
            if (isset($_GET['id'])) {
                // Get specific appointment with all details
                $appointment = $appointmentService->getAppointmentDetails($_GET['id']);
                if (!$appointment) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Appointment not found']);
                } else {
                    echo json_encode(['success' => true, 'data' => $appointment]);
                }
            } else {
                // Get appointments for date range
                $startDate = $_GET['start_date'] ?? date('Y-m-d');
                $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('+30 days'));
                
                // Optional filters
                $filters = [];
                if (isset($_GET['location_id'])) $filters['location_id'] = $_GET['location_id'];
                if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
                if (isset($_GET['appointment_type'])) $filters['appointment_type'] = $_GET['appointment_type'];
                if (isset($_GET['customer_id'])) $filters['customer_id'] = $_GET['customer_id'];
                
                $appointments = $appointmentService->getAppointmentsForRange($startDate, $endDate, $filters);
                echo json_encode(['success' => true, 'data' => $appointments]);
            }
            break;
            
        case 'POST':
            // Create new appointment
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                $data = $_POST; // Try form data if JSON fails
            }
            
            // Log received data
            $logMessage = "\n" . str_repeat("-", 80) . "\n";
            $logMessage .= "RECEIVED DATA - $timestamp\n";
            $logMessage .= str_repeat("-", 80) . "\n";
            $logMessage .= "Raw Input: " . file_get_contents('php://input') . "\n";
            $logMessage .= "Decoded Data:\n";
            foreach ($data as $key => $value) {
                $logMessage .= "$key: " . (is_array($value) ? json_encode($value) : $value) . "\n";
            }
            $existingContent = file_exists($logFile) ? file_get_contents($logFile) : '';
            file_put_contents($logFile, $logMessage . $existingContent);
            
            // Validate required fields
            $requiredFields = ['start_time', 'location_id', 'title', 'appointment_type', 'customer_id'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Validate appointment_type
            $validTypes = [
                'install90', 'install120',
                'recalibration', 'removal', 'final_download',
                'service', 'paper_swap',
                'other15', 'other30', 'other45', 'other60'
            ];
            if (!in_array($data['appointment_type'], $validTypes)) {
                throw new Exception("Invalid appointment type");
            }
            
            // Validate date/time value
            if (!strtotime($data['start_time'])) {
                throw new Exception("Invalid start time format");
            }
            
            $appointmentId = $appointmentService->createAppointment($data);
            echo json_encode(['success' => true, 'appointment_id' => $appointmentId]);
            break;
            
        case 'PUT':
            // Update appointment
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                throw new Exception("Appointment ID is required");
            }
            
            $appointmentService->updateAppointment(
                $data['id'], 
                $data, 
                $_SESSION['user_id']
            );
            
            echo json_encode(['success' => true]);
            break;
            
        case 'DELETE':
            // Delete appointment
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                throw new Exception("Appointment ID is required");
            }
            
            $appointmentService->deleteAppointment(
                $data['id'], 
                $_SESSION['user_id'], 
                $data['reason'] ?? null
            );
            
            echo json_encode(['success' => true]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>