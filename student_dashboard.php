<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Student') {
    header("Location: login.php");
    exit;
}
include 'config.php';
$user_id = $_SESSION['userID'];
$user_name = $_SESSION['name'];
$navbar_title = 'Student Dashboard';

// Fetch studentID from database if not in session
$student_id = isset($_SESSION['studentID']) ? $_SESSION['studentID'] : null;
if (!$student_id) {
    $stmt = $pdo->prepare("SELECT studentID FROM Users WHERE userID = ?");
    $stmt->execute([$user_id]);
    $student_id = $stmt->fetchColumn();
    $_SESSION['studentID'] = $student_id;
}

// Fetch available internships (Open status, not applied)
$stmt = $pdo->prepare("SELECT i.internshipID, i.title, i.description, i.location, i.duration, i.posterPath, u.name AS employer 
                       FROM Internships i 
                       JOIN Users u ON i.employerID = u.userID 
                       WHERE i.status = 'Open' 
                       AND i.internshipID NOT IN (SELECT internshipID FROM Applications WHERE studentID = ?)");
$stmt->execute([$user_id]);
$internships = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch student applications
$stmt = $pdo->prepare("SELECT a.applicationID, a.status, i.title 
                       FROM Applications a 
                       JOIN Internships i ON a.internshipID = i.internshipID 
                       WHERE a.studentID = ?");
$stmt->execute([$user_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch notifications
$stmt = $pdo->prepare("SELECT message, status, createdAt FROM Notifications WHERE userID = ? ORDER BY createdAt DESC");
//$stmt->execute([$user_id]);//
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle application submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply'])) {
    $internshipID = $_POST['internshipID'];
    $upload_dir = 'Uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    try {
        // Insert application
        $stmt = $pdo->prepare("INSERT INTO Applications (studentID, internshipID, status) VALUES (?, ?, 'Pending')");
        $stmt->execute([$user_id, $internshipID]);
        $applicationID = $pdo->lastInsertId();

        // Upload CV
        if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK && $_FILES['cv']['type'] === 'application/pdf') {
            $cvPath = $upload_dir . time() . '_cv_' . basename($_FILES['cv']['name']);
            if (move_uploaded_file($_FILES['cv']['tmp_name'], $cvPath)) {
                $stmt = $pdo->prepare("INSERT INTO ApplicationDocuments (applicationID, documentPath, documentType) VALUES (?, ?, ?)");
                $stmt->execute([$applicationID, $cvPath, 'CV']);
            } else {
                throw new Exception("Failed to move CV file.");
            }
        } else {
            throw new Exception("Invalid or missing CV file. Please upload a PDF.");
        }

        // Upload Resume
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK && $_FILES['resume']['type'] === 'application/pdf') {
            $resumePath = $upload_dir . time() . '_resume_' . basename($_FILES['resume']['name']);
            if (move_uploaded_file($_FILES['resume']['tmp_name'], $resumePath)) {
                $stmt = $pdo->prepare("INSERT INTO ApplicationDocuments (applicationID, documentPath, documentType) VALUES (?, ?, ?)");
                $stmt->execute([$applicationID, $resumePath, 'Resume']);
            } else {
                throw new Exception("Failed to move resume file.");
            }
        } else {
            throw new Exception("Invalid or missing resume file. Please upload a PDF.");
        }

        $stmt = $pdo->prepare("INSERT INTO Notifications (userID, message) VALUES (?, ?)");
        $stmt->execute([$user_id, "You applied for an internship: " . $_POST['title']]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Application submitted successfully'];
        header("Location: student_dashboard.php");
        exit;
    } catch (Exception $e) {
        $error = "Application failed: " . $e->getMessage();
    }
}

// Handle report submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_report'])) {
    $internshipID = $_POST['internshipID'];
    $report = $_FILES['report'];
    $upload_dir = 'Uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    try {
        if ($report['error'] === UPLOAD_ERR_OK && $report['type'] === 'application/pdf') {
            $report_path = $upload_dir . time() . '_' . basename($report['name']);
            if (move_uploaded_file($report['tmp_name'], $report_path)) {
                $stmt = $pdo->prepare("INSERT INTO Reports (studentID, internshipID, documentPath) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $internshipID, $report_path]);
                $stmt = $pdo->prepare("INSERT INTO Notifications (userID, message) VALUES (?, ?)");
                $stmt->execute([$user_id, "You submitted a report for internship ID: $internshipID"]);
                $_SESSION['toast'] = ['type' => 'success', 'message' => 'Report submitted successfully'];
                header("Location: student_dashboard.php");
                exit;
            } else {
                throw new Exception("Failed to move report file.");
            }
        } else {
            throw new Exception("Invalid or missing report file. Please upload a PDF.");
        }
    } catch (Exception $e) {
        $error = "Report upload failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed layout-wide" dir="ltr" data-theme="theme-default" data-assets-path="./assets/" data-template="vertical-menu-template">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>IMS - Student Dashboard</title>
    <link rel="icon" type="image/x-icon" href="./assets/img/favicon/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="./assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="./assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="./assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="./assets/css/demo.css" />
    <link rel="stylesheet" href="./assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <style>
        .poster-img {
            width: 600px;
            height: 500px;
            border-radius: 8px;
            cursor: pointer;
            object-fit: cover;
        }
        .poster-placeholder {
            width: 300px;
            height: 200px;
            border-radius: 8px;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 14px;
            color: #696cff;
            border: 1px solid #d9dee3;
        }
        .modal-fullscreen-img {
            max-width: 90vw;
            max-height: 90vh;
            margin: auto;
            display: block;
        }
        .internship-card {
            margin-bottom: 2rem;
        }
    </style>
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
                                    tton type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                                <div class="toast-body"><?php echo htmlspecialchars($_SESSION['toast']['message']); ?></div>
                            </div>
                            <?php unset($_SESSION['toast']); ?>
                        <?php endif; ?>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        

                        <!-- Welcome & Notifications -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Welcome, <?php echo htmlspecialchars($user_name); ?>!</h5>
                                <p class="card-text">Student ID: <?php echo htmlspecialchars($student_id); ?></p>
                                <h6>Notifications</h6>
                                <?php if (empty($notifications)): ?>
                                    <p>No notifications yet.</p>
                                <?php else: ?>
                                    <ul class="list-group">
                                        <?php foreach ($notifications as $notif): ?>
                                            <li class="list-group-item <?php echo $notif['status'] === 'Unread' ? 'list-group-item-warning' : ''; ?>">
                                                <?php echo htmlspecialchars($notif['message']) . " (" . $notif['createdAt'] . ")"; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Available Internships -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Available Internships</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($internships)): ?>
                                    <p>No open internships available.</p>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($internships as $internship): ?>
                                            <div class="col-md-6 internship-card">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <!-- Poster -->
                                                        <div class="text-center mb-3">
                                                            <?php if ($internship['posterPath']): ?>
                                                                <?php
                                                                $extension = strtolower(pathinfo($internship['posterPath'], PATHINFO_EXTENSION));
                                                                if (in_array($extension, ['jpg', 'jpeg', 'png'])): ?>
                                                                    <img src="<?php echo htmlspecialchars($internship['posterPath']); ?>" 
                                                                         alt="Poster" 
                                                                         class="poster-img" 
                                                                         data-bs-toggle="modal" 
                                                                         data-bs-target="#posterModal<?php echo $internship['internshipID']; ?>" 
                                                                         data-full-img="<?php echo htmlspecialchars($internship['posterPath']); ?>">
                                                                    <!-- Unique Modal for Each Internship -->
                                                                    <div class="modal fade" 
                                                                         id="posterModal<?php echo $internship['internshipID']; ?>" 
                                                                         tabindex="-1" 
                                                                         aria-labelledby="posterModalLabel<?php echo $internship['internshipID']; ?>" 
                                                                         aria-hidden="true">
                                                                        <div class="modal-dialog modal-xl">
                                                                            <div class="modal-content">
                                                                                <div class="modal-header">
                                                                                    <h5 class="modal-title" id="posterModalLabel<?php echo $internship['internshipID']; ?>">
                                                                                        Internship Poster: <?php echo htmlspecialchars($internship['title']); ?>
                                                                                    </h5>
                                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                </div>
                                                                                <div class="modal-body text-center">
                                                                                    <img src="<?php echo htmlspecialchars($internship['posterPath']); ?>" 
                                                                                         alt="Full Poster" 
                                                                                         class="modal-fullscreen-img">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php elseif ($extension === 'pdf'): ?>
                                                                    <div class="poster-placeholder">
                                                                        <p>PDF Poster: <a href="<?php echo htmlspecialchars($internship['posterPath']); ?>" target="_blank" class="text-primary">Download</a></p>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div class="poster-placeholder">
                                                                        <p>Invalid poster format</p>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <div class="poster-placeholder">
                                                                    <p>No poster available</p>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <!-- Details -->
                                                        <h5><?php echo htmlspecialchars($internship['title']); ?></h5>
                                                        <p><strong>Description:</strong> <?php echo htmlspecialchars($internship['description']); ?></p>
                                                        <p><strong>Location:</strong> <?php echo htmlspecialchars($internship['location'] ?: 'N/A'); ?></p>
                                                        <p><strong>Duration:</strong> <?php echo htmlspecialchars($internship['duration'] ?: 'N/A'); ?></p>
                                                        <p><strong>Employer:</strong> <?php echo htmlspecialchars($internship['employer']); ?></p>
                                                        <!-- Apply Form -->
                                                        <form method="POST" enctype="multipart/form-data">
                                                            <input type="hidden" name="internshipID" value="<?php echo $internship['internshipID']; ?>">
                                                            <input type="hidden" name="title" value="<?php echo htmlspecialchars($internship['title']); ?>">
                                                            <div class="mb-3">
                                                                <label for="cv<?php echo $internship['internshipID']; ?>" class="form-label">Upload CV (PDF)</label>
                                                                <input type="file" 
                                                                       name="cv" 
                                                                       id="cv<?php echo $internship['internshipID']; ?>" 
                                                                       accept=".pdf" 
                                                                       required 
                                                                       class="form-control" />
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="resume<?php echo $internship['internshipID']; ?>" class="form-label">Upload Resume (PDF)</label>
                                                                <input type="file" 
                                                                       name="resume" 
                                                                       id="resume<?php echo $internship['internshipID']; ?>" 
                                                                       accept=".pdf" 
                                                                       required 
                                                                       class="form-control" />
                                                            </div>
                                                            <button type="submit" name="apply" class="btn btn-primary">Apply</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Resources & Academic History -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Resources & Guidelines</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info" role="alert">
                                    <i class="bx bx-info-circle me-2"></i>
                                Please verify that all necessary information and documents are correct and up to date to ensure a smooth internship application process. 
                                Students are allowed to apply for as many internships are possible.
                                Remember to follow all the instructions when appplying.
                                </div>
                        
                                <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student_id); ?></p>
                                </p>
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