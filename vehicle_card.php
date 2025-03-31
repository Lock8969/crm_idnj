<?php
require_once 'auth_check.php';
// Only include the database if it's not already included in the parent file
if (!isset($vehicle)) {
    include 'db.php';
    
    // Get Info from DB if not already fetched
    if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
        die("Invalid Client ID.");
    }
    
    $client_id = $_GET['id'];
    
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
        if (!empty($vehicle['year_id'])) {
            $year_id = $vehicle['year_id'];
            $stmt = $pdo->prepare("SELECT year FROM vehicle_years WHERE id = ?");
            $stmt->execute([$year_id]);
            $year_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($year_info && isset($year_info['year'])) {
                $vehicle['year_name'] = $year_info['year'];
            } else {
                // Try to get the raw value to see what's happening
                $stmt = $pdo->prepare("SELECT * FROM vehicle_years WHERE id = ?");
                $stmt->execute([$year_id]);
                $raw_year = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log("Raw year data for ID $year_id: " . json_encode($raw_year));
                
                $vehicle['year_name'] = "Unknown (ID: $year_id)";
            }
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
}
?>

<div class="card shadow-sm">
    <!-- Card Header -->
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="fw-bold fs-3 mb-0">
            Vehicle Information
        </h5>
        <button class="btn btn-primary btn-md me-4" onclick="toggleVehicleEditMode()">
            <i class="bi bi-pencil"></i> Edit
        </button>
    </div>

    <!-- Card Body -->
    <div id="vehicleInfo">
        <div class="card-body">
            <!-- STATIC VIEW -->
            <div id="vehicle-static-view">
                <div class="row info-row">
                    <div class="col-md-6">
                        <div class="info-label">Year</div>
                        <div class="info-value" data-field="year">
                            <?php 
                            if (!empty($vehicle['year_id'])) {
                                // Fetch the year name directly here
                                $year_id = $vehicle['year_id'];
                                try {
                                    $year_stmt = $pdo->prepare("SELECT year FROM vehicle_years WHERE id = ?");
                                    $year_stmt->execute([$year_id]);
                                    $year_row = $year_stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $year_row ? htmlspecialchars($year_row['year']) : "ID: $year_id (not found)";
                                } catch (Exception $e) {
                                    echo "Error fetching year";
                                }
                            } else {
                                echo 'Not specified';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Make</div>
                        <div class="info-value" data-field="make">
                            <?php 
                            if (!empty($vehicle['make_id'])) {
                                $make_id = $vehicle['make_id'];
                                try {
                                    $make_stmt = $pdo->prepare("SELECT make FROM vehicle_makes WHERE id = ?");
                                    $make_stmt->execute([$make_id]);
                                    $make_row = $make_stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $make_row ? htmlspecialchars($make_row['make']) : "ID: $make_id (not found)";
                                } catch (Exception $e) {
                                    echo "Error fetching make";
                                }
                            } else {
                                echo 'Not specified';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="row info-row">
                    <div class="col-md-6">
                        <div class="info-label">Model</div>
                        <div class="info-value" data-field="model">
                            <?php 
                            if (!empty($vehicle['model_id'])) {
                                $model_id = $vehicle['model_id'];
                                try {
                                    $model_stmt = $pdo->prepare("SELECT model FROM vehicle_models WHERE id = ?");
                                    $model_stmt->execute([$model_id]);
                                    $model_row = $model_stmt->fetch(PDO::FETCH_ASSOC);
                                    echo $model_row ? htmlspecialchars($model_row['model']) : "ID: $model_id (not found)";
                                } catch (Exception $e) {
                                    echo "Error fetching model";
                                }
                            } else {
                                echo 'Not specified';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Hybrid</div>
                        <div class="info-value" data-field="hybrid">
                            <?php 
                            // TINYINT(1) values: 1 = Yes, 0 = No, NULL = Not specified
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
                </div>
                
                <div class="row info-row">
                    <div class="col-md-6">
                        <div class="info-label">Start System</div>
                        <div class="info-value" data-field="start_system">
                            <?php 
                            if ($vehicle['start_system'] === null) {
                                echo 'Not specified';
                            } else {
                                echo htmlspecialchars($vehicle['start_system']);
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Start/Stop</div>
                        <div class="info-value" data-field="start_stop">
                            <?php 
                            // TINYINT(1) values: 1 = Yes, 0 = No, NULL = Not specified
                            if ($vehicle['start_stop'] === null) {
                                echo 'Not specified';
                            } elseif ($vehicle['start_stop'] == 1) {
                                echo 'Yes';
                            } else {
                                echo 'No';
                            }
                            ?>
                        </div>
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
            <div id="vehicle-edit-view" class="d-none">
                <form action="update_vehicle.php" method="POST">
                <input type="hidden" name="record_id" value="<?php echo htmlspecialchars($client_id); ?>">
                <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($client_id); ?>">
                <input type="hidden" name="redirect_url" value="client_detail.php?id=<?php echo htmlspecialchars($client_id); ?>">

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
                                    if (!empty($vehicle['model_id']) && !empty($vehicle['make_id'])) {
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
                        <!-- Column 2: Hybrid -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="hybrid" class="form-label fw-bold fs-5 mb-0">Hybrid</label>
                                <!-- TINYINT(1) values: 1 = Yes, 0 = No, NULL = Not specified -->
                                <select id="hybrid" name="hybrid" class="form-control form-control-lg mb-0">
                                    <option value="" <?php echo ($vehicle['hybrid'] === null) ? "selected" : ""; ?>>Select</option>
                                    <option value="0" <?php echo ($vehicle['hybrid'] == 0) ? "selected" : ""; ?>>No</option>
                                    <option value="1" <?php echo ($vehicle['hybrid'] == 1) ? "selected" : ""; ?>>Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Column 1: Start System -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_system" class="form-label fw-bold fs-5 mb-0">Start System</label>
                                <select id="start_system" name="start_system" class="form-control form-control-lg mb-0">
                                    <option value="" <?php echo (is_null($vehicle['start_system'])) ? "selected" : ""; ?>>Select</option>
                                    <option value="Key" <?php echo ($vehicle['start_system'] === "Key") ? "selected" : ""; ?>>Key</option>
                                    <option value="Push Button" <?php echo ($vehicle['start_system'] === "Push Button") ? "selected" : ""; ?>>Push Button</option>
                                </select>
                            </div>
                        </div>
                        <!-- Column 2: Start/Stop -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_stop" class="form-label fw-bold fs-5 mb-0">Start/Stop</label>
                                <!-- TINYINT(1) values: 1 = Yes, 0 = No, NULL = Not specified -->
                                <select id="start_stop" name="start_stop" class="form-control form-control-lg mb-0">
                                    <option value="" <?php echo ($vehicle['start_stop'] === null) ? "selected" : ""; ?>>Select</option>
                                    <option value="0" <?php echo ($vehicle['start_stop'] == 0) ? "selected" : ""; ?>>No</option>
                                    <option value="1" <?php echo ($vehicle['start_stop'] == 1) ? "selected" : ""; ?>>Yes</option>
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
                        <button type="button" class="btn btn-secondary btn-lg" onclick="toggleVehicleEditMode()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>