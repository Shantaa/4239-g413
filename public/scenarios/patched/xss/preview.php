<?php
require __DIR__ . '/../../../../src/bootstrap.php';
$user = require_user();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$file = $id ? get_file($id) : null;
// Private previews remain account-bound, and the route never exposes a public
// stored-name URL. This prevents active content from being placed at a guessed
// same-origin application path.
if (!$file || $file['scenario'] !== 'xss' || ($file['variant'] ?? 'v1') !== 'patched'
    || (int)$file['owner_id'] !== (int)$user['id'] || !is_file($file['disk_path'])) {
    http_response_code(404); exit('No such preview for this account.');
}
$ext = strtolower(pathinfo((string)$file['original_name'], PATHINFO_EXTENSION));
$contentType = $ext === 'svg'
    ? 'image/svg+xml'
    : ($ext === 'html' || $ext === 'htm' ? 'text/html' : 'text/plain');
// CSP makes script execution unavailable; sandbox removes same-origin authority
// even if malformed content evades filtering. nosniff prevents MIME confusion.
header('Content-Type: ' . $contentType . '; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; script-src 'none'; object-src 'none'; frame-src 'none'; base-uri 'none'; style-src 'unsafe-inline'; img-src data: blob:; sandbox");
header('X-Frame-Options: DENY');
header('Content-Length: ' . filesize($file['disk_path']));
readfile($file['disk_path']);
exit;
