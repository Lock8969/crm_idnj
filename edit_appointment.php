<?php
require_once 'auth_check.php';
include 'db.php';

// Get the appointment ID from the URL
$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Log the appointment ID
error_log("Edit Appointment - Appointment ID: " . $appointment_id);
echo "<script>console.log('Edit Appointment - ID from URL:', " . $appointment_id . ");</script>";

if (!$appointment_id) {
    die("Invalid appointment ID");
}

try {
    // Get appointment details
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
    $stmt->execute([$appointment_id]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        die("Appointment not found");
    }

    // Log the appointment details
    error_log("Edit Appointment - Appointment Details: " . print_r($appointment, true));
    echo "<script>console.log('Edit Appointment - Appointment Details:', " . json_encode($appointment) . ");</script>";

    // Get client information
    $stmt = $pdo->prepare("SELECT * FROM client_information WHERE id = ?");
    $stmt->execute([$appointment['customer_id']]);
    $client = $stmt->fetch();

    // Get inventory information
    $stmt = $pdo->prepare("SELECT * FROM vehicle_information WHERE customer_id = ?");
    $stmt->execute([$appointment['customer_id']]);
    $inventory = $stmt->fetch();

    // Get locations for dropdown
    $stmt = $pdo->query("SELECT id, location_name FROM locations ORDER BY location_name");
    $locations = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while fetching data");
}
?>

<!-- ============================================= -->
<!-- Edit Appointment Modal -->
<!-- ============================================= -->
<div class="modal fade" id="editAppointmentModal<?php echo htmlspecialchars($appointment_id); ?>" tabindex="-1" aria-labelledby="editAppointmentModalLabel<?php echo htmlspecialchars($appointment_id); ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAppointmentModalLabel<?php echo htmlspecialchars($appointment_id); ?>">
                    Edit Appointment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Client Info Section -->
                <div class="mb-4">
                    <p class="mb-1"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></p>
                    <p class="mb-0">ID: <?php echo htmlspecialchars($client['id']); ?></p>
                    <p class="mb-0">Install Date: <?php echo htmlspecialchars(date('m/d/Y', strtotime($client['install_on']))); ?></p>
                    <p class="mb-0">Control Box: <?php echo htmlspecialchars($inventory['control_box'] ?? 'N/A'); ?></p>
                    <p class="mb-0">Handset: <?php echo htmlspecialchars($inventory['handset'] ?? 'N/A'); ?></p>
                </div>

                <!-- Appointment Form -->
                <form id="editAppointmentForm<?php echo htmlspecialchars($appointment_id); ?>">
                    <!-- Location Selection -->
                    <div class="mb-3">
                        <label for="appointmentLocation<?php echo htmlspecialchars($appointment_id); ?>" class="form-label">Location</label>
                        <select class="form-select" id="appointmentLocation<?php echo htmlspecialchars($appointment_id); ?>" required>
                            <option value="">Select Location</option>
                            <?php
                            foreach ($locations as $location) {
                                $selected = $location['id'] == $appointment['location_id'] ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($location['id']) . '" ' . $selected . '>' . 
                                     htmlspecialchars($location['location_name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Appointment Date/Time -->
                    <div class="mb-3">
                        <label for="appointmentDate<?php echo htmlspecialchars($appointment_id); ?>" class="form-label">Appointment Date</label>
                        <input type="date" class="form-control" id="appointmentDate<?php echo htmlspecialchars($appointment_id); ?>" 
                               value="<?php echo date('Y-m-d', strtotime($appointment['start_time'])); ?>" required>
                    </div>

                    <!-- Appointment Type -->
                    <div class="mb-3">
                        <label for="appointmentType<?php echo htmlspecialchars($appointment_id); ?>" class="form-label">Appointment Type</label>
                        <select class="form-select" id="appointmentType<?php echo htmlspecialchars($appointment_id); ?>" required>
                            <option value="">Select Type</option>
                            <option value="Recalibration" <?php echo $appointment['appointment_type'] == 'Recalibration' ? 'selected' : ''; ?>>Recalibration (15 min)</option>
                            <option value="Removal" <?php echo $appointment['appointment_type'] == 'Removal' ? 'selected' : ''; ?>>Removal (30 min)</option>
                            <option value="Final_download" <?php echo $appointment['appointment_type'] == 'Final_download' ? 'selected' : ''; ?>>Final Download (15 min)</option>
                            <option value="Service" <?php echo $appointment['appointment_type'] == 'Service' ? 'selected' : ''; ?>>Service (30 min)</option>
                            <option value="Paper_Swap" <?php echo $appointment['appointment_type'] == 'Paper_Swap' ? 'selected' : ''; ?>>Paper Swap (15 min)</option>
                            <option value="Other" <?php echo !in_array($appointment['appointment_type'], ['Recalibration', 'Removal', 'Final_download', 'Service', 'Paper_Swap']) ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <!-- Duration dropdown for Other appointments -->
                    <div class="mb-3" id="otherDurationContainer<?php echo htmlspecialchars($appointment_id); ?>" style="display: none;">
                        <label for="otherDuration<?php echo htmlspecialchars($appointment_id); ?>" class="form-label">Appointment Duration</label>
                        <select class="form-select" id="otherDuration<?php echo htmlspecialchars($appointment_id); ?>">
                            <option value="">Select Duration</option>
                            <option value="15">15 minutes</option>
                            <option value="30">30 minutes</option>
                            <option value="45">45 minutes</option>
                            <option value="60">60 minutes</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="appointmentTime<?php echo htmlspecialchars($appointment_id); ?>" class="form-label">Appointment Time</label>
                        <select class="form-select" id="appointmentTime<?php echo htmlspecialchars($appointment_id); ?>" required>
                            <option value="">Select Time</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="serviceNotes<?php echo htmlspecialchars($appointment_id); ?>" class="form-label">Notes</label>
                        <textarea class="form-control" id="serviceNotes<?php echo htmlspecialchars($appointment_id); ?>" rows="3"><?php echo htmlspecialchars($appointment['service_note'] ?? ''); ?></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="deleteAppointment(<?php echo htmlspecialchars($appointment_id); ?>)">
                    <i class="bi bi-trash me-1"></i>Delete
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="window.location.reload()">Cancel</button>
                <button type="button" class="btn btn-info" id="getTimesBtn<?php echo $appointment_id; ?>">
                    <i class="bi bi-clock me-1"></i>Get Times
                </button>
                <button type="button" class="btn btn-success d-none" id="updateAppointmentBtn<?php echo $appointment_id; ?>">
                    <i class="bi bi-check-circle me-1"></i>Update Appointment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- MODAL SCRIPTS -->
<!-- ============================================= -->
<script>
// =============================================
// MODAL INITIALIZATION AND EVENT HANDLERS
// =============================================
document.getElementById('editAppointmentModal<?php echo htmlspecialchars($appointment_id); ?>').addEventListener('show.bs.modal', function() {
    console.log('Edit Appointment Modal opened');
    
    // Initialize appointment type change handler
    const typeSelect = document.getElementById('appointmentType<?php echo htmlspecialchars($appointment_id); ?>');
    const otherDurationContainer = document.getElementById('otherDurationContainer<?php echo htmlspecialchars($appointment_id); ?>');
    
    typeSelect.addEventListener('change', function() {
        const isOther = this.value === 'Other';
        otherDurationContainer.style.display = isOther ? 'block' : 'none';
    });
    
    // Initialize get times button
    const getTimesBtn = document.getElementById('getTimesBtn<?php echo $appointment_id; ?>');
    console.log('Get Times Button:', getTimesBtn);
    
    getTimesBtn.addEventListener('click', function() {
        console.log('Get Times button clicked');
        getAvailableTimesForEdit();
    });
    
    // Initialize update button
    const updateBtn = document.getElementById('updateAppointmentBtn<?php echo $appointment_id; ?>');
    updateBtn.addEventListener('click', function() {
        updateEditAppointment();
    });
});

// =============================================
// GET AVAILABLE TIMES FUNCTION
// =============================================
function getAvailableTimesForEdit() {
    console.log('getAvailableTimesForEdit function called');
    
    const appointmentId = <?php echo $appointment_id; ?>;
    console.log('Found appointment ID:', appointmentId);
    
    const date = document.getElementById('appointmentDate<?php echo $appointment_id; ?>').value;
    const location = document.getElementById('appointmentLocation<?php echo $appointment_id; ?>').value;
    const type = document.getElementById('appointmentType<?php echo $appointment_id; ?>').value;
    const duration = type === 'Other' ? document.getElementById('otherDuration<?php echo $appointment_id; ?>').value : 
                    type === 'Removal' || type === 'Service' ? '30' : '15';

    console.log('Form values:', {
        date: date,
        location: location,
        type: type,
        duration: duration
    });

    // Prepare the data to send
    const formData = {
        location_id: location,
        date: date,
        type: type,
        duration: duration
    };

    // Log the data being sent
    console.log('Sending form data to get_available_slots.php:', JSON.stringify(formData, null, 2));

    // Make the AJAX call
    console.log('Making fetch call to get_available_slots.php');
    fetch('get_available_slots.php?' + new URLSearchParams(formData))
        .then(response => {
            console.log('Response received:', response);
            return response.json();
        })
        .then(data => {
            console.log('Response from get_available_slots.php:', JSON.stringify(data, null, 2));
            
            if (data.success && data.data) {
                // 1. Update the time slots dropdown
                const timeSelect = document.getElementById('appointmentTime<?php echo $appointment_id; ?>');
                timeSelect.innerHTML = '<option value="">Select Time</option>';
                
                data.data.forEach(time => {
                    const option = document.createElement('option');
                    option.value = time;
                    option.textContent = time;
                    timeSelect.appendChild(option);
                });
                
                // 2. Replace Get Times button with Update Appointment button
                const getTimesBtn = document.getElementById('getTimesBtn<?php echo $appointment_id; ?>');
                if (getTimesBtn) {
                    getTimesBtn.classList.add('d-none');
                    const updateBtn = document.getElementById('updateAppointmentBtn<?php echo $appointment_id; ?>');
                    updateBtn.classList.remove('d-none');
                }

                // 3. Make location, date, and type fields read-only and change their appearance
                const fieldsToDisable = [
                    'appointmentLocation<?php echo $appointment_id; ?>',
                    'appointmentDate<?php echo $appointment_id; ?>',
                    'appointmentType<?php echo $appointment_id; ?>'
                ];

                fieldsToDisable.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        field.disabled = true;
                        field.classList.add('bg-light');
                        // If it's a select element, also style the selected option
                        if (field.tagName === 'SELECT') {
                            const selectedOption = field.options[field.selectedIndex];
                            if (selectedOption) {
                                selectedOption.style.backgroundColor = '#f8f9fa';
                            }
                        }
                    }
                });

                // Also disable the duration dropdown if it's visible
                const durationField = document.getElementById('otherDuration<?php echo $appointment_id; ?>');
                if (durationField && durationField.style.display !== 'none') {
                    durationField.disabled = true;
                    durationField.classList.add('bg-light');
                }
            } else {
                console.error('No available times returned');
                alert('No available times found for the selected criteria');
            }
        })
        .catch(error => {
            console.error('Error fetching available slots:', error);
            alert('Error fetching available time slots. Please try again.');
        });
}

// =============================================
// UPDATE APPOINTMENT FUNCTION
// =============================================
function updateEditAppointment() {
    console.log('updateEditAppointment function called');
    
    try {
        // Get the selected time
        const time = document.getElementById('appointmentTime<?php echo $appointment_id; ?>').value;
        console.log('Selected time:', time);
        
        if (!time) {
            alert('Please select a time slot');
            return;
        }

        // Prepare the update data
        const updateData = {
            appointment_id: <?php echo $appointment_id; ?>,
            user_id: <?php echo $_SESSION['user_id'] ?? 0; ?>,
            location_id: document.getElementById('appointmentLocation<?php echo $appointment_id; ?>').value,
            date: document.getElementById('appointmentDate<?php echo $appointment_id; ?>').value,
            type: document.getElementById('appointmentType<?php echo $appointment_id; ?>').value,
            time: time
        };

        console.log('Sending update data:', updateData);

        // Send to update_appointment_api.php
        fetch('update_appointment_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(updateData)
        })
        .then(response => {
            console.log('Update response received:', response);
            return response.json();
        })
        .then(data => {
            console.log('Update response data:', data);
            if (data.success) {
                alert('Appointment updated successfully');
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error updating appointment:', error);
            alert('Error updating appointment');
        });
    } catch (error) {
        console.error('Error in updateEditAppointment function:', error);
        alert('An error occurred while updating the appointment');
    }
}

// =============================================
// DELETE APPOINTMENT FUNCTION
// =============================================
function deleteAppointment(appointmentId) {
    if (confirm('Are you sure you want to delete this appointment?')) {
        // TODO: Implement appointment deletion
        console.log('Deleting appointment:', appointmentId);
    }
}
</script>
