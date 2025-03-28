<?php
// login.php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password']; // In production, hash this!

    $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && $user['passwordHash'] === $password) { // Use password_verify() in production
        $_SESSION['userID'] = $user['userID'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IMS - Login</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Login</h1>
    <?php if (isset($error)) echo "<p>$error</p>"; ?>
    <form method="POST">
        <label>Email: <input type="email" name="email" required></label><br>
        <label>Password: <input type="password" name="password" required></label><br>
        <button type="submit">Login</button>
    </form>
</body>
</html>