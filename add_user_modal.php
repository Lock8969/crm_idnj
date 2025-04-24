<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.reload()"></button>
            </div>
            <div class="modal-body">
                <form id="addUserForm">
                    <div class="mb-3">
                        <label for="addUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="addUsername" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="addFullName" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="addFullName" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="addEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="addEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="addRole" class="form-label">Role</label>
                        <select class="form-select" id="addRole" name="role" required>
                            <option value="Sales Rep">Sales Rep</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="addPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="addPassword" name="password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="window.location.reload()">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveNewUser">Add User</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add auto-capitalization to full name field
    document.getElementById('addFullName').addEventListener('input', function(e) {
        let words = this.value.toLowerCase().split(' ');
        for (let i = 0; i < words.length; i++) {
            if (words[i].length > 0) {
                words[i] = words[i][0].toUpperCase() + words[i].slice(1);
            }
        }
        this.value = words.join(' ');
    });

    // Handle save button click
    document.getElementById('saveNewUser').addEventListener('click', function() {
        const form = document.getElementById('addUserForm');
        const formData = new FormData(form);

        // Send AJAX request to add user
        fetch('add_user_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal and refresh page
                const modal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
                modal.hide();
                window.location.reload();
            } else {
                alert('Error adding user: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding the user.');
        });
    });
});
</script> 