<?php
//------------------------
// NOTES: Class Definition
//------------------------
// This class handles all receipt-related data operations
// It acts as a service layer between the database and the view
class ReceiptsService {
    //------------------------
    // NOTES: Class Properties
    //------------------------
    // $pdo: Database connection object
    // $logFile: Path to the log file where receipt data will be stored
    private $pdo;
    private $logFile = 'receipt_data.txt';

    //------------------------
    // NOTES: Service Abbreviations
    //------------------------
    // Mapping of full service names to their abbreviations
    // These names exactly match the services table in the database
    private $serviceAbbreviations = [
        'Final Download' => 'FD',
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

    //------------------------
    // NOTES: Constructor
    //------------------------
    // Initializes the service with a database connection
    // @param $pdo: PDO database connection object
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    //------------------------
    // NOTES: getClientReceipts Method
    //------------------------
    // Fetches all receipts for a specific client
    // @param $clientId: The ID of the client to fetch receipts for
    // @return: Array of receipt data including associated services
    public function getClientReceipts($clientId) {
        try {
            // Add debug logging
            error_log("Fetching receipts for client ID: " . $clientId);
            
            // First check if there are any invoices for this client
            $check_query = "SELECT COUNT(*) as count FROM invoices WHERE customer_id = :client_id";
            $check_stmt = $this->pdo->prepare($check_query);
            $check_stmt->execute(['client_id' => $clientId]);
            $invoice_count = $check_stmt->fetch()['count'];
            error_log("Direct invoice count for client " . $clientId . ": " . $invoice_count);

            // Log the actual query being executed
            $query = "
                SELECT 
                    i.id,
                    i.created_at,
                    i.status,
                    i.total_amount,
                    i.rent_total as rent_collected,
                    i.service_total as services_collected,
                    i.tax_amount as tax_collected,
                    i.created_by,
                    i.location_id,
                    COALESCE(
                        (SELECT GROUP_CONCAT(DISTINCT s.name ORDER BY s.name ASC)
                         FROM invoice_services isv
                         JOIN services s ON isv.service_id = s.id
                         WHERE isv.invoice_id = i.id),
                        'None'
                    ) as service_names
                FROM invoices i
                WHERE i.customer_id = :client_id
                ORDER BY i.created_at DESC
            ";
            
            error_log("Executing query: " . $query);
            error_log("With parameters: client_id = " . $clientId);

            $stmt = $this->pdo->prepare($query);
            $stmt->execute(['client_id' => $clientId]);
            
            // Check for errors
            $error = $stmt->errorInfo();
            if ($error[0] !== '00000') {
                error_log("SQL Error: " . print_r($error, true));
            }
            
            $receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Log the raw results
            error_log("Raw query results: " . print_r($receipts, true));
            error_log("Number of receipts found: " . count($receipts));

            //------------------------
            // NOTES: Service Abbreviation Processing
            //------------------------
            // Process each receipt to abbreviate service names and add location/tech info
            foreach ($receipts as &$receipt) {
                if (!empty($receipt['service_names'])) {
                    $services = explode(',', $receipt['service_names']);
                    $abbreviatedServices = [];
                    foreach ($services as $service) {
                        // Trim whitespace and match case-insensitively
                        $service = trim($service);
                        $found = false;
                        foreach ($this->serviceAbbreviations as $fullName => $abbr) {
                            if (strcasecmp($service, $fullName) === 0) {
                                $abbreviatedServices[] = $abbr;
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $abbreviatedServices[] = $service;
                        }
                    }
                    $receipt['service_names'] = implode(', ', $abbreviatedServices);
                } else {
                    $receipt['service_names'] = 'None';
                }

                // Get location name
                if (!empty($receipt['location_id'])) {
                    $loc_stmt = $this->pdo->prepare("SELECT location_name FROM locations WHERE id = ?");
                    $loc_stmt->execute([$receipt['location_id']]);
                    $location = $loc_stmt->fetch();
                    $receipt['location_name'] = $location ? $location['location_name'] : 'Unknown';
                } else {
                    $receipt['location_name'] = 'Unknown';
                }

                // Get technician initials
                if (!empty($receipt['created_by'])) {
                    $tech_stmt = $this->pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                    $tech_stmt->execute([$receipt['created_by']]);
                    $technician = $tech_stmt->fetch();
                    if ($technician && !empty($technician['full_name'])) {
                        // Get initials from full name
                        $words = explode(' ', $technician['full_name']);
                        $initials = '';
                        foreach ($words as $word) {
                            $initials .= strtoupper(substr($word, 0, 1));
                        }
                        $receipt['technician_initials'] = $initials;
                    } else {
                        $receipt['technician_initials'] = 'Unknown';
                    }
                } else {
                    $receipt['technician_initials'] = 'Unknown';
                }
            }

            //------------------------
            // NOTES: Data Logging
            //------------------------
            $this->logReceiptData($clientId, $receipts);

            return $receipts;
        } catch (PDOException $e) {
            error_log("Error in ReceiptsService::getClientReceipts: " . $e->getMessage());
            return [];
        }
    }

    //------------------------
    // NOTES: logReceiptData Method
    //------------------------
    // Private helper method to format and log receipt data
    // @param $clientId: The ID of the client
    // @param $receipts: Array of receipt data to log
    private function logReceiptData($clientId, $receipts) {
        //------------------------
        // NOTES: Log Header
        //------------------------
        // Creates a header for each log entry with client ID and timestamp
        $logContent = "=== Receipt Data for Client ID: {$clientId} ===\n";
        $logContent .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";

        //------------------------
        // NOTES: Data Formatting
        //------------------------
        // Formats each receipt's data into a readable string
        if (empty($receipts)) {
            $logContent .= "No receipts found for this client.\n";
        } else {
            foreach ($receipts as $receipt) {
                $logContent .= "Invoice ID: {$receipt['id']}\n";
                $logContent .= "Date: " . date('m/d/Y', strtotime($receipt['created_at'])) . "\n";
                $logContent .= "Status: {$receipt['status']}\n";
                $logContent .= "Total Amount: $" . number_format($receipt['total_amount'], 2) . "\n";
                $logContent .= "Services: " . ($receipt['service_names'] ?: 'None') . "\n";
                $logContent .= "Services Paid: $" . number_format($receipt['services_collected'], 2) . "\n";
                $logContent .= "Rent Collected: $" . number_format($receipt['rent_collected'], 2) . "\n";
                $logContent .= "Tax Collected: $" . number_format($receipt['tax_collected'], 2) . "\n";
                $logContent .= "Location: " . $receipt['location_name'] . "\n";
                $logContent .= "Technician: " . $receipt['technician_initials'] . "\n";
                $logContent .= "----------------------------------------\n";
            }
        }

        $logContent .= "\n\n";
        
        //------------------------
        // NOTES: File Writing
        //------------------------
        // Appends the formatted log content to the log file
        file_put_contents($this->logFile, $logContent, FILE_APPEND);
    }
} 