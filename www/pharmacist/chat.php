<?php
// /www/pharmacist/chat.php
session_start();
require_once __DIR__ . '/../mail/db.php';
require_once __DIR__ . '/../includes/jwt_utils.php';

$token = $_COOKIE['auth_token'] ?? null;
$secret = get_jwt_secret_from_db($pdo);
$payload = $token ? jwt_decode_and_verify($token, $secret) : null;
if (!$payload || ($payload['role'] ?? '') !== 'pharmacist') {
    header('Location: /');
    exit;
}

$pharmacist_id = $payload['sub'];
$doc = $pdo->query("SELECT id, username FROM users WHERE role='doctor' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$messages = $pdo->prepare('SELECT c.*, u.username FROM chats c JOIN users u ON u.id = c.from_user WHERE c.to_user = ? ORDER BY c.created_at DESC');
$messages->execute([$doc['id']]);
$rows = $messages->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Chat with doctor</title>
<link rel="stylesheet" href="/static/css/pharmacist.css">
</head><body>
  <main style="max-width:900px;margin:20px auto;">
    <section class="card">
      <h2>Chat with Dr <?=htmlspecialchars($doc['username'])?></h2>

      <form method="post" action="/pharmacist/send_message.php">
        <label>New message</label>
        <textarea name="message" placeholder="Type message"></textarea>
        <div style="margin-top:10px;">
          <button class="btn" type="submit">Send</button>
          <a class="btn btn-muted" href="/pharmacist/dashboard.php">Back</a>
        </div>
      </form>

      <hr style="margin:16px 0;">

      <h3>Message Historial</h3>
      <?php foreach($rows as $m): ?>
        <div class="msg">
          <div class="meta"><strong><?=htmlspecialchars($m['username'])?></strong> — <?=htmlspecialchars($m['created_at'])?></div>
          <div class="content"><?= $m['message'] /* INTENTIONAL: stored XSS possible */ ?></div>
        </div>
      <?php endforeach; ?>
    </section>
  </main>
</body></html>
