<?php
require_once 'auth_check.php';
require_once 'db.php';

// Get invoice ID from URL
$invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;

if (!$invoice_id) {
    die("No invoice ID provided");
}

try {
    // Get invoice details
    $stmt = $pdo->prepare("
        SELECT 
            i.*,
            c.first_name,
            c.last_name,
            c.email,
            c.phone_number,
            l.location_name,
            l.address,
            l.city,
            l.state,
            l.zip
        FROM invoices i
        LEFT JOIN client_information c ON i.customer_id = c.id
        LEFT JOIN locations l ON i.location_id = l.id
        WHERE i.id = ?
    ");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        die("Invoice not found");
    }

    // Get invoice services
    $stmt = $pdo->prepare("
        SELECT inv_serv.*, s.name as service_name
        FROM invoice_services inv_serv
        LEFT JOIN services s ON inv_serv.service_id = s.id
        WHERE inv_serv.invoice_id = ?
    ");
    $stmt->execute([$invoice_id]);
    $services = $stmt->fetchAll();

    // Get invoice payments
    $stmt = $pdo->prepare("
        SELECT *
        FROM payment_transactions
        WHERE invoice_id = ?
    ");
    $stmt->execute([$invoice_id]);
    $payments = $stmt->fetchAll();

    // Get next appointment information
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            l.location_name,
            l.address,
            l.city,
            l.state,
            l.zip
        FROM appointments a
        LEFT JOIN locations l ON a.location_id = l.id
        WHERE a.invoice_id = ?
        AND a.status != 'cancelled'
        AND a.start_time > NOW()
        ORDER BY a.start_time ASC
        LIMIT 1
    ");
    $stmt->execute([$invoice_id]);
    $nextAppointment = $stmt->fetch();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $invoice_id; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .company-info {
            text-align: right;
        }
        .invoice-title {
            text-align: center;
            margin: 20px 0;
            font-size: 24px;
            font-weight: bold;
        }
        .client-info {
            margin-bottom: 15px;
        }
        .invoice-details {
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        .totals {
            margin-left: auto;
            width: 300px;
        }
        .totals table {
            margin-bottom: 0;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .appointment-info {
            margin-top: 0;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            padding-top: 0px;
            padding-bottom: 0px;
        }
        .appointment-info p {
            margin: 0;
            padding: 5px;
        }
        .payments {
            margin-bottom: 5px;
            margin-top: 5px;
        }
        .payments h3 {
            margin-bottom: 5px;
            margin-top: 5px;
        }
        .payments table {
            margin-bottom: 20px;
        }
        .service-cell {
            width: 70%;
            padding: 4px;
        }
        .price-cell {
            width: 30%;
            padding: 8px;
            text-align: right;
        }
        .service-content {
            font-size: 14px;
            line-height: 1.4;
        }
        .price-content {
            font-size: 14px;
            font-weight: 500;
        }
        @media print {
            body {
                padding: 0;
            }
            .invoice-container {
                border: none;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div>
                <h2>IDNJ</h2>
                <p>Invoice #<?php echo $invoice_id; ?></p>
                <p>Date: <?php echo date('m/d/Y', strtotime($invoice['created_at'])); ?></p>
            </div>
            <div class="company-info">
                <p><?php echo htmlspecialchars($invoice['location_name']); ?></p>
                <p><?php echo htmlspecialchars($invoice['address']); ?></p>
                <p><?php echo htmlspecialchars($invoice['city'] . ', ' . $invoice['state'] . ' ' . $invoice['zip']); ?></p>
            </div>
        </div>

        <?php if ($nextAppointment): ?>
        <div class="appointment-info">
            <p><strong>Next Appointment:</strong> <?php echo date('m/d/Y', strtotime($nextAppointment['start_time'])); ?> 
            At <?php echo date('g:i A', strtotime($nextAppointment['start_time'])); ?> 
            In <?php echo htmlspecialchars($nextAppointment['location_name']); ?> 
            for <?php echo htmlspecialchars($nextAppointment['appointment_type']); ?></p>
            <p><?php echo htmlspecialchars($nextAppointment['address'] . ', ' . $nextAppointment['city'] . ', ' . $nextAppointment['state'] . ' ' . $nextAppointment['zip']); ?></p>
            <?php if (!empty($nextAppointment['service_note'])): ?>
                <p><strong>Notes:</strong> <?php echo htmlspecialchars($nextAppointment['service_note']); ?></p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="appointment-info">
            <p><strong>Next Appointment:</strong> No Next appointment created for this invoice</p>
        </div>
        <?php endif; ?>

        <div class="client-info">
            <h3>Bill To:</h3>
            <p><?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></p>
            <p><?php echo htmlspecialchars($invoice['email']); ?></p>
            <p><?php echo htmlspecialchars($invoice['phone_number']); ?></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $service): ?>
                <tr>
                    <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                    <td>$<?php echo number_format($service['price'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td>Rent Due:</td>
                    <td>$<?php echo number_format($invoice['rent_total'], 2); ?></td>
                </tr>
                <tr>
                    <td>Service Due:</td>
                    <td>$<?php echo number_format($invoice['service_total'], 2); ?></td>
                </tr>
                <tr>
                    <td>Subtotal:</td>
                    <td>$<?php echo number_format($invoice['sub_total'], 2); ?></td>
                </tr>
                <tr>
                    <td>Sales Tax (6.625%):</td>
                    <td>$<?php echo number_format($invoice['tax_amount'], 2); ?></td>
                </tr>
                <tr>
                    <td><strong>Total:</strong></td>
                    <td><strong>$<?php echo number_format($invoice['total_amount'], 2); ?></strong></td>
                </tr>
            </table>
        </div>

        <div class="payments">
            <h3>Payments:</h3>
            <table>
                <thead>
                    <tr>
                        <th>Method</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_type'])); ?></td>
                        <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="footer">
            <p>Thank you for your business!</p>
            <p>Please contact us if you have any questions about this invoice.</p>
        </div>
    </div>

    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <div style="margin-bottom: 20px;">
            <button onclick="window.print()" class="btn btn-primary btn-lg">
                <i class="bi bi-printer"></i> Print Invoice
            </button>
        </div>
        <div>
            <button onclick="window.location.href='dashboard.php'" class="btn btn-secondary me-3">
                <i class="bi bi-house"></i> Dashboard
            </button>
            <button onclick="window.location.href='quick-schedule.php'" class="btn btn-info">
                <i class="bi bi-calendar-plus"></i> Quick Schedule
            </button>
        </div>
    </div>
</body>
</html> 