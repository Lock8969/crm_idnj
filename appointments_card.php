<?php
// Debug logging
error_log("Starting appointments_card.php");
error_log("Client ID: " . (isset($client_id) ? $client_id : 'not set'));
error_log("PDO connection: " . (isset($pdo) ? 'set' : 'not set'));

//------------------------
// NOTES: Service Inclusion
//------------------------
// Includes the AppointmentService class that handles data operations
require_once 'AppointmentService.php';

//------------------------
// NOTES: Variable Validation
//------------------------
// Ensures required variables are available before proceeding
if (!isset($pdo) || !isset($client_id)) {
    error_log("Missing required variables in appointments_card.php");
    echo "<div class='alert alert-danger'>Error: Missing required variables</div>";
    return;
}

//------------------------
// NOTES: Service Initialization
//------------------------
// Creates an instance of AppointmentService with the database connection
$appointmentService = new AppointmentService($pdo);

//------------------------
// NOTES: Data Retrieval
//------------------------
// Gets all appointments for the current client using the service
try {
    error_log("Attempting to get appointments for client ID: " . $client_id);
    $appointments = $appointmentService->getClientAppointments($client_id);
    error_log("Number of appointments found: " . count($appointments));
} catch (Exception $e) {
    error_log("Error getting appointments: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    return;
}
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Appointments</h4>
        <div class="appointment-stats">
            <div class="upcoming-label">Upcoming Appointments</div>
            <div class="upcoming-count">
                <?php 
                $upcomingCount = count(array_filter($appointments, function($appt) { 
                    return $appt['is_upcoming']; 
                }));
                echo $upcomingCount;
                ?>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($appointments)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>No appointments found for this client.
            </div>
        <?php else: ?>
            <!-- Upcoming Appointments Section -->
            <div class="mb-4">
                <h5 class="text-primary mb-3">Upcoming Appointments</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Tech</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $upcomingAppointments = array_filter($appointments, function($appt) { 
                                return $appt['is_upcoming']; 
                            });
                            if (empty($upcomingAppointments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No upcoming appointments</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($upcomingAppointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['date_formatted']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['time_formatted']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['appointment_type']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['location_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['technician_initials']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['service_note'] ?? ''); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <?php if ($appointment['status'] !== 'cancelled'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="editAppointment(<?php echo $appointment['id']; ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Past Appointments Section -->
            <div>
                <h5 class="text-secondary mb-3">Past Appointments</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Tech</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $pastAppointments = array_filter($appointments, function($appt) { 
                                return !$appt['is_upcoming']; 
                            });
                            if (empty($pastAppointments)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No past appointments</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pastAppointments as $appointment): ?>
                                    <tr class="<?php echo $appointment['is_historical'] ? 'table-secondary' : ''; ?>">
                                        <td>
                                            <?php echo htmlspecialchars($appointment['date_formatted']); ?>
                                            <?php if ($appointment['is_historical']): ?>
                                                <span class="badge bg-warning text-dark ms-2" title="This appointment was updated">Updated</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['time_formatted']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['appointment_type']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['location_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['technician_initials']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($appointment['service_note'] ?? ''); ?>
                                            <?php if ($appointment['is_historical'] && !empty($appointment['update_reason'])): ?>
                                                <div class="small text-muted mt-1">
                                                    <i class="bi bi-info-circle"></i> <?php echo htmlspecialchars($appointment['update_reason']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewAppointment(<?php echo $appointment['id']; ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <?php if ($appointment['is_historical']): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-info ms-1" 
                                                            title="This appointment was updated"
                                                            data-bs-toggle="popover"
                                                            data-bs-trigger="hover"
                                                            data-bs-placement="left"
                                                            data-appointment-id="<?php echo $appointment['id']; ?>">
                                                        <i class="bi bi-clock-history"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Appointment Card Styles */
.appointment-stats {
    text-align: right;
}
.upcoming-label {
    font-size: 0.875rem;
    color: #6c757d;
}
.upcoming-count {
    font-size: 1.25rem;
    font-weight: bold;
    color: #0d6efd;
}
</style>

<script>
// Appointment Action Functions
function viewAppointment(id) {
    // TODO: Implement view appointment details
    console.log('View appointment:', id);
}

function editAppointment(id) {
    console.log('Edit Appointment - ID from appointments_card.php:', id);
    
    // Include the edit appointment modal
    fetch(`edit_appointment.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            // Create a temporary div to hold the modal
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            // Get the modal element
            const modalElement = tempDiv.querySelector('.modal');
            if (!modalElement) {
                console.error('Modal element not found in response');
                return;
            }
            
            // Get all script tags
            const scripts = tempDiv.querySelectorAll('script');
            
            // Add the modal to the document
            document.body.appendChild(modalElement);
            
            // Execute each script
            scripts.forEach(script => {
                const newScript = document.createElement('script');
                newScript.textContent = script.textContent;
                document.body.appendChild(newScript);
                newScript.remove();
            });
            
            // Initialize the modal
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            
            // Clean up when modal is hidden
            modalElement.addEventListener('hidden.bs.modal', function() {
                this.remove();
            });
        })
        .catch(error => {
            console.error('Error loading edit appointment modal:', error);
        });
}

function cancelAppointment(id) {
    // TODO: Implement cancel appointment
    console.log('Cancel appointment:', id);
}

// Initialize popovers
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl, {
            html: false,
            content: 'Loading current appointment details...'
        });
    });

    // Add hover event listeners to historical appointment buttons
    document.querySelectorAll('[data-appointment-id]').forEach(function(button) {
        button.addEventListener('mouseenter', function() {
            const appointmentId = this.dataset.appointmentId;
            const popover = bootstrap.Popover.getInstance(this);
            
            // Fetch appointment details using the appointment ID
            fetch(`appointment_api.php?id=${appointmentId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Appointment Details:', data);
                    if (data.success && data.data) {
                        const appointment = data.data;
                        
                        // Format the date and time
                        const date = new Date(appointment.start_time);
                        const formattedDate = (date.getMonth() + 1).toString().padStart(2, '0') + '/' + 
                                            date.getDate().toString().padStart(2, '0') + '/' + 
                                            date.getFullYear();
                        const hours = date.getHours();
                        const minutes = date.getMinutes().toString().padStart(2, '0');
                        const ampm = hours >= 12 ? 'PM' : 'AM';
                        const formattedHours = (hours % 12 || 12).toString().padStart(2, '0');
                        const formattedTime = `${formattedHours}:${minutes}${ampm}`;

                        const content = 
                            'Appointment Details\n\n' +
                            `Date: ${formattedDate}\n` +
                            `Time: ${formattedTime}\n` +
                            `Type: ${appointment.appointment_type}\n` +
                            `Location: ${appointment.location_name}\n` +
                            `Technician: ${appointment.technician_name}\n` +
                            `Status: ${appointment.status}\n` +
                            (appointment.service_note ? `Notes: ${appointment.service_note}` : '');
                        
                        popover._config.content = content;
                        popover.show();
                    } else {
                        popover._config.content = 'Error loading appointment details';
                        popover.show();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    popover._config.content = 'Error loading appointment details';
                    popover.show();
                });
        });
    });
});
</script>