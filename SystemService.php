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
}
?> 