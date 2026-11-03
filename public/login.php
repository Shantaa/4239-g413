<?php
require __DIR__ . '/../src/bootstrap.php';
if (current_user()) { header('Location: /'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = db()->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([trim((string)($_POST['username'] ?? ''))]);
    $user = $st->fetch();
    if ($user && password_verify((string)($_POST['password'] ?? ''), $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        header('Location: /'); exit;
    }
    $error = 'Invalid lab credentials.';
}
page_header('Sign in');
if ($error) echo '<p class="error">' . e($error) . '</p>';
?>
<form method="post"><label>Username <input name="username" required autocomplete="username"></label>
<label>Password <input type="password" name="password" required autocomplete="current-password"></label>
<button type="submit">Sign in</button></form>
<p>
    Demo accounts:
    <code>arcie</code>,
    <code>crosley</code>,
    <code>igor</code>
    (Password: <code>password123</code>)
</p>
<?php page_footer(); ?>
