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
    
    //------------------------
    // NOTES: Service Abbreviations
    //------------------------
    // Mapping of full service names to their abbreviations
    private $serviceAbbreviations = [
        'Final Download' => 'FD',
        'Final_download' => 'FD',
        'Admin Fee' => 'AF',
        'Recalibration' => 'RC',
        'Monitoring Check' => 'MC',
        'Install' => 'I',
        'Removal' => 'Rem',
        'VIO Reset' => 'VR',
        'Change Vehicle - tier 1' => 'CV1',
        'Change Vehicle - tier 2' => 'CV2',
        'Change Vehicle - tier 3' => 'CV3',
        'Mouth piece 2 pack' => 'MP2',
        'Unlock Code' => 'UC',
        'Certification fee' => 'Cert'
    ];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        // Log constructor call
        $logFile = 'appointment_service_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "\n" . str_repeat("=", 80) . "\n";
        $logMessage .= "APPOINTMENT SERVICE CONSTRUCTOR CALLED - $timestamp\n";
        $logMessage .= str_repeat("=", 80) . "\n";
        $logMessage .= "PDO Connection Status: " . ($pdo ? "Connected" : "Not Connected") . "\n";
        $existingContent = file_exists($logFile) ? file_get_contents($logFile) : '';
        file_put_contents($logFile, $logMessage . $existingContent);
    }
    
    //-------------------------------------------------------------------
    // Add logging functionality
    //-------------------------------------------------------------------
    private function logAppointmentServiceAction($logMessage) {
        $logFile = 'appointment_service_log.txt';
        $existingContent = file_exists($logFile) ? file_get_contents($logFile) : '';
        $timestamp = date('Y-m-d H:i:s');
        $newLogEntry = "\n" . str_repeat("=", 80) . "\n";
        $newLogEntry .= "APPOINTMENT SERVICE LOG - $timestamp\n";
        $newLogEntry .= str_repeat("=", 80) . "\n";
        $newLogEntry .= $logMessage . "\n";
        $newLogEntry .= str_repeat("=", 80) . "\n";
        file_put_contents($logFile, $newLogEntry . $existingContent);
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
            //-------------------------------------------------------------------
            // Log incoming data
            //-------------------------------------------------------------------
            $this->logAppointmentServiceAction(
                "INCOMING REQUEST DATA:\n" .
                "Request Type: create_appointment\n" .
                "Customer ID: " . $data['customer_id'] . "\n" .
                "Created By: " . ($_SESSION['user_id'] ?? 'N/A') . "\n" .
                "Location ID: " . $data['location_id'] . "\n" .
                "Appointment Type: " . $data['appointment_type'] . "\n" .
                "Start Time: " . $data['start_time'] . "\n" .
                "Service Note: " . ($data['service_note'] ?? 'N/A') . "\n" .
                "Status: " . ($data['status'] ?? 'scheduled') . "\n"
            );

            // Ensure start_time has seconds
            if (strlen($data['start_time']) === 16) { // Format: YYYY-MM-DD HH:MM
                $data['start_time'] .= ':00'; // Add seconds
            }

            // Convert browser time to UTC
            $browserTime = new DateTime($data['start_time'], new DateTimeZone('America/New_York')); // Assuming browser is in ET
            $browserTime->setTimezone(new DateTimeZone('UTC'));
            $data['start_time'] = $browserTime->format('Y-m-d H:i:s');

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

            //-------------------------------------------------------------------
            // Log duration calculation
            //-------------------------------------------------------------------
            $this->logAppointmentServiceAction(
                "CALCULATED DURATION:\n" .
                "Appointment Type: " . $data['appointment_type'] . "\n" .
                "Duration: " . $durationMap[$data['appointment_type']] . " minutes\n"
            );

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
            
            //-------------------------------------------------------------------
            // Log successful creation
            //-------------------------------------------------------------------
            $this->logAppointmentServiceAction(
                "DATABASE OPERATION:\n" .
                "Table: appointments\n" .
                "Action: INSERT\n" .
                "Status: Success\n" .
                "Appointment ID: " . $appointmentId . "\n"
            );
            
            // If this is an installation appointment, update client status
            if ($actualType === 'Install') {
                $updateSql = "UPDATE client_information SET status = 'Scheduled' WHERE id = :customer_id";
                $updateStmt = $this->pdo->prepare($updateSql);
                $updateStmt->execute(['customer_id' => $data['customer_id']]);
                
                //-------------------------------------------------------------------
                // Log client status update
                //-------------------------------------------------------------------
                $this->logAppointmentServiceAction(
                    "CLIENT STATUS UPDATE:\n" .
                    "Customer ID: " . $data['customer_id'] . "\n" .
                    "New Status: Scheduled\n"
                );
            }
            
            return $appointmentId;
            
        } catch (PDOException $e) {
            //-------------------------------------------------------------------
            // Log error
            //-------------------------------------------------------------------
            $this->logAppointmentServiceAction(
                "ERROR OCCURRED:\n" .
                "Error Message: " . $e->getMessage() . "\n" .
                "Stack Trace:\n" . $e->getTraceAsString() . "\n"
            );
            
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
     * GET APPOINTMENT DETAILS
     * =============================================
     * Retrieves complete appointment details including related data
     * from locations, users, and other associated tables
     * 
     * @param int $id Appointment ID
     * @return array|null Complete appointment details or null if not found
     * =============================================
     */
    public function getAppointmentDetails($id) {
        try {
            $query = "
                SELECT 
                    a.*,
                    l.location_name,
                    l.nick_name as location_nick_name,
                    l.location_type,
                    l.address,
                    l.city,
                    l.state,
                    l.zip,
                    u.full_name as technician_name
                FROM appointments a
                LEFT JOIN locations l ON a.location_id = l.id
                LEFT JOIN users u ON a.created_by = u.id
                WHERE a.id = ?
            ";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error getting appointment details: " . $e->getMessage());
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
     * Updates an existing appointment's details and tracks the change in history
     * 
     * @param int $id Appointment ID
     * @param array $data {
     *     @type int $user_id User making the update
     *     @type int $location_id New location ID
     *     @type string $appointment_type Type of appointment
     *     @type string $date Appointment date (Y-m-d)
     *     @type string $time Appointment time (H:i)
     * }
     * @return array Response with success status and message
     * =============================================
     */
    public function updateAppointment($id, $data) {
        try {
            // Validate required parameters
            if (!isset($data['user_id']) || !isset($data['location_id']) || 
                !isset($data['appointment_type']) || !isset($data['date']) || 
                !isset($data['time'])) {
                throw new Exception("Missing required parameters");
            }

            // Get current appointment data
            $current_appointment = $this->getAppointment($id);
            if (!$current_appointment) {
                throw new Exception("Appointment not found");
            }

            // Start transaction
            $this->pdo->beginTransaction();

            try {
                // Insert into appointment_history
                $history_stmt = $this->pdo->prepare("
                    INSERT INTO appointment_history (
                        appointment_id, previous_start, previous_end, 
                        previous_status, previous_location_id, 
                        changed_by_user_id, reason, notes
                    ) VALUES (
                        :appointment_id, :previous_start, :previous_end,
                        :previous_status, :previous_location_id,
                        :changed_by_user_id, :reason, :notes
                    )
                ");

                $history_stmt->execute([
                    'appointment_id' => $id,
                    'previous_start' => $current_appointment['start_time'],
                    'previous_end' => $current_appointment['end_time'],
                    'previous_status' => $current_appointment['status'],
                    'previous_location_id' => $current_appointment['location_id'],
                    'changed_by_user_id' => $data['user_id'],
                    'reason' => 'Appointment updated via edit form',
                    'notes' => $current_appointment['service_note'] // Copy the service notes from current appointment
                ]);

                // Calculate duration based on appointment type
                $duration = 30; // Default 30 minutes
                if ($data['appointment_type'] === 'Recalibration' || 
                    $data['appointment_type'] === 'Final_download' || 
                    $data['appointment_type'] === 'Paper_Swap') {
                    $duration = 15;
                }

                // Create datetime strings and convert to UTC
                $start_time = $data['date'] . ' ' . $data['time'];
                // Convert browser time to UTC
                $browserTime = new DateTime($start_time, new DateTimeZone('America/New_York')); // Assuming browser is in ET
                $browserTime->setTimezone(new DateTimeZone('UTC'));
                $start_time = $browserTime->format('Y-m-d H:i:s');
                
                // Calculate end time in UTC (subtract 1 minute from duration)
                $end_time = date('Y-m-d H:i:s', strtotime($start_time . " +" . ($duration - 1) . " minutes"));

                // Update appointment - Change status to 'rescheduled' if it was 'scheduled'
                $update_stmt = $this->pdo->prepare("
                    UPDATE appointments 
                    SET start_time = :start_time,
                        end_time = :end_time,
                        location_id = :location_id,
                        appointment_type = :appointment_type,
                        status = CASE 
                            WHEN status = 'scheduled' THEN 'rescheduled'
                            ELSE status
                        END,
                        updated_at = NOW()
                    WHERE id = :id
                ");

                $update_stmt->execute([
                    'id' => $id,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'location_id' => $data['location_id'],
                    'appointment_type' => $data['appointment_type']
                ]);

                // Commit transaction
                $this->pdo->commit();

                return [
                    'success' => true,
                    'message' => 'Appointment updated successfully'
                ];

            } catch (Exception $e) {
                // Rollback transaction on error
                $this->pdo->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
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

    /**
     * =============================================
     * GET CLIENT APPOINTMENTS
     * =============================================
     * Retrieves all appointments for a specific client
     * Includes both past and future appointments
     * 
     * @param int $clientId The ID of the client
     * @return array Array of appointment data
     * =============================================
     */
    public function getClientAppointments($clientId) {
        try {
            // Validate client ID
            if (!is_numeric($clientId) || $clientId <= 0) {
                throw new Exception("Invalid client ID provided");
            }

            // Verify client exists
            $stmt = $this->pdo->prepare("SELECT id FROM client_information WHERE id = ?");
            $stmt->execute([$clientId]);
            if (!$stmt->fetch()) {
                throw new Exception("Client not found");
            }

            // Log the request
            $this->logAppointmentServiceAction(
                "Fetching appointments for client ID: " . $clientId
            );

            // Query to get all appointments for the client
            $query = "
                SELECT 
                    a.id,
                    a.title,
                    a.appointment_type,
                    a.start_time,
                    a.end_time,
                    a.status,
                    a.service_note,
                    a.description,
                    a.created_by,
                    a.created_at,
                    l.location_name,
                    u.full_name as technician_name,
                    FALSE as is_historical,
                    NULL as update_reason
                FROM appointments a
                LEFT JOIN locations l ON a.location_id = l.id
                LEFT JOIN users u ON a.created_by = u.id
                WHERE a.customer_id = ?
                
                UNION ALL
                
                SELECT 
                    ah.appointment_id as id,
                    a.title,
                    a.appointment_type,
                    ah.previous_start as start_time,
                    ah.previous_end as end_time,
                    ah.previous_status as status,
                    a.service_note,
                    a.description,
                    a.created_by,
                    a.created_at,
                    l.location_name,
                    u.full_name as technician_name,
                    TRUE as is_historical,
                    ah.reason as update_reason
                FROM appointment_history ah
                JOIN appointments a ON ah.appointment_id = a.id
                LEFT JOIN locations l ON ah.previous_location_id = l.id
                LEFT JOIN users u ON ah.changed_by_user_id = u.id
                WHERE a.customer_id = ?
                
                ORDER BY start_time DESC
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$clientId, $clientId]);
            
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($appointments)) {
                return [];
            }

            // Process each appointment to add additional information
            foreach ($appointments as &$appointment) {
                // Convert UTC times to ET
                $utcStart = new DateTime($appointment['start_time'], new DateTimeZone('UTC'));
                $utcStart->setTimezone(new DateTimeZone('America/New_York'));
                
                $utcEnd = new DateTime($appointment['end_time'], new DateTimeZone('UTC'));
                $utcEnd->setTimezone(new DateTimeZone('America/New_York'));

                // Format dates and times in ET
                $appointment['date_formatted'] = $utcStart->format('m/d/Y');
                $appointment['time_formatted'] = $utcStart->format('g:i A');
                
                // Calculate duration using ET times
                $interval = $utcStart->diff($utcEnd);
                $appointment['duration'] = $interval->format('%h hours %i minutes');
                
                // Get technician initials
                if (!empty($appointment['technician_name'])) {
                    $words = explode(' ', $appointment['technician_name']);
                    $initials = '';
                    foreach ($words as $word) {
                        $initials .= strtoupper(substr($word, 0, 1));
                    }
                    $appointment['technician_initials'] = $initials;
                } else {
                    $appointment['technician_initials'] = 'Unknown';
                }
                
                // Abbreviate appointment type
                $appointmentType = trim($appointment['appointment_type']);
                $found = false;
                foreach ($this->serviceAbbreviations as $fullName => $abbr) {
                    if (strcasecmp($appointmentType, $fullName) === 0) {
                        $appointment['appointment_type'] = $abbr;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $appointment['appointment_type'] = $appointmentType;
                }
                
                // Determine if appointment is upcoming or past using ET time
                $now = new DateTime('now', new DateTimeZone('America/New_York'));
                $appointmentTime = $utcStart;
                // If it's a historical appointment, always mark it as past
                $appointment['is_upcoming'] = $appointment['is_historical'] ? false : ($appointmentTime > $now);
            }

            return $appointments;
        } catch (Exception $e) {
            $this->logAppointmentServiceAction(
                "Error in getClientAppointments: " . $e->getMessage()
            );
            throw $e;
        }
    }

    /**
     * =============================================
     * GET SHOP APPOINTMENTS
     * =============================================
     * Retrieves all appointments for a specific shop on a specific date
     * 
     * @param int $shopId The ID of the shop/location
     * @param string $date The date to check (Y-m-d format)
     * @return array Array of appointment data with formatted times and related information
     * =============================================
     */
    public function getShopAppointments($shopId, $date) {
        try {
            // Validate shop ID
            if (!is_numeric($shopId) || $shopId <= 0) {
                throw new Exception("Invalid shop ID provided");
            }

            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                throw new Exception("Invalid date format. Expected Y-m-d");
            }

            // Log the request
            $this->logAppointmentServiceAction(
                "Fetching appointments for shop ID: " . $shopId . " on date: " . $date
            );

            // Query to get all appointments for the shop on the specified date
            $query = "
                SELECT 
                    a.id,
                    a.customer_id,
                    a.title,
                    a.appointment_type,
                    a.start_time,
                    a.end_time,
                    a.status,
                    a.service_note,
                    a.description,
                    a.created_by,
                    a.created_at,
                    c.first_name as customer_first_name,
                    c.last_name as customer_last_name,
                    u.full_name as technician_name,
                    vm.make as vehicle_make,
                    vmo.model as vehicle_model,
                    vy.year as vehicle_year,
                    CASE 
                        WHEN EXISTS (
                            SELECT 1 
                            FROM invoices i 
                            WHERE i.customer_id = a.customer_id 
                            AND DATE(i.created_at) = ?
                            AND i.status = 'paid'
                        ) THEN 1
                        ELSE 0
                    END as has_invoice_today
                FROM appointments a
                LEFT JOIN client_information c ON a.customer_id = c.id
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN vehicle_information vi ON a.customer_id = vi.customer_id
                LEFT JOIN vehicle_makes vm ON vi.make_id = vm.id
                LEFT JOIN vehicle_models vmo ON vi.model_id = vmo.id
                LEFT JOIN vehicle_years vy ON vi.year_id = vy.id
                WHERE a.location_id = ?
                AND DATE(a.start_time) = ?
                AND a.status != 'cancelled'
                GROUP BY a.id
                ORDER BY a.start_time ASC
            ";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$date, $shopId, $date]);
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($appointments)) {
                return [];
            }

            // Process each appointment to add additional information
            foreach ($appointments as &$appointment) {
                // Convert UTC times to ET
                $utcStart = new DateTime($appointment['start_time'], new DateTimeZone('UTC'));
                $utcStart->setTimezone(new DateTimeZone('America/New_York'));
                
                $utcEnd = new DateTime($appointment['end_time'], new DateTimeZone('UTC'));
                $utcEnd->setTimezone(new DateTimeZone('America/New_York'));

                // Format dates and times in ET
                $appointment['date_formatted'] = $utcStart->format('m/d/Y');
                $appointment['time_formatted'] = $utcStart->format('g:i A');
                $appointment['end_time_formatted'] = $utcEnd->format('g:i A');
                
                // Calculate duration using ET times
                $interval = $utcStart->diff($utcEnd);
                $appointment['duration'] = $interval->format('%h hours %i minutes');
                
                // Get technician initials
                if (!empty($appointment['technician_name'])) {
                    $words = explode(' ', $appointment['technician_name']);
                    $initials = '';
                    foreach ($words as $word) {
                        $initials .= strtoupper(substr($word, 0, 1));
                    }
                    $appointment['technician_initials'] = $initials;
                } else {
                    $appointment['technician_initials'] = 'Unknown';
                }
                
                // Format vehicle information
                $appointment['vehicle_info'] = '';
                if (!empty($appointment['vehicle_year']) && !empty($appointment['vehicle_make']) && !empty($appointment['vehicle_model'])) {
                    $appointment['vehicle_info'] = $appointment['vehicle_year'] . ' ' . 
                                                 $appointment['vehicle_make'] . ' ' . 
                                                 $appointment['vehicle_model'];
                }
                
                // Abbreviate appointment type
                $appointmentType = trim($appointment['appointment_type']);
                $found = false;
                foreach ($this->serviceAbbreviations as $fullName => $abbr) {
                    if (strcasecmp($appointmentType, $fullName) === 0) {
                        $appointment['appointment_type'] = $abbr;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $appointment['appointment_type'] = $appointmentType;
                }
            }

            return $appointments;
        } catch (Exception $e) {
            $this->logAppointmentServiceAction(
                "Error in getShopAppointments: " . $e->getMessage()
            );
            throw $e;
        }
    }
}
?> 