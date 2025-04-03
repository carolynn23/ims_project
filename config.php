<?php
// config.php
$host = 'localhost';
$dbname = 'ims_db';
$username = 'root'; // Default XAMpp MySQL user
$password = '';     // Default XAMPP MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>