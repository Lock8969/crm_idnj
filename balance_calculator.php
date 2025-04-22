<?php
/**
 * =============================================
 * BALANCE CALCULATOR
 * =============================================
 * 
 * This file contains functions for calculating account balances and rent due.
 * It also serves as an API endpoint for balance calculations.
 * 
 * Functions:
 * - calculateRentBalance(): Calculates rent balance based on installation date
 * - getBalanceBreakdown(): Provides detailed breakdown of all balance components
 * 
 * API Endpoint:
 * - GET /balance_calculator.php?customer_id=X
 * 
 * =============================================
 */

require_once 'db.php';

// If this is an API request, handle it
if (isset($_GET['customer_id'])) {
    // Set headers for JSON response
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Content-Type');

    try {
        // Set timezone to Eastern Time
        $et = new DateTimeZone('America/New_York');
        
        // Get today's date in ET
        $today = new DateTime('now', $et);
        
        // Get the date parameter if provided, otherwise use today
        if (isset($_GET['date'])) {
            // If date is provided, ensure it's interpreted in ET
            $date = new DateTime($_GET['date'], $et);
            
            // Calculate days between today and provided date (inclusive)
            $days_diff = $today->diff($date)->days + 1; // Add 1 to include both dates
            
            // Add to log entry
            $logEntry = str_repeat("=", 50) . "\n";
            $logEntry .= "TIMESTAMP: " . date('Y-m-d H:i:s') . "\n";
            $logEntry .= "CUSTOMER ID: " . $_GET['customer_id'] . "\n";
            $logEntry .= "TODAY'S DATE: " . $today->format('Y-m-d') . "\n";
            $logEntry .= "PROVIDED DATE: " . $date->format('Y-m-d') . "\n";
            $logEntry .= "DAYS BETWEEN (INCLUSIVE): " . $days_diff . "\n";
            $logEntry .= str_repeat("=", 50) . "\n";
            
            // Write to log file
            $logFile = 'balance_calc_log.txt';
            $existingContent = file_exists($logFile) ? file_get_contents($logFile) : '';
            file_put_contents($logFile, $logEntry . $existingContent);
        } else {
            $date = $today;
        }
        
        // Format the date for the calculation
        $formatted_date = $date->format('Y-m-d');
        
        // Get the rent balance
        $rent_balance = calculateRentBalance($_GET['customer_id'], $formatted_date);
        
        // Get today's balance
        $today_balance = calculateRentBalance($_GET['customer_id'], $today->format('Y-m-d'));
        
        // Return the results
        echo json_encode([
            'success' => true,
            'data' => [
                'rent_balance' => $rent_balance,
                'today_balance' => $today_balance,
                'calculation_date' => $formatted_date,
                'today_date' => $today->format('Y-m-d'),
                'timezone' => 'America/New_York',
                'is_dst' => $date->format('I') // 1 if daylight savings, 0 if not
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error calculating balance: ' . $e->getMessage()
        ]);
    }
    exit;
}

/**
 * =============================================
 * CALCULATE RENT BALANCE
 * =============================================
 * 
 * Calculates the total rent balance due for a client as of a specific date or dates.
 * 
 * Calculation Process:
 * 1. Gets client's installation date and price code
 * 2. Calculates number of days from installation to specified date(s) (inclusive)
 * 3. Multiplies days by price code to get total rent owed
 * 4. Sums up all total payments from invoices
 * 5. Returns the difference between rent owed and total collected
 * 
 * @param int $customer_id The ID of the customer
 * @param string|array|null $dates The date(s) to calculate balance as of (defaults to today)
 * @return float|array The calculated balance(s)
 * =============================================
 */
function calculateRentBalance($customer_id, $dates = null) {
    global $pdo;
    
    // Create log entry
    $logEntry = str_repeat("=", 50) . "\n";
    $logEntry .= "START LOG\n";
    $logEntry .= str_repeat("=", 50) . "\n";
    $logEntry .= "TIMESTAMP: " . date('Y-m-d H:i:s') . "\n";
    $logEntry .= "CUSTOMER ID: " . $customer_id . "\n";
    $logEntry .= "DATES: " . (is_array($dates) ? implode(", ", $dates) : $dates) . "\n";
    
    // Add error checking for log file
    $logFile = 'balance_calc_log.txt';
    if (!file_exists($logFile)) {
        error_log("Log file does not exist: " . $logFile);
    }
    if (!is_writable($logFile)) {
        error_log("Log file is not writable: " . $logFile);
    }
    
    try {
        // Set timezone to Eastern Time
        $et = new DateTimeZone('America/New_York');
        $utc = new DateTimeZone('UTC');
        
        // Get today's date in ET
        $today = new DateTime('now', $et);
        $today_str = $today->format('Y-m-d');
        
        // If no date provided, use today's date
        if ($dates === null) {
            $dates = [$today_str];
        }
        
        // If $dates is a string (single date), convert to array
        if (is_string($dates)) {
            $dates = [$dates];
        }
        
        // Get client's installation date and price code
        $stmt = $pdo->prepare("SELECT install_on, price_code FROM client_information WHERE id = ?");
        $stmt->execute([$customer_id]);
        $client_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $logEntry .= "CLIENT INFO:\n";
        $logEntry .= "- Installation Date: " . $client_info['install_on'] . "\n";
        $logEntry .= "- Price Code: $" . $client_info['price_code'] . "\n";
        
        // If no installation date, return 0 or array of zeros
        if (!$client_info['install_on']) {
            $logEntry .= "NO INSTALLATION DATE FOUND - RETURNING 0\n";
            $logEntry .= str_repeat("=", 50) . "\n";
            $logEntry .= "END LOG\n";
            $logEntry .= str_repeat("=", 50) . "\n\n";
            
            // Read existing content
            $existingContent = file_exists($logFile) ? file_get_contents($logFile) : '';
            // Prepend new log entry
            $writeResult = file_put_contents($logFile, $logEntry . $existingContent);
            if ($writeResult === false) {
                error_log("Failed to write to log file: " . $logFile);
                error_log("Log entry content: " . $logEntry);
            }
            
            if (count($dates) === 1) {
                return 0.00;
            } else {
                $result = [];
                foreach ($dates as $date) {
                    $result[$date] = 0.00;
                }
                return $result;
            }
        }
        
        // Get total collected from invoices (only need to do this once)
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(sub_total_collected), 0) as sub_total_collected FROM invoices WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $sub_total_collected = $stmt->fetch(PDO::FETCH_ASSOC)['sub_total_collected'];
        
        // Get total services charged from invoice_services (only need to do this once)
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(inv_serv.price_at_time), 0) as total_services_charged 
            FROM invoice_services inv_serv
            JOIN invoices i ON i.id = inv_serv.invoice_id 
            WHERE i.customer_id = ?
        ");
        $stmt->execute([$customer_id]);
        $total_services_charged = $stmt->fetch(PDO::FETCH_ASSOC)['total_services_charged'];
        
        $logEntry .= "TOTAL COLLECTED: $" . $sub_total_collected . "\n";
        $logEntry .= "TOTAL SERVICES CHARGED: $" . $total_services_charged . "\n";
        
        // Calculate balances for each date
        $results = [];
        
        // Create installation date in UTC (from database) and convert to ET
        $install_date = new DateTime($client_info['install_on'], $utc);
        $install_date->setTimezone($et);
        
        // Calculate today's balance first
        $days_from_install_today = $install_date->diff($today)->days + 1;
        $rent_owed_today = $days_from_install_today * $client_info['price_code'];
        $total_owed_today = $rent_owed_today + $total_services_charged;
        $balance_today = round($total_owed_today - $sub_total_collected, 2);
        
        $logEntry .= "\nBALANCE AS OF TODAY (" . $today_str . "):\n";
        $logEntry .= "- Days from install: " . $days_from_install_today . "\n";
        $logEntry .= "- Rent owed: $" . $rent_owed_today . "\n";
        $logEntry .= "- Services charged: $" . $total_services_charged . "\n";
        $logEntry .= "- Total owed: $" . $total_owed_today . "\n";
        $logEntry .= "- Balance: $" . $balance_today . "\n";
        
        foreach ($dates as $date) {
            $logEntry .= "\nCALCULATION FOR DATE: " . $date . "\n";
            
            // Create end date in ET
            $end_date = new DateTime($date, $et);
            
            // Calculate number of days from installation to specified date (inclusive)
            $days_from_install = $install_date->diff($end_date)->days + 1; // Add 1 to include both dates
            
            // Calculate days between today and provided date (inclusive)
            $days_from_today = $today->diff($end_date)->days + 1; // Add 1 to include both dates
            $amount_from_today = $days_from_today * $client_info['price_code'];
            
            $logEntry .= "- Days from install: " . $days_from_install . "\n";
            $logEntry .= "- Days from today: " . $days_from_today . " ($" . number_format($amount_from_today, 2) . ")\n";
            
            // Calculate total rent owed
            $rent_owed = $days_from_install * $client_info['price_code'];
            $total_owed = $rent_owed + $total_services_charged;
            
            $logEntry .= "- Rent owed: $" . $rent_owed . "\n";
            $logEntry .= "- Services charged: $" . $total_services_charged . "\n";
            $logEntry .= "- Total owed: $" . $total_owed . "\n";
            $logEntry .= "- Balance calculation: $" . $total_owed . " - $" . $sub_total_collected . " = $" . ($total_owed - $sub_total_collected) . "\n";
            
            // Calculate and store the balance
            $results[$date] = round($total_owed - $sub_total_collected, 2);
        }
        
        $logEntry .= "\nFINAL RESULTS:\n";
        foreach ($results as $date => $result) {
            $logEntry .= "- " . $date . ": $" . $result . "\n";
        }
        $logEntry .= str_repeat("=", 50) . "\n";
        $logEntry .= "END LOG\n";
        $logEntry .= str_repeat("=", 50) . "\n\n";
        
        // Read existing content and prepend new log entry
        $existingContent = file_exists($logFile) ? file_get_contents($logFile) : '';
        $writeResult = file_put_contents($logFile, $logEntry . $existingContent);
        if ($writeResult === false) {
            error_log("Failed to write to log file: " . $logFile);
            error_log("Log entry content: " . $logEntry);
        }
        
        // If original input was a single date, return just that value
        if (count($dates) === 1) {
            return reset($results);
        }
        
        // Otherwise return all results
        return $results;
        
    } catch (PDOException $e) {
        $logEntry .= "\nERROR: " . $e->getMessage() . "\n";
        $logEntry .= str_repeat("=", 50) . "\n";
        $logEntry .= "END LOG\n";
        $logEntry .= str_repeat("=", 50) . "\n\n";
        
        // Read existing content and prepend new log entry
        $existingContent = file_exists($logFile) ? file_get_contents($logFile) : '';
        $writeResult = file_put_contents($logFile, $logEntry . $existingContent);
        if ($writeResult === false) {
            error_log("Failed to write error to log file: " . $logFile);
            error_log("Error log entry content: " . $logEntry);
        }
        
        error_log("Error calculating rent balance: " . $e->getMessage());
        if (count($dates) === 1) {
            return 0.00;
        } else {
            $result = [];
            foreach ($dates as $date) {
                $result[$date] = 0.00;
            }
            return $result;
        }
    }
}

/**
 * =============================================
 * GET BALANCE BREAKDOWN
 * =============================================
 * 
 * Provides a detailed breakdown of all balance components for a client.
 * 
 * Components:
 * - total_charged: Sum of all invoices
 * - total_paid: Sum of all payments
 * - balance_due: Difference between charged and paid
 * - remaining_rent: Remaining rent balance
 * - remaining_services: Remaining service charges
 * - remaining_tax: Remaining tax amount
 * 
 * @param int $customer_id The ID of the customer
 * @param string|null $as_of_date The date to calculate balance as of (defaults to today)
 * @return array Array containing all balance components
 * =============================================
 */
function getBalanceBreakdown($customer_id, $as_of_date = null) {
    global $pdo;
    
    try {
        // If no date provided, use today's date
        if (!$as_of_date) {
            $as_of_date = date('Y-m-d');
        }
        
        // Get total charged from invoices up to the specified date
        $stmt = $pdo->prepare("SELECT 
            COALESCE(SUM(total_amount), 0) as total_charged,
            COALESCE(SUM(rent_total), 0) as rent_charged,
            COALESCE(SUM(service_total), 0) as service_charged,
            COALESCE(SUM(tax_amount), 0) as tax_charged
            FROM invoices 
            WHERE customer_id = ? 
            AND invoice_date <= ?");
        $stmt->execute([$customer_id, $as_of_date]);
        $charged = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get total paid from invoices up to the specified date
        $stmt = $pdo->prepare("SELECT 
            COALESCE(SUM(rent_collected + services_collected + tax_collected), 0) as total_paid,
            COALESCE(SUM(rent_collected), 0) as rent_paid,
            COALESCE(SUM(services_collected), 0) as service_paid,
            COALESCE(SUM(tax_collected), 0) as tax_paid
            FROM invoices 
            WHERE customer_id = ? 
            AND invoice_date <= ?");
        $stmt->execute([$customer_id, $as_of_date]);
        $paid = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate remaining balances
        return [
            'total_charged' => round($charged['total_charged'], 2),
            'total_paid' => round($paid['total_paid'], 2),
            'balance_due' => round($charged['total_charged'] - $paid['total_paid'], 2),
            'remaining_rent' => round($charged['rent_charged'] - $paid['rent_paid'], 2),
            'remaining_services' => round($charged['service_charged'] - $paid['service_paid'], 2),
            'remaining_tax' => round($charged['tax_charged'] - $paid['tax_paid'], 2)
        ];
    } catch (PDOException $e) {
        error_log("Error getting balance breakdown: " . $e->getMessage());
        return [
            'total_charged' => 0.00,
            'total_paid' => 0.00,
            'balance_due' => 0.00,
            'remaining_rent' => 0.00,
            'remaining_services' => 0.00,
            'remaining_tax' => 0.00
        ];
    }
}
?> 