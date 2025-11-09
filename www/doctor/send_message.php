<?php
session_start();
require_once __DIR__ . '/../mail/db.php';
require_once __DIR__ . '/../includes/jwt_utils.php';

// Auth: doctor only
$token = $_COOKIE['auth_token'] ?? null;
$secret = get_jwt_secret_from_db($pdo);
$payload = $token ? jwt_decode_and_verify($token, $secret) : null;

// Comprobar en la base de datos si el usuario definido en el token existe y tiene efectivamente el rol afirmado por el token
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

// Obtener id a partir del username
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

// Determine recipient pharmacist(s) - for simplicity, send to all pharmacists
$pharmacists = $pdo->query("SELECT id FROM users WHERE role='pharmacist'")->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("INSERT INTO chats (from_user, to_user, message) VALUES (?, ?, ?)");

foreach ($pharmacists as $ph_id) {
    $stmt->execute([$doctor_id, $ph_id, $message]);
}

// Redirect back to chat
header('Location: /doctor/inbox_chat.php');
exit;
