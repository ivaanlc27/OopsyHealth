<?php
// /www/includes/jwt_utils.php
// Simple JWT HMAC-SHA256 helpers (no external libs). Intentionally simple for lab.

function get_jwt_secret_from_db(PDO $pdo) {
    $stmt = $pdo->prepare("SELECT value FROM app_secrets WHERE name = 'jwt_secret' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['value'] ?? null;
}

function jwt_encode(array $payload, string $secret) : string {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $b64 = function($data){ return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); };
    $header_b = $b64(json_encode($header));
    $payload_b = $b64(json_encode($payload));
    $sig = hash_hmac('sha256', "$header_b.$payload_b", $secret, true);
    $sig_b = $b64($sig);
    return "$header_b.$payload_b.$sig_b";
}

function jwt_decode_and_verify(string $token, string $secret) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$header_b, $payload_b, $sig_b] = $parts;
    $b64d = function($v){
        $v = strtr($v, '-_', '+/');
        $pad = 4 - (strlen($v) % 4);
        if ($pad < 4) $v .= str_repeat('=', $pad);
        return base64_decode($v);
    };
    $sig = $b64d($sig_b);
    $expected = hash_hmac('sha256', "$header_b.$payload_b", $secret, true);
    if (!hash_equals($expected, $sig)) return null;
    $payload_json = $b64d($payload_b);
    $payload = json_decode($payload_json, true);
    return $payload;
}
