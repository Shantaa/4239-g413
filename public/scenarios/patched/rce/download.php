<?php
require __DIR__ . '/../../../../src/bootstrap.php';
$user = require_user();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$file = $id ? get_file($id) : null;
// Owner check and exact variant check prevent another account or a V1/V2/V3
// record from being reused through this hardened RCE delivery route.
if (!$file || $file['scenario'] !== 'rce' || ($file['variant'] ?? 'v1') !== 'patched'
    || (int)$file['owner_id'] !== (int)$user['id'] || !is_file($file['disk_path'])) {
    http_response_code(404); exit('File not found');
}
$name = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename((string)$file['original_name']));
// Active content is always data, never an executable web resource.
header('Content-Type: application/octet-stream');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . filesize($file['disk_path']));
readfile($file['disk_path']);
exit;
