<?php
/**
 * =============================================
 * APPOINTMENT SERVICE METHODS
 * =============================================
 * 
 * Available Methods:
 * 
 * getAppointment()
 * - Gets a single appointment by ID
 * - Returns appointment details or null if not found
 * 
 * getAppointmentsForRange()
 * - Gets appointments within a date range
 * - Supports filtering by location, status, type, customer
 * - Returns array of appointments
 * 
 * createAppointment()
 * - Creates a new appointment
 * - Updates client status to 'Scheduled' for Install appointments
 * - Returns the new appointment ID
 * 
 * updateAppointment()
 * - Updates an existing appointment's details
 * - Tracks who made the update and when
 * 
 * deleteAppointment()
 * - Soft deletes an appointment (marks as cancelled)
 * - Tracks who deleted and why
 * 
 * getAvailableTimeSlots()
 * - Gets available 15-minute slots between 7am-8pm
 * - Checks against existing appointments
 * - Returns array of available times
 * 
 * =============================================
 * APPOINTMENT SERVICE
 * =============================================
 * 
 * Handles all appointment-related database operations including:
 * - Creating new appointments
 * - Retrieving appointments
 * - Updating appointments
 * - Deleting appointments (soft delete)
 * 
 * =============================================
 * SPECIAL FEATURES
 * =============================================
 * - Automatically updates client_information status to 'Scheduled' when creating an Install appointment
 * - Soft deletes appointments (sets status to 'cancelled' instead of removing)
 * - Tracks who created/updated/deleted appointments
 * - Supports filtering appointments by date range, location, status, type, and customer
 * 
 * =============================================
 * REQUIRED FIELDS for createAppointment():
 * =============================================
 * - customer_id: The ID of the customer
 * - title: The appointment title
 * - appointment_type: Must be one of: 'Install', 'Recalibration', 'Removal', 'Final_download', 'Service', 'Paper_Swap'
 * - start_time: Appointment start time (MySQL datetime format)
 * - end_time: Appointment end time (MySQL datetime format)
 * - location_id: The ID of the appointment location
 * 
 * =============================================
 * OPTIONAL FIELDS
 * =============================================
 * - status: Appointment status (defaults to 'scheduled')
 * - service_note: Any service-related notes
 * - description: General appointment description
 * 
 * =============================================
 * DATABASE TABLES USED
 * =============================================
 * - appointments: Main appointments table
 * - client_information: Updated when creating Install appointments
 * 
 * =============================================
 */

class AppointmentService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * =============================================
     * CREATE APPOINTMENT
     * =============================================
     * Creates a new appointment and updates client status if it's an installation
     * 
     * @param array $data Appointment data
     * @return int The ID of the created appointment
     * =============================================
     */
    public function createAppointment($data) {
        try {
            // Duration map for calculating end time (in minutes)
            $durationMap = [
                'install90' => 89,
                'install120' => 119,
                'recalibration' => 14,
                'removal' => 29,
                'paper_swap' => 14,
                'final_download' => 14,
                'service' => 29,
                'other15' => 14,
                'other30' => 29,
                'other45' => 44,
                'other60' => 59
            ];

            // Map input type to actual appointment type
            $typeMap = [
                'install90' => 'Install',
                'install120' => 'Install',
                'recalibration' => 'Recalibration',
                'removal' => 'Removal',
                'paper_swap' => 'Paper_Swap',
                'final_download' => 'Final_download',
                'service' => 'Service',
                'other15' => 'Other',
                'other30' => 'Other',
                'other45' => 'Other',
                'other60' => 'Other'
            ];

            // Validate required fields
            $requiredFields = ['start_time', 'location_id', 'title', 'appointment_type', 'customer_id'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            // Validate appointment_type exists in our maps
            if (!isset($durationMap[$data['appointment_type']])) {
                throw new Exception("Invalid appointment type");
            }

            // Calculate end time based on start time and duration
            $startTime = strtotime($data['start_time']);
            $duration = $durationMap[$data['appointment_type']] * 60; // Convert minutes to seconds
            $endTime = date('Y-m-d H:i:s', $startTime + $duration);
            $formattedStartTime = date('Y-m-d H:i:s', $startTime);

            // Map the input type to the actual appointment type
            $actualType = $typeMap[$data['appointment_type']];
            
            $sql = "INSERT INTO appointments (
                customer_id,
                title,
                appointment_type,
                start_time,
                end_time,
                location_id,
                status,
                service_note,
                description,
                created_by,
                created_at
            ) VALUES (
                :customer_id,
                :title,
                :appointment_type,
                :start_time,
                :end_time,
                :location_id,
                :status,
                :service_note,
                :description,
                :created_by,
                NOW()
            )";
            
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->execute([
                ':customer_id' => $data['customer_id'],
                ':title' => $data['title'],
                ':appointment_type' => $actualType,
                ':start_time' => $formattedStartTime,
                ':end_time' => $endTime,
                ':location_id' => $data['location_id'],
                ':status' => $data['status'] ?? 'scheduled',
                ':service_note' => $data['service_note'] ?? null,
                ':description' => $data['description'] ?? null,
                ':created_by' => $_SESSION['user_id'] ?? null
            ]);
            
            $appointmentId = $this->pdo->lastInsertId();
            
            // If this is an installation appointment, update client status
            if ($actualType === 'Install') {
                $updateSql = "UPDATE client_information SET status = 'Scheduled' WHERE id = :customer_id";
                $updateStmt = $this->pdo->prepare($updateSql);
                $updateStmt->execute(['customer_id' => $data['customer_id']]);
            }
            
            return $appointmentId;
            
        } catch (PDOException $e) {
            throw new Exception("Error creating appointment: " . $e->getMessage());
        }
    }
    
    /**
     * =============================================
     * GET APPOINTMENT
     * =============================================
     * Retrieves a single appointment by its ID
     * 
     * @param int $id Appointment ID
     * @return array|null Appointment data or null if not found
     * =============================================
     */
    public function getAppointment($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM appointments WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error getting appointment: " . $e->getMessage());
        }
    }
    
    /**
     * =============================================
     * GET APPOINTMENTS FOR RANGE
     * =============================================
     * Retrieves appointments within a date range with optional filters
     * 
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @param array $filters Optional filters
     * @return array Array of appointments
     * =============================================
     */
    public function getAppointmentsForRange($startDate, $endDate, $filters = []) {
        try {
            $sql = "SELECT * FROM appointments WHERE start_time BETWEEN ? AND ?";
            $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
            
            // Add filters if provided
            if (!empty($filters['location_id'])) {
                $sql .= " AND location_id = ?";
                $params[] = $filters['location_id'];
            }
            if (!empty($filters['status'])) {
                $sql .= " AND status = ?";
                $params[] = $filters['status'];
            }
            if (!empty($filters['appointment_type'])) {
                $sql .= " AND appointment_type = ?";
                $params[] = $filters['appointment_type'];
            }
            if (!empty($filters['customer_id'])) {
                $sql .= " AND customer_id = ?";
                $params[] = $filters['customer_id'];
            }
            
            $sql .= " ORDER BY start_time ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            throw new Exception("Error getting appointments: " . $e->getMessage());
        }
    }
    
    /**
     * =============================================
     * UPDATE APPOINTMENT
     * =============================================
     * Updates an existing appointment's details
     * 
     * @param int $id Appointment ID
     * @param array $data Updated appointment data
     * @param int $userId User making the update
     * =============================================
     */
    public function updateAppointment($id, $data, $userId) {
        try {
            $sql = "UPDATE appointments SET 
                customer_id = :customer_id,
                title = :title,
                appointment_type = :appointment_type,
                start_time = :start_time,
                end_time = :end_time,
                location_id = :location_id,
                status = :status,
                service_note = :service_note,
                description = :description,
                updated_by = :updated_by,
                updated_at = NOW()
                WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->execute([
                ':id' => $id,
                ':customer_id' => $data['customer_id'],
                ':title' => $data['title'],
                ':appointment_type' => $data['appointment_type'],
                ':start_time' => $data['start_time'],
                ':end_time' => $data['end_time'],
                ':location_id' => $data['location_id'],
                ':status' => $data['status'] ?? 'scheduled',
                ':service_note' => $data['service_note'] ?? null,
                ':description' => $data['description'] ?? null,
                ':updated_by' => $userId
            ]);
            
        } catch (PDOException $e) {
            throw new Exception("Error updating appointment: " . $e->getMessage());
        }
    }
    
    /**
     * =============================================
     * DELETE APPOINTMENT
     * =============================================
     * Soft deletes an appointment by marking it as cancelled
     * 
     * @param int $id Appointment ID
     * @param int $userId User making the deletion
     * @param string|null $reason Reason for deletion
     * =============================================
     */
    public function deleteAppointment($id, $userId, $reason = null) {
        try {
            $sql = "UPDATE appointments SET 
                status = 'cancelled',
                deleted_by = :deleted_by,
                deleted_at = NOW(),
                deletion_reason = :reason
                WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':deleted_by' => $userId,
                ':reason' => $reason
            ]);
            
        } catch (PDOException $e) {
            throw new Exception("Error deleting appointment: " . $e->getMessage());
        }
    }
    
    /**
     * =============================================
     * GET AVAILABLE TIME SLOTS
     * =============================================
     * Returns available time slots for a given location and date
     * 
     * @param int $location_id The ID of the location
     * @param string $date The date to check (Y-m-d format)
     * @param string $type The type of appointment
     * @param int $duration The duration in minutes
     * @return array Array of available time slots in HH:mm format
     * =============================================
     */
    public function getAvailableTimeSlots($location_id, $date, $type, $duration) {
        try {
            // Set up timezone objects
            $utc = new DateTimeZone('UTC');
            $et = new DateTimeZone('America/New_York');
            
            // Get all booked appointments for the location and date
            $sql = "SELECT start_time, end_time 
                   FROM appointments 
                   WHERE location_id = ? 
                   AND DATE(start_time) = ? 
                   AND status != 'cancelled'";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$location_id, $date]);
            $bookedSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert booked slots to ET timestamps
            $bookedTimes = [];
            foreach ($bookedSlots as $slot) {
                // Create DateTime objects from UTC times
                $startDateTime = new DateTime($slot['start_time'], $utc);
                $endDateTime = new DateTime($slot['end_time'], $utc);
                
                // Convert to ET
                $startDateTime->setTimezone($et);
                $endDateTime->setTimezone($et);
                
                $bookedTimes[] = [
                    'start' => $startDateTime->getTimestamp(),
                    'end' => $endDateTime->getTimestamp()
                ];
            }
            
            // Generate all possible slots between 7am-8pm ET
            $availableSlots = [];
            
            // Create DateTime objects for business hours in ET
            $startDateTime = new DateTime($date . ' 07:00:00', $et);
            $endDateTime = new DateTime($date . ' 20:00:00', $et);
            
            // Create slots in ET
            $currentDateTime = clone $startDateTime;
            while ($currentDateTime < $endDateTime) {
                $slotStart = $currentDateTime->getTimestamp();
                $slotEnd = $slotStart + ($duration * 60);
                
                // Check if slot overlaps with any booked appointments
                $isAvailable = true;
                foreach ($bookedTimes as $booked) {
                    if (($slotStart >= $booked['start'] && $slotStart < $booked['end']) ||
                        ($slotEnd > $booked['start'] && $slotEnd <= $booked['end']) ||
                        ($slotStart <= $booked['start'] && $slotEnd >= $booked['end'])) {
                        $isAvailable = false;
                        break;
                    }
                }
                
                // Check if the slot ends before the business day ends
                if ($slotEnd <= $endDateTime->getTimestamp() && $isAvailable) {
                    $availableSlots[] = $currentDateTime->format('H:i');
                }
                
                // Move to next 15-minute slot
                $currentDateTime->modify('+15 minutes');
            }
            
            return $availableSlots;
            
        } catch (PDOException $e) {
            throw new Exception("Error getting available time slots: " . $e->getMessage());
        }
    }
}
?> 