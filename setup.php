<?php

require_once __DIR__ . '/includes/functions.php';


const SEED_PLACEHOLDER_HASH = '$2y$10$eImiTXuWVxfM37uY4JANjQ.3z8kQh0Q1xqg8s0h5r0hZ0m9Yq3nOe';

$lockFile  = __DIR__ . '/config/installed.lock';
$locked    = is_file($lockFile);
$errors    = [];
$done      = [];
$completed = false;


$adminName  = 'Site Administrator';
$adminEmail = '';
$makeDemo   = false;

if (!$locked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $adminName  = trim($_POST['admin_name']  ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $pass       = $_POST['admin_password']   ?? '';
    $passAgain  = $_POST['admin_password2']  ?? '';
    $makeDemo   = !empty($_POST['make_demo']);
    $demoPass   = $_POST['demo_password']    ?? '';

    
    if ($adminName === '') {
        $errors[] = 'Please enter a name for the administrator account.';
    }
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid administrator email address.';
    }
    if (strlen($pass) < 8) {
        $errors[] = 'The administrator password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) {
        $errors[] = 'The administrator password must contain at least one letter and one number.';
    }
    if ($pass !== $passAgain) {
        $errors[] = 'The two administrator passwords do not match.';
    }
    if ($makeDemo && strlen($demoPass) < 8) {
        $errors[] = 'The demo customer password must be at least 8 characters.';
    }

    /* ---------------- Write the accounts ---------------- */
    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            /* Administrator — update in place if the email already exists */
            $hash  = password_hash($pass, PASSWORD_DEFAULT);
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $check->execute([$adminEmail]);

            if ($check->fetch()) {
                $pdo->prepare('UPDATE users SET name = ?, password_hash = ?, role = ? WHERE email = ?')
                    ->execute([$adminName, $hash, 'admin', $adminEmail]);
                $done[] = 'Updated the administrator account for ' . $adminEmail;
            } else {
                $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)')
                    ->execute([$adminName, $adminEmail, $hash, 'admin']);
                $done[] = 'Created the administrator account for ' . $adminEmail;
            }

            /* Optional demo customer, for demonstrating the storefront */
            if ($makeDemo) {
                $demoEmail = 'customer@velvetvogue.lk';
                $demoHash  = password_hash($demoPass, PASSWORD_DEFAULT);
                $check->execute([$demoEmail]);
                if ($check->fetch()) {
                    $pdo->prepare('UPDATE users SET name = ?, password_hash = ?, role = ? WHERE email = ?')
                        ->execute(['Demo Customer', $demoHash, 'customer', $demoEmail]);
                    $done[] = 'Updated the demo customer account for ' . $demoEmail;
                } else {
                    $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)')
                        ->execute(['Demo Customer', $demoEmail, $demoHash, 'customer']);
                    $done[] = 'Created the demo customer account for ' . $demoEmail;
                }
            }

            /* Clear any seed account still carrying the unusable placeholder
               hash, so no signed-out account is left behind. */
            $wipe = $pdo->prepare('DELETE FROM users WHERE password_hash = ?');
            $wipe->execute([SEED_PLACEHOLDER_HASH]);
            if ($wipe->rowCount() > 0) {
                $done[] = 'Removed ' . $wipe->rowCount() . ' placeholder account(s) left by the SQL seed file';
            }

            $pdo->commit();

            /* Lock the wizard so it cannot be replayed */
            @mkdir(dirname($lockFile), 0755, true);
            @file_put_contents(
                $lockFile,
                'Velvet Vogue setup completed ' . date('c') . PHP_EOL
            );
            $locked    = is_file($lockFile);
            $completed = true;
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $errors[] = 'Setup could not be completed: ' . $ex->getMessage();
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Velvet Vogue setup</title>
<style>
  :root { --velvet:#800020; --ink:#201b18; --ink-soft:#5c534c; --line:#ddd3c8; --ivory:#f7f3ee; }
  body   { font-family:system-ui,"Segoe UI",sans-serif; max-width:640px; margin:3rem auto;
           padding:0 1.2rem; color:var(--ink); background:var(--ivory); line-height:1.6; }
  h1     { color:var(--velvet); font-size:1.9rem; margin-bottom:.4rem; }
  h2     { font-size:1.05rem; margin:1.8rem 0 .8rem; }
  .card  { background:#fff; border:1px solid var(--line); border-radius:6px; padding:1.6rem; }
  label  { display:block; font-size:.78rem; letter-spacing:.08em; text-transform:uppercase;
           color:var(--ink-soft); margin-bottom:.3rem; }
  input[type=text], input[type=email], input[type=password] {
           width:100%; padding:.65rem .8rem; margin-bottom:1rem; border:1px solid var(--line);
           border-radius:4px; background:var(--ivory); font-size:1rem; }
  input:focus { outline:none; border-color:var(--velvet); background:#fff; }
  button { padding:.8rem 1.6rem; background:var(--velvet); color:#fff; border:0; border-radius:4px;
           font-size:.85rem; letter-spacing:.12em; text-transform:uppercase; cursor:pointer; }
  button:hover { background:#5c0018; }
  .note  { padding:.9rem 1rem; border-radius:4px; font-size:.9rem; margin-bottom:1.2rem; }
  .note--error { background:rgba(128,0,32,.09); color:#5c0018; }
  .note--ok    { background:rgba(45,110,60,.12); color:#2d6e3c; }
  .hint  { font-size:.82rem; color:var(--ink-soft); margin:-.6rem 0 1rem; }
  code   { background:#f0e9df; padding:.1rem .4rem; border-radius:4px; }
  ul     { margin:.6rem 0 0 1.1rem; }
  .check { display:flex; gap:.55rem; align-items:flex-start; margin:1.2rem 0 .8rem; }
  .check input { margin-top:.35rem; }
  .check label { text-transform:none; letter-spacing:0; font-size:.95rem; color:var(--ink); margin:0; }
</style>
</head>
<body>

<h1>Velvet Vogue setup</h1>

<?php if ($completed): ?>

  <div class="card">
    <div class="note note--ok"><strong>Setup complete.</strong> The administrator account is ready to use.</div>
    <ul><?php foreach ($done as $d): ?><li><?= e($d) ?></li><?php endforeach; ?></ul>
    <h2>Two things to do now</h2>
    <ul>
      <li>Delete <code>setup.php</code> from the site folder.</li>
      <li>Keep the password you chose somewhere safe — it is stored only as a hash and cannot be recovered.</li>
    </ul>
    <p style="margin-top:1.4rem">
      <a href="<?= e(base_url('account.php')) ?>">Sign in &rarr;</a> &nbsp;·&nbsp;
      <a href="<?= e(base_url('index.php')) ?>">Go to the store &rarr;</a>
    </p>
  </div>

<?php elseif ($locked): ?>

  <div class="card">
    <div class="note note--error"><strong>Setup has already been run on this installation.</strong></div>
    <p>The wizard will not run a second time, so that it cannot be used to take over the
       administrator account. Delete <code>setup.php</code> from the site folder.</p>
    <p style="margin-top:1rem">If you genuinely need to run setup again — for example after
       reimporting the database — delete <code>config/installed.lock</code> first.</p>
    <p style="margin-top:1.4rem"><a href="<?= e(base_url('index.php')) ?>">Go to the store &rarr;</a></p>
  </div>

<?php else: ?>

  <p style="margin-bottom:1.4rem">Choose the credentials for the administrator account. Nothing is
     preset, and the password is hashed by this PHP installation before it is stored.</p>

  <div class="card">
    <?php if ($errors): ?>
      <div class="note note--error">
        <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>

      <h2 style="margin-top:0">Administrator account</h2>

      <label for="admin_name">Display name</label>
      <input type="text" id="admin_name" name="admin_name" value="<?= e($adminName) ?>" required>

      <label for="admin_email">Email address</label>
      <input type="email" id="admin_email" name="admin_email" value="<?= e($adminEmail) ?>"
             placeholder="you@example.com" required>
      <p class="hint">This is the address you will sign in with.</p>

      <label for="admin_password">Password</label>
      <input type="password" id="admin_password" name="admin_password" required>
      <p class="hint">At least 8 characters, including one letter and one number.</p>

      <label for="admin_password2">Confirm password</label>
      <input type="password" id="admin_password2" name="admin_password2" required>

      <h2>Demo customer account <span style="font-weight:400;color:var(--ink-soft)">(optional)</span></h2>
      <div class="check">
        <input type="checkbox" id="make_demo" name="make_demo" value="1" <?= $makeDemo ? 'checked' : '' ?>>
        <label for="make_demo">Also create <code>customer@velvetvogue.lk</code> for demonstrating the storefront</label>
      </div>

      <label for="demo_password">Demo customer password</label>
      <input type="password" id="demo_password" name="demo_password">
      <p class="hint">Only needed if the box above is ticked. Minimum 8 characters.</p>

      <button type="submit">Complete setup</button>
    </form>
  </div>

<?php endif; ?>

</body>
</html>
