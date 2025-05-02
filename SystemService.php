<?php
/**
 * SystemService Class
 * Handles all system-level operations including user management
 */
class SystemService {
    private $db;

    /**
     * Constructor
     * Establishes database connection using MySQLi
     * Throws error if connection fails
     */
    public function __construct() {
        $this->db = new mysqli('localhost', 'xpjjbrbbmv', 'Fs8YyHyejv', 'xpjjbrbbmv');
        
        if ($this->db->connect_error) {
            die("Connection failed: " . $this->db->connect_error);
        }
    }

    /**------------------------------------------------------------
     * CREATE USER METHOD
     * ------------------------------------------------------------
     * Creates a new user in the system
     * 
     * @param string $username - User's login username
     * @param string $email - User's email address
     * @param string $password - User's password (will be hashed)
     * @param string $fullName - User's full name
     * @param string $role - User's role (defaults to 'Sales Rep')
     * 
     * @return array - Contains success status and message
     * 
     * Validates:
     * - Required fields (username, password, full_name)
     * - Username uniqueness
     * 
     * Features:
     * - Password hashing
     * - Role assignment
     * - Error handling
     */
    public function createUser($username, $email, $password, $fullName, $role = 'Sales Rep') {
        // Validate input
        if (empty($username) || empty($password) || empty($fullName)) {
            return ['success' => false, 'message' => 'Username, password, and full name are required'];
        }

        // Check if username already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'Username already exists'];
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, $passwordHash, $fullName, $role);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'User created successfully'];
        } else {
            return ['success' => false, 'message' => 'Error creating user: ' . $stmt->error];
        }
    }

    /**------------------------------------------------------------
     * UPDATE USER METHOD
     * ------------------------------------------------------------
     * Updates an existing user's information
     * 
     * @param array $data - Contains user update information:
     *   - user_id (required) - ID of user to update
     *   - username (optional) - New username
     *   - email (optional) - New email
     *   - full_name (optional) - New full name
     *   - role (optional) - New role
     *   - password (optional) - New password (will be hashed)
     * 
     * @return array - Contains success status and message
     * 
     * Validates:
     * - User existence
     * - Username uniqueness (if username is being updated)
     * - At least one field to update
     * 
     * Features:
     * - Dynamic query building (only updates provided fields)
     * - Password hashing
     * - Prepared statements for security
     * - Error handling
     */
    public function updateUser($data) {
        // Validate required fields
        if (empty($data['user_id'])) {
            return ['success' => false, 'message' => 'User ID is required'];
        }

        // Check if user exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->bind_param("i", $data['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // If this is a delete action, just update the active status
        if (isset($data['active']) && $data['active'] === 0) {
            $stmt = $this->db->prepare("UPDATE users SET active = 0 WHERE id = ?");
            $stmt->bind_param("i", $data['user_id']);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'User deactivated successfully'];
            } else {
                return ['success' => false, 'message' => 'Error deactivating user: ' . $stmt->error];
            }
        }

        // Build update query dynamically based on provided fields
        $updates = [];
        $params = [];
        $types = "";

        // Username update with uniqueness check
        if (!empty($data['username'])) {
            // Check if new username is already taken by another user
            $check = $this->db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check->bind_param("si", $data['username'], $data['user_id']);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                return ['success' => false, 'message' => 'Username already taken'];
            }
            $updates[] = "username = ?";
            $params[] = $data['username'];
            $types .= "s";
        }

        // Email update
        if (!empty($data['email'])) {
            $updates[] = "email = ?";
            $params[] = $data['email'];
            $types .= "s";
        }

        // Full name update
        if (!empty($data['full_name'])) {
            $updates[] = "full_name = ?";
            $params[] = $data['full_name'];
            $types .= "s";
        }

        // Role update
        if (!empty($data['role'])) {
            $updates[] = "role = ?";
            $params[] = $data['role'];
            $types .= "s";
        }

        // Password update (only if provided)
        if (!empty($data['password'])) {
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            $updates[] = "password_hash = ?";
            $params[] = $passwordHash;
            $types .= "s";
        }

        // If no fields to update
        if (empty($updates)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }

        // Add user_id to params
        $params[] = $data['user_id'];
        $types .= "i";

        // Build and execute the update query
        $query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'User updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Error updating user: ' . $stmt->error];
        }
    }

    /**
     * =============================================
     * HANDLE TIME CLOCK
     * =============================================
     * Manages clock in/out operations for users
     * 
     * @param array $data Contains:
     *   - user_id: ID of the user
     *   - location_id: ID of the location
     *   - clock_in_time: DateTime for clock in (if clocking in)
     *   - clock_out_time: DateTime for clock out (if clocking out)
     *   - additional_time: Additional minutes (only for clock out)
     *   - notes: Any notes for the time entry
     *   - action: Optional - 'check_status' to only check if user has active entry
     * 
     * @return array Contains success status and message
     */
    public function handleTimeClock($data) {
        try {
            // Validate required fields
            if (empty($data['user_id'])) {
                return ['success' => false, 'message' => 'User ID is required'];
            }

            // Get device type from data
            $browser = isset($data['device_type']) ? $data['device_type'] : 'other';

            // If just checking status
            if (isset($data['action']) && $data['action'] === 'check_status') {
                $stmt = $this->db->prepare("
                    SELECT id FROM time_table 
                    WHERE user_id = ? AND status = 'active'
                ");
                $stmt->bind_param("i", $data['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                return [
                    'success' => true,
                    'has_active_entry' => $result->num_rows > 0
                ];
            }

            // Validate location_id for actual clock in/out operations
            if (empty($data['location_id'])) {
                return ['success' => false, 'message' => 'Location ID is required'];
            }

            // Get user's full name and location name
            $stmt = $this->db->prepare("
                SELECT u.full_name, l.location_name 
                FROM users u 
                JOIN locations l ON l.id = ? 
                WHERE u.id = ?
            ");
            $stmt->bind_param("ii", $data['location_id'], $data['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $userInfo = $result->fetch_assoc();

            if (!$userInfo) {
                return ['success' => false, 'message' => 'User or location not found'];
            }

            // If clocking in
            if (!empty($data['clock_in_time'])) {
                // Check if user already has an active time entry
                $stmt = $this->db->prepare("
                    SELECT id FROM time_table 
                    WHERE user_id = ? AND status = 'active'
                ");
                $stmt->bind_param("i", $data['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    return ['success' => false, 'message' => 'User already has an active time entry'];
                }

                // Create new time entry
                $stmt = $this->db->prepare("
                    INSERT INTO time_table (
                        user_id, location_id, clock_in_time, status, clock_in_browser, notes
                    ) VALUES (?, ?, ?, 'active', ?, ?)
                ");
                $stmt->bind_param("iisss", 
                    $data['user_id'], 
                    $data['location_id'], 
                    $data['clock_in_time'],
                    $browser,
                    $data['notes']
                );
                
                if ($stmt->execute()) {
                    return [
                        'success' => true, 
                        'message' => 'Successfully clocked in',
                        'log_id' => $stmt->insert_id,
                        'full_name' => $userInfo['full_name'],
                        'location_name' => $userInfo['location_name']
                    ];
                } else {
                    return ['success' => false, 'message' => 'Error clocking in: ' . $stmt->error];
                }
            }
            
            // If clocking out
            if (!empty($data['clock_out_time'])) {
                // Find the active time entry for this user
                $stmt = $this->db->prepare("
                    SELECT id FROM time_table 
                    WHERE user_id = ? AND status = 'active'
                ");
                $stmt->bind_param("i", $data['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    return ['success' => false, 'message' => 'No active time entry found for user'];
                }

                $row = $result->fetch_assoc();
                $log_id = $row['id'];

                // Update the time entry with clock out time
                $stmt = $this->db->prepare("
                    UPDATE time_table 
                    SET clock_out_time = ?,
                        additional_time = ?,
                        notes = ?,
                        clock_out_browser = ?,
                        status = 'completed'
                    WHERE user_id = ? AND status = 'active'
                ");
                $stmt->bind_param("sissi", 
                    $data['clock_out_time'],
                    $data['additional_time'],
                    $data['notes'],
                    $browser,
                    $data['user_id']
                );
                
                if ($stmt->execute()) {
                    return [
                        'success' => true, 
                        'message' => 'Successfully clocked out',
                        'log_id' => $log_id,
                        'full_name' => $userInfo['full_name'],
                        'location_name' => $userInfo['location_name']
                    ];
                } else {
                    return ['success' => false, 'message' => 'Error clocking out: ' . $stmt->error];
                }
            }

            return ['success' => false, 'message' => 'Invalid time clock operation'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * =============================================
     * GET TIME CLOCK REPORT
     * =============================================
     * Fetches time clock entries for a given date range
     * 
     * @param array $data Contains:
     *   - start_date: Start date in Y-m-d format
     *   - end_date: End date in Y-m-d format
     *   - location_id: Optional - Filter by location
     *   - user_id: Optional - Filter by user
     * 
     * @return array Array of time entries with user and location details
     */
    public function getTimeClockReport($data) {
        try {
            // Validate required fields
            if (empty($data['start_date']) || empty($data['end_date'])) {
                return ['success' => false, 'message' => 'Start date and end date are required'];
            }

            // Build the base query
            $sql = "
                SELECT 
                    t.id,
                    t.clock_in_time,
                    t.clock_out_time,
                    t.additional_time,
                    t.notes,
                    t.status,
                    u.full_name as user_name,
                    l.location_name,
                    l.location_type,
                    TIMESTAMPDIFF(MINUTE, t.clock_in_time, COALESCE(t.clock_out_time, NOW())) + COALESCE(t.additional_time, 0) as total_minutes
                FROM time_table t
                JOIN users u ON u.id = t.user_id
                JOIN locations l ON l.id = t.location_id
                WHERE DATE(t.clock_in_time) BETWEEN ? AND ?
            ";

            $params = [$data['start_date'], $data['end_date']];
            $types = "ss";

            // Add optional filters
            if (!empty($data['location_id'])) {
                $sql .= " AND t.location_id = ?";
                $params[] = $data['location_id'];
                $types .= "i";
            }

            if (!empty($data['user_id'])) {
                $sql .= " AND t.user_id = ?";
                $params[] = $data['user_id'];
                $types .= "i";
            }

            // Order by clock in time
            $sql .= " ORDER BY t.clock_in_time DESC";

            // Prepare and execute the query
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->db->error);
            }

            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            // Process the results
            $entries = [];
            while ($row = $result->fetch_assoc()) {
                // Format dates and times
                $row['clock_in_time'] = date('Y-m-d g:i A', strtotime($row['clock_in_time']));
                if ($row['clock_out_time']) {
                    $row['clock_out_time'] = date('Y-m-d g:i A', strtotime($row['clock_out_time']));
                }

                // Calculate hours and minutes
                $totalMinutes = $row['total_minutes'];
                $hours = floor($totalMinutes / 60);
                $minutes = $totalMinutes % 60;
                $row['total_time'] = sprintf('%d hours %d minutes', $hours, $minutes);

                // Add status label
                $row['status_label'] = $row['status'] === 'active' ? 'Active' : 'Completed';

                $entries[] = $row;
            }

            return [
                'success' => true,
                'data' => $entries,
                'total_entries' => count($entries)
            ];

        } catch (Exception $e) {
            error_log("Time Clock Report Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error generating time clock report: ' . $e->getMessage()
            ];
        }
    }
}
?> 