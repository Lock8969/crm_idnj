<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php'; // Include your database connection file

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Fetch locations for the dropdown
try {
    $locations_stmt = $pdo->query("SELECT id, location_name FROM locations ORDER BY location_name");
    $locations = $locations_stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching locations: " . $e->getMessage());
    $locations = [];
}

// Check if the user has a remember me cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    try {
        // Verify the remember token
        $token = $_COOKIE['remember_token'];
        $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = :token");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Token verification error: " . $e->getMessage());
        // Clear the invalid cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $location_id = $_POST['location_id'];
    $remember = isset($_POST['rememberme']);
    
    // Validate location selection
    if (empty($location_id)) {
        $error = "Please select your location.";
    } else {
        try {
            // Using named parameters to be explicit
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
            $stmt->execute([
                'username' => $username,
                'email' => $username
            ]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = "Invalid username or password.";
            } else {
                // Verify the password
                if (password_verify($password, $user['password_hash'])) {
                    // Start the session and store user information
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['location_id'] = $location_id;
                    
                    // Get location name for display purposes
                    $loc_stmt = $pdo->prepare("SELECT location_name FROM locations WHERE id = :id");
                    $loc_stmt->execute(['id' => $location_id]);
                    $location = $loc_stmt->fetch();
                    if ($location) {
                        $_SESSION['location_name'] = $location['location_name'];
                    }
                    
                    // Handle "Remember me" functionality
                    if ($remember) {
                        // Generate a secure token
                        $token = bin2hex(random_bytes(32));
                        
                        // Store token in the database
                        $update = $pdo->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
                        $update->execute([
                            'token' => $token,
                            'id' => $user['id']
                        ]);
                        
                        // Set a cookie that expires in 30 days
                        setcookie('remember_token', $token, time() + (86400 * 30), '/');
                    }
                    
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = "Invalid username or password.";
                }
            }
        } catch (PDOException $e) {
            error_log("Database error in login.php: " . $e->getMessage());
            $error = "A system error occurred. Please try again later.";
        }
    }
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

    <title>Sign In | IDNJ</title>
</head>

<body>
    <!-- container -->
    <main class="container d-flex flex-column">
        <div class="row align-items-center justify-content-center g-0 min-vh-100">
            <div class="col-12 col-md-8 col-lg-6 col-xxl-4 py-8 py-xl-0">
                <div class="position-absolute end-0 top-0 p-8">
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
                </div>
                <!-- Card -->
                <div class="card smooth-shadow-md">
                    <!-- Card body -->
                    <div class="card-body p-6">
                        <div class="mb-4 text-center">
                            <a href="../index.html">
                                <img src="/dashui/assets/images/brand/logo/idnj_full.png" class="d-block mx-auto mb-2 text-inverse" alt="Image" />
                            </a>
                            <p class="mb-6">Please enter your user information.</p>
                        </div>
                        <!-- Form -->
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <!-- Username -->
                            <div class="mb-3">
                                <label for="username" class="form-label">Username or email</label>
                                <input type="text" id="username" class="form-control" name="username" placeholder="Username or email" required />
                            </div>
                            <!-- Password -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" class="form-control" name="password" placeholder="**************" required />
                            </div>
                            <!-- Location Dropdown -->
                            <div class="mb-3">
                                <label for="location_id" class="form-label">Location</label>
                                <select id="location_id" name="location_id" class="form-select" required>
                                    <option value="">Select your location</option>
                                    <?php foreach ($locations as $location): ?>
                                        <option value="<?php echo htmlspecialchars($location['id']); ?>">
                                            <?php echo htmlspecialchars($location['location_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Checkbox -->
                            <div class="d-lg-flex justify-content-between align-items-center mb-4">
                                <div class="form-check custom-checkbox">
                                    <input type="checkbox" class="form-check-input" id="rememberme" name="rememberme" />
                                    <label class="form-check-label" for="rememberme">Remember me</label>
                                </div>
                            </div>
                            <div>
                                <!-- Button -->
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Sign in</button>
                                </div>

                                <div class="d-md-flex justify-content-end mt-4">
                                    <div>
                                        <a href="forgot-password.php" class="text-inherit fs-5">Forgot your password?</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger mt-3"><?php echo $error; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!-- Scripts -->
    <!-- Libs JS -->
    <script src="/dashui/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/dashui/assets/libs/feather-icons/dist/feather.min.js"></script>
    <script src="/dashui/assets/libs/simplebar/dist/simplebar.min.js"></script>

    <!-- Theme JS -->
    <script src="/dashui/assets/js/theme.min.js"></script>

</body>
</html>