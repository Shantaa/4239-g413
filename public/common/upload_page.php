<?php
require __DIR__ . '/../../src/bootstrap.php';
$user = require_user();
if (!isset($scenario, $variant) || !in_array($scenario, ['rce', 'xss', 'idor'], true) || !in_array($variant, ['v1', 'v2', 'v3', 'patched'], true)) {
    http_response_code(404); exit('Version not implemented');
}
$headings = [
 'rce' => 'RCE — ' . strtoupper($variant) . ($variant === 'v1' ? ': vulnerable baseline' : ': strict file validation'),
 'xss' => 'XSS — ' . strtoupper($variant),
 'idor' => 'IDOR — ' . strtoupper($variant)
];
$descs = [
 'rce' => $variant === 'v1'
    ? 'Files are stored in a web-accessible, PHP-executable directory with no file-type validation.'
    : 'V2 enforces strict extension allowlisting and finfo MIME type verification.',
 'xss' => $variant === 'v1'
    ? 'SVG/HTML uploads can be opened inside an unsandboxed same-origin preview.'
    : 'Files are stored outside the web root and delivered as downloads with a neutral content type.',
 'idor' => $variant === 'v1'
    ? 'Documents are private in intent, but the download handler intentionally omits ownership authorization.'
    : 'Files use opaque identifiers, but V2 intentionally does not enforce ownership authorization.'
];
page_header($headings[$scenario]);
?>
<p><?= e($descs[$scenario]) ?></p>
<form method="post" enctype="multipart/form-data" action="<?= e(scenario_url($scenario, $variant)) ?>upload.php">
<label>Choose a small test file (maximum 1 MB)<input type="file" name="userfile" required></label>
<button type="submit">Upload lab file</button>
</form>
<h2>Your files in this version</h2>
<table><thead><tr><th><?= $scenario === 'idor' && $variant === 'v2' ? 'Opaque identifier' : 'ID' ?></th><th>Original filename</th><th>Action</th></tr></thead><tbody>
<?php foreach (find_my_files((int)$user['id'], $scenario, $variant) as $file): ?>
<tr><td><?= $scenario === 'idor' && $variant === 'v2' ? e((string)$file['public_token']) : (int)$file['id'] ?></td><td><?= e((string)$file['original_name']) ?></td><td>
<?php if ($scenario === 'xss' && $variant === 'v1'): ?>
<a href="<?= e(scenario_url($scenario, $variant)) ?>preview.php?id=<?= (int)$file['id'] ?>">Preview</a> ·
<?php endif; ?>
<a href="<?= e(file_link($file)) ?>"><?= $scenario === 'idor' ? 'Download' : 'Open uploaded file' ?></a>
</td></tr>
<?php endforeach; ?>
</tbody></table>
<?php if ($scenario === 'idor'): ?>
<p class="note">For the IDOR experiment, upload a private test file as Igor, record its identifier, then sign in as Arcie and request that identifier through this version's download route.</p>
<?php endif; ?>
<p><a href="<?= e(scenario_url($scenario)) ?>">Back to versions</a></p>
<?php page_footer(); ?>
