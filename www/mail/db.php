<?php

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
    echo "DB connection failed: " . htmlspecialchars($e->getMessage());
    exit;
}
