<?php
// index.php
session_start();
include 'config.php';

if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'];
switch ($role) {
    case 'Admin':
        header("Location: admin_dashboard.php");
        break;
    case 'Student':
        header("Location: student_dashboard.php");
        break;
    case 'Employer':
        header("Location: employer_dashboard.php");
        break;
    case 'Lecturer':
        header("Location: lecturer_dashboard.php");
        break;
    default:
        $error = "Unknown role";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IMS - Home</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Internship Management System</h1>
    <?php if (isset($error)) echo "<p>$error</p>"; ?>
    <p>Redirecting...</p>
    <a href="logout.php">Logout</a>
    <script src="js/scripts.js"></script>
</body>
</html>