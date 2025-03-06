<?php include 'db.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
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

    <title>Leads | Dash UI</title>
</head>
<body>
<main id="main-wrapper" class="main-wrapper">
<div class="header">
    <!-- navbar -->
    <div class="navbar-custom navbar navbar-expand-lg">
		<div class="container-fluid px-0">
			<a class="navbar-brand d-block d-md-none" href="./index.html">
				<img src="dashui/dist/assets/images/brand/logo/logo-2.svg" alt="Image" />
			</a>

			<a id="nav-toggle" href="#!" class="ms-auto ms-md-0 me-0 me-lg-3">
				<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="bi bi-text-indent-left text-muted" viewBox="0 0 16 16">
					<path
						d="M2 3.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zm.646 2.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1 0 .708l-2 2a.5.5 0 0 1-.708-.708L4.293 8 2.646 6.354a.5.5 0 0 1 0-.708zM7 6.5a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5zm0 3a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5zm-5 3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z"
					/>
				</svg>
			</a>

			<div class="d-none d-md-none d-lg-block">
				<!-- Form -->
				<form action="#">
					<div class="input-group">
						<input class="form-control rounded-3 bg-transparent ps-9" type="search" value="" id="searchInput" placeholder="Search" />
						<span class="">
							<button class="btn position-absolute start-0" type="button">
								<svg
									xmlns="http://www.w3.org/2000/svg"
									width="15"
									height="15"
									viewBox="0 0 24 24"
									fill="none"
									stroke="currentColor"
									stroke-width="2"
									stroke-linecap="round"
									stroke-linejoin="round"
									class="feather feather-search text-dark"
								>
									<circle cx="11" cy="11" r="8"></circle>
									<line x1="21" y1="21" x2="16.65" y2="16.65"></line>
								</svg>
							</button>
						</span>
					</div>
				</form>
			</div>
            <!--Navbar nav -->
            <ul class="navbar-nav navbar-right-wrap ms-lg-auto d-flex nav-top-wrap align-items-center ms-4 ms-lg-0">
                            <li>
                                <div class="dropdown">
                                    <button class="btn btn-ghost btn-icon rounded-circle" type="button" aria-expanded="false" data-bs-toggle="dropdown" aria-label="Toggle theme (auto)">
                                        <i class="bi theme-icon-active"></i>
                                        <span class="visually-hidden bs-theme-text">Toggle theme</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="bs-theme-text">
                                        <li>
                                            <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light" aria-pressed="false">
                                                <i class="bi theme-icon bi-sun-fill"></i>
                                                <span class="ms-2">Light</span>
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark" aria-pressed="false">
                                                <i class="bi theme-icon bi-moon-stars-fill"></i>
                                                <span class="ms-2">Dark</span>
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item d-flex align-items-center active" data-bs-theme-value="auto" aria-pressed="true">
                                                <i class="bi theme-icon bi-circle-half"></i>
                                                <span class="ms-2">Auto</span>
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                             </li>

                                <li class="dropdown stopevent ms-2">
                                    <a class="btn btn-ghost btn-icon rounded-circle" href="dashui/dist/assets/images/brand/logo/logo-2.svg" role="button" id="dropdownNotification" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="icon-xs" data-feather="bell"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end" aria-labelledby="dropdownNotification">
                                        <div>
                                            <div class="border-bottom px-3 pt-2 pb-3 d-flex justify-content-between align-items-center">
                                                <p class="mb-0 text-dark fw-medium fs-4">Notifications</p>
                                                <a href="#!" class="text-muted">
                                                    <span>
                                                        <i class="me-1 icon-xs" data-feather="settings"></i>
                                                    </span>
                                                </a>
                                            </div>
                                            <div data-simplebar style="height: 250px">
                                                <!-- List group -->
                                                <ul class="list-group list-group-flush notification-list-scroll">
                                                    <!-- List group item -->
                                                    <li class="list-group-item bg-light">
                                                        <a href="#!" class="text-muted">
                                                            <h5 class="mb-1">Rishi Chopra</h5>
                                                            <p class="mb-0">Mauris blandit erat id nunc blandit, ac eleifend dolor pretium.</p>
                                                        </a>
                                                    </li>
                                                    <!-- List group item -->
                                                    <li class="list-group-item">
                                                        <a href="#!" class="text-muted">
                                                            <h5 class="mb-1">Neha Kannned</h5>
                                                            <p class="mb-0">Proin at elit vel est condimentum elementum id in ante. Maecenas et sapien metus.</p>
                                                        </a>
                                                    </li>
                                                    <!-- List group item -->
                                                    <li class="list-group-item">
                                                        <a href="#!" class="text-muted">
                                                            <h5 class="mb-1">Nirmala Chauhan</h5>
                                                            <p class="mb-0">Morbi maximus urna lobortis elit sollicitudin sollicitudieget elit vel pretium.</p>
                                                        </a>
                                                    </li>
                                                    <!-- List group item -->
                                                    <li class="list-group-item">
                                                        <a href="#!" class="text-muted">
                                                            <h5 class="mb-1">Sina Ray</h5>
                                                            <p class="mb-0">Sed aliquam augue sit amet mauris volutpat hendrerit sed nunc eu diam.</p>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="border-top px-3 py-2 text-center">
                                                <a href="#!" class="text-inherit">View all Notifications</a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
				<!-- List -->
				<li class="dropdown ms-2">
					<a class="rounded-circle" href="#!" role="button" id="dropdownUser" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						<div class="avatar avatar-md avatar-indicators avatar-online">
							<img alt="avatar" src="dashui/dist/assets/images/brand/logo/logo-2.svg" class="rounded-circle" />
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
								<a class="dropdown-item d-flex align-items-center" href="#!">
									<i class="me-2 icon-xxs dropdown-item-icon" data-feather="user"></i>
									Edit Profile
								</a>
							</li>
							<li>
								<a class="dropdown-item" href="#!">
									<i class="me-2 icon-xxs dropdown-item-icon" data-feather="activity"></i>
									Activity Log
								</a>
							</li>

							<li>
								<a class="dropdown-item d-flex align-items-center" href="#!">
									<i class="me-2 icon-xxs dropdown-item-icon" data-feather="settings"></i>
									Settings
								</a>
							</li>
							<li>
								<a class="dropdown-item" href="./index.html">
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
    </div> <!-- LINE 742 HTML -->
</div>
</div>
</div>
        <div id="app-content">
				<!-- Container fluid -->

				<div class="app-content-area">
					<div class="bg-primary pt-10 pb-21 mt-n6 mx-n4"></div>
					<div class="container-fluid mt-n22">
						<div class="row">
                         <div class="col-xl-3 col-lg-6 col-md-12 col-12 mb-5">


                        <!-- Page header -->
                        <div class="mb-5">
                            <a href="#!" class="btn btn-white">Create New Project</a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Leads Table -->
        		        <div class="row">
                          <div class="col-12 mb-5">

                                
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="mb-0">Leads</h3>
                                    </div>
                                    <div class="card-body">
                                        <?php
                                        try {
                                            $stmt = $pdo->query("SELECT id, first_name, last_name, email, phone_number, status, created_at FROM leads");
                                            $leads = $stmt->fetchAll();

                                            if (count($leads) > 0) {
                                                echo '<div class="table-responsive">';
                                                echo '<table class="table table-striped">';
                                                echo '<thead>
                                                        <tr>
                                                            <th>Full Name</th>
                                                            <th>Email</th>
                                                            <th>Phone</th>
                                                            <th>Status</th>
                                                            <th>Created At</th>
                                                        </tr>
                                                      </thead>
                                                      <tbody>';
                                                foreach ($leads as $lead) {
                                                    echo "<tr>
                                                            <td><a href='lead_detail.php?id={$lead['id']}' class='text-primary'>{$lead['first_name']} {$lead['last_name']}</a></td>
                                                            <td>{$lead['email']}</td>
                                                            <td>{$lead['phone_number']}</td>
                                                            <td>{$lead['status']}</td>
                                                            <td>" . date("m/d/y", strtotime($lead['created_at'])) . "</td>
                                                          </tr>";
                                                }
                                                echo '</tbody></table>';
                                                echo '</div>';
                                            } else {
                                                echo '<div class="alert alert-warning">No leads found.</div>';
                                            }
                                        } catch (PDOException $e) {
                                            echo '<div class="alert alert-danger">Error fetching leads: ' . $e->getMessage() . '</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="/dashui/dist/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/dashui/dist/assets/libs/simplebar/dist/simplebar.min.js"></script>
    <script src="/dashui/dist/assets/js/theme.min.js"></script>

</body>

</html>
