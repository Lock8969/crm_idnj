<?php
/**
 * LeadsService
 * 
 * Service class for handling lead-related operations
 */

class LeadsService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        // Log constructor call
        $logFile = 'leads_service_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "\n" . str_repeat("=", 80) . "\n";
        $logMessage .= "LEADS SERVICE CONSTRUCTOR CALLED - $timestamp\n";
        $logMessage .= str_repeat("=", 80) . "\n";
        $logMessage .= "PDO Connection Status: " . ($pdo ? "Connected" : "Not Connected") . "\n";
        $existingContent = file_exists($logFile) ? file_get_contents($logFile) : '';
        file_put_contents($logFile, $logMessage . $existingContent);
    }
    
    /**
     * Log service actions
     * 
     * @param string $message The message to log
     * @param array $data Optional data to include in the log
     */
    private function logAction($message, $data = null) {
        $logFile = 'leads_service_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "\n" . str_repeat("=", 80) . "\n";
        $logMessage .= "LEADS SERVICE ACTION - $timestamp\n";
        $logMessage .= str_repeat("=", 80) . "\n";
        $logMessage .= "Message: $message\n";
        if ($data !== null) {
            $logMessage .= "Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }
        $existingContent = file_exists($logFile) ? file_get_contents($logFile) : '';
        file_put_contents($logFile, $logMessage . $existingContent);
    }

    /**
     * Get the last 50 call logs
     * 
     * @param string $search Optional search term to filter results
     * @return array Array of call logs
     */
    public function getRecentCallLogs($search = '') {
        try {
            $query = "
                SELECT 
                    cl.*,
                    c.first_name,
                    c.last_name,
                    c.phone_number
                FROM call_log cl
                LEFT JOIN client_information c ON c.phone_number = cl.to_number
            ";
            
            $params = [];
            
            if (!empty($search)) {
                $query .= " WHERE 
                    cl.from_number LIKE ? OR 
                    cl.to_number LIKE ? OR 
                    cl.source LIKE ? OR 
                    CONCAT(c.first_name, ' ', c.last_name) LIKE ?";
                $searchParam = "%$search%";
                $params = [$searchParam, $searchParam, $searchParam, $searchParam];
            }
            
            $query .= " ORDER BY cl.created_at DESC LIMIT 50";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert timestamps to Eastern Time with DST adjustment
            foreach ($logs as &$log) {
                if (isset($log['created_at'])) {
                    // Create DateTime object from UTC time
                    $date = new DateTime($log['created_at'], new DateTimeZone('UTC'));
                    
                    // Convert to Eastern Time
                    $date->setTimezone(new DateTimeZone('America/New_York'));
                    
                    // Update the created_at field with the converted time in 12-hour format
                    $log['created_at'] = $date->format('Y-m-d h:i A');
                }
            }
            
            // Log the action
            $this->logAction('Fetched recent call logs', ['count' => count($logs), 'search' => $search]);
            
            return $logs;
        } catch(PDOException $e) {
            $this->logAction('Error fetching call logs: ' . $e->getMessage());
            return [];
        }
    }
} 