<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}
include 'config.php';
$user_id = $_SESSION['userID'];
$user_name = $_SESSION['name'];
$navbar_title = 'Admin Dashboard';

// Fetch system stats
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM Users WHERE status = 'Active'")->fetchColumn(),
    'internships' => $pdo->query("SELECT COUNT(*) FROM Internships WHERE status = 'Open'")->fetchColumn(),
    'applications' => $pdo->query("SELECT COUNT(*) FROM Applications")->fetchColumn(),
    'approved_apps' => $pdo->query("SELECT COUNT(*) FROM Applications WHERE status = 'Approved'")->fetchColumn(),
    'grades' => $pdo->query("SELECT COUNT(*) FROM Grades")->fetchColumn(),
    'logins' => $pdo->query("SELECT COUNT(*) FROM Users WHERE lastLogin IS NOT NULL")->fetchColumn()
];

// Fetch analytics data
$app_trends = $pdo->query("SELECT DATE_FORMAT(appliedAt, '%Y-%m') AS month, COUNT(*) AS count 
                           FROM Applications 
                           GROUP BY month 
                           ORDER BY month DESC 
                           LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
$app_labels = array_column($app_trends, 'month');
$app_data = array_column($app_trends, 'count');

$approval_rates = $pdo->query("SELECT status, COUNT(*) AS count 
                              FROM Applications 
                              GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
$approval_labels = array_column($approval_rates, 'status');
$approval_data = array_column($approval_rates, 'count');

$grades = $pdo->query("SELECT grade, COUNT(*) AS count 
                       FROM Grades 
                       GROUP BY grade")->fetchAll(PDO::FETCH_ASSOC);
$grade_labels = array_column($grades, 'grade');
$grade_data = array_column($grades, 'count');

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_report'])) {
    $reportType = $_POST['reportType'];
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Ymd') . '.csv"');
    $output = fopen('php://output', 'w');
    
    if ($reportType === 'applications') {
        fputcsv($output, ['Student', 'Internship', 'Status', 'Applied At']);
        $stmt = $pdo->query("SELECT u.name, i.title, a.status, a.appliedAt 
                             FROM Applications a 
                             JOIN Users u ON a.studentID = u.userID 
                             JOIN Internships i ON a.internshipID = i.internshipID");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [$row['name'], $row['title'], $row['status'], $row['appliedAt']]);
        }
    } elseif ($reportType === 'approvals') {
        fputcsv($output, ['Student', 'Internship', 'Applied At']);
        $stmt = $pdo->query("SELECT u.name, i.title, a.appliedAt 
                             FROM Applications a 
                             JOIN Users u ON a.studentID = u.userID 
                             JOIN Internships i ON a.internshipID = i.internshipID 
                             WHERE a.status = 'Approved'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [$row['name'], $row['title'], $row['appliedAt']]);
        }
    } elseif ($reportType === 'evaluations') {
        fputcsv($output, ['Student', 'Lecturer', 'Grade', 'Comments']);
        $stmt = $pdo->query("SELECT u1.name AS student, u2.name AS lecturer, g.grade, g.comments 
                             FROM Grades g 
                             JOIN Users u1 ON g.studentID = u1.userID 
                             JOIN Users u2 ON g.lecturerID = u2.userID");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [$row['student'], $row['lecturer'], $row['grade'], $row['comments']]);
        }
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed layout-wide" dir="ltr" data-theme="theme-default" data-assets-path="./assets/" data-template="vertical-menu-template">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>IMS - Admin Dashboard</title>
    <link rel="icon" type="image/x-icon" href="./assets/img/favicon/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="./assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="./assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="./assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="./assets/css/demo.css" />
    <link rel="stylesheet" href="./assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="./assets/vendor/libs/apex-charts/apex-charts.css" />
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
                        <h4 class="py-3 mb-4"><span class="text-muted fw-light">Dashboard /</span> Admin</h4>

                        <!-- Welcome & Stats -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Welcome, <?php echo htmlspecialchars($user_name); ?>!</h5>
                                <p class="card-text">System Usage & Performance:</p>
                                <div class="row">
                                    <div class="col-md-2 mb-2"><strong>Active Users:</strong> <?php echo $stats['users']; ?></div>
                                    <div class="col-md-2 mb-2"><strong>Open Internships:</strong> <?php echo $stats['internships']; ?></div>
                                    <div class="col-md-2 mb-2"><strong>Total Applications:</strong> <?php echo $stats['applications']; ?></div>
                                    <div class="col-md-2 mb-2"><strong>Approved Apps:</strong> <?php echo $stats['approved_apps']; ?></div>
                                    <div class="col-md-2 mb-2"><strong>Grades:</strong> <?php echo $stats['grades']; ?></div>
                                    <div class="col-md-2 mb-2"><strong>Recent Logins:</strong> <?php echo $stats['logins']; ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Analytics Charts -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Application Trends</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="applicationTrendsChart"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Approval Rates</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="approvalRatesChart"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Grade Distribution</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="gradeDistributionChart"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Report Generation -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Generate Reports</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="reportType" class="form-label">Report Type</label>
                                        <select class="form-select" id="reportType" name="reportType" required>
                                            <option value="applications">All Applications</option>
                                            <option value="approvals">Approved Applications</option>
                                            <option value="evaluations">Evaluations</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="generate_report" class="btn btn-primary">Generate CSV</button>
                                </form>
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
    <script src="./assets/vendor/libs/apex-charts/apexcharts.js"></script>
    <script src="./assets/js/main.js"></script>
    <script>
        // ApexCharts for Analytics
        document.addEventListener('DOMContentLoaded', function () {
            var options = {
                chart: { height: 300, type: 'line', toolbar: { show: false } },
                series: [{ name: 'Applications', data: <?php echo json_encode($app_data); ?> }],
                xaxis: { categories: <?php echo json_encode($app_labels); ?>, title: { text: 'Month' } },
                yaxis: { title: { text: 'Applications' } },
                colors: ['#696cff'],
                stroke: { curve: 'smooth' },
                dataLabels: { enabled: false }
            };
            new ApexCharts(document.querySelector('#applicationTrendsChart'), options).render();

            options = {
                chart: { height: 300, type: 'bar', toolbar: { show: false } },
                series: [{ name: 'Count', data: <?php echo json_encode($approval_data); ?> }],
                xaxis: { categories: <?php echo json_encode($approval_labels); ?>, title: { text: 'Status' } },
                yaxis: { title: { text: 'Count' } },
                colors: ['#696cff'],
                dataLabels: { enabled: false }
            };
            new ApexCharts(document.querySelector('#approvalRatesChart'), options).render();

            options = {
                chart: { height: 300, type: 'bar', toolbar: { show: false } },
                series: [{ name: 'Count', data: <?php echo json_encode($grade_data); ?> }],
                xaxis: { categories: <?php echo json_encode($grade_labels); ?>, title: { text: 'Grade' } },
                yaxis: { title: { text: 'Count' } },
                colors: ['#696cff'],
                dataLabels: { enabled: false }
            };
            new ApexCharts(document.querySelector('#gradeDistributionChart'), options).render();
        });
    </script>
</body>
</html>