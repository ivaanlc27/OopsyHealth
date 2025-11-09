<?php

session_start();
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../mail/db.php';

$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $notice = "Please enter a valid email address.";
    } else {
        session_regenerate_id(true);

        // Store the requested email in session encrypted (prevents tampering)
        try {
            $_SESSION['reset_email_enc'] = encrypt_session_value($email);
        } catch (Exception $e) {
            $notice = "Server error: session encryption not configured.";
        }

        // Important: CLEAR any previous token state so confirm.php will require a fresh token
        unset($_SESSION['reset_token']);
        unset($_SESSION['reset_token_id']);
        unset($_SESSION['reset_prefill_otp']);
        unset($_SESSION['last_token_created']);

        // Realistic behavior: only create token if user exists
        $stmt = $pdo->prepare('SELECT id, username, phone FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(12)); // 24 hex chars
            $expires_at = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

            // token NOT bound to email
            $ins = $pdo->prepare('INSERT INTO password_resets (token, otp, expires_at, created_at) VALUES (?, NULL, ?, NOW())');
            $ins->execute([$token, $expires_at]);

            // Insert simulated email so user finds token in simulated inbox
            $body = "Hello {$user['username']},\n\nA password reset was requested. Use this token to continue:\n\n{$token}\n\n(This token expires in 1 hour.)\n\n- OopsyHealth lab";
            $mail = $pdo->prepare('INSERT INTO emails (to_email, subject, body) VALUES (?, ?, ?)');
            $mail->execute([$email, 'Password reset token', $body]);

            // Optionally store last token created for instructor debug
            $_SESSION['last_token_created'] = $token;
        } else {
            // still behave generically
            $_SESSION['last_token_created'] = null;
        }

        // Redirect to confirm.php so the user is prompted to enter the token
        header('Location: /reset/confirm.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Request password reset</title>
<link rel="stylesheet" href="/static/css/email.css">
<meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
  <div class="page-wrap login-card" style="max-width:520px;">
    <h2>Request password reset</h2>
    <?php if (!empty($notice)): ?><div class="notice"><?=htmlspecialchars($notice)?></div><?php endif; ?>

    <form method="post" autocomplete="off">
      <label>Email
        <input name="email" type="email" required placeholder="your.email@oopsyhealth.com">
      </label>
      <div style="display:flex;gap:8px;">
        <button class="btn-primary" type="submit">Request token</button>
        <a class="btn-secondary" href="/">Back</a>
      </div>
    </form>
  </div>
</body>
</html>
