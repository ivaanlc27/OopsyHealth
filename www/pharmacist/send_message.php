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

if (!$payload || ($payload['role'] ?? '') !== 'pharmacist') {
    
  if ($payload && ($payload['role'] ?? '') === 'doctor') {
      header('Location: /doctor/dashboard.php');
      exit;
  }
    header('Location: /');
    exit;
}

$query = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$query->execute([$payload['username'] ?? '']);
$pharmacist_id = $query->fetchColumn();

$to = $pdo->query("SELECT id FROM users WHERE role='doctor' LIMIT 1")->fetchColumn();
$msg = $_POST['message'] ?? '';
if ($msg !== '') {
    // store raw message without sanitization (stored XSS)
    $ins = $pdo->prepare('INSERT INTO chats (from_user, to_user, message) VALUES (?, ?, ?)');
    $ins->execute([$pharmacist_id, $to, $msg]);
}
header('Location: /pharmacist/chat.php');
exit;
