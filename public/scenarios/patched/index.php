<?php
require __DIR__ . '/../../../src/bootstrap.php';
require_user();
page_header('Final integrated application');
?>
<p>This combined hardened build applies the patched controls to all three investigations. Upload and retrieve files from one of the scenario-specific controls below.</p>
<div class="cards">
    <a class="card" href="/scenarios/patched/rce/"><strong>RCE controls</strong><span>Allowlisted private upload with octet-stream delivery</span></a>
    <a class="card" href="/scenarios/patched/xss/"><strong>XSS controls</strong><span>Private preview with restrictive CSP and sandbox directives</span></a>
    <a class="card" href="/scenarios/patched/idor/"><strong>IDOR controls</strong><span>Opaque 32-byte token plus owner authorization</span></a>
</div>
<p><a href="/">Back to dashboard</a></p>
<?php page_footer(); ?>
