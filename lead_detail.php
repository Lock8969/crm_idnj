<?php
$page_title = "Client Details | IDNJ";
$page_heading = "Client Information";
include 'header.php';
?>

        <!-- Container fluid -->
        <div class="app-content-area">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-12">
                <!-- Page header -->
                <div class="mb-5">
                    <h3 class="mb-0">
                        <?php echo htmlspecialchars($lead['first_name'] . ' ' . $lead['last_name']); ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Card Body -->
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-12"> <!-- Full width -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <!-- Open Form Here -->
                    <form action="update_lead.php" method="POST">
                        <input type="hidden" name="lead_id" value="<?php echo htmlspecialchars($lead['id']); ?>">

                        <div class="row">
                            <!-- Column 1: First Name -->
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label fw-bold fs-5">First Name</label>
                                    <input type="text" class="form-control form-control-lg" id="first_name" name="first_name"
                                           value="<?php echo htmlspecialchars($lead['first_name']); ?>" required>
                                </div>
                            </div>
                            <!-- Column 2: Last Name -->
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label fw-bold fs-5">Last Name</label>
                                    <input type="text" class="form-control form-control-lg" id="last_name" name="last_name"
                                           value="<?php echo htmlspecialchars($lead['last_name']); ?>" required>
                                </div>
                            </div>
                            <!-- Column 3: Phone -->
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="phone_number" class="form-label fw-bold fs-5">Phone</label>
                                    <input type="text" class="form-control form-control-lg" id="phone_number" name="phone_number"
                                        value="<?php echo htmlspecialchars($lead['phone_number']); ?>" required
                                        maxlength="12" oninput="formatPhoneNumber(this)">
                                </div>
                            </div>
                            <!-- Column 4: Email -->
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="email" class="form-label fw-bold fs-5">Email</label>
                                    <input type="email" class="form-control form-control-lg" id="email" name="email"
                                           value="<?php echo htmlspecialchars($lead['email']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Full Width Address Field -->
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="address1" class="form-label fw-bold fs-5">Address</label>
                                    <input type="text" class="form-control form-control-lg" id="address1" name="address1"
                                           value="<?php echo htmlspecialchars($lead['address1']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-success btn-lg">Save</button>
                            <a href="leads.php" class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                    <!-- Close Form Here -->
                </div>
            </div>
        </div>
    </div>
</div>







 <!-- JavaScript -->
<script src="/dashui/dist/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="/dashui/dist/assets/libs/simplebar/dist/simplebar.min.js"></script>
<script src="/dashui/dist/assets/js/theme.min.js"></script>
<script>
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, ''); // Remove all non-numeric characters

    // Remove leading 1 or +1
    if (value.startsWith("1")) {
        value = value.substring(1);
    }

    // Ensure max 10 digits after cleaning
    value = value.substring(0, 10);

    // Format as 000-000-0000
    if (value.length > 3 && value.length <= 6) {
        value = value.replace(/(\d{3})(\d+)/, '$1-$2');
    } else if (value.length > 6) {
        value = value.replace(/(\d{3})(\d{3})(\d+)/, '$1-$2-$3');
    }

    input.value = value; // Update the input field
}
</script>

</body>

</html>