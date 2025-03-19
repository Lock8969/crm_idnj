<?php
if (!defined('INCLUDED_IN_SCRIPT')) {
    die('No direct script access allowed');
}

function renderPaymentModal2($client_id, $first_name, $last_name, $lead) {
    // If no lead data provided, return early
    if (!$lead) return;
?>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal<?php echo htmlspecialchars($lead['id']); ?>" tabindex="-1" aria-labelledby="paymentModalLabel<?php echo htmlspecialchars($lead['id']); ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel<?php echo htmlspecialchars($lead['id']); ?>">Payment Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="paymentForm<?php echo htmlspecialchars($lead['id']); ?>" action="conv_process_pymt.php" method="POST">
                    <input type="hidden" name="lead_id" value="<?php echo htmlspecialchars($lead['id']); ?>">
                    <input type="hidden" name="amount" id="hiddenTotal<?php echo htmlspecialchars($lead['id']); ?>">
                    
                    <!-- Payment Amount Fields -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Payment Details</h5>
                        </div>
                        <div class="card-body">
                            <!-- Pricing Code -->
                            <div class="row mb-3">
                                <label for="pricingCode<?php echo htmlspecialchars($lead['id']); ?>" class="col-sm-4 col-form-label">Pricing Code</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control" id="pricingCode<?php echo htmlspecialchars($lead['id']); ?>" 
                                               name="pricing_code" placeholder="0.00">
                                    </div>
                                </div>
                            </div>

                            <!-- Four Week Rental -->
                            <div class="row mb-3">
                                <label for="fourWeekRental<?php echo htmlspecialchars($lead['id']); ?>" class="col-sm-4 col-form-label">Four Week Rental</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control" id="fourWeekRental<?php echo htmlspecialchars($lead['id']); ?>" 
                                               name="four_week_rental" readonly>
                                    </div>
                                    <small class="text-muted">Automatically calculated as pricing code × 28 days</small>
                                </div>
                            </div>

                            <!-- Certification Fee -->
                            <div class="row mb-3">
                                <label for="certificationFee<?php echo htmlspecialchars($lead['id']); ?>" class="col-sm-4 col-form-label">Certification Fee</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control" id="certificationFee<?php echo htmlspecialchars($lead['id']); ?>" 
                                               name="certification_fee" placeholder="0.00">
                                    </div>
                                </div>
                            </div>

                            <!-- Administration Fee -->
                            <div class="row mb-3">
                                <label for="adminFee<?php echo htmlspecialchars($lead['id']); ?>" class="col-sm-4 col-form-label">Administration Fee</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control" id="adminFee<?php echo htmlspecialchars($lead['id']); ?>" 
                                               name="admin_fee" placeholder="0.00">
                                    </div>
                                </div>
                            </div>

                            <!-- Applied Credit -->
                            <div class="row mb-3">
                                <label for="appliedCredit<?php echo htmlspecialchars($lead['id']); ?>" class="col-sm-4 col-form-label">Applied Credit</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control" id="appliedCredit<?php echo htmlspecialchars($lead['id']); ?>" 
                                               name="applied_credit" placeholder="0.00">
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <!-- Sub-total -->
                            <div class="row mb-3">
                                <label for="subtotal<?php echo htmlspecialchars($lead['id']); ?>" class="col-sm-4 col-form-label fw-bold">Sub-total</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control fw-bold" id="subtotal<?php echo htmlspecialchars($lead['id']); ?>" 
                                               name="subtotal" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Sales Tax -->
                            <div class="row mb-3">
                                <label for="salesTax<?php echo htmlspecialchars($lead['id']); ?>" class="col-sm-4 col-form-label">Sales Tax (6.625%)</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control" id="salesTax<?php echo htmlspecialchars($lead['id']); ?>" 
                                               name="sales_tax" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Total -->
                            <div class="row mb-3">
                                <label for="displayTotal<?php echo htmlspecialchars($lead['id']); ?>" class="col-sm-4 col-form-label fw-bold">Total</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control fw-bold" id="displayTotal<?php echo htmlspecialchars($lead['id']); ?>" 
                                               name="total" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Credit Card Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Credit Card Information</h5>
                        </div>
                        <div class="card-body">
                            <!-- Card Number -->
                            <div class="row mb-3">
                                <label for="cardNumber<?php echo htmlspecialchars($lead['id']); ?>" class="col-sm-4 col-form-label">Card Number</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" id="cardNumber<?php echo htmlspecialchars($lead['id']); ?>" 
                                           name="card_number" maxlength="19" placeholder="XXXX XXXX XXXX XXXX">
                                </div>
                            </div>

                            <!-- Expiration Date and CVV -->
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label">Expiration Date</label>
                                <div class="col-sm-8">
                                    <div class="row">
                                        <div class="col-6">
                                            <select class="form-select" id="expMonth<?php echo htmlspecialchars($lead['id']); ?>" name="exp_month">
                                                <option value="">Month</option>
                                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                                    <option value="<?php echo sprintf('%02d', $i); ?>"><?php echo sprintf('%02d', $i); ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <select class="form-select" id="expYear<?php echo htmlspecialchars($lead['id']); ?>" name="exp_year">
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
                                <label for="cvv<?php echo htmlspecialchars($lead['id']); ?>" class="col-sm-4 col-form-label">CVV</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" id="cvv<?php echo htmlspecialchars($lead['id']); ?>" 
                                           name="cvv" maxlength="4" placeholder="XXX">
                                </div>
                            </div>

                            <!-- Cardholder Name -->
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label">Cardholder Name</label>
                                <div class="col-sm-8">
                                    <div class="row">
                                        <div class="col-6">
                                            <input type="text" class="form-control" id="cardFirstName<?php echo htmlspecialchars($lead['id']); ?>" 
                                                   name="first_name" placeholder="First Name">
                                        </div>
                                        <div class="col-6">
                                            <input type="text" class="form-control" id="cardLastName<?php echo htmlspecialchars($lead['id']); ?>" 
                                                   name="last_name" placeholder="Last Name">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="processPaymentBtn<?php echo htmlspecialchars($lead['id']); ?>">Process Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const leadId = '<?php echo htmlspecialchars($lead['id']); ?>';
    const form = document.getElementById('paymentForm' + leadId);
    
    // Get data from previous modal
    const leadData = JSON.parse(sessionStorage.getItem('leadConversionData') || '{}');
    
    // Pre-populate card holder names from previous modal
    document.getElementById('cardFirstName' + leadId).value = leadData.first_name || '';
    document.getElementById('cardLastName' + leadId).value = leadData.last_name || '';

    // Add the formatting setup here, before calculating totals
    formatDecimalInput(leadId);
    
    // Then do the original calculations
    calculatePaymentTotals(leadId);

    // Format card number input
    document.getElementById('cardNumber' + leadId).addEventListener('input', function(e) {
        let value = this.value.replace(/\D/g, '');
        let formattedValue = '';
        
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) {
                formattedValue += ' ';
            }
            formattedValue += value.charAt(i);
        }
        
        this.value = formattedValue;
    });

    // Format CVV input
    document.getElementById('cvv' + leadId).addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '');
    });

    // Handle Process Payment button click
    document.getElementById('processPaymentBtn' + leadId).addEventListener('click', function(e) {
        e.preventDefault();
        
        // Get the expiration date
        const expMonth = document.getElementById('expMonth' + leadId).value;
        const expYear = document.getElementById('expYear' + leadId).value;
        
        // Get the total amount
        const totalAmount = document.getElementById('hiddenTotal' + leadId).value;
        
        // Get cardholder name
        const firstName = document.getElementById('cardFirstName' + leadId).value;
        const lastName = document.getElementById('cardLastName' + leadId).value;
        
        // Get card details
        const cardNumber = document.getElementById('cardNumber' + leadId).value.replace(/\s/g, '');
        const cvv = document.getElementById('cvv' + leadId).value;
        
        // Validate required fields
        if (!firstName || !lastName || !cardNumber || !expMonth || !expYear || !cvv || !totalAmount) {
            alert('Please fill in all required fields');
            return;
        }
        
        // Validate card number length
        if (cardNumber.length < 15 || cardNumber.length > 16) {
            alert('Please enter a valid card number');
            return;
        }
        
        // Validate CVV
        if (cvv.length < 3 || cvv.length > 4) {
            alert('Please enter a valid CVV');
            return;
        }
        
        // Validate amount
        if (parseFloat(totalAmount) <= 0) {
            alert('Invalid payment amount');
            return;
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('first_name', firstName);
        formData.append('last_name', lastName);
        formData.append('card_number', cardNumber);
        formData.append('expiration_date', expMonth + expYear);
        formData.append('cvv', cvv);
        formData.append('amount', totalAmount);
        
        // Show loading state
        const submitButton = this;
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        
        // Send the payment request
        fetch('conv_process_pymt.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Server response:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            if (data.success) {
                // Payment successful
                alert('Payment processed successfully!');
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal' + leadId));
                modal.hide();
                // Optionally refresh the page or update UI
                window.location.reload();
            } else {
                // Payment failed
                alert(data.message || 'Payment processing failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing the payment: ' + error.message);
        })
        .finally(() => {
            // Restore button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        });
    });
});

function validatePaymentForm(leadId) {
    // Add your validation logic here
    return true;
}

// Function to format decimal input
function formatDecimalInput(leadId) {
    const numberInputs = [
        document.getElementById('pricingCode' + leadId),
        document.getElementById('certificationFee' + leadId),
        document.getElementById('adminFee' + leadId),
        document.getElementById('appliedCredit' + leadId)
    ];
    
    numberInputs.forEach(input => {
        if (!input) return;
        
        // Clear field on focus to allow fresh entry
        input.addEventListener('focus', function() {
            this.value = '';
        });
        
        input.addEventListener('input', function() {
            // Get raw value with no decimal
            let rawValue = this.value.replace(/\./g, '');
            
            // If it's empty, keep it that way to show placeholder
            if (rawValue === '') {
                calculatePaymentTotals(leadId);
                return;
            }
            
            // Convert to a number to remove leading zeros
            let numValue = parseInt(rawValue, 10);
            
            // Format with decimal before last 2 digits
            if (rawValue.length > 2) {
                let whole = Math.floor(numValue / 100);
                let decimal = numValue % 100;
                // Pad decimal with leading zero if needed
                decimal = decimal.toString().padStart(2, '0');
                this.value = whole + '.' + decimal;
            } else {
                // Pad for values less than 1
                let padded = numValue.toString().padStart(2, '0');
                this.value = '0.' + padded;
            }
            
            // Update calculations
            calculatePaymentTotals(leadId);
        });
        
        // Handle blur for final formatting
        input.addEventListener('blur', function() {
            if (this.value === '') {
                // Leave empty to show placeholder
                calculatePaymentTotals(leadId);
                return;
            }
            
            // Ensure proper format
            let value = parseFloat(this.value) || 0;
            this.value = value.toFixed(2);
            calculatePaymentTotals(leadId);
        });
    });
}

// Function to calculate totals
function calculatePaymentTotals(leadId) {
    const pricingCode = parseFloat(document.getElementById('pricingCode' + leadId).value || 0);
    const fourWeekRental = pricingCode * 28;
    const certificationFee = parseFloat(document.getElementById('certificationFee' + leadId).value || 0);
    const adminFee = parseFloat(document.getElementById('adminFee' + leadId).value || 0);
    const appliedCredit = parseFloat(document.getElementById('appliedCredit' + leadId).value || 0);
    
    const subtotal = fourWeekRental + certificationFee + adminFee - appliedCredit;
    const salesTax = subtotal * 0.06625; // 6.625% tax rate
    const total = subtotal + salesTax;
    
    // Update form fields
    document.getElementById('fourWeekRental' + leadId).value = fourWeekRental.toFixed(2);
    document.getElementById('subtotal' + leadId).value = subtotal.toFixed(2);
    document.getElementById('salesTax' + leadId).value = salesTax.toFixed(2);
    document.getElementById('displayTotal' + leadId).value = total.toFixed(2);
    
    // Update the hidden amount input for the API call
    document.getElementById('hiddenTotal' + leadId).value = total.toFixed(2);
}
</script>
<?php
}
?>