<?php
// signup.php
session_start();
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Student') header("Location: student_dashboard.php");
    elseif ($_SESSION['role'] === 'Lecturer') header("Location: lecturer_dashboard.php");
    elseif ($_SESSION['role'] === 'Employer') header("Location: employer_dashboard.php");
    exit;
}
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $studentID = ($role === 'Student') ? trim($_POST['studentID']) : NULL;

    // Validate studentID for Student role
    if ($role === 'Student' && empty($studentID)) {
        $error = "Student ID is required for students.";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE email = ? OR (studentID = ? AND studentID IS NOT NULL)");
        $stmt->execute([$email, $studentID]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Email or Student ID already registered.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO Users (name, email, password, role, studentID) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password, $role, $studentID]);
            header("Location: login.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style" dir="ltr" data-theme="theme-default" data-assets-path="./assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>IMS - Sign Up</title>
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
    <script>
        function toggleStudentID() {
            var role = document.getElementById('role').value;
            var studentIDField = document.getElementById('studentIDField');
            studentIDField.style.display = (role === 'Student') ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <div class="card">
                    <div class="card-body">
                        <h4 class="mb-2">Sign Up for IMS</h4>
                        <p class="mb-4">Create your account.</p>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required />
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required />
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required />
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required onchange="toggleStudentID()">
                                    <option value="Student">Student</option>
                                    <option value="Lecturer">Lecturer</option>
                                    <option value="Employer">Employer</option>
                                </select>
                            </div>
                            <div class="mb-3" id="studentIDField" style="display: block;">
                                <label for="studentID" class="form-label">Student ID</label>
                                <input type="text" class="form-control" id="studentID" name="studentID" placeholder="e.g., STU001" />
                            </div>
                            <button type="submit" class="btn btn-primary d-grid w-100">Sign Up</button>
                        </form>
                        <p class="text-center mt-3">
                            Have an account? <a href="login.php">Login</a>
                        </p>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger mt-3" role="alert"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
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