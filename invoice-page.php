<?php
require_once 'auth_check.php';
require_once 'db.php';
require_once 'balance_calculator.php'; // Include the balance calculator file

// Get client ID from URL
$client_id = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;

// Fetch client data
try {
    // Get basic client information
    $stmt = $pdo->prepare("SELECT * FROM client_information WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();
    
    if (!$client) {
        die("Client not found");
    }
    
    // Get handset information
    $hs_stmt = $pdo->prepare("SELECT serial_number FROM hs_inventory WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
    $hs_stmt->execute([$client_id]);
    $hs_result = $hs_stmt->fetch();
    $handset = $hs_result ? $hs_result['serial_number'] : 'Not Assigned';
    
    // Get control box information
    $cb_stmt = $pdo->prepare("SELECT serial_number FROM cb_inventory WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
    $cb_stmt->execute([$client_id]);
    $cb_result = $cb_stmt->fetch();
    $control_box = $cb_result ? $cb_result['serial_number'] : 'Not Assigned';
    
    // Get balance information
    $today = date('Y-m-d');
    $rent_balance = calculateRentBalance($client_id, $today);
    
    // Get locations for dropdown
    $locations_stmt = $pdo->prepare("SELECT id, location_name FROM locations ORDER BY location_name");
    $locations_stmt->execute();
    $locations = $locations_stmt->fetchAll();
    
    // Log the balance calculation
    echo "<script>console.log('Balance Calculation:', { customer_id: {$client_id}, date: '{$today}', rent_balance: {$rent_balance} });</script>";
    
} catch (PDOException $e) {
    die("Error: Could not fetch client data: " . $e->getMessage());
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
    
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Theme CSS -->
    <link rel="stylesheet" href="/dashui/assets/css/theme.min.css">

    <title>Invoice | IDNJ</title>

    <style>
.flatpickr-day.disabled {
    background: #eee !important;
    color: #bbb !important;
    opacity: 1 !important;
    cursor: not-allowed;
}
</style>
</head>

<body>
<?php include 'navigation.php'; ?>

<div id="app-content">
    <div class="app-content-area">
        <div class="container-fluid">
            <div class="content-wrapper">
                <!-- Page header -->
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-12">
                      <div class="d-flex align-items-center mb-8"> 
                            <div>
                                <h3 class="mb-0 fw-bold">Invoice</h3>
                                <p class="text-muted mb-0">Location: <?php echo htmlspecialchars($_SESSION['location_name'] ?? 'Unknown'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="row">
                    <div class="col-md-12 ps-20">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <!-- Column 1 -->
                                    <div class="col-md-5 ps-12">
                                        <h4 class="mb-4"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-0">ID: <?php echo htmlspecialchars($client['id']); ?></p>
                                        <p class="mb-0">Control Box: <?php echo htmlspecialchars($control_box); ?></p>
                                        <p class="mb-0">Handset: <?php echo htmlspecialchars($handset); ?></p>
                                    </div>
                                </div>

                                        <!-- Appointment Section -->
                                        <div class="mt-4">
                                    <h5 class="mb-3">Appointment</h5>
                                        <!-- Appointment Form -->
                                        <form id="appointmentForm">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-check mb-3">
                                                        <input class="form-check-input" type="checkbox" id="noAppointment" name="no_appointment">
                                                        <label class="form-check-label" for="noAppointment">
                                                            No Appointment Needed
                                                        </label>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="location" class="form-label">Location</label>
                                                        <select class="form-select" id="location" name="location_id" required>
                                                            <option value="">Select Location</option>
                                                            <?php foreach ($locations as $location): ?>
                                                                <option value="<?php echo $location['id']; ?>">
                                                                    <?php echo htmlspecialchars($location['location_name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="appointmentDate" class="form-label">Appointment Date</label>
                                                        <input type="text" class="form-control" id="appointmentDate" name="appointment_date" required>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="appointmentType" class="form-label">Appointment Type</label>
                                                        <select class="form-select" id="appointmentType" name="appointment_type" required>
                                                            <option value="">Select Type</option>
                                                            <option value="Recalibration">Recalibration</option>
                                                            <option value="Removal">Removal</option>
                                                            <option value="Final_download">Final Download</option>
                                                            <option value="Service">Service</option>
                                                            <option value="Paper_Swap">Paper Swap</option>
                                                            <option value="Other">Other</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="appointmentTime" class="form-label">Appointment Time</label>
                                                        <select class="form-select" id="appointmentTime" name="appointment_time" required>
                                                            <option value="">Select Time</option>
                                                        </select>
                                                    </div>
                                                    
                                                        <div class="mb-7">
                                                        <label for="notes" class="form-label">Notes</label>
                                                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <button type="button" class="btn btn-primary" onclick="saveAppointment()">Save Appointment</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    </div>

                                    <!-- Column 2 -->
                                    <div class="col-md-7  mt-9 ps-15 pe-8">
                                        <div class="row mb-4">
                                            <div class="col-md-12">
                                                <p class="mb-0">Install Date: <?php echo $client['install_on'] ? date('m/d/Y', strtotime($client['install_on'])) : 'Not Set'; ?></p>
                                                <p class="mb-0">Removal Date: <?php echo $client['removal_date'] ? date('m/d/Y', strtotime($client['removal_date'])) : 'Not Set'; ?></p>
                                                <p class="mb-0">Account Balance: 
                                                    <?php 
                                                    if ($rent_balance !== null) {
                                                        if ($rent_balance < 0) {
                                                            echo '<span class="text-success">Customer Credit: $' . number_format(abs($rent_balance), 2) . '</span>';
                                                        } else if ($rent_balance > 0) {
                                                            echo '<span class="text-danger">Balance Owed: $' . number_format($rent_balance, 2) . '</span>';
                                                        } else {
                                                            echo '<span class="text-success">$0.00</span>';
                                                        }
                                                    } else {
                                                        echo 'Error loading balance';
                                                    }
                                                    ?>
                                                </p>
                                            </div>
                                        </div>

                                        <h5 class="mb-3">Invoice</h5>
                                        
                                        <!-- Pending Appointment Date -->
                                        <div class="mb-3">
                                            <span class="form-check-label">Pending Appointment Date: <span id="pendingAppointmentDate" class="fw-bold">Not Set</span></span>
                                        </div>
                                        
                                        <!-- Services Section -->
                                        <div class="mb-4">
                                            
                                            <div id="servicesContainer">
                                                <!-- Service rows will be added here dynamically -->
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addServiceRow()">
                                                <i class="bi bi-plus"></i> Add Service
                                            </button>
                                        </div>

                                        <!-- Totals Section -->
                                        <div class="mb-4">
                                            <div class="row">
                                                <div class="col-md-8 offset-md-3">
                                                    <table class="table table-sm">
                                                        <tr>
                                                            <td id="rentDueLabel">Rent Due:</td>
                                                            <td class="text-end">$<span id="rentDue">0.00</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Service Due:</td>
                                                            <td class="text-end">$<span id="serviceDue">0.00</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Subtotal:</td>
                                                            <td class="text-end">$<span id="subtotal">0.00</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Sales Tax (6.625%):</td>
                                                            <td class="text-end">$<span id="tax">0.00</span></td>
                                                        </tr>
                                                        <tr class="fw-bold">
                                                            <td>Total:</td>
                                                            <td class="text-end">$<span id="total">0.00</span></td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Payment Section -->
                                        <div class="mb-4">
                                            
                                            <div class="mb-3">
                                                <label class="form-label fw-bold fs-4">Total Due: $<span id="totalDue" class="fw-bold fs-4">0.00</span></label>
                                            </div>
                                            <div id="paymentMethodsContainer">
                                                <!-- First payment method (always visible) -->
                                                <div class="row mb-3 payment-method-row">
                                                    <div class="col-5">
                                                        <div class="mb-3">
                                                            <label class="form-label">Amount Collected</label>
                                                            <input type="number" class="form-control payment-amount fw-bold" step="0.01" min="0" onchange="updateTotalDue()" oninput="updateTotalDue()">
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="mb-3">
                                                            <label class="form-label">Payment Method</label>
                                                            <div class="input-group">
                                                                <select class="form-select payment-method" onchange="updatePaymentMethodOptions(this)">
                                                                    <option value="credit_card" selected>Credit Card</option>
                                                                    <option value="cash">Cash</option>
                                                                    <option value="check">Check</option>
                                                                    <option value="other">Other</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-1 d-flex align-items-center justify-content-center pt-6">
                                                        <button type="button" class="btn btn-link text-danger p-0 me-3" disabled style="opacity: 0.5;">
                                                            <i class="bi bi-trash fs-5"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-primary btn-sm p-0" onclick="addPaymentMethod()">
                                                            <i class="bi bi-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            </button>
                                        </div>
                                        <!-- Submit Button -->
                                        <div class="d-flex justify-content-start mb-3">
                                            <button type="button" class="btn btn-primary" id="submitInvoiceBtn" disabled onclick="submitInvoice()">Submit Invoice</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    // Calculate max date (60 days from today)
    const today = new Date();
today.setHours(0, 0, 0, 0); // Clear time

const sixtyDaysLater = new Date(today);
sixtyDaysLater.setDate(today.getDate() + 60);

const flatpickrConfig = {
    dateFormat: "m/d/Y",
    minDate: "today",
    disableMobile: "true",
    allowInput: true,
    altInput: false,
    altFormat: "F j, Y",
    width: "100%",
    calendarWidth: "300px",
    position: "below",
    disable: [
        function(date) {
            // Disable dates before today or after 60 days
            return date < today || date > sixtyDaysLater;
        }
    ],
    onDayCreate: function(dObj, dStr, fp, dayElem) {
        const date = dayElem.dateObj;
        if (date < today || date > sixtyDaysLater) {
            dayElem.classList.add("disabled");
        }
    }
};

flatpickr("#appointmentDate", flatpickrConfig);

    // Function to disable appointment fields
    function disableAppointmentFields() {
        const formElements = document.querySelectorAll('#appointmentForm select, #appointmentForm input[type="text"]');
        formElements.forEach(element => {
            element.disabled = true;
            element.classList.add('bg-light');
            element.removeAttribute('required');
        });
        
        // Disable save button and change its appearance
        const saveButton = document.querySelector('#appointmentForm button[type="button"]');
        if (saveButton) {
            saveButton.disabled = true;
            saveButton.classList.remove('btn-primary');
            saveButton.classList.add('btn-secondary');
            saveButton.style.opacity = '0.65';
        }
    }

    // Handle no appointment checkbox
    document.getElementById('noAppointment').addEventListener('change', function() {
        if (this.checked) {
            // Make API call to balance calculator
            const clientId = <?php echo $client_id; ?>;
            const today = new Date().toISOString().split('T')[0]; // Get today's date in YYYY-MM-DD format

            fetch(`balance_calculator.php?customer_id=${clientId}&date=${today}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const rentBalance = data.data.rent_balance;
                        
                        // Update rent due display
                        const rentDueElement = document.getElementById('rentDue');
                        rentDueElement.textContent = rentBalance.toFixed(2);
                        
                        // Update pending appointment date
                        document.getElementById('pendingAppointmentDate').textContent = 'Not Needed';
                        
                        // Update totals
                        updateTotals();
                        
                        // Disable and gray out appointment fields
                        disableAppointmentFields();
                        
                        // Enable submit invoice button
                        document.getElementById('submitInvoiceBtn').disabled = false;
                    } else {
                        console.error('Error fetching balance:', data.error);
                        alert('Error calculating rent balance. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error calculating rent balance. Please try again.');
                });
        } else {
            // Re-enable fields if unchecked
        const formElements = document.querySelectorAll('#appointmentForm select, #appointmentForm input[type="text"]');
        formElements.forEach(element => {
                element.disabled = false;
                element.classList.remove('bg-light');
                element.setAttribute('required', 'required');
            });
            
            // Re-enable save button
            const saveButton = document.querySelector('#appointmentForm button[type="button"]');
            if (saveButton) {
                saveButton.disabled = false;
                saveButton.classList.remove('btn-secondary');
                saveButton.classList.add('btn-primary');
                saveButton.style.opacity = '1';
            }
            
            // Reset pending appointment date
            document.getElementById('pendingAppointmentDate').textContent = 'Not Set';
            
            // Disable submit invoice button
            document.getElementById('submitInvoiceBtn').disabled = true;
        }
    });

    // Get form elements
    const locationSelect = document.getElementById('location');
    const dateInput = document.getElementById('appointmentDate');
    const typeSelect = document.getElementById('appointmentType');
    const timeSelect = document.getElementById('appointmentTime');

    // Function to check if all required fields are filled
    function checkRequiredFields() {
        const locationSelected = locationSelect.value !== '';
        const dateSelected = dateInput.value !== '';
        const typeSelected = typeSelect.value !== '';
        
        return locationSelected && dateSelected && typeSelected;
    }

    // Function to fetch available time slots
    async function fetchAvailableSlots() {
        // Calculate duration based on appointment type
        const duration = ['Recalibration', 'Final_download', 'Paper_Swap'].includes(typeSelect.value) ? 15 : 30;

        // Convert date from m/d/Y to Y-m-d format
        const [month, day, year] = dateInput.value.split('/');
        const formattedDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;

        // Construct URL with query parameters
        const url = new URL('get_available_slots.php', window.location.origin);
        url.searchParams.append('location_id', locationSelect.value);
        url.searchParams.append('date', formattedDate);
        url.searchParams.append('type', typeSelect.value);
        url.searchParams.append('duration', duration);

        console.log('Sending request to get_available_slots.php with data:', {
            location_id: locationSelect.value,
            date: formattedDate,
            type: typeSelect.value,
            duration: duration
        });

        try {
            const response = await fetch(url.toString());
            const result = await response.json();
            console.log('Received response from get_available_slots.php:', JSON.stringify(result, null, 2));

            // Clear existing time options except the first one
            while (timeSelect.options.length > 1) {
                timeSelect.remove(1);
            }

            // Add available time slots to dropdown
            if (result.success && result.data) {
                result.data.forEach(slot => {
                    const option = document.createElement('option');
                    option.value = slot;
                    option.textContent = slot;
                    timeSelect.appendChild(option);
                });
                timeSelect.disabled = false;

                // Disable location, date, and type fields
                locationSelect.disabled = true;
                dateInput.disabled = true;
                typeSelect.disabled = true;

                // Add visual indication that fields are locked
                locationSelect.classList.add('bg-light');
                dateInput.classList.add('bg-light');
                typeSelect.classList.add('bg-light');
            } else {
                console.error('Error fetching time slots:', result.message || 'Unknown error');
                timeSelect.disabled = true;
            }
        } catch (error) {
            console.error('Error making API request:', error);
            timeSelect.disabled = true;
        }
    }

    // Add event listeners to all required fields
    [locationSelect, dateInput, typeSelect].forEach(element => {
        element.addEventListener('change', function() {
            if (checkRequiredFields()) {
                fetchAvailableSlots();
            } else {
                timeSelect.disabled = true;
                timeSelect.value = '';
            }
        });
    });

    // Initialize time select as disabled
    timeSelect.disabled = true;

    // Add one service row by default
    addServiceRow();

    function saveAppointment() {
        // Get form elements
        const locationSelect = document.getElementById('location');
        const dateInput = document.getElementById('appointmentDate');
        const typeSelect = document.getElementById('appointmentType');
        const timeSelect = document.getElementById('appointmentTime');
        const noAppointmentCheckbox = document.getElementById('noAppointment');
        const notesTextarea = document.getElementById('notes');

        // Check if "No Appointment Needed" is checked
        if (noAppointmentCheckbox.checked) {
            // If no appointment needed, we can proceed
            updateRentBalance();
            return;
        }

        // Validate required fields
        if (!locationSelect.value) {
            alert('Please select a location');
            return;
        }
        if (!dateInput.value) {
            alert('Please select an appointment date');
            return;
        }
        if (!typeSelect.value) {
            alert('Please select an appointment type');
            return;
        }
        if (!timeSelect.value) {
            alert('Please select an appointment time');
            return;
        }

        // Format and set the pending appointment date
        const [month, day, year] = dateInput.value.split('/');
        const formattedDate = `${month}/${day}/${year}`;
        document.getElementById('pendingAppointmentDate').textContent = formattedDate;

        // If all validations pass, update rent balance
        updateRentBalance();
    }

    function updateRentBalance() {
        const dateInput = document.getElementById('appointmentDate');
        const clientId = <?php echo $client_id; ?>; // Get client ID from PHP variable
        
        // Format date for API (YYYY-MM-DD)
        const [month, day, year] = dateInput.value.split('/');
        const formattedDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;

        // Make API call to balance calculator
        fetch(`balance_calculator.php?customer_id=${clientId}&date=${formattedDate}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const rentBalance = data.data.rent_balance;
                    
                    // Update rent due display
                    const rentDueElement = document.getElementById('rentDue');
                    rentDueElement.textContent = rentBalance.toFixed(2);
                    
                    // Update totals
                    updateTotals();

                    // Disable all appointment fields
                    disableAppointmentFields();
                    
                    // Enable submit invoice button
                    document.getElementById('submitInvoiceBtn').disabled = false;
                } else {
                    console.error('Error fetching balance:', data.error);
                    alert('Error calculating rent balance. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error calculating rent balance. Please try again.');
            });
    }

    function updateTotals() {
        // Get rent due
        const rentDue = parseFloat(document.getElementById('rentDue').textContent) || 0;
        
        // Calculate service due
        let serviceDue = 0;
        const serviceRows = document.querySelectorAll('#servicesContainer .row');
        serviceRows.forEach(row => {
            const priceInput = row.querySelector('.service-price');
            if (priceInput) {
                serviceDue += parseFloat(priceInput.value) || 0;
            }
        });
        
        // Calculate subtotal
        const subtotal = rentDue + serviceDue;
        
        // Calculate tax (only on positive amounts)
        const tax = subtotal > 0 ? subtotal * 0.06625 : 0;
        
        // Calculate total
        const total = subtotal + tax;
        
        // Update display
        document.getElementById('serviceDue').textContent = serviceDue.toFixed(2);
        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('tax').textContent = tax.toFixed(2);
        document.getElementById('total').textContent = total.toFixed(2);
        
        // Update total due
        updateTotalDue();
    }

    // Function to add a new service row
    function addServiceRow() {
        const container = document.getElementById('servicesContainer');
        const row = document.createElement('div');
        row.className = 'row mb-1';
        
        // Get services from PHP
        const services = <?php 
            $stmt = $pdo->prepare("SELECT id, name, price FROM services 
                                  WHERE name != 'Install' 
                                  ORDER BY 
                                    CASE 
                                        WHEN name = 'Recalibration' THEN 1
                                        WHEN name = 'Monitoring Check' THEN 2
                                        ELSE 3
                                    END,
                                    name");
            $stmt->execute();
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Replace Change Vehicle tiers with CV - Tier format
            foreach ($services as &$service) {
                if (strpos($service['name'], 'Change Vehicle') === 0) {
                    $tier = str_replace('Change Vehicle - Tier ', '', $service['name']);
                    $service['name'] = 'CV - Tier ' . $tier;
                }
            }
            echo json_encode($services);
        ?>;
        
        row.innerHTML = `
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Service</label>
                    <select class="form-select service-select" onchange="updateServicePrice(this)">
                        <option value="">Select Service</option>
                        ${services.map(service => 
                            `<option value="${service.id}" data-price="${service.price}">${service.name} - $${parseFloat(service.price).toFixed(2)}</option>`
                        ).join('')}
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Price</label>
                    <input type="number" class="form-control service-price" step="0.01" min="0" onchange="updateTotals()">
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-center justify-content-center pt-4">
                <button type="button" class="btn btn-link text-danger p-0" onclick="removeServiceRow(this)">
                    <i class="bi bi-trash fs-5"></i>
                </button>
            </div>
        `;
        container.appendChild(row);
    }

    // Function to update service price based on selection
    function updateServicePrice(select) {
        const selectedOption = select.options[select.selectedIndex];
        const price = selectedOption.getAttribute('data-price');
        const priceInput = select.closest('.row').querySelector('.service-price');
        
        if (price) {
            priceInput.value = price;
            updateTotals();
        } else {
            priceInput.value = '';
            updateTotals();
        }
    }

    // Function to remove a service row
    function removeServiceRow(button) {
        const row = button.closest('.row');
        row.remove();
        updateTotals();
    }

    // Function to update total due
    function updateTotalDue() {
        let totalDue = parseFloat(document.getElementById('total').textContent) || 0;
        let totalCollected = 0;
        
        // Sum up all payment amounts
        const paymentAmounts = document.querySelectorAll('.payment-amount');
        paymentAmounts.forEach(input => {
            totalCollected += parseFloat(input.value) || 0;
        });
        
        // Calculate remaining due (can be negative if overpaid)
        const remainingDue = totalDue - totalCollected;
        
        // Update total due display and color
        const totalDueElement = document.getElementById('totalDue');
        totalDueElement.textContent = remainingDue.toFixed(2);
        
        // Update color based on remaining due
        if (remainingDue <= 0) {
            totalDueElement.classList.remove('text-danger');
            totalDueElement.classList.add('text-success');
        } else {
            totalDueElement.classList.remove('text-success');
            totalDueElement.classList.add('text-danger');
        }
    }

    // Function to add a new payment method row
    function addPaymentMethod() {
        const container = document.getElementById('paymentMethodsContainer');
        const newRow = document.createElement('div');
        newRow.className = 'row mb-2 payment-method-row';
        
        // Get all currently used payment methods
        const usedMethods = new Set();
        const existingSelects = document.querySelectorAll('.payment-method');
        existingSelects.forEach(select => {
            if (select.value) {
                usedMethods.add(select.value);
            }
        });
        
        // Create options HTML, excluding used methods
        const options = [
            { value: 'credit_card', text: 'Credit Card' },
            { value: 'cash', text: 'Cash' },
            { value: 'check', text: 'Check' },
            { value: 'other', text: 'Other' }
        ].filter(option => !usedMethods.has(option.value))
         .map(option => `<option value="${option.value}">${option.text}</option>`)
         .join('');
        
        newRow.innerHTML = `
            <div class="col-5">
                <div class="mb-3">
                    <label class="form-label">Amount Collected</label>
                    <input type="number" class="form-control payment-amount fw-bold" step="0.01" min="0" onchange="updateTotalDue()" oninput="updateTotalDue()">
                </div>
            </div>
            <div class="col-4">
                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <div class="input-group">
                        <select class="form-select payment-method" onchange="updatePaymentMethodOptions(this)">
                            ${options}
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-1 d-flex align-items-center justify-content-center pt-6">
                <button type="button" class="btn btn-link text-danger p-0 me-3" onclick="removePaymentMethod(this)">
                    <i class="bi bi-trash fs-5"></i>
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm p-0" onclick="addPaymentMethod()">
                    <i class="bi bi-plus"></i>
                </button>
            </div>
        `;
        
        container.appendChild(newRow);
    }

    // Function to update payment method options when a selection is made
    function updatePaymentMethodOptions(select) {
        const allSelects = document.querySelectorAll('.payment-method');
        const usedMethods = new Set();
        
        // Get all currently used payment methods
        allSelects.forEach(s => {
            if (s.value && s !== select) {
                usedMethods.add(s.value);
            }
        });
        
        // Update all dropdowns except the one that was just changed
        allSelects.forEach(s => {
            if (s !== select) {
                const currentValue = s.value;
                const options = [
                    { value: 'credit_card', text: 'Credit Card' },
                    { value: 'cash', text: 'Cash' },
                    { value: 'check', text: 'Check' },
                    { value: 'other', text: 'Other' }
                ].filter(option => !usedMethods.has(option.value));
                
                s.innerHTML = options.map(option => 
                    `<option value="${option.value}" ${option.value === currentValue ? 'selected' : ''}>${option.text}</option>`
                ).join('');
            }
        });
    }

    // Function to remove a payment method row
    function removePaymentMethod(button) {
        const row = button.closest('.payment-method-row');
        const removedValue = row.querySelector('.payment-method').value;
        row.remove();
        updateTotalDue();
        
        // Update other dropdowns to include the removed method
        const allSelects = document.querySelectorAll('.payment-method');
        allSelects.forEach(select => {
            const currentValue = select.value;
            const options = [
                { value: 'credit_card', text: 'Credit Card' },
                { value: 'cash', text: 'Cash' },
                { value: 'check', text: 'Check' },
                { value: 'other', text: 'Other' }
            ];
            
            select.innerHTML = options.map(option => 
                `<option value="${option.value}" ${option.value === currentValue ? 'selected' : ''}>${option.text}</option>`
            ).join('');
        });
    }

    // Function to submit invoice
    function submitInvoice() {
        // Get all payment amounts and methods
        const payments = {};
        const paymentRows = document.querySelectorAll('.payment-method-row');
        paymentRows.forEach(row => {
            const amount = parseFloat(row.querySelector('.payment-amount').value) || 0;
            const method = row.querySelector('.payment-method').value;
            payments[method] = (payments[method] || 0) + amount;
        });

        // Get selected services
        const services = [];
        const serviceRows = document.querySelectorAll('#servicesContainer .row');
        serviceRows.forEach(row => {
            const select = row.querySelector('.service-select');
            const priceInput = row.querySelector('.service-price');
            if (select.value && priceInput.value) {
                services.push({
                    service_id: select.value,
                    name: select.options[select.selectedIndex].text.split(' - ')[0],
                    price: parseFloat(priceInput.value)
                });
            }
        });

        // Get totals
        const rentTotal = parseFloat(document.getElementById('rentDue').textContent) || 0;
        const serviceTotal = parseFloat(document.getElementById('serviceDue').textContent) || 0;
        const subTotal = parseFloat(document.getElementById('subtotal').textContent) || 0;
        const taxAmount = parseFloat(document.getElementById('tax').textContent) || 0;
        const invoiceTotal = parseFloat(document.getElementById('total').textContent) || 0;
        const totalCollected = Object.values(payments).reduce((sum, amount) => sum + amount, 0);
        const remainingBalance = parseFloat(document.getElementById('totalDue').textContent) || 0;

        // Get location and customer info
        const appointmentLocationId = document.getElementById('location').value; // Location from form for appointment
        const customerId = <?php echo $client_id; ?>;
        const createdBy = <?php echo $_SESSION['user_id'] ?? 1; ?>;
        const loggedInLocationId = <?php echo $_SESSION['location_id'] ?? 0; ?>; // Logged-in user's location

        // Prepare the request data
        const requestData = {
            request_type: "create_invoice_with_payment",
            method: "POST",
            customer_id: customerId,
            created_by: createdBy,
            location_id: loggedInLocationId, // Use logged-in user's location
            payment_type: Object.keys(payments)[0] || "credit_card", // Default to credit_card if no payments
            amount: totalCollected.toFixed(2),
            services: services,
            payments: Object.fromEntries(
                Object.entries(payments).map(([key, value]) => [key, value.toFixed(2)])
            ),
            rent_total: rentTotal.toFixed(2),
            service_total: serviceTotal.toFixed(2),
            sub_total: subTotal.toFixed(2),
            tax_amount: taxAmount.toFixed(2),
            invoice_total: invoiceTotal.toFixed(2),
            total_collected: totalCollected.toFixed(2),
            remaining_balance: remainingBalance.toFixed(2)
        };

        // Make the API call
        fetch('payment_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            // Log the payment API response
            console.log('Payment API Response:', JSON.stringify(data, null, 2));
            
            if (data.success) {
                // Store the invoice_id from the payment API response
                const invoiceId = data.data.invoice_id;
                console.log('Invoice ID from response:', invoiceId);
                
                // Check if "No Appointment Needed" is checked
                const noAppointmentChecked = document.getElementById('noAppointment').checked;
                
                if (!noAppointmentChecked) {
                    // Only make appointment API call if noAppointment is not checked
                    const appointmentType = document.getElementById('appointmentType').value;
                    const appointmentDate = document.getElementById('appointmentDate').value;
                    const appointmentTime = document.getElementById('appointmentTime').value;
                    const notes = document.getElementById('notes').value;

                    // Format the date and time for the API
                    const [month, day, year] = appointmentDate.split('/');
                    const formattedDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
                    const startTime = `${formattedDate} ${appointmentTime}`;

                    // Prepare appointment data
                    const appointmentData = {
                        customer_id: customerId,
                        title: `${appointmentType} Appointment`,
                        appointment_type: appointmentType.toLowerCase(),
                        start_time: startTime,
                        location_id: appointmentLocationId, // Use location from form
                        service_note: notes,
                        status: "scheduled",
                        invoice_id: invoiceId // Include the invoice_id from payment API response
                    };

                    // Make appointment API call
                    return fetch('appointment_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(appointmentData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Invoice and appointment submitted successfully!');
                            // Redirect to print_invoice.php with the invoice ID
                            window.location.href = `print_invoice.php?invoice_id=${invoiceId}`;
                        } else {
                            throw new Error(data.error || 'Unknown error');
                        }
                    });
                } else {
                    // If noAppointment is checked, just show success message and redirect
                    alert('Invoice submitted successfully!');
                    window.location.href = `print_invoice.php?invoice_id=${invoiceId}`;
                    return Promise.resolve();
                }
            } else {
                throw new Error(data.error || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error submitting invoice/appointment. Please try again.');
        });
    }
</script>
</body>
</html>