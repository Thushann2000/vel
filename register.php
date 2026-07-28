<?php
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('account.php'));
    exit;
}
csrf_check();

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$errors = [];
if ($name === '')                               { $errors[] = 'Please enter your name.'; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Please enter a valid email address.'; }
if (strlen($password) < 8)                      { $errors[] = 'Password must be at least 8 characters.'; }

/* Reject duplicate email */
if (!$errors) {
    $check = db()->prepare('SELECT id FROM users WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) {
        $errors[] = 'An account already exists with that email.';
    }
}

if ($errors) {
    flash(implode(' ', $errors), 'error');
    header('Location: ' . base_url('account.php'));
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$ins  = db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)');
$ins->execute([$name, $email, $hash, 'customer']);

session_regenerate_id(true);
$_SESSION['user_id'] = (int) db()->lastInsertId();
flash('Welcome to Velvet Vogue, ' . $name . '.', 'success');
header('Location: ' . base_url('account.php'));
exit;
