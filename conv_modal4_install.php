<?php
if (!defined('INCLUDED_IN_SCRIPT')) {
    die('No direct script access allowed');
}

function renderInstallAppointmentModal($lead) {
?>
<!-- Installation Appointment Modal -->
<div class="modal fade" id="installAppointmentModal<?php echo htmlspecialchars($lead['id']); ?>" tabindex="-1" aria-labelledby="installAppointmentModalLabel<?php echo htmlspecialchars($lead['id']); ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="installAppointmentModalLabel<?php echo htmlspecialchars($lead['id']); ?>">Schedule Installation Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="installAppointmentForm<?php echo htmlspecialchars($lead['id']); ?>">
                    <input type="hidden" name="lead_id" value="<?php echo htmlspecialchars($lead['id']); ?>">
                    
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="installDate<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Installation Date</label>
                                <input type="date" class="form-control" id="installDate<?php echo htmlspecialchars($lead['id']); ?>" 
                                       name="install_date" tabindex="1">
                            </div>

                            <div class="mb-3">
                                <label for="installTime<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Installation Time</label>
                                <select class="form-select" id="installTime<?php echo htmlspecialchars($lead['id']); ?>" 
                                       name="install_time" tabindex="2">
                                    <option value="">Select Time</option>
                                    <?php
                                    // Generate time options from 7:00 AM to 8:30 PM in 30-minute intervals
                                    $start = strtotime('7:00 AM');
                                    $end = strtotime('8:30 PM');
                                    $interval = 30 * 60; // 30 minutes in seconds
                                    
                                    for ($time = $start; $time <= $end; $time += $interval) {
                                        $time_str = date('g:i A', $time);
                                        echo "<option value=\"" . $time_str . "\">" . $time_str . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="installLocation<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Installation Location</label>
                                <select class="form-select" id="installLocation<?php echo htmlspecialchars($lead['id']); ?>" 
                                       name="install_location" tabindex="3">
                                    <option value="">Select Location</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="installLength<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Installation Length</label>
                                <select class="form-select" id="installLength<?php echo htmlspecialchars($lead['id']); ?>" 
                                       name="install_length" tabindex="4">
                                    <option value="">Select Length</option>
                                    <option value="90">90 minutes</option>
                                    <option value="120">120 minutes</option>
                                </select>
                            </div>

                            <!-- Hidden inputs for start and end times -->
                            <input type="hidden" id="startTime<?php echo htmlspecialchars($lead['id']); ?>" name="start_time">
                            <input type="hidden" id="endTime<?php echo htmlspecialchars($lead['id']); ?>" name="end_time">
                            
                            <!-- End time display -->
                            <div class="mb-3">
                                <label class="form-label">Estimated End Time</label>
                                <div class="form-control" id="endTimeDisplay<?php echo htmlspecialchars($lead['id']); ?>">
                                    Select date, time, and length to see end time
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="saveInstallAppointment<?php echo htmlspecialchars($lead['id']); ?>">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            Save Appointment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Main Modal Script -->
<script>
// =============================================
// MAIN MODAL SCRIPT START
// Handles:
// - Modal opening and data retrieval
// - Location dropdown population
// - Save button functionality
// - Form submission to server
// =============================================

document.addEventListener('DOMContentLoaded', function() {
    const leadId = '<?php echo htmlspecialchars($lead['id']); ?>';
    const installModal = document.getElementById('installAppointmentModal' + leadId);
    const locationSelect = document.getElementById('installLocation' + leadId);
    
    // Get stored lead data when modal opens
    installModal.addEventListener('show.bs.modal', function (event) {
        console.log('\n\n=== Install Appointment Modal Opening ===');
        console.log('Event triggered:', event.type);
        console.log('Lead ID:', leadId);
        console.log('Modal Element:', installModal);
        
        // Get lead-specific data from previous modal
        const storageKey = 'leadConversionData_' + leadId;
        console.log('Storage Key:', storageKey);
        
        const storedData = sessionStorage.getItem(storageKey);
        console.log('Raw Stored Data:', storedData);
        
        if (storedData) {
            try {
                const leadData = JSON.parse(storedData);
                console.log('\n=== Lead Conversion Data from Previous Modals ===');
                console.log('Client Information:');
                console.log('- First Name:', leadData.first_name);
                console.log('- Last Name:', leadData.last_name);
                console.log('- Email:', leadData.email);
                console.log('- Phone:', leadData.phone_number);
                console.log('- Client ID:', leadData.client_id);
                
                console.log('\nPayment Information:');
                console.log('- Transaction ID:', leadData.transaction?.id);
                console.log('- Amount:', leadData.transaction?.amount);
                console.log('- Status:', leadData.transaction?.status);
                console.log('- Timestamp:', leadData.transaction?.timestamp);
                
                console.log('\nVehicle Information:');
                console.log('- Year:', leadData.year_id);
                console.log('- Make:', leadData.make_id);
                console.log('- Model:', leadData.model_id);
                console.log('- Hybrid:', leadData.hybrid);
                console.log('- Start System:', leadData.start_system);
                console.log('- Start/Stop:', leadData.start_stop);
                
                console.log('=== Complete Lead Data Object ===');
                console.log(JSON.stringify(leadData, null, 2));
                console.log('===============================================\n\n');
            } catch (error) {
                console.error('Error parsing stored data:', error);
            }
        } else {
            console.log('No stored data found for lead ' + leadId);
            console.log('All sessionStorage keys:', Object.keys(sessionStorage));
        }

        // Fetch locations
        console.log('About to fetch locations...');
        fetch('get_locations.php')
            .then(response => response.json())
            .then(data => {
                console.log('Locations data received:', data);
                if (data.success) {
                    // Clear existing options except the first one
                    while (locationSelect.options.length > 1) {
                        locationSelect.remove(1);
                    }
                    
                    // Add new options
                    data.locations.forEach(location => {
                        const option = new Option(location.location_name, location.id);
                        locationSelect.add(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error fetching locations:', error);
            });
    });

    // Handle save button click
    document.getElementById('saveInstallAppointment' + leadId).addEventListener('click', function() {
        const saveButton = this;
        const spinner = saveButton.querySelector('.spinner-border');
        
        // Show spinner, disable button
        spinner.classList.remove('d-none');
        saveButton.disabled = true;

        // Get form data
        const formData = new FormData(document.getElementById('installAppointmentForm' + leadId));
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        // Get lead data from storage
        const storageKey = 'leadConversionData_' + leadId;
        const storedData = sessionStorage.getItem(storageKey);
        if (storedData) {
            const leadData = JSON.parse(storedData);
            
            // =============================================
            // APPOINTMENT TYPE MAPPING
            // =============================================
            // Convert install duration to correct appointment type
            // Example: 90 minutes -> install90, 120 minutes -> install120
            const installLength = data.install_length;
            console.log('Install Length:', installLength); // Debug log
            const installType = installLength === '90' ? 'install90' : 'install120';
            console.log('Mapped Install Type:', installType); // Debug log
            
            // =============================================
            // DATA PREPARATION
            // =============================================
            // Format appointment data for API
            // Note: end_time is no longer needed as it's calculated by the service
            const appointmentData = {
                customer_id: leadData.client_id,
                title: 'Install',
                appointment_type: installType,
                start_time: data.start_time,
                location_id: data.install_location,
                description: `${leadData.client_id} ${leadData.first_name} ${leadData.last_name}<br>
Price Code: ${leadData.pricing_code}<br>
${leadData.law_type}<br>`
            };

            console.log('Sending to API:', JSON.stringify(appointmentData, null, 2));

            // =============================================
            // API CALL
            // =============================================
            // Send data to appointment API
            fetch('appointment_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(appointmentData)
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json().then(data => {
                    console.log('Response data:', data);
                    return data;
                });
            })
            .then(result => {
                if (result.success) {
                    alert('Installation appointment saved successfully!');
                    // Close the modal
                    const modal = bootstrap.Modal.getInstance(installModal);
                    modal.hide();
                } else {
                    throw new Error(result.message || 'Failed to save installation appointment');
                }
            })
            .catch(error => {
                console.error('Error saving installation appointment:', error);
                console.error('Full error details:', error.message);
                alert('Failed to save installation appointment. Please try again.');
                // Re-enable save button and hide spinner
                saveButton.disabled = false;
                spinner.classList.add('d-none');
            });
        } else {
            console.error('No lead data found in storage');
            alert('Error: No lead data found. Please try again.');
            saveButton.disabled = false;
            spinner.classList.add('d-none');
        }
    });
});
</script>

<!-- Time Calculation Script -->
<script>
// =============================================
// TIME CALCULATION SCRIPT START
// Handles:
// - Date and time selection
// - Installation length calculation
// - End time display
// - Hidden input updates for API submission
// =============================================

document.addEventListener('DOMContentLoaded', function() {
    const leadId = '<?php echo htmlspecialchars($lead['id']); ?>';
    const dateInput = document.getElementById('installDate' + leadId);
    const timeSelect = document.getElementById('installTime' + leadId);
    const lengthSelect = document.getElementById('installLength' + leadId);
    
    // Function to calculate end time
    function calculateEndTime() {
        const date = dateInput.value;
        const time = timeSelect.value;
        const length = parseInt(lengthSelect.value) || 0;
        
        console.log('Date:', date);
        console.log('Time:', time);
        console.log('Length:', length);
        
        if (date && time && length) {
            // Convert 12-hour time to 24-hour format
            const [timeStr, period] = time.split(' ');
            let [hours, minutes] = timeStr.split(':');
            hours = parseInt(hours);
            if (period === 'PM' && hours !== 12) hours += 12;
            if (period === 'AM' && hours === 12) hours = 0;
            const time24 = `${hours.toString().padStart(2, '0')}:${minutes}`;
            
            console.log('Combined date-time string:', date + 'T' + time24);
            // Create start datetime
            const startDateTime = new Date(date + 'T' + time24);
            
            // Add the duration in minutes
            const endDateTime = new Date(startDateTime.getTime() + (length * 60000));
            
            // Format the end time for display
            const endTimeStr = endDateTime.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            // Update the hidden input with the full datetime
            const startTimeInput = document.getElementById('startTime' + leadId);
            const endTimeInput = document.getElementById('endTime' + leadId);
            
            if (startTimeInput && endTimeInput) {
                startTimeInput.value = startDateTime.toISOString().slice(0, 19).replace('T', ' ');
                endTimeInput.value = endDateTime.toISOString().slice(0, 19).replace('T', ' ');
            }
            
            // Show the end time to the user
            const endTimeDisplay = document.getElementById('endTimeDisplay' + leadId);
            if (endTimeDisplay) {
                endTimeDisplay.textContent = endTimeStr;
            }
        }
    }
    
    // Add event listeners for changes
    dateInput.addEventListener('change', calculateEndTime);
    timeSelect.addEventListener('change', calculateEndTime);
    lengthSelect.addEventListener('change', calculateEndTime);
});

// =============================================
// TIME CALCULATION SCRIPT END
// =============================================
</script>

<?php
}
?> 