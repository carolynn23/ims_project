<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employer') {
    header("Location: login.php");
    exit;
}
include 'config.php';
$user_id = $_SESSION['userID'];
$user_name = $_SESSION['name'];
$navbar_title = 'Manage Applications';

// Fetch employerID
$employer_id = isset($_SESSION['employerID']) ? $_SESSION['employerID'] : null;
if (!$employer_id) {
    $stmt = $pdo->prepare("SELECT employerID FROM Users WHERE userID = ?");
    
    $employer_id = $stmt->fetchColumn();
    $_SESSION['employerID'] = $employer_id;
}

// Fetch applications for employer's internships
$stmt = $pdo->prepare("SELECT a.applicationID, a.studentID, a.internshipID, a.status, i.title AS internship_title, u.name AS student_name 
                       FROM Applications a 
                       JOIN Internships i ON a.internshipID = i.internshipID 
                       JOIN Users u ON a.studentID = u.userID 
                       WHERE i.employerID = ?");
$stmt->execute([$user_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle application status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $applicationID = $_POST['applicationID'];
    $new_status = $_POST['status'];
    try {
        $stmt = $pdo->prepare("UPDATE Applications SET status = ? WHERE applicationID = ? AND internshipID IN (SELECT internshipID FROM Internships WHERE employerID = ?)");
        $stmt->execute([$new_status, $applicationID, $user_id]);
        $stmt = $pdo->prepare("INSERT INTO Notifications (userID, message) VALUES (?, ?)");
        $stmt->execute([$user_id, "Updated application status to $new_status for application ID: $applicationID"]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Application status updated successfully'];
        header("Location: manage_applications.php");
        exit;
    } catch (Exception $e) {
        $error = "Status update failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed layout-wide" dir="ltr" data-theme="theme-default" data-assets-path="./assets/" data-template="vertical-menu-template">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>IMS - Manage Applications</title>
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
                        <h4 class="py-3 mb-4"><span class="text-muted fw-light">Employer /</span> Manage Applications</h4>

                        <!-- Applications Received -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Manage Applications</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($applications)): ?>
                                    <p>No applications received yet.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Application ID</th>
                                                    <th>Internship</th>
                                                    <th>Student</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($applications as $app): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($app['applicationID']); ?></td>
                                                        <td><?php echo htmlspecialchars($app['internship_title']); ?></td>
                                                        <td><?php echo htmlspecialchars($app['student_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($app['status']); ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#applicationModal<?php echo $app['applicationID']; ?>">
                                                                View Documents
                                                            </button>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="applicationID" value="<?php echo $app['applicationID']; ?>">
                                                                <button type="submit" name="update_status" value="Accepted" class="btn btn-sm btn-success">Approve</button>
                                                                <button type="submit" name="update_status" value="Rejected" class="btn btn-sm btn-danger">Reject</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                    <!-- Application Documents Modal -->
                                                    <div class="modal fade" 
                                                         id="applicationModal<?php echo $app['applicationID']; ?>" 
                                                         tabindex="-1" 
                                                         aria-labelledby="applicationModalLabel<?php echo $app['applicationID']; ?>" 
                                                         aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="applicationModalLabel<?php echo $app['applicationID']; ?>">
                                                                        Application: <?php echo htmlspecialchars($app['internship_title']); ?> - <?php echo htmlspecialchars($app['student_name']); ?>
                                                                    </h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <?php
                                                                    $stmt = $pdo->prepare("SELECT documentPath, documentType FROM ApplicationDocuments WHERE applicationID = ?");
                                                                    $stmt->execute([$app['applicationID']]);
                                                                    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                                    if (empty($documents)): ?>
                                                                        <p>No documents available.</p>
                                                                    <?php else: ?>
                                                                        <ul class="list-group">
                                                                            <?php foreach ($documents as $doc): ?>
                                                                                <li class="list-group-item">
                                                                                    <a href="<?php echo htmlspecialchars($doc['documentPath']); ?>" 
                                                                                       target="_blank" 
                                                                                       class="text-primary">
                                                                                        View <?php echo htmlspecialchars($doc['documentType']); ?>
                                                                                    </a>
                                                                                </li>
                                                                            <?php endforeach; ?>
                                                                        </ul>
                                                                    <?php endif; ?>
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