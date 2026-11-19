<?php
require __DIR__ . '/../../../../src/bootstrap.php';
$user = require_user();
$token = filter_input(INPUT_GET, 'token', FILTER_UNSAFE_RAW);
// The token is a 32-byte value encoded as exactly 64 lowercase hex characters.
// It is only an identifier; authorization below is based on owner_id, not secrecy.
$file = is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token)
    ? get_file_by_token($token)
    : null;
if (!$file || $file['scenario'] !== 'idor' || ($file['variant'] ?? 'v1') !== 'patched'
    || (int)$file['owner_id'] !== (int)$user['id'] || !is_file($file['disk_path'])) {
    http_response_code(404); exit('File not found');
}
$name = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename((string)$file['original_name']));
// Private access is confirmed before neutral attachment delivery.
header('Content-Type: application/octet-stream');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . filesize($file['disk_path']));
readfile($file['disk_path']);
exit;
