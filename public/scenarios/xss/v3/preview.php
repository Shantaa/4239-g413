<?php
require __DIR__ . '/../../../../src/bootstrap.php';
$user = require_user();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$file = $id ? get_file($id) : null;
if (!$file || $file['scenario'] !== 'xss' || ($file['variant'] ?? 'v1') !== 'v3'
    || (int)$file['owner_id'] !== (int)$user['id'] || !is_file($file['disk_path'])) {
    http_response_code(404); exit('No such preview for this account.');
}
header('Content-Type: image/svg+xml');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; script-src 'none'; style-src 'unsafe-inline'; img-src data:;");
header('Content-Length: ' . filesize($file['disk_path']));
readfile($file['disk_path']);
exit;
