<?php
require_once __DIR__ . '/includes/functions.php';

$user = current_user();

/* If logged in, load this user's orders (header + items) */
$orders = [];
if ($user) {
    $stmt = db()->prepare(
        'SELECT id, total, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC'
    );
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll();
}

$pageTitle  = 'Account Velvet Vogue';
$activePage = 'account';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--slim">
  <div class="wrap">
    <span class="eyebrow">Members</span>
    <h1><?= $user ? 'Your Account' : 'Sign In or Register' ?></h1>
    <p><?= $user ? 'Manage your details and review past orders.' : 'Sign in to track orders and save your sizes, or create an account to join the Atelier.' ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?php if ($user): ?>
     
      <div class="split" style="align-items:start">
        <div class="split__body">
          <div class="form-card">
            <span class="eyebrow eyebrow--dark">Profile</span>
            <h2 style="margin-bottom:1.4rem">Welcome back, <?= e($user['name']) ?></h2>
            <div class="info-list">
              <div class="info-item"><h4>Name</h4><p><?= e($user['name']) ?></p></div>
              <div class="info-item"><h4>Email</h4><p><?= e($user['email']) ?></p></div>
              <div class="info-item"><h4>Account type</h4><p><?= ucfirst(e($user['role'])) ?></p></div>
            </div>
            <div class="card-actions" style="margin-top:1.6rem">
              <?php if (is_admin()): ?>
                <a class="btn btn--gold" href="<?= e(base_url('admin/index.php')) ?>">Admin Dashboard</a>
              <?php endif; ?>
              <a class="btn" href="<?= e(base_url('logout.php')) ?>">Sign Out</a>
            </div>
          </div>
        </div>

        <div class="split__media" style="align-self:stretch">
          <div class="form-card" style="height:100%">
            <span class="eyebrow eyebrow--dark">Order History</span>
            <h2 style="margin-bottom:1.4rem">Your Orders</h2>
            <?php if (!$orders): ?>
              <p style="color:var(--ink-soft)">You haven't placed any orders yet.</p>
              <a class="btn btn--sm" style="margin-top:1rem" href="<?= e(base_url('products.php')) ?>">Start Shopping</a>
            <?php else: ?>
              <table class="data-table">
                <thead><tr><th>Ref</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>
                  <?php foreach ($orders as $o): ?>
                    <tr>
                      <td>#<?= (int)$o['id'] ?></td>
                      <td><?= e(date('d M Y', strtotime($o['created_at']))) ?></td>
                      <td><?= money($o['total']) ?></td>
                      <td><span class="pill pill--<?= e($o['status']) ?>"><?= ucfirst(e($o['status'])) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>
      </div>

    <?php else: ?>
      <!-- ============ Logged-out: auth tabs ============ -->
      <div class="split" style="align-items:start">
        <div class="split__body">
          <div class="form-card">
            <div class="auth-tabs" role="tablist" aria-label="Account access">
              <button class="auth-tab active" type="button" data-target="panel-login" role="tab">Sign In</button>
              <button class="auth-tab" type="button" data-target="panel-register" role="tab">Register</button>
            </div>

            <!-- Sign in -->
            <div class="auth-panel" id="panel-login" role="tabpanel">
              <form method="post" action="<?= e(base_url('login.php')) ?>" novalidate>
                <?= csrf_field() ?>
                <div class="field">
                  <label for="li-email">Email address</label>
                  <input type="email" id="li-email" name="email" placeholder="you@example.com" required>
                </div>
                <div class="field">
                  <label for="li-pass">Password</label>
                  <input type="password" id="li-pass" name="password" placeholder="Your password" required>
                </div>
                <button class="btn btn--block" type="submit">Sign In</button>
                <p class="cart-note" style="color:var(--ink-soft)">Demo admin: admin@velvetvogue.lk · Demo customer: customer@velvetvogue.lk (password set by setup.php).</p>
              </form>
            </div>

            <!-- Register -->
            <div class="auth-panel is-hidden" id="panel-register" role="tabpanel">
              <form method="post" action="<?= e(base_url('register.php')) ?>" novalidate>
                <?= csrf_field() ?>
                <div class="field">
                  <label for="rg-name">Full name</label>
                  <input type="text" id="rg-name" name="name" placeholder="Your name" required>
                </div>
                <div class="field">
                  <label for="rg-email">Email address</label>
                  <input type="email" id="rg-email" name="email" placeholder="you@example.com" required>
                </div>
                <div class="field">
                  <label for="rg-pass">Create password</label>
                  <input type="password" id="rg-pass" name="password" placeholder="At least 8 characters" required>
                </div>
                <button class="btn btn--gold btn--block" type="submit">Create Account</button>
              </form>
            </div>
          </div>
        </div>

        <div class="split__media" style="align-self:stretch">
          <div class="form-card" style="height:100%">
            <span class="eyebrow eyebrow--dark">Why Join</span>
            <h2 style="margin-bottom:1.6rem">The Atelier</h2>
            <div class="info-list">
              <div class="info-item"><h4>Order Tracking</h4><p>Follow every order from the studio to your door.</p></div>
              <div class="info-item"><h4>Saved Details</h4><p>Check out faster with your details on file.</p></div>
              <div class="info-item"><h4>Private Previews</h4><p>First access to new arrivals and limited pieces.</p></div>
              <div class="info-item"><h4>Styling Notes</h4><p>Seasonal edits and care guides from our team.</p></div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
