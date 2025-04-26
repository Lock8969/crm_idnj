<?php
require_once 'auth_check.php';
include_once 'invoice_appointment_modal.php';
require_once 'db.php';  // Use existing database connection

// Function to get client information
function getClientInfo($clientId) {
    global $pdo;
    
    try {
        // Get basic client info
        $stmt = $pdo->prepare("
            SELECT 
                ci.install_on,
                ci.removal_date,
                ci.calibration_interval,
                cb.serial_number as control_box,
                hs.serial_number as handset
            FROM client_information ci
            LEFT JOIN cb_inventory cb ON cb.customer_id = ci.id
            LEFT JOIN hs_inventory hs ON hs.customer_id = ci.id
            WHERE ci.id = ?
        ");
        $stmt->execute([$clientId]);
        $clientInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Extract number from calibration_interval (e.g., "60 day" -> 60)
        $calibrationInterval = 60; // Default
        if ($clientInfo['calibration_interval']) {
            preg_match('/(\d+)/', $clientInfo['calibration_interval'], $matches);
            $calibrationInterval = $matches[1] ?? 60;
        }
        
        // Format the data
        return [
            'install_date' => $clientInfo['install_on'] ? date('m/d/Y', strtotime($clientInfo['install_on'])) : 'Missing',
            'removal_date' => $clientInfo['removal_date'] ? date('m/d/Y', strtotime($clientInfo['removal_date'])) : 'Not updated',
            'control_box' => $clientInfo['control_box'] ?: 'Not assigned',
            'handset' => $clientInfo['handset'] ?: 'Not assigned',
            'calibration_interval' => $calibrationInterval
        ];
    } catch(PDOException $e) {
        error_log("Error fetching client info: " . $e->getMessage());
        return [
            'install_date' => 'Error',
            'removal_date' => 'Error',
            'control_box' => 'Error',
            'handset' => 'Error',
            'calibration_interval' => 60
        ];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Ensure jQuery Loads First -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" data-cfasync="false"></script>

    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="author" content="Codescandy" />

    <!-- Favicon icon -->
    <link rel="shortcut icon" type="image/x-icon" href="dashui/assets/images/brand/logo/idnj_logo_small.png" />

    <!-- Color modes -->
    <script src="/dashui/assets/js/vendors/color-modes.js"></script>

    <!-- Libs CSS -->
    <link href="/dashui/assets/libs/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="/dashui/assets/libs/@mdi/font/css/materialdesignicons.min.css" rel="stylesheet" />
    <link href="/dashui/assets/libs/simplebar/dist/simplebar.min.css" rel="stylesheet" />

    <!-- Theme CSS -->
    <link rel="stylesheet" href="/dashui/assets/css/theme.min.css">

    <title>Quick Schedule | IDNJ</title>
    

</head>

<body>
<?php include 'navigation.php'; ?>

<div id="app-content">
    <div class="app-content-area">
        <div class="container-fluid">
            <div class="content-wrapper">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-12">
                        <!-- Page header -->
                        <div class="d-flex align-items-center mb-5">
                            <div>
                                <h3 class="mb-0 fw-bold">Quick Schedule</h3>
                                <p class="text-muted mb-0">Location: <?php echo htmlspecialchars($_SESSION['location_name'] ?? 'Unknown'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shop Schedule Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <?php
                                // Include AppointmentService
                                require_once 'AppointmentService.php';
                                
                                try {
                                    // Initialize AppointmentService
                                    $appointmentService = new AppointmentService($pdo);
                                    
                                    // Get current location ID from session
                                    $currentLocationId = $_SESSION['location_id'] ?? null;
                                    
                                    // Get all locations for dropdown
                                    $locationsStmt = $pdo->prepare("SELECT id, location_name FROM locations ORDER BY location_name");
                                    $locationsStmt->execute();
                                    $locations = $locationsStmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    // Get today's appointments for current location
                                    $today = date('Y-m-d');
                                    $appointments = [];
                                    if ($currentLocationId) {
                                        $appointments = $appointmentService->getShopAppointments($currentLocationId, $today);
                                        
                                        // Add client_id to each appointment
                                        foreach ($appointments as &$appointment) {
                                            $appointment['client_id'] = $appointment['customer_id'];
                                        }
                                        unset($appointment); // Break the reference
                                    }
                                } catch(PDOException $e) {
                                    error_log("Database Error: " . $e->getMessage());
                                }
                                ?>
                                
                                <!-- Location Dropdown -->
                                <div class="row mb-4">
                                    <div class="col-md-2 mb-4">
                                        <label for="locationSelect" class="form-label">Location</label>
                                        <select class="form-select" id="locationSelect" name="location" onchange="updateSchedule()">
                                            <?php foreach ($locations as $location): ?>
                                                <option value="<?php echo $location['id']; ?>" 
                                                        <?php echo ($location['id'] == $currentLocationId) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($location['location_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Appointments Table -->
                                <div class="table-responsive table-card">
                                    <table class="table text-nowrap mb-0 table-centered table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Client ID</th>
                                                <th>Customer</th>
                                                <th>Time</th>
                                                <th>Type</th>
                                                <th>Vehicle</th>
                                                <th>Notes</th>
                                                <th>Invoice</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($appointments)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">No appointments scheduled for today</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($appointments as $appointment): ?>
                                                    <tr class="<?php echo $appointment['has_invoice_today'] ? 'table-secondary' : ''; ?>">
                                                        <td><a href="client_detail.php?id=<?php echo $appointment['client_id']; ?>" class="text-inherit"><?php echo htmlspecialchars($appointment['client_id']); ?></a></td>
                                                        <td><a href="client_detail.php?id=<?php echo $appointment['client_id']; ?>" class="text-inherit"><?php echo htmlspecialchars($appointment['customer_first_name'] . ' ' . $appointment['customer_last_name']); ?></a></td>
                                                        <td>
                                                            <?php echo htmlspecialchars($appointment['time_formatted']); ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($appointment['appointment_type']); ?></td>
                                                        <td>
                                                            <?php 
                                                                if (!empty($appointment['vehicle_info'])) {
                                                                    echo htmlspecialchars($appointment['vehicle_info']);
                                                                } else {
                                                                    echo '<span class="text-muted">No vehicle info</span>';
                                                                }
                                                            ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($appointment['service_note'] ?? ''); ?></td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                   data-bs-toggle="modal" 
                                                                   data-bs-target="#nextAppointmentModal<?php echo htmlspecialchars($appointment['client_id']); ?>">
                                                                <i class="bi bi-plus-circle"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

<!-- ============================================= -->
<!-- INVOICE APPOINTMENT MODAL -->
<!-- ============================================= -->
<?php foreach ($appointments as $appointment): ?>
<div class="modal fade" id="nextAppointmentModal<?php echo htmlspecialchars($appointment['client_id']); ?>" tabindex="-1" aria-labelledby="nextAppointmentModalLabel<?php echo htmlspecialchars($appointment['client_id']); ?>" aria-hidden="true">
    <!-- Add Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="nextAppointmentModalLabel<?php echo htmlspecialchars($appointment['client_id']); ?>">
                    Next Appointment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.reload();"></button>
            </div>
            <div class="modal-body">
                <!-- Client Info Section -->
                <div class="mb-4">
                    <p class="mb-1"><?php echo htmlspecialchars($appointment['customer_first_name'] . ' ' . $appointment['customer_last_name']); ?></p>
                    <p class="mb-0">ID: <?php echo htmlspecialchars($appointment['client_id']); ?></p>
                    <?php 
                    $clientInfo = getClientInfo($appointment['client_id']);
                    ?>
                    <p class="mb-0">Install Date: <?php echo htmlspecialchars($clientInfo['install_date']); ?></p>
                    <p class="mb-0">Control Box: <?php echo htmlspecialchars($clientInfo['control_box']); ?></p>
                    <p class="mb-0">Handset: <?php echo htmlspecialchars($clientInfo['handset']); ?></p>
                    <p class="mb-0">Account Balance: <span id="accountBalance<?php echo htmlspecialchars($appointment['client_id']); ?>">Loading...</span></p>
                    <p class="mb-0">Removal Date: <?php echo htmlspecialchars($clientInfo['removal_date']); ?></p>
                    <p class="mb-0" id="pendingAppointmentDisplay<?php echo htmlspecialchars($appointment['client_id']); ?>"></p>
                </div>

                <!-- Appointment Form -->
                <form id="nextAppointmentForm<?php echo htmlspecialchars($appointment['client_id']); ?>">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="noAppointment<?php echo htmlspecialchars($appointment['client_id']); ?>" onchange="toggleAppointmentFields(<?php echo htmlspecialchars($appointment['client_id']); ?>)">
                            <label class="form-check-label" for="noAppointment<?php echo htmlspecialchars($appointment['client_id']); ?>">
                                No Appointment Needed
                            </label>
                        </div>
                    </div>
                    <div id="appointmentFields<?php echo htmlspecialchars($appointment['client_id']); ?>">
                        <!-- Location Selection -->
                        <div class="mb-3">
                            <label for="appointmentLocation<?php echo htmlspecialchars($appointment['client_id']); ?>" class="form-label">Location</label>
                            <select class="form-select" id="appointmentLocation<?php echo htmlspecialchars($appointment['client_id']); ?>" required>
                                <option value="">Select Location</option>
<?php
                                $locations = getLocations();
                                foreach ($locations as $location) {
                                    echo '<option value="' . htmlspecialchars($location['id']) . '">' . 
                                         htmlspecialchars($location['location_name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Appointment Date/Time -->
                        <div class="mb-3">
                            <label for="appointmentDate<?php echo htmlspecialchars($appointment['client_id']); ?>" class="form-label">Appointment Date</label>
                            <div class="input-group">
                                <input type="text" class="form-control flatpickr" id="appointmentDate<?php echo htmlspecialchars($appointment['client_id']); ?>" required>
                                <span class="input-group-text"><i class="feather icon-calendar"></i></span>
                            </div>
                        </div>

                        <!-- Appointment Type -->
                        <div class="mb-3">
                            <label for="appointmentType<?php echo htmlspecialchars($appointment['client_id']); ?>" class="form-label">Appointment Type</label>
                            <select class="form-select" id="appointmentType<?php echo htmlspecialchars($appointment['client_id']); ?>" required>
                                <option value="">Select Type</option>
                                <option value="Recalibration">Recalibration (15 min)</option>
                                <option value="Removal">Removal (30 min)</option>
                                <option value="Final_download">Final Download (15 min)</option>
                                <option value="Service">Service (30 min)</option>
                                <option value="Paper_Swap">Paper Swap (15 min)</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <!-- Duration dropdown for Other appointments -->
                        <div class="mb-3" id="otherDurationContainer<?php echo htmlspecialchars($appointment['client_id']); ?>" style="display: none;">
                            <label for="otherDuration<?php echo htmlspecialchars($appointment['client_id']); ?>" class="form-label">Appointment Duration</label>
                            <select class="form-select" id="otherDuration<?php echo htmlspecialchars($appointment['client_id']); ?>">
                                <option value="">Select Duration</option>
                                <option value="15">15 minutes</option>
                                <option value="30">30 minutes</option>
                                <option value="45">45 minutes</option>
                                <option value="60">60 minutes</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="appointmentTime<?php echo htmlspecialchars($appointment['client_id']); ?>" class="form-label">Appointment Time</label>
                            <select class="form-select" id="appointmentTime<?php echo htmlspecialchars($appointment['client_id']); ?>" required>
                                <option value="">Select Time</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="serviceNotes<?php echo htmlspecialchars($appointment['client_id']); ?>" class="form-label">Notes</label>
                            <textarea class="form-control" id="serviceNotes<?php echo htmlspecialchars($appointment['client_id']); ?>" rows="3"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="window.location.reload();">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveAppointmentBtn<?php echo htmlspecialchars($appointment['client_id']); ?>" onclick="saveAppointment(<?php echo htmlspecialchars($appointment['client_id']); ?>)">
                    Save Appointment
                </button>
                <button type="button" class="btn btn-success d-none" id="invoiceBtn<?php echo htmlspecialchars($appointment['client_id']); ?>" onclick="openInvoiceModal(<?php echo htmlspecialchars($appointment['client_id']); ?>)">
                    Invoice
                </button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- ============================================= -->
<!-- INVOICE MODAL -->
<!-- ============================================= -->
<?php foreach ($appointments as $appointment): ?>
<div class="modal fade" id="invoiceModal<?php echo htmlspecialchars($appointment['client_id']); ?>" tabindex="-1" aria-labelledby="invoiceModalLabel<?php echo htmlspecialchars($appointment['client_id']); ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 575px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="invoiceModalLabel<?php echo htmlspecialchars($appointment['client_id']); ?>">
                    Create Invoice
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.reload();"></button>
            </div>
            <div class="modal-body">
                <!-- Client Info Section -->
                <div class="mb-4">
                    <p class="mb-1"><?php echo htmlspecialchars($appointment['customer_first_name'] . ' ' . $appointment['customer_last_name']); ?></p>
                    <p class="mb-0">ID: <?php echo htmlspecialchars($appointment['client_id']); ?></p>
                    <?php 
                    $clientInfo = getClientInfo($appointment['client_id']);
                    ?>
                    <p class="mb-0">Install Date: <?php echo htmlspecialchars($clientInfo['install_date']); ?></p>
                    <p class="mb-0">Control Box: <?php echo htmlspecialchars($clientInfo['control_box']); ?></p>
                    <p class="mb-0">Handset: <?php echo htmlspecialchars($clientInfo['handset']); ?></p>
                    <p class="mb-0">Account Balance Today: <span id="accountBalanceToday<?php echo htmlspecialchars($appointment['client_id']); ?>" style="color: #dc3545;">Loading...</span></p>
                    <p class="mb-0">Removal Date: <?php echo htmlspecialchars($clientInfo['removal_date']); ?></p>
                    <p class="mb-0">Appointment Date Pending: <span id="pendingAppointmentDate<?php echo htmlspecialchars($appointment['client_id']); ?>">Not Set</span></p>
                    <p class="mb-0" id="pendingAppointmentDisplay<?php echo htmlspecialchars($appointment['client_id']); ?>"></p>
                </div>

                <!-- Services Section -->
                <div class="mb-4">
                    <h5 class="mb-3">Services</h5>
                    <div id="servicesContainer<?php echo htmlspecialchars($appointment['client_id']); ?>">
                        <!-- Service rows will be added here dynamically -->
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addServiceRow(<?php echo htmlspecialchars($appointment['client_id']); ?>)">
                        <i class="bi bi-plus"></i> Add Service
                    </button>
                </div>

                <!-- Totals Section -->
                <div class="mb-4">
                    <div class="row">
                        <div class="col-md-6 offset-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td id="rentDueLabel<?php echo htmlspecialchars($appointment['client_id']); ?>">Rent Due:</td>
                                    <td class="text-end">$<span id="rentDue<?php echo htmlspecialchars($appointment['client_id']); ?>">0.00</span></td>
                                </tr>
                                <tr>
                                    <td>Service Due:</td>
                                    <td class="text-end">$<span id="serviceDue<?php echo htmlspecialchars($appointment['client_id']); ?>">0.00</span></td>
                                </tr>
                                <tr>
                                    <td>Subtotal:</td>
                                    <td class="text-end">$<span id="subtotal<?php echo htmlspecialchars($appointment['client_id']); ?>">0.00</span></td>
                                </tr>
                                <tr>
                                    <td>Sales Tax (6.625%):</td>
                                    <td class="text-end">$<span id="tax<?php echo htmlspecialchars($appointment['client_id']); ?>">0.00</span></td>
                                </tr>
                                <tr class="fw-bold">
                                    <td>Total:</td>
                                    <td class="text-end">$<span id="total<?php echo htmlspecialchars($appointment['client_id']); ?>">0.00</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Payment Section -->
                <div class="mb-4">
                    <h5 class="mb-3">Payment</h5>
                    <div class="mb-3">
                        <label class="form-label">Total Due: $<span id="totalDue<?php echo htmlspecialchars($appointment['client_id']); ?>">0.00</span></label>
                    </div>
                    <div id="paymentMethodsContainer<?php echo htmlspecialchars($appointment['client_id']); ?>">
                        <!-- First payment method (always visible) -->
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Amount Collected</label>
                                    <input type="number" class="form-control payment-amount" step="0.01" min="0" onchange="updateTotalDue(<?php echo htmlspecialchars($appointment['client_id']); ?>)">
                                </div>
                            </div>
                            <div class="col-5">
                                <div class="mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <div class="input-group">
                                        <select class="form-select payment-method">
                                            <option value="credit_card" selected>Credit Card</option>
                                            <option value="cash">Cash</option>
                                            <option value="check">Check</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-1 d-flex align-items-center justify-content-center pt-4">
                                <button type="button" class="btn btn-link text-danger p-0" onclick="removePaymentMethod(this)">
                                    <i class="bi bi-trash fs-5"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPaymentMethod(<?php echo htmlspecialchars($appointment['client_id']); ?>)">
                        <i class="bi bi-plus"></i> Add Method
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="window.location.reload();">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveInvoice(<?php echo htmlspecialchars($appointment['client_id']); ?>)">
                    Save Invoice
                </button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Add Flatpickr JS before other scripts -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- ============================================= -->
<!-- INITIAL SCRIPT - SERVICE PRICES -->
<!-- ============================================= -->
<script>
// ONLY DECLARE SERVICEPRICES IF IT HASN'T BEEN DECLARED YET
if (typeof window.servicePrices === 'undefined') {
    window.servicePrices = {
        'Recalibration': 75.00,
        'Removal': 100.00,
        'Final_download': 50.00,
        'Service': 125.00,
        'Paper_Swap': 25.00,
        'Other': 0.00
    };
}
</script>

<!-- ============================================= -->
<!-- TOGGLE APPOINTMENT FIELDS FUNCTION -->
<!-- ============================================= -->
<script>
function toggleAppointmentFields(clientId) {
    const noAppointment = document.getElementById('noAppointment' + clientId);
    const appointmentFields = document.getElementById('appointmentFields' + clientId);
    const dateInput = document.getElementById('appointmentDate' + clientId);
    const timeSelect = document.getElementById('appointmentTime' + clientId);
    const notesInput = document.getElementById('serviceNotes' + clientId);

    if (noAppointment.checked) {
        appointmentFields.style.display = 'none';
        dateInput.required = false;
        timeSelect.required = false;
        // CLEAR PENDING APPOINTMENT DATA WHEN "NO APPOINTMENT NEEDED" IS CHECKED
        sessionStorage.removeItem('pendingAppointment');
        console.log('CLEARED PENDING APPOINTMENT DATA - NO APPOINTMENT NEEDED CHECKED');
    } else {
        appointmentFields.style.display = 'block';
        dateInput.required = true;
        timeSelect.required = true;
    }
}
</script>

<!-- ============================================= -->
<!-- FETCH AVAILABLE TIME SLOTS FUNCTION -->
<!-- ============================================= -->
<script>
function fetchAvailableTimeSlots(clientId) {
    const locationId = document.getElementById('appointmentLocation' + clientId).value;
    const date = document.getElementById('appointmentDate' + clientId).value;
    const time = document.getElementById('appointmentTime' + clientId).value;
    const typeSelect = document.getElementById('appointmentType' + clientId);
    const otherDurationContainer = document.getElementById('otherDurationContainer' + clientId);
    const otherDurationSelect = document.getElementById('otherDuration' + clientId);

    // SHOW LOADING STATE
    const timeSelect = document.getElementById('appointmentTime' + clientId);
    timeSelect.innerHTML = '<option value="">Loading available times...</option>';
    timeSelect.disabled = true;

    // GET APPOINTMENT TYPE AND DURATION
    const type = typeSelect.value;
    let duration = 15; // DEFAULT DURATION FOR STANDARD APPOINTMENTS

    // SET DURATION BASED ON APPOINTMENT TYPE
    if (type === 'Other') {
        duration = parseInt(otherDurationSelect.value) || 15;
    } else {
        switch(type) {
            case 'Recalibration':
            case 'Final_download':
            case 'Paper_Swap':
                duration = 15;
                break;
            case 'Removal':
            case 'Service':
                duration = 30;
                break;
        }
    }

    // MAKE API CALL WITH TYPE AND DURATION
    fetch(`get_available_slots.php?location_id=${locationId}&date=${date}&type=${type}&duration=${duration}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // CLEAR EXISTING OPTIONS EXCEPT THE FIRST ONE
                timeSelect.innerHTML = '<option value="">Select Time</option>';
                
                // ADD AVAILABLE TIME SLOTS
                data.data.forEach(time => {
                    const option = document.createElement('option');
                    option.value = time; // KEEP 24-HOUR FORMAT FOR VALUE
                    
                    // CONVERT TO 12-HOUR FORMAT FOR DISPLAY
                    const [hours, minutes] = time.split(':');
                    const hour = parseInt(hours);
                    const ampm = hour >= 12 ? 'PM' : 'AM';
                    const hour12 = hour % 12 || 12;
                    option.textContent = `${hour12}:${minutes} ${ampm}`;
                    
                    timeSelect.appendChild(option);
                });

                // DISABLE LOCATION, DATE, AND TYPE FIELDS ONCE TIMES ARE LOADED
                const locationSelect = document.getElementById('appointmentLocation' + clientId);
                locationSelect.disabled = true;
                const dateInput = document.getElementById('appointmentDate' + clientId);
                dateInput.disabled = true;
                // DISABLE FLATPICKR INSTANCE
                if (dateInput._flatpickr) {
                    dateInput._flatpickr.destroy();
                }
                const typeSelect = document.getElementById('appointmentType' + clientId);
                typeSelect.disabled = true;
                if (otherDurationContainer) {
                    otherDurationSelect.disabled = true;
                }
            } else {
                timeSelect.innerHTML = '<option value="">Error loading times</option>';
            }
        })
        .catch(error => {
            timeSelect.innerHTML = '<option value="">Error loading times</option>';
            console.error('Error:', error);
        })
        .finally(() => {
            timeSelect.disabled = false;
        });
}
</script>

<!-- ============================================= -->
<!-- SAVE APPOINTMENT FUNCTION -->
<!-- ============================================= -->
<script>
function saveAppointment(clientId) {
    // GET ALL FORM DATA
    const noAppointmentChecked = document.getElementById('noAppointment' + clientId).checked;
    const appointmentFields = document.getElementById('appointmentFields' + clientId);
    const locationSelect = document.getElementById('appointmentLocation' + clientId);
    const dateInput = document.getElementById('appointmentDate' + clientId);
    const typeSelect = document.getElementById('appointmentType' + clientId);
    const timeSelect = document.getElementById('appointmentTime' + clientId);
    const otherDurationSelect = document.getElementById('otherDuration' + clientId);
    const otherDurationContainer = document.getElementById('otherDurationContainer' + clientId);
    
    // VALIDATE EITHER NO APPOINTMENT IS CHECKED OR ALL REQUIRED FIELDS ARE FILLED
    if (!noAppointmentChecked) {
        // CHECK IF ALL REQUIRED FIELDS ARE FILLED
        if (!locationSelect.value || !dateInput.value || !typeSelect.value || !timeSelect.value) {
            alert('Please fill in all required fields or check "No Appointment Needed"');
            return;
        }
        
        // IF APPOINTMENT TYPE IS "OTHER", CHECK DURATION
        if (typeSelect.value === 'Other' && (!otherDurationSelect || !otherDurationSelect.value)) {
            alert('Please select a duration for Other appointment type');
            return;
        }
    }

    const formData = {
        client_id: clientId,
        appointmentDate: dateInput.value,
        appointmentTime: timeSelect.value,
        appointmentType: typeSelect.value,
        appointmentNotes: document.getElementById('serviceNotes' + clientId).value,
        location_id: document.getElementById('appointmentLocation' + clientId).value,
        noAppointment: noAppointmentChecked
    };

    // STORE IN SESSION STORAGE
    sessionStorage.setItem('appointmentData_' + clientId, JSON.stringify(formData));

    // LOG APPOINTMENT DATA IN READABLE FORMAT
    console.log('=== APPOINTMENT DATA SAVED ===');
    console.log('Client ID:', formData.client_id);
    console.log('Appointment Date:', formData.appointmentDate);
    console.log('Appointment Time:', formData.appointmentTime);
    console.log('Appointment Type:', formData.appointmentType);
    console.log('Appointment Notes:', formData.appointmentNotes);
    console.log('No Appointment Needed:', formData.noAppointment);
    console.log('===========================');

    // HIDE SAVE APPOINTMENT BUTTON AND SHOW INVOICE BUTTON
    document.getElementById('saveAppointmentBtn' + clientId).classList.add('d-none');
    document.getElementById('invoiceBtn' + clientId).classList.remove('d-none');
}
</script>

<!-- ============================================= -->
<!-- OPEN INVOICE MODAL FUNCTION -->
<!-- ============================================= -->
<script>
function openInvoiceModal(clientId) {
    // GET APPOINTMENT DATA FROM SESSION STORAGE
    const appointmentData = JSON.parse(sessionStorage.getItem('appointmentData_' + clientId));
    
    // UPDATE PENDING APPOINTMENT DISPLAY
    const pendingAppointmentSpan = document.getElementById('pendingAppointmentDate' + clientId);
    if (appointmentData && !appointmentData.noAppointment) {
        // CREATE DATE OBJECT AND ENSURE IT'S IN LOCAL TIMEZONE
        const date = new Date(appointmentData.appointmentDate + 'T' + appointmentData.appointmentTime);
        
        // FORMAT DATE AS MM/DD/YYYY
        const formattedDate = (date.getMonth() + 1).toString().padStart(2, '0') + '/' + 
                            date.getDate().toString().padStart(2, '0') + '/' + 
                            date.getFullYear();
        
        // FORMAT TIME TO 12-HOUR FORMAT WITH AM/PM
        const hours = date.getHours();
        const minutes = date.getMinutes().toString().padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        const formattedHours = (hours % 12 || 12).toString().padStart(2, '0');
        const formattedTime = `${formattedHours}:${minutes}${ampm}`;
        
        // GET LOCATION NAME FROM SELECT ELEMENT
        const locationSelect = document.getElementById('appointmentLocation' + clientId);
        const locationName = locationSelect.options[locationSelect.selectedIndex].text;
        
        // GET SERVICE TYPE
        const serviceType = appointmentData.appointmentType;

        // CALCULATE DAYS UNTIL APPOINTMENT
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const appointmentDay = new Date(appointmentData.appointmentDate);
        appointmentDay.setHours(0, 0, 0, 0);
        const daysUntilAppointment = Math.floor((appointmentDay - today) / (1000 * 60 * 60 * 24)) + 1;
        
        pendingAppointmentSpan.innerHTML = `${formattedDate} ${formattedTime} - ${locationName} - ${serviceType}<br>${daysUntilAppointment} days from today`;
    } else {
        pendingAppointmentSpan.textContent = 'Not Set';
    }

    // USE APPOINTMENT DATE IF AVAILABLE, OTHERWISE USE TODAY'S DATE
    let appointmentDate;
    if (appointmentData && appointmentData.appointmentDate) {
        appointmentDate = appointmentData.appointmentDate;
    } else {
        const today = new Date();
        appointmentDate = today.toISOString().split('T')[0]; // FORMAT: YYYY-MM-DD
    }

    // FORMAT THE DATE FOR DISPLAY (MM/DD/YYYY)
    const [year, month, day] = appointmentDate.split('-');
    const formattedDate = `${month}/${day}/${year}`;

    // UPDATE RENT DUE LABEL WITH FORMATTED DATE
    document.getElementById('rentDueLabel' + clientId).textContent = `Rent Due (${formattedDate}):`;

    // CALCULATE RENT BALANCE (USING ORIGINAL YYYY-MM-DD FORMAT FOR API)
    fetch(`balance_calculator.php?customer_id=${clientId}&date=${appointmentDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const rentBalance = data.data.rent_balance;
                const todayBalance = data.data.today_balance;
                
                // UPDATE RENT DUE DISPLAY
                document.getElementById('rentDue' + clientId).textContent = rentBalance.toFixed(2);
                
                // UPDATE ACCOUNT BALANCE DISPLAY WITH APPROPRIATE FORMATTING
                const balanceSpan = document.getElementById('accountBalance' + clientId);
                const todayBalanceSpan = document.getElementById('accountBalanceToday' + clientId);
                
                // FORMAT TODAY'S BALANCE
                if (todayBalance < 0) {
                    todayBalanceSpan.className = 'text-success';
                    todayBalanceSpan.textContent = `Customer Credit: $${Math.abs(todayBalance).toFixed(2)}`;
                } else if (todayBalance > 0) {
                    todayBalanceSpan.className = 'text-danger';
                    todayBalanceSpan.textContent = `Balance Owed: $${todayBalance.toFixed(2)}`;
                } else {
                    todayBalanceSpan.className = 'text-success';
                    todayBalanceSpan.textContent = '$0.00';
                }
                
                updateTotals(clientId);
                
                // Initialize Total Due with the Total value
                const total = parseFloat(document.getElementById('total' + clientId).textContent);
                const totalDueSpan = document.getElementById('totalDue' + clientId);
                totalDueSpan.textContent = total.toFixed(2);
                
                // Set initial color based on total
                if (total <= 0) {
                    totalDueSpan.style.color = '#198754'; // GREEN FOR PAID IN FULL
                } else {
                    totalDueSpan.style.color = '#dc3545'; // RED FOR STILL OWING
                }
            }
        })
        .catch(error => {
            console.error('Error calculating rent balance:', error);
            document.getElementById('accountBalanceToday' + clientId).textContent = 'Error loading balance';
            document.getElementById('accountBalance' + clientId).textContent = 'Error loading balance';
        });

    // CLOSE THE APPOINTMENT MODAL
    const appointmentModal = bootstrap.Modal.getInstance(document.getElementById('nextAppointmentModal' + clientId));
    appointmentModal.hide();

    // OPEN INVOICE MODAL
    const invoiceModal = new bootstrap.Modal(document.getElementById('invoiceModal' + clientId));
    invoiceModal.show();

    // MOVE FOCUS TO THE FIRST FOCUSABLE ELEMENT IN THE INVOICE MODAL
    setTimeout(() => {
        const firstFocusableElement = document.querySelector('#invoiceModal' + clientId + ' [tabindex="0"], #invoiceModal' + clientId + ' button, #invoiceModal' + clientId + ' input, #invoiceModal' + clientId + ' select');
        if (firstFocusableElement) {
            firstFocusableElement.focus();
        }
    }, 100);

    // ADD INITIAL SERVICE ROW
    addServiceRow(clientId);
}
</script>

<!-- ============================================= -->
<!-- ADD PAYMENT METHOD FUNCTION -->
<!-- ============================================= -->
<script>
function addPaymentMethod(clientId) {
    const container = document.getElementById('paymentMethodsContainer' + clientId);
    const newMethod = document.createElement('div');
    newMethod.className = 'row mb-3';
    newMethod.innerHTML = `
        <div class="col-6">
            <div class="mb-3">
                <label class="form-label">Amount Collected</label>
                <input type="number" class="form-control payment-amount" step="0.01" min="0" onchange="updateTotalDue(${clientId})">
            </div>
        </div>
        <div class="col-5">
            <div class="mb-3">
                <label class="form-label">Payment Method</label>
                <div class="input-group">
                    <select class="form-select payment-method">
                        <option value="credit_card" selected>Credit Card</option>
                        <option value="cash">Cash</option>
                        <option value="check">Check</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-1 d-flex align-items-center justify-content-center pt-4">
            <button type="button" class="btn btn-link text-danger p-0" onclick="removePaymentMethod(this)">
                <i class="bi bi-trash fs-5"></i>
            </button>
        </div>
    `;
    container.appendChild(newMethod);
}
</script>

<!-- ============================================= -->
<!-- REMOVE PAYMENT METHOD FUNCTION -->
<!-- ============================================= -->
<script>
function removePaymentMethod(button) {
    const row = button.closest('.row');
    const clientId = row.closest('.modal').id.replace('invoiceModal', '');
    row.remove();
    updateTotalDue(clientId);
}
</script>

<!-- ============================================= -->
<!-- UPDATE TOTAL DUE FUNCTION -->
<!-- ============================================= -->
<script>
function updateTotalDue(clientId) {
    const total = parseFloat(document.getElementById('total' + clientId).textContent);
    const paymentAmounts = document.querySelectorAll('#paymentMethodsContainer' + clientId + ' .payment-amount');
    const paymentMethods = document.querySelectorAll('#paymentMethodsContainer' + clientId + ' .payment-method');
    let totalPaid = 0;
    
    // LOG PAYMENT INPUTS IN A CLEAR FORMAT
    console.log('=== PAYMENT DETAILS ===');
    console.log('Payment Method | Amount Entered | Amount (Parsed)');
    console.log('----------------------------------------');
    paymentAmounts.forEach((input, index) => {
        const method = paymentMethods[index].value;
        const value = input.value;
        const parsed = parseFloat(value) || 0;
        console.log(`${method.padEnd(15)} | ${value.padEnd(13)} | $${parsed.toFixed(2)}`);
    });
    console.log('----------------------------------------');
    
    // CALCULATE TOTAL PAID
    paymentAmounts.forEach(input => {
        const amount = parseFloat(input.value) || 0;
        totalPaid += amount;
    });
    
    const remainingDue = total - totalPaid;
    document.getElementById('totalDue' + clientId).textContent = remainingDue.toFixed(2);
    
    // UPDATE COLOR BASED ON REMAINING AMOUNT
    const totalDueSpan = document.getElementById('totalDue' + clientId);
    if (remainingDue <= 0) {
        totalDueSpan.style.color = '#198754'; // GREEN FOR PAID IN FULL
    } else {
        totalDueSpan.style.color = '#dc3545'; // RED FOR STILL OWING
    }

    // UPDATE THE TOTAL COLLECTED DISPLAY
    const totalCollectedSpan = document.getElementById('totalCollected' + clientId);
    if (totalCollectedSpan) {
        totalCollectedSpan.textContent = totalPaid.toFixed(2);
    }

    // LOG SUMMARY IN A CLEAR FORMAT
    console.log('\n=== PAYMENT SUMMARY ===');
    console.log(`Total Invoice Amount: $${total.toFixed(2)}`);
    console.log(`Total Paid:          $${totalPaid.toFixed(2)}`);
    console.log(`Remaining Due:       $${remainingDue.toFixed(2)}`);
    console.log('----------------------------------------');
}
</script>

<!-- ============================================= -->
<!-- ADD SERVICE ROW FUNCTION -->
<!-- ============================================= -->
<script>
function addServiceRow(clientId) {
    const container = document.getElementById('servicesContainer' + clientId);
    const row = document.createElement('div');
    row.className = 'row mb-2 align-items-center';
    row.innerHTML = `
        <div class="col-6">
            <select class="form-select service-select" onchange="updateServicePrice(this, ${clientId})">
                <option value="">Select Service</option>
                <?php
                // FETCH SERVICES FROM DATABASE WITH CUSTOM ORDERING
                $services_query = "SELECT id, name, price FROM services 
                                 WHERE is_active = 1 
                                 ORDER BY 
                                    CASE 
                                        WHEN name = 'Recalibration' THEN 1
                                        WHEN name = 'Monitoring Check' THEN 2
                                        ELSE 3
                                    END,
                                    name";
                $services_stmt = $pdo->prepare($services_query);
                $services_stmt->execute();
                while ($service = $services_stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo '<option value="' . htmlspecialchars($service['id']) . '" data-price="' . htmlspecialchars($service['price']) . '">' . 
                         htmlspecialchars($service['name']) . ' - $' . number_format($service['price'], 2) . '</option>';
                }
                ?>
            </select>
        </div>
        <div class="col-5">
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="text" class="form-control service-price" readonly>
            </div>
        </div>
        <div class="col-1 d-flex align-items-center justify-content-center">
            <button type="button" class="btn btn-link text-danger p-0" onclick="removeServiceRow(this, ${clientId})" title="Remove Service">
                <i class="bi bi-trash fs-5"></i>
            </button>
        </div>
    `;
    container.appendChild(row);
    updateTotals(clientId);
}
</script>

<!-- ============================================= -->
<!-- UPDATE SERVICE PRICE FUNCTION -->
<!-- ============================================= -->
<script>
function updateServicePrice(select, clientId) {
    const option = select.options[select.selectedIndex];
    const priceInput = select.closest('.row').querySelector('.service-price');
    const price = option.dataset.price || '0.00';
    priceInput.value = price;
    updateTotals(clientId);
}
</script>

<!-- ============================================= -->
<!-- REMOVE SERVICE ROW FUNCTION -->
<!-- ============================================= -->
<script>
function removeServiceRow(button, clientId) {
    const row = button.closest('.row');
    row.remove();
    updateTotals(clientId);
}
</script>

<!-- ============================================= -->
<!-- UPDATE TOTALS FUNCTION -->
<!-- ============================================= -->
<script>
function updateTotals(clientId) {
    let serviceDue = 0;
    let subtotal = 0;
    
    // ADD RENT DUE TO SUBTOTAL
    const rentDue = parseFloat(document.getElementById('rentDue' + clientId).textContent) || 0;
    subtotal += rentDue;
    
    // CALCULATE SERVICE DUE
    const container = document.getElementById('servicesContainer' + clientId);
    const rows = container.children;
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const price = parseFloat(row.querySelector('input').value) || 0;
        serviceDue += price;
    }
    
    // UPDATE SERVICE DUE DISPLAY
    document.getElementById('serviceDue' + clientId).textContent = serviceDue.toFixed(2);
    
    // ADD SERVICE DUE TO SUBTOTAL
    subtotal += serviceDue;
    
    // ONLY APPLY TAX TO POSITIVE AMOUNTS
    const tax = subtotal > 0 ? subtotal * 0.06625 : 0;
    const total = subtotal + tax;
    
    document.getElementById('subtotal' + clientId).textContent = subtotal.toFixed(2);
    document.getElementById('tax' + clientId).textContent = tax.toFixed(2);
    document.getElementById('total' + clientId).textContent = total.toFixed(2);
}
</script>

<!-- ============================================= -->
<!-- SAVE INVOICE FUNCTION -->
<!-- ============================================= -->
<script>
function saveInvoice(clientId) {
    // GET ALL SERVICES
    const services = [];
    const container = document.getElementById('servicesContainer' + clientId);
    const rows = container.children;
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const select = row.querySelector('select');
        const serviceId = select.value;
        if (serviceId) {
            const priceInput = row.querySelector('.service-price');
            services.push({
                service_id: serviceId,
                name: select.options[select.selectedIndex].text.split(' - ')[0],
                price: parseFloat(priceInput.value) || 0
            });
        }
    }

    // GET ALL PAYMENTS
    const payments = {};
    const paymentAmounts = document.querySelectorAll('#paymentMethodsContainer' + clientId + ' .payment-amount');
    const paymentMethods = document.querySelectorAll('#paymentMethodsContainer' + clientId + ' .payment-method');
    
    let primaryPaymentType = 'credit_card';
    let primaryPaymentAmount = 0;
    
    // PROCESS PAYMENTS
    paymentAmounts.forEach((input, index) => {
        const amount = parseFloat(input.value) || 0;
        const paymentType = paymentMethods[index].value;
        
        // ONLY ADD TO PAYMENTS IF AMOUNT IS GREATER THAN 0
        if (amount > 0) {
            payments[paymentType] = amount;
            
            // SET FIRST NON-ZERO PAYMENT AS PRIMARY
            if (primaryPaymentAmount === 0) {
                primaryPaymentType = paymentType;
                primaryPaymentAmount = amount;
            }
        }
    });

    // IF NO PAYMENTS WERE ADDED, ADD A DEFAULT PAYMENT OF 0.00
    if (Object.keys(payments).length === 0) {
        payments['credit_card'] = 0.00;
        primaryPaymentType = 'credit_card';
        primaryPaymentAmount = 0.00;
    }

    // GET TOTALS
    const rentDue = parseFloat(document.getElementById('rentDue' + clientId).textContent) || 0;
    const serviceDue = parseFloat(document.getElementById('serviceDue' + clientId).textContent) || 0;
    const subtotal = parseFloat(document.getElementById('subtotal' + clientId).textContent) || 0;
    const tax = parseFloat(document.getElementById('tax' + clientId).textContent) || 0;
    const total = parseFloat(document.getElementById('total' + clientId).textContent) || 0;

    // CALCULATE TOTAL COLLECTED
    const totalCollected = Object.values(payments).reduce((sum, amount) => sum + amount, 0);

    // CONSTRUCT INVOICE DATA
    const invoiceData = {
        request_type: 'create_invoice_with_payment',
        customer_id: clientId,
        created_by: <?php echo $_SESSION['user_id']; ?>,
        location_id: <?php echo $_SESSION['location_id']; ?>,
        services: services,
        payment_type: primaryPaymentType,
        amount: primaryPaymentAmount,
        payments: payments,
        rent_total: rentDue,
        service_total: serviceDue,
        sub_total: subtotal,
        tax_amount: tax,
        invoice_total: total,
        total_collected: totalCollected,
        remaining_balance: total - totalCollected
    };

    // LOG THE DATA BEING SENT
    console.log('=== INVOICE DATA BEING SENT ===');
    console.log(JSON.stringify(invoiceData, null, 2));

    // SEND DATA TO SERVER
    fetch('payment_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(invoiceData)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                console.error('API Error Response:', err);
                throw new Error(err.message || 'Failed to create invoice');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // GET APPOINTMENT DATA FROM SESSION STORAGE
            const appointmentData = JSON.parse(sessionStorage.getItem('appointmentData_' + clientId));
            
            // IF WE HAVE APPOINTMENT DATA AND IT'S NOT MARKED AS "NO APPOINTMENT NEEDED"
            if (appointmentData && !appointmentData.noAppointment) {
                // PREPARE APPOINTMENT DATA FOR API
                const appointmentApiData = {
                    customer_id: clientId,
                    title: appointmentData.appointmentType + ' Appointment',
                    appointment_type: appointmentData.appointmentType.toLowerCase(),
                    start_time: appointmentData.appointmentDate + ' ' + appointmentData.appointmentTime,
                    location_id: appointmentData.location_id,
                    service_note: appointmentData.appointmentNotes,
                    status: 'scheduled'
                };

                // SEND APPOINTMENT DATA TO APPOINTMENT API
                fetch('appointment_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(appointmentApiData)
                })
                .then(response => response.json())
                .then(appointmentResult => {
                    if (!appointmentResult.success) {
                        console.error('Error creating appointment:', appointmentResult.message);
                    }
                })
                .catch(error => {
                    console.error('Error sending appointment data:', error);
                });
            }

            const modal = bootstrap.Modal.getInstance(document.getElementById('invoiceModal' + clientId));
            modal.hide();
            window.location.reload();
        } else {
            console.error('API Error:', data);
            alert('Error creating invoice: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error creating invoice. Please try again.');
    });
}
</script>

<!-- ============================================= -->
<!-- DOM CONTENT LOADED EVENT LISTENER -->
<!-- ============================================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const appointmentModals = document.querySelectorAll('[id^="nextAppointmentModal"]');
    if (appointmentModals.length > 0) {
        appointmentModals.forEach(modal => {
            const locationSelect = modal.querySelector('[id^="appointmentLocation"]');
            const dateInput = modal.querySelector('[id^="appointmentDate"]');
            const typeSelect = modal.querySelector('[id^="appointmentType"]');
            const timeSelect = modal.querySelector('[id^="appointmentTime"]');
            const otherDurationContainer = modal.querySelector('[id^="otherDurationContainer"]');
            const otherDurationSelect = modal.querySelector('[id^="otherDuration"]');
            const clientId = modal.id.replace('nextAppointmentModal', '');

            // Only proceed if all required elements exist
            if (locationSelect && dateInput && typeSelect && timeSelect) {
                // FUNCTION TO INITIALIZE FLATPICKR WITH CALIBRATION INTERVAL
                function initializeDatePicker() {
                    // GET CALIBRATION INTERVAL FROM PHP
                    const calibrationInterval = <?php 
                        $clientInfo = getClientInfo($appointment['client_id']);
                        echo $clientInfo['calibration_interval']; 
                    ?>;
                    const today = new Date();
                    const maxDate = new Date();
                    maxDate.setDate(today.getDate() + calibrationInterval);

                    // INITIALIZE FLATPICKR WITH THE CALCULATED MAX DATE
                    flatpickr(dateInput, {
                        dateFormat: "Y-m-d",
                        minDate: "today",
                        maxDate: maxDate
                    });
                }

                // INITIALIZE DATE PICKER WHEN MODAL IS SHOWN
                modal.addEventListener('show.bs.modal', function() {
                    initializeDatePicker();
                });

                // FUNCTION TO CHECK IF ALL REQUIRED FIELDS ARE FILLED
                function checkFields() {
                    const location = locationSelect.value;
                    const date = dateInput.value;
                    const type = typeSelect.value;
                    
                    if (location && date && type) {
                        // GET DURATION BASED ON APPOINTMENT TYPE
                        let duration = 15; // DEFAULT DURATION
                        if (type === 'Other' && otherDurationSelect) {
                            duration = parseInt(otherDurationSelect.value) || 15;
                        } else {
                            switch(type) {
                                case 'Recalibration':
                                case 'Final_download':
                                case 'Paper_Swap':
                                    duration = 15;
                                    break;
                                case 'Removal':
                                case 'Service':
                                    duration = 30;
                                    break;
                            }
                        }

                        // FETCH AVAILABLE TIME SLOTS
                        fetch(`get_available_slots.php?location_id=${location}&date=${date}&type=${type}&duration=${duration}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // CLEAR EXISTING OPTIONS
                                    timeSelect.innerHTML = '<option value="">Select Time</option>';
                                    
                                    // ADD AVAILABLE TIME SLOTS
                                    data.data.forEach(time => {
                                        const option = document.createElement('option');
                                        option.value = time;
                                        
                                        // CONVERT TO 12-HOUR FORMAT FOR DISPLAY
                                        const [hours, minutes] = time.split(':');
                                        const hour = parseInt(hours);
                                        const ampm = hour >= 12 ? 'PM' : 'AM';
                                        const hour12 = hour % 12 || 12;
                                        option.textContent = `${hour12}:${minutes} ${ampm}`;
                                        
                                        timeSelect.appendChild(option);
                                    });

                                    // DISABLE LOCATION, DATE, AND TYPE FIELDS
                                    locationSelect.disabled = true;
                                    dateInput.disabled = true;
                                    typeSelect.disabled = true;
                                    if (otherDurationContainer && otherDurationSelect) {
                                        otherDurationSelect.disabled = true;
                                    }

                                    // ADD VISUAL INDICATION THAT FIELDS ARE LOCKED
                                    locationSelect.classList.add('bg-light');
                                    dateInput.classList.add('bg-light');
                                    typeSelect.classList.add('bg-light');
                                    if (otherDurationContainer && otherDurationSelect) {
                                        otherDurationSelect.classList.add('bg-light');
                                    }
                                } else {
                                    alert('Error fetching available times: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Error fetching available times');
                            });
                    }
                }

                // ADD EVENT LISTENERS
                locationSelect.addEventListener('change', checkFields);
                dateInput.addEventListener('change', checkFields);
                typeSelect.addEventListener('change', checkFields);
                if (otherDurationSelect) {
                    otherDurationSelect.addEventListener('change', checkFields);
                }
            }
        });
    }
});
</script>

<?php include 'footer.php'; ?>

