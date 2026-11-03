<?php
require __DIR__ . '/../../../src/bootstrap.php';
$user = require_user();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$file = $id ? get_file($id) : null;
if (!$file || $file['scenario'] !== 'idor' || !is_file($file['disk_path'])) {
    http_response_code(404); exit('File not found');
}
// CWE-639 / IDOR: intentionally missing check:
// if ((int)$file['owner_id'] !== (int)$user['id']) { http_response_code(403); exit; }
// The stored path is database-generated, so this scenario is NOT a path traversal lab.
$name = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['original_name']));
header('Content-Type: application/octet-stream');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . filesize($file['disk_path']));
readfile($file['disk_path']);
exit;
