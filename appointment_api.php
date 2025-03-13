<?php
require_once 'auth_check.php';
include 'db.php';
require_once 'AppointmentService.php';

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
                // Get specific appointment
                $appointment = $appointmentService->getAppointment($_GET['id']);
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