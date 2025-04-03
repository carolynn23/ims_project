<?php
// index.php
session_start();
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Student') header("Location: student_dashboard.php");
    elseif ($_SESSION['role'] === 'Lecturer') header("Location: lecturer_dashboard.php");
    elseif ($_SESSION['role'] === 'Employer') header("Location: employer_dashboard.php");
    exit;
} else {
    header("Location: login.php"); // Redirects to login instead of landing
    exit;
}
?>
