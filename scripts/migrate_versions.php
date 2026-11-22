<?php
// Non-destructive migration for an EXISTING lab.sqlite. Back up the file first.
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
if (!is_file(DATABASE_FILE)) {
    fwrite(STDERR, 'No existing lab.sqlite found; use init_db.php for a new database.' . PHP_EOL);
    exit(1);
}
$pdo = db();
$cols = $pdo->query('PRAGMA table_info(files)')->fetchAll();
$hasVariant = false;
foreach ($cols as $col) {
    if ($col['name'] === 'variant') { $hasVariant = true; break; }
}
if (!$hasVariant) {
    $pdo->exec("ALTER TABLE files ADD COLUMN variant TEXT NOT NULL DEFAULT 'v1'");
    echo 'Added files.variant with default v1.' . PHP_EOL;
} else {
    echo 'files.variant already exists.' . PHP_EOL;
}
$hasToken = false;
foreach ($cols as $col) {
    if ($col['name'] === 'public_token') { $hasToken = true; break; }
}
if (!$hasToken) {
    $pdo->exec("ALTER TABLE files ADD COLUMN public_token TEXT");
    echo 'Added files.public_token.' . PHP_EOL;
}
$pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_files_public_token ON files(public_token)');
$pdo->exec("UPDATE files SET variant = 'v1' WHERE variant IS NULL OR variant = ''");
if (!is_dir(DATA_ROOT . '/private/v1') && !mkdir(DATA_ROOT . '/private/v1', 0750, true)) {
    throw new RuntimeException('Could not create data/private/v1');
}
if (!is_dir(DATA_ROOT . '/private/v2') && !mkdir(DATA_ROOT . '/private/v2', 0750, true)) {
    throw new RuntimeException('Could not create data/private/v2');
}
if (!is_dir(DATA_ROOT . '/private/xss/v2') && !mkdir(DATA_ROOT . '/private/xss/v2', 0750, true)) {
    throw new RuntimeException('Could not create data/private/xss/v2');
}
if (!is_dir(DATA_ROOT . '/private/rce/v3') && !mkdir(DATA_ROOT . '/private/rce/v3', 0750, true)) {
    throw new RuntimeException('Could not create data/private/rce/v3');
}
if (!is_dir(DATA_ROOT . '/private/xss/v3') && !mkdir(DATA_ROOT . '/private/xss/v3', 0750, true)) {
    throw new RuntimeException('Could not create data/private/xss/v3');
}
if (!is_dir(DATA_ROOT . '/private/v3') && !mkdir(DATA_ROOT . '/private/v3', 0750, true)) {
    throw new RuntimeException('Could not create data/private/v3');
}
if (!is_dir(DATA_ROOT . '/private/patched') && !mkdir(DATA_ROOT . '/private/patched', 0750, true)) {
    throw new RuntimeException('Could not create data/private/patched');
}
if (!is_dir(DATA_ROOT . '/private/rce/patched') && !mkdir(DATA_ROOT . '/private/rce/patched', 0750, true)) {
    throw new RuntimeException('Could not create data/private/rce/patched');
}
if (!is_dir(DATA_ROOT . '/private/xss/patched') && !mkdir(DATA_ROOT . '/private/xss/patched', 0750, true)) {
    throw new RuntimeException('Could not create data/private/xss/patched');
}
echo 'Existing uploads and users preserved. Migration complete.' . PHP_EOL;
