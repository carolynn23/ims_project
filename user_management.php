<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}
include 'config.php';
$user_id = $_SESSION['userID'];
$user_name = $_SESSION['name'];
$navbar_title = 'User Management';

// Fetch all users
$stmt = $pdo->query("SELECT userID, name, email, role, studentID, status, createdAt FROM Users ORDER BY createdAt DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        header("Location: user_management.php");
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
        header("Location: user_management.php");
        exit;
    } catch (PDOException $e) {
        $error = "Failed to delete user: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed layout-wide" dir="ltr" data-theme="theme-default" data-assets-path="./assets/" data-template="vertical-menu-template">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>IMS - User Management</title>
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
                        <h4 class="py-3 mb-4"><span class="text-muted fw-light">Admin /</span> User Management</h4>

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
    <script>
        // Toggle Student ID field in Edit User modal
        function toggleStudentIDModal(userID) {
            console.log('toggleStudentIDModal called for userID: ' + userID);
            var role = document.getElementById('role' + userID).value;
            var studentIDDiv = document.getElementById('studentID_div' + userID);
            if (role === 'Student') {
                studentIDDiv.style.display = 'block';
            } else {
                studentIDDiv.style.display = 'none';
            }
        }
    </script>
</body>
</html>