<?php
require_once 'auth_check.php';

// Database connection
$db_host = 'localhost';
$db_user = 'xpjjbrbbmv';
$db_pass = 'Fs8YyHyejv';
$db_name = 'xpjjbrbbmv';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get count of active users
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE active = 1");
    $stmt->execute();
    $active_users_count = $stmt->fetchColumn();
} catch(PDOException $e) {
    $active_users_count = 0; // Default to 0 if there's an error
    error_log("Database Error: " . $e->getMessage());
}
?><!DOCTYPE html>
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

    <title>Settings | IDNJ</title>
    

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
                                <h3 class="mb-0 fw-bold">System Settings</h3>
                                <p class="text-muted mb-0">Location: <?php echo htmlspecialchars($_SESSION['location_name'] ?? 'Unknown'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Settings Grid -->
                <div class="row g-4">
                    <!-- User Management Card -->
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="icon-shape icon-lg bg-light-primary text-primary rounded-2 me-3">
                                        <i data-feather="users" class="icon-xs"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0">User Management</h4>
                                        <p class="text-muted mb-0">Manage system users and permissions</p>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted me-2">Active Users:</span>
                                        <span class="h5 mb-0"><?php echo htmlspecialchars($active_users_count); ?></span>
                                    </div>
                                    <div>
                                        <a href="user_list.php" class="btn btn-sm btn-outline-primary me-2">Manage</a>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                            <i class="fas fa-user-plus me-2"></i>Add New
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Locations Card -->
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="icon-shape icon-lg bg-light-info text-info rounded-2 me-3">
                                        <i data-feather="map-pin" class="icon-xs"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0">Locations</h4>
                                        <p class="text-muted mb-0">Manage branch locations</p>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted me-2">Total Locations:</span>
                                        <span class="h5 mb-0">5</span>
                                    </div>
                                    <div>
                                        <a href="location_list.php" class="btn btn-sm btn-outline-primary me-2">Manage</a>
                                        <a href="add_location.php" class="btn btn-sm btn-primary">Add New</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Services Card -->
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="icon-shape icon-lg bg-light-success text-success rounded-2 me-3">
                                        <i data-feather="settings" class="icon-xs"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0">Services</h4>
                                        <p class="text-muted mb-0">Manage system services</p>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted me-2">Active Services:</span>
                                        <span class="h5 mb-0">8</span>
                                    </div>
                                    <div>
                                        <a href="service_list.php" class="btn btn-sm btn-outline-primary me-2">Manage</a>
                                        <a href="add_service.php" class="btn btn-sm btn-primary">Add New</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Settings Card -->
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="icon-shape icon-lg bg-light-warning text-warning rounded-2 me-3">
                                        <i data-feather="mail" class="icon-xs"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0">Email Settings</h4>
                                        <p class="text-muted mb-0">Configure email templates and settings</p>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted me-2">Last Updated:</span>
                                        <span class="h5 mb-0">2 days ago</span>
                                    </div>
                                    <div>
                                        <a href="email_settings.php" class="btn btn-sm btn-primary">Configure</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Logs Card -->
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="icon-shape icon-lg bg-light-danger text-danger rounded-2 me-3">
                                        <i data-feather="activity" class="icon-xs"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0">System Logs</h4>
                                        <p class="text-muted mb-0">View system activity and logs</p>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted me-2">New Entries:</span>
                                        <span class="h5 mb-0">24</span>
                                    </div>
                                    <div>
                                        <a href="system_logs.php" class="btn btn-sm btn-primary">View Logs</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Backup Card -->
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="icon-shape icon-lg bg-light-secondary text-secondary rounded-2 me-3">
                                        <i data-feather="cloud" class="icon-xs"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0">Backup</h4>
                                        <p class="text-muted mb-0">Manage system backups</p>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span class="text-muted me-2">Last Backup:</span>
                                        <span class="h5 mb-0">1 day ago</span>
                                    </div>
                                    <div>
                                        <a href="backup_settings.php" class="btn btn-sm btn-outline-primary me-2">Settings</a>
                                        <a href="#" class="btn btn-sm btn-primary">Backup Now</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

<?php include 'footer.php'; ?>

<!-- Add User Modal -->
<?php include 'add_user_modal.php'; ?>