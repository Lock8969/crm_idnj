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
                <form id="paymentForm<?php echo htmlspecialchars($lead['id']); ?>">
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
                        <button type="button" class="btn btn-primary" id="processPaymentBtn<?php echo htmlspecialchars($lead['id']); ?>">Process Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Main initialization script
document.addEventListener('DOMContentLoaded', function() {
    const leadId = '<?php echo htmlspecialchars($lead['id']); ?>';
    const paymentModal = document.getElementById('paymentModal' + leadId);
    
    // Only initialize when the modal is actually being shown
    paymentModal.addEventListener('show.bs.modal', function (event) {
        console.log('Payment Modal Opening for Lead ID:', leadId);
        
        // Get lead-specific data from previous modal
        const storageKey = 'leadConversionData_' + leadId;
        console.log('Payment Modal - Looking for storage key:', storageKey);
        
        const storedData = sessionStorage.getItem(storageKey);
        console.log('Payment Modal - Retrieved stored data:', storedData);
        
        if (storedData) {
            const leadData = JSON.parse(storedData);
            console.log('Payment Modal - Parsed lead data:', leadData);
            
            // Pre-populate card holder names from stored data
            if (leadData.first_name) {
                console.log('Setting first name to:', leadData.first_name);
                document.getElementById('cardFirstName' + leadId).value = leadData.first_name;
            }
            if (leadData.last_name) {
                console.log('Setting last name to:', leadData.last_name);
                document.getElementById('cardLastName' + leadId).value = leadData.last_name;
            }
        } else {
            console.log('No stored data found for lead ' + leadId);
        }
    });

    // Initialize decimal formatting and calculations
    formatDecimalInput(leadId);
    calculatePaymentTotals(leadId);
});
</script>

<script>
// Card number formatting script
document.addEventListener('DOMContentLoaded', function() {
    const leadId = '<?php echo htmlspecialchars($lead['id']); ?>';
    
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
});
</script>

<script>
// CVV formatting script
document.addEventListener('DOMContentLoaded', function() {
    const leadId = '<?php echo htmlspecialchars($lead['id']); ?>';
    
    // Format CVV input
    document.getElementById('cvv' + leadId).addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '');
    });
});
</script>

<script>
// Payment processing script
document.addEventListener('DOMContentLoaded', function() {
    const leadId = '<?php echo htmlspecialchars($lead['id']); ?>';
    
    // Prevent multiple event listener registration
    const buttonId = 'processPaymentBtn' + leadId;
    const paymentButton = document.getElementById(buttonId);
    
    // Check if we've already attached the listener to this button
    if (paymentButton && !paymentButton.hasAttribute('data-listener-attached')) {
        console.log('Attaching payment event listener for lead:', leadId);
        
        // Mark the button as having a listener attached
        paymentButton.setAttribute('data-listener-attached', 'true');
        
        // Handle Process Payment button click
        paymentButton.addEventListener('click', function(e) {
            console.log('Payment process started for lead:', leadId, '- Time:', new Date().toISOString());
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

            // Validate expiration date
            const currentDate = new Date();
            const currentYear = currentDate.getFullYear();
            const currentMonth = currentDate.getMonth() + 1;
            const selectedYear = parseInt(expYear);
            const selectedMonth = parseInt(expMonth);

            if (selectedYear < currentYear || (selectedYear === currentYear && selectedMonth < currentMonth)) {
                alert('Card has expired');
                return;
            }
            
            // Create form data
            const formData = new FormData();
            formData.append('first_name', firstName);
            formData.append('last_name', lastName);
            formData.append('card_number', cardNumber);
            // Format expiration date as MMYY for Authorize.net
            const expirationDate = expMonth + expYear.substring(2); // Get last 2 digits of year
            formData.append('expiration_date', expirationDate);
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
                // Get the response text
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
                console.log('Authorize.net full response:', data);
                
                // Check for success based on Authorize.net's response format
                // responseCode = 1 means Approved
                if (data.transactionResponse && 
                    data.transactionResponse.responseCode == '1') {
                    
                    // Payment successful
                    const transId = data.transactionResponse.transId;
                    
                    // Get existing stored data
                    const storageKey = 'leadConversionData_' + leadId;
                    const storedData = sessionStorage.getItem(storageKey);
                    let leadData = storedData ? JSON.parse(storedData) : {};
                    
                    // Add transaction data to lead data
                    leadData.transaction = {
                        id: transId,
                        amount: totalAmount,
                        status: 'success',
                        timestamp: new Date().toISOString(),
                        response: data.transactionResponse
                    };
                    
                    // Add pricing code to lead data
                    const pricingCode = document.getElementById('pricingCode' + leadId).value;
                    leadData.pricing_code = pricingCode;
                    
                    // Add additional fee fields
                    leadData.certification_fee = document.getElementById('certificationFee' + leadId).value;
                    leadData.admin_fee = document.getElementById('adminFee' + leadId).value;
                    leadData.applied_credit = document.getElementById('appliedCredit' + leadId).value;
                    leadData.sales_tax = document.getElementById('salesTax' + leadId).value;
                    
                    // Store updated data back
                    sessionStorage.setItem(storageKey, JSON.stringify(leadData));
                    
                    // Trigger the client creation process
                    createClientFromStoredData(leadId);
                    
                    alert('Payment processed successfully! Transaction ID: ' + transId);
                    
                    // Replace Process Payment button with Next button
                    const submitButton = this;
                    const newButton = document.createElement('button');
                    newButton.type = 'button';
                    newButton.className = 'btn btn-primary';
                    newButton.id = 'nextVehicleBtn' + leadId;
                    newButton.innerHTML = 'Next: Update Vehicle Information';
                    
                    // Add click handler for the new button
                    newButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        // Close current modal
                        const currentModal = bootstrap.Modal.getInstance(document.getElementById('paymentModal' + leadId));
                        currentModal.hide();
                        
                        // Show vehicle information modal
                        setTimeout(() => {
                            const vehicleModal = new bootstrap.Modal(document.getElementById('vehicleInfoModal' + leadId));
                            vehicleModal.show();
                        }, 150);
                    });
                    
                    // Replace the old button with the new one
                    submitButton.parentNode.replaceChild(newButton, submitButton);
                } else {
                    // Payment failed - extract error message
                    let errorMessage = 'Payment processing failed';
                    
                    // Try to get specific error message from Authorize.net
                    if (data.transactionResponse && data.transactionResponse.errors) {
                        errorMessage = data.transactionResponse.errors[0].errorText;
                    } else if (data.messages && data.messages.message) {
                        errorMessage = data.messages.message[0].text;
                    }
                    
                    alert(errorMessage);
                    // Restore button state on failure
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing the payment: ' + error.message);
                // Restore button state on error
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            })
            .finally(() => {
                console.log('Payment process completed for lead:', leadId, '- Time:', new Date().toISOString());
            });
            
            // Prevent form submission as an extra precaution
            return false;
        });
    }
});
</script>

<script>
// Standalone client creation script
function createClientFromStoredData(leadId) {
    console.log('Starting client creation process for lead:', leadId);
    
    // Get the stored data
    const storageKey = 'leadConversionData_' + leadId;
    const storedData = sessionStorage.getItem(storageKey);
    
    if (!storedData) {
        console.error('No stored data found for lead:', leadId);
        return;
    }
    
    try {
        const leadData = JSON.parse(storedData);
        console.log('Retrieved stored data:', JSON.stringify(leadData, null, 2));
        
        // Send the data to create client
        fetch('conv_create_client.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(leadData)
        })
        .then(response => {
            console.log('Server response status:', response.status);
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Raw server response:', text);
                    throw new Error('Invalid JSON response: ' + text);
                }
            });
        })
        .then(result => {
            if (result.success) {
                console.log('Client created successfully with ID:', result.client_id);
                // Add client ID to stored data
                leadData.client_id = result.client_id;
                sessionStorage.setItem(storageKey, JSON.stringify(leadData));
            } else {
                console.error('Error creating client:', result.error);
                alert('Client creation failed: ' + result.error);
            }
        })
        .catch(error => {
            console.error('Error during client creation:', error);
            console.error('Error details:', JSON.stringify(error, null, 2));
            alert('An error occurred while creating the client record');
        });
    } catch (error) {
        console.error('Error parsing stored data:', error);
    }
}
</script>

<script>
// Form validation function
function validatePaymentForm(leadId) {
    // Add your validation logic here
    return true;
}
</script>

<script>
// Decimal input formatting function
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
</script>

<script>
// Payment totals calculation function
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