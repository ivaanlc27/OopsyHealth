<?php

session_start();
require_once __DIR__ . '/../mail/db.php';
require_once __DIR__ . '/../includes/jwt_utils.php';

$token = $_COOKIE['auth_token'] ?? null;
$secret = get_jwt_secret_from_db($pdo);
$payload = $token ? jwt_decode_and_verify($token, $secret) : null;

// Verify username exists and role matches in DBs
$query = $pdo->prepare('SELECT role FROM users WHERE username = ? LIMIT 1');
$query->execute([$payload['username'] ?? '']);
$db_role = $query->fetchColumn();
if (!$db_role  || $db_role !== ($payload['role'] ?? '')) {
    header('Location: /');
    exit;
}

if (!$payload || ($payload['role'] ?? '') !== 'pharmacist') {
    
  if ($payload && ($payload['role'] ?? '') === 'doctor') {
      header('Location: /doctor/dashboard.php');
      exit;
  }
    header('Location: /');
    exit;
}

// input filename (default index file)
$file = $_GET['file'] ?? 'drug_info.md';

// base directory
$base = __DIR__ . '/../static/pdfs/';
// This allows directory traversal (../../..) and inclusion of arbitrary files.
$path = $base . $file;

// If the file exists INCLUDE it (this will execute PHP code inside the file)
if (file_exists($path)) {
    include $path;
    exit;
} else {
    echo "File not found.";
    exit;
}
