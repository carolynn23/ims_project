<?php
// reset_password.php
session_start();
if (isset($_SESSION['role'])) {
    header("Location: index.php");
    exit;
}
include 'config.php';

$token = $_GET['token'] ?? '';
if (empty($token)) {
    $error = "Invalid or missing token.";
} else {
    $stmt = $pdo->prepare("SELECT * FROM reset_tokens WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $token_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$token_data) {
        $error = "Invalid or expired token.";
    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE Users SET password = ? WHERE userID = ?");
        $stmt->execute([$password, $token_data['userID']]);
        $stmt = $pdo->prepare("DELETE FROM reset_tokens WHERE token = ?");
        $stmt->execute([$token]);
        $message = "Password reset successfully. Please log in.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style" dir="ltr" data-theme="theme-default" data-assets-path="./assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>IMS - Reset Password</title>
    <link rel="icon" type="image/x-icon" href="./assets/img/favicon/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="./assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="./assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="./assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="./assets/css/demo.css" />
    <script src="./assets/vendor/js/helpers.js"></script>
    <script src="./assets/js/demo.js"></script>
</head>
<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <div class="card">
                    <div class="card-body">
                        <h4 class="mb-2">Reset Password</h4>
                        <p class="mb-4">Enter your new password.</p>
                        <?php if (isset($message)): ?>
                            <div class="alert alert-success mb-3" role="alert">
                                <?php echo htmlspecialchars($message); ?> <a href="login.php">Login here</a>.
                            </div>
                        <?php elseif (isset($error)): ?>
                            <div class="alert alert-danger mb-3" role="alert"><?php echo htmlspecialchars($error); ?></div>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required />
                                </div>
                                <button type="submit" class="btn btn-primary d-grid w-100">Reset Password</button>
                            </form>
                        <?php endif; ?>
                        <p class="text-center mt-3">
                            <a href="login.php">Back to Login</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="content-footer footer bg-footer-theme">
        <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
            <div class="mb-2 mb-md-0">
                © <?php echo date("Y"); ?>, Internship Management System
            </div>
        </div>
    </footer>
    <script src="./assets/vendor/libs/jquery/jquery.js"></script>
    <script src="./assets/vendor/libs/popper/popper.js"></script>
    <script src="./assets/vendor/js/bootstrap.js"></script>
    <script src="./assets/js/main.js"></script>
</body>
</html>
