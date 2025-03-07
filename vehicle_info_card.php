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
$edit_vehicle = isset($_GET['edit_vehicle']) && $_GET['edit_vehicle'] == 1;

// Vehicle info Table
try {
    // Fetch vehicle details from the database
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
    die("An error occurred while fetching vehicle details.");
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

    <title>Vehicle Details | IDNJ</title>

    <style>
        .info-row {
            margin-bottom: 1rem;
        }
        .info-label {
            font-weight: bold;
            font-size: 1rem;
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

<!-- JavaScript to Toggle Edit Mode -->
<script>
function toggleEditMode() {
    const staticView = document.getElementById('static-view');
    const editView = document.getElementById('edit-view');
    staticView.classList.toggle('d-none');
    editView.classList.toggle('d-none');
}

document.addEventListener("DOMContentLoaded", function() {
    const form = document.querySelector("form[action='update_vehicle.php']");
    form.addEventListener("submit", function(event) {
        event.preventDefault(); // Prevent the default form submission

        const formData = new FormData(form);

        fetch("update_vehicle.php", {
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
            document.querySelector(".info-value[data-field='year']").textContent = formData.get("year_id");
            document.querySelector(".info-value[data-field='make']").textContent = formData.get("make_id");
            document.querySelector(".info-value[data-field='model']").textContent = formData.get("model_id");
            document.querySelector(".info-value[data-field='hybrid']").textContent = formData.get("hybrid");
            document.querySelector(".info-value[data-field='start_system']").textContent = formData.get("start_system");
            document.querySelector(".info-value[data-field='notes']").textContent = formData.get("notes");
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
                        Vehicle Information
                    </h5>
                    <button class="btn btn-primary btn-md me-4" onclick="toggleEditMode()">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                </div>
            

                <!-- Card Body -->
                <div id="vehicleInfo">
                    <div class="card-body">
                        <!-- STATIC VIEW -->
                        <div id="static-view">
                            <div class="row info-row">
                                <div class="col-md-6">
                                    <div class="info-label">Year</div>
                                    <div class="info-value" data-field="year"><?php echo isset($vehicle['year_name']) ? htmlspecialchars($vehicle['year_name']) : 'Not specified'; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">Make</div>
                                    <div class="info-value" data-field="make"><?php echo isset($vehicle['make_name']) ? htmlspecialchars($vehicle['make_name']) : 'Not specified'; ?></div>
                                </div>
                            </div>
                            
                            <div class="row info-row">
                                <div class="col-md-6">
                                    <div class="info-label">Model</div>
                                    <div class="info-value" data-field="model"><?php echo isset($vehicle['model_name']) ? htmlspecialchars($vehicle['model_name']) : 'Not specified'; ?></div>
                                </div>
                            </div>
                            
                            <div class="row info-row">
                                <div class="col-md-6">
                                    <div class="info-label">Hybrid</div>
                                    <div class="info-value" data-field="hybrid">
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
                                    <div class="info-value" data-field="start_system"><?php echo !empty($vehicle['start_system']) ? htmlspecialchars($vehicle['start_system']) : 'Not specified'; ?></div>
                                </div>
                            </div>
                            
                            <?php if (!empty($vehicle['notes'])): ?>
                            <div class="row info-row">
                                <div class="col-md-12">
                                    <div class="info-label">Vehicle Notes</div>
                                    <div class="info-value" data-field="notes"><?php echo nl2br(htmlspecialchars($vehicle['notes'])); ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Edit View -->
                        <div id="edit-view" class="d-none">
                            <form action="update_vehicle.php" method="POST">
                                <input type="hidden" name="record_id" value="<?php echo htmlspecialchars($client_id); ?>">
                                <input type="hidden" name="redirect_url" value="vehicle_info_card.php?id=<?php echo htmlspecialchars($client_id); ?>">

                                <div class="row">
                                    <!-- Column 1: Year -->
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
                                    <!-- Column 2: Make -->
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

                                <div class="row">
                                    <!-- Column 1: Model -->
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

                                <div class="row">
                                    <!-- Column 1: Hybrid -->
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
                                    <!-- Column 2: Start System -->
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

                                <div class="row">
                                    <!-- Column 1: Notes -->
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="notes" class="form-label fw-bold fs-5 mb-0">Vehicle Notes</label>
                                            <textarea id="notes" name="notes" class="form-control form-control-lg mb-0" rows="4"><?php echo htmlspecialchars($vehicle['notes']); ?></textarea>
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
        </div> <!-- End Second Column -->

        <!-- ✅ JavaScript ✅ -->
        <!-- Theme JS: Controls DashUI theme settings and layout behavior -->
        <script src="/dashui/assets/js/theme.min.js"></script>
        <!-- Bootstrap JS: Enables Bootstrap functionality (e.g., modals, tooltips, dropdowns) -->
        <script src="/dashui/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
        <!-- SimpleBar JS: Adds custom scrollbar styling for better UI experience -->
        <script src="/dashui/assets/libs/simplebar/dist/simplebar.min.js"></script>

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

