<?php
// /www/doctor/review_chat.php
// Intentionally renders raw messages for lab automation (DO NOT use in production)

session_start();
require_once __DIR__ . '/../mail/db.php';    // $pdo
require_once __DIR__ . '/../includes/jwt_utils.php'; // get_jwt_secret_from_db, jwt_encode

// doctor id in your fixture (adjust if different)
$doctor_id = 4;

// Build JWT for the doctor and set as cookie so the headless browser has auth
$secret = get_jwt_secret_from_db($pdo);
$payload = [
    'username' => 'david.bennett',
    'role' => 'doctor',
    'iat' => time(),
    'exp' => time() + 60*60 // 1 hour
];
// jwt_encode should exist in your jwt_utils.php (used earlier)
$token = jwt_encode($payload, $secret);

// set cookie (path=/ so it covers the app)
setcookie('auth_token', $token, 0, '/', '', false, false);

// Fetch messages for doctor
$stmt = $pdo->prepare('SELECT c.id, c.message, c.created_at, u.username FROM chats c JOIN users u ON u.id = c.from_user WHERE c.to_user = ? ORDER BY c.created_at ASC');
$stmt->execute([$doctor_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Render a minimal page that outputs raw messages (so any <script> executes)
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Doctor review chat (bot)</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body { font-family: sans-serif; padding:12px; background:#fff; color:#111; }
    .msg { border:1px solid #ddd; padding:8px; margin:8px 0; border-radius:6px; }
    .meta { font-size:0.85em; color:#666; margin-bottom:6px; }
  </style>
</head>
<body>
  <h2>Automated review for doctor (id <?= htmlspecialchars($doctor_id) ?>)</h2>
  <p>This page is intended for an internal headless browser to load and execute any message payloads.</p>

  <?php foreach ($rows as $m): ?>
    <div class="msg">
      <div class="meta"><?= htmlspecialchars($m['username']) ?> — <?= htmlspecialchars($m['created_at']) ?></div>
      <!-- INTENTIONAL: message output RAW so stored XSS executes in the doctor's browser -->
      <div class="body"><?= $m['message'] ?></div>
    </div>
  <?php endforeach; ?>

  <p>Done.</p>
</body>
</html>
