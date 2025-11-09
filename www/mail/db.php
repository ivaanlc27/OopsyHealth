<?php
// db.php - simple PDO helper
$host = 'db_mysql';
$db   = 'oopsy_db';
$user = 'oopsy_user';
$pass = 'oopsy_user_pass';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // In a lab, show message; in production do not expose.
    echo "DB connection failed: " . htmlspecialchars($e->getMessage());
    exit;
}
