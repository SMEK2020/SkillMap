<?php
// Database configuration
$host = "localhost";
$db_name = "skillmap"; // তোমার ডাটাবেসের নাম
$username = "root";    // ডাটাবেস ইউজারনেম
$password = "";        // ডাটাবেস পাসওয়ার্ড

try {
    // PDO connection
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
