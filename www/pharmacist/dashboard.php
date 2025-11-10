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

$pharmacist_name = $payload['username'];

// find associated doctor (single doctor)
$doc = $pdo->query("SELECT id, username, email, phone, bio FROM users WHERE role = 'doctor' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$doctor_username = $doc['username'] ?? '[no doctor]';
$doctor_bio = $doc['bio'] ?? '';

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Pharmacist panel — OopsyHealth</title>
  <link rel="stylesheet" href="/static/css/pharmacist.css">
</head>
<body>
  <header class="ph-header">
    <div class="brand">
      <img src="/static/images/logo.png" alt="logo" class="logo-small">
      <h1>OopsyHealth — Pharmacist</h1>
    </div>
    <div class="hdr-right">
      <div class="user-info">Logged in as <strong><?=htmlspecialchars($pharmacist_name)?></strong></div>
      <div class="links">
        <a href="/auth/logout.php" class="btn-link">Logout</a>
      </div>
    </div>
  </header>

  <main>
    <div class="two-col">
      <aside class="card panel">
        <h3>Associated doctor</h3>
        <p><strong><?=htmlspecialchars($doctor_username)?></strong></p>
        <p class="hint">Doctor bio</p>
        <div class="card" style="padding:10px; margin-top:8px;">
          <?= nl2br(htmlspecialchars($doctor_bio)) ?>
        </div>

        <hr style="margin:12px 0;">
        <p><a class="btn" href="/pharmacist/doctor_profile.php">View doctor profile</a></p>
        <p style="margin-top:10px;"><a class="btn btn-muted" href="/pharmacist/edit_bio.php">Edit your bio</a></p>
      </aside>

      <section class="card panel">
        <h2>Welcome, <?=htmlspecialchars($pharmacist_name)?></h2>

        <div style="margin-top:12px;">
          <p><a class="btn" href="/pharmacist/med_check.php">Check medication stock</a></p>
          <p style="margin-top:8px;"><a class="btn" href="/pharmacist/med_info.php?file=drug_info.md">View drug info</a></p>
          <p style="margin-top:8px;"><a class="btn" href="/pharmacist/chat.php">Chat with doctor</a></p>
        </div>
      </section>
    </div>
  </main>
</body>
</html>
