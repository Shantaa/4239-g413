<?php
require __DIR__ . '/../../../../src/bootstrap.php';
require_user();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$file = $id ? get_file($id) : null;
if (!$file || $file['scenario'] !== 'xss' || ($file['variant'] ?? 'v1') !== 'v2'
    || !is_file($file['disk_path'])) {
    http_response_code(404); exit('File not found');
}
$name = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename((string)$file['original_name']));
header('Content-Type: application/octet-stream');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . filesize($file['disk_path']));
readfile($file['disk_path']);
exit;
