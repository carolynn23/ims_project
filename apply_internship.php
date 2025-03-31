<?php
// apply_internship.php
session_start();
if ($_SESSION['role'] !== 'Student') {
    header("Location: index.php");
    exit;
}
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $internshipID = $_POST['internshipID'];
    $studentID = $_SESSION['userID'];

    $stmt = $pdo->prepare("INSERT INTO Applications (studentID, internshipID, status) VALUES (?, ?, 'Pending')");
    try {
        $stmt->execute([$studentID, $internshipID]);
        $message = "Application submitted successfully!";
    } catch (PDOException $e) {
        $error = "Error submitting application: " . $e->getMessage();
    }
}

// Fetch open internships
$stmt = $pdo->query("SELECT i.internshipID, i.title, i.description, i.location, u.name AS employer 
                     FROM Internships i 
                     JOIN Users u ON i.employerID = u.userID 
                     WHERE i.status = 'Open'");
$internships = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apply for Internship</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Apply for Internship</h1>
    <?php 
    if (isset($message)) echo "<p style='color: green;'>$message</p>";
    if (isset($error)) echo "<p style='color: red;'>$error</p>";
    ?>
    <h2>Available Internships</h2>
    <?php foreach ($internships as $internship): ?>
        <div>
            <h3><?php echo htmlspecialchars($internship['title']); ?></h3>
            <p><?php echo htmlspecialchars($internship['description']); ?></p>
            <p>Location: <?php echo htmlspecialchars($internship['location']); ?></p>
            <p>Employer: <?php echo htmlspecialchars($internship['employer']); ?></p>
            <form method="POST">
                <input type="hidden" name="internshipID" value="<?php echo $internship['internshipID']; ?>">
                <button type="submit">Apply</button>
            </form>
        </div>
        <hr>
    <?php endforeach; ?>
    <a href="student_dashboard.php">Back to Dashboard</a>
</body>
</html>