<?php
require_once 'auth_check.php';
require_once 'db.php';
require_once 'SystemService.php';

// Initialize SystemService
$systemService = new SystemService();

// Get report data if dates are provided
$reportData = [];
if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $reportData = $systemService->getTimeClockReport([
        'start_date' => $_GET['start_date'],
        'end_date' => $_GET['end_date']
    ]);
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

    <title>Time Clock Report | IDNJ</title>
    

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
                                <h3 class="mb-0 fw-bold">Time Clock Report</h3>
                                <p class="text-muted mb-0">Location: <?php echo htmlspecialchars($_SESSION['location_name'] ?? 'Unknown'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Date Range Filter -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="reportFilterForm" class="row g-3" method="GET">
                                    <div class="col-md-4">
                                        <label for="startDate" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="startDate" name="start_date" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="endDate" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="endDate" name="end_date" required>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-search me-2"></i>Generate Report
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Time Clock Report Table -->
                <div class="row">
                    <div class="col-12">
                        <?php if (isset($reportData['success']) && $reportData['success']): ?>
                            <?php if (empty($reportData['data'])): ?>
                                <div class="card">
                                    <div class="card-body">
                                        <p class="text-center mb-0">No time entries found for the selected period</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php 
                                // Group entries by user
                                $userEntries = [];
                                foreach ($reportData['data'] as $entry) {
                                    $userEntries[$entry['user_name']][] = $entry;
                                }
                                // Sort users alphabetically
                                ksort($userEntries);
                                ?>
                                
                                <?php foreach ($userEntries as $userName => $entries): ?>
                                    <?php
                                    // Calculate totals for this user
                                    $totalMinutes = 0;
                                    $totalAdditionalMinutes = 0;
                                    foreach ($entries as $entry) {
                                        // Extract hours and minutes from total_time string (e.g., "8 hours 30 minutes")
                                        preg_match('/(\d+)\s*hours?\s*(\d+)?\s*minutes?/', $entry['total_time'], $matches);
                                        $hours = isset($matches[1]) ? (int)$matches[1] : 0;
                                        $minutes = isset($matches[2]) ? (int)$matches[2] : 0;
                                        $totalMinutes += ($hours * 60) + $minutes;
                                        
                                        // Add additional time
                                        $totalAdditionalMinutes += (int)$entry['additional_time'];
                                    }
                                    
                                    // Calculate total hours and minutes
                                    $totalHours = floor(($totalMinutes + $totalAdditionalMinutes) / 60);
                                    $remainingMinutes = ($totalMinutes + $totalAdditionalMinutes) % 60;
                                    
                                    // Format the totals
                                    $totalTimeStr = floor($totalMinutes / 60) . ' hours ' . ($totalMinutes % 60) . ' minutes';
                                    $totalAdditionalStr = floor($totalAdditionalMinutes / 60) . ' hours ' . ($totalAdditionalMinutes % 60) . ' minutes';
                                    $totalCombinedStr = $totalHours . ' hours ' . $remainingMinutes . ' minutes';

                                    // Format date range
                                    $startDate = new DateTime($_GET['start_date']);
                                    $endDate = new DateTime($_GET['end_date']);
                                    $dateRange = $startDate->format('l F jS Y') . ' to ' . $endDate->format('l F jS Y');
                                    ?>
                                    <div class="card mb-3">
                                        <div class="card-header bg-white" role="button" data-bs-toggle="collapse" 
                                             data-bs-target="#user-<?php echo md5($userName); ?>" 
                                             aria-expanded="false" aria-controls="user-<?php echo md5($userName); ?>">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="mb-0 fs-4"><?php echo htmlspecialchars($userName); ?></h5>
                                                </div>
                                                <div class="text-center flex-grow-1">
                                                    <div class="text-muted fs-4"><?php echo $dateRange; ?></div>
                                                </div>
                                                <div class="text-end">
                                                    <div class="text-muted fs-4">
                                                        <span class="me-3"><strong>Total Time:</strong> <?php echo $totalTimeStr; ?></span>
                                                    </div>
                                                </div>
                                                <i class="bi bi-chevron-down ms-3"></i>
                                            </div>
                                        </div>
                                        <div class="collapse" id="user-<?php echo md5($userName); ?>">
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Location</th>
                                                                <th>Clock In</th>
                                                                <th>Clock Out</th>
                                                                <th>Total Time</th>
                                                                <th>Additional Time</th>
                                                                <th>Status</th>
                                                                <th>Notes</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($entries as $entry): ?>
                                                                <tr>
                                                                    <td><?php 
                                                                        $date = new DateTime(explode(' ', $entry['clock_in_time'])[0]);
                                                                        echo $date->format('m/d/Y'); 
                                                                    ?></td>
                                                                    <td><?php echo htmlspecialchars($entry['location_name']); ?></td>
                                                                    <td><?php 
                                                                        $time = explode(' ', $entry['clock_in_time'])[1] . ' ' . explode(' ', $entry['clock_in_time'])[2];
                                                                        echo date('g:i A', strtotime($time));
                                                                    ?></td>
                                                                    <td><?php 
                                                                        if ($entry['clock_out_time']) {
                                                                            $time = explode(' ', $entry['clock_out_time'])[1] . ' ' . explode(' ', $entry['clock_out_time'])[2];
                                                                            echo date('g:i A', strtotime($time));
                                                                        } else {
                                                                            echo '-';
                                                                        }
                                                                    ?></td>
                                                                    <td><?php echo htmlspecialchars($entry['total_time']); ?></td>
                                                                    <td><?php echo $entry['additional_time']; ?> minutes</td>
                                                                    <td>
                                                                        <span class="badge <?php echo $entry['status'] === 'active' ? 'bg-success' : 'bg-primary'; ?>">
                                                                            <?php echo htmlspecialchars($entry['status_label']); ?>
                                                                        </span>
                                                                    </td>
                                                                    <td><?php echo htmlspecialchars($entry['notes'] ?? '-'); ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                        <tfoot>
                                                            <tr class="table-light">
                                                                <td colspan="4" class="text-end"><strong>Totals:</strong></td>
                                                                <td><strong><?php echo $totalTimeStr; ?></strong></td>
                                                                <td><strong><?php echo $totalAdditionalStr; ?></strong></td>
                                                                <td colspan="2"><strong>Total Hours: <?php echo $totalCombinedStr; ?></strong></td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php elseif (isset($reportData['message'])): ?>
                            <div class="card">
                                <div class="card-body">
                                    <p class="text-center text-danger mb-0">
                                        Error: <?php echo htmlspecialchars($reportData['message']); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Add Flatpickr for date inputs -->
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
                <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

                <script>
                    $(document).ready(function() {
                        // Initialize date pickers
                        flatpickr("#startDate", {
                            dateFormat: "Y-m-d",
                            maxDate: "today",
                            altInput: true,
                            altFormat: "m/d/Y"
                        });
                        
                        flatpickr("#endDate", {
                            dateFormat: "Y-m-d",
                            maxDate: "today",
                            altInput: true,
                            altFormat: "m/d/Y"
                        });

                        // Set default date range (current month)
                        const today = new Date();
                        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                        
                        // Only set default dates if they're not already set
                        if (!$('#startDate').val()) {
                            $('#startDate').val(firstDay.toISOString().split('T')[0]);
                        }
                        if (!$('#endDate').val()) {
                            $('#endDate').val(lastDay.toISOString().split('T')[0]);
                        }
                    });
                </script>

<?php include 'footer.php'; ?>