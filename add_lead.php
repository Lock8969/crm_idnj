<?php include 'index.php'; ?> 


<!DOCTYPE html>
<html lang="en">
<head>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM - Dashboard</title>

    <!-- SB Admin 2 CSS -->
    <link href="sb_admin/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="sb_admin/css/sb-admin-2.min.css" rel="stylesheet">

    <!-- jQuery and Bootstrap JS -->
    <script src="sb_admin/vendor/jquery/jquery.min.js"></script>
    <script src="sb_admin/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core Plugin JavaScript -->
    <script src="sb_admin/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- SB Admin 2 Custom Scripts -->
    <script src="sb_admin/js/sb-admin-2.min.js"></script>
</head>

</head>
<body>
    <?php include 'index.php'; ?> 
    <div class="container mt-4">
        <h2>Add a New Lead</h2>
        <form action="add_lead.php" method="POST">
            <div class="mb-3">
                <label>Name:</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Email:</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Phone:</label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Add Lead</button>
        </form>
        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $name = $_POST['name'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];

            $sql = "INSERT INTO leads (name, email, phone) VALUES ('$name', '$email', '$phone')";
            if ($conn->query($sql) === TRUE) {
                echo "<div class='alert alert-success mt-3'>Lead added successfully!</div>";
            } else {
                echo "<div class='alert alert-danger mt-3'>Error: " . $conn->error . "</div>";
            }
        }
        ?>
    </div>
</body>
</html>