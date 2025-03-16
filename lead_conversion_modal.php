<?php
/**
 * Lead Conversion Modal Component
 * 
 * Usage:
 * include 'lead_conversion_modal.php';
 * renderLeadConversionModal($lead, $customAction = null);
 * 
 * @param array $lead The lead data array
 * @param string $customAction Optional custom form action
 */

function renderLeadConversionModal($lead, $customAction = null) {
    $formAction = $customAction ?? 'process_lead_conversion.php';
    $leadId = htmlspecialchars($lead['id']);
    $firstName = htmlspecialchars($lead['first_name'] ?? '');
    $lastName = htmlspecialchars($lead['last_name'] ?? '');
    $phone = htmlspecialchars($lead['phone_number'] ?? '');
    $email = htmlspecialchars($lead['email'] ?? '');
?>
<!-- Modal for lead conversion -->
<div class="modal fade" id="convertLeadModal<?php echo $leadId; ?>" tabindex="-1" aria-labelledby="convertLeadModalLabel<?php echo $leadId; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="convertLeadModalLabel<?php echo $leadId; ?>">Convert Lead: <?php echo trim($firstName . ' ' . $lastName); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="convertLeadForm<?php echo $leadId; ?>">
                    <input type="hidden" name="lead_id" value="<?php echo $leadId; ?>">
                    
                    <div class="row mb-3">
                        <!-- First Name -->
                        <div class="col-md-6">
                            <label for="firstName<?php echo $leadId; ?>" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName<?php echo $leadId; ?>" name="first_name" value="<?php echo $firstName; ?>" required>
                        </div>
                        
                        <!-- Last Name -->
                        <div class="col-md-6">
                            <label for="lastName<?php echo $leadId; ?>" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastName<?php echo $leadId; ?>" name="last_name" value="<?php echo $lastName; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <!-- Driver's License State -->
                        <div class="col-md-6">
                            <label for="dlState<?php echo $leadId; ?>" class="form-label">Driver's License State</label>
                            <select class="form-select" id="dlState<?php echo $leadId; ?>" name="dl_state" required>
                            <option value="">Select State</option>
                                <?php include 'states.php'; // Include states array
                                foreach ($states as $abbr => $name) {
                                    echo "<option value='{$abbr}'>{$abbr}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <!-- Law Type -->
                        <div class="col-md-6">
                            <label for="lawType<?php echo $leadId; ?>" class="form-label">Law Type</label>
                            <select class="form-select" id="lawType<?php echo $leadId; ?>" name="law_type" required>
                                <option value="">Select Law Type</option>
                                <option value="Old Law">Old Law</option>
                                <option value="New Law">New Law</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <!-- Offense # -->
                        <div class="col-md-6">
                            <label for="offenseNum<?php echo $leadId; ?>" class="form-label">Offense #</label>
                            <select class="form-select" id="offenseNum<?php echo $leadId; ?>" name="offense_number" required>
                                <option value="">Select Offense Number</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>
                        
                        <!-- Phone Number -->
                        <div class="col-md-6">
                            <label for="phoneNumber<?php echo $leadId; ?>" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phoneNumber<?php echo $leadId; ?>" name="phone_number" value="<?php echo $phone; ?>" required maxlength="12" oninput="formatPhoneNumber(this)">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <!-- Email -->
                        <div class="col-md-6">
                            <label for="email<?php echo $leadId; ?>" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email<?php echo $leadId; ?>" name="email" value="<?php echo $email; ?>">
                            <!-- Welcome Email Checkbox -->
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="welcomeEmail<?php echo $leadId; ?>" name="send_welcome_email">
                                <label class="form-check-label" for="welcomeEmail<?php echo $leadId; ?>">
                                    Send Welcome Email
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <!-- This column intentionally left empty to maintain layout -->
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <!-- Install Comments -->
                        <div class="col-md-12">
                            <label for="installComments<?php echo $leadId; ?>" class="form-label">Install Comments</label>
                            <textarea class="form-control" id="installComments<?php echo $leadId; ?>" name="install_comments" rows="3"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="nextBtn<?php echo $leadId; ?>" onclick="showPaymentModal('<?php echo $leadId; ?>')">
                    Continue to Payment
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showPaymentModal(leadId) {
    // Hide the current modal
    let currentModal = bootstrap.Modal.getInstance(document.getElementById('convertLeadModal' + leadId));
    
    // Remove focus from any elements before hiding
    document.activeElement.blur();
    
    currentModal.hide();
    
    // Collect lead data from the form
    const form = document.getElementById('convertLeadForm' + leadId);
    const formData = new FormData(form);
    const leadData = {};
    
    // Convert FormData to a simple object
    for (const [key, value] of formData.entries()) {
        leadData[key] = value;
    }
    
    // Store lead data in sessionStorage for access by payment modal
    sessionStorage.setItem('leadData_' + leadId, JSON.stringify(leadData));
    
    // Show the payment modal
    let paymentModal = new bootstrap.Modal(document.getElementById('paymentModal' + leadId));
    paymentModal.show();
    
    // Wait for modal to be fully shown before setting up event listeners
    paymentModal._element.addEventListener('shown.bs.modal', function() {
        // Set the nextBtn event handler for moving to vehicle info modal
        const nextBtn = document.getElementById('nextBtn' + leadId);
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                // Get the client ID from the payment modal's next button
                const clientId = this.getAttribute('data-client-id');
                
                // Hide the payment modal
                paymentModal.hide();
                
                // Show the vehicle info modal (Modal 3)
                let vehicleModal = new bootstrap.Modal(document.getElementById('vehicleInfoModal' + leadId));
                
                // Pass the client ID to the vehicle modal if needed
                if (clientId) {
                    document.getElementById('vehicleInfoModal' + leadId).setAttribute('data-client-id', clientId);
                }
                
                vehicleModal.show();
            });
        } else {
            console.warn('Next button not found with ID: nextBtn' + leadId);
        }
    });
}
</script>

<?php
    // Include the payment modal
    include_once 'payment_modal.php';

    // Prepare lead data for the payment modal
    $leadData = [
        'id' => $leadId,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'phone_number' => $phone,
        'email' => $email
    ];

    // Render the payment modal with lead data
    renderPaymentModal(null, $firstName, $lastName, $leadData);
}
?>