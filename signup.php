<?php
session_start();
include 'config.php';

// Handle signup submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['signup'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $csrf_token = $_POST['csrf_token'];

    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        die('CSRF validation failed');
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO Users (email, password, name, role) VALUES (?, ?, 'New User', ?)");
        $stmt->execute([$email, $password, $role]);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['role'] = $role;
        $_SESSION['email'] = $email;
        error_log("Signup successful: email=$email, role=$role, user_id=" . $_SESSION['user_id']);
        if (strtolower($role) === 'student') {
            $_SESSION['new_user'] = true; // Flag for new student
            header("Location: student-profile-setup-1.php");
        } else {
            $_SESSION['toast'] = ['type' => 'success', 'message' => 'Account created! Please log in.'];
            header("Location: login.php");
        }
        exit;
    } catch (PDOException $e) {
        $error = "Signup failed: " . ($e->getCode() == 23000 ? 'Email already exists.' : $e->getMessage());
        error_log("Signup error: " . $e->getMessage());
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed layout-wide" dir="ltr" data-theme="theme-default" data-assets-path="./assets/" data-template="vertical-menu-template">
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
    <link rel="stylesheet" href="./assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <style>
        .authentication-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 0;
        }
        .authentication-inner {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            padding: 0;
        }
        .signup-card {
            padding: 2rem;
        }
        .role-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            justify-content: center;
        }
        .role-btn {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            border: 1px solid #d9dee3;
            border-radius: 0.375rem;
            background: #fff;
            transition: all 0.3s ease;
        }
        .role-btn:hover, .role-btn.active {
            background-color: #696cff;
            color: #fff;
            border-color: #696cff;
        }
        .google-btn {
            background: transparent;
            border: 1px solid #4285f4;
            color: #4285f4;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        .google-btn:hover {
            background-color: rgba(66, 133, 244, 0.1);
            color: #357abd;
            border-color: #357abd;
        }
        .google-icon {
            width: 20px;
            height: 20px;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"><path fill="#4285F4" d="M24 9.5c3.2 0 5.9 1.1 8.1 3.1l6-6C33.2 3.6 28.1 2 24 2 15.6 2 8.4 6.6 4.2 13.7l7.4 5.7c1.5-4.4 5.3-7.7 10.4-7.9z"/><path fill="#34A853" d="M46.2 24.2c0-1.3-.1-2.5-.4-3.7H24v7h10.8c-.5 2.6-1.9 4.8-4.1 6.2l6.4 5c3.8-3.5 6.1-8.6 6.1-14.5z"/><path fill="#FBBC05" d="M13.6 33.8c4.8 4.1 11.5 6.6 19.4 6.6 5.9 0 10.9-2 14.5-5.4l-6.4-5c-1.8 1.2-4.1 2-6.1 2-4.7 0-8.7-3.2-10.1-7.4H4.2v4.7c2.3 4.5 7 7.4 12.4 7.7z"/><path fill="#EA4335" d="M4.2 18.7v4.7h14.5c.8-3.7 3.1-6.6 6-7.8l-6.4-5C14.6 8.6 10.5 11.1 8.1 14.7 7.5 15.6 7 16.6 6.6 17.6L4.2 18.7z"/></svg>') no-repeat center;
        }
    </style>
    <script src="./assets/vendor/js/helpers.js"></script>
    <script src="./assets/js/config.js"></script>
</head>
<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <div class="layout-page">
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="authentication-wrapper authentication-basic">
                            <div class="authentication-inner py-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="mb-2">Sign Up 👋</h4>
                                        <p class="mb-4">Please create your account</p>
                                        <form method="POST" class="needs-validation" novalidate>
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <div class="role-buttons">
                                                <button type="button" class="role-btn btn-sm <?php echo isset($_POST['role']) && $_POST['role'] == 'Student' ? 'active' : ''; ?>" 
                                                        onclick="document.getElementById('role').value='Student'; this.classList.add('active'); 
                                                                 document.querySelectorAll('.role-btn').forEach(btn => btn !== this && btn.classList.remove('active'));">Student</button>
                                                <button type="button" class="role-btn btn-sm <?php echo isset($_POST['role']) && $_POST['role'] == 'Employer' ? 'active' : ''; ?>" 
                                                        onclick="document.getElementById('role').value='Employer'; this.classList.add('active'); 
                                                                 document.querySelectorAll('.role-btn').forEach(btn => btn !== this && btn.classList.remove('active'));">Organization</button>
                                                <button type="button" class="role-btn btn-sm <?php echo isset($_POST['role']) && $_POST['role'] == 'Lecturer' ? 'active' : ''; ?>" 
                                                        onclick="document.getElementById('role').value='Lecturer'; this.classList.add('active'); 
                                                                 document.querySelectorAll('.role-btn').forEach(btn => btn !== this && btn.classList.remove('active'));">Lecturer</button>
                                                <input type="hidden" name="role" id="role" value="<?php echo $_POST['role'] ?? ''; ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" name="email" id="email" class="form-control" required>
                                                <div class="invalid-feedback">Please enter a valid email.</div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="password" class="form-label">Password</label>
                                                <input type="password" name="password" id="password" class="form-control" required minlength="6">
                                                <div class="invalid-feedback">Password must be at least 6 characters.</div>
                                            </div>
                                            <button type="submit" name="signup" class="btn btn-primary w-100 mb-2">Sign Up</button>
                                            <p class="text-center mb-3">Already have an account? <a href="login.php" class="text-primary">Log in</a></p>
                                            <button type="button" class="google-btn" onclick="alert('Google OAuth setup required');">
                                                <span class="google-icon"></span> Continue with Google
                                            </button>
                                        </form>
                                        <?php if (isset($error)): ?>
                                            <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($error); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <script>
        (function() {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
    <script src="./assets/vendor/libs/jquery/jquery.js"></script>
    <script src="./assets/vendor/libs/popper/popper.js"></script>
    <script src="./assets/vendor/js/bootstrap.js"></script>
    <script src="./assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="./assets/vendor/js/menu.js"></script>
    <script src="./assets/js/main.js"></script>
</body>
</html>