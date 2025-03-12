<?php
require_once 'auth_check.php';
include 'db.php';
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

    <title>Dashboard | IDNJ</title>
    
    <style>
    /* Desktop-specific styling only FIXES MARGINS OFFSET BY NAV*/
    @media (min-width: 992px) {
    .content-wrapper {
        margin-right: -5rem;
        margin-left: 4rem;
    }
}
</style>

</head>
<body>

<!-- Format Phone Number: Formats input as 000-000-0000 and removes leading "+1" or "1" -->
<script>
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '').replace(/^1/, '').slice(0, 10); // Remove non-digits & leading "1"

    input.value = value.replace(/(\d{3})(\d{3})?(\d{4})?/, (m, p1, p2, p3) => 
        [p1, p2, p3].filter(Boolean).join('-')); // Format as 000-000-0000
}
</script>
<!-- script for model dropdown -->

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

<div id="app-content">
    <div class="app-content-area">
        <div class="container-fluid">
            <div class="content-wrapper">
                <!-- Page header -->
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-12">
                        <div class="d-flex justify-content-between align-items-center mb-5">
                            <div>
                                <h3 class="mb-0 fw-bold">Dashboard</h3>
                                <p class="text-muted mb-0">Location: <?php echo htmlspecialchars($_SESSION['location_name'] ?? 'Unknown'); ?></p>
                            </div>
                            <a href="#!" class="btn btn-primary">Time Clock</a>
                        </div>
                    </div>
                </div>

                <!-- Include the client list card - spans full width -->
                <div class="row">
                    <div class="col-12">
                        <?php include 'client_list.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>