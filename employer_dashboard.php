<?php
session_start();
if ($_SESSION['role'] !== 'Employer') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employer Dashboard</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Employer Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
    <ul>
        <li><a href="post_internship.php">Post Internship</a></li>
        <li><a href="#">Review Applications</a></li>
        <li><a href="#">Evaluate Interns</a></li>
    </ul>
    <a href="index.php">Back</a> | <a href="logout.php">Logout</a>
</body>
</html>