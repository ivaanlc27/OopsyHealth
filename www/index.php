<?php
session_start();

// Example: your auth controller will set $_SESSION['error'] or pass messages via flash
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

// preserve old input (if any)
$old_user = $_SESSION['old_user'] ?? '';
unset($_SESSION['old_user']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>OopsyHealth — Telemedicine Login</title>
  <link rel="stylesheet" href="/static/css/index.css">
</head>
<body>
  <div class="page-wrap">
    <header class="site-header">
      <!-- Put your downloaded logo at ./www/static/images/logo.png -->
      <img class="logo" src="/static/images/logo.png" alt="OopsyHealth Logo">
      <h1>OopsyHealth — Telemedicine</h1>

      <div class="header-links">
        <a class="inbox-link" href="/mail/" target="_blank" rel="noopener">
          <img src="/static/images/email.png" alt="Email icon" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;">
          OopsyHealth EMAIL - Inbox</a>
      </div>

    </header>

    <main class="container">
      <!-- Left info box -->
      <aside class="starter-box" aria-labelledby="starter-title">
        <h3 id="starter-title">Lab Starter Account</h3>

        <p><strong>Context:</strong> You are <em>Alice Smith</em>, a patient at OopsyHealth.
           You booked an appointment which is scheduled six months from now. 
         Tired of waiting, you decide to poke around the site.</p>

        <p><strong>Credentials:</strong></p>
        <ul>
          <li>Username: <code>alice.smith</code></li>
          <li>Password: <code>patientpass</code></li>
        </ul>
      </aside>

      <!-- Center login box -->
      <section class="card" aria-labelledby="login-title">
        <h2 id="login-title">Login</h2>

        <?php if ($error): ?>
          <div class="notice" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form class="form" action="/auth/login.php" method="post" autocomplete="off" novalidate>
          <label for="username">Username
            <input id="username" name="username" type="text" value="<?= htmlspecialchars($old_user) ?>" required autofocus>
          </label>

          <label for="password">Password
            <input id="password" name="password" type="password" required>
          </label>

          <button type="submit" class="btn-primary">Login</button>
        </form>

        <hr class="divider">

        <div class="forgot-block">
          <h3>Forgot password?</h3>
          <p>Request a password reset token.</p>

          <!-- classic 'forgot password' input to trigger reset flow -->
          <form action="/reset/request.php" method="post" class="reset-form" autocomplete="off">
            <label for="reset-email" class="sr-only">Email</label>
            <input id="reset-email" name="email" type="email" placeholder="name.surname@oopsyhealth.com" required>
            <button type="submit" class="btn-secondary">Request reset</button>
          </form>
        </div>
      </section>
    </main>
  </div>
</body>
</html>

