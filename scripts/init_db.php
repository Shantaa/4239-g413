<?php
// Use this on a NEW lab database only. Does not reset an existing database.
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
if (!is_dir(DATA_ROOT)) mkdir(DATA_ROOT, 0750, true);
if (!is_dir(DATA_ROOT . '/private')) mkdir(DATA_ROOT . '/private', 0750, true);
$pdo = db();
$pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE NOT NULL, password_hash TEXT NOT NULL)');
$pdo->exec('CREATE TABLE IF NOT EXISTS files (id INTEGER PRIMARY KEY AUTOINCREMENT, owner_id INTEGER NOT NULL, scenario TEXT NOT NULL, original_name TEXT NOT NULL, stored_name TEXT NOT NULL, disk_path TEXT NOT NULL, content_type TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(owner_id) REFERENCES users(id))');
$insert = $pdo->prepare('INSERT OR IGNORE INTO users (username, password_hash) VALUES (?, ?)');
$accounts = [
    'arcie'   => 'password123',
    'crosley' => 'password123',
    'igor'    => 'password123'
];

foreach ($accounts as $name => $pass) {
    $insert->execute([
        $name,
        password_hash($pass, PASSWORD_DEFAULT)
    ]);
}

echo "Database initialized: " . DATABASE_FILE . PHP_EOL;
echo "LAB-ONLY accounts: arcie, crosley, igor" . PHP_EOL;
