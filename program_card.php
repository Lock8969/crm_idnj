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
            Program Information
        </h5>
        <button class="btn btn-primary btn-md me-4" onclick="toggleProgramEditMode()">
            <i class="bi bi-pencil"></i> Edit
        </button>
    </div>

    <!-- Card Body -->
    <div id="programInfo">
        <div class="card-body">
            <!-- STATIC VIEW -->
            <div id="program-static-view">
                <div class="row info-row">
                    <div class="col-md-6">
                        <div class="info-label">Status</div>
                        <div class="info-value" data-field="status"><?php echo htmlspecialchars($client['status']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Offense Number</div>
                        <div class="info-value" data-field="offense_number"><?php echo htmlspecialchars($client['offense_number'] ?: 'N/A'); ?></div>
                    </div>
                </div>
                
                <div class="row info-row">
                    <div class="col-md-6">
                        <div class="info-label">Install Date</div>
                        <div class="info-value" data-field="install_on">
                            <?php echo $client['install_on'] ? date('m/d/Y', strtotime($client['install_on'])) : 'Not scheduled'; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Driver's License State</div>
                        <div class="info-value" data-field="dl_state"><?php echo htmlspecialchars($client['dl_state'] ?: 'N/A'); ?></div>
                    </div>
                </div>
                
                <div class="row info-row">
                    <div class="col-md-6">
                        <div class="info-label">Law Type</div>
                        <div class="info-value" data-field="law_type"><?php echo htmlspecialchars($client['law_type'] ?: 'N/A'); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Price Code</div>
                        <div class="info-value" data-field="price_code">
                            <?php echo $client['price_code'] ? '$' . number_format($client['price_code'], 2) : 'N/A'; ?>
                        </div>
                    </div>
                </div>

                <div class="row info-row">
                    <div class="col-md-6">
                        <div class="info-label">Calibration Interval</div>
                        <div class="info-value" data-field="calibration_interval"><?php echo htmlspecialchars($client['calibration_interval'] ?: 'N/A'); ?></div>
                    </div>
                </div>

                <?php if (!empty($client['install_comments'])): ?>
                <div class="row info-row">
                    <div class="col-md-12">
                        <div class="info-label">Install Comments</div>
                        <div class="info-value" data-field="install_comments"><?php echo nl2br(htmlspecialchars($client['install_comments'])); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Edit View -->
            <div id="program-edit-view" class="d-none">
                <?php
                /**
                 * =============================================
                 * PROGRAM INFORMATION UPDATE FORM
                 * =============================================
                 * This form submits to update_program.php to update
                 * program-related fields in the client_information table.
                 * 
                 * The form includes:
                 * - Status (read-only)
                 * - Offense Number
                 * - Install Date
                 * - Driver's License State
                 * - Law Type
                 * - Price Code
                 * - Install Comments
                 * =============================================
                 */
                ?>
                <form action="update_program.php" method="POST" onsubmit="
                    const formData = new FormData(this);
                    const data = {};
                    for (let [key, value] of formData.entries()) {
                        data[key] = value;
                    }
                    console.log('Form data being sent:', data);
                ">
                    <input type="hidden" name="record_id" value="<?php echo htmlspecialchars($client['id']); ?>">
                    <input type="hidden" name="redirect_url" value="client_detail.php?id=<?php echo htmlspecialchars($client['id']); ?>">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($client['status']); ?>">

                    <div class="row">
                        <!-- Column 1: Status (Read Only) -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold fs-5 mb-0">Status</label>
                                <div class="form-control-lg mb-0 fw-bold">
                                    <?php echo htmlspecialchars($client['status']); ?>
                                </div>
                            </div>
                        </div>
                        <!-- Column 2: Offense Number -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="offense_number" class="form-label fw-bold fs-5 mb-0">Offense Number</label>
                                <select class="form-control form-control-lg mb-0" id="offense_number" name="offense_number">
                                    <option value="">Select Offense Number</option>
                                    <option value="1" <?php echo $client['offense_number'] == '1' ? 'selected' : ''; ?>>1</option>
                                    <option value="2" <?php echo $client['offense_number'] == '2' ? 'selected' : ''; ?>>2</option>
                                    <option value="3" <?php echo $client['offense_number'] == '3' ? 'selected' : ''; ?>>3</option>
                                    <option value="subsequent" <?php echo $client['offense_number'] == 'subsequent' ? 'selected' : ''; ?>>Subsequent</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Column 1: Install Date -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="install_on" class="form-label fw-bold fs-5 mb-0">Install Date</label>
                                <input type="date" class="form-control form-control-lg mb-0" id="install_on" name="install_on"
                                    value="<?php echo $client['install_on'] ?: ''; ?>">
                            </div>
                        </div>
                        <!-- Column 2: Driver's License State -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="dl_state" class="form-label fw-bold fs-5 mb-0">Driver's License State</label>
                                <?php include 'states.php'; ?> <!-- LOAD the states array from states.php page -->
                                <select id="dl_state" name="dl_state" class="form-control form-control-lg mb-0">
                                    <option value="">Select State</option>
                                    <?php
                                    foreach ($states as $abbr => $name) {
                                        $selected = ($client['dl_state'] == $abbr) ? 'selected' : '';
                                        echo "<option value='{$abbr}' {$selected}>{$abbr}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Column 1: Law Type -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="law_type" class="form-label fw-bold fs-5 mb-0">Law Type</label>
                                <select class="form-control form-control-lg mb-0" id="law_type" name="law_type">
                                    <option value="">Select Law Type</option>
                                    <option value="old law" <?php echo $client['law_type'] == 'old law' ? 'selected' : ''; ?>>Old Law</option>
                                    <option value="new law" <?php echo $client['law_type'] == 'new law' ? 'selected' : ''; ?>>New Law</option>
                                </select>
                            </div>
                        </div>
                        <!-- Column 2: Price Code -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="price_code" class="form-label fw-bold fs-5 mb-0">Price Code</label>
                                <input type="number" step="0.01" class="form-control form-control-lg mb-0" id="price_code" name="price_code"
                                    value="<?php echo htmlspecialchars($client['price_code']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Column 1: Calibration Interval -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="calibration_interval" class="form-label fw-bold fs-5 mb-0">Calibration Interval</label>
                                <select class="form-control form-control-lg mb-0" id="calibration_interval" name="calibration_interval">
                                    <option value="">Select Interval</option>
                                    <option value="30 day" <?php echo $client['calibration_interval'] == '30 day' ? 'selected' : ''; ?>>30 Day</option>
                                    <option value="60 day" <?php echo $client['calibration_interval'] == '60 day' ? 'selected' : ''; ?>>60 Day</option>
                                    <option value="90 day" <?php echo $client['calibration_interval'] == '90 day' ? 'selected' : ''; ?>>90 Day</option>
                                    <option value="No Limit" <?php echo $client['calibration_interval'] == 'No Limit' ? 'selected' : ''; ?>>No Limit</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Install Comments -->
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="install_comments" class="form-label fw-bold fs-5 mb-0">Install Comments</label>
                                <textarea class="form-control form-control-lg mb-0" id="install_comments" name="install_comments" rows="4"><?php echo htmlspecialchars($client['install_comments']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-success btn-lg">Save</button>
                        <button type="button" class="btn btn-secondary btn-lg" onclick="toggleProgramEditMode()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleProgramEditMode() {
    const staticView = document.getElementById('program-static-view');
    const editView = document.getElementById('program-edit-view');
    if (staticView && editView) {
        staticView.classList.toggle('d-none');
        editView.classList.toggle('d-none');
    }
}
</script> 