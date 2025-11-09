<?php
// /www/doctor/edit_bio.php
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

$err = null;
$ok = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = $_POST['bio'] ?? '';
    $stmt = $pdo->prepare('UPDATE users SET bio = ? WHERE id = ?');
    $stmt->execute([$bio, $payload['sub']]);
    $ok = "Bio updated.";
}

// load current bio
$stmt = $pdo->prepare('SELECT bio FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$payload['sub']]);
$current = $stmt->fetchColumn();
?>
<!doctype html><html><head><meta charset="utf-8"><title>Edit doctor bio</title>
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
          <a class="btn btn-muted" href="/doctor/dashboard.php">Cancel</a>
        </div>
      </form>
    </section>
  </main>
</body></html>
