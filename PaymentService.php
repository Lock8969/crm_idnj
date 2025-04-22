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
        $newLogEntry = "\n" . str_repeat("=", 80) . "\n";
        $newLogEntry .= "PAYMENT SERVICE LOG - $timestamp\n";
        $newLogEntry .= str_repeat("=", 80) . "\n";
        $newLogEntry .= $logMessage . "\n";
        $newLogEntry .= str_repeat("=", 80) . "\n";
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
     *   - sub_total: Sum of rent_total and service_total
     *   - tax_amount: Tax amount
     *   - invoice_total: Total invoice amount
     * 
     * @return array Result of the operation with keys:
     *   - success: Boolean indicating success or failure
     *   - message: Success or error message
     *   - invoice_id: ID of the created invoice
     *   - transaction_id: ID of the payment transaction
     *   - breakdown: Array containing:
     *     - services_collected: Amount collected for services
     *     - services_tax_collected: Tax amount collected for services
     *     - total_collected: Total amount collected
     *     - remaining_balance: Remaining balance after payment
     *     - sub_total: Sum of rent_total and service_total
     */
    public function createInvoiceWithPayment($data) {
        try {
            //-------------------------------------------------------------------
            // Log incoming data
            //-------------------------------------------------------------------
            $this->logPaymentServiceAction(
                "INCOMING REQUEST DATA:\n" .
                "Request Type: " . ($data['request_type'] ?? 'N/A') . "\n" .
                "Customer ID: " . $data['customer_id'] . "\n" .
                "Created By: " . $data['created_by'] . "\n" .
                "Location ID: " . $data['location_id'] . "\n" .
                "Payment Type: " . ($data['payment_type'] ?? 'N/A') . "\n" .
                "Amount: $" . $data['amount'] . "\n" .
                "Rent Total: $" . ($data['rent_total'] ?? 0) . "\n" .
                "\nSERVICES:\n" . 
                (empty($data['services']) ? "No services provided\n" : 
                    array_reduce($data['services'], function($carry, $service) {
                        return $carry . "- {$service['name']} (ID: {$service['service_id']}): $" . $service['price'] . "\n";
                    }, "")) .
                "\nPAYMENTS:\n" .
                (empty($data['payments']) ? "No payments provided\n" : 
                    array_reduce(array_keys($data['payments']), function($carry, $type) use ($data) {
                        return $carry . "- {$type}: $" . $data['payments'][$type] . "\n";
                    }, ""))
            );

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
            // Calculate sub_total if not provided
            //-------------------------------------------------------------------
            if (empty($data['sub_total'])) {
                $data['sub_total'] = ($data['service_total'] ?? 0) + ($data['rent_total'] ?? 0);
            }
            
            //-------------------------------------------------------------------
            // Calculate total collected from all payment types
            //-------------------------------------------------------------------
            $total_collected = 0;
            if (!empty($data['payments'])) {
                $total_collected = array_reduce($data['payments'], function($carry, $amount) {
                    return $carry + (float)$amount;
                }, 0);
            } else {
                // Fallback to single payment amount if payments array not provided
                $total_collected = (float)($data['amount'] ?? 0);
            }
            
            //-------------------------------------------------------------------
            // Log calculations
            //-------------------------------------------------------------------
            $this->logPaymentServiceAction(
                "CALCULATED AMOUNTS:\n" .
                "Service Total: $" . ($data['service_total'] ?? 0) . "\n" .
                "Rent Total: $" . ($data['rent_total'] ?? 0) . "\n" .
                "Sub Total: $" . $data['sub_total'] . "\n" .
                "Total Collected: $" . $total_collected . "\n"
            );

            //-------------------------------------------------------------------
            // Calculate tax if not provided
            //-------------------------------------------------------------------
            if (empty($data['tax_amount'])) {
                $taxable_amount = $data['service_total'] + ($data['rent_total'] ?? 0);
                $data['tax_amount'] = round($taxable_amount * 0.06625, 2);
            }
            
            //-------------------------------------------------------------------
            // Calculate total if not provided
            //-------------------------------------------------------------------
            if (empty($data['invoice_total'])) {
                $data['invoice_total'] = ($data['service_total'] ?? 0) + 
                                         ($data['rent_total'] ?? 0) + 
                                         ($data['tax_amount'] ?? 0);
            }
            
            //-------------------------------------------------------------------
            // Log tax and total calculations
            //-------------------------------------------------------------------
            $this->logPaymentServiceAction(
                "TAX AND TOTAL CALCULATIONS:\n" .
                "Tax Amount: $" . $data['tax_amount'] . "\n" .
                "Invoice Total: $" . $data['invoice_total'] . "\n"
            );

            //-------------------------------------------------------------------
            // Calculate payment allocation
            //-------------------------------------------------------------------
            $tax_rate = 0.06625;
            
            if ($total_collected == $data['invoice_total']) {
                $tax_collected = $data['tax_amount'];
                $sub_total_collected = $data['sub_total'];
                $status = 'paid';
            } else if ($total_collected < $data['invoice_total']) {
                $tax_collected = round($total_collected * $tax_rate, 2);
                $sub_total_collected = $total_collected - $tax_collected;
                $status = 'open';
            } else {
                $tax_collected = round($total_collected * $tax_rate, 2);
                $sub_total_collected = $total_collected - $tax_collected;
                $status = 'paid';
            }

            //-------------------------------------------------------------------
            // Log payment breakdown
            //-------------------------------------------------------------------
            $this->logPaymentServiceAction(
                "PAYMENT BREAKDOWN:\n" .
                "Total Collected: $" . $total_collected . "\n" .
                "Sub Total: $" . $data['sub_total'] . "\n" .
                "Sub Total Collected: $" . $sub_total_collected . "\n" .
                "Tax Collected: $" . $tax_collected . "\n" .
                "Status: " . $status . "\n"
            );

            //-------------------------------------------------------------------
            // Create invoice record
            //-------------------------------------------------------------------
            $stmt = $this->pdo->prepare("
                INSERT INTO invoices (
                    customer_id, created_by, location_id,
                    service_total, rent_total, tax_amount, total_amount,
                    sub_total, sub_total_collected, tax_collected,
                    total_collected, status, notes, credit_applied
                ) VALUES (
                    :customer_id, :created_by, :location_id,
                    :service_total, :rent_total, :tax_amount, :invoice_total,
                    :sub_total, :sub_total_collected, :tax_collected,
                    :total_collected, :status, :notes, :credit_applied
                )
            ");
            
            //-------------------------------------------------------------------
            // Log invoice creation
            //-------------------------------------------------------------------
            $this->logPaymentServiceAction(
                "INSERTING INTO INVOICES TABLE:\n" .
                "Customer ID: " . $data['customer_id'] . "\n" .
                "Created By: " . $data['created_by'] . "\n" .
                "Location ID: " . $data['location_id'] . "\n" .
                "Service Total: $" . ($data['service_total'] ?? 0) . "\n" .
                "Rent Total: $" . ($data['rent_total'] ?? 0) . "\n" .
                "Tax Amount: $" . ($data['tax_amount'] ?? 0) . "\n" .
                "Invoice Total: $" . ($data['invoice_total'] ?? 0) . "\n" .
                "Sub Total: $" . ($data['sub_total'] ?? 0) . "\n" .
                "Sub Total Collected: $" . $sub_total_collected . "\n" .
                "Tax Collected: $" . $tax_collected . "\n" .
                "Total Collected: $" . $total_collected . "\n" .
                "Status: " . $status . "\n" .
                "Credit Applied: $" . ($data['payments']['customer_credit'] ?? 0) . "\n"
            );
            
            $stmt->execute([
                'customer_id' => $data['customer_id'],
                'created_by' => $data['created_by'],
                'location_id' => $data['location_id'],
                'service_total' => $data['service_total'] ?? 0,
                'rent_total' => $data['rent_total'] ?? 0,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'invoice_total' => $data['invoice_total'] ?? 0,
                'sub_total' => $data['sub_total'] ?? 0,
                'sub_total_collected' => $sub_total_collected,
                'tax_collected' => $tax_collected,
                'total_collected' => $total_collected,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
                'credit_applied' => $data['payments']['customer_credit'] ?? 0
            ]);
            
            $invoice_id = $this->pdo->lastInsertId();
            
            //-------------------------------------------------------------------
            // Add services to invoice_services table
            //-------------------------------------------------------------------
            if (!empty($data['services'])) {
                $this->logPaymentServiceAction(
                    "INSERTING INTO INVOICE_SERVICES TABLE:\n" .
                    "Invoice ID: " . $invoice_id . "\n" .
                    "Services to be added:\n" .
                    array_reduce($data['services'], function($carry, $service) {
                        return $carry . "- Service ID: {$service['service_id']}\n";
                    }, "")
                );
                
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
                        'service_id' => $service['service_id'],
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
                    if ($service['service_id'] == 5) {
                        $this->logPaymentServiceAction(
                            "UPDATING CLIENT_INFORMATION TABLE:\n" .
                            "Customer ID: " . $data['customer_id'] . "\n" .
                            "Setting install_on to today and status to 'Installed'"
                        );
                        
                        $stmt = $this->pdo->prepare("
                            UPDATE client_information 
                            SET install_on = CURDATE(),
                                status = 'Installed'
                            WHERE id = :customer_id
                        ");
                        
                        $stmt->execute([
                            'customer_id' => $data['customer_id']
                        ]);
                        break;
                    }
                }
            }
            
            //-------------------------------------------------------------------
            // CREATE PAYMENT TRANSACTIONS
            //-------------------------------------------------------------------
            $payment_id = null;

            if (isset($data['payments'])) {
                $this->logPaymentServiceAction(
                    "PROCESSING PAYMENTS:\n" .
                    "Payment Methods:\n" .
                    array_reduce(array_keys($data['payments']), function($carry, $type) use ($data) {
                        if ($type === 'total_collected' || $type === 'remaining_balance') return $carry;
                        $amount = $data['payments'][$type];
                        if ($amount <= 0) return $carry;
                        return $carry . "- {$type}: $" . $amount . "\n";
                    }, "")
                );
                
                foreach ($data['payments'] as $payment_type => $amount) {
                    if ($amount <= 0 || in_array($payment_type, ['total_collected', 'remaining_balance'])) {
                        continue;
                    }
                    
                    $transaction_id = 'TXN' . time() . rand(1000, 9999);
                    
                    $this->logPaymentServiceAction(
                        "INSERTING INTO PAYMENT_TRANSACTIONS TABLE:\n" .
                        "Invoice ID: " . $invoice_id . "\n" .
                        "Customer ID: " . $data['customer_id'] . "\n" .
                        "Transaction ID: " . $transaction_id . "\n" .
                        "Amount: $" . $amount . "\n" .
                        "Payment Type: " . $payment_type . "\n" .
                        "Status: approved\n"
                    );
                    
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
                        'payment_type' => $payment_type,
                        'created_by' => $data['created_by'],
                        'location_id' => $data['location_id'],
                        'description' => $data['description'] ?? 'Invoice Payment'
                    ]);
                    
                    if ($payment_id === null) {
                        $payment_id = $this->pdo->lastInsertId();
                    }
                }
            } else {
                $transaction_id = 'TXN' . time() . rand(1000, 9999);
                
                $this->logPaymentServiceAction(
                    "INSERTING INTO PAYMENT_TRANSACTIONS TABLE (SINGLE PAYMENT):\n" .
                    "Invoice ID: " . $invoice_id . "\n" .
                    "Customer ID: " . $data['customer_id'] . "\n" .
                    "Transaction ID: " . $transaction_id . "\n" .
                    "Amount: $" . $data['amount'] . "\n" .
                    "Payment Type: " . $data['payment_type'] . "\n" .
                    "Status: approved\n"
                );
                
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
                    'payment_type' => $data['payment_type'],
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
            // Log successful completion
            //-------------------------------------------------------------------
            $this->logPaymentServiceAction(
                "TRANSACTION COMPLETED SUCCESSFULLY:\n" .
                "Invoice ID: " . $invoice_id . "\n" .
                "Transaction ID: " . $payment_id . "\n" .
                "Status: Success\n"
            );
            
            return [
                'success' => true,
                'message' => 'Invoice created with payment successfully',
                'invoice_id' => $invoice_id,
                'transaction_id' => $payment_id,
                'breakdown' => [
                    'services_collected' => $data['service_total'] ?? 0,
                    'services_tax_collected' => $data['tax_amount'] ?? 0,
                    'total_collected' => $data['amount'],
                    'remaining_balance' => $data['invoice_total'] - $data['amount'],
                    'sub_total' => $data['sub_total']
                ]
            ];
            
        } catch (Exception $e) {
            //-------------------------------------------------------------------
            // Rollback transaction on error
            //-------------------------------------------------------------------
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            
            //-------------------------------------------------------------------
            // Log error
            //-------------------------------------------------------------------
            $this->logPaymentServiceAction(
                "ERROR OCCURRED:\n" .
                "Error Message: " . $e->getMessage() . "\n" .
                "Stack Trace:\n" . $e->getTraceAsString() . "\n"
            );
            
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
