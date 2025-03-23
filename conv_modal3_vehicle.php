<?php
if (!defined('INCLUDED_IN_SCRIPT')) {
    die('No direct script access allowed');
}

// Include database connection
include 'db.php';

function renderVehicleInfoModal($lead) {
?>
<!-- Vehicle Information Modal -->
<div class="modal fade" id="vehicleInfoModal<?php echo htmlspecialchars($lead['id']); ?>" tabindex="-1" aria-labelledby="vehicleInfoModalLabel<?php echo htmlspecialchars($lead['id']); ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vehicleInfoModalLabel<?php echo htmlspecialchars($lead['id']); ?>">Vehicle Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="vehicleInfoForm<?php echo htmlspecialchars($lead['id']); ?>">
                    <input type="hidden" name="lead_id" value="<?php echo htmlspecialchars($lead['id']); ?>">
                    
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="vehicleYear<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Year</label>
                                <input type="text" class="form-control" id="vehicleYear<?php echo htmlspecialchars($lead['id']); ?>" 
                                       name="vehicle_year" tabindex="1">
                            </div>

                            <div class="mb-3">
                                <label for="vehicleMake<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Make</label>
                                <input type="text" class="form-control" id="vehicleMake<?php echo htmlspecialchars($lead['id']); ?>" 
                                       name="vehicle_make" tabindex="2">
                            </div>

                            <div class="mb-3">
                                <label for="vehicleModel<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Model</label>
                                <input type="text" class="form-control" id="vehicleModel<?php echo htmlspecialchars($lead['id']); ?>" 
                                       name="vehicle_model" tabindex="3">
                            </div>

                            <div class="mb-3">
                                <label for="isHybrid<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Hybrid</label>
                                <select class="form-select" id="isHybrid<?php echo htmlspecialchars($lead['id']); ?>" name="is_hybrid" tabindex="4">
                                    <option value="">Select Option</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="startSystem<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Start System</label>
                                <select class="form-select" id="startSystem<?php echo htmlspecialchars($lead['id']); ?>" name="start_system" tabindex="5">
                                    <option value="">Select Option</option>
                                    <option value="Key">Key</option>
                                    <option value="Push button">Push Button</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="startStop<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Start/Stop</label>
                                <select class="form-select" id="startStop<?php echo htmlspecialchars($lead['id']); ?>" name="start_stop" tabindex="6">
                                    <option value="">Select Option</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="saveVehicleInfo<?php echo htmlspecialchars($lead['id']); ?>">Save Vehicle Information</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const leadId = '<?php echo htmlspecialchars($lead['id']); ?>';
    const vehicleModal = document.getElementById('vehicleInfoModal' + leadId);
    
    // Get stored lead data when modal opens
    vehicleModal.addEventListener('show.bs.modal', function (event) {
        console.log('Vehicle Info Modal Opening for Lead ID:', leadId);
        
        // Get lead-specific data from previous modal
        const storageKey = 'leadConversionData_' + leadId;
        console.log('Vehicle Info Modal - Looking for storage key:', storageKey);
        
        const storedData = sessionStorage.getItem(storageKey);
        console.log('Vehicle Info Modal - Retrieved stored data:', storedData);
        
        if (storedData) {
            const leadData = JSON.parse(storedData);
            console.log('Vehicle Info Modal - Parsed lead data:', leadData);
            
            // Here we can pre-populate fields from leadData if needed
            // Example: if (leadData.vehicle_year) {
            //     document.getElementById('vehicleYear' + leadId).value = leadData.vehicle_year;
            // }
        } else {
            console.log('No stored data found for lead ' + leadId);
        }
    });

    // Handle save button click
    document.getElementById('saveVehicleInfo' + leadId).addEventListener('click', function() {
        // Get form data
        const formData = new FormData(document.getElementById('vehicleInfoForm' + leadId));
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        // Get existing stored data
        const storageKey = 'leadConversionData_' + leadId;
        const storedData = sessionStorage.getItem(storageKey);
        let leadData = storedData ? JSON.parse(storedData) : {};

        // Add vehicle information to lead data
        leadData.vehicle_info = data;

        // Store updated data back
        sessionStorage.setItem(storageKey, JSON.stringify(leadData));

        // Close the modal
        const modal = bootstrap.Modal.getInstance(vehicleModal);
        modal.hide();

        // Here we can add the API call to save to database
        // saveVehicleInfoToDatabase(data);
    });
});
</script>

<?php
}
?> 