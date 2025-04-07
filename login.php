<?php
// login.php
session_start();
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Student') header("Location: student_dashboard.php");
    elseif ($_SESSION['role'] === 'Lecturer') header("Location: lecturer_dashboard.php");
    elseif ($_SESSION['role'] === 'Employer') header("Location: employer_dashboard.php");
    exit;
}
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['userID'] = $user['userID'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        if ($user['role'] === 'Student') header("Location: student_dashboard.php");
        elseif ($user['role'] === 'Lecturer') header("Location: lecturer_dashboard.php");
        elseif ($user['role'] === 'Employer') header("Location: employer_dashboard.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-wide" dir="ltr" data-theme="theme-default" data-assets-path="./assets/" data-template="vertical-menu-template">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>IMS - Login</title>
    <meta name="description" content="" />
    <link rel="icon" type="image/x-icon" href="./assets/img/favicon/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="./assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="./assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="./assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="./assets/css/demo.css" />
    <link rel="stylesheet" href="./assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <script src="./assets/vendor/js/helpers.js"></script>
    <script src="./assets/js/config.js"></script>
    <style>
        /* Center and shrink the authentication wrapper */
        .authentication-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* Full viewport height */
            padding: 0; /* Remove extra padding */
        }
        .authentication-inner {
            max-width: 450px; /* Smaller width */
            width: 100%; /* Responsive */
            margin: 0 auto; /* Center horizontally */
        }
        .card {
            box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.1); /* Subtle shadow */
        }
    </style>
</head>
<body>
    <div class="authentication-wrapper authentication-basic">
        <div class="authentication-inner py-4">
            <!-- Login -->
            <div class="card p-2">
                <div class="card-body">
                    <div class="app-brand justify-content-center mb-4">
                        <a href="index.php" class="app-brand-link gap-2">
                            <span class="app-brand-text demo text-heading fw-bold">IMS</span>
                        </a>
                    </div>
                    <h4 class="mb-2">Welcome to IMS! 👋</h4>
                    <p class="mb-4">Please sign-in to your account</p>
                    <form id="formAuthentication" class="mb-3" method="POST">
                        <div class="form-floating form-floating-outline mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" autofocus required />
                            <label for="email">Email</label>
                        </div>
                        <div class="form-floating form-floating-outline mb-3">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required />
                            <label for="password">Password</label>
                        </div>
                        <div class="mb-3">
                            <button class="btn btn-primary d-grid w-100" type="submit">Sign in</button>
                        </div>
                    </form>
                    <p class="text-center">
                        <span>New on our platform?</span>
                        <a href="signup.php">
                            <span>Create an account</span>
                        </a>
                    </p>
                    <p class="text-center">
                        <a href="forgot_password.php">
                            <span>Forgot Password?</span>
                        </a>
                    </p>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger mt-3" role="alert"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- /Login -->
        </div>
    </div>
    <script src="./assets/vendor/libs/jquery/jquery.js"></script>
    <script src="./assets/vendor/libs/popper/popper.js"></script>
    <script src="./assets/vendor/js/bootstrap.js"></script>
    <script src="./assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="./assets/vendor/js/menu.js"></script>
    <script src="./assets/js/main.js"></script>
</body>
</html>