<?php
// /www/doctor/dashboard.php
session_start();
require_once __DIR__ . '/../mail/db.php';
require_once __DIR__ . '/../includes/jwt_utils.php';

// Auth: decode JWT from cookie and verify role = doctor
$token = $_COOKIE['auth_token'] ?? null;
$secret = get_jwt_secret_from_db($pdo);
$payload = $token ? jwt_decode_and_verify($token, $secret) : null;

if (!$payload || ($payload['role'] ?? '') !== 'doctor') {
    header('Location: /');
    exit;
}

$doctor_id = $payload['sub'];

// Load doctor's info
$stmt = $pdo->prepare('SELECT username, bio, email, phone FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

// List patients
$patients = $pdo->query("SELECT id, username, email FROM users WHERE role = 'patient' ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

// Helper to render safe fields
function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Doctor dashboard — OopsyHealth</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/static/css/pharmacist.css">
  <style>
    .dashboard .panel { margin-bottom: 20px; }
    .card { padding: 16px; border: 1px solid #ddd; border-radius: 8px; background:#fff; }
    .btn { display:inline-block; padding:6px 12px; background:#2c7; color:#fff; text-decoration:none; border-radius:4px; }
    .btn-link { margin-left:8px; text-decoration:none; color:#2c7; font-weight:600; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:8px; border-bottom:1px solid #eee; text-align:left; }
    th { background:#f9f9f9; }
    .chat-card { border:1px solid #ddd; border-radius:6px; padding:12px; max-width:700px; margin-top:8px; }
    .chat-msg { padding:8px; margin-bottom:6px; border-radius:4px; background:#f2f2f2; }
    .chat-meta { font-size:0.9em; color:#555; margin-bottom:4px; }
  </style>
</head>
<body>
  <header class="ph-header" style="display:flex;justify-content:space-between;align-items:center;padding:12px 24px;background:#eef;">
    <div class="brand">
      <img src="/static/images/logo.png" alt="OopsyHealth Logo" style="height:40px;margin-right:12px;vertical-align:middle;">
      <span style="font-size:1.3em;font-weight:600;">OopsyHealth — Doctor</span>
    </div>
    <div class="hdr-right">
      <span>Logged in as <strong><?= esc($doctor['username'] ?? '[unknown]') ?></strong></span>
      <a class="btn-link" href="/doctor/edit_bio.php">Edit bio</a>
      <a class="btn-link" href="/doctor/inbox_chat.php">Chat</a>
      <a class="btn-link" href="/auth/logout.php">Logout</a>
    </div>
  </header>

  <main class="dashboard" style="max-width:1000px;margin:20px auto;">
    <section class="panel card">
      <h2>Welcome, Dr. <?= esc($doctor['username'] ?? '') ?></h2>
      <p><strong>Email:</strong> <?= esc($doctor['email'] ?? '') ?> &nbsp; <strong>Phone:</strong> <?= esc($doctor['phone'] ?? '') ?></p>
      <p><strong>Bio:</strong> <?= esc($doctor['bio'] ?? '') ?></p>
    </section>

    <section class="panel card">
      <h3>Patients</h3>
      <?php if (empty($patients)): ?>
        <p>No patients found.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Patient</th>
              <th>Email</th>
              <th>Reports</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($patients as $p): ?>
              <tr>
                <td><?= esc($p['username']) ?></td>
                <td><?= esc($p['email']) ?></td>
                <td>
                  <a class="btn" href="/doctor/patient_reports.php?owner=<?= urlencode($p['id']) ?>">View reports</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
