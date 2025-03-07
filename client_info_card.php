<?php
include 'db.php';

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

        .info-row {
            margin-bottom: 1rem;
        }
        .info-label {
            
            font-size: 1 rem;
        }
        .info-value {
            font-size: 1.2rem;
            font-weight: bold;
        }
    </style>
</head>

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

<!-- JavaScript to Toggle Edit Mode -->
<script>
function toggleEditMode() {
    const staticView = document.getElementById('static-view');
    const editView = document.getElementById('edit-view');
    staticView.classList.toggle('d-none');
    editView.classList.toggle('d-none');
}

document.addEventListener("DOMContentLoaded", function() {
    const form = document.querySelector("form[action='update_client.php']");
    form.addEventListener("submit", function(event) {
        event.preventDefault(); // Prevent the default form submission

        const formData = new FormData(form);

        fetch("update_client.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            // Handle the response data if needed
            console.log(data);
            // Switch back to static view
            toggleEditMode();
            // Optionally, update the static view with the new data
            document.querySelector(".info-value[data-field='first_name']").textContent = formData.get("first_name");
            document.querySelector(".info-value[data-field='last_name']").textContent = formData.get("last_name");
            document.querySelector(".info-value[data-field='phone_number']").textContent = formData.get("phone_number");
            document.querySelector(".info-value[data-field='email']").textContent = formData.get("email");
            document.querySelector(".info-value[data-field='address1']").textContent = formData.get("address1");
            document.querySelector(".info-value[data-field='address2']").textContent = formData.get("address2");
            document.querySelector(".info-value[data-field='city']").textContent = formData.get("city");
            document.querySelector(".info-value[data-field='state']").textContent = formData.get("state");
            document.querySelector(".info-value[data-field='zip']").textContent = formData.get("zip");
        })
        .catch(error => {
            console.error("Error:", error);
        });
    });
});
</script>


<!--- THIS IS WHERE NAVIGATION ENDS AND PAGE BEGINS --->



       
        
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
                    <!-- Rest of card content -->
              

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
                                    <div class="info-label">Phone</div>
                                    <div class="info-value" data-field="phone_number"><?php echo htmlspecialchars($client['phone_number']); ?></div>
                                </div>
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
                                <input type="hidden" name="redirect_url" value="client_info_card.php?id=<?php echo htmlspecialchars($client['id']); ?>">

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
                    </div> <!-- End Card Body -->
                </div> <!-- End Card Body -->
            </div> <!-- End Card -->
        </div> <!-- End First Column -->

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

