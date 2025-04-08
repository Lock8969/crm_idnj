<?php
require_once 'auth_check.php';
include 'db.php';


// Get Info from DB
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    error_log("Invalid Client ID: " . $_GET['id']);
    die("Invalid Client ID.");
}
// Gets ID from URL
$client_id = $_GET['id'];
error_log("Client ID: " . $client_id);

// Client info Table
try {
    // Fetch client details from the database
    $stmt = $pdo->prepare("SELECT * FROM client_information WHERE id = :id");
    $stmt->execute(['id' => $client_id]);
    $client = $stmt->fetch();

    if (!$client) {
        error_log("Client not found: " . $client_id);
        die("Client not found.");
    }

    // Vehicle Table
    $stmt = $pdo->prepare("SELECT * FROM vehicle_information WHERE customer_id = :customer_id");
    $stmt->execute(['customer_id' => $client_id]);
    $vehicle = $stmt->fetch();

} catch (PDOException $e) {
    error_log("Error fetching details: " . $e->getMessage());
    die("An error occurred while fetching client or vehicle details.");
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
    <meta name="author" content="idnj" />

    <!-- Favicon icon-->
    <link rel="shortcut icon" type="image-x-icon" href="/dashui/assets/images/brand/logo/idnj_logo_small.png" />

    <!-- Color modes -->
    <script src="/dashui/assets/js/vendors/color-modes.js"></script>

    <!-- Libs CSS -->
    <link href="/dashui/assets/libs/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="/dashui/assets/libs/@mdi/font/css/materialdesignicons.min.css" rel="stylesheet" />
    <link href="/dashui/assets/libs/simplebar/dist/simplebar.min.css" rel="stylesheet" />

    <!-- Theme CSS -->
    <link rel="stylesheet" href="/dashui/assets/css/theme.min.css">
        <!-- Custom CSS -->
        <link rel="stylesheet" href="/dashui/assets/css/custom.css">

    <title>Client Detail | IDNJ</title>
    
    <style>
    /* Desktop-specific styling only FIXES MARGINS OFFSET BY NAV*/
    @media (min-width: 992px) {
        .content-wrapper {
            margin-right: 2rem;
            margin-left: 2rem;
        }
    }
    
    /* Card styles for info displays */
    .info-row {
        margin-bottom: 1rem;
    }
    .info-label {
        font-size: 1rem;
    }
    .info-value {
        font-size: 1.2rem;
        font-weight: bold;
    }
    </style>

</head>
<body>

<!-- Format Phone Number -->
<script>
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '').replace(/^1/, '').slice(0, 10);
    input.value = value.replace(/(\d{3})(\d{3})?(\d{4})?/, (m, p1, p2, p3) => 
        [p1, p2, p3].filter(Boolean).join('-'));
}
</script>

<!-- Script for model dropdown -->
<script>
$(document).ready(function(){
    $("#make").change(function(){
        var make_id = $(this).val();
        $("#model").prop("disabled", true).html("<option>Loading...</option>");

        if(make_id) {
            $.ajax({
                url: "get_models.php",
                type: "POST",
                data: { make_id: make_id },
                success: function(response){
                    $("#model").html(response).prop("disabled", false);
                },
                error: function() {
                    alert("Error fetching models. Please try again.");
                }
            });
        } else {
            $("#model").html("<option value=''>Select Model</option>").prop("disabled", true);
        }
    });
});
</script>

<!--- Begin Navigation --->
<?php include 'navigation.php'; ?>

<!--- THIS IS WHERE NAVIGATION ENDS AND PAGE BEGINS --->
<div id="app-content">
    <div class="app-content-area">
        <div class="container-fluid">
            <div class="content-wrapper">
                <!-- Page header -->
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-12">
                        <div class="mb-5">
                            <h3 class="mb-0">
                                <?php echo $client_id . '&nbsp;&nbsp;&nbsp;' . htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                            </h3>
                        </div>
                    </div>
                </div>
                
                <!-- Client and Vehicle Cards -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <?php include 'info_card.php'; ?>
                    </div>
                    <div class="col-md-6 mb-4">
                        <?php include 'program_card.php'; ?>
                    </div>
                </div>
                
                <!-- Vehicle and Device Card Row -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <?php include 'vehicle_card.php'; ?>
                    </div>
                    <div class="col-md-6 mb-4">
                        <?php include 'device_card.php'; ?>
                    </div>
                </div>

                <!-- Receipts Card Row -->
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <?php include 'receipts_card.php'; ?>
                    </div>
                </div>

                <!-- Footer -->
                <?php include 'footer.php'; ?>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="/dashui/assets/js/theme.min.js"></script>
<script src="/dashui/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="/dashui/assets/libs/simplebar/dist/simplebar.min.js"></script>

<!-- Capitalize Words -->
<script>
function capitalizeWords(input) {
    input.value = input.value.replace(/\b\w/g, char => char.toUpperCase());
}
</script>

<!-- Toggle functions for both cards -->
<script>
function toggleEditMode() {
    const staticView = document.getElementById('static-view');
    const editView = document.getElementById('edit-view');
    if (staticView && editView) {
        staticView.classList.toggle('d-none');
        editView.classList.toggle('d-none');
    }
}

function toggleVehicleEditMode() {
    const staticView = document.getElementById('vehicle-static-view');
    const editView = document.getElementById('vehicle-edit-view');
    if (staticView && editView) {
        staticView.classList.toggle('d-none');
        editView.classList.toggle('d-none');
    }
}

function toggleDeviceEditMode() {
    const staticView = document.getElementById('device-static-view');
    const editView = document.getElementById('device-edit-view');
    if (staticView && editView) {
        staticView.classList.toggle('d-none');
        editView.classList.toggle('d-none');
    }
}
</script>

