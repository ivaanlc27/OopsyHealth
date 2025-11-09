<?php
// /www/doctor/inbox_chat.php
session_start();
require_once __DIR__ . '/../mail/db.php';
require_once __DIR__ . '/../includes/jwt_utils.php';

$token = $_COOKIE['auth_token'] ?? null;
$secret = get_jwt_secret_from_db($pdo);
$payload = $token ? jwt_decode_and_verify($token, $secret) : null;
if (!$payload || ($payload['role'] ?? '') !== 'doctor') {
    header('Location: /');
    exit;
}

$ph = $pdo->query("SELECT id, username FROM users WHERE role='pharmacist' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$messages = $pdo->prepare('SELECT c.*, u.username FROM chats c JOIN users u ON u.id = c.from_user WHERE c.to_user = ? ORDER BY c.created_at DESC');
$messages->execute([$payload['sub']]);
$rows = $messages->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html><head><meta charset="utf-8"><title>Doctor inbox</title></head><body>
  <h2>Messages</h2>
  <?php foreach($rows as $m): ?>
    <div style="border:1px solid #ddd;padding:8px;margin:8px 0;">
      <strong><?=htmlspecialchars($m['username'])?></strong> — <?=htmlspecialchars($m['created_at'])?><br>
      <!-- stored XSS triggers here in doctor's browser -->
      <div class="msg-body"><?= $m['message'] ?></div>
    </div>
  <?php endforeach; ?>
  <p>Your bio: <?php
      $bio = $pdo->prepare('SELECT bio FROM users WHERE id=? LIMIT 1');
      $bio->execute([$payload['sub']]);
      echo htmlspecialchars($bio->fetchColumn() ?? '');
  ?></p>
</body></html>
