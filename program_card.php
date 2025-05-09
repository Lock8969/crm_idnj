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
        <div class="card-body" style="min-height: 400px;">
            <!-- STATIC VIEW -->
            <div id="program-static-view" class="h-100 d-flex flex-column justify-content-between">
                <!-- Row 1 -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <div class="form-label fw-bold fs-5 mb-0">Install Date</div>
                            <div class="form-control form-control-lg mb-0 bg-light" data-field="install_on">
                                <?php echo $client['install_on'] ? date('m/d/Y', strtotime($client['install_on'])) : 'Not scheduled'; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <div class="form-label fw-bold fs-5 mb-0">Offense Number</div>
                            <div class="form-control form-control-lg mb-0 bg-light" data-field="offense_number"><?php echo htmlspecialchars($client['offense_number'] ?: 'N/A'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <div class="form-label fw-bold fs-5 mb-0">Status</div>
                            <div class="form-control form-control-lg mb-0 bg-light" data-field="status"><?php echo htmlspecialchars($client['status']); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Row 2 -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <div class="form-label fw-bold fs-5 mb-0">Calibration Interval</div>
                            <div class="form-control form-control-lg mb-0 bg-light" data-field="calibration_interval"><?php echo htmlspecialchars($client['calibration_interval'] ?: 'N/A'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <div class="form-label fw-bold fs-5 mb-0">Law Type</div>
                            <div class="form-control form-control-lg mb-0 bg-light" data-field="law_type"><?php echo htmlspecialchars($client['law_type'] ?: 'N/A'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <div class="form-label fw-bold fs-5 mb-0">Out of State</div>
                            <div class="form-control form-control-lg mb-0 bg-light" data-field="out_of_state"><?php echo $client['out_of_state'] ? 'Yes' : 'No'; ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Row 3 -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <div class="form-label fw-bold fs-5 mb-0">Arresting Municipality</div>
                            <div class="form-control form-control-lg mb-0 bg-light" data-field="arresting_municipality">
                                <?php 
                                if ($client['arresting_municipality']) {
                                    $stmt = $pdo->prepare("SELECT township, municipal_code FROM municipality WHERE id = :id");
                                    $stmt->execute(['id' => $client['arresting_municipality']]);
                                    $municipality = $stmt->fetch();
                                    echo htmlspecialchars($municipality['township'] . ' (' . $municipality['municipal_code'] . ')');
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <div class="form-label fw-bold fs-5 mb-0">Offense Date</div>
                            <div class="form-control form-control-lg mb-0 bg-light" data-field="offense_date"><?php echo $client['offense_date'] ? date('m/d/Y', strtotime($client['offense_date'])) : 'N/A'; ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <div class="form-label fw-bold fs-5 mb-0">Driver's License State</div>
                            <div class="form-control form-control-lg mb-0 bg-light" data-field="dl_state"><?php echo htmlspecialchars($client['dl_state'] ?: 'N/A'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Row 4 -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <div class="form-label fw-bold fs-5 mb-0">Price Code</div>
                            <div class="form-control form-control-lg mb-0 bg-light" data-field="price_code">
                                <?php echo $client['price_code'] ? '$' . number_format($client['price_code'], 2) : 'N/A'; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-center">
                        <?php if (!empty($client['install_comments'])): ?>
                        <button type="button" class="btn btn-primary" 
                            data-bs-toggle="popover" 
                            data-bs-placement="bottom"
                            title="Install Comments" 
                            data-bs-content="<?php echo htmlspecialchars($client['install_comments']); ?>">
                            <i class="bi bi-card-text me-2"></i>Install Comments
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <!-- Empty column for alignment -->
                    </div>
                </div>
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

                    <!-- Row 1 -->
                    <div class="row">
                        <!-- Column 1: Install Date -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="install_on" class="form-label fw-bold fs-5 mb-0">Install Date</label>
                                <input type="date" class="form-control form-control-lg mb-0" id="install_on" name="install_on"
                                    value="<?php echo $client['install_on'] ?: ''; ?>">
                            </div>
                        </div>
                        <!-- Column 2: Offense Number -->
                        <div class="col-md-4">
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
                        <!-- Column 3: Status (Read Only) -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold fs-5 mb-0">Status</label>
                                <div class="form-control-lg mb-0 fw-bold">
                                    <?php echo htmlspecialchars($client['status']); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2 -->
                    <div class="row">
                        <!-- Column 1: Calibration Interval -->
                        <div class="col-md-4">
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
                        <!-- Column 2: Law Type -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="law_type" class="form-label fw-bold fs-5 mb-0">Law Type</label>
                                <select class="form-control form-control-lg mb-0" id="law_type" name="law_type">
                                    <option value="">Select Law Type</option>
                                    <option value="old law" <?php echo $client['law_type'] == 'old law' ? 'selected' : ''; ?>>Old Law</option>
                                    <option value="new law" <?php echo $client['law_type'] == 'new law' ? 'selected' : ''; ?>>New Law</option>
                                </select>
                            </div>
                        </div>
                        <!-- Column 3: Out of State -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="out_of_state" class="form-label fw-bold fs-5 mb-0">Out of State</label>
                                <select class="form-control form-control-lg mb-0" id="out_of_state" name="out_of_state">
                                    <option value="0" <?php echo $client['out_of_state'] == 0 ? 'selected' : ''; ?>>No</option>
                                    <option value="1" <?php echo $client['out_of_state'] == 1 ? 'selected' : ''; ?>>Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3 -->
                    <div class="row">
                        <!-- Column 1: Arresting Municipality -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="arresting_municipality" class="form-label fw-bold fs-5 mb-0">Arresting Municipality</label>
                                <select class="form-control form-control-lg mb-0" id="arresting_municipality" name="arresting_municipality">
                                    <option value="">Select Municipality</option>
                                    <?php
                                    $stmt = $pdo->query("SELECT id, township, municipal_code FROM municipality ORDER BY township");
                                    while ($municipality = $stmt->fetch()) {
                                        $selected = ($client['arresting_municipality'] == $municipality['id']) ? 'selected' : '';
                                        echo "<option value='{$municipality['id']}' {$selected}>{$municipality['township']} ({$municipality['municipal_code']})</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <!-- Column 2: Offense Date -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="offense_date" class="form-label fw-bold fs-5 mb-0">Offense Date</label>
                                <input type="date" class="form-control form-control-lg mb-0" id="offense_date" name="offense_date"
                                    value="<?php echo $client['offense_date'] ? date('Y-m-d', strtotime($client['offense_date'])) : ''; ?>">
                            </div>
                        </div>
                        <!-- Column 3: Driver's License State -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="dl_state" class="form-label fw-bold fs-5 mb-0">Driver's License State</label>
                                <?php include 'states.php'; ?>
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

                    <!-- Row 4 -->
                    <div class="row">
                        <!-- Column 1: Price Code -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="price_code" class="form-label fw-bold fs-5 mb-0">Price Code</label>
                                <input type="number" step="0.01" class="form-control form-control-lg mb-0" id="price_code" name="price_code"
                                    value="<?php echo htmlspecialchars($client['price_code']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Install Comments -->
                    <div class="row">
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