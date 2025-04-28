<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}
include 'config.php';
$user_id = $_SESSION['userID'];
$user_name = $_SESSION['name'];
$navbar_title = 'Internship Management';

// Fetch all internships
$stmt = $pdo->query("SELECT i.internshipID, i.title, i.status, i.postedAt, u.name AS employer_name 
                     FROM Internships i JOIN Users u ON i.employerID = u.userID");
$internships = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle internship status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_internship'])) {
    $internshipID = $_POST['internshipID'];
    $status = $_POST['status'];
    try {
        $stmt = $pdo->prepare("UPDATE Internships SET status = ? WHERE internshipID = ?");
        $stmt->execute([$status, $internshipID]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Internship status updated successfully'];
        header("Location: internship_management.php");
        exit;
    } catch (PDOException $e) {
        $error = "Failed to update internship: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed layout-wide" dir="ltr" data-theme="theme-default" data-assets-path="./assets/" data-template="vertical-menu-template">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>IMS - Internship Management</title>
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
                        <h4 class="py-3 mb-4"><span class="text-muted fw-light">Admin /</span> Internship Management</h4>

                        <!-- Internship Management -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Internship Management</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Employer</th>
                                                <th>Status</th>
                                                <th>Posted</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($internships as $internship): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($internship['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($internship['employer_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($internship['status']); ?></td>
                                                    <td><?php echo htmlspecialchars($internship['postedAt']); ?></td>
                                                    <td>
                                                        <form method="POST">
                                                            <input type="hidden" name="internshipID" value="<?php echo $internship['internshipID']; ?>">
                                                            <select name="status" class="form-select d-inline-block w-auto">
                                                                <option value="Open" <?php echo $internship['status'] === 'Open' ? 'selected' : ''; ?>>Open</option>
                                                                <option value="Closed" <?php echo $internship['status'] === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                                                            </select>
                                                            <button type="submit" name="update_internship" class="btn btn-sm btn-primary">Update</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
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