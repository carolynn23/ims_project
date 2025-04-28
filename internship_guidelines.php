<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}
include 'config.php';
$user_id = $_SESSION['userID'];
$user_name = $_SESSION['name'];
$navbar_title = 'Internship Guidelines';

// Fetch guidelines
$stmt = $pdo->query("SELECT guidelineID, title, content, updatedAt FROM InternshipGuidelines ORDER BY updatedAt DESC");
$guidelines = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        header("Location: internship_guidelines.php");
        exit;
    } catch (PDOException $e) {
        $error = "Failed to save guideline: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed layout-wide" dir="ltr" data-theme="theme-default" data-assets-path="./assets/" data-template="vertical-menu-template">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>IMS - Internship Guidelines</title>
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
                        <h4 class="py-3 mb-4"><span class="text-muted fw-light">Admin / Internship Management /</span> Guidelines</h4>

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