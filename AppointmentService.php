<?php
class AppointmentService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new appointment
     * @param array $data Appointment data
     * @return int The ID of the created appointment
     */
    public function createAppointment($data) {
        try {
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
                ':appointment_type' => $data['appointment_type'],
                ':start_time' => $data['start_time'],
                ':end_time' => $data['end_time'],
                ':location_id' => $data['location_id'],
                ':status' => $data['status'] ?? 'scheduled',
                ':service_note' => $data['service_note'] ?? null,
                ':description' => $data['description'] ?? null,
                ':created_by' => $_SESSION['user_id'] ?? null
            ]);
            
            return $this->pdo->lastInsertId();
            
        } catch (PDOException $e) {
            throw new Exception("Error creating appointment: " . $e->getMessage());
        }
    }
    
    /**
     * Get a single appointment by ID
     * @param int $id Appointment ID
     * @return array|null Appointment data or null if not found
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
     * Get appointments for a date range
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @param array $filters Optional filters
     * @return array Array of appointments
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
     * Update an appointment
     * @param int $id Appointment ID
     * @param array $data Updated appointment data
     * @param int $userId User making the update
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
     * Delete an appointment
     * @param int $id Appointment ID
     * @param int $userId User making the deletion
     * @param string|null $reason Reason for deletion
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
}
?> 