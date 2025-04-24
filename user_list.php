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
    
    // Get all users
    $stmt = $pdo->prepare("SELECT id, username, email, full_name, role, nickname, created_at FROM users WHERE active = 1 ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $users = [];
    error_log("Database Error: " . $e->getMessage());
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

    <title>User Management | IDNJ</title>
    <style>
    /* Desktop-specific styling only FIXES MARGINS OFFSET BY NAV */
    @media (min-width: 992px) {
        .content-wrapper {
            margin-right: -5rem;
            margin-left: 4rem;
        }
    }
    </style>

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
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h1 class="h3 mb-0 text-gray-800">User Management</h1>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fas fa-user-plus me-2"></i>Add New User
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Users List Card -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Users List</h5>
                                <div class="d-flex align-items-center">
                                    <form class="me-3">
                                        <div class="input-group">
                                            <input type="search" class="form-control" placeholder="Search users" id="userSearch">
                                            <button class="btn btn-outline-secondary" type="button">
                                                <i class="bi bi-search"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table mb-0" id="usersTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Username</th>
                                                <th>Full Name</th>
                                                <th>Display Name</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($users)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">No active users found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($users as $user): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($user['nickname']); ?></td>
                                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                        <td>
                                                            <span class="d-inline-block px-2 py-1 rounded-2 bg-<?php echo $user['role'] === 'Admin' ? 'primary' : 'info'; ?> text-white small">
                                                                <?php echo htmlspecialchars($user['role']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-primary me-2 edit-user-btn" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#editUserModal"
                                                                    data-user-id="<?php echo $user['id']; ?>"
                                                                    data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                                    data-fullname="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                                    data-nickname="<?php echo htmlspecialchars($user['nickname']); ?>"
                                                                    data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                                    data-role="<?php echo htmlspecialchars($user['role']); ?>"
                                                                    data-bs-toggle="tooltip" 
                                                                    data-bs-title="Edit">
                                                                <i data-feather="edit" class="icon-xs"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                    onclick="confirmDelete(<?php echo $user['id']; ?>)"
                                                                    data-bs-toggle="tooltip" 
                                                                    data-bs-title="Deactivate">
                                                                <i data-feather="x-circle" class="icon-xs"></i>
                                                            </button>
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

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="editUserId" name="user_id">
                    <div class="mb-3">
                        <label for="editUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="editUsername" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="editFullName" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="editFullName" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="editEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="editRole" class="form-label">Role</label>
                        <select class="form-select" id="editRole" name="role" required>
                            <option value="Admin">Admin</option>
                            <option value="Sales Rep">Sales Rep</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editPassword" class="form-label">Update Password (optional)</label>
                        <input type="password" class="form-control" id="editPassword" name="password" placeholder="Leave blank to keep current password">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="window.location.reload()">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveUserChanges">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<?php include 'add_user_modal.php'; ?>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Deactivation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to deactivate this user? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Deactivate</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            delay: { show: 500, hide: 100 }
        })
    });
    
    // User search
    $("#userSearch").on("keyup search", function() {
        var value = $(this).val().toLowerCase();
        $("#usersTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
});

let userIdToDelete = null;

function confirmDelete(userId) {
    userIdToDelete = userId;
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    modal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (userIdToDelete) {
        // Send AJAX request to deactivate user
        fetch('update_user_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `user_id=${userIdToDelete}&action=delete`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal and refresh page
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
                modal.hide();
                window.location.reload();
            } else {
                alert('Error deactivating user: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deactivating the user.');
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Handle edit button clicks
    document.querySelectorAll('.edit-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const username = this.getAttribute('data-username');
            const fullName = this.getAttribute('data-fullname');
            const email = this.getAttribute('data-email');
            const role = this.getAttribute('data-role');

            // Set form values
            document.getElementById('editUserId').value = userId;
            document.getElementById('editUsername').value = username;
            document.getElementById('editFullName').value = fullName;
            document.getElementById('editEmail').value = email;
            document.getElementById('editRole').value = role;
        });
    });

    // Add auto-capitalization to full name field
    document.getElementById('editFullName').addEventListener('input', function(e) {
        let words = this.value.toLowerCase().split(' ');
        for (let i = 0; i < words.length; i++) {
            if (words[i].length > 0) {
                words[i] = words[i][0].toUpperCase() + words[i].slice(1);
            }
        }
        this.value = words.join(' ');
    });

    // Handle save button click
    document.getElementById('saveUserChanges').addEventListener('click', function() {
        const form = document.getElementById('editUserForm');
        const formData = new FormData(form);

        // Send AJAX request to update user
        fetch('update_user_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal and refresh page
                const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
                modal.hide();
                window.location.reload();
            } else {
                alert('Error updating user: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the user.');
        });
    });
});
</script>

<?php include 'footer.php'; ?>