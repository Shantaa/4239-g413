<?php
require __DIR__ . '/../../src/bootstrap.php';
$user = require_user();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('POST only'); }
if (!isset($_FILES['userfile']) || !is_array($_FILES['userfile']) || $_FILES['userfile']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400); exit('Upload failed or was rejected by PHP upload settings.');
}
$f = $_FILES['userfile'];
// Only a lab-stability bound. Deliberately NO extension, MIME or file content validation.
if ($f['size'] > 1024*1024) { http_response_code(413); exit('Lab limit: 1 MB'); }
$name = basename(str_replace('\\', '/', (string)$f['name']));
$name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
if ($name === '' || $name === '.' || $name === '..') { http_response_code(400); exit('Invalid filename'); }
// Unique prefix prevents accidental overwrites; the extension is intentionally retained.
$stored = bin2hex(random_bytes(8)) . '_' . $name;
$dest = $scenario === 'idor' ? DATA_ROOT . '/private' : PUBLIC_ROOT . '/scenarios/' . $scenario . '/uploads';
if (!is_dir($dest) || !is_writable($dest)) { http_response_code(500); exit('Upload directory is not writable. Check setup instructions.'); }
$target = $dest . '/' . $stored;
if (!move_uploaded_file($f['tmp_name'], $target)) { http_response_code(500); exit('Could not store uploaded file.'); }
try {
  $st = db()->prepare('INSERT INTO files (owner_id, scenario, original_name, stored_name, disk_path, content_type) VALUES (?, ?, ?, ?, ?, ?)');
  $st->execute([(int)$user['id'], $scenario, (string)$f['name'], $stored, $target, (string)($f['type'] ?? 'application/octet-stream')]);
} catch (Throwable $ex) {
  unlink($target); throw $ex;
}
header('Location: /scenarios/' . $scenario . '/?uploaded=1');
exit;
