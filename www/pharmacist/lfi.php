<?php
// /www/pharmacist/lfi.php
// INTENTIONALLY VULNERABLE FOR LAB: this file includes the requested path directly,
// which will cause PHP to execute any PHP code inside the included file. Do NOT use
// this pattern in production — it's a classical LFI/RCE vector.

session_start();
require_once __DIR__ . '/../mail/db.php';
require_once __DIR__ . '/../includes/jwt_utils.php';

$token = $_COOKIE['auth_token'] ?? null;
$secret = get_jwt_secret_from_db($pdo);
$payload = $token ? jwt_decode_and_verify($token, $secret) : null;
if (!$payload || ($payload['role'] ?? '') !== 'pharmacist') {
    
  if ($payload && ($payload['role'] ?? '') === 'doctor') {
      // redirect doctors to their panel
      header('Location: /doctor/dashboard.php');
      exit;
  }
    header('Location: /');
    exit;
}

// input filename (default index file)
$file = $_GET['file'] ?? 'drug_info.md';

// base directory for convenience (but note: we intentionally allow traversal in this lab)
$base = __DIR__ . '/../static/pdfs/';

// NOTE: intentionally vulnerable: we concatenate and include without sanitization.
// This allows directory traversal (../../..) and inclusion of arbitrary files.
$path = $base . $file;

// If the file exists include it (this will execute PHP code inside the file)
if (file_exists($path)) {
    // We DON'T set a content-type for included PHP because included code may emit hea3ders and content.
    // But for non-PHP files the include will simply output their contents.
    include $path;
    exit;
} else {
    // Try the literal path if user passed an absolute or traversing path that didn't resolve from $base
    $alternate = $file;
    if (file_exists($alternate)) {
        include $alternate;
        exit;
    }

    echo "File not found. Tried: " . htmlspecialchars($path) . " and " . htmlspecialchars($alternate);
}
