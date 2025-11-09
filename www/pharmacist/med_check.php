<?php
// /www/pharmacist/med_check.php
session_start();
require_once __DIR__ . '/../mail/db.php';
require_once __DIR__ . '/../includes/jwt_utils.php';

$token = $_COOKIE['auth_token'] ?? null;
$secret = get_jwt_secret_from_db($pdo);
$payload = $token ? jwt_decode_and_verify($token, $secret) : null;
if (!$payload || ($payload['role'] ?? '') !== 'pharmacist') {
    header('Location: /');
    exit;
}

$result = null;
$drug_input = $_POST['drug'] ?? null;
$qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
if ($drug_input) {
    // INTENTIONALLY VULNERABLE: user input is concatenated into SQL
    $sql = "SELECT CASE WHEN ((SELECT amount FROM inventory WHERE name = '{$drug_input}' LIMIT 1) >= {$qty}) THEN 1 ELSE 0 END AS has_it";
    try {
        $row = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
        $result = ($row['has_it'] == 1) ? 'YES' : 'NO';
    } catch (PDOException $e) {
        $result = "ERROR: " . $e->getMessage();
    }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Medication boolean check</title>
<link rel="stylesheet" href="/static/css/pharmacist.css"></head><body>
  <main style="max-width:900px;margin:20px auto;">
    <section class="card">
      <h2>Medication availability</h2>
      <p class="hint">This endpoint is intentionally vulnerable to blind boolean SQLi for lab exercises.</p>

      <form method="post">
        <label>Drug name (exact): <input name="drug" value="<?=htmlspecialchars($drug_input)?>"></label>
        <label>Quantity: <input name="qty" type="number" value="<?=htmlspecialchars($qty)?>"></label>
        <div style="margin-top:10px;">
          <button class="btn" type="submit">Check</button>
          <a class="btn btn-muted" href="/pharmacist/dashboard.php">Back</a>
        </div>
      </form>

      <?php if ($result !== null): ?>
        <div class="notice" style="margin-top:12px;">
          <strong>Result:</strong> <?=htmlspecialchars($result)?>
        </div>
      <?php endif; ?>
    </section>
  </main>
</body></html>
