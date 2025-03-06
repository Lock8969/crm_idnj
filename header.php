<?php
include 'db.php';

// Get client ID from URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    die("Invalid Client ID.");
}

$client_id = $_GET['id'];

try {
    // Fetch client details from the database
    $stmt = $pdo->prepare("SELECT * FROM client_information WHERE id = :id");
    $stmt->execute(['id' => $client_id]);
    $client = $stmt->fetch();

    if (!$client) {
        die("Client not found.");
    }

    // Fetch vehicle information for this client
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
    <link rel="shortcut icon" type="image/x-icon" href="dashui/dist/assets/images/favicon/favicon.ico" />

    <!-- Color modes -->
    <script src="/dashui/dist/assets/js/vendors/color-modes.js"></script>

    <!-- Libs CSS -->
    <link href="/dashui/dist/assets/libs/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="/dashui/dist/assets/libs/@mdi/font/css/materialdesignicons.min.css" rel="stylesheet" />
    <link href="/dashui/dist/assets/libs/simplebar/dist/simplebar.min.css" rel="stylesheet" />

    <!-- Theme CSS -->
    <link rel="stylesheet" href="/dashui/dist/assets/css/theme.min.css">

    <title>Client Details | IDNJ</title>
    <style>
        .card-header button.btn-link {
    font-size: 28px !important; /* Forces font size */
    font-weight: bold !important; /* Ensures boldness */
    text-decoration: none; /* Keeps the Bootstrap styling */
    color : black !important;
}
    </style>
</head>
<body>
    <!-- Wrapper -->
    <main id="main-wrapper" class="main-wrapper">
      <div class="header">
	<!-- Navbar -->
<div class="navbar-custom navbar navbar-expand-lg">
    <div class="container-fluid px-0">
        <a class="navbar-brand d-block d-md-none" href="../index.html">
            <img src="../assets/images/brand/logo/logo-2.svg" alt="Image" />
        </a>
        <!-- vertical nav bar toggle -->
        <a id="nav-toggle" href="#!" class="ms-auto ms-md-0 me-0 me-lg-3">
				<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="bi bi-text-indent-left text-muted" viewBox="0 0 16 16">
					<path
						d="M2 3.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zm.646 2.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1 0 .708l-2 2a.5.5 0 0 1-.708-.708L4.293 8 2.646 6.354a.5.5 0 0 1 0-.708zM7 6.5a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5zm0 3a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5zm-5 3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z"
					/>
				</svg>
			</a>
        <!-- Search Bar -->
        <form class="d-none d-md-flex ms-3">
            <input type="search" class="form-control" placeholder="Search" aria-label="Search" />
        </form>

        <div class="ms-auto"></div> <!-- Push profile dropdown to the right -->

        <!-- Profile Dropdown -->
        <ul class="navbar-nav navbar-right-wrap ms-lg-auto d-flex align-items-center">
            <li class="dropdown">
                <a class="rounded-circle" href="#!" role="button" id="dropdownUser" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="avatar avatar-md avatar-indicators avatar-online">
                        <img alt="avatar" src="../assets/images/avatar/avatar-11.jpg" class="rounded-circle" />
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownUser">
                    <div class="px-4 pb-0 pt-2">
                        <div class="lh-1">
                            <h5 class="mb-1">John E. Grainger</h5>
                            <a href="#!" class="text-inherit fs-6">View my profile</a>
                        </div>
                        <div class="dropdown-divider mt-3 mb-2"></div>
                    </div>
                    <ul class="list-unstyled">
                       
                        <li>
                            <a class="dropdown-item" href="../index.html">
                                <i class="me-2 icon-xxs dropdown-item-icon" data-feather="power"></i>
                                Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</div>
</div>

			<!-- navbar vertical -->
			<div class="app-menu"><!-- Sidebar -->

<div class="navbar-vertical navbar nav-dashboard">
	<div class="h-100" data-simplebar>
		<!-- Brand logo -->
		<a class="navbar-brand" href="./index.html">
			<img src="dashui/dist/assets/images/brand/logo/logo-2.svg" alt="dash ui - bootstrap 5 admin dashboard template" />
		</a>
		<!-- Navbar nav -->
		<ul class="navbar-nav flex-column" id="sideNavbar">
			<!-- Nav item -->
			<li class="nav-item">
				<a
					class="nav-link has-arrow "
					href="#!"
					data-bs-toggle="collapse"
					data-bs-target="#navDashboard"
					aria-expanded="false"
					aria-controls="navDashboard"
				>
					<i data-feather="home" class="nav-icon me-2 icon-xxs"></i>
					Dashboard
				</a>

				<div id="navDashboard" class="collapse  show " data-bs-parent="#sideNavbar">
					<ul class="nav flex-column">
						<li class="nav-item">
							<a class="nav-link " href="./pages/dashboard-analytics.html">Analytics</a>
						</li>
						<li class="nav-item">
							<a class="nav-link  active " href="./index.html">Project</a>
						</li>

						<li class="nav-item">
							<a class="nav-link has-arrow " href="./pages/dashboard-ecommerce.html">Ecommerce</a>
						</li>
						<li class="nav-item">
							<a class="nav-link has-arrow " href="./pages/dashboard-crm.html">CRM</a>
						</li>
						<li class="nav-item">
							<a class="nav-link has-arrow " href="./pages/dashboard-finance.html">Finance</a>
						</li>
						<li class="nav-item">
							<a class="nav-link has-arrow " href="./pages/dashboard-blog.html">Blog</a>
						</li>
					</ul>
				</div>
			</li>

            <!-- Nav item -->
			<li class="nav-item">
				<div class="navbar-heading">Apps</div>
			</li>
			<!-- Nav item -->
			<li class="nav-item">
				<a class="nav-link has-arrow " href="./pages/calendar.html">Calendar</a>
			</li>
        </ul>
    </div> 
</div>
</div>
</div>
<div id="app-content">