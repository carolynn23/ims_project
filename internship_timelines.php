<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}
include 'config.php';
$user_id = $_SESSION['userID'];
$user_name = $_SESSION['name'];
$navbar_title = 'Internship Timelines';

// Fetch timelines
$stmt = $pdo->query("SELECT timelineID, title, startDate, endDate FROM InternshipTimelines ORDER BY startDate DESC");
$timelines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle timeline creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_timeline'])) {
    $title = trim($_POST['title']);
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO InternshipTimelines (title, startDate, endDate) VALUES (?, ?, ?)");
        $stmt->execute([$title, $startDate, $endDate]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Timeline saved successfully'];
        header("Location: internship_timelines.php");
        exit;
    } catch (PDOException $e) {
        $error = "Failed to save timeline: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed layout-wide" dir="ltr" data-theme="theme-default" data-assets-path="./assets/" data-template="vertical-menu-template">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>IMS - Internship Timelines</title>
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
                        <h4 class="py-3 mb-4"><span class="text-muted fw-light">Admin / Internship Management /</span> Timelines</h4>

                        <!-- Internship Timelines -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Internship Timelines</h5>
                            </div>
                            <div class="card-body">
                                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addTimelineModal">Add Timeline</button>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($timelines as $timeline): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($timeline['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($timeline['startDate']); ?></td>
                                                    <td><?php echo htmlspecialchars($timeline['endDate']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Add Timeline Modal -->
                                <div class="modal fade" id="addTimelineModal" tabindex="-1" aria-labelledby="addTimelineModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="addTimelineModalLabel">Add Timeline</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST">
                                                    <div class="mb-3">
                                                        <label for="title_timeline" class="form-label">Title</label>
                                                        <input type="text" class="form-control" id="title_timeline" name="title" required />
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="startDate" class="form-label">Start Date</label>
                                                        <input type="date" class="form-control" id="startDate" name="startDate" required />
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="endDate" class="form-label">End Date</label>
                                                        <input type="date" class="form-control" id="endDate" name="endDate" required />
                                                    </div>
                                                    <button type="submit" name="save_timeline" class="btn btn-primary">Save</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <a href="admin_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
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