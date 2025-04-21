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
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['date_formatted']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['time_formatted']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['appointment_type']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['location_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['technician_initials']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['service_note'] ?? ''); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewAppointment(<?php echo $appointment['id']; ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>
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
    // Include the edit appointment modal
    fetch(`edit_appointment.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            // Create a temporary div to hold the modal
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            // Add the modal to the document
            document.body.appendChild(tempDiv.firstElementChild);
            
            // Initialize the modal
            const modal = new bootstrap.Modal(document.getElementById(`editAppointmentModal${id}`));
            modal.show();
            
            // Clean up when modal is hidden
            document.getElementById(`editAppointmentModal${id}`).addEventListener('hidden.bs.modal', function() {
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
</script> 