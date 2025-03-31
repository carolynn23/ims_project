<?php
session_start();
if ($_SESSION['role'] !== 'Student') {
    header("Location: index.php");
    exit;
}
include 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Student Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
    <ul>
        <li><a href="apply_internship.php">Apply for Internship</a></li>
        <li><a href="#">View Applications</a></li>
        <li><a href="#">Submit Reports</a></li>
    </ul>
    <a href="index.php">Back</a> | <a href="logout.php">Logout</a>
</body>
</html>