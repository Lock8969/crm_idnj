<?php
/**
 * Payment Modal Component Version 2
 * 
 * Usage:
 * include 'lead_convert_pymt_model2.php';
 * renderPaymentModal2($customerId = null, $firstName = null, $lastName = null, $leadData = null);
 * 
 * @param int|null $customerId The customer ID if coming from client table
 * @param string|null $firstName The customer's first name (if known)
 * @param string|null $lastName The customer's last name (if known)
 * @param array|null $leadData Full lead data if coming from lead conversion
 */
function renderPaymentModal2($customerId = null, $firstName = null, $lastName = null, $leadData = null) {
    // Generate a unique identifier for this modal instance - note the "2" after "paymentModal"
    $modalId = 'paymentModal2' . ($customerId ?: (isset($leadData['id']) ? $leadData['id'] : rand(1000, 9999)));
    
    // Determine if we're coming from lead conversion or from client
    $isFromLead = !empty($leadData);
    
    // Use lead data if available, otherwise use passed parameters
    if ($isFromLead) {
        $firstName = $leadData['first_name'] ?? $firstName;
        $lastName = $leadData['last_name'] ?? $lastName;
        $leadId = $leadData['id'] ?? null;
    }
?>
<!-- Payment Modal -->
<div class="modal fade" id="<?php echo $modalId; ?>" tabindex="-1" aria-labelledby="<?php echo $modalId; ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="<?php echo $modalId; ?>Label">
                    Payment Information<?php echo (!empty($firstName) && !empty($lastName)) ? ' - ' . htmlspecialchars($firstName . ' ' . $lastName) : ''; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="paymentForm<?php echo $modalId; ?>">
                    <!-- Hidden fields to track data flow -->
                    <?php if ($customerId): ?>
                    <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($customerId); ?>">
                    <?php endif; ?>
                    
                    <?php if ($isFromLead): ?>
                    <input type="hidden" name="lead_id" value="<?php echo htmlspecialchars($leadId); ?>">
                    <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($firstName); ?>">
                    <input type="hidden" name="last_name" value="<?php echo htmlspecialchars($lastName); ?>">
                    <input type="hidden" name="dl_state" value="<?php echo htmlspecialchars($leadData['dl_state'] ?? ''); ?>">
                    <input type="hidden" name="law_type" value="<?php echo htmlspecialchars($leadData['law_type'] ?? ''); ?>">
                    <input type="hidden" name="offense_number" value="<?php echo htmlspecialchars($leadData['offense_number'] ?? ''); ?>">
                    <input type="hidden" name="phone_number" value="<?php echo htmlspecialchars($leadData['phone_number'] ?? ''); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($leadData['email'] ?? ''); ?>">
                    <input type="hidden" name="send_welcome_email" value="<?php echo isset($leadData['send_welcome_email']) ? '1' : '0'; ?>">
                    <input type="hidden" name="install_comments" value="<?php echo htmlspecialchars($leadData['install_comments'] ?? ''); ?>">
                    <?php endif; ?>
                    
                    <!-- Payment Amount Fields -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Payment Details</h5>
                        </div>
                        <div class="card-body">
                            
                        <!-- Pricing Code -->
                            <div class="row mb-3">
                            <label for="pricingCode<?php echo $modalId; ?>" class="col-sm-4 col-form-label">Pricing Code <span class="text-danger">*</span></label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="pricingCode<?php echo $modalId; ?>" name="pricing_code"  
                                        placeholder="0.00" step="0.01" min="0" required> 
                                </div>
                            </div>
                        </div>
                            
                            <!-- Four Week Rental (Calculated) -->
                            <div class="row mb-3">
                                <label for="fourWeekRental<?php echo $modalId; ?>" class="col-sm-4 col-form-label">Four Week Rental</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control" id="fourWeekRental<?php echo $modalId; ?>" 
                                            name="four_week_rental" value="0.00" readonly>
                                    </div>
                                    <small class="text-muted">Automatically calculated as pricing code × 28 days</small>
                                </div>
                            </div>
                            
                            <!-- Certification Fee -->
                            <div class="row mb-3">
                                <label for="certFee<?php echo $modalId; ?>" class="col-sm-4 col-form-label">Certification Fee</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="certFee<?php echo $modalId; ?>" 
                                            name="cert_fee" placeholder="0.00" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>

                            <!-- Administration Fee -->
                            <div class="row mb-3">
                                <label for="adminFee<?php echo $modalId; ?>" class="col-sm-4 col-form-label">Administration Fee</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="adminFee<?php echo $modalId; ?>" 
                                            name="admin_fee" placeholder="0.00" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>

                            <!-- Applied Credit -->
                            <div class="row mb-3">
                                <label for="appliedCredit<?php echo $modalId; ?>" class="col-sm-4 col-form-label">Applied Credit</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="appliedCredit<?php echo $modalId; ?>" 
                                            name="applied_credit" placeholder="0.00" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <!-- Subtotal (Calculated) -->
                            <div class="row mb-3">
                                <label for="subtotal<?php echo $modalId; ?>" class="col-sm-4 col-form-label fw-bold">Sub-Total</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control fw-bold" id="subtotal<?php echo $modalId; ?>" 
                                            name="subtotal" value="0.00" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Sales Tax (Calculated) -->
                            <div class="row mb-3">
                                <label for="salesTax<?php echo $modalId; ?>" class="col-sm-4 col-form-label">Sales Tax (6.625%)</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control" id="salesTax<?php echo $modalId; ?>" 
                                            name="sales_tax" value="0.00" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Total (Calculated) -->
                            <div class="row mb-3">
                                <label for="totalAmount<?php echo $modalId; ?>" class="col-sm-4 col-form-label fw-bold">Total</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control fw-bold" id="totalAmount<?php echo $modalId; ?>" 
                                            name="total_amount" value="0.00" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Credit Card Fields -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Credit Card Information</h5>
                        </div>
                        <div class="card-body">
                            <!-- Card Number -->
                            <div class="row mb-3">
                                <label for="cardNumber<?php echo $modalId; ?>" class="col-sm-4 col-form-label">Card Number <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" id="cardNumber<?php echo $modalId; ?>" 
                                        name="card_number" placeholder="XXXX XXXX XXXX XXXX" maxlength="19" required
                                        oninput="formatCardNumber(this)">
                                </div>
                            </div>
                            
                            <!-- Expiration Date -->
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label">Expiration Date <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <div class="row">
                                        <div class="col-6">
                                            <select class="form-select" id="expMonth<?php echo $modalId; ?>" name="exp_month" required>
                                                <option value="">Month</option>
                                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                                    <option value="<?php echo sprintf('%02d', $i); ?>"><?php echo sprintf('%02d', $i); ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <select class="form-select" id="expYear<?php echo $modalId; ?>" name="exp_year" required>
                                                <option value="">Year</option>
                                                <?php 
                                                $currentYear = date('Y');
                                                for ($i = $currentYear; $i <= $currentYear + 10; $i++): 
                                                ?>
                                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- CVV -->
                            <div class="row mb-3">
                                <label for="cvv<?php echo $modalId; ?>" class="col-sm-4 col-form-label">CVV <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" id="cvv<?php echo $modalId; ?>" 
                                        name="cvv" placeholder="XXX" maxlength="4" required
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                </div>
                            </div>
                            
                            <!-- Cardholder Name -->
                            <div class="row mb-3">
                                <label for="cardholderFirstName<?php echo $modalId; ?>" class="col-sm-4 col-form-label">Cardholder Name <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <div class="row">
                                        <div class="col-6">
                                            <input type="text" class="form-control" id="cardholderFirstName<?php echo $modalId; ?>" 
                                                name="cardholder_first_name" placeholder="First Name" 
                                                value="<?php echo htmlspecialchars($firstName ?? ''); ?>" required>
                                        </div>
                                        <div class="col-6">
                                            <input type="text" class="form-control" id="cardholderLastName<?php echo $modalId; ?>" 
                                                name="cardholder_last_name" placeholder="Last Name" 
                                                value="<?php echo htmlspecialchars($lastName ?? ''); ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Status Message -->
                    <div id="paymentStatus<?php echo $modalId; ?>" class="alert d-none"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="processPaymentBtn<?php echo $modalId; ?>" onclick="processPayment('<?php echo $modalId; ?>')">
                    Process Payment
                </button>
                <button type="button" class="btn btn-success d-none" id="nextBtn<?php echo $modalId; ?>">
                    Continue to Vehicle Info
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for calculating payment totals -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set up the calculation for this specific modal instance
    setupPaymentCalculations('<?php echo $modalId; ?>');
});

// Function to set up payment calculations for a specific modal
function setupPaymentCalculations(modalId) {
    // Get input elements
    const pricingCodeInput = document.getElementById('pricingCode' + modalId);
    const certFeeInput = document.getElementById('certFee' + modalId);
    const adminFeeInput = document.getElementById('adminFee' + modalId);
    const appliedCreditInput = document.getElementById('appliedCredit' + modalId);
    
    // Add input event listeners to all inputs that affect the calculation
    const inputs = [pricingCodeInput, certFeeInput, adminFeeInput, appliedCreditInput];
    inputs.forEach(input => {
        if (input) {
            input.addEventListener('input', function() {
                calculatePaymentTotals(modalId);
            });
        }
    });
    
    // Initial calculation
    calculatePaymentTotals(modalId);
}

// Calculate payment totals
function calculatePaymentTotals(modalId) {
    const pricingCode = parseFloat(document.getElementById('pricingCode' + modalId).value || 0);
    const fourWeekRental = pricingCode * 28;
    const certFee = parseFloat(document.getElementById('certFee' + modalId).value || 0);
    const adminFee = parseFloat(document.getElementById('adminFee' + modalId).value || 0);
    const appliedCredit = parseFloat(document.getElementById('appliedCredit' + modalId).value || 0);
    
    const subtotal = fourWeekRental + certFee + adminFee - appliedCredit;
    const salesTax = subtotal * 0.06625; // 6.625% tax rate
    const total = subtotal + salesTax;
    
    // Update form fields
    document.getElementById('fourWeekRental' + modalId).value = fourWeekRental.toFixed(2);
    document.getElementById('subtotal' + modalId).value = subtotal.toFixed(2);
    document.getElementById('salesTax' + modalId).value = salesTax.toFixed(2);
    document.getElementById('totalAmount' + modalId).value = total.toFixed(2);
}

// Format credit card number with spaces
function formatCardNumber(input) {
    let value = input.value.replace(/\D/g, '');
    let formattedValue = '';
    
    for (let i = 0; i < value.length; i++) {
        if (i > 0 && i % 4 === 0) {
            formattedValue += ' ';
        }
        formattedValue += value.charAt(i);
    }
    
    input.value = formattedValue;
}

// Process payment function - will be implemented separately
function processPayment(modalId) {
    // Validate the form first
    const form = document.getElementById('paymentForm' + modalId);
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Show a processing message
    const statusDiv = document.getElementById('paymentStatus' + modalId);
    statusDiv.className = 'alert alert-info';
    statusDiv.textContent = 'Processing payment...';
    
    // Disable the process button to prevent multiple submissions
    const processBtn = document.getElementById('processPaymentBtn' + modalId);
    processBtn.disabled = true;
    processBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
    
    // For testing purposes, simulate a successful payment after 2 seconds
    setTimeout(function() {
        statusDiv.className = 'alert alert-success';
        statusDiv.textContent = 'Payment processed successfully!';
        
        // Hide the process button and show the next button
        processBtn.classList.add('d-none');
        const nextBtn = document.getElementById('nextBtn' + modalId);
        nextBtn.classList.remove('d-none');
        
        // Enable the next button to proceed to vehicle info
        nextBtn.disabled = false;
    }, 2000);
}
</script>
<?php
}
?>