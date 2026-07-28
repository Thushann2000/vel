<?php
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('account.php'));
    exit;
}
csrf_check();

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = db()->prepare('SELECT id, name, password_hash, role FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    // Prevent session fixation
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    flash('Welcome back, ' . $user['name'] . '.', 'success');
    header('Location: ' . base_url($user['role'] === 'admin' ? 'admin/index.php' : 'account.php'));
    exit;
}

flash('Those credentials did not match our records.', 'error');
header('Location: ' . base_url('account.php'));
exit;
