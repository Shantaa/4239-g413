<?php
require __DIR__ . '/../../src/bootstrap.php';
$user = require_user();

// UPDATE VERSION GATE
if (!isset($scenario, $variant) || !in_array($scenario, ['rce', 'xss', 'idor'], true) || !in_array($variant, ['v1', 'v2', 'v3', 'patched'], true)) {
    http_response_code(404); exit('Version not implemented');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('POST only'); }
if (!isset($_FILES['userfile']) || !is_array($_FILES['userfile']) || $_FILES['userfile']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400); exit('Upload failed or was rejected by PHP upload settings.');
}

$f = $_FILES['userfile'];
if ($f['size'] > 1024*1024) { http_response_code(413); exit('Lab limit: 1 MB'); }

$name = basename(str_replace('\\', '/', (string)$f['name']));
$name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
if ($name === '' || $name === '.' || $name === '..') { http_response_code(400); exit('Invalid filename'); }

// V2 RCE DEFENCE
if ($scenario === 'rce' && $variant === 'v2') {
    // Strict Extension Allowlist
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed_exts = ['txt', 'png', 'jpg', 'jpeg', 'pdf'];

    if (!in_array($ext, $allowed_exts, true)) {
        http_response_code(403);
        exit('V2 Defence Blocked: File extension .' . htmlspecialchars($ext) . ' is not allowed.');
    }

    // Strict Content/MIME Type Verification using finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $f['tmp_name']);
    finfo_close($finfo);

    $allowed_mimes = ['text/plain', 'image/png', 'image/jpeg', 'application/pdf'];
    if (!in_array($mime, $allowed_mimes, true)) {
        http_response_code(403);
        exit('V2 Defence Blocked: Invalid file content type (' . htmlspecialchars($mime) . ').');
    }
}

// V2/V3 storage defences: keep hardened uploads outside the web root. V1
// retains its intentionally vulnerable public storage for comparison.
$stored = bin2hex(random_bytes(8)) . '_' . $name;
$private_storage = $scenario === 'idor'
    || ($scenario === 'xss' && in_array($variant, ['v2', 'v3'], true))
    || ($scenario === 'rce' && $variant === 'v3');
$dest = $private_storage
    ? DATA_ROOT . '/private/' . ($scenario === 'xss' || $scenario === 'rce' ? $scenario . '/' : '') . $variant
    : PUBLIC_ROOT . '/scenarios/' . $scenario . '/' . $variant . '/uploads';

if (!is_dir($dest) || !is_writable($dest)) { http_response_code(500); exit('Upload directory is not writable.'); }
$target = $dest . '/' . $stored;

if (!move_uploaded_file($f['tmp_name'], $target)) { http_response_code(500); exit('Could not store uploaded file.'); }

try {
    // IDOR V2/V3 use opaque tokens instead of exposing sequential DB IDs.
    // V3 additionally checks ownership when the token is used for download.
    $token = ($scenario === 'idor' && in_array($variant, ['v2', 'v3'], true))
        ? bin2hex(random_bytes(16))
        : null;
    $st = db()->prepare('INSERT INTO files (owner_id, scenario, variant, original_name, stored_name, disk_path, content_type, public_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    // Use the validated finfo $mime for DB storage if V2, otherwise fall back to the unverified client type
    $db_mime = ($scenario === 'rce' && $variant === 'v2') ? $mime : (string)($f['type'] ?? 'application/octet-stream');
    $st->execute([(int)$user['id'], $scenario, $variant, (string)$f['name'], $stored, $target, $db_mime, $token]);
} catch (Throwable $ex) {
    unlink($target); throw $ex;
}

header('Location: ' . scenario_url($scenario, $variant) . '?uploaded=1');
exit;