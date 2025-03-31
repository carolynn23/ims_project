<?php
session_start();
if ($_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
    <ul>
        <li><a href="#">Manage Users</a></li>
        <li><a href="#">Approve Internships</a></li>
        <li><a href="#">Generate Reports</a></li>
    </ul>
    <a href="index.php">Back</a> | <a href="logout.php">Logout</a>
</body>
</html>