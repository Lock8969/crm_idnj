<?php
$page_title = "Client Details | IDNJ";
$page_heading = "Client Information";
include 'header.php';
?>

<body>
    <div class="row row-cols-1 row-cols-md-2 g-4">
        <!-- Left Column (Card with Form) -->
        <div class="col ps-4"> <!-- Adds left padding -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Card title</h5>
                    <!-- Open Form Here -->
                    <form action="update_lead.php" method="POST">
                        <input type="hidden" name="lead_id" value="<?php echo htmlspecialchars($lead['id']); ?>">

                        <div class="row">
                            <!-- Column 1: First Name -->
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label fw-bold fs-5">First Name</label>
                                    <input type="text" class="form-control form-control-lg" id="first_name" name="first_name"
                                           value="<?php echo htmlspecialchars($lead['first_name']); ?>" required>
                                </div>
                            </div>

                            <!-- Column 2: Last Name -->
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label fw-bold fs-5">Last Name</label>
                                    <input type="text" class="form-control form-control-lg" id="last_name" name="last_name"
                                           value="<?php echo htmlspecialchars($lead['last_name']); ?>" required>
                                </div>
                            </div>

                            <!-- Column 3: Phone -->
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="phone_number" class="form-label fw-bold fs-5">Phone</label>
                                    <input type="text" class="form-control form-control-lg" id="phone_number" name="phone_number"
                                           value="<?php echo htmlspecialchars($lead['phone_number']); ?>" required
                                           maxlength="12" oninput="formatPhoneNumber(this)">
                                </div>
                            </div>

                            <!-- Column 4: Email -->
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="email" class="form-label fw-bold fs-5">Email</label>
                                    <input type="email" class="form-control form-control-lg" id="email" name="email"
                                           value="<?php echo htmlspecialchars($lead['email']); ?>" required>
                                </div>
                            </div>
                        </div> <!-- End First Row -->

                        
                            <!-- Full Width Address Field -->
                            <div class="col-md-3">  <!-- Changed col-md-3 to col-md-12 for full width -->
                                <div class="mb-3">
                                    <label for="address1" class="form-label fw-bold fs-5">Address</label>
                                    <input type="text" class="form-control form-control-lg" id="address1" name="address1"
                                           value="<?php echo htmlspecialchars($lead['address1']); ?>" required>
                             <!-- Address2 Field -->
                                <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="address2" class="form-label fw-bold fs-5 mb-0">Address 2</label>
                                    <input type="text" class="form-control form-control-lg mb-0" id="address2" name="address2"
                                           value="<?php echo htmlspecialchars($client['address2']); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <!-- City Field -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="city" class="form-label fw-bold fs-5 mb-0">City</label>
                                    <input type="text" class="form-control form-control-lg mb-0" id="city" name="city"
                                           value="<?php echo htmlspecialchars($client['city']); ?>">
                                </div>
                            </div>
                            <!-- State Field -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="state" class="form-label fw-bold fs-5 mb-0">State</label>
                                    <input type="text" class="form-control form-control-lg mb-0" id="state" name="state"
                                           value="<?php echo htmlspecialchars($client['state']); ?>">
                                </div>
                            </div>
                        
                        <div class="row">
                            <!-- Zip Field -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="zip" class="form-label fw-bold fs-5 mb-0">Zip</label>
                                    <input type="text" class="form-control form-control-lg mb-0" id="zip" name="zip"
                                           value="<?php echo htmlspecialchars($client['zip']); ?>">
                                </div>
                            </div>
                        </div> <!-- End Second Row -->

                        <div class="text-end">
                            <button type="submit" class="btn btn-success btn-lg">Save</button>
                            <a href="leads.php" class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                    <!-- Close Form Here -->
                </div> <!-- End Card Body -->
            </div> <!-- End Card -->
        </div> <!-- End Left Column -->

        <!-- Right Column -->
        <div class="col pe-4"> <!-- Adds right padding -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Card title</h5>
                    <p class="card-text">This is a longer card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>
                </div>
            </div>
        </div>

        <!-- Left Column -->
        <div class="col ps-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Card title</h5>
                    <p class="card-text">This is a longer card with supporting text below as a natural lead-in to additional content.</p>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col pe-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Card title</h5>
                    <p class="card-text">This is a longer card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>
                </div>
            </div>
        </div>
    </div> <!-- End Row -->

    <!-- JavaScript -->
    <script src="/dashui/dist/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/dashui/dist/assets/libs/simplebar/dist/simplebar.min.js"></script>
    <script src="/dashui/dist/assets/js/theme.min.js"></script>
    <script>
        function formatPhoneNumber(input) {
            let value = input.value.replace(/\D/g, ''); // Remove all non-numeric characters

            // Remove leading 1 or +1
            if (value.startsWith("1")) {
                value = value.substring(1);
            }

            // Ensure max 10 digits after cleaning
            value = value.substring(0, 10);

            // Format as 000-000-0000
            if (value.length > 3 && value.length <= 6) {
                value = value.replace(/(\d{3})(\d+)/, '$1-$2');
            } else if (value.length > 6) {
                value = value.replace(/(\d{3})(\d{3})(\d+)/, '$1-$2-$3');
            }

            input.value = value; // Update the input field
        }
    </script>
</body>
</html>
