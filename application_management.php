<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employer') {
    header("Location: login.php");
    exit;
}
include 'config.php';
$user_id = $_SESSION['userID'];
$user_name = $_SESSION['name'];
$navbar_title = 'Application Management';

// Check if ApplicationDocuments table exists
$tables = $pdo->query("SHOW TABLES LIKE 'ApplicationDocuments'")->fetchAll();
$applicationDocumentsExists = count($tables) > 0;

// Fetch applications for employer's internships with document count
try {
    if ($applicationDocumentsExists) {
        $stmt = $pdo->prepare("SELECT a.applicationID, a.studentID, a.status, a.appliedAt, 
                                      i.title, u.name AS student_name, u.studentID AS student_id,
                                      (SELECT COUNT(*) FROM ApplicationDocuments WHERE applicationID = a.applicationID) AS doc_count 
                               FROM Applications a 
                               JOIN Internships i ON a.internshipID = i.internshipID 
                               JOIN Users u ON a.studentID = u.userID 
                               WHERE i.employerID = ? 
                               ORDER BY a.appliedAt DESC");
    } else {
        $stmt = $pdo->prepare("SELECT a.applicationID, a.studentID, a.status, a.appliedAt, 
                                      i.title, u.name AS student_name, u.studentID AS student_id,
                                      0 AS doc_count 
                               FROM Applications a 
                               JOIN Internships i ON a.internshipID = i.internshipID 
                               JOIN Users u ON a.studentID = u.userID 
                               WHERE i.employerID = ? 
                               ORDER BY a.appliedAt DESC");
    }
    $stmt->execute([$user_id]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching applications: " . $e->getMessage();
}

// Handle application status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $applicationID = $_POST['applicationID'];
    $status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE Applications SET status = ? WHERE applicationID = ?");
        $stmt->execute([$status, $applicationID]);
        $studentID = $pdo->query("SELECT studentID FROM Applications WHERE applicationID = $applicationID")->fetchColumn();
        $studentEmail = $pdo->query("SELECT email FROM Users WHERE userID = $studentID")->fetchColumn();
        $message = "Your application status updated to: $status";
        
        // Insert notification
        $stmt = $pdo->prepare("INSERT INTO Notifications (userID, message) VALUES (?, ?)");
        $stmt->execute([$studentID, $message]);
        
        // Send email
        include 'send_email.php';
        sendEmail($studentEmail, "IMS Application Update", "<h3>Application Update</h3><p>$message</p>");
        
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Application status updated successfully'];
        header("Location: application_management.php");
        exit;
    } catch (PDOException $e) {
        $error = "Failed to update status: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed layout-wide" dir="ltr" data-theme="theme-default" data-assets-path="./assets/" data-template="vertical-menu-template">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>IMS - Application Management</title>
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
                        <?php if (!$applicationDocumentsExists): ?>
                            <div class="alert alert-warning">Warning: ApplicationDocuments table is missing. Document-related features are disabled.</div>
                        <?php endif; ?>
                        <h4 class="py-3 mb-4"><span class="text-muted fw-light">Employer /</span> Application Management</h4>

                        <!-- Manage Applications -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Student Applications</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($applications)): ?>
                                    <p>No applications yet.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Student</th>
                                                    <th>Student ID</th>
                                                    <th>Internship</th>
                                                    <th>Status</th>
                                                    <th>Applied At</th>
                                                    <th>Documents</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($applications as $app): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($app['student_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($app['student_id']); ?></td>
                                                        <td><?php echo htmlspecialchars($app['title']); ?></td>
                                                        <td><?php echo htmlspecialchars($app['status']); ?></td>
                                                        <td><?php echo htmlspecialchars($app['appliedAt']); ?></td>
                                                        <td>
                                                            <?php if ($applicationDocumentsExists && $app['doc_count'] > 0): ?>
                                                                <a href="view_documents.php?applicationID=<?php echo $app['applicationID']; ?>" class="btn btn-sm btn-outline-primary">View Resume</a>
                                                            <?php else: ?>
                                                                No documents
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="applicationID" value="<?php echo $app['applicationID']; ?>">
                                                                <select name="status" class="form-select d-inline-block w-auto">
                                                                    <option value="Pending" <?php echo $app['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                                    <option value="Approved" <?php echo $app['status'] === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                                                    <option value="Rejected" <?php echo $app['status'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                                </select>
                                                                <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                                <a href="employer_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
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