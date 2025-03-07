<?php
include 'db.php';
include 'navigation.php';

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
    <meta name="author" content="Codescandy" />

    <!-- Favicon icon -->
    <link rel="shortcut icon" type="image/x-icon" href="dashui/assets/images/favicon/favicon.ico" />

    <!-- Color modes -->
    <script src="/dashui/assets/js/vendors/color-modes.js"></script>

    <!-- Libs CSS -->
    <link href="/dashui/assets/libs/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="/dashui/assets/libs/@mdi/font/css/materialdesignicons.min.css" rel="stylesheet" />
    <link href="/dashui/assets/libs/simplebar/dist/simplebar.min.css" rel="stylesheet" />

    <!-- Theme CSS -->
    <link rel="stylesheet" href="/dashui/assets/css/theme.min.css">

    <title>Client Detail | IDNJ</title>

    
</head>




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

<<!--- THIS IS WHERE NAVIGATION ENDS AND PAGE BEGINS --->

<!-- After your navigation ends and page begins -->
<div class="app-content-area">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-12">
                <!-- Page header -->
                <div class="mb-5">
                    <h3 class="mb-0">
                        <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                    </h3>
                </div>
            </div>
        </div>
        
        <!-- Single row to contain both cards -->
        <div class="row g-4">
            <!-- Left column for client info card -->
            <div class="col-md-6">
                <?php include 'client_info_card.php'; ?>
            </div>
            
            <!-- Right column for vehicle info card -->
            <div class="col-md-6">
                <?php include 'vehicle_info_card.php'; ?>
            </div>
        </div>
    </div>
</div>




 <!-- ✅ JavaScript ✅ -->
  <!-- Theme JS: Controls DashUI theme settings and layout behavior -->
<script src="/dashui/assets/js/theme.min.js"></script>
<!-- Bootstrap JS: Enables Bootstrap functionality (e.g., modals, tooltips, dropdowns) -->
<script src="/dashui/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<!-- SimpleBar JS: Adds custom scrollbar styling for better UI experience -->
<script src="/dashui/assets/libs/simplebar/dist/simplebar.min.js"></script>


<!-- Capitalize Words: Ensures first letter of each word is capitalized while typing -->
<script>
function capitalizeWords(input) {
    input.value = input.value.replace(/\b\w/g, char => char.toUpperCase());
}
</script>

<!-- Format Phone Number: Formats input as 000-000-0000 and removes leading "+1" or "1" -->
<script>
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '').replace(/^1/, '').slice(0, 10); // Remove non-digits & leading "1"

    input.value = value.replace(/(\d{3})(\d{3})?(\d{4})?/, (m, p1, p2, p3) => 
        [p1, p2, p3].filter(Boolean).join('-')); // Format as 000-000-0000
}
</script>
<!-- JavaScript to Toggle Arrow Direction -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const toggleButton = document.querySelector("[data-bs-toggle='collapse']");
        const toggleIcon = toggleButton.querySelector(".toggle-icon");

        toggleButton.addEventListener("click", function() {
            if (toggleIcon.textContent === "▼") {
                toggleIcon.textContent = "▲";
            } else {
                toggleIcon.textContent = "▼";
            }
        });
    });
</script>
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



</body>

</html>