<?php

session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['mail_user'])) {
    header('Location: /mail/login.php');
    exit;
}

$user = $_SESSION['mail_user'];

// Fetch emails sent to this user's email
$stmt = $pdo->prepare('SELECT id, subject, body, created_at FROM emails WHERE to_email = ? ORDER BY created_at DESC');
$stmt->execute([$user['email']]);
$emails = $stmt->fetchAll();

?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Inbox — <?=htmlspecialchars($user['email'])?></title>
<link rel="stylesheet" href="/static/css/email.css">
</head>
<body>
  <div class="page-wrap" style="max-width:900px;margin:28px auto;">
    <header style="display:flex;justify-content:space-between;align-items:center;">
      <h2>Inbox — <?=htmlspecialchars($user['username'])?></h2>
      <div>
        <form method="post" action="/mail/logout.php" style="display:inline;">
          <button class="btn-secondary">Logout</button>
        </form>
      </div>
    </header>

    <?php if (empty($emails)): ?>
      <p>No messages yet.</p>
    <?php else: ?>
      <table style="width:100%;border-collapse:collapse;margin-top:12px;">
        <thead>
          <tr style="text-align:left;border-bottom:1px solid #e1e6e6;">
            <th style="padding:8px;">Received</th>
            <th style="padding:8px;">Subject</th>
            <th style="padding:8px;"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($emails as $e): ?>
            <tr>
              <td style="padding:10px;vertical-align:top;white-space:nowrap;"><?=htmlspecialchars($e['created_at'])?></td>
              <td style="padding:10px;vertical-align:top;"><strong><?=htmlspecialchars($e['subject'])?></strong><div style="color:#4b6b78;margin-top:6px;white-space:pre-wrap;"><?=htmlspecialchars($e['body'])?></div></td>
              <td style="padding:10px;vertical-align:top;text-align:right;">
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</body>
</html>
