<?php
//------------------------
// NOTES: Service Inclusion
//------------------------
// Includes the ReceiptsService class that handles data operations
require_once 'ReceiptsService.php';
require_once 'balance_calculator.php';
require_once 'invoice_appointment_modal.php';

//------------------------
// NOTES: Variable Validation
//------------------------
// Ensures required variables are available before proceeding
if (!isset($pdo) || !isset($client_id)) {
    echo "<script>console.error('Missing required variables in receipts_card.php');</script>";
    die("Error: Missing required variables");
}

//------------------------
// NOTES: Client Data Setup
//------------------------
// Fetch client data needed for the invoice modal
try {
    $stmt = $pdo->prepare("SELECT * FROM client_information WHERE id = :id");
    $stmt->execute(['id' => $client_id]);
    $client = $stmt->fetch();
    
    if (!$client) {
        throw new Exception("Client not found");
    }
} catch (Exception $e) {
    error_log("Error fetching client data: " . $e->getMessage());
    die("Error: Could not fetch client data");
}

//------------------------
// NOTES: Balance Calculation
//------------------------
// Calculate the client's current balance
$balance = calculateRentBalance($client_id, date('Y-m-d'));

//------------------------
// NOTES: Service Initialization
//------------------------
// Creates an instance of ReceiptsService with the database connection
$receiptsService = new ReceiptsService($pdo);

//------------------------
// NOTES: Data Retrieval
//------------------------
// Gets all receipts for the current client using the service
$receipts = $receiptsService->getClientReceipts($client_id);

// Add debug output
error_log("Receipts data in receipts_card.php: " . print_r($receipts, true));

//------------------------
// NOTES: Debug Logging
//------------------------
// Outputs debug information to the browser console
echo "<script>
    console.log('Client ID:', " . json_encode($client_id) . ");
    console.log('Number of receipts found:', " . count($receipts) . ");
    " . (!empty($receipts) ? "console.log('First receipt:', " . json_encode($receipts[0]) . ");" : "") . "
</script>";

// Add debug output to the page
echo "<!-- Debug Output -->
<div style='display:none;'>
    <pre>" . print_r($receipts, true) . "</pre>
</div>";

//------------------------
// NOTES: Render Invoice Modal
//------------------------
// Render the invoice modal for this client
renderNextAppointmentModal($client, $pdo);
?>

<!-- ------------------------
NOTES: Card Structure
------------------------
This is the main container for the receipts table
It uses Bootstrap card classes for styling -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Receipts</h4>
        <div class="balance-display">
            <div class="balance-label">Current Balance (no tax)</div>
            <div class="balance-amount <?php 
                if ($balance > 0) echo 'positive';
                elseif ($balance < 0) echo 'negative';
                else echo 'zero';
            ?>" style="<?php 
                if ($balance > 0) echo 'color: #dc3545;';
                elseif ($balance < 0) echo 'color: #198754;';
                else echo 'color: #0d6efd;';
            ?>">
                <?php 
                if ($balance > 0) echo 'Balance Owed: $';
                elseif ($balance < 0) echo 'Customer Credit: $';
                else echo 'Balance: $';
                ?><?php echo number_format(abs($balance), 2); ?>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#invoiceModal<?php echo $client_id; ?>">
                <i class="bi bi-plus-lg me-1"></i>New Invoice
            </button>
        </div>
        <!-- ------------------------
        NOTES: Table Container
        ------------------------
        Wraps the table in a responsive container
        Allows horizontal scrolling on small screens -->
        <div class="table-responsive">
            <table class="table table-hover">
                <!-- ------------------------
                NOTES: Table Header
                ------------------------
                Defines the columns for the receipts table
                Each column represents a piece of receipt data -->
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice #</th>
                        <th>Services</th>
                        <th>Invoice Total</th>
                        <th>Services Paid</th>
                        <th>Rent Paid</th>
                        <th>Tax Paid</th>
                        <th>Total Collected</th>
                        <th>Location</th>
                        <th>Tech</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <!-- ------------------------
                NOTES: Table Body
                ------------------------
                Contains the actual receipt data
                Shows a message if no receipts are found -->
                <tbody>
                    <?php if (empty($receipts)): ?>
                        <tr>
                            <td colspan="12" class="text-center">No receipts found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($receipts as $receipt): 
                            //------------------------
                            // NOTES: Total Calculation
                            //------------------------
                            // Calculates the total amount collected
                            // Sums up services, rent, and tax collected
                            $total_collected = $receipt['services_collected'] + $receipt['rent_collected'] + $receipt['tax_collected'];
                        ?>
                            <tr>
                                <!-- ------------------------
                                NOTES: Data Display
                                ------------------------
                                Each cell displays formatted receipt data
                                Uses htmlspecialchars for security
                                Formats numbers with 2 decimal places
                                Shows '-' for empty service names -->
                                <td><?php echo date('m/d/Y', strtotime($receipt['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($receipt['id']); ?></td>
                                <td><?php echo htmlspecialchars($receipt['service_names']); ?></td>
                                <td>$<?php echo number_format($receipt['total_amount'], 2); ?></td>
                                <td>$<?php echo number_format($receipt['services_collected'], 2); ?></td>
                                <td>$<?php echo number_format($receipt['rent_collected'], 2); ?></td>
                                <td>$<?php echo number_format($receipt['tax_collected'], 2); ?></td>
                                <td>$<?php echo number_format($total_collected, 2); ?></td>
                                <td><?php echo htmlspecialchars($receipt['location_name']); ?></td>
                                <td><?php echo htmlspecialchars($receipt['technician_initials']); ?></td>
                                <td><?php echo htmlspecialchars($receipt['status']); ?></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div> 