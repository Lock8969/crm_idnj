<?php
require_once 'auth_check.php';
require_once 'db.php';  // Add database connection
require_once 'LeadsService.php';

// Initialize LeadsService with database connection
$leadsService = new LeadsService($pdo);

// Get search term if provided
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get recent call logs
try {
    $callLogs = $leadsService->getRecentCallLogs($search);
} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $callLogs = [];
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

    <title>Call Log | IDNJ</title>
    

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
                                <h3 class="mb-0 fw-bold">Phone Calls</h3>
                                <p class="text-muted mb-0">Location: <?php echo htmlspecialchars($_SESSION['location_name'] ?? 'Unknown'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Call Logs Table Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <!-- Call Logs Table -->
                                <div class="table-responsive table-card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h4 class="mb-0">Call Log</h4>
                                        
                                        <!-- Center refresh button -->
                                        <button type="button" class="btn btn-primary" onclick="window.location.reload();">
                                            <i data-feather="refresh-cw" class="icon-xs me-1"></i>
                                            Refresh
                                        </button>
                                        
                                        <div class="d-flex align-items-center">
                                            <!-- Search form -->
                                            <form class="d-flex me-3" method="GET">
                                                <input type="search" name="search" class="form-control" placeholder="Search calls" 
                                                       value="<?php echo htmlspecialchars($search); ?>" aria-label="Search" />
                                                <button type="submit" class="btn btn-primary ms-2">
                                                    <i data-feather="search" class="icon-xs"></i>
                                                </button>
                                                <?php if (!empty($search)): ?>
                                                    <a href="leads_incoming.php" class="btn btn-outline-secondary ms-2">
                                                        <i data-feather="x" class="icon-xs"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </form>
                                            
                                            <!-- Dropdown menu -->
                                            <div class="dropdown dropstart">
                                                <a href="#!" class="btn btn-ghost btn-icon btn-sm rounded-circle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i data-feather="more-vertical" class="icon-xs"></i>
                                                </a>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item d-flex align-items-center" href="leads.php">View All Leads</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <table class="table text-nowrap mb-0 table-centered table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date/Time</th>
                                                <th>Caller ID Name</th>
                                                <th>Caller ID</th>
                                                <th>Source</th>
                                                <th>Prior Calls</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($callLogs)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">No call logs found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($callLogs as $log): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars(date('m/d/Y g:i A', strtotime($log['created_at']))); ?></td>
                                                        <td><?php echo htmlspecialchars($log['name'] ?? 'Unknown'); ?></td>
                                                        <td><?php echo htmlspecialchars($log['from_number'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($log['source'] ?? 'Unknown'); ?></td>
                                                        <td><?php echo htmlspecialchars($log['prior_calls'] ?? '0'); ?></td>
                                                        <td>
                                                            <?php if ($log['id']): ?>
                                                                <a href="client_detail.php?id=<?php echo $log['id']; ?>" 
                                                                   class="btn btn-sm btn-outline-primary">
                                                                    <i class="bi bi-person"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

<?php include 'footer.php'; ?>