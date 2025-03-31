<?php
/**
 * =============================================
 * GET AVAILABLE SLOTS API
 * =============================================
 * 
 * Returns available time slots for a given location and date
 * 
 * Required Parameters:
 * - location_id: The ID of the location
 * - date: The date to check (Y-m-d format)
 * - type: The type of appointment
 * - duration: The duration in minutes
 * 
 * Returns:
 * - success: boolean
 * - data: array of available time slots in HH:mm format
 * - message: error message if success is false
 * =============================================
 */

require_once 'auth_check.php';
include 'db.php';
require_once 'AppointmentService.php';

header('Content-Type: application/json');

// Create appointment service
$appointmentService = new AppointmentService($pdo);

try {
    // Validate required parameters
    if (!isset($_GET['location_id']) || !isset($_GET['date']) || !isset($_GET['type']) || !isset($_GET['duration'])) {
        throw new Exception("Missing required parameters: location_id, date, type, and duration");
    }
    
    $location_id = $_GET['location_id'];
    $date = $_GET['date'];
    $type = $_GET['type'];
    $duration = (int)$_GET['duration'];
    
    // Validate location_id is numeric
    if (!is_numeric($location_id)) {
        throw new Exception("Invalid location_id format");
    }
    
    // Validate date format (Y-m-d)
    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
        throw new Exception("Invalid date format. Use YYYY-MM-DD");
    }
    
    // Validate date is not in the past
    if (strtotime($date) < strtotime('today')) {
        throw new Exception("Cannot check availability for past dates");
    }
    
    // Validate duration is a positive number
    if ($duration <= 0) {
        throw new Exception("Invalid duration value");
    }
    
    // Get available time slots
    $availableSlots = $appointmentService->getAvailableTimeSlots($location_id, $date, $type, $duration);
    
    // Return success response with available slots
    echo json_encode([
        'success' => true,
        'data' => $availableSlots
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 