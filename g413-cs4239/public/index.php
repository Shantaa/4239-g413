<?php
require __DIR__ . '/../src/bootstrap.php';
$user = require_user();
page_header('Laboratory dashboard');
?>
<p>Signed in as <strong><?= e($user['username']) ?></strong>. This is one file-management application with three isolated experiment routes.</p>
<div class="cards">
<a class="card" href="/scenarios/rce/"><strong>1. Server-side execution</strong><span>Unrestricted web-accessible uploads</span></a>
<a class="card" href="/scenarios/xss/"><strong>2. Uploaded-content XSS</strong><span>Active documents rendered within the application origin</span></a>
<a class="card" href="/scenarios/idor/"><strong>3. Unauthorized file access</strong><span>Private files retrieved using a file ID</span></a>
</div>
<h2>Research boundary</h2><p>Do not consider acceptance or ordinary retrieval to be evidence of a vulnerability. Member B must demonstrate unauthorized server execution, browser script execution, or cross-user data disclosure.</p>
<?php page_footer(); ?>
