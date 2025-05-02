<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'auth_check.php';
require_once 'db.php';
require_once 'SystemService.php';

// Initialize SystemService
$systemService = new SystemService();

// Check if user has an active time entry
try {
    $result = $systemService->handleTimeClock([
        'user_id' => $_SESSION['user_id'],
        'action' => 'check_status'
    ]);
    $hasActiveEntry = $result['has_active_entry'] ?? false;
} catch (Exception $e) {
    // Log error but don't show to user
    error_log("Time Clock Error: " . $e->getMessage());
    $hasActiveEntry = false;
}

// Ensure we have a valid user ID
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
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
    <link rel="shortcut icon" type="image/x-icon" href="dashui/assets/images/brand/logo/idnj_logo_small.png" />

    <!-- Color modes -->
    <script src="/dashui/assets/js/vendors/color-modes.js"></script>

    <!-- Libs CSS -->
    <link href="/dashui/assets/libs/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="/dashui/assets/libs/@mdi/font/css/materialdesignicons.min.css" rel="stylesheet" />
    <link href="/dashui/assets/libs/simplebar/dist/simplebar.min.css" rel="stylesheet" />

    <!-- Theme CSS -->
    <link rel="stylesheet" href="/dashui/assets/css/theme.min.css">

    <title>Time Clock | IDNJ</title>
    

</head>

<body>
<?php include 'navigation.php'; ?>

<div id="app-content">
    <div class="app-content-area">
        <div class="container-fluid">
            <div class="content-wrapper">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-12">
                        <!-- Page header -->
                        <div class="d-flex align-items-center mb-5">
                            <div>
                                <h3 class="mb-0 fw-bold">Time Clock</h3>
                                <p class="text-muted mb-0">Location: <?php echo htmlspecialchars($_SESSION['location_name'] ?? 'Unknown'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Clock Display -->
                <div class="row mb-4">
                    <div class="col-lg-6 col-md-8 col-12 mx-auto">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h2 class="mb-0" id="liveClock">Loading...</h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Time Clock Interface -->
                <div class="row">
                    <div class="col-lg-6 col-md-8 col-12 mx-auto">
                        <div class="card">
                            <div class="card-body">
                                <form id="timeClockForm" method="POST" action="process_time_clock.php">
                                    <!-- Clock In/Out Buttons -->
                                    <div class="d-grid gap-2 mb-4">
                                        <button type="button" class="btn btn-success btn-lg" id="clockInBtn" <?php echo $hasActiveEntry ? 'disabled' : ''; ?>>
                                            <i class="bi bi-box-arrow-in-right me-2"></i>Clock In
                                        </button>
                                        <button type="button" class="btn btn-danger btn-lg" id="clockOutBtn" <?php echo !$hasActiveEntry ? 'disabled' : ''; ?>>
                                            <i class="bi bi-box-arrow-right me-2"></i>Clock Out
                                        </button>
                                    </div>

                                    <!-- Additional Time Dropdown -->
                                    <div class="mb-4" id="additionalTimeContainer" style="display: <?php echo $hasActiveEntry ? 'block' : 'none'; ?>">
                                        <label for="additionalTime" class="form-label">Additional Time (minutes)</label>
                                        <select class="form-select form-select-lg" id="additionalTime" name="additional_time">
                                            <option value="0">No additional time</option>
                                            <option value="15">15 minutes</option>
                                            <option value="30">30 minutes</option>
                                            <option value="45">45 minutes</option>
                                            <option value="60">60 minutes</option>
                                            <option value="90">90 minutes</option>
                                            <option value="105">105 minutes</option>
                                            <option value="120">120 minutes</option>
                                        </select>
                                    </div>

                                    <!-- Notes Section -->
                                    <div class="mb-4">
                                        <label for="notes" class="form-label">Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                            placeholder="Enter any additional notes here..."></textarea>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add some custom styles -->
                <style>
                    .card {
                        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
                        border: none;
                    }
                    .btn-lg {
                        padding: 1rem;
                    }
                    @media (max-width: 768px) {
                        .col-lg-6 {
                            padding: 0 15px;
                        }
                    }
                </style>

                <!-- Hidden device detection elements -->
                <div style="display: none;">
                    <div class="d-block d-md-none" id="isMobile">mobile</div>
                    <div class="d-none d-md-block" id="isDesktop">desktop</div>
                </div>

                <!-- Add JavaScript for button interactions -->
                <script>
                    $(document).ready(function() {
                        // Log the hasActiveEntry value
                        console.log('Time Clock State:', {
                            hasActiveEntry: <?php echo json_encode($hasActiveEntry); ?>,
                            userId: <?php echo json_encode($_SESSION['user_id']); ?>,
                            locationName: <?php echo json_encode($_SESSION['location_name'] ?? 'Unknown'); ?>
                        });

                        // Live Clock Function
                        function updateClock() {
                            const now = new Date();
                            const options = { 
                                weekday: 'long', 
                                year: 'numeric', 
                                month: 'long', 
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit',
                                hour12: true
                            };
                            document.getElementById('liveClock').textContent = now.toLocaleDateString('en-US', options);
                        }
                        
                        // Update clock immediately and then every second
                        updateClock();
                        setInterval(updateClock, 1000);

                        // Function to get device type
                        function getDeviceType() {
                            const width = window.innerWidth;
                            console.log('Window width:', width);
                            
                            if (width < 768) {
                                console.log('Detected as: mobile');
                                return 'mobile';
                            } else {
                                console.log('Detected as: desktop');
                                return 'desktop';
                            }
                        }

                        // Handle clock in
                        $('#clockInBtn').click(function() {
                            const deviceType = getDeviceType();
                            console.log('Sending device type for clock in:', deviceType);
                            
                            $.ajax({
                                url: 'time_clock_api.php',
                                method: 'POST',
                                data: {
                                    action: 'clock_in',
                                    notes: $('#notes').val(),
                                    device_type: deviceType
                                },
                                success: function(response) {
                                    if (response.success) {
                                        $('#clockInBtn').prop('disabled', true);
                                        $('#clockOutBtn').prop('disabled', false);
                                        $('#additionalTimeContainer').show();
                                        const now = new Date();
                                        const timeStr = now.toLocaleString('en-US', { 
                                            hour: '2-digit', 
                                            minute: '2-digit',
                                            hour12: true 
                                        });
                                        alert(`${response.full_name} successfully clocked into ${response.location_name} at ${timeStr}`);
                                    } else {
                                        alert('Error: ' + response.message);
                                    }
                                },
                                error: function(xhr, status, error) {
                                    alert('Error: Could not process request. Please try again.');
                                    console.error('AJAX Error:', error);
                                }
                            });
                        });

                        // Handle clock out
                        $('#clockOutBtn').click(function() {
                            const deviceType = getDeviceType();
                            console.log('Sending device type for clock out:', deviceType);
                            
                            $.ajax({
                                url: 'time_clock_api.php',
                                method: 'POST',
                                data: {
                                    action: 'clock_out',
                                    additional_time: $('#additionalTime').val(),
                                    notes: $('#notes').val(),
                                    device_type: deviceType
                                },
                                success: function(response) {
                                    if (response.success) {
                                        $('#clockInBtn').prop('disabled', false);
                                        $('#clockOutBtn').prop('disabled', true);
                                        $('#additionalTimeContainer').hide();
                                        const now = new Date();
                                        const timeStr = now.toLocaleString('en-US', { 
                                            hour: '2-digit', 
                                            minute: '2-digit',
                                            hour12: true 
                                        });
                                        alert(`${response.full_name} successfully clocked out of ${response.location_name} at ${timeStr}`);
                                    } else {
                                        alert('Error: ' + response.message);
                                    }
                                },
                                error: function(xhr, status, error) {
                                    alert('Error: Could not process request. Please try again.');
                                    console.error('AJAX Error:', error);
                                }
                            });
                        });
                    });
                </script>

<?php include 'footer.php'; ?>