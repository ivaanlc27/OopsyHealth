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

// after successful login (you already have $user row and $pdo)
if (in_array($user['role'], ['pharmacist','doctor'], true)) {
    require_once __DIR__ . '/../includes/jwt_utils.php';
    $secret = get_jwt_secret_from_db($pdo);
    $payload = [
      'sub' => $user['id'],
      'username' => $user['username'],
      'role' => $user['role'],
      'iat' => time(),
      'exp' => time() + 60*60*4
    ];
    $token = jwt_encode($payload, $secret);
    setcookie('auth_token', $token, 0, '/', '', false, false); // httpOnly false

    if ($user['role'] === 'pharmacist') {
        header('Location: /pharmacist/dashboard.php');
        exit;
    } elseif ($user['role'] === 'doctor') {
        header('Location: /doctor/dashboard.php');
        exit;
    }
}


// for other roles, redirect to home (you can expand later)
header('Location: /');
exit;