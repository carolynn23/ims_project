<?php
$host = 'localhost';
$db = 'ims2_db'; // Confirmed correct variable
$user = 'root';
$pass = ''; // Update if you have a MySQL root password
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<!-- PDO Connected Successfully -->";
} catch (PDOException $e) {
    echo "<!-- PDO Error: " . $e->getMessage() . " -->";
}
?>