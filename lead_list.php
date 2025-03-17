<?php
require_once 'auth_check.php';

// Ensure this file can be included safely in other scripts
if (!defined('INCLUDED_IN_SCRIPT')) {
    define('INCLUDED_IN_SCRIPT', true);

    // Check if database connection exists; if not, include it
    if (!isset($pdo)) {
        include 'db.php';
    }
}

// Get current page from URL parameter
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); // Ensure the page is at least 1
$per_page = 10;
$offset = ($current_page - 1) * $per_page;

// Get search term if provided
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$params = [];

if (!empty($search)) {
    // Added id to the search conditions
    $search_condition = "WHERE id = ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone_number LIKE ?";
    $search_param = "%$search%";
    
    // First try exact ID match, then fuzzy match for other fields
    $params = [$search, $search_param, $search_param, $search_param, $search_param];
}

// Determine if we're on the dashboard page
$is_dashboard = (strpos($_SERVER['REQUEST_URI'], '/dashboard.php') !== false);

// Fetch lead information from the database
try {
    // Get total count for pagination
    $count_query = "SELECT COUNT(*) FROM leads $search_condition";
    $count_stmt = $pdo->prepare($count_query);
    
    if (!empty($search)) {
        $count_stmt->execute($params);
    } else {
        $count_stmt->execute();
    }
    
    $total_leads = $count_stmt->fetchColumn();
    $total_pages = ceil($total_leads / $per_page);
    
    $query = "SELECT id, phone_number, first_name, last_name, email, status, hybrid, start_system, vehicle_notes, year, make, model
          FROM leads 
          WHERE converted_client_id IS NULL 
          $search_condition 
          ORDER BY id DESC 
          LIMIT $offset, $per_page";
    
    $stmt = $pdo->prepare($query);
    
    if (!empty($search)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    
    $leads = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching leads list: " . $e->getMessage());
    $leads = [];
    $total_pages = 1;
}

// Create pagination URL
function get_leads_pagination_url($page, $search) {
    $url = '?';
    if (!empty($search)) {
        $url .= 'search=' . urlencode($search) . '&';
    }
    return $url . 'page=' . $page;
}
?>

<div class="card h-100 mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Leads List</h4>
        
        <?php if (!$is_dashboard): ?>
        <div class="d-flex align-items-center">
            <!-- Search form -->
            <form class="d-flex me-3">
                <input type="search" name="search" class="form-control" placeholder="Search leads" 
                       value="<?php echo htmlspecialchars($search); ?>" aria-label="Search" />
                <button type="submit" class="btn btn-primary ms-2">
                    <i data-feather="search" class="icon-xs"></i>
                </button>
            </form>
            
            <!-- Dropdown menu -->
            <div class="dropdown dropstart">
                <a href="#!" class="btn btn-ghost btn-icon btn-sm rounded-circle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i data-feather="more-vertical" class="icon-xs"></i>
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item d-flex align-items-center" href="add_lead.php">Add New Lead</a></li>
                    <li><a class="dropdown-item d-flex align-items-center" href="leads.php">View All Leads</a></li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="table-responsive table-card">
            <table class="table text-nowrap mb-0 table-centered">
                <thead class="table-light">
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Year/Make/Model</th>
                        <th>Vehicle Options</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leads)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No leads found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leads as $index => $lead): ?>
                            <tr>
                                <td>
                                    <div class="dropdown">
                                       <!-- Replace your current + button dropdown with this -->
                                        <button class="btn btn-ghost btn-icon btn-sm rounded-circle" data-bs-toggle="modal" data-bs-target="#convertLeadModal<?php echo htmlspecialchars($lead['id']); ?>">
                                            <i data-feather="plus-circle" class="icon-xs text-primary"></i>
                                        </button>

                                        <?php
                                        // Include the modal component file
                                        include_once 'lead_conversion_modal.php';
                                        // Render the modal for this lead
                                        renderLeadConversionModal($lead);
                                        ?>

                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="add_client.php?lead_id=<?php echo htmlspecialchars($lead['id']); ?>">Add as Client</a></li>
                                            <li><a class="dropdown-item" href="schedule_install.php?lead_id=<?php echo htmlspecialchars($lead['id']); ?>">Schedule Install</a></li>
                                        </ul>
                                    </div>
                                </td>
                                <td>
                                    <div class="lh-1">
                                        <h5 class="mb-0">
                                            <a href="lead_detail.php?id=<?php echo htmlspecialchars($lead['id']); ?>" class="text-inherit">
                                                <?php echo htmlspecialchars(trim($lead['first_name'] . ' ' . $lead['last_name'])); ?>
                                            </a>
                                        </h5>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($lead['phone_number']); ?></td>
                                <td><?php echo htmlspecialchars($lead['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo ($lead['status'] == 'Installed') ? 'success' : 
                                            (($lead['status'] == 'Scheduled') ? 'primary' : 
                                            (($lead['status'] == 'Lost Lead') ? 'danger' : 
                                            (($lead['status'] == 'Follow Up') ? 'warning' : 'secondary'))); 
                                    ?>">
                                        <?php echo htmlspecialchars($lead['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $vehicle_info = [];
                                    if (!empty($lead['year'])) $vehicle_info[] = $lead['year'];
                                    if (!empty($lead['make'])) $vehicle_info[] = $lead['make'];
                                    if (!empty($lead['model'])) $vehicle_info[] = $lead['model'];
                                    
                                    echo !empty($vehicle_info) ? htmlspecialchars(implode(' ', $vehicle_info)) : 'Not specified';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $vehicle_options = [];
                                    if ($lead['hybrid'] == 1) $vehicle_options[] = 'Hybrid';
                                    if (!empty($lead['start_system'])) $vehicle_options[] = $lead['start_system'];
                                    
                                    echo !empty($vehicle_options) ? htmlspecialchars(implode(', ', $vehicle_options)) : 'Not specified';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        <!-- Always show pagination -->
        <nav aria-label="Leads list pagination">
            <ul class="pagination justify-content-end mb-0">
                <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo get_leads_pagination_url($current_page - 1, $search); ?>" tabindex="-1" <?php echo ($current_page <= 1) ? 'aria-disabled="true"' : ''; ?>>Previous</a>
                </li>
                
                <?php
                // Determine page range to display
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $start_page + 4);
                $start_page = max(1, $end_page - 4);
                
                // Always show at least page 1 even if there's no data
                if ($total_pages < 1) $total_pages = 1;
                
                for ($i = $start_page; $i <= $end_page; $i++): 
                ?>
                    <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo get_leads_pagination_url($i, $search); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo get_leads_pagination_url($current_page + 1, $search); ?>" <?php echo ($current_page >= $total_pages) ? 'aria-disabled="true"' : ''; ?>>Next</a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<!-- Initialize Feather Icons -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>