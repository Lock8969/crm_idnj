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
                                <select class="form-select" id="vehicleYear<?php echo htmlspecialchars($lead['id']); ?>" 
                                       name="year_id" tabindex="1">
                                    <option value="">Select Year</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="vehicleMake<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Make</label>
                                <select class="form-select" id="vehicleMake<?php echo htmlspecialchars($lead['id']); ?>" 
                                       name="make_id" tabindex="2">
                                    <option value="">Select Make</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="vehicleModel<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Model</label>
                                <select class="form-select" id="vehicleModel<?php echo htmlspecialchars($lead['id']); ?>" 
                                       name="model_id" tabindex="3" disabled>
                                    <option value="">Select Model</option>
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
                                    <option value="Push Button">Push Button</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="startStop<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Start/Stop</label>
                                <select class="form-select" id="startStop<?php echo htmlspecialchars($lead['id']); ?>" name="start_stop" tabindex="6">
                                    <option value="">Select Option</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="isHybrid<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Hybrid</label>
                                <select class="form-select" id="isHybrid<?php echo htmlspecialchars($lead['id']); ?>" name="hybrid" tabindex="4">
                                    <option value="">Select Option</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="saveVehicleInfo<?php echo htmlspecialchars($lead['id']); ?>">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            Save Vehicle Information
                        </button>
                        <button type="button" class="btn btn-success d-none" id="setInstallAppointment<?php echo htmlspecialchars($lead['id']); ?>">
                            Set Install Appointment
                        </button>
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
    const yearSelect = document.getElementById('vehicleYear' + leadId);
    const makeSelect = document.getElementById('vehicleMake' + leadId);
    const modelSelect = document.getElementById('vehicleModel' + leadId);
    
    // Get stored lead data when modal opens
    vehicleModal.addEventListener('show.bs.modal', function (event) {
        console.log('Vehicle Info Modal Opening for Lead ID:', leadId);
        
        // Load vehicle years if not already loaded
        if (yearSelect.options.length <= 1) {
            fetch('get_vehicle_years.php')
                .then(response => response.json())
                .then(data => {
                    data.forEach(year => {
                        const option = document.createElement('option');
                        option.value = year.id;
                        option.textContent = year.year;
                        yearSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error loading vehicle years:', error));
        }

        // Load vehicle makes if not already loaded
        if (makeSelect.options.length <= 1) {
            fetch('get_vehicle_makes.php')
                .then(response => response.json())
                .then(data => {
                    data.forEach(make => {
                        const option = document.createElement('option');
                        option.value = make.id;
                        option.textContent = make.make;
                        makeSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error loading vehicle makes:', error));
        }
        
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

    // Handle make selection change
    makeSelect.addEventListener('change', function() {
        const selectedMakeId = this.value;
        modelSelect.disabled = !selectedMakeId;
        modelSelect.innerHTML = '<option value="">Select Model</option>';
        
        if (selectedMakeId) {
            const formData = new FormData();
            formData.append('make_id', selectedMakeId);
            
            fetch('get_models.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                modelSelect.innerHTML = html;
            })
            .catch(error => console.error('Error loading models:', error));
        }
    });

    // Handle save button click
    document.getElementById('saveVehicleInfo' + leadId).addEventListener('click', function() {
        const saveButton = this;
        const spinner = saveButton.querySelector('.spinner-border');
        const setAppointmentButton = document.getElementById('setInstallAppointment' + leadId);
        
        // Show spinner, disable button
        spinner.classList.remove('d-none');
        saveButton.disabled = true;

        // Get form data
        const formData = new FormData(document.getElementById('vehicleInfoForm' + leadId));
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        // Add client_id from previous modal data
        const storageKey = 'leadConversionData_' + leadId;
        const storedData = sessionStorage.getItem(storageKey);
        if (storedData) {
            const leadData = JSON.parse(storedData);
            data.client_id = leadData.client_id;
        }

        // Send data to server
        fetch('save_new_vehicle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Hide save button, show appointment button
                saveButton.classList.add('d-none');
                setAppointmentButton.classList.remove('d-none');
                
                // Add click handler for the Set Install Appointment button
                setAppointmentButton.addEventListener('click', function() {
                    console.log('=== Set Install Appointment Button Clicked ===');
                    console.log('Lead ID:', leadId);
                    
                    // Log the current stored data
                    const storageKey = 'leadConversionData_' + leadId;
                    const storedData = sessionStorage.getItem(storageKey);
                    console.log('Current Stored Data:', storedData);
                    
                    // Close current modal
                    const currentModal = bootstrap.Modal.getInstance(document.getElementById('vehicleInfoModal' + leadId));
                    currentModal.hide();
                    
                    // Show installation appointment modal
                    setTimeout(() => {
                        console.log('Opening Install Appointment Modal...');
                        const installModal = new bootstrap.Modal(document.getElementById('installAppointmentModal' + leadId));
                        installModal.show();
                    }, 150);
                });
            } else {
                throw new Error(result.error || 'Failed to save vehicle information');
            }
        })
        .catch(error => {
            console.error('Error saving vehicle information:', error);
            alert('Failed to save vehicle information. Please try again.');
            // Re-enable save button and hide spinner
            saveButton.disabled = false;
            spinner.classList.add('d-none');
        });
    });
});
</script>

<?php
}
?> 