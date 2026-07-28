<?php
require_once __DIR__ . '/includes/admin-guard.php';

$pdo = db();
$stats = [
    'products' => (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn(),
    'orders'   => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'revenue'  => (float) $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ('paid','shipped')")->fetchColumn(),
    'customers'=> (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn(),
];
$recent = $pdo->query(
    'SELECT o.id, o.full_name, o.total, o.status, o.created_at
     FROM orders o ORDER BY o.created_at DESC LIMIT 6'
)->fetchAll();

$pageTitle  = 'Admin Dashboard Velvet Vogue';
$activePage = 'admin';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/_nav.php';
?>

<section class="section">
  <div class="wrap">
    <header class="section-head">
      <span class="eyebrow eyebrow--dark">Administration</span>
      <h2>Dashboard</h2>
      <hr class="gold-rule">
    </header>

    <div class="stat-grid">
      <div class="stat-card"><span class="stat-card__label">Active Products</span><strong><?= $stats['products'] ?></strong></div>
      <div class="stat-card"><span class="stat-card__label">Total Orders</span><strong><?= $stats['orders'] ?></strong></div>
      <div class="stat-card"><span class="stat-card__label">Revenue</span><strong><?= money($stats['revenue']) ?></strong></div>
      <div class="stat-card"><span class="stat-card__label">Customers</span><strong><?= $stats['customers'] ?></strong></div>
    </div>

    <div class="form-card" style="margin-top:2.5rem">
      <div class="admin-card-head">
        <h3>Recent Orders</h3>
        <a class="btn btn--sm" href="<?= e(base_url('admin/orders.php')) ?>">View all</a>
      </div>
      <?php if (!$recent): ?>
        <p style="color:var(--ink-soft)">No orders yet.</p>
      <?php else: ?>
        <table class="data-table">
          <thead><tr><th>Ref</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
          <tbody>
            <?php foreach ($recent as $o): ?>
              <tr>
                <td>#<?= (int)$o['id'] ?></td>
                <td><?= e($o['full_name']) ?></td>
                <td><?= money($o['total']) ?></td>
                <td><span class="pill pill--<?= e($o['status']) ?>"><?= ucfirst(e($o['status'])) ?></span></td>
                <td><?= e(date('d M Y', strtotime($o['created_at']))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
