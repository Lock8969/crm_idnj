<?php
require_once 'auth_check.php';
include 'db.php';

// Get filters if set
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$shop_filter = isset($_GET['shop']) ? $_GET['shop'] : 'all';

// Track which sections are open
$hs_open = isset($_GET['hs_open']) ? $_GET['hs_open'] : '0';
$cb_open = isset($_GET['cb_open']) ? $_GET['cb_open'] : '0';

// Get locations for dropdown
try {
    $locations_stmt = $pdo->query("SELECT id, location_name FROM locations ORDER BY location_name");
    $locations = $locations_stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching locations: " . $e->getMessage());
    $locations = [];
}

// Fetch HS inventory with filter conditions
$hs_query = "SELECT hs.id, hs.serial_number, hs.status, 
                  c.first_name, c.last_name, c.id as customer_id,
                  l.location_name, l.id as location_id
             FROM hs_inventory hs
             LEFT JOIN client_information c ON hs.customer_id = c.id
             LEFT JOIN locations l ON hs.location_id = l.id
             WHERE 1=1";

// Apply filters to HS query
if ($status_filter == 'active') {
    $hs_query .= " AND hs.customer_id IS NOT NULL";
} else if ($status_filter == 'in_stock') {
    $hs_query .= " AND hs.customer_id IS NULL AND hs.location_id IS NOT NULL";
    
    if ($shop_filter != 'all') {
        $hs_query .= " AND hs.location_id = " . intval($shop_filter);
    }
}

$hs_query .= " ORDER BY hs.serial_number";

// Fetch CB inventory with similar filters
$cb_query = "SELECT cb.id, cb.serial_number, cb.status, 
                 c.first_name, c.last_name, c.id as customer_id,
                 l.location_name, l.id as location_id
             FROM cb_inventory cb
             LEFT JOIN client_information c ON cb.customer_id = c.id
             LEFT JOIN locations l ON cb.location_id = l.id
             WHERE 1=1";

// Apply filters to CB query
if ($status_filter == 'active') {
    $cb_query .= " AND cb.customer_id IS NOT NULL";
} else if ($status_filter == 'in_stock') {
    $cb_query .= " AND cb.customer_id IS NULL AND cb.location_id IS NOT NULL";
    
    if ($shop_filter != 'all') {
        $cb_query .= " AND cb.location_id = " . intval($shop_filter);
    }
}

$cb_query .= " ORDER BY cb.serial_number";

// Execute the queries
try {
    $hs_stmt = $pdo->query($hs_query);
    $hs_inventory = $hs_stmt->fetchAll();
    
    $cb_stmt = $pdo->query($cb_query);
    $cb_inventory = $cb_stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching inventory: " . $e->getMessage());
    $hs_inventory = [];
    $cb_inventory = [];
}

// Export to CSV if requested
if (isset($_GET['export']) && $_GET['export'] == 'hs') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="hs_inventory.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Serial Number', 'Status', 'Customer', 'Location']);
    
    foreach ($hs_inventory as $device) {
        $customer_name = $device['customer_id'] ? $device['first_name'] . ' ' . $device['last_name'] : 'None';
        fputcsv($output, [
            $device['serial_number'],
            $device['status'],
            $customer_name,
            $device['location_name'] ?? 'None'
        ]);
    }
    fclose($output);
    exit;
}

if (isset($_GET['export']) && $_GET['export'] == 'cb') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="cb_inventory.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Serial Number', 'Status', 'Customer', 'Location']);
    
    foreach ($cb_inventory as $device) {
        $customer_name = $device['customer_id'] ? $device['first_name'] . ' ' . $device['last_name'] : 'None';
        fputcsv($output, [
            $device['serial_number'],
            $device['status'],
            $customer_name,
            $device['location_name'] ?? 'None'
        ]);
    }
    fclose($output);
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

    <title>Inventory | IDNJ</title>
</head>

<body>
<?php include 'navigation.php'; ?>

<div id="app-content">
    <div class="app-content-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-12">
                    <!-- Page header -->
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <div>
                            <h3 class="mb-0 fw-bold">Inventory</h3>
                            <p class="text-muted mb-0">Location: <?php echo htmlspecialchars($_SESSION['location_name'] ?? 'Unknown'); ?></p>
                        </div>
                        <a href="#!" class="btn btn-primary">Time Clock</a>
                    </div>
                </div>
            </div>

            <!-- Filter Form -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form id="filterForm" action="" method="GET" class="row g-3 align-items-end">
                                <!-- Hidden fields to track collapse state -->
                                <input type="hidden" name="hs_open" id="hs_open" value="<?php echo $hs_open; ?>">
                                <input type="hidden" name="cb_open" id="cb_open" value="<?php echo $cb_open; ?>">
                                
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="all" <?php echo ($status_filter == 'all') ? 'selected' : ''; ?>>All Status</option>
                                        <option value="active" <?php echo ($status_filter == 'active') ? 'selected' : ''; ?>>Active Only</option>
                                        <option value="in_stock" <?php echo ($status_filter == 'in_stock') ? 'selected' : ''; ?>>In Stock</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="shop" class="form-label">Location</label>
                                    <select class="form-select" id="shop" name="shop" <?php echo ($status_filter != 'in_stock') ? 'disabled' : ''; ?>>
                                        <option value="all" <?php echo ($shop_filter == 'all') ? 'selected' : ''; ?>>All Locations</option>
                                        <?php foreach ($locations as $location): ?>
                                            <option value="<?php echo $location['id']; ?>" 
                                                <?php echo ($shop_filter == $location['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($location['location_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- HS Inventory Card -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <a class="text-inherit" data-bs-toggle="collapse" href="#hsInventoryCollapse" role="button" aria-expanded="<?php echo ($hs_open == '1') ? 'true' : 'false'; ?>" aria-controls="hsInventoryCollapse">
                                    <i class="bi bi-chevron-down me-2"></i>Handsets (<?php echo count($hs_inventory); ?>)
                                </a>
                            </h5>
                            <div class="d-flex align-items-center">
                                <form class="me-3">
                                    <div class="input-group">
                                        <input type="search" class="form-control" placeholder="Search Handsets" id="hsSearch">
                                        <button class="btn btn-outline-secondary" type="button">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </form>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="hsActionDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="hsActionDropdown">
                                        <li><a class="dropdown-item" href="add_hs.php">Add New Handset</a></li>
                                        <li><a class="dropdown-item" href="?export=hs<?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?><?php echo isset($_GET['shop']) ? '&shop=' . $_GET['shop'] : ''; ?>">Export to CSV</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="collapse <?php echo ($hs_open == '1') ? 'show' : ''; ?>" id="hsInventoryCollapse">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table mb-0" id="hsInventoryTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Serial Number</th>
                                                <th>Customer</th>
                                                <th>Location</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($hs_inventory)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No handsets found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($hs_inventory as $device): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($device['serial_number']); ?></td>
                                                        <td>
                                                            <?php if ($device['customer_id']): ?>
                                                                <a href="client_detail.php?id=<?php echo $device['customer_id']; ?>">
                                                                    <?php echo htmlspecialchars($device['first_name'] . ' ' . $device['last_name']); ?>
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="text-muted">None</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($device['location_name'] ?? 'None'); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo ($device['status'] == 'ASSIGNED') ? 'success' : 
                                                                    (($device['status'] == 'IN_STOCK') ? 'primary' : 
                                                                    (($device['status'] == 'MAINTENANCE') ? 'warning' : 'secondary')); 
                                                            ?>">
                                                                <?php echo htmlspecialchars($device['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="hs_detail.php?id=<?php echo $device['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" data-bs-title="History">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <a href="hs_edit.php?id=<?php echo $device['id']; ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" data-bs-title="Edit">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
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
            </div>

            <!-- CB Inventory Card -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <a class="text-inherit" data-bs-toggle="collapse" href="#cbInventoryCollapse" role="button" aria-expanded="<?php echo ($cb_open == '1') ? 'true' : 'false'; ?>" aria-controls="cbInventoryCollapse">
                                    <i class="bi bi-chevron-down me-2"></i>Control Boxes (<?php echo count($cb_inventory); ?>)
                                </a>
                            </h5>
                            <div class="d-flex align-items-center">
                                <form class="me-3">
                                    <div class="input-group">
                                        <input type="search" class="form-control" placeholder="Search Control Boxes" id="cbSearch">
                                        <button class="btn btn-outline-secondary" type="button">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </form>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="cbActionDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="cbActionDropdown">
                                        <li><a class="dropdown-item" href="add_cb.php">Add New Control Box</a></li>
                                        <li><a class="dropdown-item" href="?export=cb<?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?><?php echo isset($_GET['shop']) ? '&shop=' . $_GET['shop'] : ''; ?>">Export to CSV</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="collapse <?php echo ($cb_open == '1') ? 'show' : ''; ?>" id="cbInventoryCollapse">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table mb-0" id="cbInventoryTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Serial Number</th>
                                                <th>Customer</th>
                                                <th>Location</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($cb_inventory)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No control boxes found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($cb_inventory as $device): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($device['serial_number']); ?></td>
                                                        <td>
                                                            <?php if ($device['customer_id']): ?>
                                                                <a href="client_detail.php?id=<?php echo $device['customer_id']; ?>">
                                                                    <?php echo htmlspecialchars($device['first_name'] . ' ' . $device['last_name']); ?>
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="text-muted">None</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($device['location_name'] ?? 'None'); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo ($device['status'] == 'ASSIGNED') ? 'success' : 
                                                                    (($device['status'] == 'IN_STOCK') ? 'primary' : 
                                                                    (($device['status'] == 'MAINTENANCE') ? 'warning' : 'secondary')); 
                                                            ?>">
                                                                <?php echo htmlspecialchars($device['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="cb_detail.php?id=<?php echo $device['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" data-bs-title="History">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <a href="cb_edit.php?id=<?php echo $device['id']; ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" data-bs-title="Edit">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
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
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<!-- Client-side search for tables -->
<script>
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            delay: { show: 500, hide: 100 }
        })
    });
    
    // Track collapse state for handsets
    $('#hsInventoryCollapse').on('shown.bs.collapse', function () {
        $('#hs_open').val('1');
    });
    
    $('#hsInventoryCollapse').on('hidden.bs.collapse', function () {
        $('#hs_open').val('0');
    });
    
    // Track collapse state for control boxes
    $('#cbInventoryCollapse').on('shown.bs.collapse', function () {
        $('#cb_open').val('1');
    });
    
    $('#cbInventoryCollapse').on('hidden.bs.collapse', function () {
        $('#cb_open').val('0');
    });
    
    // HS inventory search
    $("#hsSearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#hsInventoryTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    
    // CB inventory search
    $("#cbSearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#cbInventoryTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    
    // Status filter controls location dropdown
    $("#status").change(function() {
        if ($(this).val() === "in_stock") {
            $("#shop").prop("disabled", false);
        } else {
            $("#shop").val("all").prop("disabled", true);
        }
    });
});
</script>

</body>
</html>