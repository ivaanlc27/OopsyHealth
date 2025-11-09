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

$error = null;
$rows = [];
$type_input = trim((string)($_POST['type'] ?? ''));

if ($type_input !== '') {
    try {
        $query = "SELECT name, amount FROM inventory WHERE type = '$type_input'"; // Unsafe direct interpolation
        $stmt = $pdo->query($query);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Database error: " . htmlspecialchars($e->getMessage());
    }
}

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Medication by type — OopsyHealth</title>
  <link rel="stylesheet" href="/static/css/pharmacist.css">
  <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
  <main style="max-width:900px;margin:20px auto;">
    <section class="card">
      <h2>Medication lookup by type</h2>
      <p class="hint">Enter a medication <strong>type</strong> (e.g. <code>antibiotic</code>, <code>analgesic</code>, <code>anti-inflammatory</code>) to list available items (name + amount).</p>

      <?php if ($error): ?>
        <div class="notice" style="margin-bottom:12px;"><?= $error ?></div>
      <?php endif; ?>

      <form method="post" style="margin-bottom:14px;">
        <label>Type: <input name="type" value="<?=htmlspecialchars($type_input)?>" placeholder="e.g. antibiotic"></label>
        <div style="margin-top:10px;">
          <button class="btn" type="submit">Search</button>
          <a class="btn btn-muted" href="/pharmacist/dashboard.php">Back</a>
        </div>
      </form>

      <?php if ($type_input !== ''): ?>
        <h3>Results for type: <?=htmlspecialchars($type_input)?></h3>

        <?php if (empty($rows)): ?>
          <div class="notice">No medicines found for that type.</div>
        <?php else: ?>
          <table style="width:100%;border-collapse:collapse;">
            <thead>
              <tr style="text-align:left;border-bottom:1px solid #e6f2f2;">
                <th style="padding:8px">Name</th>
                <th style="padding:8px">Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td style="padding:8px; vertical-align:top;"><?=htmlspecialchars($r['name'])?></td>
                  <td style="padding:8px; vertical-align:top;"><?=htmlspecialchars((string)$r['amount'])?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
