<?php
// -----
// DEVICE CARD COMPONENT
// This component displays and manages device assignments for a client
// Includes both static view and edit mode functionality
// -----

// -----
// DATABASE CONNECTION AND CLIENT DATA FETCHING
// Ensures database connection exists and fetches client information
// -----
if (!isset($client)) {
    include 'db.php';
    
    // Validate client ID from URL
    if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
        die("Invalid Client ID.");
    }
    
    $client_id = $_GET['id'];
    
    // Fetch client details
    $stmt = $pdo->prepare("SELECT * FROM client_information WHERE id = :id");
    $stmt->execute(['id' => $client_id]);
    $client = $stmt->fetch();
    
    if (!$client) {
        die("Client not found.");
    }
}

// -----
// FETCH AVAILABLE DEVICES
// Retrieves unassigned handsets and control boxes from inventory
// -----
$stmt = $pdo->prepare("SELECT * FROM hs_inventory WHERE customer_id IS NULL");
$stmt->execute();
$available_handsets = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM cb_inventory WHERE customer_id IS NULL");
$stmt->execute();
$available_control_boxes = $stmt->fetchAll();

// -----
// FETCH CURRENTLY ASSIGNED DEVICES
// Retrieves devices currently assigned to the client
// -----
$stmt = $pdo->prepare("SELECT * FROM hs_inventory WHERE customer_id = :customer_id");
$stmt->execute(['customer_id' => $client_id]);
$current_handset = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM cb_inventory WHERE customer_id = :customer_id");
$stmt->execute(['customer_id' => $client_id]);
$current_control_box = $stmt->fetch();
?>

<!-- -----
     DEVICE CARD CONTAINER
     Main container for device information display and editing
     ----- -->
<div class="card shadow-sm">
    <!-- -----
         CARD HEADER
         Contains title and edit button
         ----- -->
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="fw-bold fs-3 mb-0">
            Device Information
        </h5>
        <button class="btn btn-primary btn-md me-4" onclick="toggleDeviceEditMode()">
            <i class="bi bi-pencil"></i> Edit
        </button>
    </div>

    <!-- -----
         CARD BODY
         Contains both static and edit views
         ----- -->
    <div id="deviceInfo">
        <div class="card-body">
            <!-- -----
                 STATIC VIEW
                 Displays current device assignments in read-only format
                 ----- -->
            <div id="device-static-view">
                <div class="row info-row">
                    <div class="col-md-6">
                        <div class="info-label">Handset</div>
                        <div class="info-value" data-field="handset">
                            <?php echo $current_handset ? htmlspecialchars($current_handset['serial_number']) : 'N/A'; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Control Box</div>
                        <div class="info-value" data-field="control_box">
                            <?php echo $current_control_box ? htmlspecialchars($current_control_box['serial_number']) : 'N/A'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- -----
                 EDIT VIEW
                 Form for updating device assignments with dynamic search
                 ----- -->
            <div id="device-edit-view" class="d-none">
                <form action="update_device.php" method="POST">
                    <input type="hidden" name="record_id" value="<?php echo htmlspecialchars($client['id']); ?>">
                    <input type="hidden" name="redirect_url" value="client_detail.php?id=<?php echo htmlspecialchars($client['id']); ?>">
                    <input type="hidden" name="handset_id" id="handset_id" value="">
                    <input type="hidden" name="control_box_id" id="control_box_id" value="">

                    <div class="row">
                        <!-- -----
                             HANDSET SELECTION
                             Dynamic search input for handset assignment
                             ----- -->
                        <div class="col-md-6">
                            <div class="mb-3 position-relative">
                                <label for="handsetInput" class="form-label fw-bold fs-5 mb-0">Handset</label>
                                <input type="text" 
                                       class="form-control form-control-lg mb-0" 
                                       id="handsetInput" 
                                       placeholder="Search and select handset..."
                                       value="<?php echo $current_handset ? htmlspecialchars($current_handset['serial_number']) : ''; ?>"
                                       autocomplete="off">
                                <div id="handsetResults" class="dropdown-menu w-100" style="display: none; position: absolute; width: calc(100% - 2px); left: 1px; right: 1px;"></div>
                            </div>
                        </div>
                        <!-- -----
                             CONTROL BOX SELECTION
                             Dynamic search input for control box assignment
                             ----- -->
                        <div class="col-md-6">
                            <div class="mb-3 position-relative">
                                <label for="controlBoxInput" class="form-label fw-bold fs-5 mb-0">Control Box</label>
                                <input type="text" 
                                       class="form-control form-control-lg mb-0" 
                                       id="controlBoxInput" 
                                       placeholder="Search and select control box..."
                                       value="<?php echo $current_control_box ? htmlspecialchars($current_control_box['serial_number']) : ''; ?>"
                                       autocomplete="off">
                                <div id="controlBoxResults" class="dropdown-menu w-100" style="display: none; position: absolute; width: calc(100% - 2px); left: 1px; right: 1px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- -----
                         FORM BUTTONS
                         Save and cancel actions
                         ----- -->
                    <div class="text-end">
                        <button type="submit" class="btn btn-success btn-lg">Save</button>
                        <button type="button" class="btn btn-secondary btn-lg" onclick="window.location.reload()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- -----
     JAVASCRIPT FUNCTIONALITY
     Handles dynamic search, keyboard navigation, and form interactions
     ----- -->
<script type="text/javascript">
// -----
// INITIALIZATION
// Sets up event listeners and functionality when DOM is loaded
// -----
document.addEventListener('DOMContentLoaded', function() {
    // -----
    // DEVICE SEARCH SETUP
    // Configures dynamic search functionality for both handset and control box inputs
    // -----
    function setupDeviceSearch(inputId, resultsId, type) {
        const input = document.getElementById(inputId);
        const results = document.getElementById(resultsId);
        const hiddenInput = document.getElementById(type + '_id');
        let searchTimeout;
        let currentIndex = -1;

        // -----
        // INPUT EVENT HANDLER
        // Manages real-time search as user types
        // -----
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.trim();

            if (searchTerm.length < 2) {
                results.style.display = 'none';
                currentIndex = -1;
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`get_devices.php?type=${type}&search=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.json())
                    .then(devices => {
                        results.innerHTML = '';
                        currentIndex = -1;
                        if (devices.length > 0) {
                            devices.forEach(device => {
                                const option = document.createElement('a');
                                option.className = 'dropdown-item';
                                option.href = '#';
                                option.textContent = device.serial_number;
                                option.onclick = (e) => {
                                    e.preventDefault();
                                    input.value = device.serial_number;
                                    hiddenInput.value = device.id;
                                    results.style.display = 'none';
                                };
                                results.appendChild(option);
                            });
                            results.style.display = 'block';
                        } else {
                            results.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching devices:', error);
                        results.style.display = 'none';
                        currentIndex = -1;
                    });
            }, 300);
        });

        // -----
        // KEYBOARD NAVIGATION - INPUT FIELD
        // Handles keyboard navigation when focus is in input field
        // -----
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Tab' || e.key === 'ArrowDown') {
                if (results.style.display !== 'none' && results.children.length > 0) {
                    e.preventDefault();
                    currentIndex = 0;
                    results.children[0].focus();
                }
            }
        });

        // -----
        // KEYBOARD NAVIGATION - DROPDOWN
        // Handles keyboard navigation within the dropdown results
        // -----
        results.addEventListener('keydown', function(e) {
            const items = results.children;
            const lastIndex = items.length - 1;

            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    if (currentIndex < lastIndex) {
                        currentIndex++;
                        items[currentIndex].focus();
                    }
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    if (currentIndex > 0) {
                        currentIndex--;
                        items[currentIndex].focus();
                    } else if (currentIndex === 0) {
                        currentIndex = -1;
                        input.focus();
                    }
                    break;
                case 'Enter':
                    e.preventDefault();
                    if (currentIndex >= 0 && currentIndex <= lastIndex) {
                        const device = items[currentIndex];
                        input.value = device.textContent;
                        hiddenInput.value = device.dataset.id;
                        results.style.display = 'none';
                        currentIndex = -1;
                    }
                    break;
                case 'Escape':
                    e.preventDefault();
                    results.style.display = 'none';
                    currentIndex = -1;
                    input.focus();
                    break;
                case 'Tab':
                    e.preventDefault();
                    const nextIndex = e.shiftKey ? currentIndex - 1 : currentIndex + 1;
                    
                    if (nextIndex >= 0 && nextIndex <= lastIndex) {
                        currentIndex = nextIndex;
                        items[currentIndex].focus();
                    } else if (nextIndex < 0) {
                        currentIndex = -1;
                        input.focus();
                    } else {
                        const nextInput = type === 'handset' ? document.getElementById('controlBoxInput') : null;
                        if (nextInput) {
                            nextInput.focus();
                        }
                    }
                    break;
            }
        });

        // -----
        // CLICK OUTSIDE HANDLER
        // Closes dropdown when clicking outside the input and results
        // -----
        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !results.contains(e.target)) {
                results.style.display = 'none';
                currentIndex = -1;
            }
        });
    }

    // -----
    // INITIALIZE SEARCH FUNCTIONALITY
    // Sets up search for both handset and control box inputs
    // -----
    setupDeviceSearch('handsetInput', 'handsetResults', 'handset');
    setupDeviceSearch('controlBoxInput', 'controlBoxResults', 'control_box');
});
</script> 