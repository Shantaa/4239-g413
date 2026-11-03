<?php
// All code in this project is intended for an ISOLATED, localhost-only security lab.
declare(strict_types=1);
const LAB_ROOT = __DIR__ . '/..';
const PUBLIC_ROOT = LAB_ROOT . '/public';
const DATA_ROOT = LAB_ROOT . '/data';
const DATABASE_FILE = DATA_ROOT . '/lab.sqlite';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DATABASE_FILE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
    return $pdo;
}
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function current_user(): ?array {
    if (!isset($_SESSION['user_id'])) return null;
    $st = db()->prepare('SELECT id, username FROM users WHERE id = ?');
    $st->execute([$_SESSION['user_id']]);
    $row = $st->fetch();
    return $row ?: null;
}
function require_user(): array {
    $user = current_user();
    if ($user === null) {
        header('Location: /login.php');
        exit;
    }
    return $user;
}
function get_file(int $id): ?array {
    $st = db()->prepare('SELECT * FROM files WHERE id = ?');
    $st->execute([$id]);
    $f = $st->fetch();
    return $f ?: null;
}
function find_my_files(int $owner, string $scenario): array {
    $st = db()->prepare('SELECT * FROM files WHERE owner_id = ? AND scenario = ? ORDER BY id DESC');
    $st->execute([$owner, $scenario]);
    return $st->fetchAll();
}
function file_link(array $f): string {
    if ($f['scenario'] === 'idor') return '/scenarios/idor/download.php?id=' . $f['id'];
    return '/scenarios/' . $f['scenario'] . '/uploads/' . rawurlencode($f['stored_name']);
}
function page_header(string $title): void {
    $user = current_user();
    $name = $user ? e((string)$user['username']) : 'guest';
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e($title) . ' | CS4239 Lab</title><link rel="stylesheet" href="/assets/lab.css"></head><body><div class="wrap">';
    echo '<header><strong>CS4239 File Lifecycle Lab</strong><nav><a href="/">Home</a> · <span>' . $name . '</span> · ';
    echo ($user ? '<a href="/logout.php">Log out</a>' : '<a href="/login.php">Log in</a>') . '</nav></header>';
    echo '<main><h1>' . e($title) . '</h1>';
}
function page_footer(): void { echo '</main><footer>Local teaching environment · Unhardened baseline</footer></div></body></html>'; }
