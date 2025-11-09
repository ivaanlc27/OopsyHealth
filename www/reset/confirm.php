<?php
// confirm.php
// Single page to validate a token (generate OTP) and then accept OTP + new password.
// Shows OTP icon + OTP value when the target email is Alice (lab convenience).
//
// WARNING: intentionally insecure lab behavior. Do NOT use in production.

session_start();
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../mail/db.php'; // $pdo

// lab "Alice" constant — change if your Alice email is different
$ALICE_EMAIL = 'alice.smith@oopsyhealth.com';

function safe_post(string $k) {
    return isset($_POST[$k]) ? trim($_POST[$k]) : null;
}

$err = null;
$ok = null;
$otp_generated = null;

// Decrypt target email from session (encrypted by request.php)
$enc_email = $_SESSION['reset_email_enc'] ?? null;
$target_email = $enc_email ? decrypt_session_value($enc_email) : null;
if ($enc_email && $target_email === null) {
    $err = "Session corrupted or server misconfigured. Start the reset flow again.";
}

// Step A: token submission (generate/obtain OTP, save token id in session)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token_input']) && !isset($_POST['otp_input'])) {
    // Clear previous token state so new token_input always forces new OTP step
    unset($_SESSION['reset_token']);
    unset($_SESSION['reset_token_id']);
    unset($_SESSION['reset_prefill_otp']);
    unset($_SESSION['reset_phone_last2']);
    unset($_SESSION['reset_show_otp']);

    if (!empty($err)) {
        // nothing
    } else {
        $token = trim($_POST['token_input']);
        if ($token === '') {
            $err = "Please enter your reset token.";
        } else {
            $stmt = $pdo->prepare('SELECT id, token, otp, expires_at FROM password_resets WHERE token = ? LIMIT 1');
            $stmt->execute([$token]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $err = "Token not found.";
            } else {
                try {
                    $now = new DateTime();
                    $expires = new DateTime($row['expires_at'] ?? 'now');
                } catch (Exception $e) {
                    $err = "Invalid token expiry format.";
                    $expires = null;
                }

                if ($expires && $expires < $now) {
                    $err = "Token expired.";
                } else {
                    // generate a NEW OTP every time Generate OTP is pressed
                    $otp_generated = str_pad((string)random_int(0, 999), 3, '0', STR_PAD_LEFT);
                    $upd = $pdo->prepare('UPDATE password_resets SET otp = ? WHERE id = ?');
                    $upd->execute([$otp_generated, $row['id']]);

                    // store token and token id in session for subsequent OTP verification step
                    $_SESSION['reset_token'] = $token;
                    $_SESSION['reset_token_id'] = $row['id'];
                    $_SESSION['reset_prefill_otp'] = $otp_generated;

                    // fetch phone from target user for notification (store last 2 digits)
                    if ($target_email) {
                        $u = $pdo->prepare('SELECT phone FROM users WHERE email = ? LIMIT 1');
                        $u->execute([$target_email]);
                        $user = $u->fetch(PDO::FETCH_ASSOC);
                        if ($user && !empty($user['phone'])) {
                            // strip non-digits and take last 2 digits
                            $digits = preg_replace('/\D+/', '', $user['phone']);
                            $_SESSION['reset_phone_last2'] = substr($digits, -2);
                        }
                    }

                    // If the target account is Alice, allow showing the OTP icon + value (lab convenience)
                    if (strcasecmp($target_email, $ALICE_EMAIL) === 0) {
                        $_SESSION['reset_show_otp'] = true;
                    } else {
                        unset($_SESSION['reset_show_otp']);
                    }
                }
            }
        }
    }
}

// Step B: OTP + new password submission -> validate and perform reset, then delete token row
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp_input'], $_POST['new_password'])) {
    if (!empty($err)) {
        // nothing
    } else {
        if (empty($_SESSION['reset_token']) || empty($_SESSION['reset_token_id'])) {
            $err = "No token in session. Start the reset flow first.";
        } else {
            $otp_input = trim($_POST['otp_input']);
            $new_password = $_POST['new_password'] ?? '';

            if ($otp_input === '' || $new_password === '') {
                $err = "All fields are required.";
            } else {
                $token_id = $_SESSION['reset_token_id'];

                $stmt = $pdo->prepare('SELECT id, token, otp, expires_at FROM password_resets WHERE id = ? LIMIT 1');
                $stmt->execute([$token_id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$row) {
                    $err = "Token not found (it may have been used or expired).";
                } else {
                    try {
                        $now = new DateTime();
                        $expires = new DateTime($row['expires_at'] ?? 'now');
                    } catch (Exception $e) {
                        $err = "Invalid token expiry format.";
                        $expires = null;
                    }

                    if ($expires && $expires < $now) {
                        $err = "Token expired.";
                    } elseif ($row['otp'] !== $otp_input) {
                        $err = "Invalid OTP.";
                    } elseif (empty($target_email)) {
                        $err = "Target email not resolved in session. Start flow again.";
                    } else {
                        $u = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                        $u->execute([$target_email]);
                        $user = $u->fetch(PDO::FETCH_ASSOC);

                        if (!$user) {
                            $err = "Target account not found for resolved email: " . htmlspecialchars($target_email);
                        } else {
                            // update password (bcrypt)
                            $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
                            $up = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                            $up->execute([$new_hash, $user['id']]);

                            // notify target in simulated inbox
                            $stmtMail = $pdo->prepare('INSERT INTO emails (to_email, subject, body) VALUES (?, ?, ?)');
                            $body = "Hello,\n\nYour account password was successfully changed via the password reset flow.\n\n- OopsyHealth lab";
                            $stmtMail->execute([$target_email, 'Your password was changed', $body]);

                            // delete the token row (remove token + otp)
                            $del = $pdo->prepare('DELETE FROM password_resets WHERE id = ?');
                            $del->execute([$row['id']]);

                            $ok = "Password updated for " . htmlspecialchars($target_email) . ". You can now log in with the new password.";

                            // cleanup session keys for this flow (keep reset_email_enc so the session still "remembers" the requested email)
                            unset($_SESSION['reset_token']);
                            unset($_SESSION['reset_token_id']);
                            unset($_SESSION['reset_prefill_otp']);
                            unset($_SESSION['reset_phone_last2']);
                            unset($_SESSION['reset_show_otp']);
                        }
                    }
                }
            }
        }
    }
}

// helper values for rendering
$token_in_session = !empty($_SESSION['reset_token']);
$otp_prefill = htmlspecialchars($_SESSION['reset_prefill_otp'] ?? '');
$phone_last2 = htmlspecialchars($_SESSION['reset_phone_last2'] ?? '');
$show_otp_for_alice = !empty($_SESSION['reset_show_otp']) && strcasecmp($target_email, $ALICE_EMAIL) === 0;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Reset password — OopsyHealth</title>
  <link rel="stylesheet" href="/static/css/email.css">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    /* small inline tweaks to position the otp icon nicely */
    .otp-banner {
      display:flex;
      align-items:center;
      gap:12px;
      margin:14px 0;
      padding:10px;
      background:#f7fbfb;
      border:1px solid #e0f2f1;
      border-radius:8px;
    }
    .otp-banner img { width:48px; height:48px; }
    .otp-value { font-weight:700; font-size:1.3rem; color:#1b5e20; }
  </style>
</head>
<body>
  <div class="page-wrap login-card" style="max-width:640px;">
    <h2>Reset password</h2>

    <?php if ($err): ?>
      <div class="notice"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <?php if ($ok): ?>
      <div style="background:#dff0d8;color:#236b2b;padding:10px;border-radius:6px;margin-bottom:12px;">
        <?= htmlspecialchars($ok) ?>
      </div>
      <a class="btn-primary" href="/">Back to login</a>
    <?php else: ?>

      <?php if (!$token_in_session): ?>
        <form method="post" autocomplete="off">
          <label>Reset token
            <input name="token_input" type="text" required placeholder="Paste your reset token here">
          </label>
          <div style="display:flex;gap:8px;margin-top:10px;">
            <button class="btn-primary" type="submit">Generate OTP</button>
            <a class="btn-secondary" href="/">Back</a>
          </div>
        </form>

      <?php else: ?>
        <p style="margin-bottom:12px;">
          An OTP has been sent to the phone associated with the account requested earlier.
          <?php if ($phone_last2): ?>
              The phone ends with <strong><?= $phone_last2 ?></strong>.
          <?php endif; ?>
          The target account to be changed is
          <strong><?= htmlspecialchars($target_email ?? '[unknown]') ?></strong>.
        </p>

        <?php if ($show_otp_for_alice): ?>
          <!-- Lab convenience: show OTP widget for Alice -->
          <div class="otp-banner" role="status" aria-live="polite">
            <img src="/static/images/otp.png" alt="OTP icon">
            <div>
              <div style="font-size:0.95rem;color:#2c3e50;">Simulated SMS to Alice's phone:</div>
              <div class="otp-value"><?= $otp_prefill ?: '---' ?></div>
              <div style="font-size:0.85rem;color:#556f6b;margin-top:4px;">(Shown because in this lab you are Alice. Therefore, you have access to Alice's phone)</div>
            </div>
          </div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
          <label>One-time code
            <input name="otp_input" type="text" pattern="\d{3}" maxlength="3" required placeholder="DDD" value="">
          </label>

          <label style="margin-top:8px;">New password
            <input name="new_password" type="password" required placeholder="Choose new password">
          </label>

          <div style="display:flex;gap:8px;margin-top:10px;">
            <button class="btn-primary" type="submit">Set new password</button>
            <a class="btn-secondary" href="/">Back</a>
          </div>
        </form>
      <?php endif; ?>

    <?php endif; ?>
  </div>
</body>
</html>
