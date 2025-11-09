<?php

session_start();
require_once __DIR__ . '/db.php';

$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // lookup user
    $stmt = $pdo->prepare('SELECT id, username, email, password_hash FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // logged into the simulated mailbox as that user
        $_SESSION['mail_user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ];
        header('Location: /mail/inbox.php');
        exit;
    } else {
        $err = "Invalid credentials for mail access.";
    }
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Inbox — Login</title>
<link rel="stylesheet" href="/static/css/email.css">
</head>
<body>
  <div class="page-wrap login-card">
    <h2>Inbox — Login</h2>
    <?php if ($err): ?><div class="notice"><?=htmlspecialchars($err)?></div><?php endif; ?>

    <form method="post">
        <label>Email
            <input name="email" type="email" required placeholder="user.surname@oopsyhealth.com">
        </label>
        <label>Password
            <input name="password" type="password" required>
        </label>
        <div style="display:flex;gap:8px;">
            <button class="btn-primary" type="submit">Open Inbox</button>
            <a class="btn-secondary" href="/">&larr; Back</a>
        </div>
    </form>
</div>
</body>
</html>
