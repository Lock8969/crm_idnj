<?php
include 'db.php';
include 'navigation.php';

// Get Info from DB
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    error_log("Invalid Client ID: " . $_GET['id']);
    die("Invalid Client ID.");
}

$client_id = $_GET['id'];
error_log("Client ID: " . $client_id);

// Track which sections to edit
$edit_contact = isset($_GET['edit_contact']) && $_GET['edit_contact'] == 1;
$edit_vehicle = isset($_GET['edit_vehicle']) && $_GET['edit_vehicle'] == 1;

// Global edit mode will edit both sections
if (isset($_GET['edit']) && $_GET['edit'] == 1) {
    $edit_contact = true;
    $edit_vehicle = true;
}

// Client info Table
try {
    // Fetch client details from the database
    $stmt = $pdo->prepare("SELECT * FROM client_information WHERE id = :id");
    $stmt->execute(['id' => $client_id]);
    $client = $stmt->fetch();

    if (!$client) {
        error_log("Client not found: " . $client_id);
        die("Client not found.");
    }

    // Vehicle Table
    $stmt = $pdo->prepare("SELECT * FROM vehicle_information WHERE customer_id = :customer_id");
    $stmt->execute(['customer_id' => $client_id]);
    $vehicle = $stmt->fetch();

    // If vehicle doesn't exist, provide an empty array to prevent errors
    if (!$vehicle) {
        $vehicle = [
            'year_id' => null,
            'make_id' => null,
            'model_id' => null,
            'hybrid' => null,
            'start_system' => null,
            'notes' => ''
        ];
    }

    // Get additional information for display
    // Get the vehicle year, make, and model names for display
    if (!empty($vehicle['year_id'])) {
        $stmt = $pdo->prepare("SELECT year FROM vehicle_years WHERE id = :id");
        $stmt->execute(['id' => $vehicle['year_id']]);
        $year_info = $stmt->fetch();
        $vehicle['year_name'] = $year_info ? $year_info['year'] : 'Unknown';
    }

    if (!empty($vehicle['make_id'])) {
        $stmt = $pdo->prepare("SELECT make FROM vehicle_makes WHERE id = :id");
        $stmt->execute(['id' => $vehicle['make_id']]);
        $make_info = $stmt->fetch();
        $vehicle['make_name'] = $make_info ? $make_info['make'] : 'Unknown';
    }

    if (!empty($vehicle['model_id'])) {
        $stmt = $pdo->prepare("SELECT model FROM vehicle_models WHERE id = :id");
        $stmt->execute(['id' => $vehicle['model_id']]);
        $model_info = $stmt->fetch();
        $vehicle['model_name'] = $model_info ? $model_info['model'] : 'Unknown';
    }

} catch (PDOException $e) {
    error_log("Error fetching details: " . $e->getMessage());
    die("An error occurred while fetching client or vehicle details.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Ensure jQuery Loads First -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" data-cfasync="false"></script>

    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="author" content="Codescandy" />

    <!-- Favicon icon -->
    <link rel="shortcut icon" type="image/x-icon" href="dashui/assets/images/favicon/favicon.ico" />

    <!-- Color modes -->
    <script src="/dashui/assets/js/vendors/color-modes.js"></script>

    <!-- Libs CSS -->
    <link href="/dashui/assets/libs/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="/dashui/assets/libs/@mdi/font/css/materialdesignicons.min.css" rel="stylesheet" />
    <link href="/dashui/assets/libs/simplebar/dist/simplebar.min.css" rel="stylesheet" />

    <!-- Theme CSS -->
    <link rel="stylesheet" href="/dashui/assets/css/theme.min.css">

    <title>Client Details | IDNJ</title>
    <style>
        .card-header button.btn-link {
            font-size: 28px !important; /* Forces font size */
            font-weight: bold !important; /* Ensures boldness */
            text-decoration: none; /* Keeps the Bootstrap styling */
            color: black !important;
        }
        .info-row {
            margin-bottom: 1rem;
        }
        .info-label {
            font-weight: bold;
            font-size: 1.1rem;
        }
        .info-value {
            font-size: 1.1rem;
        }
    </style>
</head>



<!--- THIS IS WHERE NAVIGATION ENDS AND PAGE BEGINS --->

<!-- Container fluid -->
<div class="app-content-area">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-12">
                <!-- Page header -->
                <div class="mb-5 d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                    </h3>
                    <?php if (!$edit_contact && !$edit_vehicle): ?>
                        <a href="test.php?id=<?php echo htmlspecialchars($client_id); ?>&edit=1" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit All
                        </a>
                    <?php else: ?>
                        <a href="test.php?id=<?php echo htmlspecialchars($client_id); ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel Editing
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4 px-0">
    <div class="row g-4"> <!-- Bootstrap row to align cards side by side -->

        <!-- Left Column (First Card) - CONTACT INFORMATION -->
        <div class="col-md-6"> <!-- Takes 50% width on medium+ screens -->
            <div class="card shadow-sm">
                <!-- Card Header with Clickable Collapse Button -->
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold fs-1 mb-0">
                        <button class="btn btn-link text-decoration-none p-0 display-6" type="button" data-bs-toggle="collapse" data-bs-target="#contactInfo" aria-expanded="false" aria-controls="contactInfo">
                            Contact Information <span class="toggle-icon fs-5">▼</span>
                        </button>
                    </h5>
                    <?php if (!$edit_contact): ?>
                    <a href="test.php?id=<?php echo htmlspecialchars($client_id); ?>&edit_contact=1" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Collapsible Card Body -->
                <div id="contactInfo" class="collapse show">
                    <div class="card-body">
                        <?php if ($edit_contact): ?>
                            <!-- EDIT MODE: Contact Information Form -->
                            <form action="update_client.php" method="POST">
                                <input type="hidden" name="record_id" value="<?php echo htmlspecialchars($client['id']); ?>">
                                <input type="hidden" name="redirect_url" value="test.php?id=<?php echo htmlspecialchars($client['id']); ?>">

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
                                    <!-- Column 1: Phone -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="phone_number" class="form-label fw-bold fs-5 mb-0">Phone</label>
                                            <input type="text" class="form-control form-control-lg mb-0" id="phone_number" name="phone_number"
                                                value="<?php echo htmlspecialchars($client['phone_number']); ?>" required
                                                maxlength="12" oninput="formatPhoneNumber(this)">
                                        </div>
                                    </div>
                                    <!-- Column 2: Email -->
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
                                                value="<?php echo htmlspecialchars($client['city']); ?>">
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
                                    <a href="test.php?id=<?php echo htmlspecialchars($client_id); ?>" class="btn btn-secondary btn-lg">Cancel</a>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- VIEW MODE: Display Client Information -->
                            <div class="row info-row">
                                <div class="col-md-6">
                                    <div class="info-label">First Name</div>
                                    <div class="info-value"><?php echo htmlspecialchars($client['first_name']); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">Last Name</div>
                                    <div class="info-value"><?php echo htmlspecialchars($client['last_name']); ?></div>
                                </div>
                            </div>
                            
                            <div class="row info-row">
                                <div class="col-md-6">
                                    <div class="info-label">Phone</div>
                                    <div class="info-value"><?php echo htmlspecialchars($client['phone_number']); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">Email</div>
                                    <div class="info-value"><?php echo htmlspecialchars($client['email']); ?></div>
                                </div>
                            </div>
                            
                            <div class="row info-row">
                                <div class="col-md-6">
                                    <div class="info-label">Address</div>
                                    <div class="info-value"><?php echo htmlspecialchars($client['address1']); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">Address 2</div>
                                    <div class="info-value"><?php echo htmlspecialchars($client['address2'] ?: 'N/A'); ?></div>
                                </div>
                            </div>
                            
                            <div class="row info-row">
                                <div class="col-md-6">
                                    <div class="info-label">City</div>
                                    <div class="info-value"><?php echo htmlspecialchars($client['city'] ?: 'N/A'); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">State</div>
                                    <div class="info-value"><?php echo htmlspecialchars($client['state'] ?: 'N/A'); ?></div>
                                </div>
                            </div>
                            
                            <div class="row info-row">
                                <div class="col-md-6">
                                    <div class="info-label">Zip</div>
                                    <div class="info-value"><?php echo htmlspecialchars($client['zip'] ?: 'N/A'); ?></div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <a href="test.php?id=<?php echo htmlspecialchars($client_id); ?>&edit_contact=1" class="btn btn-primary btn-lg">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                            </div>
                        <?php endif; ?>
                    </div> <!-- End Card Body -->
                </div> <!-- End Collapsible Section -->
            </div> <!-- End Card -->
        </div> <!-- End First Column -->

        <!-- Second Card - VEHICLE INFORMATION -->
        <div class="col-md-6"> <!-- Half width -->
            <div class="card shadow-sm">
                <!-- Card Header with Clickable Collapse Button -->
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold fs-1 mb-0">
                        <button class="btn btn-link text-decoration-none p-0 display-6" type="button" data-bs-toggle="collapse" data-bs-target="#vehicleInfo" aria-expanded="false" aria-controls="vehicleInfo">
                            Vehicle Information <span class="toggle-icon fs-5">▼</span>
                        </button>
                    </h5>
                    <?php if (!$edit_vehicle): ?>
                    <a href="test.php?id=<?php echo htmlspecialchars($client_id); ?>&edit_vehicle=1" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Collapsible Card Body -->
                <div id="vehicleInfo" class="collapse show">
                    <div class="card-body">
                        <?php if ($edit_vehicle): ?>
                            <!-- EDIT MODE: Vehicle Information Form -->
                            <form action="update_vehicle.php" method="POST">
                                <input type="hidden" name="record_id" value="<?php echo htmlspecialchars($client_id); ?>">
                                <input type="hidden" name="redirect_url" value="test.php?id=<?php echo htmlspecialchars($client_id); ?>">

                                <!-- Row 1: Year, Make -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="year" class="form-label fw-bold fs-5 mb-0">Year</label>
                                            <select id="year" name="year_id" class="form-control form-control-lg mb-0">
                                                <option value="">Select Year</option>
                                                <?php
                                                $stmt = $pdo->query("SELECT id, year FROM vehicle_years ORDER BY year DESC");
                                                while ($row = $stmt->fetch()) {
                                                    $selected = ($row['id'] == $vehicle['year_id']) ? "selected" : "";
                                                    echo "<option value='{$row['id']}' $selected>{$row['year']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Make Dropdown -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="make" class="form-label fw-bold fs-5 mb-0">Make</label>
                                            <select id="make" name="make_id" class="form-control form-control-lg mb-0">
                                                <option value="">Select Make</option>
                                                <?php
                                                $stmt = $pdo->query("SELECT id, make FROM vehicle_makes ORDER BY make");
                                                while ($row = $stmt->fetch()) {
                                                    $selected = ($row['id'] == $vehicle['make_id']) ? "selected" : "";
                                                    echo "<option value='{$row['id']}' $selected>{$row['make']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Row 2: Model -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="model" class="form-label fw-bold fs-5 mb-0">Model</label>
                                            <select id="model" name="model_id" class="form-control form-control-lg mb-0" <?php echo empty($vehicle['make_id']) ? 'disabled' : ''; ?>>
                                                <option value="">Select Model</option>
                                                <?php
                                                // If a model is already selected, fetch its name
                                                if (!empty($vehicle['model_id'])) {
                                                    $stmt = $pdo->prepare("SELECT id, model FROM vehicle_models WHERE make_id = ? ORDER BY model");
                                                    $stmt->execute([$vehicle['make_id']]);
                                                    while ($row = $stmt->fetch()) {
                                                        $selected = ($row['id'] == $vehicle['model_id']) ? "selected" : "";
                                                        echo "<option value='{$row['id']}' $selected>{$row['model']}</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Row 3: Hybrid, Start System -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="hybrid" class="form-label fw-bold fs-5 mb-0">Hybrid</label>
                                            <select id="hybrid" name="hybrid" class="form-control form-control-lg mb-0">
                                                <option value="" <?php echo ($vehicle['hybrid'] === null) ? "selected" : ""; ?>>Select</option>
                                                <option value="0" <?php echo ($vehicle['hybrid'] == 0) ? "selected" : ""; ?>>No</option>
                                                <option value="1" <?php echo ($vehicle['hybrid'] == 1) ? "selected" : ""; ?>>Yes</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="start_system" class="form-label fw-bold fs-5 mb-0">Start System</label>
                                            <select id="start_system" name="start_system" class="form-control form-control-lg mb-0">
                                                <option value="" <?php echo (is_null($vehicle['start_system'])) ? "selected" : ""; ?>>Select</option>
                                                <option value="Key" <?php echo ($vehicle['start_system'] === "Key") ? "selected" : ""; ?>>Key</option>
                                                <option value="Push button" <?php echo ($vehicle['start_system'] === "Push button") ? "selected" : ""; ?>>Push Button</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Row 4: Notes -->
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="notes" class="form-label fw-bold fs-5 mb-0">Vehicle Notes</label>
                                            <textarea id="notes" name="notes" class="form-control form-control-lg mb-0" rows="4"><?php echo htmlspecialchars($vehicle['notes']); ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-success btn-lg">Save</button>
                                    <a href="client_details.php?id=<?php echo htmlspecialchars($client_id); ?>" class="btn btn-secondary btn-lg">Cancel</a>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- VIEW MODE: Display Vehicle Information -->
                            <div class="row info-row">
                                <div class="col-md-6">
                                    <div class="info-label">Year</div>
                                    <div class="info-value"><?php echo isset($vehicle['year_name']) ? htmlspecialchars($vehicle['year_name']) : 'Not specified'; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">Make</div>
                                    <div class="info-value"><?php echo isset($vehicle['make_name']) ? htmlspecialchars($vehicle['make_name']) : 'Not specified'; ?></div>
                                </div>
                            </div>
                            
                            <div class="row info-row">
                                <div class="col-md-6">
                                    <div class="info-label">Model</div>
                                    <div class="info-value"><?php echo isset($vehicle['model_name']) ? htmlspecialchars($vehicle['model_name']) : 'Not specified'; ?></div>
                                </div>
                            </div>
                            
                            <div class="row info-row">
                                <div class="col-md-6">
                                    <div class="info-label">Hybrid</div>
                                    <div class="info-value">
                                        <?php 
                                        if ($vehicle['hybrid'] === null) {
                                            echo 'Not specified';
                                        } elseif ($vehicle['hybrid'] == 1) {
                                            echo 'Yes';
                                        } else {
                                            echo 'No';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">Start System</div>
                                    <div class="info-value"><?php echo !empty($vehicle['start_system']) ? htmlspecialchars($vehicle['start_system']) : 'Not specified'; ?></div>
                                </div>
                            </div>
                            
                            <?php if (!empty($vehicle['notes'])): ?>
                            <div class="row info-row">
                                <div class="col-md-12">
                                    <div class="info-label">Vehicle Notes</div>
                                    <div class="info-value"><?php echo nl2br(htmlspecialchars($vehicle['notes'])); ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="text-end">
                                <a href="test.php?id=<?php echo htmlspecialchars($client_id); ?>&edit_vehicle=1" class="btn btn-primary btn-lg">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                            </div>
                        <?php endif; ?>
                    </div> <!-- End Card Body -->
                </div> <!-- End Collapsible Section -->
            </div> <!-- End Card -->
        </div> <!-- End Second Column -->
    </div> <!-- End Row -->
</div>

 <!-- ✅ JavaScript ✅ -->
  <!-- Theme JS: Controls DashUI theme settings and layout behavior -->
  <script src="/dashui/assets/js/theme.min.js"></script>
<!-- Bootstrap JS: Enables Bootstrap functionality (e.g., modals, tooltips, dropdowns) -->
<script src="/dashui/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<!-- SimpleBar JS: Adds custom scrollbar styling for better UI experience -->
<script src="/dashui/assets/libs/simplebar/dist/simplebar.min.js"></script>

<!-- Capitalize Words: Ensures first letter of each word is capitalized while typing -->
<script>
function capitalizeWords(input) {
    input.value = input.value.replace(/\b\w/g, char => char.toUpperCase());
}
</script>

<!-- Format Phone Number: Formats input as 000-000-0000 and removes leading "+1" or "1" -->
<script>
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '').replace(/^1/, '').slice(0, 10); // Remove non-digits & leading "1"

    input.value = value.replace(/(\d{3})(\d{3})?(\d{4})?/, (m, p1, p2, p3) => 
        [p1, p2, p3].filter(Boolean).join('-')); // Format as 000-000-0000
}
</script>

<!-- JavaScript to Toggle Arrow Direction -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Get all toggle buttons
    const toggleButtons = document.querySelectorAll("[data-bs-toggle='collapse']");
    
    toggleButtons.forEach(function(button) {
        const toggleIcon = button.querySelector(".toggle-icon");
        
        button.addEventListener("click", function() {
            if (toggleIcon.textContent === "▼") {
                toggleIcon.textContent = "▲";
            } else {
                toggleIcon.textContent = "▼";
            }
        });
    });
});
</script>

<!-- AJAX for Model Dropdown -->
<script>
$(document).ready(function(){
    $("#make").change(function(){
        var make_id = $(this).val();
        $("#model").prop("disabled", true).html("<option>Loading...</option>");

        if(make_id) {
            $.ajax({
                url: "get_models.php",
                type: "POST",
                data: { make_id: make_id },
                success: function(response){
                    $("#model").html(response).prop("disabled", false);
                },
                error: function() {
                    alert("Error fetching models. Please try again.");
                }
            });
        } else {
            $("#model").html("<option value=''>Select Model</option>").prop("disabled", true);
        }
    });
});
</script>
</body>
</html>