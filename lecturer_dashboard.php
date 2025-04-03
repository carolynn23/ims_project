<?php
session_start();
if ($_SESSION['role'] !== 'Lecturer') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lecturer Dashboard</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Lecturer Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
    <ul>
        <li><a href="#">Monitor Progress</a></li>
        <li><a href="#">Review Assessments</a></li>
        <li><a href="#">Assign Grades</a></li>
    </ul>
    <a href="index.php">Back</a> | <a href="logout.php">Logout</a>
</body>
</html>