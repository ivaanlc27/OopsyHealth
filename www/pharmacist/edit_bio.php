<?php
// /www/pharmacist/edit_bio.php
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

// Obtener id a partir del username
$query = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$query->execute([$payload['username'] ?? '']);
$pharmacist_id = $query->fetchColumn();

$err = null;
$ok = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = $_POST['bio'] ?? '';
    $stmt = $pdo->prepare('UPDATE users SET bio = ? WHERE id = ?');
    $stmt->execute([$bio, $pharmacist_id]);
    $ok = "Bio updated.";
}

// load current bio
$stmt = $pdo->prepare('SELECT bio FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$pharmacist_id]);
$current = $stmt->fetchColumn();
?>
<!doctype html><html><head><meta charset="utf-8"><title>Edit bio</title>
<link rel="stylesheet" href="/static/css/pharmacist.css"></head><body>
  <main style="max-width:900px;margin:20px auto;">
    <section class="card">
      <h2>Edit your biography</h2>
      <?php if ($err): ?><div class="notice"><?=htmlspecialchars($err)?></div><?php endif; ?>
      <?php if ($ok): ?><div class="notice"><?=htmlspecialchars($ok)?></div><?php endif; ?>

      <form method="post">
        <label>Biography</label>
        <textarea name="bio"><?=htmlspecialchars($current)?></textarea>
        <div style="margin-top:10px;">
          <button class="btn" type="submit">Save</button>
          <a class="btn btn-muted" href="/pharmacist/dashboard.php">Back</a>
        </div>
      </form>
    </section>
  </main>
</body></html>
