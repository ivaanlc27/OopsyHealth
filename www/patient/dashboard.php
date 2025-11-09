<?php
// /www/patient/dashboard.php
session_start();
require_once __DIR__ . '/../mail/db.php'; // $pdo

// Require login and patient role
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header('Location: /');
    exit;
}

$user = $_SESSION['user'];

// find the (single) pharmacist to display (lab simplification)
$phStmt = $pdo->query("SELECT username FROM users WHERE role = 'pharmacist' LIMIT 1");
$ph = $phStmt->fetch(PDO::FETCH_ASSOC);
$pharmacist_username = $ph['username'] ?? 'pharmacist.not.found';

// Fetch reports belonging to this patient (to list on dashboard)
$repStmt = $pdo->prepare('SELECT id, title, created_at FROM reports WHERE owner_id = ? ORDER BY created_at DESC');
$repStmt->execute([$user['id']]);
$reports = $repStmt->fetchAll(PDO::FETCH_ASSOC);

$upload_notice = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload_file'])) {
    $f = $_FILES['upload_file'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $upload_notice = "Upload error code: " . $f['error'];
    } else {
        // server-side blacklist
        $blacklist_regex = '/\.(php|php3|php4|php5|phtml|phar|phpt|pht|phps)(\.|$)/i';

        if (preg_match($blacklist_regex, $f['name'])) {
            $upload_notice = "Upload rejected: disallowed extension.";
        } else {
            $dest_dir = __DIR__ . '/../uploads';
            if (!is_dir($dest_dir)) mkdir($dest_dir, 0777, true);

            // Guardar con nombre original
            $safe_name = basename($f['name']);
            $dest = $dest_dir . '/' . $safe_name;

            // Opcional: evitar sobrescribir
            if (file_exists($dest)) {
                $safe_name = pathinfo($safe_name, PATHINFO_FILENAME) 
                             . '-' . time() 
                             . '.' . pathinfo($safe_name, PATHINFO_EXTENSION);
                $dest = $dest_dir . '/' . $safe_name;
            }

            if (!move_uploaded_file($f['tmp_name'], $dest)) {
                $upload_notice = "Failed to move uploaded file.";
            } else {
                $public_url = '/uploads/' . $safe_name;
                $upload_notice = "File uploaded successfully. Accessible at: " . htmlspecialchars($public_url);
            }
        }
    }
}


?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Patient dashboard — OopsyHealth</title>
  <link rel="stylesheet" href="/static/css/patient.css">
  <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
  <header class="ph-header">
    <div class="brand">
      <img src="/static/images/logo.png" alt="OopsyHealth Logo" class="logo-small">
      <h1>OopsyHealth</h1>
    </div>
    <div class="hdr-right">
      <div class="user-info">Logged in as <strong><?=htmlspecialchars($user['username'])?></strong></div>
      <div class="links">
        <a href="/auth/logout.php" class="btn-link">Logout</a>
      </div>
    </div>
  </header>

  <main class="dashboard">
    <aside class="panel left">
      <h3>Your assigned pharmacist</h3>
      <p><strong><?=htmlspecialchars($pharmacist_username)?></strong></p>
      <hr>
      <h4>Upload a medical file</h4>

      <?php if ($upload_notice): ?>
        <div class="notice"><?= $upload_notice ?></div>
      <?php endif; ?>

      <form id="uploadForm" method="post" enctype="multipart/form-data" novalidate>
        <label>Select file
          <input id="upload_file" name="upload_file" type="file" accept="image/*,application/pdf,text/plain">
        </label>
        <div class="hint">Allowed: images, PDF, text. Max: 2 MB</div>
        <div style="margin-top:8px;">
          <button class="btn-primary" type="submit">Upload</button>
        </div>
      </form>
    </aside>

    <section class="panel main">
      <h2>Welcome, <?=htmlspecialchars($user['username'])?></h2>

      <div class="card">
        <h3>Your reports</h3>
        <?php if (empty($reports)): ?>
          <p>No reports yet.</p>
        <?php else: ?>
          <ul class="reports-list">
            <?php foreach($reports as $r): ?>
              <li>
                <a href="/patient/report.php?id=<?=urlencode($r['id'])?>"><?=htmlspecialchars($r['title'])?></a>
                <span class="muted"> — <?=htmlspecialchars($r['created_at'])?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

    </section>
  </main>

  <script>
  // client-side upload checks (purely advisory; can be bypassed)
  (function(){
    const form = document.getElementById('uploadForm');
    const fileInput = document.getElementById('upload_file');
    const MAX = 2 * 1024 * 1024; // 2MB
    form.addEventListener('submit', function(e){
      const f = fileInput.files[0];
      if (!f) { e.preventDefault(); alert('Please choose a file'); return; }
      if (f.size > MAX) { e.preventDefault(); alert('File too large (max 2 MB)'); return; }
      const allowed = ['image/', 'application/pdf', 'text/'];
      const ok = allowed.some(prefix => f.type.startsWith(prefix));
      if (!ok) {
        e.preventDefault();
        alert('File type not allowed. Allowed types: images, PDF, text.');
      }
    });
  })();
  </script>
</body>
</html>
