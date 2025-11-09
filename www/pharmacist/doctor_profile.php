<?php
// /www/pharmacist/doctor_profile.php
session_start();
require_once __DIR__ . '/../mail/db.php';
require_once __DIR__ . '/../includes/jwt_utils.php';

$token = $_COOKIE['auth_token'] ?? null;
$secret = get_jwt_secret_from_db($pdo);
$payload = $token ? jwt_decode_and_verify($token, $secret) : null;

// Comprobar en la base de datos si el usuario definido en el token existe y tiene efectivamente el rol afirmado por el token
$query = $pdo->prepare('SELECT role FROM users WHERE username = ? LIMIT 1');
$query->execute([$payload['username'] ?? '']);
$db_role = $query->fetchColumn();
if (!$db_role  || $db_role !== ($payload['role'] ?? '')) {
    header('Location: /');
    exit;
}

if (!$payload || ($payload['role'] ?? '') !== 'pharmacist') {
    
  if ($payload && ($payload['role'] ?? '') === 'doctor') {
      // redirect doctors to their panel
      header('Location: /doctor/dashboard.php');
      exit;
  }
    header('Location: /');
    exit;
}

$doc = $pdo->query("SELECT id, username, email, phone, bio FROM users WHERE role='doctor' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>
<!doctype html><html><head><meta charset="utf-8"><title>Doctor profile</title>
<link rel="stylesheet" href="/static/css/pharmacist.css"></head><body>
  <main style="max-width:900px;margin:20px auto;">
    <section class="card">
      <h2>Doctor profile — <?=htmlspecialchars($doc['username'])?></h2>
      <p><strong>Email:</strong> <?=htmlspecialchars($doc['email'])?></p>
      <p><strong>Phone:</strong> <?=htmlspecialchars($doc['phone'])?></p>
      <hr>
      <h3>Biography</h3>
      <div class="card" style="padding:10px">
        <?= nl2br(htmlspecialchars($doc['bio'])) ?>
      </div>
      <p style="margin-top:12px;"><a class="btn" href="/pharmacist/dashboard.php">Back</a></p>
    </section>
  </main>
</body></html>
