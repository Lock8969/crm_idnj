<?php
if (!defined('INCLUDED_IN_SCRIPT')) {
    die('No direct script access allowed');
}
include_once 'states.php';

function renderLeadConvertModal1($lead) {
?>
<!-- Modal template - will be used for each lead -->
<div class="modal fade" id="convertLeadModal1<?php echo htmlspecialchars($lead['id']); ?>" tabindex="-1" aria-labelledby="convertLeadModalLabel<?php echo htmlspecialchars($lead['id']); ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="convertLeadModalLabel<?php echo htmlspecialchars($lead['id']); ?>">Convert Lead</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="convertLeadForm<?php echo htmlspecialchars($lead['id']); ?>" action="process_lead_conversion.php" method="POST">
                    <input type="hidden" name="lead_id" value="<?php echo htmlspecialchars($lead['id']); ?>">
                    
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="firstName<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="firstName<?php echo htmlspecialchars($lead['id']); ?>" 
                                       name="first_name" value="<?php echo htmlspecialchars($lead['first_name'] ?? ''); ?>" tabindex="1">
                            </div>

                            <div class="mb-3">
                                <label for="dlState<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Driver's License State</label>
                                <select class="form-select" id="dlState<?php echo htmlspecialchars($lead['id']); ?>" name="dl_state" tabindex="3">
                                    <option value="">Select State</option>
                                    <?php foreach ($states as $abbr => $state): ?>
                                        <option value="<?php echo htmlspecialchars($abbr); ?>" 
                                            <?php echo (($lead['dl_state'] ?? '') === $abbr) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($abbr); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="email<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email<?php echo htmlspecialchars($lead['id']); ?>" 
                                       name="email" value="<?php echo htmlspecialchars($lead['email'] ?? ''); ?>" tabindex="5">
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="sendWelcome<?php echo htmlspecialchars($lead['id']); ?>" 
                                       name="send_welcome" tabindex="7">
                                <label class="form-check-label" for="sendWelcome<?php echo htmlspecialchars($lead['id']); ?>">Send Welcome Mail</label>
                            </div>

                            <div class="mb-3">
                                <label for="installComments<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Install Comments</label>
                                <textarea class="form-control" id="installComments<?php echo htmlspecialchars($lead['id']); ?>" 
                                          name="install_comments" rows="3" tabindex="8"><?php echo htmlspecialchars($lead['install_comments'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="lastName<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="lastName<?php echo htmlspecialchars($lead['id']); ?>" 
                                       name="last_name" value="<?php echo htmlspecialchars($lead['last_name'] ?? ''); ?>" tabindex="2">
                            </div>

                            <div class="mb-3">
                                <label for="lawType<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Law Type</label>
                                <select class="form-select" id="lawType<?php echo htmlspecialchars($lead['id']); ?>" name="law_type" tabindex="4">
                                    <option value="">Select Law Type</option>
                                    <option value="Old Law" <?php echo (($lead['law_type'] ?? '') === 'Old Law') ? 'selected' : ''; ?>>Old Law</option>
                                    <option value="New Law" <?php echo (($lead['law_type'] ?? '') === 'New Law') ? 'selected' : ''; ?>>New Law</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="offenseNumber<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Offense Number</label>
                                <select class="form-select" id="offenseNumber<?php echo htmlspecialchars($lead['id']); ?>" name="offense_number" tabindex="6">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo (($lead['offense_number'] ?? '') == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="phoneNumber<?php echo htmlspecialchars($lead['id']); ?>" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phoneNumber<?php echo htmlspecialchars($lead['id']); ?>" 
                                       name="phone_number" value="<?php echo htmlspecialchars($lead['phone_number'] ?? ''); ?>" maxlength="12" oninput="formatPhoneNumber(this)" tabindex="7">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="continueToPayment<?php echo htmlspecialchars($lead['id']); ?>">Continue to Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const continueBtn = document.getElementById('continueToPayment<?php echo htmlspecialchars($lead['id']); ?>');
    const form = document.getElementById('convertLeadForm<?php echo htmlspecialchars($lead['id']); ?>');
    
    continueBtn.addEventListener('click', function() {
        // Get all form data
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });
        
        // Store the data in sessionStorage
        sessionStorage.setItem('leadConversionData', JSON.stringify(data));
        
        // Close current modal
        const currentModal = bootstrap.Modal.getInstance(document.getElementById('convertLeadModal1<?php echo htmlspecialchars($lead['id']); ?>'));
        currentModal.hide();
        
        // Wait for the current modal to fully close
        setTimeout(() => {
            // Show the payment modal
            const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal<?php echo htmlspecialchars($lead['id']); ?>'));
            paymentModal.show();
        }, 150);
    });
});
</script>
<?php
}
?>