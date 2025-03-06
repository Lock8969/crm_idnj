<?php
$host = "127.0.0.1"; // Cloudways MySQL Host
$dbname = "xpjjbrbbmv"; // Cloudways Database Name
$username = "xpjjbrbbmv"; // Cloudways Database Username
$password = "Fs8YyHyejv"; // Cloudways Database Password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    exit("Database connection failed: " . $e->getMessage());
}
?>
