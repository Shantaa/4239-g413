<?php
require __DIR__ . '/../src/bootstrap.php';
$user = require_user();
page_header('Laboratory dashboard');
?>
<p>Signed in as <strong><?= e((string)$user['username']) ?></strong>. Explore three security investigations, with separate versions for each defence.</p>
<div class="cards">
    <a class="card" href="/scenarios/rce/"><strong>1. Server-side code execution</strong><span>Upload handling and server execution policies</span></a>
    <a class="card" href="/scenarios/xss/"><strong>2. Stored XSS</strong><span>Rendering and delivery of uploaded active content</span></a>
    <a class="card" href="/scenarios/idor/"><strong>3. Unauthorized file access</strong><span>Retrieval and ownership checks</span></a>
    <a class="card" href="/scenarios/patched/"><strong>Final integrated application</strong><span>Combined defences (not implemented yet)</span></a>
</div>
<?php page_footer(); ?>
