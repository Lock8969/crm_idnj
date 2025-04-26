<?php
/**
 * =============================================
 * INVOICE APPOINTMENT MODEL
 * =============================================
 * 
 * This file handles all appointment-related functionality
 * for the invoice flow. It contains the appointment modal
 * and all related JavaScript functions.
 */

// =============================================
// REQUIRED FILES
// =============================================
include_once 'get_locations.php';

// =============================================
// DATABASE QUERY FUNCTIONS
// =============================================

/**
 * Gets client data needed for appointment flow
 * Returns: removal_date and calibration_interval from client_information table
 */
function getClientAppointmentData($client_id, $pdo) {
    try {
        $query = "SELECT removal_date, calibration_interval, install_on FROM client_information WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$client_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching client appointment data: " . $e->getMessage());
        return false;
    }
}

/**
 * Gets client inventory information
 * Returns: handset and control box numbers
 */
function getClientInventory($client_id, $pdo) {
    try {
        // Get handset from hs_inventory
        $hs_query = "SELECT serial_number FROM hs_inventory WHERE customer_id = ? ORDER BY id DESC LIMIT 1";
        $hs_stmt = $pdo->prepare($hs_query);
        $hs_stmt->execute([$client_id]);
        $hs_result = $hs_stmt->fetch(PDO::FETCH_ASSOC);
        $handset = $hs_result ? $hs_result['serial_number'] : 'Not Assigned';
        
        // Get control box from cb_inventory
        $cb_query = "SELECT serial_number FROM cb_inventory WHERE customer_id = ? ORDER BY id DESC LIMIT 1";
        $cb_stmt = $pdo->prepare($cb_query);
        $cb_stmt->execute([$client_id]);
        $cb_result = $cb_stmt->fetch(PDO::FETCH_ASSOC);
        $control_box = $cb_result ? $cb_result['serial_number'] : 'Not Assigned';
        
        return [
            'handset' => $handset,
            'control_box' => $control_box
        ];
    } catch (PDOException $e) {
        error_log("Error fetching client inventory: " . $e->getMessage());
        return [
            'handset' => 'Error Loading',
            'control_box' => 'Error Loading'
        ];
    }
}

/**
 * Gets complete client information
 * Returns: all relevant client data from client_information table
 */
function getClientInformation($client_id, $pdo) {
    try {
        $query = "SELECT removal_date, calibration_interval, install_on FROM client_information WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$client_id]);
        $client_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$client_data) {
            return [
                'install_on' => 'Not Installed',
                'removal_date' => 'Missing',
                'calibration_interval' => 'Not Set'
            ];
        }
        
        return [
            'install_on' => $client_data['install_on'] ? date('m/d/Y', strtotime($client_data['install_on'])) : 'Not Installed',
            'removal_date' => $client_data['removal_date'] ? date('m/d/Y', strtotime($client_data['removal_date'])) : 'Not Set',
            'calibration_interval' => $client_data['calibration_interval'] ? $client_data['calibration_interval'] : 'Not Set'
        ];
    } catch (PDOException $e) {
        error_log("Error fetching client information: " . $e->getMessage());
        return [
            'install_on' => 'Error Loading',
            'removal_date' => 'Error Loading',
            'calibration_interval' => 'Error Loading'
        ];
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
 * Creates the Next Appointment Modal
 * Shows: Client info, removal date, next appointment
 * Allows: Setting new appointment or marking as no appointment needed
 */
function renderNextAppointmentModal($client, $pdo) {
    // Fetch additional client data needed for this modal
    $client_data = getClientAppointmentData($client['id'], $pdo);
    $client_info = getClientInformation($client['id'], $pdo);
    $inventory = getClientInventory($client['id'], $pdo);
    
    $removal_date = $client_info['removal_date'];
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
                        <p class="mb-0">Install Date: <?php echo htmlspecialchars($client_info['install_on']); ?></p>
                        <p class="mb-0">Control Box: <?php echo htmlspecialchars($inventory['control_box']); ?></p>
                        <p class="mb-0">Handset: <?php echo htmlspecialchars($inventory['handset']); ?></p>
                        <p class="mb-0">Account Balance: <span id="accountBalance<?php echo htmlspecialchars($client['id']); ?>">Loading...</span></p>
                        <p class="mb-0">Removal Date: <?php echo $removal_date ? date('m/d/Y', strtotime($removal_date)) : 'Not Set'; ?></p>
                        <p class="mb-0" id="pendingAppointmentDisplay<?php echo htmlspecialchars($client['id']); ?>"></p>
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
                            <!-- Location Selection -->
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

                            <!-- Appointment Date/Time -->
                            <div class="mb-3">
                                <label for="appointmentDate<?php echo htmlspecialchars($client['id']); ?>" class="form-label">Appointment Date</label>
                                <div class="input-group">
                                    <input type="text" class="form-control flatpickr" id="appointmentDate<?php echo htmlspecialchars($client['id']); ?>" required>
                                    <span class="input-group-text"><i class="feather icon-calendar"></i></span>
                                </div>
                            </div>

                            <!-- Appointment Type -->
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
                    <button type="button" class="btn btn-primary" id="saveAppointmentBtn<?php echo htmlspecialchars($client['id']); ?>" onclick="saveAppointment(<?php echo htmlspecialchars($client['id']); ?>)">
                        Save Appointment
                    </button>
                    <button type="button" class="btn btn-success d-none" id="invoiceBtn<?php echo htmlspecialchars($client['id']); ?>" onclick="openInvoiceModal(<?php echo htmlspecialchars($client['id']); ?>)">
                        Invoice
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
            // Clear pending appointment data when "No Appointment Needed" is checked
            sessionStorage.removeItem('pendingAppointment');
            console.log('Cleared pending appointment data - No Appointment Needed checked');
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
     * Sets up listeners for location, date, and type changes
     * Triggers time slot fetch when all required fields are filled
     */
    document.getElementById('nextAppointmentModal<?php echo htmlspecialchars($client['id']); ?>').addEventListener('show.bs.modal', function() {
        // Fetch balance data when modal opens
        const today = new Date().toISOString().split('T')[0];
        fetch(`balance_calculator.php?customer_id=<?php echo htmlspecialchars($client['id']); ?>&date=${today}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const balanceSpan = document.getElementById('accountBalance<?php echo htmlspecialchars($client['id']); ?>');
                    if (data.data.today_balance < 0) {
                        balanceSpan.className = 'text-success';
                        balanceSpan.textContent = `Customer Credit: $${Math.abs(data.data.today_balance).toFixed(2)}`;
                    } else if (data.data.today_balance > 0) {
                        balanceSpan.className = 'text-danger';
                        balanceSpan.textContent = `Balance Owed: $${data.data.today_balance.toFixed(2)}`;
                    } else {
                        balanceSpan.className = 'text-success';
                        balanceSpan.textContent = '$0.00';
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching balance data:', error);
                const balanceSpan = document.getElementById('accountBalance<?php echo htmlspecialchars($client['id']); ?>');
                balanceSpan.textContent = 'Error loading balance';
            });

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
        // Move focus back to the trigger button
        const triggerButton = document.querySelector(`[data-bs-target="#nextAppointmentModal<?php echo htmlspecialchars($client['id']); ?>"]`);
        if (triggerButton) {
            triggerButton.focus();
        }
    });

    // Add click event listener for cancel button in appointment modal
    document.querySelector(`#nextAppointmentModal<?php echo htmlspecialchars($client['id']); ?> .btn-secondary`).addEventListener('click', function() {
        sessionStorage.removeItem('pendingAppointment');
        console.log('Cleared pending appointment data - Appointment modal cancel clicked');
        // Move focus back to the trigger button before closing
        const triggerButton = document.querySelector(`[data-bs-target="#nextAppointmentModal<?php echo htmlspecialchars($client['id']); ?>"]`);
        if (triggerButton) {
            triggerButton.focus();
        }
        window.location.reload();
    });
    </script>

    <!-- ============================================= -->
    <!-- TIME SLOTS FETCH SCRIPT -->
    <!-- ============================================= -->
    <script>
    /**
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
     * Validates form fields and prepares for saving
     * Makes API call to save appointment
     * Handles response and UI updates
     */
    function saveAppointment(clientId) {
        // Get all form data
        const noAppointmentChecked = document.getElementById('noAppointment' + clientId).checked;
        const appointmentFields = document.getElementById('appointmentFields' + clientId);
        const locationSelect = document.getElementById('appointmentLocation' + clientId);
        const dateInput = document.getElementById('appointmentDate' + clientId);
        const typeSelect = document.getElementById('appointmentType' + clientId);
        const timeSelect = document.getElementById('appointmentTime' + clientId);
        const otherDurationSelect = document.getElementById('otherDuration' + clientId);
        const otherDurationContainer = document.getElementById('otherDurationContainer' + clientId);
        
        // Validate either No Appointment is checked or all required fields are filled
        if (!noAppointmentChecked) {
            // Check if all required fields are filled
            if (!locationSelect.value || !dateInput.value || !typeSelect.value || !timeSelect.value) {
                alert('Please fill in all required fields or check "No Appointment Needed"');
                return;
            }
            
            // If appointment type is "Other", check duration
            if (typeSelect.value === 'Other' && (!otherDurationSelect || !otherDurationSelect.value)) {
                alert('Please select a duration for Other appointment type');
                return;
            }
        }

        const formData = {
            clientId: clientId,
            appointmentDate: dateInput.value,
            appointmentTime: timeSelect.value,
            appointmentType: typeSelect.value,
            appointmentNotes: document.getElementById('serviceNotes' + clientId).value,
            location_id: document.getElementById('appointmentLocation' + clientId).value,
            noAppointment: noAppointmentChecked
        };

        // Store in session storage
        sessionStorage.setItem('appointmentData_' + clientId, JSON.stringify(formData));

        // Log appointment data in readable format
        console.log('=== Appointment Data Saved ===');
        console.log('Client ID:', formData.clientId);
        console.log('Appointment Date:', formData.appointmentDate);
        console.log('Appointment Time:', formData.appointmentTime);
        console.log('Appointment Type:', formData.appointmentType);
        console.log('Appointment Notes:', formData.appointmentNotes);
        console.log('No Appointment Needed:', formData.noAppointment);
        console.log('===========================');

        // Hide Save Appointment button and show Invoice button
        document.getElementById('saveAppointmentBtn' + clientId).classList.add('d-none');
        document.getElementById('invoiceBtn' + clientId).classList.remove('d-none');
    }

    /**
     * Opens the invoice modal
     */
    function openInvoiceModal(clientId) {
        // Get appointment data from session storage
        const appointmentData = JSON.parse(sessionStorage.getItem('appointmentData_' + clientId));
        
        // Update pending appointment display
        const pendingAppointmentSpan = document.getElementById('pendingAppointmentDate' + clientId);
        if (appointmentData && !appointmentData.noAppointment) {
            // Create date object and ensure it's in local timezone
            const date = new Date(appointmentData.appointmentDate + 'T' + appointmentData.appointmentTime);
            
            // Format date as MM/DD/YYYY
            const formattedDate = (date.getMonth() + 1).toString().padStart(2, '0') + '/' + 
                                date.getDate().toString().padStart(2, '0') + '/' + 
                                date.getFullYear();
            
            // Format time to 12-hour format with AM/PM
            const hours = date.getHours();
            const minutes = date.getMinutes().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const formattedHours = (hours % 12 || 12).toString().padStart(2, '0');
            const formattedTime = `${formattedHours}:${minutes}${ampm}`;
            
            // Get location name from select element
            const locationSelect = document.getElementById('appointmentLocation' + clientId);
            const locationName = locationSelect.options[locationSelect.selectedIndex].text;
            
            // Get service type
            const serviceType = appointmentData.appointmentType;

            // Calculate days until appointment
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const appointmentDay = new Date(appointmentData.appointmentDate);
            appointmentDay.setHours(0, 0, 0, 0);
            const daysUntilAppointment = Math.floor((appointmentDay - today) / (1000 * 60 * 60 * 24)) + 1;
            
            pendingAppointmentSpan.innerHTML = `${formattedDate} ${formattedTime} - ${locationName} - ${serviceType}<br>${daysUntilAppointment} days from today`;
        } else {
            pendingAppointmentSpan.textContent = 'Not Set';
        }

        // Use appointment date if available, otherwise use today's date
        let appointmentDate;
        if (appointmentData && appointmentData.appointmentDate) {
            appointmentDate = appointmentData.appointmentDate;
        } else {
            const today = new Date();
            appointmentDate = today.toISOString().split('T')[0]; // Format: YYYY-MM-DD
        }

        // Format the date for display (MM/DD/YYYY)
        const [year, month, day] = appointmentDate.split('-');
        const formattedDate = `${month}/${day}/${year}`;

        // Update rent due label with formatted date
        document.getElementById('rentDueLabel' + clientId).textContent = `Rent Due (${formattedDate}):`;

        // Calculate rent balance (using original YYYY-MM-DD format for API)
        fetch(`balance_calculator.php?customer_id=${clientId}&date=${appointmentDate}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const rentBalance = data.data.rent_balance;
                    const todayBalance = data.data.today_balance;
                    
                    // Update rent due display
                    document.getElementById('rentDue' + clientId).textContent = rentBalance.toFixed(2);
                    
                    // Update account balance display with appropriate formatting
                    const balanceSpan = document.getElementById('accountBalance' + clientId);
                    const todayBalanceSpan = document.getElementById('accountBalanceToday' + clientId);
                    
                    // Format today's balance
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
                    
                    // Format appointment date balance
                    if (rentBalance < 0) {
                        balanceSpan.className = 'text-success';
                        balanceSpan.textContent = `Customer Credit: $${Math.abs(rentBalance).toFixed(2)}`;
                    } else if (rentBalance > 0) {
                        balanceSpan.className = 'text-danger';
                        balanceSpan.textContent = `Balance Owed: $${rentBalance.toFixed(2)}`;
                    } else {
                        balanceSpan.className = 'text-success';
                        balanceSpan.textContent = '$0.00';
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

        // Close the appointment modal
        const appointmentModal = bootstrap.Modal.getInstance(document.getElementById('nextAppointmentModal' + clientId));
        appointmentModal.hide();

        // Open the invoice modal
        const invoiceModal = new bootstrap.Modal(document.getElementById('invoiceModal' + clientId));
        invoiceModal.show();

        // Move focus to the first focusable element in the invoice modal
        setTimeout(() => {
            const firstFocusableElement = document.querySelector('#invoiceModal' + clientId + ' [tabindex="0"], #invoiceModal' + clientId + ' button, #invoiceModal' + clientId + ' input, #invoiceModal' + clientId + ' select');
            if (firstFocusableElement) {
                firstFocusableElement.focus();
            }
        }, 100);

        // Add initial service row
        addServiceRow(clientId);
    }

    // Add event listener to handle focus when appointment modal is hidden
    document.getElementById('nextAppointmentModal<?php echo htmlspecialchars($client['id']); ?>').addEventListener('hidden.bs.modal', function() {
        // Remove focus from any elements in the hidden modal
        const focusedElement = document.activeElement;
        if (focusedElement && this.contains(focusedElement)) {
            focusedElement.blur();
        }
    });
    </script>

    <!-- ============================================= -->
    <!-- INVOICE MODAL -->
    <!-- ============================================= -->
    <div class="modal fade" id="invoiceModal<?php echo htmlspecialchars($client['id']); ?>" tabindex="-1" aria-labelledby="invoiceModalLabel<?php echo htmlspecialchars($client['id']); ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 575px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="invoiceModalLabel<?php echo htmlspecialchars($client['id']); ?>">
                        Create Invoice
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Client Info Section -->
                    <div class="mb-4">
                        <p class="mb-1"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></p>
                        <p class="mb-0">ID: <?php echo htmlspecialchars($client['id']); ?></p>
                        <p class="mb-0">Install Date: <?php echo htmlspecialchars($client_info['install_on']); ?></p>
                        <p class="mb-0">Control Box: <?php echo htmlspecialchars($inventory['control_box']); ?></p>
                        <p class="mb-0">Handset: <?php echo htmlspecialchars($inventory['handset']); ?></p>
                        <p class="mb-0">Account Balance Today: <span id="accountBalanceToday<?php echo htmlspecialchars($client['id']); ?>" style="color: #dc3545;">Loading...</span></p>
                        <p class="mb-0">Removal Date: <?php echo $removal_date ? date('m/d/Y', strtotime($removal_date)) : 'Not Set'; ?></p>
                        <p class="mb-0">Appointment Date Pending: <span id="pendingAppointmentDate<?php echo htmlspecialchars($client['id']); ?>">Not Set</span></p>
                        <p class="mb-0" id="pendingAppointmentDisplay<?php echo htmlspecialchars($client['id']); ?>"></p>
                    </div>

                    <!-- Services Section -->
                    <div class="mb-4">
                        <h5 class="mb-3">Services</h5>
                        <div id="servicesContainer<?php echo htmlspecialchars($client['id']); ?>">
                            <!-- Service rows will be added here dynamically -->
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addServiceRow(<?php echo htmlspecialchars($client['id']); ?>)">
                            <i class="bi bi-plus"></i> Add Service
                        </button>
                    </div>

                    <!-- Totals Section -->
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-6 offset-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td id="rentDueLabel<?php echo htmlspecialchars($client['id']); ?>">Rent Due:</td>
                                        <td class="text-end">$<span id="rentDue<?php echo htmlspecialchars($client['id']); ?>">0.00</span></td>
                                    </tr>
                                    <tr>
                                        <td>Service Due:</td>
                                        <td class="text-end">$<span id="serviceDue<?php echo htmlspecialchars($client['id']); ?>">0.00</span></td>
                                    </tr>
                                    <tr>
                                        <td>Subtotal:</td>
                                        <td class="text-end">$<span id="subtotal<?php echo htmlspecialchars($client['id']); ?>">0.00</span></td>
                                    </tr>
                                    <tr>
                                        <td>Sales Tax (6.625%):</td>
                                        <td class="text-end">$<span id="tax<?php echo htmlspecialchars($client['id']); ?>">0.00</span></td>
                                    </tr>
                                    <tr class="fw-bold">
                                        <td>Total:</td>
                                        <td class="text-end">$<span id="total<?php echo htmlspecialchars($client['id']); ?>">0.00</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Section -->
                    <div class="mb-4">
                        <h5 class="mb-3">Payment</h5>
                        <div class="mb-3">
                            <label class="form-label">Total Due: $<span id="totalDue<?php echo htmlspecialchars($client['id']); ?>">0.00</span></label>
                        </div>
                        <div id="paymentMethodsContainer<?php echo htmlspecialchars($client['id']); ?>">
                            <!-- First payment method (always visible) -->
                            <div class="row mb-3">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="form-label">Amount Collected</label>
                                        <input type="number" class="form-control payment-amount" step="0.01" min="0" onchange="updateTotalDue(<?php echo htmlspecialchars($client['id']); ?>)">
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
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPaymentMethod(<?php echo htmlspecialchars($client['id']); ?>)">
                            <i class="bi bi-plus"></i> Add Method
                        </button>
                    </div>

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

                    function removePaymentMethod(button) {
                        const row = button.closest('.row');
                        const clientId = row.closest('.modal').id.replace('invoiceModal', '');
                        row.remove();
                        updateTotalDue(clientId);
                    }

                    function updateTotalDue(clientId) {
                        const total = parseFloat(document.getElementById('total' + clientId).textContent);
                        const paymentAmounts = document.querySelectorAll('#paymentMethodsContainer' + clientId + ' .payment-amount');
                        let totalPaid = 0;
                        
                        paymentAmounts.forEach(input => {
                            const amount = parseFloat(input.value) || 0;
                            totalPaid += amount;
                        });
                        
                        const remainingDue = total - totalPaid;
                        document.getElementById('totalDue' + clientId).textContent = remainingDue.toFixed(2);
                        
                        // Update color based on remaining amount
                        const totalDueSpan = document.getElementById('totalDue' + clientId);
                        if (remainingDue <= 0) {
                            totalDueSpan.style.color = '#198754'; // Green for paid in full
                        } else {
                            totalDueSpan.style.color = '#dc3545'; // Red for still owing
                        }

                        // Update the total collected display
                        const totalCollectedSpan = document.getElementById('totalCollected' + clientId);
                        if (totalCollectedSpan) {
                            totalCollectedSpan.textContent = totalPaid.toFixed(2);
                        }
                    }
                    </script>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="window.location.reload();">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveInvoice(<?php echo htmlspecialchars($client['id']); ?>)">
                        Save Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================= -->
    <!-- INVOICE SCRIPT -->
    <!-- ============================================= -->
    <script>
    // Only declare servicePrices if it hasn't been declared yet
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

    // Add a new service row
    function addServiceRow(clientId) {
        const container = document.getElementById('servicesContainer' + clientId);
        const row = document.createElement('div');
        row.className = 'row mb-2 align-items-center';
        row.innerHTML = `
            <div class="col-6">
                <select class="form-select service-select" onchange="updateServicePrice(this, ${clientId})">
                    <option value="">Select Service</option>
                    <?php
                    // Fetch services from database with custom ordering
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

    /**
     * Update service price when a service is selected
     */
    function updateServicePrice(select, clientId) {
        const option = select.options[select.selectedIndex];
        const priceInput = select.closest('.row').querySelector('.service-price');
        const price = option.dataset.price || '0.00';
        priceInput.value = price;
        updateTotals(clientId);
    }

    /**
     * Remove a service row
     */
    function removeServiceRow(button, clientId) {
        const row = button.closest('.row');
        row.remove();
        updateTotals(clientId);
    }

    // Update totals when services change
    function updateTotals(clientId) {
        let serviceDue = 0;
        let subtotal = 0;
        
        // Add rent due to subtotal
        const rentDue = parseFloat(document.getElementById('rentDue' + clientId).textContent) || 0;
        subtotal += rentDue;
        
        // Calculate service due
        const container = document.getElementById('servicesContainer' + clientId);
        const rows = container.children;
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const price = parseFloat(row.querySelector('input').value) || 0;
            serviceDue += price;
        }
        
        // Update service due display
        document.getElementById('serviceDue' + clientId).textContent = serviceDue.toFixed(2);
        
        // Add service due to subtotal
        subtotal += serviceDue;
        
        // Only apply tax to positive amounts
        const tax = subtotal > 0 ? subtotal * 0.06625 : 0;
        const total = subtotal + tax;
        
        document.getElementById('subtotal' + clientId).textContent = subtotal.toFixed(2);
        document.getElementById('tax' + clientId).textContent = tax.toFixed(2);
        document.getElementById('total' + clientId).textContent = total.toFixed(2);

        // Get payment amounts and methods
        const paymentAmounts = document.querySelectorAll('#paymentMethodsContainer' + clientId + ' .payment-amount');
        const paymentMethods = document.querySelectorAll('#paymentMethodsContainer' + clientId + ' .payment-method');
        let totalCollected = 0;
        let payments = [];

        // Calculate total collected and store payment methods
        paymentAmounts.forEach((input, index) => {
            const amount = parseFloat(input.value) || 0;
            if (amount > 0) {
                const paymentType = paymentMethods[index].value;
                totalCollected += amount;
                payments.push({
                    type: paymentType,
                    amount: amount
                });
            }
        });

        // Remove invoice data creation and storage
        // const invoiceData = {
        //     clientId: clientId,
        //     rentDue: rentDue,
        //     serviceDue: serviceDue,
        //     subtotal: subtotal,
        //     tax: tax,
        //     total: total,
        //     payments: payments,
        //     total_collected: totalCollected,
        //     rent_total: rentDue,
        //     service_total: serviceDue,
        //     sub_total: subtotal,
        //     tax_amount: tax,
        //     invoice_total: total
        // };
        // sessionStorage.setItem('invoiceData_' + clientId, JSON.stringify(invoiceData));

        // Remove console logs
        // console.log('=== Invoice Data Updated ===');
        // console.log('Client ID:', clientId);
        // console.log('\nAmounts:');
        // console.log(`  Rent Due: $${rentDue.toFixed(2)}`);
        // console.log(`  Service Due: $${serviceDue.toFixed(2)}`);
        // console.log(`  Subtotal: $${subtotal.toFixed(2)}`);
        // console.log(`  Tax: $${tax.toFixed(2)}`);
        // console.log(`  Total: $${total.toFixed(2)}`);
        // console.log('\nPayments:');
        // payments.forEach(payment => {
        //     console.log(`  ${payment.type}: $${payment.amount.toFixed(2)}`);
        // });
        // console.log(`  Total Collected: $${totalCollected.toFixed(2)}`);
        // console.log('===========================');
    }

    // Save invoice data
    function saveInvoice(clientId) {
        // Get all services
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

        // Get all payments - default to 0 if no amount entered
        const payments = {};
        const paymentAmounts = document.querySelectorAll('#paymentMethodsContainer' + clientId + ' .payment-amount');
        const paymentMethods = document.querySelectorAll('#paymentMethodsContainer' + clientId + ' .payment-method');
        
        let primaryPaymentType = 'credit_card'; // Default
        let primaryPaymentAmount = 0.00; // Default to 0.00
        
        paymentAmounts.forEach((input, index) => {
            const amount = parseFloat(input.value) || 0.00; // Default to 0.00 if empty
            const paymentType = paymentMethods[index].value;
            payments[paymentType] = amount;
            
            // Set the first payment as primary, even if amount is 0
            if (index === 0) {
                primaryPaymentType = paymentType;
                primaryPaymentAmount = amount;
            }
        });

        // Get totals
        const rentDue = parseFloat(document.getElementById('rentDue' + clientId).textContent) || 0;
        const serviceDue = parseFloat(document.getElementById('serviceDue' + clientId).textContent) || 0;
        const subtotal = parseFloat(document.getElementById('subtotal' + clientId).textContent) || 0;
        const tax = parseFloat(document.getElementById('tax' + clientId).textContent) || 0;
        const total = parseFloat(document.getElementById('total' + clientId).textContent) || 0;

        // Calculate total collected - will be 0.00 if no amounts entered
        const totalCollected = Object.values(payments).reduce((sum, amount) => sum + amount, 0);

        // Construct invoice data that satisfies both payment_api and PaymentService
        const invoiceData = {
            request_type: 'create_invoice_with_payment',
            customer_id: clientId,
            created_by: <?php echo $_SESSION['user_id']; ?>,
            location_id: <?php echo $_SESSION['location_id']; ?>,
            services: services,
            // Required by payment_api - will be 0.00 if no amount entered
            payment_type: primaryPaymentType,
            amount: primaryPaymentAmount,
            // Preferred by PaymentService
            payments: payments,
            rent_total: rentDue,
            service_total: serviceDue,
            sub_total: subtotal,
            tax_amount: tax,
            invoice_total: total,
            total_collected: totalCollected,
            remaining_balance: total - totalCollected
        };

        // Log the data being sent
        console.log('Sending invoice data:', JSON.stringify(invoiceData, null, 2));

        // Send data to server
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
                // Get appointment data from session storage
                const appointmentData = JSON.parse(sessionStorage.getItem('appointmentData_' + clientId));
                
                // If we have appointment data and it's not marked as "No Appointment Needed"
                if (appointmentData && !appointmentData.noAppointment) {
                    // Prepare appointment data for API
                    const appointmentApiData = {
                        customer_id: clientId,
                        title: appointmentData.appointmentType + ' Appointment',
                        appointment_type: appointmentData.appointmentType.toLowerCase(),
                        start_time: appointmentData.appointmentDate + ' ' + appointmentData.appointmentTime,
                        location_id: appointmentData.location_id,
                        service_note: appointmentData.appointmentNotes,
                        status: 'scheduled'
                    };

                    // Send appointment data to appointment API
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

    <!-- Add Flatpickr JS before other scripts -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <?php
}
?> 