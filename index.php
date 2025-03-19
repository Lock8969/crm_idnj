<?php
include 'db.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Dashboard</title>

    <!-- SB Admin 2 CSS -->
    <link href="sb_admin/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="sb_admin/css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>

    <!-- jQuery and Bootstrap JS -->
    <script src="sb_admin/vendor/jquery/jquery.min.js"></script>
    <script src="sb_admin/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core Plugin JavaScript -->
    <script src="sb_admin/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- SB Admin 2 Custom Scripts -->
    <script src="sb_admin/js/sb-admin-2.min.js"></script>
    <style>
    .dataTables_filter {
        text-align: left !important;
        display: flex;
        justify-content: flex-end;
        width: 100%;
        padding-right: 80px; /* Adjust spacing */
}
  
</style>

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
                <div class="sidebar-brand-icon"><i class="fas fa-laptop"></i></div>
                <div class="sidebar-brand-text mx-3">CRM</div>
            </a>

            <hr class="sidebar-divider">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item active">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-fw fa-table"></i>
                    <span>Leads</span>
                </a>
            </li>

            <!-- Nav Item - Add Lead -->
            <li class="nav-item">
                <a class="nav-link" href="add_lead.php">
                    <i class="fas fa-fw fa-user-plus"></i>
                    <span>Add Lead</span>
                </a>
            </li>

            <hr class="sidebar-divider d-none d-md-block">
        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <h4 class="m-0 text-dark">CRM Dashboard</h4>
                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

<!-- Page Heading -->
<h1 class="h3 mb-4 text-gray-800">Leads List</h1>

<div class="card shadow">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Current Leads</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
        <table class="table table-hover" id="leadsTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
        <?php
                    try {
                $stmt = $pdo->query("SELECT id, first_name, last_name, email, phone_number, status, created_at FROM leads");
                $leads = $stmt->fetchAll();

        foreach ($leads as $lead) {
            echo "<tr>
                    <td><a href='lead_detail.php?id={$lead['id']}' class='text-primary'>{$lead['first_name']} {$lead['last_name']}</a></td>
                    <td>{$lead['email']}</td>
                    <td>{$lead['phone_number']}</td>
                    <td>{$lead['status']}</td>
                    <td>" . date("m/d/y", strtotime($lead['created_at'])) . "</td>
                  </tr>";
        }
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Error fetching leads: ' . $e->getMessage() . '</div>';
    }
    ?>
</tbody>



            </table>
        </div>
    </div>
</div>

<!-- DataTables JS -->
<script src="/sb_admin/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="/sb_admin/vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- DataTables Initialization -->
<script>
    $(document).ready(function() {
        $('#leadsTable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": false,
            "order": [[5, "desc"]]
        });
    });
</script>





                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>© <?php echo date("Y"); ?> CRM IDNJ</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Initialize Feather Icons -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            feather.replace();
        });
    </script>
</body>

</html>
