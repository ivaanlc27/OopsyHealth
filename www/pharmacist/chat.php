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

// Find the single doctor associated
$doc = $pdo->query("SELECT id, username, email, bio FROM users WHERE role='doctor' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$doctor_id = $doc['id'] ?? null;
$doctor_username = $doc['username'] ?? '[no doctor found]';
$doctor_bio = $doc['bio'] ?? '';

// Fetch only the last message sent from the doctor TO this pharmacist
$lastMessage = null;
if ($doctor_id) {
    $stmt = $pdo->prepare('SELECT c.*, u.username AS from_username
                           FROM chats c
                           JOIN users u ON u.id = c.from_user
                           WHERE c.from_user = ? AND c.to_user = ?
                           ORDER BY c.created_at DESC
                           LIMIT 1');
    $stmt->execute([(int)$doctor_id, (int)$pharmacist_id]);
    $lastMessage = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Chat with doctor — OopsyHealth</title>
  <link rel="stylesheet" href="/static/css/pharmacist.css">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    .card { background:#fff;padding:18px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.06); }
    .meta { color:#556; font-size:0.9rem; margin-bottom:8px; }
    .content { background:#f8fafd; padding:12px; border-radius:6px; white-space:pre-wrap; }
    textarea { width:100%; min-height:100px; padding:8px; border-radius:6px; border:1px solid #dfe6e9; }
    .btn { background:#16a085; color:#fff; border:none; padding:8px 12px; border-radius:6px; cursor:pointer; }
    .btn-muted { background:#95a5a6; color:#fff; padding:8px 12px; border-radius:6px; text-decoration:none; }
  </style>
</head>
<body>
  <main style="max-width:900px;margin:20px auto;">
    <section class="card">
      <h2>Chat with Dr <?= htmlspecialchars($doctor_username) ?></h2>

      <div style="margin-bottom:12px;">
        <strong>Doctor bio:</strong>
        <div style="margin-top:6px;color:#334;">
          <?= htmlspecialchars($doctor_bio) ?>
        </div>
      </div>

      <form method="post" action="/pharmacist/send_message.php" style="margin-bottom:14px;">
        <label for="message">New message</label>
        <textarea id="message" name="message" placeholder="Type message"></textarea>
        <div style="margin-top:10px;">
          <button class="btn" type="submit">Send</button>
          <a class="btn-muted" href="/pharmacist/dashboard.php" style="margin-left:8px;">Back</a>
        </div>
      </form>

      <hr style="margin:16px 0;">

      <h3>Last message received from Dr <?= htmlspecialchars($doctor_username) ?></h3>

      <?php if ($lastMessage): ?>
        <div class="msg">
          <div class="meta"><strong><?= htmlspecialchars($lastMessage['from_username']) ?></strong> — <?= htmlspecialchars($lastMessage['created_at']) ?></div>
          <div class="content"><?= htmlspecialchars($lastMessage['message']) ?></div>
        </div>
      <?php else: ?>
        <div class="notice">No messages received from the doctor yet.</div>
      <?php endif; ?>

    </section>
  </main>
</body>
</html>
