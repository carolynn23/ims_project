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

// Fetch all users
$stmt = $pdo->query("SELECT userID, name, email, role, studentID, status, createdAt FROM Users ORDER BY createdAt DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch system stats
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM Users WHERE status = 'Active'")->fetchColumn(),
    'internships' => $pdo->query("SELECT COUNT(*) FROM Internships WHERE status = 'Open'")->fetchColumn(),
    'applications' => $pdo->query("SELECT COUNT(*) FROM Applications")->fetchColumn(),
    'approved_apps' => $pdo->query("SELECT COUNT(*) FROM Applications WHERE status = 'Approved'")->fetchColumn(),
    'grades' => $pdo->query("SELECT COUNT(*) FROM Grades")->fetchColumn(),
    'logins' => $pdo->query("SELECT COUNT(*) FROM Users WHERE lastLogin IS NOT NULL")->fetchColumn()
];

// Fetch all internships
$stmt = $pdo->query("SELECT i.internshipID, i.title, i.status, i.postedAt, u.name AS employer_name 
                     FROM Internships i JOIN Users u ON i.employerID = u.userID");
$internships = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch guidelines
$stmt = $pdo->query("SELECT guidelineID, title, content, updatedAt FROM InternshipGuidelines ORDER BY updatedAt DESC");
$guidelines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch timelines
$stmt = $pdo->query("SELECT timelineID, title, startDate, endDate FROM InternshipTimelines ORDER BY startDate DESC");
$timelines = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $studentID = $role === 'Student' ? trim($_POST['studentID']) : NULL;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO Users (name, email, password, role, studentID, status) VALUES (?, ?, ?, ?, ?, 'Active')");
        $stmt->execute([$name, $email, $password, $role, $studentID]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'User added successfully'];
        header("Location: admin_dashboard.php");
        exit;
    } catch (PDOException $e) {
        $error = "Failed to add user: " . $e->getMessage();
    }
}

// Handle user update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $userID = $_POST['userID'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $studentID = $role === 'Student' ? trim($_POST['studentID']) : NULL;
    $status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE Users SET name = ?, email = ?, role = ?, studentID = ?, status = ? WHERE userID = ?");
        $stmt->execute([$name, $email, $role, $studentID, $status, $userID]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'User updated successfully'];
        header("Location: admin_dashboard.php");
        exit;
    } catch (PDOException $e) {
        $error = "Failed to update user: " . $e->getMessage();
    }
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $userID = $_POST['userID'];
    try {
        $stmt = $pdo->prepare("DELETE FROM Users WHERE userID = ?");
        $stmt->execute([$userID]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'User deleted successfully'];
        header("Location: admin_dashboard.php");
        exit;
    } catch (PDOException $e) {
        $error = "Failed to delete user: " . $e->getMessage();
    }
}

// Handle internship status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_internship'])) {
    $internshipID = $_POST['internshipID'];
    $status = $_POST['status'];
    try {
        $stmt = $pdo->prepare("UPDATE Internships SET status = ? WHERE internshipID = ?");
        $stmt->execute([$status, $internshipID]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Internship status updated successfully'];
        header("Location: admin_dashboard.php");
        exit;
    } catch (PDOException $e) {
        $error = "Failed to update internship: " . $e->getMessage();
    }
}

// Handle guideline creation/update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_guideline'])) {
    $guidelineID = $_POST['guidelineID'] ?: null;
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    
    try {
        if ($guidelineID) {
            $stmt = $pdo->prepare("UPDATE InternshipGuidelines SET title = ?, content = ? WHERE guidelineID = ?");
            $stmt->execute([$title, $content, $guidelineID]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO InternshipGuidelines (title, content) VALUES (?, ?)");
            $stmt->execute([$title, $content]);
        }
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Guideline saved successfully'];
        header("Location: admin_dashboard.php");
        exit;
    } catch (PDOException $e) {
        $error = "Failed to save guideline: " . $e->getMessage();
    }
}

// Handle timeline creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_timeline'])) {
    $title = trim($_POST['title']);
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO InternshipTimelines (title, startDate, endDate) VALUES (?, ?, ?)");
        $stmt->execute([$title, $startDate, $endDate]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Timeline saved successfully'];
        header("Location: admin_dashboard.php");
        exit;
    } catch (PDOException $e) {
        $error = "Failed to save timeline: " . $e->getMessage();
    }
}

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
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
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

                        <!-- Add User -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Add New User</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="name" name="name" required />
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" required />
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" required />
                                    </div>
                                    <div class="mb-3">
                                        <label for="role" class="form-label">Role</label>
                                        <select class="form-select" id="role" name="role" required onchange="toggleStudentID()">
                                            <option value="Student">Student</option>
                                            <option value="Lecturer">Lecturer</option>
                                            <option value="Employer">Employer</option>
                                            <option value="Admin">Admin</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="studentID_div" style="display: none;">
                                        <label for="studentID" class="form-label">Student ID</label>
                                        <input type="text" class="form-control" id="studentID" name="studentID" />
                                    </div>
                                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                                </form>
                            </div>
                        </div>

                        <!-- User Management -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">User Management</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Student ID</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['studentID'] ?: 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($user['status']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['createdAt']); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['userID']; ?>">Edit</button>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="userID" value="<?php echo $user['userID']; ?>">
                                                            <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                <!-- Edit User Modal -->
                                                <div class="modal fade" id="editUserModal<?php echo $user['userID']; ?>" tabindex="-1" aria-labelledby="editUserModalLabel<?php echo $user['userID']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="editUserModalLabel<?php echo $user['userID']; ?>">Edit User</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form method="POST">
                                                                    <input type="hidden" name="userID" value="<?php echo $user['userID']; ?>">
                                                                    <div class="mb-3">
                                                                        <label for="name<?php echo $user['userID']; ?>" class="form-label">Name</label>
                                                                        <input type="text" class="form-control" id="name<?php echo $user['userID']; ?>" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required />
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="email<?php echo $user['userID']; ?>" class="form-label">Email</label>
                                                                        <input type="email" class="form-control" id="email<?php echo $user['userID']; ?>" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required />
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="role<?php echo $user['userID']; ?>" class="form-label">Role</label>
                                                                        <select class="form-select" id="role<?php echo $user['userID']; ?>" name="role" required onchange="toggleStudentIDModal(<?php echo $user['userID']; ?>)">
                                                                            <option value="Student" <?php echo $user['role'] === 'Student' ? 'selected' : ''; ?>>Student</option>
                                                                            <option value="Lecturer" <?php echo $user['role'] === 'Lecturer' ? 'selected' : ''; ?>>Lecturer</option>
                                                                            <option value="Employer" <?php echo $user['role'] === 'Employer' ? 'selected' : ''; ?>>Employer</option>
                                                                            <option value="Admin" <?php echo $user['role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-3" id="studentID_div<?php echo $user['userID']; ?>" style="display: <?php echo $user['role'] === 'Student' ? 'block' : 'none'; ?>;">
                                                                        <label for="studentID<?php echo $user['userID']; ?>" class="form-label">Student ID</label>
                                                                        <input type="text" class="form-control" id="studentID<?php echo $user['userID']; ?>" name="studentID" value="<?php echo htmlspecialchars($user['studentID']); ?>" />
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="status<?php echo $user['userID']; ?>" class="form-label">Status</label>
                                                                        <select class="form-select" id="status<?php echo $user['userID']; ?>" name="status" required>
                                                                            <option value="Active" <?php echo $user['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                                                            <option value="Inactive" <?php echo $user['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                                        </select>
                                                                    </div>
                                                                    <button type="submit" name="update_user" class="btn btn-primary">Save Changes</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

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
                            </div>
                        </div>

                        <!-- Internship Guidelines -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Internship Guidelines</h5>
                            </div>
                            <div class="card-body">
                                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addGuidelineModal">Add Guideline</button>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Content</th>
                                                <th>Updated</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($guidelines as $guideline): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($guideline['title']); ?></td>
                                                    <td><?php echo htmlspecialchars(substr($guideline['content'], 0, 50)) . '...'; ?></td>
                                                    <td><?php echo htmlspecialchars($guideline['updatedAt']); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editGuidelineModal<?php echo $guideline['guidelineID']; ?>">Edit</button>
                                                    </td>
                                                </tr>
                                                <!-- Edit Guideline Modal -->
                                                <div class="modal fade" id="editGuidelineModal<?php echo $guideline['guidelineID']; ?>" tabindex="-1" aria-labelledby="editGuidelineModalLabel<?php echo $guideline['guidelineID']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="editGuidelineModalLabel<?php echo $guideline['guidelineID']; ?>">Edit Guideline</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form method="POST">
                                                                    <input type="hidden" name="guidelineID" value="<?php echo $guideline['guidelineID']; ?>">
                                                                    <div class="mb-3">
                                                                        <label for="title<?php echo $guideline['guidelineID']; ?>" class="form-label">Title</label>
                                                                        <input type="text" class="form-control" id="title<?php echo $guideline['guidelineID']; ?>" name="title" value="<?php echo htmlspecialchars($guideline['title']); ?>" required />
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="content<?php echo $guideline['guidelineID']; ?>" class="form-label">Content</label>
                                                                        <textarea class="form-control" id="content<?php echo $guideline['guidelineID']; ?>" name="content" rows="5" required><?php echo htmlspecialchars($guideline['content']); ?></textarea>
                                                                    </div>
                                                                    <button type="submit" name="save_guideline" class="btn btn-primary">Save</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Add Guideline Modal -->
                                <div class="modal fade" id="addGuidelineModal" tabindex="-1" aria-labelledby="addGuidelineModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="addGuidelineModalLabel">Add Guideline</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST">
                                                    <div class="mb-3">
                                                        <label for="title_new" class="form-label">Title</label>
                                                        <input type="text" class="form-control" id="title_new" name="title" required />
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="content_new" class="form-label">Content</label>
                                                        <textarea class="form-control" id="content_new" name="content" rows="5" required></textarea>
                                                    </div>
                                                    <button type="submit" name="save_guideline" class="btn btn-primary">Save</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

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
        // Toggle Student ID field in Add User form
        function toggleStudentID() {
            var role = document.getElementById('role').value;
            document.getElementById('studentID_div').style.display = role === 'Student' ? 'block' : 'none';
        }

        // Toggle Student ID field in Edit User modal
        function toggleStudentIDModal(userID) {
            var role = document.getElementById('role' + userID).value;
            document.getElementById('studentID_div' + userID).style.display = role === 'Student' ? 'block' : 'none';
        }

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