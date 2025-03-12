<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="author" content="idnj" />

    <!-- Favicon icon-->
    <link rel="shortcut icon" type="image/x-icon" href="/dashui/assets/images/favicon/favicon.ico" />

    <!-- Color modes -->
    <script src="/dashui/assets/js/vendors/color-modes.js"></script>

    <!-- Libs CSS -->
    <link href="/dashui/assets/libs/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="/dashui/assets/libs/@mdi/font/css/materialdesignicons.min.css" rel="stylesheet" />
    <link href="/dashui/assets/libs/simplebar/dist/simplebar.min.css" rel="stylesheet" />

    <!-- Theme CSS -->
    <link rel="stylesheet" href="/dashui/assets/css/theme.min.css">

    <title>Sign Up | IDNJ - Bootstrap 5 Admin Dashboard Template</title>
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
                        <form method="POST" action="/create_user.php">
                            <!-- Username -->
                            <div class="mb-3">
                                <label for="username" class="form-label">User Name</label>
                                <input type="text" id="username" class="form-control" name="username" placeholder="User Name" required />
                            </div>
                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" class="form-control" name="email" placeholder="Email address here" required />
                            </div>
                            <!-- Password -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" class="form-control" name="password" placeholder="**************" required />
                            </div>
                            <!-- Confirm Password -->
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" id="confirm_password" class="form-control" name="confirm_password" placeholder="**************" required />
                            </div>
                            <div>
                                <!-- Button -->
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Create Account</button>
                                </div>

                                <div class="d-md-flex justify-content-between mt-4">
                                    <div class="mb-2 mb-md-0">
                                        <a href="login.php" class="fs-5">Already member? Login</a>
                                    </div>
                                    <div>
                                        <a href="forget-password.html" class="text-inherit fs-5">Forgot your password?</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($_GET['error']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_GET['success']) && $_GET['success'] == 'true'): ?>
                        <div class="alert alert-success mt-3">
                            <p class="mb-2">Account created successfully!</p>
                            <div class="text-center">
                                <a href="login.php" class="btn btn-primary">Login Now</a>
                                </div>
                            </div>
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