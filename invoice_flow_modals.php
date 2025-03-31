<?php
/**
 * Database Query Functions Section
 * This section contains all database queries needed for the invoice flow modals.
 * Each function is documented with its purpose and expected return values.
 */

// =============================================
// REQUIRED FILES
// =============================================
include_once 'get_locations.php';
require_once 'balance_calculator.php';

// =============================================
// DATABASE QUERY FUNCTIONS
// =============================================
// These functions handle all database operations for the invoice flow
// Each function is specific to a particular data need

/**
 * Gets client data needed for invoice flow
 * Returns: removal_date and calibration_interval from client_information table
 */
function getClientInvoiceData($client_id, $pdo) {
    try {
        $query = "SELECT removal_date, calibration_interval FROM client_information WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$client_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching client invoice data: " . $e->getMessage());
        return false;
    }
}

/**
 * Interprets calibration interval string into a date
 * Returns: Y-m-d formatted date string or null if "No Limit"
 */
function interpretCalibrationInterval($interval) {
    if (!$interval || $interval === 'No Limit') {
        return null;
    }
    
    // Extract number from string (e.g., "30 day" -> 30)
    if (preg_match('/(\d+)\s*day/i', $interval, $matches)) {
        $days = (int)$matches[1];
        // Calculate date X days from today
        $date = date('Y-m-d', strtotime("+{$days} days"));
        return $date;
    }
    
    return null;
}

/**
 * Gets next scheduled appointment for client
 * Returns: location_name, appointment_date, appointment_time
 * Only returns appointments from today onwards
 */
function getNextScheduledAppointment($client_id, $pdo) {
    try {
        $query = "SELECT a.start_time, l.location_name, a.appointment_type 
                 FROM appointments a 
                 LEFT JOIN locations l ON a.location_id = l.id 
                 WHERE a.customer_id = ? 
                 AND a.start_time >= CURDATE() 
                 ORDER BY a.start_time ASC 
                 LIMIT 1";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$client_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching next appointment: " . $e->getMessage());
        return false;
    }
}

/**
 * Gets inventory information for a client
 * Returns: control box and handset serial numbers
 */
function getClientInventory($client_id, $pdo) {
    try {
        // Get control box info
        $cb_query = "SELECT serial_number FROM cb_inventory WHERE customer_id = ?";
        $cb_stmt = $pdo->prepare($cb_query);
        $cb_stmt->execute([$client_id]);
        $control_box = $cb_stmt->fetch(PDO::FETCH_ASSOC);

        // Get handset info
        $hs_query = "SELECT serial_number FROM hs_inventory WHERE customer_id = ?";
        $hs_stmt = $pdo->prepare($hs_query);
        $hs_stmt->execute([$client_id]);
        $handset = $hs_stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'control_box' => $control_box ? $control_box['serial_number'] : 'Missing',
            'handset' => $handset ? $handset['serial_number'] : 'Missing'
        ];
    } catch (PDOException $e) {
        error_log("Error fetching inventory data: " . $e->getMessage());
        return [
            'control_box' => 'Error',
            'handset' => 'Error'
        ];
    }
}

/**
 * Modal Rendering Functions Section
 * This section contains functions that render the various modals in the invoice flow.
 * Each function is responsible for displaying specific information and handling
 * user interactions for that step of the invoice process.
 */

// =============================================
// MODAL RENDERING FUNCTIONS
// =============================================
// These functions create the UI elements for the invoice flow
// Each function handles a specific step in the process

/**
 * Creates the Next Appointment Modal
 * Shows: Client info, removal date, next appointment
 * Allows: Setting new appointment or marking as no appointment needed
 */
function renderNextAppointmentModal($client, $pdo) {
    // Fetch additional client data needed for this modal
    $client_data = getClientInvoiceData($client['id'], $pdo);
    $removal_date = $client_data ? $client_data['removal_date'] : null;
    $calibration_interval = $client_data ? $client_data['calibration_interval'] : null;
    $max_days = interpretCalibrationInterval($calibration_interval);
    
    // Get next scheduled appointment
    $next_appointment = getNextScheduledAppointment($client['id'], $pdo);
    $appointment_display = "No Scheduled Appointments";
    
    if ($next_appointment) {
        $date = date('m/d/y', strtotime($next_appointment['start_time']));
        $time = date('g:i A', strtotime($next_appointment['start_time']));
        $type = ucfirst(str_replace('_', ' ', $next_appointment['appointment_type']));
        $appointment_display = $next_appointment['location_name'] . " - " . $type . " - " . $date . " " . $time;
    }

    // First render the invoice modal to ensure it exists
    renderInvoiceModal($client, $pdo);
    ?>
                            <!-- ============================================= -->
                            <!-- Appointment Modal -->
                            <!-- ============================================= -->
    <div class="modal fade" id="nextAppointmentModal<?php echo htmlspecialchars($client['id']); ?>" tabindex="-1" aria-labelledby="nextAppointmentModalLabel<?php echo htmlspecialchars($client['id']); ?>" aria-hidden="true">
        <!-- Add Flatpickr CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="nextAppointmentModalLabel<?php echo htmlspecialchars($client['id']); ?>">
                        Next Appointment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Client Info Section -->
                    <div class="mb-4">
                        <p class="mb-1"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></p>
                        <p class="mb-0">ID: <?php echo htmlspecialchars($client['id']); ?></p>
                        <p class="mb-0">Removal Date: <?php echo $removal_date ? htmlspecialchars($removal_date) : 'Missing'; ?></p>
                        <p class="mb-0">Calibration Interval: <?php echo $calibration_interval ? htmlspecialchars($calibration_interval) : 'Not Set'; ?></p>
                        <p class="mb-0">Next Appointment: <?php echo htmlspecialchars($appointment_display); ?></p>
                    </div>

                    <!-- Appointment Form -->
                    <form id="nextAppointmentForm<?php echo htmlspecialchars($client['id']); ?>">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="noAppointment<?php echo htmlspecialchars($client['id']); ?>" onchange="toggleAppointmentFields(<?php echo htmlspecialchars($client['id']); ?>)">
                                <label class="form-check-label" for="noAppointment<?php echo htmlspecialchars($client['id']); ?>">
                                    No Appointment Needed
                                </label>
                            </div>
                        </div>
                        <div id="appointmentFields<?php echo htmlspecialchars($client['id']); ?>">
                            <!-- ============================================= -->
                            <!-- LOCATION SELECTION -->
                            <!-- ============================================= -->
                            <!-- Required field for setting appointment location -->
                            <div class="mb-3">
                                <label for="appointmentLocation<?php echo htmlspecialchars($client['id']); ?>" class="form-label">Location</label>
                                <select class="form-select" id="appointmentLocation<?php echo htmlspecialchars($client['id']); ?>" required>
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

                            <!-- ============================================= -->
                            <!-- APPOINTMENT DATE/TIME -->
                            <!-- ============================================= -->
                            <div class="mb-3">
                                <label for="appointmentDate<?php echo htmlspecialchars($client['id']); ?>" class="form-label">Appointment Date</label>
                                <div class="input-group">
                                    <input type="text" class="form-control flatpickr" id="appointmentDate<?php echo htmlspecialchars($client['id']); ?>" required>
                                    <span class="input-group-text"><i class="feather icon-calendar"></i></span>
                                </div>
                            </div>

                            <!-- ============================================= -->
                            <!-- APPOINTMENT TYPE -->
                            <!-- ============================================= -->
                            <!-- Required field for selecting the type of appointment -->
                            <div class="mb-3">
                                <label for="appointmentType<?php echo htmlspecialchars($client['id']); ?>" class="form-label">Appointment Type</label>
                                <select class="form-select" id="appointmentType<?php echo htmlspecialchars($client['id']); ?>" required>
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
                            <div class="mb-3" id="otherDurationContainer<?php echo htmlspecialchars($client['id']); ?>" style="display: none;">
                                <label for="otherDuration<?php echo htmlspecialchars($client['id']); ?>" class="form-label">Appointment Duration</label>
                                <select class="form-select" id="otherDuration<?php echo htmlspecialchars($client['id']); ?>">
                                    <option value="">Select Duration</option>
                                    <option value="15">15 minutes</option>
                                    <option value="30">30 minutes</option>
                                    <option value="45">45 minutes</option>
                                    <option value="60">60 minutes</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="appointmentTime<?php echo htmlspecialchars($client['id']); ?>" class="form-label">Appointment Time</label>
                                <select class="form-select" id="appointmentTime<?php echo htmlspecialchars($client['id']); ?>" required>
                                    <option value="">Select Time</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="serviceNotes<?php echo htmlspecialchars($client['id']); ?>" class="form-label">Notes</label>
                                <textarea class="form-control" id="serviceNotes<?php echo htmlspecialchars($client['id']); ?>" rows="3"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveNextAppointment(<?php echo htmlspecialchars($client['id']); ?>)">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Save Appointment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================= -->
    <!-- MODAL FIELD TOGGLE SCRIPT -->
    <!-- ============================================= -->
    <script>
    /**
     * =============================================
     * MODAL FIELD TOGGLE
     * =============================================
     * Shows/hides appointment fields based on "No Appointment Needed" checkbox
     * Also toggles required attribute on date and time fields
     */
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
        } else {
            appointmentFields.style.display = 'block';
            dateInput.required = true;
            timeSelect.required = true;
        }
    }
    </script>

    <!-- ============================================= -->
    <!-- MODAL RESET SCRIPT -->
    <!-- ============================================= -->
    <script>
    /**
     * =============================================
     * RESET MODAL FIELDS
     * =============================================
     * Resets all fields in the modal to their initial state
     */
    function resetModalFields(clientId) {
        // Reset form fields
        document.getElementById('noAppointment' + clientId).checked = false;
        document.getElementById('appointmentFields' + clientId).style.display = 'block';
        
        // Reset and enable location
        const locationSelect = document.getElementById('appointmentLocation' + clientId);
        locationSelect.value = '';
        locationSelect.disabled = false;
        
        // Reset and enable date
        const dateInput = document.getElementById('appointmentDate' + clientId);
        dateInput.value = '';
        dateInput.disabled = false;
        // Reinitialize Flatpickr
        if (dateInput._flatpickr) {
            dateInput._flatpickr.destroy();
        }
        const maxDate = <?php echo $max_days ? "'" . $max_days . "'" : 'null'; ?>;
        const flatpickrConfig = {
            dateFormat: "Y-m-d",
            minDate: "today",
            disableMobile: "true",
            allowInput: true,
            altInput: true,
            altFormat: "F j, Y",
            width: "100%",
            calendarWidth: "300px",
            position: "below"
        };
        if (maxDate !== null) {
            flatpickrConfig.maxDate = maxDate;
        }
        flatpickr(dateInput, flatpickrConfig);
        
        // Reset and enable type
        const typeSelect = document.getElementById('appointmentType' + clientId);
        typeSelect.value = '';
        typeSelect.disabled = false;
        
        // Reset and hide duration
        const otherDurationContainer = document.getElementById('otherDurationContainer' + clientId);
        const otherDurationSelect = document.getElementById('otherDuration' + clientId);
        if (otherDurationContainer) {
            otherDurationContainer.style.display = 'none';
            otherDurationSelect.value = '';
            otherDurationSelect.disabled = false;
        }
        
        // Reset time slots
        const timeSelect = document.getElementById('appointmentTime' + clientId);
        timeSelect.innerHTML = '<option value="">Select Time</option>';
        timeSelect.disabled = false;
        
        // Reset notes
        document.getElementById('serviceNotes' + clientId).value = '';
    }
    </script>

    <!-- ============================================= -->
    <!-- EVENT LISTENERS SCRIPT -->
    <!-- ============================================= -->
    <script>
    /**
     * =============================================
     * EVENT LISTENERS SETUP
     * =============================================
     * Sets up listeners for location, date, and type changes
     * Triggers time slot fetch when all required fields are filled
     */
    document.getElementById('nextAppointmentModal<?php echo htmlspecialchars($client['id']); ?>').addEventListener('show.bs.modal', function() {
        const locationSelect = document.getElementById('appointmentLocation<?php echo htmlspecialchars($client['id']); ?>');
        const dateInput = document.getElementById('appointmentDate<?php echo htmlspecialchars($client['id']); ?>');
        const typeSelect = document.getElementById('appointmentType<?php echo htmlspecialchars($client['id']); ?>');
        const timeSelect = document.getElementById('appointmentTime<?php echo htmlspecialchars($client['id']); ?>');
        const otherDurationContainer = document.getElementById('otherDurationContainer<?php echo htmlspecialchars($client['id']); ?>');
        const otherDurationSelect = document.getElementById('otherDuration<?php echo htmlspecialchars($client['id']); ?>');

        // Initialize Flatpickr with wider calendar
        const maxDate = <?php echo $max_days ? "'" . $max_days . "'" : 'null'; ?>;
        const flatpickrConfig = {
            dateFormat: "Y-m-d",
            minDate: "today",
            disableMobile: "true",
            allowInput: true,
            altInput: true,
            altFormat: "F j, Y",
            width: "100%",
            calendarWidth: "300px",
            position: "below"
        };

        // Add maxDate if calibration interval is set
        if (maxDate !== null) {
            flatpickrConfig.maxDate = maxDate;
        }

        flatpickr(dateInput, flatpickrConfig);

        // Handle appointment type change
        typeSelect.addEventListener('change', function() {
            const isOther = this.value === 'Other';
            otherDurationContainer.style.display = isOther ? 'block' : 'none';
            if (!isOther) {
                otherDurationSelect.value = ''; // Clear duration when switching away from Other
            }
        });

        // Function to check if all required fields are filled
        function checkFields() {
            const type = typeSelect.value;
            const isOther = type === 'Other';
            
            if (locationSelect.value && dateInput.value && type) {
                if (isOther && !otherDurationSelect.value) {
                    return; // Don't fetch if Other is selected but no duration chosen
                }
                fetchAvailableTimeSlots(<?php echo htmlspecialchars($client['id']); ?>);
            }
        }

        // Add change event listeners
        locationSelect.addEventListener('change', checkFields);
        dateInput.addEventListener('change', checkFields);
        typeSelect.addEventListener('change', checkFields);
        otherDurationSelect.addEventListener('change', checkFields);
    });

    // Add hidden.bs.modal event listener to reset fields when modal is closed
    document.getElementById('nextAppointmentModal<?php echo htmlspecialchars($client['id']); ?>').addEventListener('hidden.bs.modal', function() {
        resetModalFields(<?php echo htmlspecialchars($client['id']); ?>);
    });
    </script>

    <!-- ============================================= -->
    <!-- TIME SLOTS FETCH SCRIPT -->
    <!-- ============================================= -->
    <script>
    /**
     * =============================================
     * TIME SLOTS FETCH
     * =============================================
     * Fetches available time slots from the API
     * Converts 24-hour time to 12-hour format for display
     * Handles loading states and errors
     */
    function fetchAvailableTimeSlots(clientId) {
        const locationId = document.getElementById('appointmentLocation' + clientId).value;
        const date = document.getElementById('appointmentDate' + clientId).value;
        const time = document.getElementById('appointmentTime' + clientId).value;
        const typeSelect = document.getElementById('appointmentType' + clientId);
        const otherDurationContainer = document.getElementById('otherDurationContainer' + clientId);
        const otherDurationSelect = document.getElementById('otherDuration' + clientId);

        // Show loading state
        const timeSelect = document.getElementById('appointmentTime' + clientId);
        timeSelect.innerHTML = '<option value="">Loading available times...</option>';
        timeSelect.disabled = true;

        // Get appointment type and duration
        const type = typeSelect.value;
        let duration = 15; // Default duration for standard appointments

        // Set duration based on appointment type
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

        // Make API call with type and duration
        fetch(`get_available_slots.php?location_id=${locationId}&date=${date}&type=${type}&duration=${duration}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear existing options except the first one
                    timeSelect.innerHTML = '<option value="">Select Time</option>';
                    
                    // Add available time slots
                    data.data.forEach(time => {
                        const option = document.createElement('option');
                        option.value = time; // Keep 24-hour format for value
                        
                        // Convert to 12-hour format for display
                        const [hours, minutes] = time.split(':');
                        const hour = parseInt(hours);
                        const ampm = hour >= 12 ? 'PM' : 'AM';
                        const hour12 = hour % 12 || 12;
                        option.textContent = `${hour12}:${minutes} ${ampm}`;
                        
                        timeSelect.appendChild(option);
                    });

                    // Disable location, date, and type fields once times are loaded
                    const locationSelect = document.getElementById('appointmentLocation' + clientId);
                    locationSelect.disabled = true;
                    const dateInput = document.getElementById('appointmentDate' + clientId);
                    dateInput.disabled = true;
                    // Disable Flatpickr instance
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
    <!-- SAVE APPOINTMENT SCRIPT -->
    <!-- ============================================= -->
    <script>
    /**
     * =============================================
     * SAVE APPOINTMENT
     * =============================================
     * Validates form fields and prepares for saving
     * Makes API call to save appointment
     * Handles response and UI updates
     */
    function saveNextAppointment(clientId) {
        // =============================================
        // BUTTON STATE MANAGEMENT
        // =============================================
        const saveButton = document.querySelector(`#nextAppointmentModal${clientId} .btn-primary`);
        const spinner = saveButton.querySelector('.spinner-border');
        
        // Show spinner, disable button
        spinner.classList.remove('d-none');
        saveButton.disabled = true;

        // =============================================
        // CHECK FOR NO APPOINTMENT NEEDED
        // =============================================
        const noAppointmentCheckbox = document.getElementById('noAppointment' + clientId);
        if (noAppointmentCheckbox.checked) {
            // Disable all form fields
            const form = document.getElementById('nextAppointmentForm' + clientId);
            const fields = form.querySelectorAll('input, select, textarea');
            fields.forEach(field => {
                field.disabled = true;
            });
            
            // Hide save button and show invoice button
            saveButton.style.display = 'none';
            const invoiceButton = document.createElement('button');
            invoiceButton.type = 'button';
            invoiceButton.className = 'btn btn-success';
            invoiceButton.innerHTML = 'Invoice';
            invoiceButton.onclick = () => {
                // Close the appointment modal
                const appointmentModal = bootstrap.Modal.getInstance(document.getElementById('nextAppointmentModal' + clientId));
                appointmentModal.hide();
                
                // Show the invoice modal using Bootstrap's modal
                const invoiceModalElement = document.getElementById('invoiceModal' + clientId);
                if (invoiceModalElement) {
                    const invoiceModal = new bootstrap.Modal(invoiceModalElement);
                    invoiceModal.show();
                } else {
                    console.error('Invoice modal not found. Please refresh the page.');
                    alert('Error: Invoice modal not found. Please refresh the page.');
                }
            };
            saveButton.parentNode.appendChild(invoiceButton);
            
            // Disable the checkbox
            noAppointmentCheckbox.disabled = true;
            
            // Hide spinner and show success message
            spinner.classList.add('d-none');
            alert('No appointment needed');
            return;
        }

        // =============================================
        // FORM DATA COLLECTION
        // =============================================
        const locationId = document.getElementById('appointmentLocation' + clientId).value;
        const date = document.getElementById('appointmentDate' + clientId).value;
        const time = document.getElementById('appointmentTime' + clientId).value;
        const type = document.getElementById('appointmentType' + clientId).value;
        const otherDuration = document.getElementById('otherDuration' + clientId)?.value;
        const notes = document.getElementById('serviceNotes' + clientId).value;

        // =============================================
        // REQUIRED FIELD VALIDATION
        // =============================================
        if (!locationId || !date || !time || !type) {
            alert('Please fill in all required fields');
            saveButton.disabled = false;
            spinner.classList.add('d-none');
            return;
        }

        // Additional validation for "Other" type
        if (type === 'Other' && !otherDuration) {
            alert('Please select a duration for Other appointment type');
            saveButton.disabled = false;
            spinner.classList.add('d-none');
            return;
        }

        // =============================================
        // APPOINTMENT TYPE MAPPING
        // =============================================
        const appointmentType = type === 'Other' ? 
            'other' + otherDuration : 
            type.toLowerCase();

        // =============================================
        // DATE/TIME FORMATTING
        // =============================================
        const [hours, minutes] = time.split(':');
        const startDateTime = new Date(date + 'T' + hours + ':' + minutes);
        const startTime = startDateTime.toISOString().slice(0, 19).replace('T', ' ');

        // =============================================
        // DATA PREPARATION
        // =============================================
        const appointmentData = {
            customer_id: clientId,
            title: type,
            appointment_type: appointmentType,
            start_time: startTime,
            location_id: locationId,
            description: null,
            service_note: notes || null
        };

        // =============================================
        // API CALL
        // =============================================
        fetch('appointment_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(appointmentData)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('Appointment saved successfully!');
                
                // Disable all form fields
                const form = document.getElementById('nextAppointmentForm' + clientId);
                const fields = form.querySelectorAll('input, select, textarea');
                fields.forEach(field => {
                    field.disabled = true;
                });
                
                // Hide save button and show invoice button
                saveButton.style.display = 'none';
                const invoiceButton = document.createElement('button');
                invoiceButton.type = 'button';
                invoiceButton.className = 'btn btn-success';
                invoiceButton.innerHTML = 'Invoice';
                invoiceButton.onclick = () => {
                    // Close the appointment modal
                    const appointmentModal = bootstrap.Modal.getInstance(document.getElementById('nextAppointmentModal' + clientId));
                    appointmentModal.hide();
                    
                    // Show the invoice modal using Bootstrap's modal
                    const invoiceModalElement = document.getElementById('invoiceModal' + clientId);
                    if (invoiceModalElement) {
                        const invoiceModal = new bootstrap.Modal(invoiceModalElement);
                        invoiceModal.show();
                    } else {
                        console.error('Invoice modal not found. Please refresh the page.');
                        alert('Error: Invoice modal not found. Please refresh the page.');
                    }
                };
                saveButton.parentNode.appendChild(invoiceButton);
                
                // Disable the "No Appointment Needed" checkbox
                const noAppointmentCheckbox = document.getElementById('noAppointment' + clientId);
                noAppointmentCheckbox.disabled = true;
                
            } else {
                throw new Error(result.message || 'Failed to save appointment');
            }
        })
        .catch(error => {
            console.error('Error saving appointment:', error);
            alert('Failed to save appointment. Please try again.');
            saveButton.disabled = false;
            spinner.classList.add('d-none');
        });
    }
    </script>

    <!-- Add Flatpickr JS before other scripts -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <?php
}

/**
 * Creates the Invoice Modal
 * Shows: Client info, inventory, account balance, removal date
 * Allows: Adding services and calculating totals
 */
function renderInvoiceModal($client, $pdo) {
    // Get client data
    $client_data = getClientInvoiceData($client['id'], $pdo);
    $removal_date = $client_data ? $client_data['removal_date'] : 'Missing';
    
    // Get inventory info
    $inventory = getClientInventory($client['id'], $pdo);
    
    // Get today's date for account balance
    $today = date('m/d/Y');
    
    // Calculate current rent balance due
    $rent_balance = calculateRentBalance($client['id']);
    ?>
    <!-- ============================================= -->
    <!-- INVOICE MODAL -->
    <!-- ============================================= -->
    <div class="modal fade" id="invoiceModal<?php echo htmlspecialchars($client['id']); ?>" tabindex="-1" aria-labelledby="invoiceModalLabel<?php echo htmlspecialchars($client['id']); ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="invoiceModalLabel<?php echo htmlspecialchars($client['id']); ?>">
                        Create Invoice
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- ============================================= -->
                    <!-- CLIENT INFORMATION SECTION -->
                    <!-- ============================================= -->
                    <div class="mb-4">
                        <p class="mb-1"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></p>
                        <p class="mb-0">ID: <?php echo htmlspecialchars($client['id']); ?></p>
                        <p class="mb-0">Control Box: <?php echo htmlspecialchars($inventory['control_box']); ?></p>
                        <p class="mb-0">Handset: <?php echo htmlspecialchars($inventory['handset']); ?></p>
                        <p class="mb-0">Account Balance Due as of Today <?php echo $today; ?>: $<?php echo number_format($rent_balance, 2); ?></p>
                        <p class="mb-0">Removal Date: <?php echo htmlspecialchars($removal_date); ?></p>
                    </div>

                    <!-- ============================================= -->
                    <!-- RENTAL SECTION -->
                    <!-- ============================================= -->
                    <div class="mb-4">
                        <h6>Rental Payment Due: $0.00</h6>
                    </div>

                    <!-- ============================================= -->
                    <!-- SERVICES SECTION -->
                    <!-- ============================================= -->
                    <div class="mb-4">
                        <h6>Services</h6>
                        <div id="servicesContainer<?php echo htmlspecialchars($client['id']); ?>">
                            <!-- Service rows will be added here dynamically -->
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addServiceRow(<?php echo htmlspecialchars($client['id']); ?>)">
                            <i class="feather icon-plus"></i> Add Service
                        </button>
                    </div>

                    <!-- ============================================= -->
                    <!-- TOTALS SECTION -->
                    <!-- ============================================= -->
                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Services Subtotal:</span>
                            <span id="servicesSubtotal<?php echo htmlspecialchars($client['id']); ?>">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Sales Tax (6.625%):</span>
                            <span id="salesTax<?php echo htmlspecialchars($client['id']); ?>">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Invoice Total:</span>
                            <span id="invoiceTotal<?php echo htmlspecialchars($client['id']); ?>">$0.00</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveInvoice(<?php echo htmlspecialchars($client['id']); ?>)">
                        Save Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================= -->
    <!-- INVOICE MODAL SCRIPTS -->
    <!-- ============================================= -->
    <script>
    /**
     * =============================================
     * SERVICE ROW TEMPLATE
     * =============================================
     * Creates a new row for service selection
     */
    function createServiceRow(clientId) {
        const row = document.createElement('div');
        row.className = 'row mb-2 align-items-center';
        row.innerHTML = `
            <div class="col-md-8">
                <select class="form-select service-select" onchange="updateServicePrice(this, ${clientId})">
                    <option value="">Select Service</option>
                    <?php
                    // Fetch services from database
                    $services_query = "SELECT id, name, price FROM services WHERE is_active = 1 ORDER BY name";
                    $services_stmt = $pdo->prepare($services_query);
                    $services_stmt->execute();
                    while ($service = $services_stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="' . htmlspecialchars($service['id']) . '" data-price="' . htmlspecialchars($service['price']) . '">' . 
                             htmlspecialchars($service['name']) . ' - $' . number_format($service['price'], 2) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="text" class="form-control service-price" readonly>
                </div>
            </div>
            <div class="col-12 mt-2">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeServiceRow(this)">
                    <i class="feather icon-trash-2"></i> Remove
                </button>
            </div>
        `;
        return row;
    }

    /**
     * =============================================
     * ADD SERVICE ROW
     * =============================================
     * Adds a new service selection row
     */
    function addServiceRow(clientId) {
        const container = document.getElementById('servicesContainer' + clientId);
        container.appendChild(createServiceRow(clientId));
    }

    /**
     * =============================================
     * REMOVE SERVICE ROW
     * =============================================
     * Removes a service selection row
     */
    function removeServiceRow(button) {
        button.closest('.row').remove();
        updateTotals(button.closest('.modal').querySelector('[id^="invoiceModal"]').id.replace('invoiceModal', ''));
    }

    /**
     * =============================================
     * UPDATE SERVICE PRICE
     * =============================================
     * Updates the price display when a service is selected
     */
    function updateServicePrice(select, clientId) {
        const option = select.options[select.selectedIndex];
        const priceInput = select.closest('.row').querySelector('.service-price');
        priceInput.value = option.dataset.price || '0.00';
        updateTotals(clientId);
    }

    /**
     * =============================================
     * UPDATE TOTALS
     * =============================================
     * Calculates and updates all totals
     */
    function updateTotals(clientId) {
        const modal = document.getElementById('invoiceModal' + clientId);
        let servicesTotal = 0;
        
        // Sum up all service prices
        modal.querySelectorAll('.service-price').forEach(input => {
            servicesTotal += parseFloat(input.value) || 0;
        });

        // Calculate tax
        const tax = servicesTotal * 0.06625;
        const total = servicesTotal + tax;

        // Update displays
        modal.querySelector('#servicesSubtotal' + clientId).textContent = '$' + servicesTotal.toFixed(2);
        modal.querySelector('#salesTax' + clientId).textContent = '$' + tax.toFixed(2);
        modal.querySelector('#invoiceTotal' + clientId).textContent = '$' + total.toFixed(2);
    }

    /**
     * =============================================
     * SAVE INVOICE
     * =============================================
     * Handles saving the invoice
     */
    function saveInvoice(clientId) {
        // TODO: Implement invoice saving
        alert('Invoice saving functionality to be implemented');
    }

    // Add initial service row when modal opens
    document.getElementById('invoiceModal<?php echo htmlspecialchars($client['id']); ?>').addEventListener('show.bs.modal', function() {
        const container = document.getElementById('servicesContainer<?php echo htmlspecialchars($client['id']); ?>');
        container.innerHTML = ''; // Clear existing rows
        addServiceRow(<?php echo htmlspecialchars($client['id']); ?>);
    });
    </script>
    <?php
}
?>
