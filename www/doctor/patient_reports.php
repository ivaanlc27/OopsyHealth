<?php
session_start();
require_once __DIR__ . '/../mail/db.php';
require_once __DIR__ . '/../includes/jwt_utils.php';

// Auth: doctor only
$token = $_COOKIE['auth_token'] ?? null;
$secret = get_jwt_secret_from_db($pdo);
$payload = $token ? jwt_decode_and_verify($token, $secret) : null;
if (!$payload || ($payload['role'] ?? '') !== 'doctor') {
    header('Location: /');
    exit;
}

// Get patient ID from query
$owner_id = isset($_GET['owner']) ? (int)$_GET['owner'] : 0;

// Fetch patient info
$stmt = $pdo->prepare('SELECT username, email FROM users WHERE id=? AND role="patient" LIMIT 1');
$stmt->execute([$owner_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die('Patient not found.');
}

// Fetch patient reports
$reports = $pdo->prepare('SELECT title, content, created_at FROM reports WHERE owner_id=? ORDER BY created_at DESC');
$reports->execute([$owner_id]);
$reports = $reports->fetchAll(PDO::FETCH_ASSOC);

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Patient Reports — <?= esc($patient['username']) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="/static/css/pharmacist.css">
<style>
.container { max-width:900px; margin:20px auto; }
.card { padding:16px; border:1px solid #ddd; border-radius:8px; background:#fff; margin-bottom:12px; }
</style>
</head>
<body>
<div class="container">
  <h2>Reports for <?= esc($patient['username']) ?> (<?= esc($patient['email']) ?>)</h2>

  <?php if (empty($reports)): ?>
    <p>No reports found for this patient.</p>
  <?php else: ?>
    <?php foreach($reports as $r): ?>
      <div class="card">
        <div><strong><?= esc($r['title']) ?></strong> — <em><?= esc($r['created_at']) ?></em></div>
        <pre style="margin-top:6px; white-space:pre-wrap;"><?= esc($r['content']) ?></pre>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <p><a class="btn-link" href="/doctor/dashboard.php">Back to Dashboard</a></p>
</div>
</body>
</html>
