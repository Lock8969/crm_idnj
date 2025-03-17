<?php
/**
 * Vehicle Information Modal
 * 
 * Usage:
 * include 'vehicle_info_modal.php';
 * renderVehicleInfoModal($clientId);
 *
 * @param int $clientId The newly converted client ID
 */

function renderVehicleInfoModal($clientId) {
?>
<!-- Vehicle Information Modal -->
<div class="modal fade" id="vehicleInfoModal<?php echo $clientId; ?>" tabindex="-1" aria-labelledby="vehicleInfoModalLabel<?php echo $clientId; ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vehicleInfoModalLabel<?php echo $clientId; ?>">Enter Vehicle Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="vehicleInfoForm<?php echo $clientId; ?>">
                    <input type="hidden" name="client_id" value="<?php echo $clientId; ?>">

                    <div class="mb-3">
                        <label for="vehicleYear<?php echo $clientId; ?>" class="form-label">Vehicle Year</label>
                        <input type="number" class="form-control" id="vehicleYear<?php echo $clientId; ?>" name="vehicle_year" placeholder="Enter vehicle year" min="1900" max="2099">
                    </div>

                    <div class="mb-3">
                        <label for="vehicleMake<?php echo $clientId; ?>" class="form-label">Vehicle Make</label>
                        <input type="text" class="form-control" id="vehicleMake<?php echo $clientId; ?>" name="vehicle_make" placeholder="Enter vehicle make">
                    </div>

                    <div class="mb-3">
                        <label for="vehicleModel<?php echo $clientId; ?>" class="form-label">Vehicle Model</label>
                        <input type="text" class="form-control" id="vehicleModel<?php echo $clientId; ?>" name="vehicle_model" placeholder="Enter vehicle model">
                    </div>

                    <div class="mb-3">
                        <label for="startSystem<?php echo $clientId; ?>" class="form-label">Start System</label>
                        <select class="form-select" id="startSystem<?php echo $clientId; ?>" name="start_system">
                            <option value="">Select Start System</option>
                            <option value="Key">Key Start</option>
                            <option value="Push button">Push Button</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="startStop<?php echo $clientId; ?>" class="form-label">Start Stop</label>
                        <select class="form-select" id="startStop<?php echo $clientId; ?>" name="start_stop">
                            <option value="">Select Option</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="hybrid<?php echo $clientId; ?>" class="form-label">Hybrid</label>
                        <select class="form-select" id="hybrid<?php echo $clientId; ?>" name="hybrid">
                            <option value="">Select Option</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveVehicleInfo<?php echo $clientId; ?>" onclick="saveVehicleInfo('<?php echo $clientId; ?>')">
                    Save Vehicle Info
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function saveVehicleInfo(clientId) {
    const form = document.getElementById('vehicleInfoForm' + clientId);
    const formData = new FormData(form);
    const vehicleData = {};

    for (const [key, value] of formData.entries()) {
        vehicleData[key] = value;
    }

    console.log('Saving vehicle information:', vehicleData);

    // Send data to backend
    fetch('save_vehicle_info.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(vehicleData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Vehicle information saved successfully!');
            let modal = bootstrap.Modal.getInstance(document.getElementById('vehicleInfoModal' + clientId));
            modal.hide();
        } else {
            alert('Error saving vehicle info: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error saving vehicle info:', error);
        alert('An unexpected error occurred.');
    });
}
</script>

<?php
}
?>