<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employer') {
    header("Location: login.php");
    exit;
}
include 'config.php';
$user_id = $_SESSION['userID'];
$user_name = $_SESSION['name'];
$navbar_title = 'Assessment';

// Fetch reports for employer's internships
$stmt = $pdo->prepare("SELECT r.reportID, r.studentID, r.internshipID, r.documentPath, i.title AS internship_title, u.name AS student_name 
                       FROM Reports r 
                       JOIN Internships i ON r.internshipID = i.internshipID 
                       JOIN Users u ON r.studentID = u.userID 
                       WHERE i.employerID = ?");
$stmt->execute([$user_id]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle assessment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_assessment'])) {
    $reportID = $_POST['reportID'];
    $grade = $_POST['grade'];
    $comments = $_POST['comments'];
    try {
        // Insert or update grade in是谁
        $stmt = $pdo->prepare("INSERT INTO Grades (studentID, internshipID, grade, comments) 
                               VALUES ((SELECT studentID FROM Reports WHERE reportID = ?), 
                                       (SELECT internshipID FROM Reports WHERE reportID = ?), ?, ?) 
                               ON DUPLICATE KEY UPDATE grade = ?, comments = ?");
        $stmt->execute([$reportID, $reportID, $grade, $comments, $grade, $comments]);
        // Add notification
        $stmt = $pdo->prepare("INSERT INTO Notifications (userID, message) VALUES (?, ?)");
        $stmt->execute([$user_id, "Submitted assessment for report ID: $reportID"]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Assessment submitted successfully'];
        header("Location: employer_assessment.php");
        exit;
    } catch (Exception $e) {
        $error = "Assessment submission failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed layout-wide" dir="ltr" data-theme="theme-default" data-assets-path="./assets/" data-template="vertical-menu-template">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>IMS - Assessment</title>
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
</head>
<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include 'sidebar.php'; ?>
            <div class="layout-page">
                <?php include 'navbar.php'; ?>
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <?php if (isset($_SESSION['toast'])): ?>
                            <div class="bs-toast toast toast-placement-ex m-2 fade bg-<?php echo $_SESSION['toast']['type']; ?> top-0 end-0 show" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="toast-header">
                                    <i class="bx bx-bell me-2"></i>
                                    <div class="me-auto fw-medium">Notification</div>
                                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                                <div class="toast-body"><?php echo htmlspecialchars($_SESSION['toast']['message']); ?></div>
                            </div>
                            <?php unset($_SESSION['toast']); ?>
                        <?php endif; ?>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <h4 class="py-3 mb-4"><span class="text-muted fw-light">Employer /</span> Assessment</h4>

                        <!-- Student Reports -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Student Reports</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($reports)): ?>
                                    <p>No reports submitted yet.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Report ID</th>
                                                    <th>Internship</th>
                                                    <th>Student</th>
                                                    <th>Report</th>
                                                    <th>Current Grade</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($reports as $report): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($report['reportID']); ?></td>
                                                        <td><?php echo htmlspecialchars($report['internship_title']); ?></td>
                                                        <td><?php echo htmlspecialchars($report['student_name']); ?></td>
                                                        <td>
                                                            <a href="<?php echo htmlspecialchars($report['documentPath']); ?>" 
                                                               target="_blank" 
                                                               class="text-primary">
                                                                View Report
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $stmt = $pdo->prepare("SELECT grade, comments FROM Grades WHERE studentID = ? AND internshipID = ?");
                                                            $stmt->execute([$report['studentID'], $report['internshipID']]);
                                                            $grade = $stmt->fetch(PDO::FETCH_ASSOC);
                                                            echo $grade ? htmlspecialchars($grade['grade']) . ($grade['comments'] ? ' (' . htmlspecialchars($grade['comments']) . ')' : '') : 'Not graded';
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#assessmentModal<?php echo $report['reportID']; ?>">
                                                                Assess
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <!-- Assessment Modal -->
                                                    <div class="modal fade" 
                                                         id="assessmentModal<?php echo $report['reportID']; ?>" 
                                                         tabindex="-1" 
                                                         aria-labelledby="assessmentModalLabel<?php echo $report['reportID']; ?>" 
                                                         aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="assessmentModalLabel<?php echo $report['reportID']; ?>">
                                                                        Assess Report: <?php echo htmlspecialchars($report['internship_title']); ?> - <?php echo htmlspecialchars($report['student_name']); ?>
                                                                    </h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <form method="POST">
                                                                        <input type="hidden" name="reportID" value="<?php echo $report['reportID']; ?>">
                                                                        <div class="mb-3">
                                                                            <label for="grade<?php echo $report['reportID']; ?>" class="form-label">Grade</label>
                                                                            <input type="text" 
                                                                                   name="grade" 
                                                                                   id="grade<?php echo $report['reportID']; ?>" 
                                                                                   class="form-control" 
                                                                                   value="<?php echo $grade ? htmlspecialchars($grade['grade']) : ''; ?>" 
                                                                                   required>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label for="comments<?php echo $report['reportID']; ?>" class="form-label">Comments</label>
                                                                            <textarea name="comments" 
                                                                                      id="comments<?php echo $report['reportID']; ?>" 
                                                                                      class="form-control"><?php echo $grade ? htmlspecialchars($grade['comments']) : ''; ?></textarea>
                                                                        </div>
                                                                        <button type="submit" name="submit_assessment" class="btn btn-primary">Submit Assessment</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
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