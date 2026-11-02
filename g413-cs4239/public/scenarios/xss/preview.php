<?php
require __DIR__ . '/../../../src/bootstrap.php';
$user = require_user();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$file = $id ? get_file($id) : null;
if (!$file || $file['scenario'] !== 'xss' || (int)$file['owner_id'] !== (int)$user['id']) {
    http_response_code(404); exit('No such preview for this account.');
}
page_header('Preview: ' . $file['original_name']);
?>
<p>Baseline preview: untrusted content is loaded from <strong>the same origin</strong> without sandboxing. The marker below can be changed by a script running in the uploaded SVG/HTML.</p>
<p id="xss-status" class="marker">Browser execution marker: NOT TRIGGERED</p>
<iframe title="Uploaded file preview" class="preview" src="<?= e(file_link($file)) ?>"></iframe>
<p><a href="/scenarios/xss/">Back to XSS uploads</a></p>
<?php page_footer(); ?>
