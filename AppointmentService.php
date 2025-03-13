<?php
class AppointmentService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Create a new appointment
    public function createAppointment($data) {
        // Validate appointment data
        $this->validateAppointmentData($data);
        
        // Check for conflicts
        if ($this->hasScheduleConflict($data['location_id'], $data['start_time'], $data['end_time'])) {
            throw new Exception("Schedule conflict detected");
        }
        
        // Insert appointment
        $stmt = $this->pdo->prepare("
            INSERT INTO appointments (lead_id, customer_id, title, appointment_type, service_note, 
                                     description, start_time, end_time, location_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['lead_id'] ?? null,
            $data['customer_id'] ?? null,
            $data['title'],
            $data['appointment_type'],
            $data['service_note'] ?? null,
            $data['description'] ?? null,
            $data['start_time'],
            $data['end_time'],
            $data['location_id'],
            $data['status'] ?? 'scheduled'
        ]);
        
        $appointmentId = $this->pdo->lastInsertId();
        
        // Update lead status if needed
        if (!empty($data['lead_id'])) {
            $this->updateLeadStatus($data['lead_id'], 'Scheduled');
        }
        
        return $appointmentId;
    }
    
    // Get appointment by ID
    public function getAppointment($id) {
        $stmt = $this->pdo->prepare("
            SELECT a.*, 
                   CONCAT(l.first_name, ' ', l.last_name) as lead_name,
                   CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                   loc.location_name
            FROM appointments a
            LEFT JOIN leads l ON a.lead_id = l.id
            LEFT JOIN client_information c ON a.customer_id = c.id
            LEFT JOIN locations loc ON a.location_id = loc.id
            WHERE a.id = ?
        ");
        
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Update an appointment
    public function updateAppointment($id, $data, $userId) {
        // Get current appointment data for history
        $currentAppointment = $this->getAppointment($id);
        if (!$currentAppointment) {
            throw new Exception("Appointment not found");
        }
        
        // Validate appointment data
        $this->validateAppointmentData($data);
        
        // Check for conflicts (except with itself)
        if ($this->hasScheduleConflict(
            $data['location_id'], 
            $data['start_time'], 
            $data['end_time'], 
            $id
        )) {
            throw new Exception("Schedule conflict detected");
        }
        
        // Save history record
        $this->saveAppointmentHistory($id, $currentAppointment, $userId, $data['change_reason'] ?? null);
        
        // Update appointment
        $stmt = $this->pdo->prepare("
            UPDATE appointments 
            SET lead_id = ?, 
                customer_id = ?,
                title = ?,
                appointment_type = ?,
                service_note = ?,
                description = ?,
                start_time = ?,
                end_time = ?,
                location_id = ?,
                status = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['lead_id'] ?? null,
            $data['customer_id'] ?? null,
            $data['title'],
            $data['appointment_type'],
            $data['service_note'] ?? null,
            $data['description'] ?? null,
            $data['start_time'],
            $data['end_time'],
            $data['location_id'],
            $data['status'] ?? 'scheduled',
            $id
        ]);
        
        // Clear Google sync data if significant changes were made
        if ($this->needsResync($currentAppointment, $data)) {
            $this->clearGoogleSyncData($id);
        }
        
        return true;
    }
    
    // Delete an appointment
    public function deleteAppointment($id, $userId, $reason = null) {
        // Get current appointment for history
        $currentAppointment = $this->getAppointment($id);
        if (!$currentAppointment) {
            throw new Exception("Appointment not found");
        }
        
        // Save to history
        $this->saveAppointmentHistory($id, $currentAppointment, $userId, $reason ?? "Appointment deleted");
        
        // Delete the appointment
        $stmt = $this->pdo->prepare("DELETE FROM appointments WHERE id = ?");
        $stmt->execute([$id]);
        
        return true;
    }
    
    // Get appointments for a date range
    public function getAppointmentsForRange($startDate, $endDate, $filters = []) {
        $where = ["(start_time >= ? AND start_time <= ?)"];
        $params = [$startDate, $endDate];
        
        // Add filters
        if (!empty($filters['location_id'])) {
            $where[] = "location_id = ?";
            $params[] = $filters['location_id'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['appointment_type'])) {
            $where[] = "appointment_type = ?";
            $params[] = $filters['appointment_type'];
        }
        
        if (!empty($filters['customer_id'])) {
            $where[] = "customer_id = ?";
            $params[] = $filters['customer_id'];
        }
        
        $whereClause = implode(" AND ", $where);
        
        $stmt = $this->pdo->prepare("
            SELECT a.*, 
                   CONCAT(l.first_name, ' ', l.last_name) as lead_name,
                   CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                   loc.location_name
            FROM appointments a
            LEFT JOIN leads l ON a.lead_id = l.id
            LEFT JOIN client_information c ON a.customer_id = c.id
            LEFT JOIN locations loc ON a.location_id = loc.id
            WHERE $whereClause
            ORDER BY start_time
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Check for scheduling conflicts
    private function hasScheduleConflict($locationId, $startTime, $endTime, $excludeAppointmentId = null) {
        $params = [$locationId, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime];
        $excludeClause = "";
        
        if ($excludeAppointmentId) {
            $excludeClause = "AND id != ?";
            $params[] = $excludeAppointmentId;
        }
        
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM appointments 
            WHERE location_id = ? 
            AND (
                (start_time <= ? AND end_time > ?) OR
                (start_time < ? AND end_time >= ?) OR
                (start_time >= ? AND end_time <= ?)
            )
            AND status NOT IN ('cancelled', 'completed')
            $excludeClause
        ");
        
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
    
    // Update lead status when scheduling
    private function updateLeadStatus($leadId, $status) {
        $stmt = $this->pdo->prepare("UPDATE leads SET status = ? WHERE id = ?");
        $stmt->execute([$status, $leadId]);
    }
    
    // Save appointment change history
    private function saveAppointmentHistory($appointmentId, $currentData, $userId, $reason = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO appointment_history 
            (appointment_id, previous_start, previous_end, previous_status, previous_location_id, changed_by_user_id, reason)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $appointmentId,
            $currentData['start_time'],
            $currentData['end_time'],
            $currentData['status'],
            $currentData['location_id'],
            $userId,
            $reason
        ]);
    }
    
    // Check if appointment needs Google Calendar resync
    private function needsResync($oldData, $newData) {
        // Check if any significant fields changed that would require Google resync
        return $oldData['start_time'] != $newData['start_time'] ||
               $oldData['end_time'] != $newData['end_time'] ||
               $oldData['title'] != $newData['title'] ||
               $oldData['status'] != $newData['status'] ||
               $oldData['location_id'] != $newData['location_id'];
    }
    
    // Clear Google sync data when appointment changes significantly
    private function clearGoogleSyncData($appointmentId) {
        $stmt = $this->pdo->prepare("
            UPDATE appointments
            SET google_event_id = NULL, last_sync = NULL
            WHERE id = ?
        ");
        $stmt->execute([$appointmentId]);
    }
    
    // Validate appointment data
    private function validateAppointmentData($data) {
        // Ensure required fields are present
        $requiredFields = ['start_time', 'end_time', 'location_id', 'title', 'appointment_type'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Validate date/time values
        if (strtotime($data['start_time']) >= strtotime($data['end_time'])) {
            throw new Exception("End time must be after start time");
        }
        
        // Ensure at least one of lead_id or customer_id is set
        if (empty($data['lead_id']) && empty($data['customer_id'])) {
            throw new Exception("Either lead_id or customer_id must be provided");
        }
        
        // Validate appointment_type
        $validTypes = ['Install', 'Recalibration', 'Removal_Download', 'Service', 'Paper_Swap'];
        if (!in_array($data['appointment_type'], $validTypes)) {
            throw new Exception("Invalid appointment type");
        }
        
        // If appointment is of type Service, service_note is required
        if ($data['appointment_type'] == 'Service' && empty($data['service_note'])) {
            throw new Exception("Service note is required for Service appointment type");
        }
        
        return true;
    }
}
?>