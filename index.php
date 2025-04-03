<?php
// index.php
session_start();
include 'config.php';
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
    <?php if (isset($_SESSION['userID'])): ?>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <p><a href="login.php">Login</a> to access the system.</p>
    <?php endif; ?>
    <script src="js/scripts.js"></script>
</body>
</html>