<?php
require __DIR__ . '/../../src/bootstrap.php';
require_user();
$catalog = [
    'rce' => [
        'title' => 'Investigation 1 — Server-side code execution',
        'description' => 'Compare upload validation with execution isolation. The primary security outcome is unauthorized server-side PHP execution.',
        'versions' => [
            'v1' => ['Vulnerable baseline', 'Unrestricted upload into a PHP-executable directory.', true],
            'v2' => ['Upload validation', 'Extension and server-side content checks.', true],
            'v3' => ['Execution isolation', 'Non-executable storage and controlled delivery (planned).', false],
        ],
    ],
    'xss' => [
        'title' => 'Investigation 2 — Stored XSS',
        'description' => 'Compare unsafe rendering with safe delivery and browser-side restrictions. The security outcome is script execution in the application origin.',
        'versions' => [
            'v1' => ['Vulnerable baseline', 'Uploaded SVG/HTML executed in a same-origin preview.', true],
            'v2' => ['Safe file delivery', 'Attachment downloads and safe content handling.', true],
            'v3' => ['Content Security Policy', 'Browser script restrictions in the preview context (planned).', false],
        ],
    ],
    'idor' => [
        'title' => 'Investigation 3 — Unauthorized file access',
        'description' => 'Compare obscuring identifiers with enforcing authorization. The outcome is whether one user can read another user’s private file.',
        'versions' => [
            'v1' => ['Vulnerable baseline', 'Sequential IDs with no per-file ownership check.', true],
            'v2' => ['Unpredictable identifiers', 'Opaque file identifiers without ownership checks.', true],
            'v3' => ['Ownership authorization', 'Enforce object-level access control (planned).', false],
        ],
    ],
];
if (!isset($scenario, $catalog[$scenario])) { http_response_code(404); exit('Unknown investigation'); }
$config = $catalog[$scenario];
page_header($config['title']);
?>
<p><?= e($config['description']) ?></p>
<div class="cards">
<?php foreach ($config['versions'] as $version => $details): ?>
    <a class="card" href="<?= e(scenario_url($scenario, $version)) ?>">
       <strong><?= e(strtoupper($version)) ?> — <?= e($details[0]) ?></strong>
       <span><?= e($details[1]) ?></span>
       <span><?= $details[2] ? 'Available now' : 'Placeholder — not implemented' ?></span>
    </a>
<?php endforeach; ?>
</div>
<p><a href="/">Back to dashboard</a></p>
<?php page_footer(); ?>
