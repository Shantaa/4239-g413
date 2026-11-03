<?php
require __DIR__ . '/../../src/bootstrap.php';
require_user();
if (!isset($scenario, $variant) || !in_array($scenario, ['rce', 'xss', 'idor'], true)
    || !preg_match('/^v[2-9][0-9]*$/', $variant)) { http_response_code(404); exit('Unknown experiment'); }
page_header(strtoupper($scenario) . ' — ' . strtoupper($variant) . ' (planned)');
?>
<p>This is a placeholder for a future defensive implementation. No security control or exploit outcome has been evaluated on this page.</p>
<p><a href="<?= e(scenario_url($scenario)) ?>">Back to versions</a></p>
<?php page_footer(); ?>
