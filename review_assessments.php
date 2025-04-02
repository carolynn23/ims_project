<?php
// review_assessments.php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Lecturer') {
    header("Location: index.php");
    exit;
}
include 'config.php';

// Fetch all reports with studentID
$stmt = $pdo->prepare("SELECT r.reportID, r.studentID AS student_id, r.internshipID, r.documentPath, r.submittedAt, r.grade,
                              u.name AS studentName, i.title AS internshipTitle
                       FROM Reports r
                       JOIN Users u ON r.studentID = u.userID
                       JOIN Internships i ON r.internshipID = i.internshipID");
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle grading
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reportID = $_POST['reportID'];
    $grade = strtoupper(trim($_POST['grade']));
    if (!in_array($grade, ['A', 'B', 'C', 'D', 'F'])) {
        $error = "Invalid grade. Use A, B, C, D, or F.";
    } else {
        $stmt = $pdo->prepare("UPDATE Reports SET grade = ? WHERE reportID = ?");
        $stmt->execute([$grade, $reportID]);
        $message = "Grade assigned successfully!";
        header("Location: review_assessments.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="./assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>IMS - Review Assessments</title>
    <meta name="description" content="Review and grade student reports" />
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
                        <a href="lecturer_dashboard.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-home-circle"></i>
                            <div data-i18n="Dashboard">Dashboard</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="#" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-user-check"></i>
                            <div data-i18n="Monitor">Monitor Progress</div>
                        </a>
                    </li>
                    <li class="menu-item active">
                        <a href="review_assessments.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-comment"></i>
                            <div data-i18n="Assessments">Review Assessments</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="#" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-edit"></i>
                            <div data-i18n="Grades">Assign Grades</div>
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
                                                    <small class="text-muted">Lecturer</small>
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
                        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Lecturer /</span> Review Assessments</h4>
                        <div class="card">
                            <h5 class="card-header">Student Reports</h5>
                            <div class="table-responsive text-nowrap">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Student</th>
                                            <th>Internship</th>
                                            <th>Document</th>
                                            <th>Submitted At</th>
                                            <th>Grade</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($reports)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No reports submitted yet.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($reports as $report): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($report['student_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($report['studentName']); ?></td>
                                                    <td><?php echo htmlspecialchars($report['internshipTitle']); ?></td>
                                                    <td>
                                                        <a href="<?php echo htmlspecialchars($report['documentPath']); ?>" target="_blank">View Report</a>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($report['submittedAt']); ?></td>
                                                    <td><?php echo htmlspecialchars($report['grade'] ?? 'Not Graded'); ?></td>
                                                    <td>
                                                        <?php if (empty($report['grade'])): ?>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="reportID" value="<?php echo $report['reportID']; ?>">
                                                                <input type="text" name="grade" class="form-control d-inline-block w-auto" placeholder="A-F" maxlength="1" required>
                                                                <button type="submit" class="btn btn-primary btn-sm">Grade</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php if (isset($message)): ?>
                            <div class="alert alert-success alert-dismissible mt-3" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible mt-3" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
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