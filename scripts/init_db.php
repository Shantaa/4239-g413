<?php
// Fresh database initialization. For an EXISTING lab database, run migrate_versions.php instead.
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
if (!is_dir(DATA_ROOT)) mkdir(DATA_ROOT, 0750, true);
if (!is_dir(DATA_ROOT . '/private/v1')) mkdir(DATA_ROOT . '/private/v1', 0750, true);
if (!is_dir(DATA_ROOT . '/private/v2')) mkdir(DATA_ROOT . '/private/v2', 0750, true);
if (!is_dir(DATA_ROOT . '/private/xss/v2')) mkdir(DATA_ROOT . '/private/xss/v2', 0750, true);
if (!is_dir(DATA_ROOT . '/private/rce/v3')) mkdir(DATA_ROOT . '/private/rce/v3', 0750, true);
if (!is_dir(DATA_ROOT . '/private/xss/v3')) mkdir(DATA_ROOT . '/private/xss/v3', 0750, true);
if (!is_dir(DATA_ROOT . '/private/v3')) mkdir(DATA_ROOT . '/private/v3', 0750, true);
if (!is_dir(DATA_ROOT . '/private/patched')) mkdir(DATA_ROOT . '/private/patched', 0750, true);
if (!is_dir(DATA_ROOT . '/private/rce/patched')) mkdir(DATA_ROOT . '/private/rce/patched', 0750, true);
if (!is_dir(DATA_ROOT . '/private/xss/patched')) mkdir(DATA_ROOT . '/private/xss/patched', 0750, true);
$pdo = db();
$pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE NOT NULL, password_hash TEXT NOT NULL)');
$pdo->exec("CREATE TABLE IF NOT EXISTS files (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    owner_id INTEGER NOT NULL,
    scenario TEXT NOT NULL,
    variant TEXT NOT NULL DEFAULT 'v1',
    original_name TEXT NOT NULL,
    stored_name TEXT NOT NULL,
    disk_path TEXT NOT NULL,
    content_type TEXT NOT NULL,
    public_token TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(owner_id) REFERENCES users(id)
)");
$pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_files_public_token ON files(public_token)');
$insert = $pdo->prepare('INSERT OR IGNORE INTO users (username, password_hash) VALUES (?, ?)');
foreach (['arcie' => 'password123', 'crosley' => 'password123', 'igor' => 'password123'] as $name => $pass) {
    $insert->execute([$name, password_hash($pass, PASSWORD_DEFAULT)]);
}
echo 'Database initialized: ' . DATABASE_FILE . PHP_EOL;
echo 'LAB-ONLY users: arcie, crosley, igor (password123)' . PHP_EOL;
