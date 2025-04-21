<?php
/**
 * =============================================
 * EDIT APPOINTMENT MODAL
 * =============================================
 * 
 * This file handles the edit appointment modal
 * and pre-fills it with the selected appointment's data.
 */

// =============================================
// REQUIRED FILES
// =============================================
include_once 'get_locations.php';

// =============================================
// GET APPOINTMENT DATA
// =============================================
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    die("Invalid appointment ID");
}

$appointment_id = $_GET['id'];

try {
    // Get appointment details
    $query = "SELECT a.*, l.location_name, u.full_name as technician_name 
              FROM appointments a 
              LEFT JOIN locations l ON a.location_id = l.id 
              LEFT JOIN users u ON a.created_by = u.id 
              WHERE a.id = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$appointment_id]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        die("Appointment not found");
    }
    
    // Get client details
    $client_query = "SELECT * FROM client_information WHERE id = ?";
    $client_stmt = $pdo->prepare($client_query);
    $client_stmt->execute([$appointment['customer_id']]);
    $client = $client_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        die("Client not found");
    }
    
    // Get client inventory
    $inventory_query = "SELECT * FROM vehicle_information WHERE customer_id = ?";
    $inventory_stmt = $pdo->prepare($inventory_query);
    $inventory_stmt->execute([$appointment['customer_id']]);
    $inventory = $inventory_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching appointment data: " . $e->getMessage());
    die("Error fetching appointment data");
}
?>

<!-- ============================================= -->
<!-- Edit Appointment Modal -->
<!-- ============================================= -->
<div class="modal fade" id="editAppointmentModal<?php echo htmlspecialchars($appointment_id); ?>" tabindex="-1" aria-labelledby="editAppointmentModalLabel<?php echo htmlspecialchars($appointment_id); ?>" aria-hidden="true">
    <!-- Add Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
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
                    <p class="mb-0">Install Date: <?php echo htmlspecialchars(date('m/d/Y', strtotime($client['install_date']))); ?></p>
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
                            $locations = getLocations();
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
                        <div class="input-group">
                            <input type="text" class="form-control flatpickr" id="appointmentDate<?php echo htmlspecialchars($appointment_id); ?>" 
                                   value="<?php echo date('m/d/Y', strtotime($appointment['start_time'])); ?>" required>
                            <span class="input-group-text"><i class="feather icon-calendar"></i></span>
                        </div>
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
                <button type="button" class="btn btn-primary" id="saveAppointmentBtn<?php echo htmlspecialchars($appointment_id); ?>" onclick="saveAppointment(<?php echo htmlspecialchars($appointment_id); ?>)">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
// Initialize Flatpickr when modal opens
document.getElementById('editAppointmentModal<?php echo htmlspecialchars($appointment_id); ?>').addEventListener('show.bs.modal', function() {
    // Store appointment data in session storage
    const appointmentData = {
        id: <?php echo htmlspecialchars($appointment_id); ?>,
        client_id: <?php echo htmlspecialchars($appointment['customer_id']); ?>,
        location_id: <?php echo htmlspecialchars($appointment['location_id']); ?>,
        appointment_date: '<?php echo date('m/d/Y', strtotime($appointment['start_time'])); ?>',
        appointment_time: '<?php echo date('H:i', strtotime($appointment['start_time'])); ?>',
        appointment_type: '<?php echo htmlspecialchars($appointment['appointment_type']); ?>',
        service_note: '<?php echo htmlspecialchars($appointment['service_note'] ?? ''); ?>',
        user_id: <?php echo htmlspecialchars($_SESSION['user_id']); ?>,
        current_location_id: <?php echo htmlspecialchars($_SESSION['location_id']); ?>
    };
    sessionStorage.setItem('currentAppointment', JSON.stringify(appointmentData));

    const dateInput = document.getElementById('appointmentDate<?php echo htmlspecialchars($appointment_id); ?>');
    const flatpickrConfig = {
        dateFormat: "m/d/Y",
        minDate: "today",
        disableMobile: "true",
        allowInput: true,
        altInput: true,
        altFormat: "m/d/Y",
        width: "100%",
        calendarWidth: "300px",
        position: "below"
    };
    
    flatpickr(dateInput, flatpickrConfig);
    
    // Set initial time
    const timeSelect = document.getElementById('appointmentTime<?php echo htmlspecialchars($appointment_id); ?>');
    const appointmentTime = '<?php echo date('H:i', strtotime($appointment['start_time'])); ?>';
    timeSelect.innerHTML = `<option value="${appointmentTime}" selected>${appointmentTime}</option>`;
    
    // Show/hide duration container based on appointment type
    const typeSelect = document.getElementById('appointmentType<?php echo htmlspecialchars($appointment_id); ?>');
    const otherDurationContainer = document.getElementById('otherDurationContainer<?php echo htmlspecialchars($appointment_id); ?>');
    
    if (typeSelect.value === 'Other') {
        otherDurationContainer.style.display = 'block';
    }
    
    typeSelect.addEventListener('change', function() {
        otherDurationContainer.style.display = this.value === 'Other' ? 'block' : 'none';
    });
});

// Save appointment changes
function saveAppointment(appointmentId) {
    // TODO: Implement save functionality
    console.log('Saving appointment:', appointmentId);
}

// Delete appointment function
function deleteAppointment(appointmentId) {
    // TODO: Implement delete functionality
    console.log('Delete appointment:', appointmentId);
}
</script> 