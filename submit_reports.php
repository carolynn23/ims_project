<?php
// submit_reports.php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Student') {
    header("Location: index.php");
    exit;
}
include 'config.php';

$studentID = $_SESSION['userID'];

// Fetch approved internships
$stmt = $pdo->prepare("SELECT i.internshipID, i.title
                       FROM Internships i
                       JOIN Applications a ON i.internshipID = a.internshipID
                       WHERE a.studentID = ? AND a.status = 'Approved'");
$stmt->execute([$studentID]);
$internships = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $internshipID = $_POST['internshipID'];
    
    if (isset($_FILES['report']) && $_FILES['report']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $file = $_FILES['report'];
        $fileName = basename($file['name']);
        $fileSize = $file['size'];
        $fileTmp = $file['tmp_name'];
        $fileType = mime_content_type($fileTmp);
        $maxSize = 5 * 1024 * 1024; // 5MB
        $allowedTypes = ['application/pdf'];

        if ($fileSize > $maxSize) {
            $error = "File size exceeds 5MB limit.";
        } elseif (!in_array($fileType, $allowedTypes)) {
            $error = "Only PDF files are allowed.";
        } else {
            $filePath = $uploadDir . time() . '_' . $fileName;
            if (move_uploaded_file($fileTmp, $filePath)) {
                $stmt = $pdo->prepare("INSERT INTO Reports (studentID, internshipID, documentPath) VALUES (?, ?, ?)");
                $stmt->execute([$studentID, $internshipID, $filePath]);
                $message = "Report submitted successfully!";
            } else {
                $error = "Failed to upload report. Check folder permissions.";
            }
        }
    } else {
        $error = "Please select a file to upload.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="./assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>IMS - Submit Reports</title>
    <meta name="description" content="Submit internship reports" />
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
    <script src="./assets/js/demo.js"></script>
</head>
<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand demo">
                    <span class="app-brand-text demo menu-text fw-bolder ms-2">IMS</span>
                </div>
                <div class="menu-inner-shadow"></div>
                <ul class="menu-inner py-1">
                    <li class="menu-item">
                        <a href="student_dashboard.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-home-circle"></i>
                            <div data-i18n="Dashboard">Dashboard</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="apply_internship.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-briefcase"></i>
                            <div data-i18n="Apply">Apply for Internship</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="view_applications.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-file"></i>
                            <div data-i18n="Applications">View Applications</div>
                        </a>
                    </li>
                    <li class="menu-item active">
                        <a href="submit_reports.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-upload"></i>
                            <div data-i18n="Reports">Submit Reports</div>
                        </a>
                    </li>
                </ul>
            </aside>
            <div class="layout-page">
                <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
                    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
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
                                                    <span class="fw-semibold d-block"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                                                    <small class="text-muted">Student</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
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
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Student /</span> Submit Reports</h4>
                        <div class="card">
                            <h5 class="card-header">Submit a Report</h5>
                            <div class="card-body">
                                <?php if (isset($message)): ?>
                                    <div class="alert alert-success alert-dismissible" role="alert">
                                        <?php echo htmlspecialchars($message); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($error)): ?>
                                    <div class="alert alert-danger alert-dismissible" role="alert">
                                        <?php echo htmlspecialchars($error); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>
                                <?php if (empty($internships)): ?>
                                    <p>No approved internships found. Apply and get approved first!</p>
                                <?php else: ?>
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label for="internshipID" class="form-label">Select Internship</label>
                                            <select class="form-select" id="internshipID" name="internshipID" required>
                                                <option value="">Choose an internship</option>
                                                <?php foreach ($internships as $internship): ?>
                                                    <option value="<?php echo $internship['internshipID']; ?>">
                                                        <?php echo htmlspecialchars($internship['title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="report" class="form-label">Upload Report (PDF only, max 5MB)</label>
                                            <input type="file" class="form-control" id="report" name="report" accept=".pdf" required />
                                        </div>
                                        <button type="submit" class="btn btn-primary">Submit Report</button>
                                    </form>
                                <?php endif; ?>
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
                    <div class="content-backdrop fade"></div>
                </div>
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