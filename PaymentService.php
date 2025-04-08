<?php
/**
 * PaymentService class
 * Handles payment processing and invoice creation
 */
class PaymentService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    //-------------------------------------------------------------------
    // Add logging functionality
    //-------------------------------------------------------------------
    private function logPaymentServiceAction($logMessage) {
        $logFile = 'payment_service_log.txt';
        $existingContent = file_exists($logFile) ? file_get_contents($logFile) : '';
        $timestamp = date('Y-m-d H:i:s');
        $newLogEntry = "=== Payment Service Log - $timestamp ===\n$logMessage\n\n";
        file_put_contents($logFile, $newLogEntry . $existingContent);
    }

    /**
     * Create an invoice with payment
     * 
     * This method creates a new invoice, adds services to it, and records the payment
     * 
     * @param array $data Required fields:
     *   - customer_id: Customer ID
     *   - created_by: User ID of who created the invoice
     *   - location_id: Location ID
     *   - services: Array of services with id, name, and price
     *   - payment_type: Type of payment (cash, credit, check, other)
     *   - amount: Payment amount
     *   - rent_total: Total rent charges (optional)
     *   - service_total: Total service charges (calculated from services)
     *   - tax_amount: Tax amount
     *   - invoice_total: Total invoice amount
     * 
     * @return array Result of the operation with keys:
     *   - success: Boolean indicating success or failure
     *   - message: Success or error message
     *   - invoice_id: ID of the created invoice
     *   - transaction_id: ID of the payment transaction
     */
    public function createInvoiceWithPayment($data) {
        try {
            //-------------------------------------------------------------------
            // Log incoming data
            //-------------------------------------------------------------------
            $this->logPaymentServiceAction("Incoming Data: " . print_r($data, true));

            //-------------------------------------------------------------------
            // Start transaction
            //-------------------------------------------------------------------
            $this->pdo->beginTransaction();
            
            //-------------------------------------------------------------------
            // Calculate service total if not provided
            //-------------------------------------------------------------------
            if (empty($data['service_total']) && !empty($data['services'])) {
                $data['service_total'] = array_reduce($data['services'], function($carry, $service) {
                    return $carry + (float)$service['price'];
                }, 0);
            }
            
            //-------------------------------------------------------------------
            // Log calculations
            //-------------------------------------------------------------------
            $this->logPaymentServiceAction("Calculated Service Total: " . $data['service_total']);

            //-------------------------------------------------------------------
            // Calculate tax if not provided
            //-------------------------------------------------------------------
            if (empty($data['tax_amount'])) {
                $taxable_amount = $data['service_total'] + ($data['rent_total'] ?? 0);
                $data['tax_amount'] = round($taxable_amount * 0.06625, 2); // 6.625% tax rate, rounded to 2 decimal places
            }
            
            //-------------------------------------------------------------------
            // Log calculations
            //-------------------------------------------------------------------
            $this->logPaymentServiceAction("Calculated Tax Amount: " . $data['tax_amount']);

            //-------------------------------------------------------------------
            // Calculate total if not provided
            //-------------------------------------------------------------------
            if (empty($data['invoice_total'])) {
                $data['invoice_total'] = ($data['service_total'] ?? 0) + 
                                         ($data['rent_total'] ?? 0) + 
                                         ($data['tax_amount'] ?? 0);
            }
            
            //-------------------------------------------------------------------
            // Log calculations
            //-------------------------------------------------------------------
            $this->logPaymentServiceAction("Calculated Invoice Total: " . $data['invoice_total']);

            //-------------------------------------------------------------------
            // Handle overpayment and tax allocation
            //-------------------------------------------------------------------
            $overpayment = max(0, $data['amount'] - $data['invoice_total']);
            if ($overpayment > 0) {
                // Calculate tax portion of overpayment
                $tax_rate = 0.06625; // 6.625%
                $tax_multiplier = $tax_rate / (1 + $tax_rate); // This gives us the portion that should be tax
                $overpayment_tax = round($overpayment * $tax_multiplier, 2);
                $overpayment_rent = $overpayment - $overpayment_tax;
                
                // Add overpayment amounts to respective totals
                $data['tax_amount'] += $overpayment_tax;
                $data['rent_total'] += $overpayment_rent;
                $data['invoice_total'] += $overpayment;
                
                $this->logPaymentServiceAction("Overpayment handled - Tax: $" . $overpayment_tax . ", Rent: $" . $overpayment_rent);
            }
            
            //-------------------------------------------------------------------
            // Determine invoice status based on payment amount
            //-------------------------------------------------------------------
            $status = ($data['amount'] >= $data['invoice_total']) ? 'paid' : 'open';
            
            //-------------------------------------------------------------------
            // Log status
            //-------------------------------------------------------------------
            $this->logPaymentServiceAction("Determined Invoice Status: " . $status);

            //-------------------------------------------------------------------
            // Create invoice record
            //-------------------------------------------------------------------
            $stmt = $this->pdo->prepare("
                INSERT INTO invoices (
                    customer_id, created_by, location_id,
                    service_total, rent_total, tax_amount, total_amount,
                    services_collected, rent_collected, tax_collected,
                    status, notes
                ) VALUES (
                    :customer_id, :created_by, :location_id,
                    :service_total, :rent_total, :tax_amount, :invoice_total,
                    :services_collected, :rent_collected, :tax_collected,
                    :status, :notes
                )
            ");
            
            //-------------------------------------------------------------------
            // Log database insertion
            //-------------------------------------------------------------------
            $this->logPaymentServiceAction("Inserting into invoices table: " . print_r($data, true));
            
            //-------------------------------------------------------------------
            // Calculate collected amounts based on payment
            //-------------------------------------------------------------------
            $services_collected = min($data['amount'], $data['service_total'] ?? 0);
            $remaining_payment = max(0, $data['amount'] - $services_collected);
            
            $rent_collected = min($remaining_payment, $data['rent_total'] ?? 0);
            $remaining_payment = max(0, $remaining_payment - $rent_collected);
            
            $tax_collected = min($remaining_payment, $data['tax_amount'] ?? 0);
            
            $stmt->execute([
                'customer_id' => $data['customer_id'],
                'created_by' => $data['created_by'],
                'location_id' => $data['location_id'],
                'service_total' => $data['service_total'] ?? 0,
                'rent_total' => $data['rent_total'] ?? 0,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'invoice_total' => $data['invoice_total'] ?? 0,
                'services_collected' => $services_collected,
                'rent_collected' => $rent_collected,
                'tax_collected' => $tax_collected,
                'status' => $status,
                'notes' => $data['notes'] ?? null
            ]);
            
            $invoice_id = $this->pdo->lastInsertId();
            
            //-------------------------------------------------------------------
            // Add services to invoice_services table
            //-------------------------------------------------------------------
            if (!empty($data['services'])) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO invoice_services (
                        invoice_id, service_id, price_at_time, created_by, location_id
                    ) VALUES (
                        :invoice_id, :service_id, :price_at_time, :created_by, :location_id
                    )
                ");
                
                foreach ($data['services'] as $service) {
                    $stmt->execute([
                        'invoice_id' => $invoice_id,
                        'service_id' => $service['id'],
                        'price_at_time' => $service['price'],
                        'created_by' => $data['created_by'],
                        'location_id' => $data['location_id']
                    ]);
                }
            }
            
            //-------------------------------------------------------------------
            // Check for service_id 5 and update client_information if present
            //-------------------------------------------------------------------
            if (!empty($data['services'])) {
                foreach ($data['services'] as $service) {
                    if ($service['id'] == 5) {
                        // Update client_information table
                        $stmt = $this->pdo->prepare("
                            UPDATE client_information 
                            SET install_on = CURDATE(),
                                status = 'Installed'
                            WHERE id = :customer_id
                        ");
                        
                        $stmt->execute([
                            'customer_id' => $data['customer_id']
                        ]);
                        
                        // Log the update
                        $this->logPaymentServiceAction("Updated client_information for id {$data['customer_id']} - Set install_on to today and status to Installed");
                        break; // Exit loop once we find service_id 5
                    }
                }
            }
            
            //-------------------------------------------------------------------
            // CREATE PAYMENT TRANSACTIONS ON PAYMENT_TRANSACTIONS TABLE
            //-------------------------------------------------------------------
            $payment_id = null;
            
            //-------------------------------------------------------------------
            // Payment type mapping to match database enum
            //-------------------------------------------------------------------
            $paymentTypeMap = [
                'cash' => 'CASH',
                'credit' => 'CREDIT_CARD',
                'check' => 'CHECK',
                'other' => 'OTHER'
            ];
            
            //-------------------------------------------------------------------
            // Process each payment method if payments array exists
            //-------------------------------------------------------------------
            if (isset($data['payments'])) {
                foreach ($data['payments'] as $payment_type => $amount) {
                    //-------------------------------------------------------------------
                    // Skip if amount is 0 or payment type is total_collected/remaining_balance
                    //-------------------------------------------------------------------
                    if ($amount <= 0 || in_array($payment_type, ['total_collected', 'remaining_balance'])) {
                        continue;
                    }
                    
                    //-------------------------------------------------------------------
                    // Map the payment type to match database enum
                    //-------------------------------------------------------------------
                    $mapped_payment_type = $paymentTypeMap[strtolower($payment_type)] ?? 'OTHER';
                    
                    $transaction_id = 'TXN' . time() . rand(1000, 9999);
                    
                    $stmt = $this->pdo->prepare("
                        INSERT INTO payment_transactions (
                            invoice_id, customer_id, transaction_id, amount, payment_type,
                            status, created_by, location_id, description, created_at
                        ) VALUES (
                            :invoice_id, :customer_id, :transaction_id, :amount, :payment_type,
                            'approved', :created_by, :location_id, :description, NOW()
                        )
                    ");
                    
                    $stmt->execute([
                        'invoice_id' => $invoice_id,
                        'customer_id' => $data['customer_id'],
                        'transaction_id' => $transaction_id,
                        'amount' => $amount,
                        'payment_type' => $mapped_payment_type,
                        'created_by' => $data['created_by'],
                        'location_id' => $data['location_id'],
                        'description' => $data['description'] ?? 'Invoice Payment'
                    ]);
                    
                    //-------------------------------------------------------------------
                    // Store the first payment_id for return value
                    //-------------------------------------------------------------------
                    if ($payment_id === null) {
                        $payment_id = $this->pdo->lastInsertId();
                    }
                }
            } else {
                //-------------------------------------------------------------------
                // Fallback to single payment method if payments array doesn't exist
                //-------------------------------------------------------------------
                $transaction_id = 'TXN' . time() . rand(1000, 9999);
                
                //-------------------------------------------------------------------
                // Map the payment type to match database enum
                //-------------------------------------------------------------------
                $mapped_payment_type = $paymentTypeMap[strtolower($data['payment_type'])] ?? 'OTHER';
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO payment_transactions (
                        invoice_id, customer_id, transaction_id, amount, payment_type,
                        status, created_by, location_id, description, created_at
                    ) VALUES (
                        :invoice_id, :customer_id, :transaction_id, :amount, :payment_type,
                        'approved', :created_by, :location_id, :description, NOW()
                    )
                ");
                
                $stmt->execute([
                    'invoice_id' => $invoice_id,
                    'customer_id' => $data['customer_id'],
                    'transaction_id' => $transaction_id,
                    'amount' => $data['amount'],
                    'payment_type' => $mapped_payment_type,
                    'created_by' => $data['created_by'],
                    'location_id' => $data['location_id'],
                    'description' => $data['description'] ?? 'Invoice Payment'
                ]);
                
                $payment_id = $this->pdo->lastInsertId();
            }
            
            //-------------------------------------------------------------------
            // Commit transaction
            //-------------------------------------------------------------------
            $this->pdo->commit();
            
            //-------------------------------------------------------------------
            // Return result
            //-------------------------------------------------------------------
            return [
                'success' => true,
                'message' => 'Invoice created with payment successfully',
                'invoice_id' => $invoice_id,
                'transaction_id' => $payment_id,
                'breakdown' => [
                    'services_collected' => $services_collected,
                    'services_tax_collected' => $tax_collected,
                    'total_collected' => $data['amount'],
                    'remaining_balance' => $data['invoice_total'] - $data['amount']
                ]
            ];
            
        } catch (Exception $e) {
            //-------------------------------------------------------------------
            // Rollback transaction on error
            //-------------------------------------------------------------------
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            
            return [
                'success' => false,
                'message' => 'Error creating invoice with payment: ' . $e->getMessage(),
                'invoice_id' => null,
                'transaction_id' => null
            ];
        }
    }
}
?>
