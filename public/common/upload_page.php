<?php
require __DIR__ . '/../../src/bootstrap.php';
$user = require_user();
$headings = [
 'rce' => 'Investigation 1 — Server execution',
 'xss' => 'Investigation 2 — Uploaded-content XSS',
 'idor' => 'Investigation 3 — Unauthorized file access'
];
$descs = [
 'rce' => 'Baseline: uploaded files are stored in a web-accessible, PHP-enabled directory. No file-type restriction is applied.',
 'xss' => 'Baseline: SVG/HTML uploads are made viewable from the application origin without a browser sandbox.',
 'idor' => 'Baseline: uploaded documents are private in intent, but the download endpoint fails to enforce ownership.'
];
page_header($headings[$scenario]);
?>
<p><?= e($descs[$scenario]) ?></p>
<form method="post" enctype="multipart/form-data" action="/scenarios/<?= e($scenario) ?>/upload.php">
<label>Choose a small test file (maximum 1 MB)<input type="file" name="userfile" required></label>
<button type="submit">Upload lab file</button>
</form>
<h2>Your files in this scenario</h2>
<table><thead><tr><th>ID</th><th>Original filename</th><th>Action</th></tr></thead><tbody>
<?php foreach (find_my_files((int)$user['id'], $scenario) as $file): ?>
<tr><td><?= (int)$file['id'] ?></td><td><?= e($file['original_name']) ?></td><td>
<?php if ($scenario === 'xss'): ?>
<a href="/scenarios/xss/preview.php?id=<?= (int)$file['id'] ?>">Preview</a> ·
<?php endif; ?>
<a href="<?= e(file_link($file)) ?>"><?= $scenario === 'idor' ? 'Download' : 'Open uploaded file' ?></a>
</td></tr>
<?php endforeach; ?>
</tbody></table>
<?php if ($scenario === 'idor'): ?>
<p class="note">This list displays only your own files, but the download handler intentionally omits the ownership check. Record Alice's file ID before signing in as Bob.</p>
<?php endif; ?>
<?php page_footer(); ?>
