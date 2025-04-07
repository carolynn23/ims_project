<?php
// student_dashboard.php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Student') {
    header("Location: login.php");
    exit;
}
include 'config.php';
$user_id = $_SESSION['userID'];
$user_name = $_SESSION['name'];
// Fetch studentID from database if not in session
$student_id = isset($_SESSION['studentID']) ? $_SESSION['studentID'] : null;
if (!$student_id) {
    $stmt = $pdo->prepare("SELECT studentID FROM Users WHERE userID = ?");
    $stmt->execute([$user_id]);
    $student_id = $stmt->fetchColumn();
    $_SESSION['studentID'] = $student_id; // Set it for future use
}

// Fetch available internships (Open status, not applied)
$stmt = $pdo->prepare("SELECT i.internshipID, i.title, i.description, i.location, i.duration, u.name AS employer 
                       FROM Internships i 
                       JOIN Users u ON i.employerID = u.userID 
                       WHERE i.status = 'Open' 
                       AND i.internshipID NOT IN (SELECT internshipID FROM Applications WHERE studentID = ?)");
$stmt->execute([$user_id]);
$internships = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch student applications
$stmt = $pdo->prepare("SELECT a.applicationID, a.status, i.title 
                       FROM Applications a 
                       JOIN Internships i ON a.internshipID = i.internshipID 
                       WHERE a.studentID = ?");
$stmt->execute([$user_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch notifications
$stmt = $pdo->prepare("SELECT message, status, createdAt FROM Notifications WHERE userID = ? ORDER BY createdAt DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle application submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply'])) {
    $internshipID = $_POST['internshipID'];
    try {
        $stmt = $pdo->prepare("INSERT INTO Applications (studentID, internshipID) VALUES (?, ?)");
        $stmt->execute([$user_id, $internshipID]);
        $stmt = $pdo->prepare("INSERT INTO Notifications (userID, message) VALUES (?, ?)");
        $stmt->execute([$user_id, "You applied for an internship: " . $_POST['title']]);
        header("Location: student_dashboard.php");
        exit;
    } catch (PDOException $e) {
        $error = "Application failed: " . $e->getMessage();
    }
}

// Handle report submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_report'])) {
    $internshipID = $_POST['internshipID'];
    $report = $_FILES['report'];
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $report_path = $upload_dir . time() . '_' . basename($report['name']);
    if (move_uploaded_file($report['tmp_name'], $report_path)) {
        $stmt = $pdo->prepare("INSERT INTO Reports (studentID, internshipID, documentPath) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $internshipID, $report_path]);
        $stmt = $pdo->prepare("INSERT INTO Notifications (userID, message) VALUES (?, ?)");
        $stmt->execute([$user_id, "You submitted a report for internship ID: $internshipID"]);
        header("Location: student_dashboard.php");
        exit;
    } else {
        $error = "Report upload failed.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed layout-wide" dir="ltr" data-theme="theme-default" data-assets-path="./assets/" data-template="vertical-menu-template">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>IMS - Student Dashboard</title>
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
        .menu-vertical { background-color: #4B3F99; }
        .menu-item .menu-link { color: #fff; }
        .menu-item .menu-link:hover { background-color: #6C63FF; }
        .layout-navbar { background-color: #4B3F99; color: #fff; }
        .navbar-brand { color: #fff; }
    </style>
</head>
<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Sidebar -->
            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand demo">
                    <a href="index.php" class="app-brand-link">
                        <span class="app-brand-text demo menu-text fw-bold ms-2">IMS</span>
                    </a>
                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
                        <i class="bx bx-chevron-left bx-sm align-middle"></i>
                    </a>
                </div>
                <div class="menu-inner-shadow"></div>
                <ul class="menu-inner py-1">
                    <li class="menu-item active">
                        <a href="student_dashboard.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-home-circle"></i>
                            <div data-i18n="Dashboard">Dashboard</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="logout.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-log-out"></i>
                            <div data-i18n="Logout">Logout</div>
                        </a>
                    </li>
                </ul>
            </aside>
            <!-- /Sidebar -->
            <div class="layout-page">
                <!-- Navbar -->
                <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                            <i class="bx bx-menu bx-sm"></i>
                        </a>
                    </div>
                    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                        <div class="navbar-nav align-items-center">
                            <div class="nav-item d-flex align-items-center">
                                <span class="navbar-brand">Student Dashboard</span>
                            </div>
                        </div>
                        <ul class="navbar-nav flex-row align-items-center ms-auto">
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <img src="./assets/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar avatar-online">
                                                        <img src="./assets/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <span class="fw-medium d-block"><?php echo htmlspecialchars($user_name); ?></span>
                                                    <small class="text-muted">Student</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li><div class="dropdown-divider"></div></li>
                                    <li>
                                        <a class="dropdown-item" href="logout.php">
                                            <i class="bx bx-power-off me-2"></i>
                                            <span class="align-middle">Log Out</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
                <!-- /Navbar -->
                <!-- Content -->
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <h4 class="py-3 mb-4"><span class="text-muted fw-light">Dashboard /</span> Student</h4>

                        <!-- Welcome & Notifications -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Welcome, <?php echo htmlspecialchars($user_name); ?>!</h5>
                                <p class="card-text">Student ID: <?php echo htmlspecialchars($student_id); ?></p>
                                <h6>Notifications</h6>
                                <?php if (empty($notifications)): ?>
                                    <p>No notifications yet.</p>
                                <?php else: ?>
                                    <ul class="list-group">
                                        <?php foreach ($notifications as $notif): ?>
                                            <li class="list-group-item <?php echo $notif['status'] === 'Unread' ? 'list-group-item-warning' : ''; ?>">
                                                <?php echo htmlspecialchars($notif['message']) . " (" . $notif['createdAt'] . ")"; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Apply for Internships -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Available Internships</h5>
                            </div>
                            <div class="card-body">
                                <?php if (isset($error)): ?>
                                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                <?php endif; ?>
                                <?php if (empty($internships)): ?>
                                    <p>No open internships available.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Description</th>
                                                    <th>Location</th>
                                                    <th>Duration</th>
                                                    <th>Employer</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($internships as $internship): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($internship['title']); ?></td>
                                                        <td><?php echo htmlspecialchars($internship['description']); ?></td>
                                                        <td><?php echo htmlspecialchars($internship['location'] ?: 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($internship['duration'] ?: 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($internship['employer']); ?></td>
                                                        <td>
                                                            <form method="POST">
                                                                <input type="hidden" name="internshipID" value="<?php echo $internship['internshipID']; ?>">
                                                                <input type="hidden" name="title" value="<?php echo htmlspecialchars($internship['title']); ?>">
                                                                <button type="submit" name="apply" class="btn btn-primary btn-sm">Apply</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Track Applications -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Your Applications</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($applications)): ?>
                                    <p>No applications submitted yet.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Internship</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($applications as $app): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($app['title']); ?></td>
                                                        <td><?php echo htmlspecialchars($app['status']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Submit Reports -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Submit Internship Report</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="internshipID" class="form-label">Select Internship</label>
                                        <select class="form-select" id="internshipID" name="internshipID" required>
                                            <?php foreach ($applications as $app): ?>
                                                <?php if ($app['status'] === 'Approved'): ?>
                                                    <option value="<?php echo $app['applicationID']; ?>"><?php echo htmlspecialchars($app['title']); ?></option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="report" class="form-label">Report Document</label>
                                        <input type="file" class="form-control" id="report" name="report" accept=".pdf" required />
                                    </div>
                                    <button type="submit" name="submit_report" class="btn btn-primary">Submit</button>
                                </form>
                            </div>
                        </div>

                        <!-- Resources & Academic History -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Resources & Academic History</h5>
                            </div>
                            <div class="card-body">
                                <p><a href="#" class="btn btn-outline-primary">Internship Guidelines</a></p>
                                <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student_id); ?></p>
                                <p><strong>Grades:</strong> <?php
                                    $stmt = $pdo->prepare("SELECT grade, comments FROM Grades WHERE studentID = ?");
                                    $stmt->execute([$user_id]);
                                    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    echo empty($grades) ? "No grades yet." : implode(", ", array_column($grades, 'grade'));
                                ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="content-backdrop fade"></div>
                </div>
                <!-- /Content -->
            </div>
        </div>
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <script src="./assets/vendor/libs/jquery/jquery.js"></script>
    <script src="./assets/vendor/libs/popper/popper.js"></script>
    <script src="./assets/vendor/js/bootstrap.js"></script>
    <script src="./assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="./assets/vendor/js/menu.js"></script>
    <script src="./assets/js/main.js"></script>
</body>
</html>