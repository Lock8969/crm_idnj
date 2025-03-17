<?php
require_once 'auth_check.php';

// Ensure this file can be included safely in other scripts
if (!defined('INCLUDED_IN_SCRIPT')) {
    define('INCLUDED_IN_SCRIPT', true);

    // Check if database connection exists before including `db.php`
    if (!isset($pdo)) {
        include 'db.php';
    }
}

// Get current page from URL parameter
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); // Ensure page is at least 1
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

// Fetch client information from the database
try {
    // Get total count for pagination
    $count_query = "SELECT COUNT(*) FROM client_information $search_condition";
    $count_stmt = $pdo->prepare($count_query);
    
    if (!empty($search)) {
        $count_stmt->execute($params);
    } else {
        $count_stmt->execute();
    }
    
    $total_clients = $count_stmt->fetchColumn();
    $total_pages = ceil($total_clients / $per_page);
    
    // Get clients for current page
    $query = "SELECT id, first_name, last_name, email, phone_number FROM client_information 
              $search_condition 
              ORDER BY last_name, first_name 
              LIMIT $offset, $per_page";
    
    $stmt = $pdo->prepare($query);
    
    if (!empty($search)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    
    $clients = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching client list: " . $e->getMessage());
    $clients = [];
    $total_pages = 1;
}

// Create pagination URL
function get_pagination_url($page, $search) {
    $url = '?';
    if (!empty($search)) {
        $url .= 'search=' . urlencode($search) . '&';
    }
    return $url . 'page=' . $page;
}
?>

<div class="card h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Client List</h4>
        
        <div class="d-flex align-items-center">
            <!-- Search form -->
            <form class="d-flex me-3">
                <input type="search" name="search" class="form-control" placeholder="Search clients" 
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
                    <li><a class="dropdown-item d-flex align-items-center" href="add_client.php">Add New Client</a></li>
                    <li><a class="dropdown-item d-flex align-items-center" href="clients.php">View All Clients</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive table-card">
            <table class="table text-nowrap mb-0 table-centered">
                <thead class="table-light">
                    <tr>
                        <th>Customer ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clients)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No clients found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clients as $index => $client): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($client['id']); ?></td>
                                <td>
                                    <div class="lh-1">
                                        <h5 class="mb-0">
                                            <a href="client_detail.php?id=<?php echo htmlspecialchars($client['id']); ?>" class="text-inherit">
                                                <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                                            </a>
                                        </h5>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($client['email']); ?></td>
                                <td><?php echo htmlspecialchars($client['phone_number']); ?></td>
                                <td>
                                    <a href="client_detail.php?id=<?php echo htmlspecialchars($client['id']); ?>" 
                                       class="btn btn-ghost btn-icon btn-sm rounded-circle texttooltip"
                                       data-template="eye<?php echo $index; ?>">
                                        <i data-feather="eye" class="icon-xs"></i>
                                        <div id="eye<?php echo $index; ?>" class="d-none">
                                            <span>View Client</span>
                                        </div>
                                    </a>
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
        <nav aria-label="Client list pagination">
            <ul class="pagination justify-content-end mb-0">
                <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo get_pagination_url($current_page - 1, $search); ?>" tabindex="-1" <?php echo ($current_page <= 1) ? 'aria-disabled="true"' : ''; ?>>Previous</a>
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
                        <a class="page-link" href="<?php echo get_pagination_url($i, $search); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo get_pagination_url($current_page + 1, $search); ?>" <?php echo ($current_page >= $total_pages) ? 'aria-disabled="true"' : ''; ?>>Next</a>
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