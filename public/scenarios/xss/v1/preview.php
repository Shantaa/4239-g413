<?php
require __DIR__ . '/../../../../src/bootstrap.php';
$user = require_user();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$file = $id ? get_file($id) : null;
if (!$file || $file['scenario'] !== 'xss' || ($file['variant'] ?? 'v1') !== 'v1'
    || (int)$file['owner_id'] !== (int)$user['id']) {
    http_response_code(404); exit('No such preview for this account.');
}
page_header('Preview: ' . (string)$file['original_name']);
?>
<p>The V1 preview loads untrusted content from the same origin in an unsandboxed iframe.</p>
<p id="xss-status" class="marker">Browser execution marker: NOT TRIGGERED</p>
<iframe title="Uploaded file preview" class="preview" src="<?= e(file_link($file)) ?>"></iframe>
<p><a href="/scenarios/xss/v1/">Back to XSS V1</a></p>
<?php page_footer(); ?>
