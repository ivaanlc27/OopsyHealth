<?php
// /www/pharmacist/send_message.php
session_start();
require_once __DIR__ . '/../mail/db.php';
require_once __DIR__ . '/../includes/jwt_utils.php';

$token = $_COOKIE['auth_token'] ?? null;
$secret = get_jwt_secret_from_db($pdo);
$payload = $token ? jwt_decode_and_verify($token, $secret) : null;
if (!$payload || ($payload['role'] ?? '') !== 'pharmacist') {
    
  if ($payload && ($payload['role'] ?? '') === 'doctor') {
      // redirect doctors to their panel
      header('Location: /doctor/dashboard.php');
      exit;
  }
    header('Location: /');
    exit;
}

$to = $pdo->query("SELECT id FROM users WHERE role='doctor' LIMIT 1")->fetchColumn();
$msg = $_POST['message'] ?? '';
if ($msg !== '') {
    // store raw message (INTENTIONALLY stored XSS)
    $ins = $pdo->prepare('INSERT INTO chats (from_user, to_user, message) VALUES (?, ?, ?)');
    $ins->execute([$payload['sub'], $to, $msg]);
}
header('Location: /pharmacist/chat.php');
exit;
