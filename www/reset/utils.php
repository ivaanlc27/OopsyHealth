<?php

function get_session_secret(): ?string {
    $env = getenv('SESSION_SECRET');
    if ($env && strlen(trim($env)) >= 16) {
        return trim($env);
    }
    return null;
}

function encrypt_session_value(string $plaintext): string {
    $secret = get_session_secret();
    if (!$secret) {
        throw new RuntimeException('SESSION_SECRET not configured.');
    }
    $key = hash('sha256', $secret, true);
    $iv = random_bytes(12); // 96-bit nonce for GCM
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($ciphertext === false) {
        throw new RuntimeException('Encryption failed.');
    }
    return base64_encode($iv . $tag . $ciphertext);
}

function decrypt_session_value(string $encoded): ?string {
    $secret = get_session_secret();
    if (!$secret) return null;
    $key = hash('sha256', $secret, true);
    $data = base64_decode($encoded, true);
    if ($data === false || strlen($data) < 28) return null; // iv(12)+tag(16) min
    $iv = substr($data, 0, 12);
    $tag = substr($data, 12, 16);
    $ciphertext = substr($data, 28);
    $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return $plaintext === false ? null : $plaintext;
}
