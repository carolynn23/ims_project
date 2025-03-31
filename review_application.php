<?php
// review_applications.php
session_start();
if ($_SESSION['role'] !== 'Employer') {
    header("Location: index.php");
    exit;
}
include 'config.php';

$employerID = $_SESSION['userID'];

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $applicationID = $_POST['applicationID'];
    $newStatus = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE Applications SET status = ? WHERE applicationID = ? AND internshipID IN 
                           (SELECT internshipID FROM Internships WHERE employerID = ?)");
    try {
        $stmt->execute([$newStatus, $applicationID, $employerID]);
        $message = "Application updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating application: " . $e->getMessage();
        // Note: The trigger will prevent multiple approvals for the same student
    }
}

// Fetch applications for this employer's internships
$stmt = $pdo->prepare("SELECT a.applicationID, a.studentID, a.status, a.appliedAt, 
                              i.title, u.name AS studentName
                       FROM Applications a
                       JOIN Internships i ON a.internshipID = i.internshipID
                       JOIN Users u ON a.studentID = u.userID
                       WHERE i.employerID = ?");
$stmt->execute([$employerID]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Applications</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Review Applications</h1>
    <?php 
    if (isset($message)) echo "<p style='color: green;'>$message</p>";
    if (isset($error)) echo "<p style='color: red;'>$error</p>";
    ?>
    <table>
        <tr>
            <th>Student</th>
            <th>Internship</th>
            <th>Status</th>
            <th>Applied At</th>
            <th>Action</th>
        </tr>
        <?php foreach ($applications as $app): ?>
            <tr>
                <td><?php echo htmlspecialchars($app['studentName']); ?></td>
                <td><?php echo htmlspecialchars($app['title']); ?></td>
                <td><?php echo htmlspecialchars($app['status']); ?></td>
                <td><?php echo htmlspecialchars($app['appliedAt']); ?></td>
                <td>
                    <?php if ($app['status'] == 'Pending'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="applicationID" value="<?php echo $app['applicationID']; ?>">
                            <input type="hidden" name="status" value="Approved">
                            <button type="submit">Approve</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="applicationID" value="<?php echo $app['applicationID']; ?>">
                            <input type="hidden" name="status" value="Rejected">
                            <button type="submit" style="background-color: #f44336;">Reject</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <a href="employer_dashboard.php">Back to Dashboard</a>
</body>
</html>