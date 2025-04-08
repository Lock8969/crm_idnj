<?php
// Only include the database if it's not already included in the parent file
if (!isset($client)) {
    include 'db.php';
    
    // Get Info from DB if not already fetched
    if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
        die("Invalid Client ID.");
    }
    
    $client_id = $_GET['id'];
    
    // Fetch client details from the database
    $stmt = $pdo->prepare("SELECT * FROM client_information WHERE id = :id");
    $stmt->execute(['id' => $client_id]);
    $client = $stmt->fetch();
    
    if (!$client) {
        die("Client not found.");
    }
}
?>

<div class="card shadow-sm">
    <!-- Card Header -->
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="fw-bold fs-3 mb-0">
            Contact Information
        </h5>
        <button class="btn btn-primary btn-md me-4" onclick="toggleEditMode()">
            <i class="bi bi-pencil"></i> Edit
        </button>
    </div>

    <!-- Card Body -->
    <div id="contactInfo">
        <div class="card-body">
            <!-- STATIC VIEW -->
            <div id="static-view">
                <div class="row info-row">
                    <div class="col-md-6">
                        <div class="info-label">First Name</div>
                        <div class="info-value" data-field="first_name"><?php echo htmlspecialchars($client['first_name']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Last Name</div>
                        <div class="info-value" data-field="last_name"><?php echo htmlspecialchars($client['last_name']); ?></div>
                    </div>
                </div>
                
                <div class="row info-row">
                    <div class="col-md-6">
                        <div class="info-label">Date of Birth</div>
                        <div class="info-value" data-field="dob"><?php echo $client['dob'] ? date('m/d/Y', strtotime($client['dob'])) : 'N/A'; ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Phone</div>
                        <div class="info-value" data-field="phone_number"><?php echo htmlspecialchars($client['phone_number']); ?></div>
                    </div>
                </div>
                
                <div class="row info-row">
                    <div class="col-md-6">
                        <div class="info-label">Email</div>
                        <div class="info-value" data-field="email"><?php echo htmlspecialchars($client['email']); ?></div>
                    </div>
                </div>
                
                <div class="row info-row">
                    <div class="col-md-6">
                        <div class="info-label">Address</div>
                        <div class="info-value" data-field="address1"><?php echo htmlspecialchars($client['address1']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Address 2</div>
                        <div class="info-value" data-field="address2"><?php echo htmlspecialchars($client['address2'] ?: 'N/A'); ?></div>
                    </div>
                </div>
                
                <div class="row info-row">
                    <div class="col-md-6">
                        <div class="info-label">City</div>
                        <div class="info-value" data-field="city"><?php echo htmlspecialchars($client['city'] ?: 'N/A'); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">State</div>
                        <div class="info-value" data-field="state"><?php echo htmlspecialchars($client['state'] ?: 'N/A'); ?></div>
                    </div>
                </div>
                
                <div class="row info-row">
                    <div class="col-md-6">
                        <div class="info-label">Zip</div>
                        <div class="info-value" data-field="zip"><?php echo htmlspecialchars($client['zip'] ?: 'N/A'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Edit View -->
            <div id="edit-view" class="d-none">
                <form action="update_client.php" method="POST">
                    <input type="hidden" name="record_id" value="<?php echo htmlspecialchars($client['id']); ?>">
                    <input type="hidden" name="redirect_url" value="client_detail.php?id=<?php echo htmlspecialchars($client['id']); ?>">

                    <!-- Form content (unchanged) -->
                    <div class="row">
                        <!-- Column 1: First Name -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label fw-bold fs-5 mb-0">First Name</label>
                                <input type="text" class="form-control form-control-lg mb-0" id="first_name" name="first_name"
                                    value="<?php echo htmlspecialchars($client['first_name']); ?>" required
                                    oninput="capitalizeWords(this)" style="text-transform: capitalize;">
                            </div>
                        </div>
                        <!-- Column 2: Last Name -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label fw-bold fs-5 mb-0">Last Name</label>
                                <input type="text" class="form-control form-control-lg mb-0" id="last_name" name="last_name"
                                    value="<?php echo htmlspecialchars($client['last_name']); ?>" required
                                    oninput="capitalizeWords(this)" style="text-transform: capitalize;">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Column 1: Date of Birth -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="dob" class="form-label fw-bold fs-5 mb-0">Date of Birth</label>
                                <input type="date" class="form-control form-control-lg mb-0" id="dob" name="dob"
                                    value="<?php echo $client['dob'] ? date('Y-m-d', strtotime($client['dob'])) : ''; ?>">
                            </div>
                        </div>
                        <!-- Column 2: Phone -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone_number" class="form-label fw-bold fs-5 mb-0">Phone</label>
                                <input type="text" class="form-control form-control-lg mb-0" id="phone_number" name="phone_number"
                                    value="<?php echo htmlspecialchars($client['phone_number']); ?>" required
                                    maxlength="12" oninput="formatPhoneNumber(this)">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <!-- Column 1: Email -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold fs-5 mb-0">Email</label>
                                <input type="email" class="form-control form-control-lg mb-0" id="email" name="email"
                                    value="<?php echo htmlspecialchars($client['email']); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <!-- Column 1 Address Field -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="address1" class="form-label fw-bold fs-5 mb-0">Address</label>
                                <input type="text" class="form-control form-control-lg mb-0" id="address1" name="address1"
                                    value="<?php echo htmlspecialchars($client['address1']); ?>">
                            </div>
                        </div>
                        <!-- Column 2 Address2 Field -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="address2" class="form-label fw-bold fs-5 mb-0">Address 2</label>
                                <input type="text" class="form-control form-control-lg mb-0" id="address2" name="address2"
                                    value="<?php echo htmlspecialchars($client['address2']); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <!-- Column 1: City Field -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="city" class="form-label fw-bold fs-5 mb-0">City</label>
                                <input type="text" class="form-control form-control-lg mb-0" id="city" name="city"
                                    value="<?php echo htmlspecialchars($client['city']); ?>" oninput="capitalizeWords(this)" style="text-transform: capitalize;">
                            </div>
                        </div>

                        <!-- Column 2: State Field -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="state" class="form-label fw-bold fs-5 mb-0">State</label>
                                <?php include 'states.php'; ?> <!-- LOAD the states array from states.php page -->
                                <select id="state" name="state" class="form-control form-control-lg mb-0">
                                    <option value="">Select State</option>
                                    <?php
                                    foreach ($states as $abbr => $name) {
                                        $selected = ($client['state'] == $abbr) ? 'selected' : '';
                                        echo "<option value='{$abbr}' {$selected}>{$abbr}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div> <!-- End Row -->

                    <div class="row">
                        <!-- Column 1 Zip Field -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="zip" class="form-label fw-bold fs-5 mb-0">Zip</label>
                                <input type="text" class="form-control form-control-lg mb-0" id="zip" name="zip"
                                    value="<?php echo htmlspecialchars($client['zip']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-success btn-lg">Save</button>
                        <button type="button" class="btn btn-secondary btn-lg" onclick="toggleEditMode()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>