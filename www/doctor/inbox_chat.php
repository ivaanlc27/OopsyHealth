<?php
session_start();
require_once __DIR__ . '/../mail/db.php';
require_once __DIR__ . '/../includes/jwt_utils.php';

// Auth: doctor only
$token = $_COOKIE['auth_token'] ?? null;
$secret = get_jwt_secret_from_db($pdo);
$payload = $token ? jwt_decode_and_verify($token, $secret) : null;
if (!$payload || ($payload['role'] ?? '') !== 'doctor') {
    header('Location: /');
    exit;
}

$doctor_id = $payload['sub'];

// Fetch chat messages (both sent and received) with pharmacists
$chats = $pdo->prepare('
    SELECT c.*, u.username as from_username, u.role as from_role
    FROM chats c
    JOIN users u ON u.id = c.from_user
    WHERE c.to_user = ? OR c.from_user = ?
    ORDER BY c.created_at ASC
');
$chats->execute([$doctor_id, $doctor_id]);
$messages = $chats->fetchAll(PDO::FETCH_ASSOC);

// Escape helper
function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Doctor Chat</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="/static/css/pharmacist.css">
<style>
body { font-family: sans-serif; background:#f9f9f9; margin:0; padding:0; }
.container { max-width:1200px; margin:20px auto; padding:0 12px; }
.chat-card { background:#fff; border:1px solid #ddd; border-radius:8px; padding:12px; display:flex; flex-direction:column; height:80vh; max-width:1200px; }
.chat-window { 
    flex:1; 
    overflow-y:auto; 
    padding:12px; 
    display:flex; 
    flex-direction: column; /* Mensajes uno debajo de otro */
}

.chat-msg {
    max-width:70%; 
    padding:8px 12px; 
    border-radius:16px; 
    margin:6px 0; 
    /* quitamos display:inline-block */
    word-wrap: break-word;
}

.chat-msg.pharmacist { 
    background:#e0f7fa; 
    align-self:flex-start; /* izquierda */
}

.chat-msg.doctor { 
    background:#c8e6c9; 
    align-self:flex-end; /* derecha */
}

.chat-meta { font-size:0.8em; color:#555; margin-bottom:2px; }
.chat-form { margin-top:12px; display:flex; }
.chat-form textarea { flex:1; padding:8px; border-radius:6px; border:1px solid #ccc; resize:none; }
.chat-form button { padding:8px 16px; margin-left:8px; border:none; border-radius:6px; background:#2c7; color:#fff; cursor:pointer; }
</style>
</head>
<body>
<div class="container">
  <h2>Chat with pharmacists</h2>
  <div class="chat-card">
    <div class="chat-window" id="chatWindow">
      <?php foreach($messages as $m): ?>
        <?php $role_class = $m['from_role'] === 'pharmacist' ? 'pharmacist' : 'doctor'; ?>
        <div class="chat-msg <?= $role_class ?>">
          <div class="chat-meta"><?= esc($m['from_username']) ?> — <?= esc($m['created_at']) ?></div>
          <div class="chat-body"><?= $m['message'] ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <form class="chat-form" method="post" action="/doctor/send_message.php">
      <textarea name="message" rows="2" placeholder="Type your message..."></textarea>
      <button type="submit">Send</button>
    </form>
  </div>
  <p style="margin-top:12px;"><a class="btn-link" href="/doctor/dashboard.php">Back to Dashboard</a></p>
</div>

<script>
// Scroll to bottom
const chatWin = document.getElementById('chatWindow');
chatWin.scrollTop = chatWin.scrollHeight;
</script>
</body>
</html>
