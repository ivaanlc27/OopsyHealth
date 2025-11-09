<?php

session_start();
require_once __DIR__ . '/../mail/db.php';
require_once __DIR__ . '/../includes/jwt_utils.php';

$token = $_COOKIE['auth_token'] ?? null;
$secret = get_jwt_secret_from_db($pdo);
$payload = $token ? jwt_decode_and_verify($token, $secret) : null;

// Verify username exists and role matches in DB
$query = $pdo->prepare('SELECT role FROM users WHERE username = ? LIMIT 1');
$query->execute([$payload['username'] ?? '']);
$db_role = $query->fetchColumn();
if (!$db_role  || $db_role !== ($payload['role'] ?? '')) {
    header('Location: /');
    exit;
}

if (!$payload || ($payload['role'] ?? '') !== 'doctor') {
    header('Location: /');
    exit;
}

$query = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$query->execute([$payload['username'] ?? '']);
$doctor_id = $query->fetchColumn();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /doctor/inbox_chat.php');
    exit;
}

// Get message text
$message = trim($_POST['message'] ?? '');
if ($message === '') {
    // Empty message: redirect back
    header('Location: /doctor/inbox_chat.php');
    exit;
}

// Determine recipient pharmacist(s) - for simplicity, send to all pharmacists (we may fix it like we did for doctor)
$pharmacists = $pdo->query("SELECT id FROM users WHERE role='pharmacist'")->fetchAll(PDO::FETCH_COLUMN);
$stmt = $pdo->prepare("INSERT INTO chats (from_user, to_user, message) VALUES (?, ?, ?)");

foreach ($pharmacists as $ph_id) {
    $stmt->execute([$doctor_id, $ph_id, $message]);
}

header('Location: /doctor/inbox_chat.php');
exit;
