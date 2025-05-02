<?php
// This PHP holds the username in profile circle at the top
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="author" content="idnj" />

    <!-- Favicon icon-->
    <link rel="shortcut icon" type="image/x-icon" href="/dashui/assets/images/brand/logo/idnj_logo_small.png" />

    <!-- Color modes -->
    <script src="/dashui/assets/js/vendors/color-modes.js"></script>

    <!-- Libs CSS -->
    <link href="/dashui/assets/libs/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="/dashui/assets/libs/@mdi/font/css/materialdesignicons.min.css" rel="stylesheet" />
    <link href="/dashui/assets/libs/simplebar/dist/simplebar.min.css" rel="stylesheet" />

    <!-- Theme CSS -->
    <link rel="stylesheet" href="/dashui/assets/css/theme.min.css">
    

</head>
<body>
<!-- Wrapper -->
<main id="main-wrapper" class="main-wrapper">
  <div class="header">
	
  <!-- Navbar -->
  <div class="navbar-custom navbar navbar-expand-lg">
    <div class="container-fluid px-0">
        <a class="navbar-brand d-block d-md-none" href="../index.html"> </a>
        
        <!-- vertical nav bar toggle -->
        <a id="nav-toggle" href="#!" class="ms-auto ms-md-0 me-0 me-lg-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="bi bi-text-indent-left text-muted" viewBox="0 0 16 16">
                <path d="M2 3.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zm.646 2.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1 0 .708l-2 2a.5.5 0 0 1-.708-.708L4.293 8 2.646 6.354a.5.5 0 0 1 0-.708zM7 6.5a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5zm0 3a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5zm-5 3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z"/>
            </svg>
        </a>
       
        <!-- Search Bar -->
		<form class="d-none d-md-flex ms-3" action="dashboard.php" method="GET">
			<div class="input-group">
				<input type="search" name="search" class="form-control" placeholder="Search clients" aria-label="Search" />
				<button type="submit" class="btn btn-outline-secondary">
					<i data-feather="search" class="icon-xs"></i>
				</button>
			</div>
		</form>

        <div class="ms-auto"></div> <!-- Push profile dropdown to the right -->

        <!-- Profile Dropdown -->
        <ul class="navbar-nav navbar-right-wrap ms-lg-auto d-flex align-items-center">
            <li class="dropdown">
                <a class="rounded-circle" href="#!" role="button" id="dropdownUser" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="avatar avatar-md avatar-indicators avatar-online">
                        <img alt="avatar" src="dashui/assets/images/brand/logo/idnj_logo_small.png" class="rounded-circle" />
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownUser">
                    <div class="px-4 pb-0 pt-2">
                        <div class="lh-1">
                            <h5 class="mb-1"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest'; ?></h5>
                        </div>
                        <div class="dropdown-divider mt-3 mb-2"></div>
                    </div>
                    <ul class="list-unstyled">
                        <li>
                            <a class="dropdown-item" href="logout.php">
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
<div class="d-flex justify-content-center align-items-center w-100 mt-3 mb-4">
    <img src="dashui/assets/images/brand/logo/idnj_logo_small.png" alt="IDNJ" height="30" class="me-2" />
    <span class="fw-bold fs-2">IDNJ</span>
</div>
        
        <!-- Custom styling for nav links with larger top margin -->
        <style>
            .nav-centered {
                text-align: center;
                margin-top: 3rem;
            }
            .nav-centered .nav-link {
                justify-content: left;
                font-size: 1rem;
                padding: 0.75rem 1rem;
            }
            .nav-centered .nav-icon {
                margin-right: 0.5rem !important;
				margin-left: 2rem !important;
            }
        </style>
        
        <!-- Navbar nav -->
        <ul class="navbar-nav flex-column nav-centered" id="sideNavbar">
            <!-- Dashboard direct link -->
            
            <li class="nav-item">
                <a class="nav-link" href="https://crm.idnj.com/dashboard.php">
                    <i data-feather="home" class="nav-icon icon-xxs"></i>
                    Dashboard
                </a>
            </li>
            <!-- Leads dropdown -->
            <li class="nav-item">
                <a
                    class="nav-link has-arrow collapsed"
                    href="#!"
                    data-bs-toggle="collapse"
                    data-bs-target="#navLeads"
                    aria-expanded="false"
                    aria-controls="navLeads"
                >
                    <i data-feather="users" class="nav-icon icon-xxs"></i>
                    Leads
                </a>
                <div id="navLeads" class="collapse" data-bs-parent="#sideNavbar">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="https://crm.idnj.com/leads_incoming.php">Call_log</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="https://crm.idnj.com/call_log.php">All Leads</a>
                        </li>
                    </ul>
                </div>
            </li>
            <!-- Inventory direct link -->
            <li class="nav-item">
                <a class="nav-link" href="inventory.php">
                    <i data-feather="box" class="nav-icon icon-xxs"></i>
                    Inventory
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="https://crm.idnj.com/system-settings.php">
                    <i data-feather="home" class="nav-icon icon-xxs"></i>
                    System
                </a>
            
                
            <!-- Nav item -->
            <li class="nav-item">
                <a
                    class="nav-link has-arrow collapsed"
                    href="#!"
                    data-bs-toggle="collapse"
                    data-bs-target="#navReports"
                    aria-expanded="false"
                    aria-controls="navReports"
                >
                    <i data-feather="file-text" class="nav-icon me-2 icon-xxs"></i>
                    Reports
                </a>
                <div id="navReports" class="collapse" data-bs-parent="#sideNavbar">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="/quick-schedule.php">Quick Schedule</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/shop-schedule.php">Shop Schedule</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/report_time.php">Time Tracker</a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
    </div> 
</div>
</div>
</div>
</body>
</html>