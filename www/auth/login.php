<?php
// /www/auth/login.php
session_start();
require_once __DIR__ . '/../mail/db.php'; // $pdo

// simple helper
function redirect_back_with_error($msg, $username = '') {
    $_SESSION['error'] = $msg;
    $_SESSION['old_user'] = $username;
    header('Location: /');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    redirect_back_with_error('Please provide username and password.', $username);
}

// lookup by username
$stmt = $pdo->prepare('SELECT id, username, email, role, password_hash FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
    redirect_back_with_error('Invalid username or password.', $username);
}

// Authentication successful: set session user
$_SESSION['user'] = [
    'id' => $user['id'],
    'username' => $user['username'],
    'email' => $user['email'],
    'role' => $user['role']
];

// redirect based on role (patients go to patient dashboard)
if ($user['role'] === 'patient') {
    header('Location: /patient/dashboard.php');
    exit;
}

// for other roles, redirect to home (you can expand later)
header('Location: /');
exit;
