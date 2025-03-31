<?php
/**
 * =============================================
 * BALANCE CALCULATOR
 * =============================================
 * 
 * This file contains functions for calculating account balances and rent due.
 * 
 * Functions:
 * - calculateRentBalance(): Calculates rent balance based on installation date
 * - getBalanceBreakdown(): Provides detailed breakdown of all balance components
 * 
 * =============================================
 */

require_once 'db.php';

/**
 * =============================================
 * CALCULATE RENT BALANCE
 * =============================================
 * 
 * Calculates the total rent balance due for a client as of a specific date.
 * 
 * Calculation Process:
 * 1. Gets client's installation date and price code
 * 2. Calculates number of days from installation to specified date (inclusive)
 * 3. Multiplies days by price code to get total rent owed
 * 4. Sums up all rent payments from invoices
 * 5. Returns the difference between rent owed and rent paid
 * 
 * @param int $customer_id The ID of the customer
 * @param string|null $as_of_date The date to calculate balance as of (defaults to today)
 * @return float The calculated balance
 * =============================================
 */
function calculateRentBalance($customer_id, $as_of_date = null) {
    global $pdo;
    
    try {
        // If no date provided, use today's date
        if (!$as_of_date) {
            $as_of_date = date('Y-m-d');
        }
        
        // Get client's installation date and price code
        $stmt = $pdo->prepare("SELECT install_on, price_code FROM client_information WHERE id = ?");
        $stmt->execute([$customer_id]);
        $client_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If no installation date, return 0
        if (!$client_info['install_on']) {
            return 0.00;
        }
        
        // Calculate number of days from installation to specified date (inclusive)
        $install_date = new DateTime($client_info['install_on']);
        $end_date = new DateTime($as_of_date);
        $days = $install_date->diff($end_date)->days + 1; // Add 1 to include both dates
        
        // Calculate total rent owed
        $rent_owed = $days * $client_info['price_code'];
        
        // Get total rent paid from invoices
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(rent_total), 0) as total_paid FROM invoices WHERE customer_id = ? AND status = 'paid'");
        $stmt->execute([$customer_id]);
        $rent_paid = $stmt->fetch(PDO::FETCH_ASSOC)['total_paid'];
        
        // Calculate and return the balance
        return round($rent_owed - $rent_paid, 2);
        
    } catch (PDOException $e) {
        error_log("Error calculating rent balance: " . $e->getMessage());
        return 0.00;
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
        
        // Get total charged from invoices
        $stmt = $pdo->prepare("SELECT 
            COALESCE(SUM(total_amount), 0) as total_charged,
            COALESCE(SUM(rent_total), 0) as rent_charged,
            COALESCE(SUM(service_total), 0) as service_charged,
            COALESCE(SUM(tax_amount), 0) as tax_charged
            FROM invoices 
            WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $charged = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get total paid from invoices
        $stmt = $pdo->prepare("SELECT 
            COALESCE(SUM(total_amount), 0) as total_paid,
            COALESCE(SUM(rent_total), 0) as rent_paid,
            COALESCE(SUM(service_total), 0) as service_paid,
            COALESCE(SUM(tax_amount), 0) as tax_paid
            FROM invoices 
            WHERE customer_id = ? AND status = 'paid'");
        $stmt->execute([$customer_id]);
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