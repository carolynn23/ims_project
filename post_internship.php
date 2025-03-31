<?php
// post_internship.php
session_start();
if ($_SESSION['role'] !== 'Employer') {
    header("Location: index.php");
    exit;
}
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $duration = $_POST['duration'];
    $requirements = $_POST['requirements'];
    $employerID = $_SESSION['userID'];

    $stmt = $pdo->prepare("INSERT INTO Internships (employerID, title, description, location, duration, requirements, status) 
                           VALUES (?, ?, ?, ?, ?, ?, 'Open')");
    try {
        $stmt->execute([$employerID, $title, $description, $location, $duration, $requirements]);
        $message = "Internship posted successfully!";
    } catch (PDOException $e) {
        $error = "Error posting internship: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Post Internship</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Post New Internship</h1>
    <?php 
    if (isset($message)) echo "<p style='color: green;'>$message</p>";
    if (isset($error)) echo "<p style='color: red;'>$error</p>";
    ?>
    <form method="POST">
        <label>Title: <input type="text" name="title" required></label><br>
        <label>Description: <textarea name="description" required></textarea></label><br>
        <label>Location: <input type="text" name="location"></label><br>
        <label>Duration: <input type="text" name="duration"></label><br>
        <label>Requirements: <textarea name="requirements"></textarea></label><br>
        <button type="submit">Post Internship</button>
    </form>
    <a href="employer_dashboard.php">Back to Dashboard</a>
</body>
</html>