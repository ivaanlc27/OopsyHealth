<?php
// /www/patient/report.php
session_start();
require_once __DIR__ . '/../mail/db.php'; // $pdo

// require login
if (empty($_SESSION['user'])) {
    header('Location: /');
    exit;
}

// fetch id from GET (no owner check == IDOR)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(404);
    echo "Report not found.";
    exit;
}

$stmt = $pdo->prepare('SELECT r.id, r.title, r.content, r.owner_id, r.created_at, u.username AS owner_username, u.email AS owner_email
                       FROM reports r
                       LEFT JOIN users u ON u.id = r.owner_id
                       WHERE r.id = ? LIMIT 1');
$stmt->execute([$id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    http_response_code(404);
    echo "Report not found.";
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Report #<?=htmlspecialchars($report['id'])?> — OopsyHealth</title>
  <link rel="stylesheet" href="/static/css/patient.css">
</head>
<body>
  <header class="ph-header">
    <div class="brand">
      <img src="/static/images/logo.png" alt="OopsyHealth Logo" class="logo-small">
      <h1>OopsyHealth</h1>
    </div>
    <div class="hdr-right">
      <div><a href="/patient/dashboard.php" class="btn-link">Back</a></div>
    </div>
  </header>

  <main class="container report-view" style="max-width:900px;margin:28px auto;">
    <article class="card">
      <h2><?=htmlspecialchars($report['title'])?></h2>
      <div class="meta">Report ID: <strong><?=htmlspecialchars($report['id'])?></strong> — Owner: <strong><?=htmlspecialchars($report['owner_username'] ?? 'user_'.$report['owner_id'])?></strong> (<?=htmlspecialchars($report['owner_email'] ?? '')?>) — Created: <?=htmlspecialchars($report['created_at'])?></div>
      <hr>
      <pre style="white-space:pre-wrap;"><?=htmlspecialchars($report['content'])?></pre>
    </article>
  </main>
</body>
</html>
